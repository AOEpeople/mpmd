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
class ConfiguredGraph extends \Mpmd\Magento\DependencyCheckCommand
{

    protected function configure()
    {
        $this
            ->setName('mpmd:dependencycheck:graph:configured')
            ->addArgument('module', InputArgument::IS_ARRAY, 'Modules')
            ->setDescription('Creates a graph of all configured depencencies.')
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

        $output = array();
        $output[] = 'digraph callgraph {';
        $output[] = '';
        $output[] = '    splines=false;';
        $output[] = '    rankdir=LR;';
        $output[] = '    edge[arrowhead=vee, arrowtail=inv, arrowsize=.7, color="#dddddd"];';
        $output[] = '    node [fontname="verdana", shape=plaintext, style="filled", fillcolor="#dddddd"];';
        $output[] = '    fontname="Verdana";';
        $output[] = '';

        foreach ($modules as $module) {
            $dependencies = $divUtil->getDeclaredDepenenciesForModule($module);
            foreach ($dependencies as $dependency) {
                $output[] = "    $module -> $dependency;";
            }
        }

        $output[] = '}';

        echo implode("\n", $output) . "\n";
    }
}