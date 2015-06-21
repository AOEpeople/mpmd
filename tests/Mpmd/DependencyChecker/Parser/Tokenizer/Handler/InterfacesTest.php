<?php

namespace Mpmd\DependencyChecker\Parser\Tokenizer\Handler;

class InterfacesTest extends \HandlerTestCase {

    protected $handlerClass = '\Mpmd\DependencyChecker\Parser\Tokenizer\Handler\Interfaces';

    protected $parserClass = '\Mpmd\DependencyChecker\Parser\Tokenizer';

    public function testSingleInterface() {
        $this->collectorMock->expects($this->once())
            ->method('addClass')
            ->with(
                $this->equalTo('B'),
                $this->equalTo('implements')
            );

        $this->parser->parse('<?php class A implements B {}');
    }

    public function testMultipleInterfaces() {
        $this->collectorMock->expects($this->exactly(2))
            ->method('addClass')
            ->withConsecutive(
                array($this->equalTo('B'), $this->equalTo('implements')),
                array($this->equalTo('C'), $this->equalTo('implements'))
            );

        $this->parser->parse('<?php class A implements B, C {}');
    }

    public function testNoInterfaces() {
        $this->collectorMock->expects($this->never())
            ->method('addClass');
        $this->parser->parse('<?php class A {}');
    }

    public function testSingleInterfaceWithLinebreak() {
        $this->collectorMock->expects($this->once())
            ->method('addClass')
            ->with(
                $this->equalTo('B'),
                $this->equalTo('implements')
            );

        $this->parser->parse('<?php class A
        implements B {}');
    }

    public function testMultipleInterfacesWithLinebreaks() {
        $this->collectorMock->expects($this->exactly(2))
            ->method('addClass')
            ->withConsecutive(
                array($this->equalTo('B'), $this->equalTo('implements')),
                array($this->equalTo('C'), $this->equalTo('implements'))
            );

        $this->parser->parse('<?php class A
        implements B,
        C {}');
    }

}