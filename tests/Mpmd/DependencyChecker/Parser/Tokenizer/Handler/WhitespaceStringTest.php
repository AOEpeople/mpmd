<?php

namespace Mpmd\DependencyChecker\Parser\Tokenizer\Handler;

class WhitespaceStringTest extends \HandlerTestCase {

    protected $handlerClass = '\Mpmd\DependencyChecker\Parser\Tokenizer\Handler\WhitespaceString';

    protected $parserClass = '\Mpmd\DependencyChecker\Parser\Tokenizer';

    public function testNew() {
        $this->collectorMock->expects($this->once())
            ->method('addClass')
            ->with(
                $this->equalTo('B'),
                $this->equalTo('new')
            );

        $this->parser->parse('<?php $a = new B();');
    }

    public function testExtends() {
        $this->collectorMock->expects($this->exactly(2))
            ->method('addClass')
            ->withConsecutive(
                array($this->equalTo('A'), $this->equalTo('class')),
                array($this->equalTo('B'), $this->equalTo('extends'))
            );

        $this->parser->parse('<?php class A extends B {}');
    }

    public function testClass() {
        $this->collectorMock->expects($this->once())
            ->method('addClass')
            ->with(
                $this->equalTo('A'),
                $this->equalTo('class')
            );

        $this->parser->parse('<?php class A {}');
    }

}