<?php
error_reporting(E_ALL);
ini_set('display_errors', 'on');
require_once "../lib/FormTable.class.php";
header('Content-type: text/html; charset=utf-8');
session_start();
 ?>
<!DOCTYPE html>
<html lang="en">
	<head>
		<style>
		table{
			border:2px solid #427120;
			background: #fcfcfc;
			border-collapse: collapse;
			margin-top: 20px;
		}
		thead td{
			font-weight: bold;
			border-bottom: 2px solid #427120;
			border-left: 1px solid #427120;
			border-right: 1px solid #427120;
			padding: 6px 10px;
			text-align: center;
		}
		tbody td{
			border-bottom: 1px solid #427120;
			border-left: 1px solid #427120;
			border-right: 1px solid #427120;
			padding: 6px 10px;
		}
    a.button{
      background-color: #eee;
      padding: 6px 10px;
      color: #000!important;
      border-radius: 6px;
      min-width: 80px;
      margin-left: 10px;
    }
    input[type="submit"]{
      border: none;
			background: none;
			background-color: red;
			padding: 6px 10px;
			color: #fff!important;
			font-weight: bold;
			border-radius: 6px;
			min-width: 80px;
			cursor:pointer;
    }
    input[type="submit"][name="edit"]{
      border: none;
			background: none;
			background-color: #eee;
			padding: 6px 10px;
			color: #000!important;
			font-weight: bold;
			border-radius: 6px;
			min-width: 80px;
			cursor:pointer;
    }
		</style>
	</head>
	<body>
		<header id="header">
		</header>
		<section id="content">
			<div class="container">
				<div class="center">
					<a class="button" href='add.php'>Aggiungi destinatario</a>
          <?php
                    $ft = new FormTable('products');
                        echo $ft->build_table();
                ?>
				</div>
			</div>
		</section>
	</body>
</html>
