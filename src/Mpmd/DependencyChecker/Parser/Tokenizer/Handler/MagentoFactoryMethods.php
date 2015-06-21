<?php

namespace Mpmd\DependencyChecker\Parser\Tokenizer\Handler;

/**
 * @author Fabrizio Branca
 * @since 2015-06-19
 */
class MagentoFactoryMethods extends AbstractHandler {

    protected $types = array(T_STRING);

    protected $factory;

    /**
     * Set Magento factory
     *
     * Poor-man's dependency injections :)
     * This gives us a chance to inject a factory mock right after instantiating this class before
     * the lazy-loading getMagentoFactory() would create a regular one.
     *
     * @param \Mpmd\Util\MagentoFactory $factory
     */
    public function setMagentoFactory(\Mpmd\Util\MagentoFactory $factory)
    {
        $this->factory = $factory;
    }

    /**
     * Get Magento factory
     *
     * @return \Mpmd\Util\MagentoFactory
     */
    public function getMagentoFactory()
    {
        if (is_null($this->factory)) {
            $this->factory = new \Mpmd\Util\MagentoFactory();
        }
        return $this->factory;
    }

    public function getFactoryMethodNames()
    {
        $magentoFactoryUtil = $this->getMagentoFactory();
        return array(
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
        foreach ($this->getFactoryMethodNames() as $method => $converter) {
            if ($string == strtolower($method)) {
                $j = $this->parser->skip($i+1, array(T_WHITESPACE));
                $this->parser->assertToken($j, '(');
                $j = $this->parser->skip($j+1, array(T_WHITESPACE));
                if ($this->parser->is($j, T_CONSTANT_ENCAPSED_STRING)) { // we can't detecte what's in variables so we focus on strings here
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

}