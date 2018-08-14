<?php
error_reporting(E_ALL);
ini_set('display_errors', 'on');
require_once "../lib/FormSaver.class.php";
$user = 'Marco/personal/';
$item_id = filter_input(INPUT_POST, 'item_id', FILTER_SANITIZE_NUMBER_INT);
// $fs = new FormSaver($item_id); //using root_target_dir as specified in config file
$fs = new FormSaver('products', $user, $item_id); //passing dinamically a root_target_dir
$fs->save();
header('Location: manage.php');
