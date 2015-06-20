<?php

abstract class HandlerTestCase extends \PHPUnit_Framework_TestCase {

    protected $handlerClass;

    /**
     * @var \Mpmd\DependencyChecker\Parser\Tokenizer
     */
    protected $tokenizer;
    protected $handler;

    /**
     * @var \Mpmd\DependencyChecker\Collector
     */
    protected $collectorMock;

    public function setup() {
        if (empty($this->handlerClass)) {
            throw new \Exception('No handler class found.');
        }
        $this->collectorMock = $this->getMockBuilder('\Mpmd\DependencyChecker\Collector')->getMock();
        $this->tokenizer = new \Mpmd\DependencyChecker\Parser\Tokenizer();
        $this->handler = new $this->handlerClass($this->tokenizer, $this->collectorMock);
        $this->tokenizer->addHandler($this->handler);
    }

}