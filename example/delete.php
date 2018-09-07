<?php
error_reporting(E_ALL);
ini_set('display_errors', 'on');
header('Content-type: text/html; charset=utf-8');
$itemId = filter_input(INPUT_POST, 'itemId', FILTER_SANITIZE_NUMBER_INT);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <title>Delete</title>
    <style>
        input[type="submit"]{
			border: none;
			background: none;
			background-color: red;
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
			text-decoration: none;
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
if (isset($itemId) && !isset($confirm)) {
    ?>
                <center>
                    <form method="post" action="delitem.php">
                        <label>
                            Are you sure you want to delete this record?
                        </label>
                        <br />
                        <br />
                        <input type="hidden" value="<?php echo $itemId; ?>" name="itemId" />
                        <input type="submit" value="Yes, delete it!" name="confirm" />
                        <a class="btn-cancel" href="manage.php">Let me think about</a>
                    </form>
                </center>
                <?php
}
?>
            </div>
        </div>
    </section>
</body>

</html>