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
			$res = mysqli_query($link,$sql);
			$article = array();
			while ($row = mysqli_fetch_array($res)) {
				$article[] = $row;
			};
		?>
		<!-- content -->
		<?php 
			foreach ($article as $value) {
				echo "<ul>
						<li><a href='./content.php?id={$value['id']}'>{$value['title']}</a></li>
					</ul>";
			}
		 ?>
	</div>
</body>
</html>
