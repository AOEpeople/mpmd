<?php

namespace Mpmd\DependencyChecker\Parser\Tokenizer\Handler;

/**
 * @author Fabrizio Branca
 * @since 2015-06-19
 */
class StaticCalls extends AbstractHandler {

    protected $types = array(T_DOUBLE_COLON);

    public function handle($i)
    {
        $this->parser->assertToken($i-1, T_STRING);
        $value = $this->parser->getValue($i-1);
        if (!in_array($value, array('self', 'parent'))) {
            $this->collector->addClass($value, 'static_call');
        }
    }

}