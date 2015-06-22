<?php

namespace Mpmd\Magento\DependencyCheckCommand;

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
class Verify extends \Mpmd\Magento\DependencyCheckCommand
{

    protected function configure()
    {
        $this
            ->setName('mpmd:dependencycheck:verify')
            ->addArgument('source', InputArgument::IS_ARRAY, 'File or directory to analyze')
            ->addOption('module', 'm', InputOption::VALUE_REQUIRED, 'Module')
            ->setDescription('Checks if found dependencies match a given module\'s xml file')
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

        $moduleName = $this->_input->getOption('module');

        if (!\Mage::getConfig()->getNode('modules/' . $moduleName)) {
            throw new \InvalidArgumentException('Could not find module.');
        }

        $divUtil = new \Mpmd\Util\Div();

        $declaredDepenencies = $divUtil->getDeclaredDepenenciesForModule($moduleName);

        $collector = $this->collectData();

        // filter the Mage god class
        $collector->filter(array('Mage'));

        // filter the default classes that come with PHP
        $collector->filter($divUtil->getDefaultClasses());

        // filter library files
        $libDir = \Mage::getBaseDir('lib');
        $libraryFiles = $collector->filter(function($className) use ($libDir) {
            return !file_exists($libDir . DS . str_replace(' ', DS, ucwords(str_replace('_', ' ', $className))) . '.php');
        });

        $actualDependencies = $collector->getModules();

        $correctDepenencies = array_intersect($actualDependencies, $declaredDepenencies);
        $undeclaredDepenencies = array_diff($actualDependencies, $declaredDepenencies);
        $unusedDeclarations = array_diff($declaredDepenencies, $actualDependencies);

        $table = array();
        foreach ($correctDepenencies as $dependency) {
            $table[] = array($dependency, $dependency, '<info>OK</info>');
        }
        foreach ($unusedDeclarations as $dependency) {
            $table[] = array($dependency, '-', '<warning> Not used </warning>');
        }
        foreach ($undeclaredDepenencies as $dependency) {
            $table[] = array('-', $dependency, '<error> Undeclared </error>');
        }

        $this->getHelper('table')
            ->setHeaders(array('Declared Dependency', 'Actual Dependency', 'Status'))
            ->renderByFormat($output, $table, $input->getOption('format'));

    }

}