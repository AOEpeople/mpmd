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
class Modulegraph extends \Mpmd\Magento\DependencyCheckCommand
{

    protected function configure()
    {
        $this
            ->setName('mpmd:dependencycheck:graph:module')
            ->addArgument('source', InputArgument::IS_ARRAY, 'File or directory to analyze')
            ->setDescription('Creates a module graph')
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

        $collector = $this->collectData();

        // filter the Mage god class
        $collector->filter(array('Mage'));

        // filter the default classes that come with PHP
        $divUtil = new \Mpmd\Util\Div();
        $collector->filter($divUtil->getDefaultClasses());

        // filter library files
        $libDir = \Mage::getBaseDir('lib');
        $libraryFiles = $collector->filter(function($className) use ($libDir) {
            return !file_exists($libDir . DS . str_replace(' ', DS, ucwords(str_replace('_', ' ', $className))) . '.php');
        });

        $output = array();
        $output[] = 'digraph callgraph {';
        $output[] = '';
        $output[] = '    splines=false;';
        $output[] = '    rankdir=LR;';
        $output[] = '    edge[arrowhead=vee, arrowtail=inv, arrowsize=.7, color="#dddddd"];';
        $output[] = '    node [fontname="verdana", shape=plaintext, style="filled", fillcolor="#dddddd"];';
        $output[] = '    fontname="Verdana";';
        $output[] = '';

        foreach ($collector->getModuleRelations() as $sourceModule => $targetModules) {
            foreach ($targetModules as $targetModule) {
                $table[] = array($sourceModule, $targetModule);
                $output[] = "    $sourceModule -> $targetModule;";
            }
        }

        $output[] = '}';

        echo implode("\n", $output) . "\n";
    }

    protected function getEdgeStyleForType($types) {
        $boldOnes = array('extends', 'implements');
        if (count(array_intersect($boldOnes, $types))) {
            return 'bold';
        }
        $dashedOnes = array('new', 'type_hints');
        if (count(array_intersect($boldOnes, $types))) {
            return 'dashed';
        }
        foreach ($types as $type) {
            if (strpos($type, 'get') === 0) {
                return 'dashed';
            }
        }
        return 'dotted';
    }
}