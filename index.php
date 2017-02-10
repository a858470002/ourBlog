<?php
	include("./header.php");

	$column = isset($_GET['type']) ? $_GET['type'] : NULL;
	if ($column != NULL){
		$column = filter_var($column,FILTER_VALIDATE_INT,array('options' => array('min_range' => 1)));
	    if (!$column) {
	        echo "<script>alert('非法的column id');window.location.href='./index.php'</script>";
	        exit;
	    } 
	} 
	//如果点击了不同栏目，查询不同结果
	if ($column != NULL) {
		$sql = "SELECT * from article where `column`= $column ";
	} else {
		$sql = "SELECT * from article ";
	}

	$sth = $dbh->query($sql);
	$article = $sth->fetchAll();
?>
<html>
<head>
	<meta charset="utf-8">
	<link rel="stylesheet" type="text/css" href="main.css">
</head>
<body>
	<div id="body">
		<!-- header -->
		<?php include("./nav.php"); ?>
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
						<li><a href='".$href."'>".htmlspecialchars($value['title'])."</a>$link</li>
					</ul>";
			}
		 ?>
	</div>
</body>
</html>
