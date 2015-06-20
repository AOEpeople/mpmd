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
        $j = $i+1;
        $this->parser->assertToken($j, T_WHITESPACE);
        $j = $this->parser->skip($j + 1, array(T_WHITESPACE, '&'));
        $this->parser->assertToken($j, T_STRING); // function name
        $j = $this->parser->skip($j + 1, array(T_WHITESPACE, '&'));

        // parse arguments
        $this->parser->assertToken($j, '(');
        $j++;
        while (!$this->parser->is($j, ')')) {
            if ($this->parser->is($j, T_STRING)) { // we found a type hint
                $this->parser->assertToken($j + 1, T_WHITESPACE);
                $this->parser->assertToken($j + 2, T_VARIABLE);
                $this->collector->addClass($this->parser->getValue($j), 'type_hint');
            }
            $j = $this->parser->skipUntil($j + 1, array(',', ')'));
        }
    }

}