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
            ->addArgument('source', InputArgument::IS_ARRAY, 'File or directory to analyze')
            ->addOption('modules', 'm', InputOption::VALUE_NONE, 'Show depenent modules')
            ->addOption('libraries', 'l', InputOption::VALUE_NONE, 'Show depenent libraries')
            ->addOption('classes', 'c', InputOption::VALUE_NONE, 'Show relationships between classes')
            ->addOption('details', 'd', InputOption::VALUE_NONE, 'Show all details (very verbose!)')
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
     * Collect data
     *
     * @return \Mpmd\DependencyChecker\Collector
     * @throws \Exception
     */
    protected function collectData()
    {
        $divUtil = new \Mpmd\Util\Div();

        $collector = new \Mpmd\DependencyChecker\Collector();

        $parsers = $this->getParsers($collector);

        foreach ($this->getFilesFromArgument() as $file) {
            $collector->setCurrentFile($file);
            $fileContent = file_get_contents($file);
            foreach ($parsers as $parser) { /* @var $parser \Mpmd\DependencyChecker\Parser\AbstractParser */
                if ($parser->listenTo($file)) {
                    $parser->parse($fileContent);
                }
            }
        }
        return $collector;
    }

    /**
     * Get files from "source" argument
     *
     * @return array
     */
    protected function getFilesFromArgument()
    {
        $divUtil = new \Mpmd\Util\Div();
        $files = array();
        $sources = $this->_input->getArgument('source');
        foreach ($sources as $source) {
            foreach (glob($source) as $globbedSource) {
                if (is_file($globbedSource)) {
                    $files[] = $globbedSource;
                } elseif (is_dir($globbedSource)) {
                    $files = array_merge($files, $divUtil->findFiles($globbedSource));
                } else {
                    throw new \InvalidArgumentException('Source not found');
                }
            }
        }
        if (count($files) == 0) {
            throw new \InvalidArgumentException('No files found');
        }
        return $files;
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

        if (!$this->_input->getOption('modules')
            && !$this->_input->getOption('libraries')
            && !$this->_input->getOption('classes')
            && !$this->_input->getOption('details')
        ) {
            throw new \InvalidArgumentException('Please specify at least one of the following options: --modules, --libraries, --classes, --details');
        }

        $divUtil = new \Mpmd\Util\Div();

        $collector = $this->collectData();

        // filter the Mage god class
        $collector->filter(array('Mage'));

        // get class relations (before filtering)
        $relations = $collector->getClassRelations();

        // filter the default classes that come with PHP
        $collector->filter($divUtil->getDefaultClasses());

        // filter library files
        $libDir = \Mage::getBaseDir('lib');
        $libraryFiles = $collector->filter(function($className) use ($libDir) {
            return !file_exists($libDir . DS . str_replace(' ', DS, ucwords(str_replace('_', ' ', $className))) . '.php');
        });


        // Show dependent modules
        if ($this->_input->getOption('modules')) {
            $table = array();
            foreach ($collector->getModuleRelations() as $sourceModule => $targetModules) {
                foreach ($targetModules as $targetModule) {
                    $table[] = array($sourceModule, $targetModule);
                }
            }
            $this->getHelper('table')
                ->setHeaders(array('Source Module', 'Target Module'))
                ->renderByFormat($output, $table, $input->getOption('format'));
        }


        // Show dependent libraries
        if ($this->_input->getOption('libraries')) {
            $table = array();
            foreach ($collector->getLibraries($libraryFiles) as $lib) {
                $table[] = array($lib);
            }
            $this->getHelper('table')
                ->setHeaders(array('Libraries'))
                ->renderByFormat($output, $table, $input->getOption('format'));
        }


        // Show class to class relations
        if ($this->_input->getOption('classes')) {
            $table = array();
            foreach ($relations as $sourceClass => $targetClasses) {
                foreach ($targetClasses as $targetClass => $types) {
                    $table[] = array($sourceClass, $targetClass, implode(',', $types));
                }
            }
            $this->getHelper('table')
                ->setHeaders(array('Source class', 'Target Class', 'Access Types'))
                ->renderByFormat($output, $table, $input->getOption('format'));
        }


        // Show all details
        if ($this->_input->getOption('details')) {
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

    /**
     * Get parsers
     *
     * @param \Mpmd\DependencyChecker\Collector $collector
     * @return array
     * @throws \Exception
     */
    protected function getParsers(\Mpmd\DependencyChecker\Collector $collector)
    {
        $parsers = array();
        $commandConfig = $this->getCommandConfig('Mpmd\Magento\DependencyCheckCommand');
        foreach ($commandConfig['parsers'] as $parserClass) {

            // instantiate the parsers
            $parser = new $parserClass();
            if (!$parser instanceof \Mpmd\DependencyChecker\Parser\ParserInterface) {
                throw new \Exception('Invalid parser class');
            }

            // add this parser's handlers
            if (isset($commandConfig[$parserClass]) && isset($commandConfig[$parserClass]['handlers'])) {
                foreach ($commandConfig[$parserClass]['handlers'] as $handlerClass) {
                    $handler = new $handlerClass($parser, $collector);
                    if (!$handler instanceof \Mpmd\DependencyChecker\HandlerInterface) {
                        throw new \Exception('Invalid handler class');
                    }
                    $parser->addHandler($handler);
                }
            }
            $parsers[] = $parser;
        }
        return $parsers;
    }

}