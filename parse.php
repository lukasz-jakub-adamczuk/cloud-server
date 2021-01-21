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

$prefix = date('Ymd_His');

if (isset($_FILES['myFile'])) {
    $source = $_FILES['myFile']['tmp_name'];
    $target = $basePath . '/uploads/' . $prefix .'_' . $_FILES['myFile']['name'];
    move_uploaded_file($source, $target);
    $sheetsFile = $target;
} else {
    $sheetsFile = $basePath . '/questions.xlsx';
}

try {
    $configFile = $basePath . '/config/sheets.yml';
    $sheets = Yaml::parse(file_get_contents($configFile));
} catch (ParseException $exception) {
    printf('Unable to parse the YAML string: %s', $exception->getMessage());
}

if (isset($_FILES['myFile'])) {
    $output = parseAllSheetsWithQuestions($sheets, $basePath, $sheetsFile, true);
    echo json_encode($output);
} else {
    $output = parseAllSheetsWithQuestions($sheets, $basePath, $sheetsFile);
    echo time()."\n";
}