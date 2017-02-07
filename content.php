<html>
<head>
	<meta charset="utf-8">
	<link rel="stylesheet" type="text/css" href="./main.css">
	<style type="text/css">
		/* 换行 */
		* {word-wrap: break-word;}
	</style>
</head>
<body>
	<div id="body">
		<!-- header -->
		<?php
			include("./header.php");
			$id = $_GET['id'];

			//查询对应id的文章
			$sql = "SELECT * from article where id=$id";
			$data = $dbh->query($sql)->fetch();
		?>
		<!-- content -->
		<div style="width: 100%;">
			<h1><?php echo $data['title']; ?></h1>
			<!-- <?php echo "<pre>".htmlspecialchars($data['formaltext'])."</pre>"; ?> -->
			<?php echo "<pre>".$data['formaltext']."</pre>"; ?>
		</div>
	</div>
</body>
</html>
