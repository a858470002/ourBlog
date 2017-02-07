<html>
<head>
	<meta charset="utf-8">
	<link rel="stylesheet" type="text/css" href="main.css">
</head>
<body>
	<div id="body">
		<!-- header -->
		<?php
			include("./header.php");

			$type = isset($_GET['type']) ? $_GET['type'] : NULL;
			
			//如果点击了不同栏目，查询不同结果
			if ($type != NULL) {
				$sql = "SELECT * from article where `column`={$type};";
			} else {
				$sql = "SELECT * from article";
			}

			$sth = $dbh->query($sql);
			$article = $sth->fetchAll();
		?>
		<!-- content -->
		<?php 
			foreach ($article as $value) {
				if ($value['is_link'] == 0) {
					$href = "./content.php?id=".$value['id'];
					$link = "";
				} else {
					$href = $value['link'];
					$link = "<sup title='It is a link'>[link]</sup>";
				}
				echo "<ul>
						<li><a href=$href>{$value['title']}</a>$link</li>
					</ul>";
			}
		 ?>
	</div>
</body>
</html>
