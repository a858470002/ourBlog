<?php
	session_start();
	header("content-type:text/html;charset=utf-8");
    include("./database.php");
	include("../model/main.php");

	//获取地址中的action
	if (isset($_GET["action"])) {
		$action = $_GET["action"];
	} else {
        header("Location: /admin/index.php");
        exit;
    }
    
    switch ($action) {
        //登录判断
        case "login":
            try {
                $user_id = login($_POST,$link);
            } catch (InvalidArgumentException $e) {
                echo "<script>alert('{$e->getMessage()}');window.location.href='../admin/login.php'</script>";
                exit;
            }

            $_SESSION['uid'] = $user_id;
            session_regenerate_id();
            header('Location: /admin/index.php'); 
			break;

		//添加文章
		case "add":
			$user_id = loginCheck();

            try {
                addArticle($_POST,$link,$user_id);
            } catch (InvalidArgumentException $e) {
                echo "<script>alert('{$e->getMessage()}');window.location.href='../admin/add.php'</script>";
                exit;
            }

            echo "<script>alert('添加成功');window.location.href='../admin/index.php';</script>";

			break;

		//修改文章
		case "edit":
			$user_id = loginCheck();
            $article_id = $_GET['id']; 

            try {
                editArticle($_POST,$link,$user_id,$article_id);
            } catch (InvalidArgumentException $e) {
                echo "<script>alert('{$e->getMessage()}');window.location.href='../admin/add.php'</script>";
                exit;
            }

            echo "<script>alert('修改成功');window.location.href='../admin/index.php';</script>";

			break;

		//删除文章
		case "delete":
			$user_id = loginCheck();
            $article_id = $_GET['id'];

            try {
                deleteArticle($link,$user_id,$article_id);
            } catch (InvalidArgumentException $e) {
                echo "<script>alert('{$e->getMessage()}');window.location.href='../admin/add.php'</script>";
                exit;
            }

            echo "<script>alert('删除成功');window.location.href='../admin/index.php';</script>";

			break;
	}

	function loginCheck() 
	{
		if(!isset($_SESSION["uid"])){
			echo "<script>alert('请登录');window.location.href='../admin/login.php';</script>";
			exit;
		} 
        return $_SESSION["uid"];
	}