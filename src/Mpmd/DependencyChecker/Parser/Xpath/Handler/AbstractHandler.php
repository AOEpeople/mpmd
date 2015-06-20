<?php

namespace Mpmd\DependencyChecker\Parser\Xpath\Handler;

/**
 * @author Fabrizio Branca
 * @since 2015-06-19
 */
abstract class AbstractHandler extends \Mpmd\DependencyChecker\AbstractHandler {

    public function listenTo($type)
    {
        return true;
    }

}