<?php

namespace Mpmd\Magento;

use N98\Magento\Application;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use N98\Util\Console\Helper\Table\Renderer\RendererFactory;

/**
 * Class CoreHacksCommand
 * @package Mpmd\Magento
 *
 * @author Fabrizio Branca
 * @since 2014-11-24
 */
class CodePoolOverridesCommand extends \N98\Magento\Command\AbstractMagentoCommand
{
    /** @var InputInterface $input */
    protected $_input;

    /** @var OutputInterface $input */
    protected $_output;

    protected function configure()
    {
        $this
            ->setName('mpmd:codepooloverrides')
            ->addArgument('htmlReportOutputPath', InputArgument::OPTIONAL, 'Path to where the HTML report will be written')
            ->addArgument('skipDirectories', InputArgument::OPTIONAL, '\''.PATH_SEPARATOR.'\'-separated list of directories that will not be considered (defaults to \'.svn'.PATH_SEPARATOR.'.git\')')
            ->setDescription('Find all code pool overrides')
            ->addOption(
                'format',
                null,
                InputOption::VALUE_OPTIONAL,
                'Output Format. One of [' . implode(',', RendererFactory::getFormats()) . ']'
            )
        ;
    }

    /**
     * Execute command
     *
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @throws \Exception
     * @return void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->_input = $input;
        $this->_output = $output;
        $this->detectMagento($output, true);
        $this->initMagento();

        $this->_output->writeln("<info>Comparing project files in '$this->_magentoRootFolder' to vanilla Magento code in '$pathToVanillaCore'...</info>");

        $skipDirectories = $this->_input->getArgument('skipDirectories');

        if (empty($skipDirectories)) {
            $skipDirectories = '.svn' . PATH_SEPARATOR . '.git';
        }
        $compareUtil = new \Mpmd\Util\Compare();

        $data = $compareUtil->compareDirectories(
            $pathToVanillaCore,
            $this->_magentoRootFolder,
            '',
            explode(PATH_SEPARATOR, $skipDirectories)
        );

        $pools = array(
            0 => 'local',
            1 => 'community',
            2 => 'core',
            3 => 'lib'
        );

        $data = array();
        $diffs = array();
        foreach ($pools as $sourcePoolKey => $sourcePoolName) {
            foreach ($pools as $targetPoolKey => $targetPoolName) {
                if ($targetPoolKey > $sourcePoolKey) {
                    $this->_output->writeln("<info>Comparing $sourcePoolName to $targetPoolName</info>");
                    $tmp = $compareUtil->compareDirectories(
                        $this->getCodePoolPath($sourcePoolName),
                        $this->getCodePoolPath($targetPoolName),
                        '',
                        explode(PATH_SEPARATOR, $skipDirectories)
                    );
                    if (isset($tmp[\Mpmd\Util\Compare::FILE_MISSING_IN_B])) {
                        unset($tmp[\Mpmd\Util\Compare::FILE_MISSING_IN_B]);
                    }
                    if (isset($tmp[\Mpmd\Util\Compare::FILE_MISSING_IN_A])) {
                        unset($tmp[\Mpmd\Util\Compare::FILE_MISSING_IN_A]);
                    }

                    // get diffs
                    foreach (array(\Mpmd\Util\Compare::DIFFERENT_FILE_CONTENT, \Mpmd\Util\Compare::SAME_FILE_BUT_COMMENTS) as $section) {
                        $diffs["$sourcePoolName-$targetPoolName"][$section] = $compareUtil->getDiffs(
                            $tmp[$section],
                            $this->getCodePoolPath($sourcePoolName),
                            $this->getCodePoolPath($targetPoolName)
                        );
                    }

                    // Print table
                    $table = array();
                    foreach ($tmp as $type => $items) {
                        $table[] = array('Type' => $type, 'Count' => count($items));
                    }
                    $this->getHelper('table')->setHeaders(array('Type', 'Count'))->renderByFormat($output, $table, $input->getOption('format'));

                    $data["$sourcePoolName-$targetPoolName"] = $tmp;
                }
            }
        }

        $htmlReportOutputPath = $this->_input->getArgument('htmlReportOutputPath');

        if ($htmlReportOutputPath) {

            $this->_output->writeln("<info>Generating detailed HTML Report</info>");

            $htmlReport = new \Mpmd\Util\Report();
            $htmlReport->addHeadline('Magento Project Mess Detector: Code Pool Override Report');

            $table = '<table>';
            $table .= '<tr>';
            $table .= '<th></th>';
            foreach ($pools as $targetPoolKey => $targetPoolName) {
                $table .= '<th>'.$targetPoolName.'</th>';
            }
            $table .= '</tr>';
            foreach ($pools as $sourcePoolKey => $sourcePoolName) {
                $table .= '<tr>';
                $table .= '<th>'.$sourcePoolName.'</th>';
                foreach ($pools as $targetPoolKey => $targetPoolName) {
                    if ($targetPoolKey > $sourcePoolKey) {
                        if (array_sum(array_map('count', $data["$sourcePoolName-$targetPoolName"])) > 0) {
                            $id = "$sourcePoolName-$targetPoolName";
                            $tmp = array();
                            foreach ($data[$id] as $key => $d) {
                                $c = count($d);
                                if ($c > 0) {
                                    $tmp[] = '<a href="#'.$id.'-'.$key.'" title="'.$key.'">'.$c.'</a>';
                                } else {
                                    $tmp[] = '<span title="'.$key.'">'.$c.'</span>';
                                }
                            }
                            $table .= '<td>' . implode(' / ', $tmp). '</td>';
                        } else {
                            $table .= '<td class="ok">&#10004;</td>';
                        }

                    } else {
                        $table .= '<td class="invalid"></td>';
                    }
                }
                $table .= '</tr>';
            }
            $table .= '</table>';
            $htmlReport->addBody($table);

            foreach ($pools as $sourcePoolKey => $sourcePoolName) {
                foreach ($pools as $targetPoolKey => $targetPoolName) {
                    if ($targetPoolKey > $sourcePoolKey) {
                        if (array_sum(array_map('count', $data["$sourcePoolName-$targetPoolName"])) > 0) {
                            $id = "$sourcePoolName-$targetPoolName";
                            $htmlReport->addHeadline("Comparing $sourcePoolName and $targetPoolName", 2, array('id' => $id.'-'));
                            $htmlReport->addDiffData($data["$sourcePoolName-$targetPoolName"], $diffs["$sourcePoolName-$targetPoolName"], $sourcePoolName, $targetPoolName, $id.'-');
                        }
                    }
                }
            }

            file_put_contents($htmlReportOutputPath, $htmlReport->render());
            $this->_output->writeln("<info>Writing HTML report to: $htmlReportOutputPath</info>");
        }
    }

    /**
     * Get code pool path
     *
     * @param $pool
     * @return mixed
     * @throws \Exception
     */
    protected function getCodePoolPath($pool) {
        $code = rtrim(realpath(\Mage::getBaseDir('code')), '/') . '/';
        $lib = rtrim(realpath(\Mage::getBaseDir('lib')), '/') . '/';
        $paths = array(
            'local' => $code . 'local',
            'community' => $code . 'community',
            'core' => $code . 'core',
            'lib' => $lib
        );
        if (!array_key_exists($pool, $paths)) {
            throw new \Exception('Could not find pool');
        }
        return $paths[$pool];
    }

}