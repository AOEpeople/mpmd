<?php

namespace Mpmd\DependencyChecker;

/**
 * @author Fabrizio Branca
 * @since 2015-06-19
 */
abstract class AbstractHandler {

    /**
     * @var \Mpmd\DependencyChecker\Parser\AbstractParser
     */
    protected $parser;

    /**
     * @var \Mpmd\DependencyChecker\Collector
     */
    protected $collector;

    public function __construct(\Mpmd\DependencyChecker\Parser\AbstractParser $parser, \Mpmd\DependencyChecker\Collector $collector)
    {
        $this->parser = $parser;
        $this->collector = $collector;
        $this->_contruct();
    }

    public function _contruct() {}

    abstract public function listenTo($type);

    abstract public function handle($current);
}