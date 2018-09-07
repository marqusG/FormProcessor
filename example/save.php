<?php

error_reporting(E_ALL);
ini_set('display_errors', 'on');
require_once 'FormProcessor/FormProcessor.class.php';
$itemId = filter_input(INPUT_POST, 'itemId', FILTER_SANITIZE_NUMBER_INT);
$fp = new FormProcessor('products', $itemId);
$fp->save();
header('Location: manage.php');
