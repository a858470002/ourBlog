<!-- header -->
<a href="../index.php" id="logo" title="点击这里返回前台首页" >Ourblog</a>
<a href="./index.php" class="nav">博文管理</a>
<a href="./add.php" class="nav">写博文</a>
<hr style="margin-bottom: 50px;">
<?php 
	//判断用户是否登录
	session_start();
	if(empty($_SESSION['user'])){
		echo "<script>alert('请登录');window.location.href='./login.php';</script>";
	}
 ?>