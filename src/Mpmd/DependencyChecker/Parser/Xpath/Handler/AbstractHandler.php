<?php

namespace Mpmd\DependencyChecker\Parser\Xpath\Handler;

/**
 * @author Fabrizio Branca
 * @since 2015-06-19
 */
abstract class AbstractHandler extends \Mpmd\DependencyChecker\AbstractHandler {

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

    public function listenTo($type)
    {
        return true;
    }

}