<?php

namespace Mpmd\DependencyChecker\Parser;

/**
 * Tokenizer
 *
 * @package Mpmd\DependencyChecker
 *
 * @author Fabrizio Branca
 * @since 2015-06-19
 */
class Xpath extends AbstractParser
{

    protected $fileExtensions = array('xml');

    public function parse($string)
    {
        if (count($this->handlers) == 0) {
            throw new \Exception('No handlers found');
        }

        $simpleXml = simplexml_load_string($string);

        foreach ($this->handlers as $handler) { /* @var $handler Xpath\Handler\AbstractHandler */
            if ($handler->listenTo($simpleXml)) {
                $handler->handle($simpleXml);
            }
        }

    }


}