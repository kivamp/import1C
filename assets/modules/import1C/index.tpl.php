<!DOCTYPE html>
<html lang="<? $modx->config['lang_code'] ?>">
<head>
	<meta charset="<? $modx->config['modx_charset'] ?>" />
	<title><?= $mod['title'] ?></title>
	<link rel="stylesheet" type="text/css" href="media/style/default/style.css?v=1.3.6">
    <link rel="stylesheet" type="text/css" href="media/style/common/font-awesome/css/font-awesome.min.css" />
    <script type="text/javascript" src="media/script/jquery/jquery.min.js"></script>
</head>
<body>
	<h1><i class="fa fa-download"></i> <?= $mod['title'] ?></h1>
	<div class="container" style="padding-bottom:2em">
<?php
	require $mod['path']."filebrowser.tpl.php";
	//require $mod['path']."upload.tpl.php";
	if ($_REQUEST['mode'] === "view") {
		require $mod['path']."view.tpl.php";
	}
	if ($_REQUEST['mode'] === "import") {
		echo $log;
	}
?>
	</div>
</body>
</html>