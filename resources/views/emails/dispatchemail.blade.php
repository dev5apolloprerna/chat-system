<?php

$root = $_SERVER['DOCUMENT_ROOT'];
$file = file_get_contents($root . '/mailers/dispatchemail.html', 'r');
$file = str_replace('#orderNo', $data['orderNo'], $file);
$file = str_replace('#courierName', $data['courierName'], $file);
$file = str_replace('#docketNo', $data['docketNo'], $file);
$file = str_replace('#link', $data['link'], $file);

// Output final content
echo $file;

?>
