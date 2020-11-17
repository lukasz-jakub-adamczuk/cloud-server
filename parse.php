<?php

if (isset($_SERVER['REMOTE_ADDR']) && $_SERVER['REMOTE_ADDR'] == '127.0.0.1') {
    ini_set('display_errors', 1);
    error_reporting(E_ALL);
}

require_once __DIR__ . '/vendor/autoload.php';

use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Yaml\Exception\ParseException;


require_once __DIR__ . '/String.php';
require_once __DIR__ . '/Parser.php';

$basePath = dirname(__FILE__);

try {
    $configFile = $basePath . '/config/sheets.yml';
    $sheets = Yaml::parse(file_get_contents($configFile));
} catch (ParseException $exception) {
    printf('Unable to parse the YAML string: %s', $exception->getMessage());
}

parseAllSheetsWithQuestions($sheets, $basePath);

echo time()."\n";