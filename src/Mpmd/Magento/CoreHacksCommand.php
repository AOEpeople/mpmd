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
class CoreHacksCommand extends \N98\Magento\Command\AbstractMagentoCommand
{
    /** @var InputInterface $input */
    protected $_input;

    /** @var OutputInterface $input */
    protected $_output;

    protected function configure()
    {
        $this
            ->setName('mpmd:corehacks')
            ->addArgument('pathToVanillaCore', InputArgument::REQUIRED, 'Path to Vanilla Core used for comparison')
            ->addArgument('htmlReportOutputPath', InputArgument::OPTIONAL, 'Path to where the HTML report will be written')
            ->addArgument('skipDirectories', InputArgument::OPTIONAL, '\''.PATH_SEPARATOR.'\'-separated list of directories that will not be considered (defaults to \'.svn'.PATH_SEPARATOR.'.git\')')
            ->setDescription('Find all core hacks')
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

        $pathToVanillaCore = $this->_input->getArgument('pathToVanillaCore');

        if (!is_file($pathToVanillaCore . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'Mage.php')) {
            throw new \Exception('Could not find vanilla Magento core in ' . $pathToVanillaCore);
        }

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

        $table = array();
        foreach ($data as $type => $items) {
            $table[] = array('Type' => $type, 'Count' => count($items));
        }

        $this->getHelper('table')
            ->setHeaders(array('Type', 'Count'))
            ->renderByFormat($output, $table, $input->getOption('format'));

        $htmlReportOutputPath = $this->_input->getArgument('htmlReportOutputPath');

        if ($htmlReportOutputPath) {

            foreach (array(\Mpmd\Util\Compare::DIFFERENT_FILE_CONTENT, \Mpmd\Util\Compare::SAME_FILE_BUT_COMMENTS) as $section) {
                $diffs[$section] = $compareUtil->getDiffs(
                    $data[$section],
                    $pathToVanillaCore,
                    $this->_magentoRootFolder
                );
            }

            $this->_output->writeln("<info>Generating detailed HTML Report</info>");

            $htmlReport = new \Mpmd\Util\Report();
            $htmlReport->addHeadline('Magento Project Mess Detector: Core Hacks Report');
            $htmlReport->addTag('p', "Comparing <strong>$pathToVanillaCore</strong> to <strong>{$this->_magentoRootFolder}</strong>");

            $htmlTableRenderer = new \Mpmd\Util\HtmlTableRenderer();

            foreach ($table as &$row) {
                $row['Type'] = '<a href="#'.$row['Type'].'">'.$row['Type'].'</a>';
            }

            $htmlReport->addHeadline('Summary', 2);

            unset($data[\Mpmd\Util\Compare::IDENTICAL_FILES]);

            $htmlReport->addBody($htmlTableRenderer->render($table, array('Type' , 'Count')));

            $htmlReport->addDiffData($data, $diffs, 'vanilla core', 'project');

            file_put_contents($htmlReportOutputPath, $htmlReport->render());
            $this->_output->writeln("<info>Writing HTML report to: $htmlReportOutputPath</info>");
        }

        // if ($output->getVerbosity() >= OutputInterface::VERBOSITY_VERBOSE) {
    }

}