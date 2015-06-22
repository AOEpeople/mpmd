<?php

namespace Mpmd\DependencyChecker\Parser;

/**
 * Parser interface
 *
 * @package Mpmd\DependencyChecker
 *
 * @author Fabrizio Branca
 * @since 2015-06-19
 */
interface ParserInterface {

    /**
     * Checks if this file is relevant for this parser
     *
     * @param $file
     * @return bool
     */
    public function listenTo($file);

    /**
     * Add a handler
     *
     * @param \Mpmd\DependencyChecker\HandlerInterface $handler
     */
    public function addHandler(\Mpmd\DependencyChecker\HandlerInterface $handler);

    /**
     * Parse: Here's we the magic happens
     *
     * @param $file
     * @return mixed
     */
    public function parse($file);

}