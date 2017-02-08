<?php 
    header("content-type:text/html;charset=utf-8");
	//判断用户是否登录
	session_start();
	if(!isset($_SESSION['uid'])){
		echo "<script>alert('请登录');window.location.href='./login.php';</script>";
        exit;
	} else {
        $user_id = $_SESSION['uid'];
    }
 ?>