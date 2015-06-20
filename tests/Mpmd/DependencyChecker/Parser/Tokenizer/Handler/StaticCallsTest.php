<?php

namespace Mpmd\DependencyChecker\Parser\Tokenizer\Handler;

class StaticCallsTest extends \HandlerTestCase {

    protected $handlerClass = '\Mpmd\DependencyChecker\Parser\Tokenizer\Handler\StaticCalls';

    public function testNoInterfaces() {
        $this->collectorMock->expects($this->never())
            ->method('addClass');
        $this->tokenizer->parse('<?php class A {}');
    }

    public function testStaticClassConstant() {
        $this->collectorMock->expects($this->once())
            ->method('addClass')
            ->with(
                $this->equalTo('B'),
                $this->equalTo('static_call')
            );

        $this->tokenizer->parse('<?php echo B::CLASS_CONSTANT;');
    }

    public function testStaticClassFunctionCall() {
        $this->collectorMock->expects($this->once())
            ->method('addClass')
            ->with(
                $this->equalTo('B'),
                $this->equalTo('static_call')
            );

        $this->tokenizer->parse('<?php echo B::functionCall();');
    }

    public function testIgnoreSelf() {
        $this->collectorMock->expects($this->never())
            ->method('addClass');

        $this->tokenizer->parse('<?php class A {
            public static function foo() {};
            public static function bar() {
                self::foo();
            };
        }');
    }

    public function testIgnoreParent() {
        $this->collectorMock->expects($this->never())
            ->method('addClass');

        $this->tokenizer->parse('<?php class A {
            public static function foo() {};
            public static function bar() {
                parent::foo();
            };
        }');
    }

}