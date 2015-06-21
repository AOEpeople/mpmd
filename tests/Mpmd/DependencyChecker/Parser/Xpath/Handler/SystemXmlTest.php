<?php

namespace Mpmd\DependencyChecker\Parser\Xpath\Handler;

class SystemXmlTest extends \HandlerTestCase {

    protected $handlerClass = '\Mpmd\DependencyChecker\Parser\Xpath\Handler\SystemXml';

    protected $parserClass = '\Mpmd\DependencyChecker\Parser\Xpath';

    protected $factoryMock;

    public function setup()
    {
        parent::setup();
        $this->setupFactoryMock();
    }

    public function testFrontendModel() {
        $this->factoryMock->expects($this->once())
            ->method('getModelClassName')
            ->with(
                $this->equalTo('adminhtml/system_config_form_field_notification')
            )->willReturn('Mage_Adminhtml_Model_System_Config_Form_Field_Notification');

        $this->collectorMock->expects($this->once())
            ->method('addClass')
            ->with(
                $this->equalTo('Mage_Adminhtml_Model_System_Config_Form_Field_Notification'),
                $this->equalTo('frontend_model')
            );

        $this->parser->parse('<?xml version="1.0"?>
            <config>
                <sections>
                    <api>
                        <groups>
                            <config>
                                <fields>
                                    <last_update translate="label">
                                        <frontend_model>adminhtml/system_config_form_field_notification</frontend_model>
                                    </last_update>
                                </fields>
                            </config>
                        </groups>
                    </api>
                </sections>
            </config>
        ');
    }

    public function testSourceModel() {
        $this->factoryMock->expects($this->once())
            ->method('getModelClassName')
            ->with(
                $this->equalTo('adminhtml/system_config_source_yesno')
            )->willReturn('Mage_Adminhtml_Model_System_Config_Source_Yesno');

        $this->collectorMock->expects($this->once())
            ->method('addClass')
            ->with(
                $this->equalTo('Mage_Adminhtml_Model_System_Config_Source_Yesno'),
                $this->equalTo('source_model')
            );

        $this->parser->parse('<?xml version="1.0"?>
            <config>
                <sections>
                    <api>
                        <groups>
                            <config>
                                <fields>
                                    <last_update translate="label">
                                        <source_model>adminhtml/system_config_source_yesno</source_model>
                                    </last_update>
                                </fields>
                            </config>
                        </groups>
                    </api>
                </sections>
            </config>
        ');
    }

    public function testBackendModel() {
        $this->factoryMock->expects($this->once())
            ->method('getModelClassName')
            ->with(
                $this->equalTo('adminhtml/system_config_backend_store')
            )->willReturn('Mage_Adminhtml_Model_System_Config_Backend_Store');

        $this->collectorMock->expects($this->once())
            ->method('addClass')
            ->with(
                $this->equalTo('Mage_Adminhtml_Model_System_Config_Backend_Store'),
                $this->equalTo('backend_model')
            );

        $this->parser->parse('<?xml version="1.0"?>
            <config>
                <sections>
                    <api>
                        <groups>
                            <config>
                                <fields>
                                    <last_update translate="label">
                                        <backend_model>adminhtml/system_config_backend_store</backend_model>
                                    </last_update>
                                </fields>
                            </config>
                        </groups>
                    </api>
                </sections>
            </config>
        ');
    }

}