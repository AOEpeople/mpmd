<?php

function autoload($className)
{
    $className = ltrim($className, '\\');

    if (strpos($className, 'Mpmd') !== 0) {
        return false;
    }

    $fileName = '';
    $namespace = '';
    if ($lastNsPos = strrpos($className, '\\')) {
        $namespace = substr($className, 0, $lastNsPos);
        $className = substr($className, $lastNsPos + 1);
        $fileName  = str_replace('\\', DIRECTORY_SEPARATOR, $namespace) . DIRECTORY_SEPARATOR;
    }
    $fileName .= str_replace('_', DIRECTORY_SEPARATOR, $className) . '.php';

    require  __DIR__. '/../src/' . $fileName;
}

spl_autoload_register('autoload');

require_once __DIR__ . '/HandlerTestCase.php';