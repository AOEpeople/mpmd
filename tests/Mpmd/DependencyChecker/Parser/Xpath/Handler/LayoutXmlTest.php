<?php

namespace Mpmd\DependencyChecker\Parser\Xpath\Handler;

class LayoutXmlTest extends \HandlerTestCase {

    protected $handlerClass = '\Mpmd\DependencyChecker\Parser\Xpath\Handler\LayoutXml';

    protected $parserClass = '\Mpmd\DependencyChecker\Parser\Xpath';

    protected $factoryMock;

    public function setup()
    {
        parent::setup();
        $this->setupFactoryMock();
    }

    public function testType() {
        $this->factoryMock->expects($this->once())
            ->method('getBlockClassName')
            ->with(
                $this->equalTo('page/html')
            )->willReturn('Mage_Page_Block_Html');

        $this->collectorMock->expects($this->once())
            ->method('addClass')
            ->with(
                $this->equalTo('Mage_Page_Block_Html'),
                $this->equalTo('block')
            );

        $this->parser->parse('<layout version="0.1.0"><default><block type="page/html" /></default></layout>');
    }
}