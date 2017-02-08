<?php 
	include("./config/database.php");
    header("content-type:text/html;charset=utf-8");

	//checkout user
	session_start();
	if(!isset($_SESSION['uid'])){
		echo "<script>alert('请登录');window.location.href='./admin/login.php';</script>";
        exit;
	} else {
        $user_id = $_SESSION['uid'];
    }