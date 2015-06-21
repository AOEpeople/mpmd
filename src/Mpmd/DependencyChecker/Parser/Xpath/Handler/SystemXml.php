<?php

namespace Mpmd\DependencyChecker\Parser\Xpath\Handler;

/**
 * @author Fabrizio Branca
 * @since 2015-06-19
 */
class SystemXml extends AbstractHandler
{

    public function handle($xml)
    {
        if (!$xml instanceof \SimpleXMLElement) {
            throw new \InvalidArgumentException("Expected a SimpleXMLElement");
        }

        $nodes = array(
            'frontend_model',
            'backend_model',
            'source_model'
        );

        foreach ($nodes as $node) {
            foreach ($xml->xpath('//'.$node) as $modelXml) { /* @var $model \SimpleXMLElement */
                $modelClassPath = (string)$modelXml;
                $class = $this->getMagentoFactory()->getModelClassName($modelClassPath);
                $this->collector->addClass($class, $node);
            }
        }
    }


}