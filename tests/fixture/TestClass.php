<?php
/**
 * Class ${NAME}
 *
 * @author Fabrizio Branca
 * @since 2015-06-18
 */
class TestClassA extends Mage_Catalog_Model_Product implements Mage_Adminhtml_Block_Widget_Grid_Column_Filter_Interface, Mage_Adminhtml_Block_Widget_Tab_Interface {
    public function canShowTab(){}
    public function getColumn(){}
    public function getHtml(){}
    public function getTabLabel(){}
    public function getTabTitle(){}
    public function isHidden(){}
    public function setColumn($column){}

    public function &getTransactionEvents(Mage_Core_AjaxController $controller) {
    }

    public function checkTypeHints (Mage_Catalog_Model_Category $category, $noHint, Mage_Core_Helper_Data $data) {
        $job = Mage::getModel("monkey/bulksync{$entity}")->load($id);
        $new = new Mage_Admin_Model_Acl(); // done
        $lifetime = Mage_Core_Model_Cache::DEFAULT_LIFETIME; // done
        $model = Mage::getModel('customer/customer');
        $singleton = Mage::getSingleton   (   'admin/session');
        $resource = Mage::getResourceModel('admin/roles_user_collection');
        $resourceSingleton = Mage::getResourceSingleton('catalog/category_tree');
        $createBlock = $this->getLayout()->createBlock('adminhtml/widget_button');
        $blockSingleton = Mage::getBlockSingleton('core/text_list');
        $resourceHelper = Mage::getResourceHelper('catalog');
        $controllerInstance = Mage::getControllerInstance('Mage_Adminhtml_Rss_CatalogController', new stdClass(), new stdClass());
        $dataHelper = Mage::helper('reports');
        $helper = Mage::helper('adminhtml/rss');

    }

}

class TestClassB extends Mage_Api2_Block_Adminhtml_Roles {

}