<?php

namespace Mpmd\DependencyChecker;

/**
 * @author Fabrizio Branca
 * @since 2015-06-19
 */
interface HandlerInterface {

    public function __construct(\Mpmd\DependencyChecker\Parser\AbstractParser $parser, \Mpmd\DependencyChecker\Collector $collector);

    public function listenTo($type);

    public function handle($current);

}