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

            $this->_output->writeln("<info>Generating detailed HTML Report</info>");

            $htmlTableRenderer = new \Mpmd\Util\HtmlTableRenderer();

            $output = '<html>
                <head>
                    <script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js"></script>
                    <script type="text/javascript">
                        $(function () {
                            $("pre.diff").before("<a class=\\"diff-toggle\\">[Toggle]</a>").hide();
                            $(".diff-toggle").click(function () {
                                $(this).next().toggle();
                                return false;
                            });
                        })
                    </script>
                    <style type="text/css">
                        body { font-family: Arial, sans-serif; font-size: 10pt; padding: 20px; }
                        a, a:visited, a:hover { color: #E26912; text-decoration: underline; }
                        table { background-color: whiteSmoke;  border-collapse: collapse; border-spacing: 0; margin-bottom: 10px; margin-top: 3px; border: 1px solid #fff; }
                        table td, table th { padding: 0 20px; line-height: 20px; color: #0084B4; font-family: \'Helvetica Neue\', Helvetica, Arial, sans-serif; font-size: 14px; border-bottom: 1px solid #fff; border-top: 1px solid #fff; text-align: left; }
                        table td:hover { background-color: #fff; }
                        table th { background-color: #ccc; color: #333; font-weight: bold; }
                        pre.diff { color: black; font-size: smaller; line-height: normal; }
                    </style>
                </head>
                <body>
                    <h1>Magento Project Mess Detector: Core Hacks Report</h1>';



            foreach ($table as &$row) {
                $row['Type'] = '<a href="#'.$row['Type'].'">'.$row['Type'].'</a>';
            }


            $output .= '<h2>Overview</h2>';
            $output .= $htmlTableRenderer->render($table, array('Type' , 'Count'));


            $output .= '<h3 id="'.\Mpmd\Util\Compare::FILE_MISSING_IN_A.'">Additional files in ' . $this->_magentoRootFolder . ':</h3>';
            $output .= "<p>Since this would also include extra modules and themes this is currently skipped</p>";
            // TODO: come up with a smart solution to only include some directories (e.g. app/code/core, the original designs and skins,...)


            $missingFiles = array();
            foreach ($data[\Mpmd\Util\Compare::FILE_MISSING_IN_B] as $file) {
                $missingFiles[] = array(
                    'File' => $file
                );
            }
            $output .= '<h3 id="'.\Mpmd\Util\Compare::FILE_MISSING_IN_B.'">Missing files in ' . $this->_magentoRootFolder . ':</h3>';
            $output .= $htmlTableRenderer->render($missingFiles, array('File'));

            $output .= '<h3 id="'.\Mpmd\Util\Compare::DIFFERENT_FILE_CONTENT.'">Changed files:</h3>';
            $diffs = $compareUtil->getDiffs(
                $data[\Mpmd\Util\Compare::DIFFERENT_FILE_CONTENT],
                $pathToVanillaCore,
                $this->_magentoRootFolder
            );
            $output .= $htmlTableRenderer->render($diffs, array('File', 'Diff'));

            $output .= '<h3 id="'.\Mpmd\Util\Compare::SAME_FILE_BUT_COMMENTS.'">Changed files (comments only):</h3>';
            $diffs = $compareUtil->getDiffs(
                $data[\Mpmd\Util\Compare::SAME_FILE_BUT_COMMENTS],
                $pathToVanillaCore,
                $this->_magentoRootFolder
            );
            $output .= $htmlTableRenderer->render($diffs, array('File', 'Diff'));

            $output .= '</body>';

            file_put_contents($htmlReportOutputPath, $output);
            $this->_output->writeln("<info>Writing HTML report to: $htmlReportOutputPath</info>");
        }

        // if ($output->getVerbosity() >= OutputInterface::VERBOSITY_VERBOSE) {
    }

}