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

} 