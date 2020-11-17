<?php

function parseSingleSheetsWithQuestions($sheetNumber) {
    if ($xlsx = SimpleXLSX::parse($basePath . '/questions.xlsx')) {
        // print_r($xlsx->rows($sheetNumber));
        print_r($xlsx->sheetNames());
    } else {
        echo SimpleXLSX::parseError();
    }
}


function parseAllSheetsWithQuestions($sheets, $basePath) {
    if ($xlsx = SimpleXLSX::parse($basePath . '/questions.xlsx')) {
        foreach ($xlsx->sheetNames() as $sk => $name) {
            $name = strtolower($name);
            $qs = parseSheet($xlsx->rows($sk));
            $generated = time() * 1000;
            $header = '';
            $header .= '# exam:        '.$name."\n";
            $header .= '# questions:   '.$sheets[$name]['questions']."\n";
            $header .= '# duration:    '.$sheets[$name]['duration']."\n";
            $header .= '# pass:        '.$sheets[$name]['pass']."\n";
            $header .= '# description: '.$sheets[$name]['name']."\n";
            $header .= '# generated:   '.$generated."\n";

            $content = $header."\n".prepareExamQuestion($qs);
            $filename = $basePath . '/exams/' . slugify($name);

            // if (!mkdir($basePath . '/exams', 0777, true)) {
            //     die('Failed to create folders...');
            // }
            
            if (file_exists($filename.'.timestamp')) {
                $oldTimestamp = file_get_contents($filename.'.timestamp');
                file_put_contents($filename.'-tmp.md', str_replace($generated, $oldTimestamp, $content));
            } else {
                // files generates for first run
                file_put_contents($filename.'.timestamp', $generated);
                file_put_contents($filename.'.md', $content);
            }

            if (file_exists($filename.'.md') && file_exists($filename.'-tmp.md')) {
                if (md5_file($filename.'.md') != md5_file($filename.'-tmp.md')) {
                    // need to serve new parsed file
                    file_put_contents($filename.'.timestamp', $generated);
                    file_put_contents($filename.'.md', $content);
                    unlink($filename.'-tmp.md');
                }
            }
        }
    } else {
        echo SimpleXLSX::parseError();
    }
}


function prepareExamQuestion($questions) {
    $str = '';
    foreach ($questions as $q) {
        $str .= $q['name'] . "\n";
        if ($q['params']) {
            $parameters = [];
            foreach ($q['params'] as $pk => $p) {
                $parameters[] = '"'.$pk.'": "'.$p.'"';
            }
            $str .= '{'.implode(', ', $parameters).'}'."\n";
        }
        if (isset($q['answers'])) {
            foreach ($q['answers'] as $ans) {
                $str .= $ans . "\n";
            }
        }
        $str .= "\n";
    };
    return $str;
}

function parseSheet($sheet) {
    $qs = [];
    foreach ($sheet as $rk => $row) {
        if (count($row) > 3) {
                    
            if ($row[0] == 'Lp.' || $row[1] == 'Question' || $row[2] == 'Options' || $row[3] == 'Answer') {
                // echo 'Skip because header';
            } elseif ($row[1] == '' || $row[2] == '') {
                // empty question or anwsers';
            } else {
                $q = [];
                $params = [];
                $params['eqi'] = trim($row[0]);
                $params['eri'] = ($rk + 1);
                
                if (!empty($row[1])) {
                    // question
                    $name = trim($row[1]);

                    $name = str_replace('-', '[dash]', $name);
                    $name = str_replace('    ', '[tab]', $name);
                    
                    $q['name'] = $name;
                }
                
                if (!empty($row[2])) {
                    // possible answers
                    $answers = trim($row[2]);
                    if ($answers[0] == '-' || $answers[0] == '+') {
                        // special parsing for multi-line answers
                        $ans = explode("\n", $answers);
                        $opts = [];
                        foreach ($ans as &$a) {
                            if (trim($a) != '') {
                                if ($a[0] == '-' || $a[0] == '+') {
                                    $q['answers'][] = implode("[br]", $opts);
                                    $opts = [];
                                    $opts[] = trim($a);
                                } else {
                                    $opts[] = trim($a);
                                }
                            }
                        }
                        $q['answers'][] = implode("[br]", $opts);
                        array_shift($q['answers']);
                        unset($a);
                        $params['shuffle_answers'] = 'false';
                        // $q['name'] .= "\n\n" . '__Question has multi-line answers. Only few are real answers, but each line is possible to check. This will be fixed sooner or later.__';
                    } else {
                        // answers
                        $ans = explode("\n", $answers);
                        foreach ($ans as &$a) {
                            if (trim($a) != '') {
                                if (strpos($a, '==') != false) {
                                    $q['answers'][] = '+'.trim($a);
                                    $params['type'] = 'matching';
                                    $params['shuffle_answers'] = 'false';
                                } else {
                                    $q['answers'][] = '-'.trim($a);
                                }
                            }
                        }
                        unset($a);
                    }
                }
                if (!empty($row[3])) {
                    // answers
                    $ans = explode(',', trim($row[3]));
                    foreach ($ans as &$a) {
                        $q['correct'][] = strToLower(trim($a));
                    }
                    unset($a);
                    if (isset($q['answers'])) {
                        foreach ($q['answers'] as $ak => $a) {
                            if (strlen($a) >= 3) {
                                $keyToReplace = substr($q['answers'][$ak], 0 ,3);
                                $keyToCompare = strToLower(substr($q['answers'][$ak], 0 ,3));
                                if ($keyToCompare[2] == ')' || $keyToCompare[2] == '.') {
                                    if (in_array($keyToCompare[1], $q['correct'])) {
                                        $q['answers'][$ak][0] = '+';
                                    }
                                }
                            }
                        }
                    }
                }
                if (!empty($row[4])) {
                    // comment
                    $search = ["\\w", '"', "\n", "\n\r"];
                    $replace = ['\\\\w', '\"', '\n', '\n'];

                    $comment = str_replace($search, $replace, trim($row[4]));
                    
                    // replace whitespaces from comment which is part of JSON
                    $comment = trim(preg_replace('/\s+/', ' ', $comment));

                    $params['comment'] = $comment;
                }
                if (!empty($row[6])) {
                    // image if exists
                    $value = trim($row[6]);
                    if (substr($value, 0, 4) == 'http') {
                        $params['image'] = $value;
                    }
                    if ($value[0] == '{') {
                        $parameters = json_decode($row[6], true);
                        foreach ($parameters as $pk => $p) {
                            $params[$pk] = $p;
                        }
                    }
                }
                
                // clear letters
                $re = '/^([+|-])\s?([a-zA-Z]?)([\.\s\)])(\s*)/m';
                $subst = '$1 ';

                $q['answers'] = preg_replace($re, $subst, $q['answers']);

                if (count($params)) {
                    $q['params'] = $params;
                }
                // print_r($q);
                $qs[] = $q;
            }
        }
    }
    return $qs;
}