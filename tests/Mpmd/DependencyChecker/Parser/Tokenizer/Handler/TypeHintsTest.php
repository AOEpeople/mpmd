<?php

namespace Mpmd\DependencyChecker\Parser\Tokenizer\Handler;

class TypeHintsTest extends \HandlerTestCase {

    protected $handlerClass = '\Mpmd\DependencyChecker\Parser\Tokenizer\Handler\TypeHints';

    protected $parserClass = '\Mpmd\DependencyChecker\Parser\Tokenizer';

    public function testFunction() {
        $this->collectorMock->expects($this->once())
            ->method('addClass')
            ->with(
                $this->equalTo('B'),
                $this->equalTo('type_hint')
            );

        $this->parser->parse('<?php function A(B $b) {}');
    }

    public function testSecondParameter() {
        $this->collectorMock->expects($this->once())
            ->method('addClass')
            ->with(
                $this->equalTo('B'),
                $this->equalTo('type_hint')
            );

        $this->parser->parse('<?php function A($a, B $b) {}');
    }

    public function testThirdParameter() {
        $this->collectorMock->expects($this->once())
            ->method('addClass')
            ->with(
                $this->equalTo('B'),
                $this->equalTo('type_hint')
            );

        $this->parser->parse('<?php function A($a, $b, B $c) {}');
    }

    public function testTwoParameters() {
        $this->collectorMock->expects($this->exactly(2))
            ->method('addClass')
            ->withConsecutive(
                array($this->equalTo('A'), $this->equalTo('type_hint')),
                array($this->equalTo('B'), $this->equalTo('type_hint'))
            );

        $this->parser->parse('<?php function A(A $a, $b, B $c) {}');
    }

    public function testMethod() {
        $this->collectorMock->expects($this->once())
            ->method('addClass')
            ->with(
                $this->equalTo('B'),
                $this->equalTo('type_hint')
            );

        $this->parser->parse('<?php class A { public function A(B $b) {} }');
    }

    public function testAnonymousFunction() {
        $this->collectorMock->expects($this->once())
            ->method('addClass')
            ->with(
                $this->equalTo('B'),
                $this->equalTo('type_hint')
            );

        $this->parser->parse('<?php $a = function(B $b) {} }');
    }

    public function testFunctionWithReference() {
        $this->collectorMock->expects($this->once())
            ->method('addClass')
            ->with(
                $this->equalTo('B'),
                $this->equalTo('type_hint')
            );

        $this->parser->parse('<?php class A { public function &A(B $b) {} }');
    }

    public function testReferenceParameters() {

        $this->collectorMock->expects($this->once())
            ->method('addClass')
            ->with(
                $this->equalTo('SimpleXMLElement'),
                $this->equalTo('type_hint')
            );

        $this->parser->parse('<?php class A {private function _assocToXml(array $array, $rootName, SimpleXMLElement &$xml) {}}');

    }

}