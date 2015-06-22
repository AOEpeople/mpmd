<?php

namespace Mpmd\Magento\DependencyCheckCommand;

use N98\Magento\Application;
use Symfony\Component\Console\Helper\TableSeparator;
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
class Configured extends \Mpmd\Magento\DependencyCheckCommand
{

    protected function configure()
    {
        $this
            ->setName('mpmd:dependencycheck:configured')
            ->addArgument('module', InputArgument::IS_ARRAY, 'Modules')
            ->setDescription('Returns the list of modules that are CONFIGURED in these module\'s config xml.')
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

        $divUtil = new \Mpmd\Util\Div();

        $requestedModules = $this->_input->getArgument('module');
        $allModules = $divUtil->getAllModules();

        $matchCount = array();
        $modules = $divUtil->globArray($allModules, $requestedModules, $matchCount);

        foreach ($matchCount as $pattern => $count) {
            if ($count == 0) {
                throw new \Exception('No match found for pattern: ' . $pattern);
            }
        }

        $table = array();
        foreach ($modules as $module) {
            $dependencies = $divUtil->getDeclaredDepenenciesForModule($module);
            if (count($dependencies) == 0) {
                $table[] = array($module, '[No dependency declared]');
            } else {
                foreach ($dependencies as $dependency) {
                    $table[] = array($module, $dependency);
                }
            }
            $table[] = new TableSeparator();
        }
        // Remove TableSeparator at the end of the table
        if (end($table) instanceof TableSeparator) {
            array_pop($table);
        }

        $this->getHelper('table')
            ->setHeaders(array('Module', 'Dependency'))
            ->renderByFormat($output, $table, $input->getOption('format'));
    }

}