<?php
namespace Mpmd\Util;

class MagentoFactory extends \Mage_Core_Model_Config {


    public function __construct()
    {
        // this is a little trick:
        // since this class extends Mage_Core_Model_Config we have access to that classes protected properties
        $this->_xml = \Mage::getConfig()->_xml;
    }

    /**
     * Retrieve helper class name
     *
     * @param   string $helperName
     * @return  string
     */
    public function getHelperClassName($helperName)
    {
        if (strpos($helperName, '/') === false) {
            $helperName .= '/data';
        }
        return $this->getGroupedClassName('helper', $helperName);
    }

    /**
     * Retrieve block class name
     *
     * @param   string $blockType
     * @return  string
     */
    public function getBlockClassName($blockType)
    {
        if (strpos($blockType, '/')===false) {
            return $blockType;
        }
        return $this->getGroupedClassName('block', $blockType);
    }

    /**
     * Retrieve module class name
     *
     * @param   sting $modelClass
     * @return  string
     */
    public function getModelClassName($modelClass)
    {
        $modelClass = trim($modelClass);
        if (strpos($modelClass, '/')===false) {
            return $modelClass;
        }
        return $this->getGroupedClassName('model', $modelClass);
    }

    /**
     * Get controller instance class name
     *
     * @param $controllerInstance
     * @return mixed
     */
    public function getControllerInstanceClassName($controllerInstance)
    {
        return $controllerInstance;
    }

    /**
     * Retreive resource helper instance
     *
     * Example:
     * $config->getResourceHelper('cms')
     * will instantiate Mage_Cms_Model_Resource_Helper_<db_adapter_name>
     *
     * @param string $moduleName
     * @return string
     */
    public function getResourceHelperClassName($moduleName)
    {
        $connectionModel = $this->_getResourceConnectionModel($moduleName);
        $helperClass     = sprintf('%s/helper_%s', $moduleName, $connectionModel);
        return $this->getResourceModelClassName($helperClass);
    }

    /**
     * Get a resource model class name
     *
     * @param string $modelClass
     * @return string|false
     */
    public function getResourceModelClassName($modelClass)
    {
        $factoryName = $this->_getResourceModelFactoryClassName($modelClass);
        if ($factoryName) {
            return $this->getModelClassName($factoryName);
        }
        return false;
    }

    /**
     * Get factory class name for a resource
     *
     * @param string $modelClass
     * @return string|false
     */
    protected function _getResourceModelFactoryClassName($modelClass)
    {
        $classArray = explode('/', $modelClass);
        if (count($classArray) != 2) {
            return false;
        }

        list($module, $model) = $classArray;
        if (!isset($this->_xml->global->models->{$module})) {
            return false;
        }

        $moduleNode = $this->_xml->global->models->{$module};
        if (!empty($moduleNode->resourceModel)) {
            $resourceModel = (string)$moduleNode->resourceModel;
        } else {
            return false;
        }

        return $resourceModel . '/' . $model;
    }


    /**
     * Retrieve class name by class group
     *
     * This is different from the original method since
     * - it doesn't use the cache
     * - it doesn't take rewrites into account
     *
     * @param   string $groupType currently supported model, block, helper
     * @param   string $classId slash separated class identifier, ex. group/class
     * @param   string $groupRootNode optional config path for group config
     * @return  string
     */
    public function getGroupedClassName($groupType, $classId, $groupRootNode=null)
    {
        if (empty($groupRootNode)) {
            $groupRootNode = 'global/'.$groupType.'s';
        }

        $classArr = explode('/', trim($classId));
        $group = $classArr[0];
        $class = !empty($classArr[1]) ? $classArr[1] : null;

        $config = $this->_xml->global->{$groupType.'s'}->{$group};

        /**
         * Backwards compatibility for pre-MMDB extensions.
         * In MMDB release resource nodes <..._mysql4> were renamed to <..._resource>. So <deprecatedNode> is left
         * to keep name of previously used nodes, that still may be used by non-updated extensions.
         */
        if (isset($config->deprecatedNode)) {
            $deprecatedNode = $config->deprecatedNode;
            $configOld = $this->_xml->global->{$groupType.'s'}->$deprecatedNode;
            if (isset($configOld->rewrite->$class)) {
                $className = (string) $configOld->rewrite->$class;
            }
        }

        // Second - if entity is not rewritten then use class prefix to form class name
        if (empty($className)) {
            if (!empty($config)) {
                $className = $config->getClassName();
            }
            if (empty($className)) {
                $className = 'mage_'.$group.'_'.$groupType;
            }
            if (!empty($class)) {
                $className .= '_'.$class;
            }
            $className = uc_words($className);
        }

        return $className;
    }

}