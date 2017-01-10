<?php
	session_start();
	header('content-type:text/html;charset=utf-8');
	include("./database.php");

	//获取地址中的action
	if (isset($_GET['action'])) {
		$action = $_GET['action'];
	} else {
		header('Location: /admin/index.php');
		exit;
	}
	
	switch($action){
		//登录判断
		case 'login':
			//Null check
 			if (!isset($_POST['email']) || !isset($_POST['password'])) {
 				echo "<script>alert('Please fill the blank!');window.location.href='../admin/login.php';</script>";
				exit;
 			}

			$email = filter_var(($_POST['email']),FILTER_VALIDATE_EMAIL);
			$password = md5($_POST['password']);

			//Email valid check
			if (!$email) {
			    echo "<script>alert('ivalid rules!');window.location.href='../admin/login.php';</script>";
			    exit;
			} else {
				$email = mysqli_real_escape_string($link,$email);
			}


			//User check
			$sql = "SELECT * from user where `email`='{$email}' and password='{$password}'";
			$res = mysqli_query($link,$sql);
			if ($res && mysqli_num_rows($res)>0) {
				$user = mysqli_fetch_assoc($res);
				$_SESSION['user']=$user['id'];
				session_regenerate_id();
				header('Location: /admin/index.php');
			} else {
				echo "<script>alert('验证失败');window.location.href='../admin/login.php';</script>";
			}
			
			break;

		//添加文章
		case 'add':
			$user_id = loginCheck();

            //Null check
            if (!isset($_POST['title']) || !isset($_POST['formaltext']) || !isset($_POST['column']) || !isset($_POST['tag'])) {
                echo "<script>alert('Please fill the blank!');window.location.href='../admin/add.php';</script>";
                exit;
            }

            //Length check
            textCheck($_POST['title'],'varchar',64,'../admin/add.php');
            textCheck($_POST['formaltext'],'text',65535,'../admin/add.php');
            $tags_old = explode(',', $_POST['tag']);
            foreach ($tags_old as $value) {
                textCheck($value,'varchar',32,'../admin/add.php');
                $tags[] = mysqli_real_escape_string($link,$value);
            }
            $title = mysqli_real_escape_string($link,$_POST['title']);
            $ftext = mysqli_real_escape_string($link,$_POST['formaltext']);
            $column =  filter_var($_POST['column'],FILTER_VALIDATE_INT,array('options' => array('min_range' => 1)));

            //Valid check
            if (!$user_id || !$column) {
                echo "<script>alert('Ivalid rules!');window.location.href='../admin/add.php';</script>";
                exit;
            }

            mysqli_autocommit($link,FALSE);
            // Insert1: new article
            $sql = "INSERT into article(title,formaltext,`column`,user_id) VALUES ('{$title}','{$ftext}',{$column},'{$user_id}');";
            $res1 = mysqli_query($link,$sql);
            $articleId = mysqli_insert_id($link);

            // Select all tags ,match the same 
            $param = "";
            foreach ($tags as $value) {
                $param .= "'{$value}',";
            }
            $param = trim($param,",");
            $sql_sel = "SELECT * from tag WHERE name in ({$param})";
            $res2 = mysqli_query($link,$sql_sel);
            $sameTags = array();
            while($row = mysqli_fetch_array($res2)){
                $sameTags[] = $row;
            }

            //Find the same tags id & name
            foreach ($sameTags as $value) {
                $arr_id[] = $value['id'];
                $arr_name[] = $value['name'];
            }
            $arr_diff = array_diff($tags, $arr_name);

            if (!empty($arr_diff)) {
                // Insert2: new tag (match, and del the same tag)
                $sql_tag = "INSERT into tag(name) VALUES ";
                foreach ($arr_diff as $value) {
                    $sql_tag .= "('{$value}'),";
                }
                $sql_tag = trim($sql_tag,",");
                $res3 = mysqli_query($link,$sql_tag);
                if($res3 && mysqli_affected_rows($link)>0){
                    //Select diff tags id 
                    $param = "";
                    foreach ($arr_diff as $value) {
                        $param .= "'{$value}',";
                    }
                    $param = trim($param,",");
                    $sql_sel = "SELECT * from tag WHERE name in ({$param})";
                    $res4 = mysqli_query($link,$sql_sel);
                    if($res4 && mysqli_affected_rows($link)>0){
                        $diffTags = array();
                        while($row = mysqli_fetch_array($res4,MYSQLI_BOTH)){
                            $diffTags[] = $row;
                        }
                    } else {
                        mysqli_rollback($link);
                        echo "<script>alert('添加失败');window.location.href='../admin/add.php';</script>";
                    }

                    //Find the same tags id & name
                    foreach ($diffTags as $value) {
                        $arr_id[] = $value['id'];
                        $arr_name[] = $value['name'];
                    }
                } else {
                    mysqli_rollback($link);
                    echo "<script>alert('添加失败');window.location.href='../admin/add.php';</script>";
                }
            }

			// Insert3: new tag & article (table tag_mid)
            $sql_mid = "INSERT into tag_mid(tag_id,article_id) VALUES ";
            foreach ($arr_id as $value) {
                $sql_mid .= "({$value},{$articleId}),";
            }
            $sql_mid = trim($sql_mid,",");
            $res5 = mysqli_query($link,$sql_mid);

            if (!$res1 || !$res2 || !$res5 ) {
                mysqli_rollback($link);
                echo "<script>alert('添加失败');window.location.href='../admin/add.php';</script>";
            } else {
                mysqli_commit($link);
                echo "<script>alert('添加成功');window.location.href='../admin/index.php';</script>";
            }

			break;

		//修改文章
		case 'edit':
			loginCheck();

			//Null check
			if (!isset($_POST['title']) || !isset($_POST['formaltext'])) 
 			{
 				echo "<script>alert('Please fill the blank!');window.location.href='../admin/add.php';</script>";
				exit;
 			}

 			//Text length check
 			textCheck($_POST['title'],'varchar',64,'../admin/edit.php');
 			textCheck($_POST['formaltext'],'text',65535,'../admin/edit.php');

			$title = mysqli_real_escape_string($link,$_POST['title']);
			$ftext = mysqli_real_escape_string($link,$_POST['formaltext']);
			$column = filter_var(($_POST['column']),FILTER_VALIDATE_INT,array('options' => array('min_range' => 1)));
			$article_id =  filter_var(($_GET['id']),FILTER_VALIDATE_INT,array('options' => array('min_range' => 1)));

			//Illegal check
			if (!$article_id || !$column) {
			    echo "<script>alert('Ivalid rules!');window.location.href='../admin/index.php';</script>";
			    exit;
			}

			//Update article
			$sql = "UPDATE article set title='{$title}',formaltext='{$ftext}',`column`={$column} where id={$article_id};";
			$res = mysqli_query($link,$sql);
			if ($res) {
				echo "<script>alert('修改成功');window.location.href='../admin/index.php';</script>";
			} else {
				echo "<script>alert('修改失败');window.location.href='../admin/edit.php?id={$article_id}';</script>";
			}

			break;

		//删除文章
		case 'delete':
			loginCheck();

            //Null id check
            if (!isset($_GET['id'])) {
                header('Location: /admin/index.php');
                exit;
            }

            //Valid id check
			$id = filter_var(($_GET['id']),FILTER_VALIDATE_INT,array('options' => array('min_range' => 1)));
            $user_id = filter_var($_SESSION['user'],FILTER_VALIDATE_INT,array('options' => array('min_range' => 1)));
			if (!$id || $user_id) {
			    echo "<script>alert('Ivalid rules!');window.location.href='../admin/index.php';</script>";
                exit;
			}
			
			//Delete article
			$sql = "DELETE from article where id={$id} and user_id={$user_id}";
			$res = mysqli_query($link,$sql);
			if ($res && mysqli_affected_rows($link)==1) {
				echo "<script>alert('删除成功');window.location.href='../admin/index.php';</script>";
			} else {
				echo "<script>alert('删除失败:The article don\'t exist or incorrect user');window.location.href='../admin/index.php';</script>";
			}

			break;

		//添加标签
		case 'addTag':
			loginCheck();

            if (!isset($_POST['articleId']) || !isset($_POST['id'])) {
                header('Location: /admin/index.php');
                exit;
            }

			$articleId = filter_var(($_POST['articleId']),FILTER_VALIDATE_INT,array('options' => array('min_range' => 1)));
			$tagId = filter_var(($_POST['id']),FILTER_VALIDATE_INT,array('options' => array('min_range' => 1)));
            
            //Illegal check
            if (!$articleId || !$tagId) {
                echo "<script>alert('Ivalid rules!');window.location.href='../admin/index.php';</script>";
                exit;
            }
			
            //Searching for article by id&user_id
			$sql = "SELECT * from article where id='{$articleId}' and user_id={$user_id}";
			$res = mysqli_query($link,$sql);
			if($res && mysqli_num_rows($res)>0){
				$data = mysqli_fetch_assoc($res);
                $newData = $data['tag'].$tagId.",";
            }

			//Update article
            $sql = "UPDATE article set tag='{$newData}' where id={$articleId};";
			$res = mysqli_query($link,$sql);
			echo $res?"success":"failed";

			break;
		
		//删除标签
		case 'reduceTag':
			loginCheck();

            if (!isset($_POST['articleId']) || !isset($_POST['id'])) {
                header('Location: /admin/index.php');
                exit;
            }

			$articleId = filter_var(($_POST['articleId']),FILTER_VALIDATE_INT,array('options' => array('min_range' => 1)));
			$tagId = filter_var(($_POST['id']),FILTER_VALIDATE_INT,array('options' => array('min_range' => 1)));

			//Illegal check
            if (!$articleId || !$tagId) {
                echo "<script>alert('Ivalid rules!');window.location.href='../admin/index.php';</script>";
                exit;
            }

            //查找该文章的所有标签
			$sql = "SELECT * from article where id='{$articleId}' and user_id={$user_id}";
			$res = mysqli_query($link,$sql);
			if ($res && mysqli_num_rows($res)>0) {
				$data = mysqli_fetch_assoc($res);
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
    			$sql = "UPDATE article set tag='{$str}' where id={$articleId} and user_id={$user_id};";
    			$res = mysqli_query($link,$sql);
    			echo $res?"success":"failed";
            } else {
                echo "failed";
            }

			break;

		//添加新标签
		case 'newTag':
			loginCheck();

            if (!isset($_POST['text']) || !isset($_POST['id'])) {
                header('Location: /admin/index.php');
                exit;
            }

			$text = mysqli_real_escape_string($link,$_POST['text']);
			$articleId = filter_var(($_POST['id']),FILTER_VALIDATE_INT,array('options' => array('min_range' => 1)));

			
            //Illegal check
            if (!$articleId) {
                echo "<script>alert('Ivalid rules!');window.location.href='../admin/index.php';</script>";
                exit;
            }

            //Add new tag into table tag
			$sql = "INSERT into tag (name) VALUES ('{$text}');";
			$res = mysqli_query($link,$sql);
			$tagId = mysqli_insert_id($link);
			if ($res && mysqli_affected_rows($link)>0) {
				$sql = "SELECT * from article where id='{$articleId}' and user_id={$user_id}";
				$res = mysqli_query($link,$sql);
				if($res && mysqli_num_rows($res)>0){
					$data = mysqli_fetch_assoc($res);
				}
				$newData = $data['tag'].$tagId.",";

				//Success, and update the data
				$sql = "UPDATE article set tag='{$newData}' where id='{$articleId}' and user_id={$user_id};";
				$res = mysqli_query($link,$sql);
                if($res && mysqli_num_rows($res)>0){
				    echo json_encode(array("id"=>$tagId, "text"=>$text));
                }

				break;
			} else {
				echo false;
				
				break;
			}
	}

	function loginCheck() 
	{
		if(!isset($_SESSION['user'])){
			echo "<script>alert('请登录');window.location.href='../admin/login.php';</script>";
			exit;
		} else {
			$user_id = $_SESSION['user'];
            return $user_id;
		}
	}

	function textCheck($string,$type,$length=65535,$url)
	{
		switch ($type) {
		 	case 'text':
		 		$string = trim($string);
		 		$num = mb_strlen($string,'UTF-8');
		 		if ($num == 0 || $num > 65535) {
		 			echo "<script>alert('Invalid param length!');window.location.href='".$url."';</script>";
		 			exit;
		 		}
		 		break;
		 	
		 	default:
		 		$string = trim($string);
				$num = mb_strlen($string,'UTF-8');
				if ($num == 0 || $num > $length) {
					//error reporting
					echo "<script>alert('Invalid param length!');window.location.href='".$url."';</script>";
					exit;
				}
		 		break;
		 }
	}