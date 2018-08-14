<?php
error_reporting(E_ALL);
ini_set('display_errors', 'on');
header('Content-type: text/html; charset=utf-8');
require_once "../lib/FormBuilder.class.php";
$item_id = filter_input(INPUT_POST, 'item_id', FILTER_SANITIZE_NUMBER_INT);
 ?>
<!DOCTYPE html>
<html lang="en">
	<head>
    <title>Edit</title>
		<style>
    fieldset{
    	width: 35%;
      border: none;
      margin: 6px 0;
    }
    fieldset label{
    	text-align: left;
      margin-right: 10px;
    }
		fieldset input:not([type="checkbox"]):not([type="radio"]),
		fieldset select,
		fieldset textarea{
			float: right;
		}
    input[type="submit"]{
      border: none;
      background: none;
      background-color: #136007;
      padding: 6px 10px;
      color: #fff!important;
      font-weight: bold;
      border-radius: 6px;
      min-width: 80px;
      cursor: pointer;
    }
    a.btn-cancel{
      background-color: #eee;
      padding: 6px 10px;
      color: #000!important;
      border-radius: 6px;
      min-width: 80px;
      margin-left: 10px;
      cursor: pointer;
    }
    </style>
	</head>
	<body>
		<header id="header">
		</header>
		<section id="content">
			<div class="container">
				<div class="center">
					<?php
                    $fb = new FormBuilder('products', $item_id);
                    $form = $fb->build_form();
                    echo $form;
                    ?>
				</div>
			</div>
		</section>
	</body>
</html>
