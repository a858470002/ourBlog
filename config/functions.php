<?php
	session_start();
	header('content-type:text/html;charset=utf-8');
	include("./database.php");

	//获取地址中的action
	if (isset($_GET['action'])) {
		$action = $_GET['action'];
	} else {
		echo "<script>alert('Illegal Operation !');window.location.href='../admin/index.php';</script>";
		exit;
	}
	
	switch($action){
		//登录判断
		case 'login':
			$email = filter_var(($_POST['email']),FILTER_VALIDATE_EMAIL);
			$password = md5($_POST['password']);

			//Email null check
			if (!isset($_POST['email'])) {
				echo "<script>alert('Please fill the blank!');window.location.href='../admin/index.php';</script>";
				exit;
			}

			//Email illegal check
			if (!$email) {
			    echo "<script>alert('ivalid rules!');window.location.href='../admin/index.php';</script>";
			    exit;
			} else {
				$email = mysqli_real_escape_string($link,$email);
			}


			//拼接select语句执行得到结果，并跳转
			$sql = "SELECT * from `user` where `email`='{$email}' and `password`='{$password}'";
			$res = mysqli_query($link,$sql);
			if ($res && mysqli_num_rows($res)>0) {
				$user = mysqli_fetch_assoc($res);
				$_SESSION['user']=$user['id'];	//存session
				session_regenerate_id();
				echo "<script>alert('验证成功');window.location.href='../admin/index.php';</script>";
			} else {
				echo "<script>alert('验证失败:".var_dump($user)."');window.location.href='../admin/login.php';</script>";
			}
			
			break;

		//添加文章
		case 'add':
			loginCheck();

 			$title = mysqli_real_escape_string($link,$_POST['title']);
			$formaltext = mysqli_real_escape_string($link,$_POST['formaltext']);	//拼接sql专用的字符串处理
			$column = filter_var(($_POST['column']),FILTER_VALIDATE_INT,array('options' => array('min_range' => 1)));
 			$user_id = filter_var($_POST['user_id'],FILTER_VALIDATE_INT,array('options' => array('min_range' => 1)));

 			//Null check
 			if (!isset($_POST['title']) || !isset($_POST['formaltext'])) 
 			{
 				echo "<script>alert('Please fill the blank!');window.location.href='../admin/add.php';</script>";
				exit;
 			}

 			//Length check
 			textCheck($_POST['title'],'varchar',64,'../admin/add.php');
 			textCheck($_POST['formaltext'],'text',65535,'../admin/add.php');

 			//Illegal check
			if (!$user_id || !$column) {
			    echo "<script>alert('Ivalid rules!');window.location.href='../admin/index.php';</script>";
			    exit;
			}

			//拼接insert语句,执行，得到结果
			$sql = "INSERT into `article`(title,formaltext,`column`,`user_id`) VALUES ('{$title}','{$formaltext}',{$column},{$user_id});";
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

			$title = mysqli_real_escape_string($link,$_POST['title']);
			$formaltext = mysqli_real_escape_string($link,$_POST['formaltext']);	//拼接sql专用的字符串处理
			$column = filter_var(($_GET['column']),FILTER_VALIDATE_INT,array('options' => array('min_range' => 1)));
			$id = filter_var(($_GET['id']),FILTER_VALIDATE_INT,array('options' => array('min_range' => 1)));

			//Null check
			if (!isset($_POST['title']) || !isset($_POST['formaltext'])) 
 			{
 				echo "<script>alert('Please fill the blank!');window.location.href='../admin/add.php';</script>";
				exit;
 			}

 			//Text length check
 			textCheck($_POST['title'],'varchar',64,'../admin/edit.php');
 			textCheck($_POST['formaltext'],'text',65535,'../admin/edit.php');

			//拼接update语句,执行，判断并跳转
			$sql = "UPDATE `article` set `title`='{$title}',`formaltext`='{$formaltext}',`column`={$column} where `id`={$id};";
			$res = mysqli_query($link,$sql);
			if ($res) {
				echo "<script>alert('修改成功');window.location.href='../admin/index.php';</script>";
			} else {
				echo "<script>alert('修改失败');window.location.href='../admin/edit.php?id={$id}';</script>";
			}

			break;

		//删除文章
		case 'delete':
			loginCheck();
			$id = filter_var(($_GET['id']),FILTER_VALIDATE_INT,array('options' => array('min_range' => 1)));
			if (!$id) {
			    throw new InvalidArgumentException('invalid id');
			}
			
			//拼接delete语句并跳转
			$sql = "DELETE from `article` where `id`={$id}";
			$res = mysqli_query($link,$sql);
			if ($res) {
				echo "<script>alert('删除成功');window.location.href='../admin/index.php';</script>";
			} else {
				echo "<script>alert('删除失败');window.location.href='../admin/index.php';</script>";
			}

			break;

		//添加标签
		case 'tag':
			loginCheck();
			$text = mysqli_real_escape_string($link,$_POST['text']);
			$id = filter_var(($_POST['id']),FILTER_VALIDATE_INT,array('options' => array('min_range' => 1)));
			
			//执行，判断并跳转
			$sql = "UPDATE `article` set `tag`='{$text}' where `id`={$id};";
			$res = mysqli_query($link,$sql);
			echo $res?"success":"failed";

			break;

		//添加标签
		case 'addTag':
			loginCheck();
			$articleId = filter_var(($_POST['articleId']),FILTER_VALIDATE_INT,array('options' => array('min_range' => 1)));
			$tagId = filter_var(($_POST['id']),FILTER_VALIDATE_INT,array('options' => array('min_range' => 1)));

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
			$articleId = filter_var(($_POST['articleId']),FILTER_VALIDATE_INT,array('options' => array('min_range' => 1)));
			$tagId =filter_var(($_POST['id']),FILTER_VALIDATE_INT,array('options' => array('min_range' => 1)));

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
			$articleId = filter_var(($_POST['id']),FILTER_VALIDATE_INT,array('options' => array('min_range' => 1)));

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
		if(!isset($_SESSION['user'])){
			echo "<script>alert('请登录');window.location.href='../admin/login.php';</script>";
			exit;
		}
	}

	function textCheck($string,$type,$length=65535,$url)
	{
		switch ($type) {
		 	case 'text':
		 		$string = trim($string);
		 		$num = mb_strlen($string,'UTF-8');
		 		if ($num == 0 || $num > 65535) {
		 			echo "<script>alert('Ivalid column rules!');window.location.href='".$url."';</script>";
		 			exit;
		 		}
		 		break;
		 	
		 	default:
		 		$string = trim($string);
				$num = mb_strlen($string,'UTF-8');
				if ($num == 0 || $num > $length) {
					//error reporting
					echo "<script>alert('Ivalid column rules!');window.location.href='".$url."';</script>";
					exit;
				}
		 		break;
		 }
	}