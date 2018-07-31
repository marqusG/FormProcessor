<?php
error_reporting(E_ALL);
ini_set('display_errors', 'on');
require_once "lib/FormModel.class.php";
$item_id = filter_input(INPUT_POST, 'item_id', FILTER_SANITIZE_NUMBER_INT);
$confirm = filter_input(INPUT_POST, 'confirm');
if (isset($confirm) && isset($item_id)) {
    $fm = new FormModel();
    $fm->delete_item($item_id);
    header('Location: manage.php');
}
