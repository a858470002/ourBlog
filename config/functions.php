<?php
	session_start();
	header('content-type:text/html;charset=utf-8');
	include("./database.php");

	//获取地址中的action
	if (isset($_GET['action'])) {
		$action = $_GET['action'];
	} else {
		echo "<script>alert('Illegal Operation !');window.location.href='../admin/index.php';</script>";
	}
	
	switch($action){
		//登录判断
		case 'login':
			$email = $_POST['email'];
			$password = md5($_POST['password']);

			$pattern = "/^([0-9A-Za-z\\-_\\.]+)@([0-9a-z]+\\.[a-z]{2,3}(\\.[a-z]{2})?)$/i";
			if ( preg_match( $pattern, $email ) ) {
				continue;
			} else {
				echo "<script>alert('Illegal E-mail Address !');window.location.href='../admin/index.php';</script>";
				exit;
			}

			//拼接select语句执行得到结果，并跳转
			$sql = "SELECT * from `user` where `email`='{$email}' and `password`='{$password}'";
			$res = mysqli_query($link,$sql);
			if ($res && mysqli_num_rows($res)>0) {
				$user = mysqli_fetch_assoc($res);
				$_SESSION['user']=$user['id'];	//存session
				echo "<script>alert('验证成功');window.location.href='../admin/index.php';</script>";
			} else {
				echo "<script>alert('验证失败');window.location.href='../admin/login.php';</script>";
			}
			
			break;

		//添加文章
		case 'add':
			loginCheck();
			$title = $_POST['title'];
			$formaltext = mysqli_real_escape_string($link,$_POST['formaltext']);//拼接sql专用的字符串处理
			$column = $_POST['column'];

			//拼接insert语句,执行，得到结果
			$sql = "INSERT into `article`(title,formaltext,`column`) VALUES ('{$title}','{$formaltext}','{$column}');";
			$res = mysqli_query($link,$sql);
			if($res && mysqli_affected_rows($link)>0){
				echo "<script>alert('添加成功');window.location.href='../admin/index.php';</script>";
			} else {
				echo "<script>alert('添加失败');window.location.href='../admin/add.php';</script>";
			}

			break;

		//修改文章
		case 'edit':
			loginCheck();
			$id = $_GET['id'];
			$title = $_POST['title'];
			$formaltext = mysqli_real_escape_string($link,$_POST['formaltext']);	//拼接sql专用的字符串处理
			$column = $_POST['column'];

			//拼接update语句,执行，判断并跳转
			$sql = "UPDATE `article` set `title`='{$title}',`formaltext`='{$formaltext}',`column`='{$column}' where `id`={$id};";
			$res = mysqli_query($link,$sql);
			if($res){
				echo "<script>alert('修改成功');window.location.href='../admin/index.php';</script>";
			} else {
				echo "<script>alert('修改失败');window.location.href='../admin/edit.php?id={$id}';</script>";
			}

			break;

		//删除文章
		case 'delete':
			loginCheck();
			$id = $_GET['id'];
			
			//拼接delete语句并跳转
			$sql = "DELETE from `article` where `id`={$id}";
			$res = mysqli_query($link,$sql);
			if($res){
				echo "<script>alert('删除成功');window.location.href='../admin/index.php';</script>";
			} else {
				echo "<script>alert('删除失败');window.location.href='../admin/index.php';</script>";
			}

			break;

		//添加标签
		case 'tag':
			loginCheck();
			$text = $_POST['text'];
			$id = $_POST['id'];
			
			//执行，判断并跳转
			$sql = "UPDATE `article` set `tag`='{$text}' where `id`={$id};";
			$res = mysqli_query($link,$sql);
			echo $res?"success":"failed";

			break;

		//添加标签
		case 'addTag':
			loginCheck();
			$articleId = $_POST['articleId'];
			$tagId = $_POST['id'];

			//
			$sql = "SELECT * from article where `id`='{$articleId}'";
			$res = mysqli_query($link,$sql);
			if($res && mysqli_num_rows($res)>0){
				$data = mysqli_fetch_assoc($res);
			}
			$newData = $data['tag'].$tagId.",";

			$sql = "UPDATE `article` set `tag`='{$newData}' where `id`={$articleId};";
			$res = mysqli_query($link,$sql);
			echo $res?"success":"failed";

			break;
		
		//删除标签
		case 'reduceTag':
			loginCheck();
			$articleId = $_POST['articleId'];
			$tagId = $_POST['id'];

			//查找该文章的所有标签
			$sql = "SELECT * from article where `id`='{$articleId}'";
			$res = mysqli_query($link,$sql);
			if($res && mysqli_num_rows($res)>0){
				$data = mysqli_fetch_assoc($res);
			}
			$arr = explode(",", $data['tag']);
			foreach ($arr as $values) {
				if ($values == $tagId) {
					//如果相等则跳过
				} else {
					$newArr[] = $values;
				}
			}
			$str = implode(",", $newArr);

			//更新标签
			$sql = "UPDATE `article` set `tag`='{$str}' where `id`={$articleId};";
			$res = mysqli_query($link,$sql);
			echo $res?"success":"failed";

			break;

		//添加新标签
		case 'newTag':
			loginCheck();
			$text = $_POST['text'];
			$articleId= $_POST['id'];

			//拼接insert语句,执行，得到结果
			$sql = "INSERT into `tag` (`name`) VALUES ('{$text}');";
			$res = mysqli_query($link,$sql);
			$tagId = mysqli_insert_id($link);
			if ($res && mysqli_affected_rows($link)>0) {

				$sql = "SELECT * from article where `id`='{$articleId}'";
				$res = mysqli_query($link,$sql);
				if($res && mysqli_num_rows($res)>0){
					$data = mysqli_fetch_assoc($res);
				}
				$newData = $data['tag'].$tagId.",";

				//执行，判断并跳转
				$sql = "UPDATE `article` set `tag`='{$newData}' where `id`='{$articleId}';";
				$res = mysqli_query($link,$sql);
				echo json_encode(array("id"=>$tagId, "text"=>$text));

				break;
			} else {
				echo "failed";
				
				break;
			}
	}

	function loginCheck() 
	{
		if(empty($_SESSION['user'])){
			echo "<script>alert('请登录');window.location.href='../admin/login.php';</script>";
			exit;
		}
		session_regenerate_id();
	}
