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
     * Get classes
     *
     * @return array
     */
    public function getModules()
    {
        $divUtil = new \Mpmd\Util\Div();

        $modules = array();
        foreach ($this->flatten() as $class) {
            $module = $divUtil->getModuleFromClass($class, 2);
            if (!in_array($module, $modules)) {
                $modules[] = $module;
            }
        }
        return $modules;
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