<?php

namespace Mpmd\DependencyChecker\Parser\Tokenizer\Handler;

/**
 * @author Fabrizio Branca
 * @since 2015-06-19
 */
class MagentoFactoryMethods extends AbstractHandler {

    protected $types = array(T_STRING);

    protected $factoryMethods = array();

    public function _construct()
    {
        $magentoFactoryUtil = new \Mpmd\Util\MagentoFactory();

        $this->factoryMethods = array(
            'getModel' => array($magentoFactoryUtil, 'getModelClassName'),
            'getSingleton' => array($magentoFactoryUtil, 'getModelClassName'),
            'getResourceModel' => array($magentoFactoryUtil, 'getResourceModelClassName'),
            'getResourceSingleton' => array($magentoFactoryUtil, 'getResourceModelClassName'),
            'createBlock' => array($magentoFactoryUtil, 'getBlockClassName'),
            'getBlockSingleton' => array($magentoFactoryUtil, 'getBlockClassName'),
            'helper' => array($magentoFactoryUtil, 'getHelperClassName'),
            'getResourceHelper' => array($magentoFactoryUtil, 'getResourceHelperClassName'),
            'getControllerInstance' => array($magentoFactoryUtil, 'getControllerInstanceClassName'),
        );
    }

    public function handle($i)
    {
        $string = strtolower($this->parser->getValue($i));
        foreach ($this->factoryMethods as $method => $converter) {
            if ($string == strtolower($method)) {
                $j = $this->parser->skip($i+1, array(T_WHITESPACE));
                $this->parser->assertToken($j, '(');
                $j = $this->parser->skip($j+1, array(T_WHITESPACE, T_STRING_CAST));
                if ($this->parser->is($j, T_VARIABLE)) {
                    // we can't find out what that variable points to, so we'll let this one go...
                    continue;
                }
                $this->parser->assertToken($j, T_CONSTANT_ENCAPSED_STRING);

                $classPath = trim($this->parser->getValue($j), '"\'');
                $class = call_user_func($converter, $classPath);

                if (!$class) {
                    $class = '[Could not resolve: '.$classPath.']';
                }
                $this->collector->addClass($class, $method);
            }
        }
    }

}