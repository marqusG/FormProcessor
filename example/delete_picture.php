<?php

error_reporting(E_ALL);
ini_set('display_errors', 'on');
require_once '../lib/FormProcessor.class.php';
$file_name = filter_input(INPUT_POST, 'file_name', FILTER_SANITIZE_STRING);
$item_id = filter_input(INPUT_POST, 'item_id', FILTER_SANITIZE_NUMBER_INT);
$fp = new FormProcessor('products');
$fp->delete_picture($file_name, $item_id);
