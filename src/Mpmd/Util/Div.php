<?php

namespace Mpmd\Util;

/**
 * Class Div
 *
 * @author Fabrizio Branca
 * @since 2015-06-19
 */
class Div {

    /**
     * Get module from classname
     *
     * @param $className
     * @return string
     */
    public function getModuleFromClass($className, $parts=2)
    {
        $parts = array_slice(explode('_', $className), 0, $parts);
        return implode('_', $parts);
    }

    /**
     * Get PHP's default classes
     *
     * @return array
     */
    public function getDefaultClasses()
    {
        return unserialize(exec('php -r \'echo serialize(get_declared_classes());\''));
    }

    /**
     * Find all files in a given directory
     *
     * @param $sourceDir
     * @return array
     */
    public function findFiles($sourceDir)
    {
        $files = array();
        $fileinfos = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($sourceDir));
        foreach($fileinfos as $pathname => $fileinfo) {
            if (!$fileinfo->isFile()) {
                continue;
            }
            $files[] = $pathname;
        }
        return $files;
    }

    /**
     * Get all modules found in config xml
     *
     * @return array
     */
    public function getAllModules()
    {
        $modules = array();
        foreach (\Mage::getConfig()->getNode('modules')->children() as $moduleName => $data) {
            $modules[] = $moduleName;
        }
        return $modules;
    }

    /**
     * Get declared dependencies for a module
     *
     * @param $moduleName
     * @return array
     */
    public function getDeclaredDepenenciesForModule($moduleName)
    {
        $declaredDepenencies = array();
        $moduleConfig = \Mage::getConfig()->getNode('modules/' . $moduleName);
        foreach ($moduleConfig->depends->children() as $dependency) {/* @var $dependency \Mage_Core_Model_Config_Element  */
            $declaredDepenencies[] = $dependency->getName();
        }
        return $declaredDepenencies;
    }

    /**
     * Glob matching on a given string (instead of filesystem paths)
     * Supports * and ?
     *
     * @param $string
     * @param $pattern
     * @param bool $ignoreCase
     * @return bool
     * @see http://stackoverflow.com/questions/13913796/php-glob-style-matching
     */
    public function matchLikeGlob($string, $pattern, $ignoreCase=false)
    {
        $expr = preg_replace_callback('/[\\\\^$.[\\]|()?*+{}\\-\\/]/', function($matches) {
            switch ($matches[0]) {
                case '*':
                    return '.*';
                case '?':
                    return '.';
                default:
                    return '\\'.$matches[0];
            }
        }, $pattern);

        $expr = '/^'.$expr.'$/';
        if ($ignoreCase) {
            $expr .= 'i';
        }
        return (bool)preg_match($expr, $string);
    }

    /**
     * Compares a list of subjects to a list of glob patterns and returns the matches
     *
     * @param array $subjects
     * @param array $patterns
     * @param array $matchCount
     * @return array
     */
    public function globArray(array $subjects, array $patterns, array &$matchCount=array())
    {
        $patterns = array_unique($patterns);
        $matchCount = array_combine($patterns, array_fill(0, count($patterns), 0));
        $results = array();
        foreach ($subjects as $subject) {
            foreach ($patterns as $pattern) {
                if ($this->matchLikeGlob($subject, $pattern)) {
                    $results[] = $subject;
                    $matchCount[$pattern]++;
                    continue;
                }
            }
        }
        return $results;
    }

} 