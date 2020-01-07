<?php

require_once('simple_wiki_markup.php');
$sSampleText = file_get_contents("sample.txt");
$html = swm::markup($sSampleText);
echo $html;