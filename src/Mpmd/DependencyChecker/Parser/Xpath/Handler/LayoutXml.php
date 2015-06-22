<?php

namespace Mpmd\DependencyChecker\Parser\Xpath\Handler;

/**
 * @author Fabrizio Branca
 * @since 2015-06-19
 */
class LayoutXml extends AbstractHandler {

    public function handle($xml)
    {
        if (!$xml instanceof \SimpleXMLElement) {
            throw new \InvalidArgumentException("Expected a SimpleXMLElement");
        }

        foreach ($xml->xpath('//block') as $blockXml) { /* @var $blockXml \SimpleXMLElement */
            $blockClassPath = (string)$blockXml['type'];
            if (empty($blockClassPath)) {
                continue;
                // TODO: this shouldn't happen. And maybe another tool could check this
                // throw new \Exception('No type found');
            }
            $class = $this->getMagentoFactory()->getBlockClassName($blockClassPath);
            $this->collector->addClass($class, 'block');
        }
    }


}