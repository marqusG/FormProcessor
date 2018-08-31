<?php

error_reporting(E_ALL);
ini_set('display_errors', 'on');
require_once '../lib/FormProcessor.class.php';
$fileName = filter_input(INPUT_POST, 'fileName', FILTER_SANITIZE_STRING);
$itemId = filter_input(INPUT_POST, 'itemId', FILTER_SANITIZE_NUMBER_INT);
$fp = new FormProcessor('products');
$fp->deleteDocument($fileName, $itemId);
