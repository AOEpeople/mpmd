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
abstract class AbstractParser {

    /**
     * @var array
     */
    protected $handlers = array();

    /**
     * @var array
     */
    protected $fileExtensions = array();

    /**
     * Checks if this file is relevant for this parser
     *
     * @param $file
     * @return bool
     */
    public function listenTo($file)
    {
        $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        return in_array($extension, $this->fileExtensions);
    }

    /**
     * Add a handler
     *
     * @param Tokenizer\Handler\AbstractHandler $handler
     */
    public function addHandler(Tokenizer\Handler\AbstractHandler $handler) {
        $this->handlers[] = $handler;
    }

    /**
     * Parse: Here's we the magic happens
     *
     * @param $file
     * @return mixed
     */
    abstract public function parse($file);

}