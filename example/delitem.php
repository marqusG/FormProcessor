<?php

error_reporting(E_ALL);
ini_set('display_errors', 'on');
require_once '../lib/FormModel.class.php';
$config = require '../lib/config.php';
$root = getenv('DOCUMENT_ROOT').DIRECTORY_SEPARATOR;
$root_target_dir = $config['general']['root_target_dir'];
$item_id = filter_input(INPUT_POST, 'item_id', FILTER_SANITIZE_NUMBER_INT);
$confirm = filter_input(INPUT_POST, 'confirm');
if (isset($confirm) && isset($item_id)) {
    $fm = new FormModel('products');
    $documents = $fm->get_files('documents', $item_id);
    $pictures = $fm->get_files('pictures', $item_id);
    $docs = explode(';', $documents);
    $pics = explode(';', $pictures);
    if (count($docs) > 0) {
        foreach ($docs as $d) {
            if (file_exists($root.$root_target_dir.'documents/'.$d)) {
                echo $root.$root_target_dir.'documents/'.$d.' EXISTS<br>';
                unlink($root.$root_target_dir.'documents/'.$d);
            }
        }
    }
    if (count($pics) > 0) {
        foreach ($pics as $p) {
            if (file_exists($root.$root_target_dir.'pictures/'.$p)) {
                echo $root.$root_target_dir.'pictures/'.$p.' EXISTS<br>';
                unlink($root.$root_target_dir.'pictures/'.$p);
            }
        }
    }
    $fm->delete_item($item_id);
    header('Location: manage.php');
}
