<?php

namespace Mpmd\DependencyChecker\Parser\Tokenizer\Handler;

/**
 * @author Fabrizio Branca
 * @since 2015-06-19
 */
abstract class AbstractHandler extends \Mpmd\DependencyChecker\AbstractHandler {

    /**
     * @var \Mpmd\DependencyChecker\Parser\Tokenizer
     */
    protected $parser;

    /**
     * @var array tokens that are relevant for this handler
     */
    protected $types = array();

    public function listenTo($type) {
        return in_array($type, $this->types);
    }
}