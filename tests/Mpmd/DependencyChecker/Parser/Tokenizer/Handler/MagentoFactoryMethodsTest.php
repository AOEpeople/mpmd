<?php

namespace Mpmd\DependencyChecker\Parser\Tokenizer\Handler;

class MagentoFactoryMethodsTest extends \HandlerTestCase {

    protected $handlerClass = '\Mpmd\DependencyChecker\Parser\Tokenizer\Handler\MagentoFactoryMethods';

    protected $parserClass = '\Mpmd\DependencyChecker\Parser\Tokenizer';

    public function setup()
    {
        parent::setup();
        $this->setupFactoryMock();
    }

    /**
     * @param $method
     * @param $resolverMethod
     * @throws \Exception
     * @dataProvider methodsDataProvider
     */
    public function testFactoryMethod($method, $resolverMethod)
    {
        $this->factoryMock->expects($this->once())
            ->method($resolverMethod)
            ->with(
                $this->equalTo('foo/bar')
            )->willReturn('Foo_Model_Bar');

        $this->collectorMock->expects($this->once())
            ->method('addClass')
            ->with(
                $this->equalTo('Foo_Model_Bar'),
                $this->equalTo($method)
            );

        $this->parser->parse('<?php Mage::'.$method.'(\'foo/bar\'");');
    }

    public function methodsDataProvider()
    {
        return array(
            array('getModel', 'getModelClassName'),
            array('getSingleton', 'getModelClassName'),
            array('getResourceModel', 'getResourceModelClassName'),
            array('getResourceSingleton', 'getResourceModelClassName'),
            array('createBlock', 'getBlockClassName'),
            array('getBlockSingleton', 'getBlockClassName'),
            array('helper', 'getHelperClassName'),
            array('getResourceHelper', 'getResourceHelperClassName'),
            array('getControllerInstance', 'getControllerInstanceClassName'),
        );
    }

}