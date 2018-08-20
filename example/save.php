<?php

error_reporting(E_ALL);
ini_set('display_errors', 'on');
require_once '../lib/FormProcessor.class.php';
$item_id = filter_input(INPUT_POST, 'item_id', FILTER_SANITIZE_NUMBER_INT);
$fp = new FormProcessor('products', $item_id);
$fp->save();
header('Location: manage.php');
