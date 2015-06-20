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

    /**
     * Constructor
     *
     * @param \Mpmd\DependencyChecker\Collector $collector
     */
    public function __construct(\Mpmd\DependencyChecker\Collector $collector)
    {
        // TODO: there are probably better ways of doing this

        $this->addHandler(new Xpath\Handler\SystemXml($this, $collector));
        $this->addHandler(new Xpath\Handler\LayoutXml($this, $collector));
    }

    public function parse($file)
    {
        $simpleXml = simplexml_load_file($file);

        foreach ($this->handlers as $handler) { /* @var $handler Xpath\Handler\AbstractHandler */
            if ($handler->listenTo($simpleXml)) {
                $handler->handle($simpleXml);
            }
        }

    }


}