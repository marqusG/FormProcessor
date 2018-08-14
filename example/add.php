<?php
error_reporting(E_ALL);
ini_set('display_errors', 'on');
header('Content-type: text/html; charset=utf-8');
require_once "../lib/FormBuilder.class.php";
?>
<!DOCTYPE html>
<html lang="en">
	<head>
		<title>
			Add
		</title>
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
		a.button{
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
				<div class="row">
					<div class="col-xs-12">
					<?php
            $fb = new FormBuilder('produtcs');
            $form = $fb->build_form();
            echo $form;
          ?>
					</div>
				</div>
			</div>
		</section>
	</body>
</html>
