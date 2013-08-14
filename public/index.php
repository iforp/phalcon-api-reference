<?php

/**
 * Prints human-readable information about a variable and terminate the current script.
 * Used for debugging purposes.
 * @param string $str
 * @param bool $is_dump
 */

function pd($str='this is print-die function', $is_dump=false)
{
    $backTrace = debug_backtrace();
    echo "<hr/><p>{$backTrace[0]['file']} ({$backTrace[0]['line']})</p><pre>";
    $is_dump || !$str || is_bool($str) || is_numeric($str)
        ? var_dump($str)
        : print_r($str);
    die;
}

error_reporting(E_ALL | E_NOTICE);


if(!extension_loaded('phalcon'))
{
    throw new Exception("Phalcon extension is required");
}


require '../app/bootstrap.php';