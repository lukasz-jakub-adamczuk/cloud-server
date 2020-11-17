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

// sanitize
$code = isset($_POST['code']) ? $_POST['code'] : null;
$hash = isset($_POST['hash']) ? $_POST['hash'] : null;

// get file content
if ($code) {
    header('Content-Type: plain/text');

    if (strlen($code) != 40) {
        $code = sha1($code);
    }
    
    if (isset($hashes[$code])) {
        $file = $hashes[$code];
    } else {
        echo 'Not found.';
    }

    if (isset($file)) {
        echo file_get_contents(dirname(__FILE__).'/exams/'.$file.'.md');
    }
    return;
}

// get file timestamp
if ($hash) {
    header('Content-Type: plain/text');

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