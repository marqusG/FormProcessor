<?php

error_reporting(E_ALL);
ini_set('display_errors', 'on');
require_once '../lib/FormModel.class.php';
$config = require '../lib/config.php';
$root = getenv('DOCUMENT_ROOT').DIRECTORY_SEPARATOR;
$root_target_dir = $config['general']['rootTargetDir'];
$itemId = filter_input(INPUT_POST, 'itemId', FILTER_SANITIZE_NUMBER_INT);
$confirm = filter_input(INPUT_POST, 'confirm');
if (isset($confirm) && isset($itemId)) {
    $fm = new FormModel('products');
    $documents = $fm->getFiles('documents', $itemId);
    $pictures = $fm->getFiles('pictures', $itemId);
    $docs = explode(';', $documents);
    $pics = explode(';', $pictures);
    if (count($docs) > 0) {
        foreach ($docs as $d) {
            if (file_exists($root.$rootTargetDir.'documents/'.$d)) {
                echo $root.$rootTargetDir.'documents/'.$d.' EXISTS<br>';
                unlink($root.$rootTargetDir.'documents/'.$d);
            }
        }
    }
    if (count($pics) > 0) {
        foreach ($pics as $p) {
            if (file_exists($root.$rootTargetDir.'pictures/'.$p)) {
                echo $root.$rootTargetDir.'pictures/'.$p.' EXISTS<br>';
                unlink($root.$rootTargetDir.'pictures/'.$p);
            }
        }
    }
    $fm->deleteItem($itemId);
    header('Location: manage.php');
}
