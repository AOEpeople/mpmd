<?php

abstract class HandlerTestCase extends \PHPUnit_Framework_TestCase {

    /**
     * @var string configure this in your inheriting class
     */
    protected $handlerClass;

    /**
     * @var string configure this in your inheriting class
     */
    protected $parserClass;

    /**
     * @var \Mpmd\DependencyChecker\Parser\AbstractParser
     */
    protected $parser;

    /**
     * @var \Mpmd\DependencyChecker\AbstractHandler
     */
    protected $handler;

    /**
     * @var \Mpmd\DependencyChecker\Collector
     */
    protected $collectorMock;

    /**
     * @var \Mpmd\Util\MagentoFactory
     */
    protected $factoryMock;

    /**
     * Set up handler, parser and collection mock
     *
     * @throws Exception
     */
    public function setup()
    {
        if (empty($this->handlerClass)) {
            throw new \Exception('No handler class found.');
        }
        if (empty($this->parserClass)) {
            throw new \Exception('No parser class found.');
        }
        $this->collectorMock = $this->getMockBuilder('\Mpmd\DependencyChecker\Collector')->getMock();
        $this->parser = new $this->parserClass();
        $this->handler = new $this->handlerClass($this->parser, $this->collectorMock);
        $this->parser->addHandler($this->handler);
    }

    public function setupFactoryMock()
    {
        // \Mpmd\Util\MagentoFactory extends Mage_Core_Model_Config, but since it's a mock and we're mocking
        // all methods I don't feel too bad about this trick.
        // btw, \Mpmd\Util\MagentoFactory needs to extend Mage_Core_Model_Config in the first place
        // since we need to be able to access Mage_Core_Model_Config's xml property without
        // using Mage_Core_Model_Config's methods since we need to resolve some paths without
        // taking cache and rewrites into account.
        if (!class_exists('Mage_Core_Model_Config')) { eval('class Mage_Core_Model_Config {}'); }

        $this->factoryMock = $this->getMockBuilder('\Mpmd\Util\MagentoFactory')
            ->disableOriginalConstructor()
            ->getMock();
        $this->handler->setMagentoFactory($this->factoryMock);
    }

}