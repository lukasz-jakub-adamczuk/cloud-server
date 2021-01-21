<?php

header('Access-Control-Allow-Origin: *');


// require_once dir(__FILE__) . '/vendor/autoload.php';
require_once './vendor/autoload.php';

use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Yaml\Exception\ParseException;

try {
    $basePath = dirname(__FILE__);
    $configFile = $basePath . '/config/hashes.yml';
    $hashes = Yaml::parse(file_get_contents($configFile));
} catch (ParseException $exception) {
    printf('Unable to parse the YAML string: %s', $exception->getMessage());
}

// poor sanitize
$code = isset($_POST['code']) ? strip_tags($_POST['code']) : null;
$hash = isset($_POST['hash']) ? strip_tags($_POST['hash']) : null;

// get file content
if ($code) {
    header('Content-Type: plain/text');

    if (count(explode('/', $code)) == 2) {
        $file = $code;
    } else {
        if (strlen($code) != 40) {
            $code = sha1($code);
        }
    }

    if (isset($hashes[$code])) {
        $file = $hashes[$code];
    }

    if (isset($file)) {
        echo file_get_contents(dirname(__FILE__).'/exams/'.$file.'.md');
    } else {
        echo 'Not found.';
    }
    return;
}

// get file timestamp
if ($hash) {
    // header('Content-Type: html/text');

    if (isset($hashes[$hash])) {
        $file = $hashes[$hash];
    } else {
        echo 'Not found.';
    }

    if (isset($file)) {
        $timestamp = (int)filemtime(dirname(__FILE__).'/exams/'.$file.'.md') * 1000;
        echo $timestamp;
    }
    return;
}