<a id="logo" href="./index.php">Ourblog</a>
<a class="nav" href="./index.php">HomePage</a>
<?php 
	include("./config/database.php");

	//查询 遍历所有导航栏
	$sql = "select * from types;";
	$res = mysqli_query($link,$sql);
	$types = array();
	while($row = mysqli_fetch_array($res)){
		$types[] = $row;
	}
	foreach ($types as $value) {
		echo "<a class='nav' href='./index.php?type={$value['id']}'>{$value['name']}</a>";
	}
 ?>
 <hr style="margin-bottom: 50px;">
