<?php

$translations = array();

function t($msg)
{
    global $translations;
    // creating array from the csv file
    if (!$translations) {
        $row = 1;
        if (($handle = fopen("translations/tokyo4.csv", "r")) !== FALSE) {
            $translations = array();
            while (($data = fgetcsv($handle, 1000, ";")) !== FALSE) {
                $translationKey = $data[0];
                $translations[$translationKey] = array();
                $num = count($data);
                $row++;
                for ($c = 1; $c < $num; $c++) {
                    array_push($translations[$translationKey], $data[$c]);
                }
            }
            fclose($handle);
        }
    }

    // getting the language variable
    if ($GLOBALS['lang'] == 'hu') $langId = 0;
    if ($GLOBALS['lang'] == 'de') $langId = 1;
    if ($GLOBALS['lang'] == 'en') $langId = 2;

    // print out the messages
    if (array_key_exists($msg, $translations)) {
        echo $translations[$msg][$langId];
    } else {
        echo $msg;
    }

}
