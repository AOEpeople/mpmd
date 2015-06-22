<?php

namespace Mpmd\DependencyChecker;

/**
 * Collector
 *
 * @package Mpmd\DependencyChecker
 *
 * @author Fabrizio Branca
 * @since 2015-06-19
 */
class Collector
{

    /**
     * @var array
     */
    protected $classes = array();

    protected $currentFile;

    public function setCurrentFile($currentFile)
    {
        $this->currentFile = $currentFile;
    }

    public function getCurrentFile()
    {
        return $this->currentFile;
    }

    /**
     * Add a class that was found
     *
     * @param $class
     * @param $type
     * @throws \Exception
     */
    public function addClass($class, $type)
    {
        if (empty($this->currentFile)) {
            throw new \Exception('No current file set.');
        }
        if (!isset($this->classes[$this->currentFile])) {
            $this->classes[$this->currentFile] = array();
        }
        if (!is_array($this->classes[$this->currentFile][$type])) {
            $this->classes[$this->currentFile][$type] = array();
        }
        if (!in_array($class, $this->classes[$this->currentFile][$type])) {
            $this->classes[$this->currentFile][$type][] = $class;
        }
    }

    /**
     * Get classes
     *
     * @return array
     */
    public function getClasses()
    {
        return $this->classes;
    }

    /**
     * Return classes only
     *
     * @return array
     */
    public function flatten() {
        $flat = array();
        foreach ($this->classes as $file => $data) {
            foreach ($data as $type => $classes) {
                foreach ($classes as $class) {
                    if (!in_array($class, $flat)) {
                        $flat[] = $class;
                    }
                }
            }
        }
        sort($flat);
        $flat = array_unique($flat);
        return $flat;
    }

    /**
     * Remove classes that match the given filter (this is destructive!)
     * The filter can be an array or a callback
     *
     * @param array|callback $filter
     * @return array
     * @throws \InvalidArgumentException
     */
    public function filter($filter) {

        if (is_array($filter)) {
            $filter = array_map('strtolower', $filter);
        }

        $filteredClasses = array();
        foreach ($this->classes as $file => $data) {
            foreach ($data as $type => $classes) {
                foreach ($classes as $key => $class) {
                    if (is_array($filter)) {
                        if (in_array(strtolower($class), $filter)) {
                            unset($this->classes[$file][$type][$key]);
                            $filteredClasses[] = $class;
                        }
                    } elseif (is_callable($filter)) {
                        if (!$filter($class)) {
                            unset($this->classes[$file][$type][$key]);
                            $filteredClasses[] = $class;
                        }
                    } else {
                        throw new \InvalidArgumentException('Invalid filter');
                    }
                }
                if (count($this->classes[$file][$type]) == 0) {
                    unset($this->classes[$file][$type]);
                }
            }
            if (count($this->classes[$file]) == 0) {
                unset($this->classes[$file]);
            }
        }
        return array_unique($filteredClasses);
    }

    /**
     * Get class-to-class relations
     *
     * @return array
     */
    public function getClassRelations()
    {
        $relations = array();
        foreach ($this->getSourceClasses() as $sourceClass => $data) {
            $relations[$sourceClass] = array();
            foreach ($data as $type => $classes) {
                foreach ($classes as $targetClass) {
                    if ($targetClass != $sourceClass) {
                        if (!isset($relations[$sourceClass][$targetClass])) {
                            $relations[$sourceClass][$targetClass] = array();
                        }
                        $relations[$sourceClass][$targetClass][] = $type;
                    }
                }
            }
        }
        return $relations;
    }

    /**
     * Get classes
     *
     * @return array
     */
    public function getModuleRelations()
    {
        $relations = array();
        $divUtil = new \Mpmd\Util\Div();
        foreach ($this->getSourceClasses() as $sourceClass => $data) {
            $sourceModule = $divUtil->getModuleFromClass($sourceClass, 2);
            if (!is_array($relations[$sourceModule])) {
                $relations[$sourceModule] = array();
            }
            foreach ($data as $type => $classes) {
                foreach ($classes as $targetClass) {
                    $targetModule = $divUtil->getModuleFromClass($targetClass, 2);
                    if ($targetModule != $sourceModule) {
                        if (strpos($targetModule, ' ') === false) { // some "modules" say "[Could not resolve: ...]"
                            if (!is_array($relations[$sourceModule])) {
                                $relations[$sourceModule] = array();
                            }
                            if (!in_array($targetModule, $relations[$sourceModule])) {
                                $relations[$sourceModule][] = $targetModule;
                            }
                        }
                    }
                }
            }
        }
        return $relations;
    }

    /**
     * Returns the class for a given file (if any)
     *
     * @param $file
     * @return bool|string
     */
    public function getClassForFile($file)
    {
        if (!isset($this->classes[$file])) {
            throw new \InvalidArgumentException('File not found.');
        }
        if (!is_array($this->classes[$file]['class']) || count($this->classes[$file]['class']) == 0) {
            return false;
        }
        if (count($this->classes[$file]['class']) > 1) {
            // TODO: found more than one class in this file! ignoring this for now...
        }
        return reset($this->classes[$file]['class']);
    }

    /**
     * Get classes
     *
     * @return array
     */
    public function getModules()
    {
        return array_keys($this->getClassesByModule());
    }

    /**
     * Get classes grouped by module
     *
     * @return array
     */
    public function getClassesByModule()
    {
        $divUtil = new \Mpmd\Util\Div();

        $classesByModule = array();
        foreach ($this->flatten() as $class) {
            $module = $divUtil->getModuleFromClass($class, 2);
            if (!in_array($module, $classesByModule)) {
                $classesByModule[$module][] = $class;
            }
        }
        return $classesByModule;
    }

    /**
     * Get all classes found in the files (not in the depencencies)
     *
     * @return array
     */
    public function getSourceClasses()
    {
        $sourceClasses = array();
        foreach ($this->classes as $file => $data) {
            $sourceClass = $this->getClassForFile($file);
            if (!$sourceClass) {
                continue;
            }
            $sourceClasses[$sourceClass] = $data;
        }
        return $sourceClasses;
    }

    /**
     * Get all modules found in source classes
     *
     * @return array
     */
    public function getSourceModules()
    {
        $divUtil = new \Mpmd\Util\Div();
        $sourceModules = array();
        foreach ($this->getSourceClasses() as $sourceClass => $data) {
            $sourceModule = $divUtil->getModuleFromClass($class, 2);
            if (!in_array($sourceModule, $sourceModules)) {
                $sourceModules[] = $sourceModule;
            }
        }
        return $sourceModules;
    }

    /**
     * Get libraries
     *
     * @param array $files
     * @return array
     */
    public function getLibraries(array $files)
    {
        $divUtil = new \Mpmd\Util\Div();

        $libraries = array();
        foreach ($files as $class) {
            $lib = $divUtil->getModuleFromClass($class, 1);
            if (!in_array($lib, $libraries)) {
                $libraries[] = $lib;
            }
        }
        return $libraries;
    }
}