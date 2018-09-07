<?php
error_reporting(E_ALL);
ini_set('display_errors', 'on');
header('Content-type: text/html; charset=utf-8');
require_once 'FormProcessor/FormProcessor.class.php';
$itemId = filter_input(INPUT_POST, 'itemId', FILTER_SANITIZE_NUMBER_INT);
?>
<!DOCTYPE html>
<html lang="en">
	<head>
    <title>Edit</title>
		<style>
    fieldset{
    	width: 35%;
      border: none;
      margin: 20px 0;
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
      .previewer{
        width: 100%;
        display: block;
        min-height: 100px;
        max-height: 200px;
        height: auto;
        overflow: auto;	
      }
      #pictures-uploader .previewer img{
        margin-right: 4px;
        position: relative;
        width: 150px;
        text-align: center;
        padding: 4px;
      }
      #documents-uploader .previewer img{
        margin-right: 4px;
        position: relative;
        width: 100px;
        text-align: center;
        padding: 4px;
      }
      #documents-uploader .previewer p{
        font-size: .8em;
        max-width: 100px;
        word-wrap: break-word;
        text-align: center;
      }
      .controls{
        display: block;
        position: absolute;
        bottom: 4px;
        left: 0;
        width: 100%;
        text-align: center;
      }
      .controls p{
        text-align: center;
        width:100%;
      }
      .img-wrapper,
      .icon-wrapper{
        float: left;
        height: 160px;
        margin: 10px 4px 10px 0;
        position: relative;
        width: 120px;
        text-align: center;
        padding: 4px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2)
      }
      .img-wrapper img{
        width: 100%;
      }
      .uploader{
        clear:both;
      }
      .icon-wrapper img{
        margin-right: 4px;
        position: relative;
        width: 100px;
        text-align: center;
        padding: 4px;
      }
      .icon-wrapper p{
        font-size: .8em;
        word-wrap: break-word;
      }    
      .btn-danger{
        background: red;
        padding: 3px 6px;
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
					<?php
    $fp = new FormProcessor('products', $itemId);
    echo $fp->buildForm();
    ?>
				</div>
			</div>
		</section>
    <script src="https://code.jquery.com/jquery-3.3.1.min.js" integrity="sha256-FgpCb/KJQlLNfOu91ta32o/NMZxltwRo8QtmkMRdAu8=" crossorigin="anonymous"></script>
    <script>
      <?php 
      echo $fp->printJsForEditPage();
      ?>
    </script>
	</body>
</html>
