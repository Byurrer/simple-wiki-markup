<?php

ini_set('error_reporting', E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

require_once('simple-wiki-markup.php');
$sSampleText = file_get_contents("sample.txt");
$html = swm::markup($sSampleText);
echo $html;