<?php

namespace Mpmd\DependencyChecker\Parser\Tokenizer\Handler;

/**
 * @author Fabrizio Branca
 * @since 2015-06-19
 */
class Interfaces extends AbstractHandler {

    protected $types = array(T_IMPLEMENTS);

    public function handle($i)
    {
        $this->parser->assertToken($i+1, T_WHITESPACE);
        $this->parser->assertToken($i+2, T_STRING);
        $j = $i+2;
        while ($this->parser->getType($j) == T_STRING) {
            $this->collector->addClass($this->parser->getValue($j), 'implements');
            $j = $this->parser->skip($j+1, array(T_WHITESPACE, ','));
        }
    }

}