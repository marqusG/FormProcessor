<?php

error_reporting(E_ALL);
ini_set('display_errors', 'on');
require_once '../lib/FormProcessor.class.php';
$fp = new FormProcessor('products');
echo '<pre>';
var_dump($fp);
echo '</pre>';
