<?php

namespace Mpmd\Magento;

use N98\Magento\Application;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use N98\Util\Console\Helper\Table\Renderer\RendererFactory;

/**
 * Class DependencyCheckCommand
 * @package Mpmd\Magento
 *
 * @author Fabrizio Branca
 * @since 2015-06-12
 */
class DependencyCheckCommand extends \N98\Magento\Command\AbstractMagentoCommand
{
    /** @var InputInterface $input */
    protected $_input;

    /** @var OutputInterface $input */
    protected $_output;

    protected function configure()
    {
        $this
            ->setName('mpmd:dependencycheck')
            ->addArgument('source', InputArgument::REQUIRED, 'File or directory to analyze')
            ->setDescription('Find dependencies')
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

        $source = $this->_input->getArgument('source');

        $divUtil = new \Mpmd\Util\Div();

        if (is_file($source)) {
            $files = array($source);
        } elseif (is_dir($source)) {
            $files = $divUtil->findFiles($source);
        } else {
            throw new \InvalidArgumentException('Source not found');
        }

        $collector = new \Mpmd\DependencyChecker\Collector();

        // TODO: there's probably a better way of doing this
        $parsers = array(
            new \Mpmd\DependencyChecker\Parser\Tokenizer($collector),
            new \Mpmd\DependencyChecker\Parser\Xpath($collector),
        );

        foreach ($files as $file) {
            $collector->setCurrentFile($file);
            foreach ($parsers as $parser) { /* @var $parser \Mpmd\DependencyChecker\Parser\AbstractParser */
                if ($parser->listenTo($file)) {
                    $parser->parse($file);
                }
            }
        }

        $collector->filter(array('Mage'));
        $collector->filter($divUtil->getDefaultClasses());

        // filter library files
        $libDir = \Mage::getBaseDir('lib');
        $libraryFiles = $collector->filter(function($className) use ($libDir) {
            return !file_exists($libDir . DS . str_replace(' ', DS, ucwords(str_replace('_', ' ', $className))) . '.php');
        });

        $table = array();
        foreach ($collector->getModules() as $lib) {
            $table[] = array($lib);
        }
        $this->getHelper('table')
            ->setHeaders(array('Modules'))
            ->renderByFormat($output, $table, $input->getOption('format'));

        $table = array();
        foreach ($collector->getLibraries($libraryFiles) as $lib) {
            $table[] = array($lib);
        }
        $this->getHelper('table')
            ->setHeaders(array('Libraries'))
            ->renderByFormat($output, $table, $input->getOption('format'));

        if ($output->getVerbosity() >= OutputInterface::VERBOSITY_VERBOSE) {
            $table = array();
            foreach ($collector->getClasses() as $file => $data) {
                foreach ($data as $type => $classes) {
                    foreach ($classes as $class) {
                        $table[] = array($file, $type, $class);
                    }
                }
            }
            $this->getHelper('table')
                ->setHeaders(array('File', 'Access Type', 'Class'))
                ->renderByFormat($output, $table, $input->getOption('format'));
        }
    }

}