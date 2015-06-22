<?php

namespace Mpmd\DependencyChecker\Parser\Tokenizer\Handler;

/**
 * @author Fabrizio Branca
 * @since 2015-06-19
 */
class TypeHints extends AbstractHandler {

    protected $types = array(T_FUNCTION);

    public function handle($i)
    {
        $j = $this->parser->skipUntil($i, array('('));

        // parse arguments
        $this->parser->assertToken($j, '(');
        $j++;
        while (!$this->parser->is($j, ')')) {
            if ($this->parser->is($j, ',')) {
                $j = $this->parser->skip($j+1, T_WHITESPACE);
            }
            if ($this->parser->is($j, T_STRING)) { // we found a type hint
                $this->parser->assertToken($j + 1, T_WHITESPACE);
                $this->parser->assertToken($j + 2, array(T_VARIABLE, '&'));
                $this->collector->addClass($this->parser->getValue($j), 'type_hint');
            }
            $j = $this->parser->skipUntil($j+1, array(',', ')'));
        }
    }

}