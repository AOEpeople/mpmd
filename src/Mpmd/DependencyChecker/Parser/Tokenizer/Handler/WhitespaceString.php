<?php

namespace Mpmd\DependencyChecker\Parser\Tokenizer\Handler;

/**
 * @author Fabrizio Branca
 * @since 2015-06-19
 */
class WhitespaceString extends AbstractHandler {

    protected $types = array(T_NEW, T_EXTENDS, T_CLASS);

    protected $groups = array(
        T_NEW => 'new',
        T_EXTENDS => 'extends',
        T_CLASS => 'class'
    );

    public function handle($i)
    {
        $currentType = $this->parser->getType($i);
        $this->parser->assertToken($i+1, T_WHITESPACE);
        try {
            $this->parser->assertToken($i + 2, T_STRING);
            $this->collector->addClass($this->parser->getValue($i + 2), $this->groups[$currentType]);
        } catch (\Exception $e) {
            if ($currentType == T_NEW) {
                // we can't handle code like this:
                // $object = new $className();
                // so, let's just skip that.
            } else {
                throw $e;
            }
        }
    }

}