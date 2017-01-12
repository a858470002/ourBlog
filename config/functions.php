<?php
	session_start();
	header("content-type:text/html;charset=utf-8");
	include("./database.php");

	//获取地址中的action
	if (isset($_GET["action"])) {
		$action = $_GET["action"];
	} else {
		header("Location: /admin/index.php");
		exit;
	}
	
	switch($action){
		//登录判断
		case "login":
			//Null check
 			if (!isset($_POST["email"]) || !isset($_POST["password"])) {
 				echo "<script>alert('Please fill the blank!');window.location.href='../admin/login.php';</script>";
				exit;
 			}

			$email = filter_var(($_POST["email"]),FILTER_VALIDATE_EMAIL);
			$password = md5($_POST["password"]);

			//Email valid check
			if (!$email) {
			    echo "<script>alert('ivalid rules!');window.location.href='../admin/login.php';</script>";
			    exit;
			} else {
				$email = mysqli_real_escape_string($link,$email);
			}


			//User check
			$sql = "SELECT * from user where `email`='$email' and password='$password'";
			$res = mysqli_query($link,$sql);
			if ($res && mysqli_num_rows($res)>0) {
				$user = mysqli_fetch_assoc($res);
				$_SESSION["user"]=$user["id"];
				session_regenerate_id();
				header("Location: /admin/index.php");
			} else {
				echo "<script>alert('验证失败');window.location.href='../admin/login.php';</script>";
			}
			
			break;

		//添加文章
		case "add":
			$user_id = loginCheck();

            //Null check
            if (!isset($_POST["title"]) || !isset($_POST["formaltext"]) || !isset($_POST["column"]) || !isset($_POST["tag"])) {
                echo "<script>alert('Please fill the blank!');window.location.href='../admin/add.php';</script>";
                exit;
            }

            //Length check
            textCheck($_POST["title"],"varchar",64,"../admin/add.php");
            textCheck($_POST["formaltext"],"text",65535,"../admin/add.php");
            if (!empty($_POST["tag"])) {
                $tags = explode(",", $_POST["tag"]);
                foreach ($tags as $value) {
                    textCheck($value,"varchar",32,"../admin/add.php");
                }
            } else {
                $tags = array();
            }
            $title = mysqli_real_escape_string($link,$_POST["title"]);
            $ftext = mysqli_real_escape_string($link,$_POST["formaltext"]);
            $column = filter_var($_POST["column"],FILTER_VALIDATE_INT,array("options" => array("min_range" => 1)));

            //Valid check
            if (!$column) {
                echo "<script>alert('Ivalid rules!');window.location.href='../admin/add.php';</script>";
                exit;
            }

            //Data check finish, mysql start
            mysqli_autocommit($link,FALSE);

            // Insert1: new article
            $sql = "INSERT into article(title,formaltext,`column`,user_id) VALUES ('$title','$ftext','$column',$user_id);";
            $res = mysqli_query($link,$sql);
            resCheck($res,$link,"添加失败:error01","../admin/add.php");

            $articleId = mysqli_insert_id($link);
            $arr_name = array();
            $arr_id = array();

            // If have tags
            if (!empty($tags)) {
                // Select all tags ,match the same 
                $param = "";
                foreach ($tags as $value) {
                    $value = mysqli_real_escape_string($link,$value);
                    $param .= "$value,";
                }
                $param = trim($param,",");
                $sql_sel = "SELECT * from tag WHERE name in ('$param')";
                $res = mysqli_query($link,$sql_sel);
                resCheck($res,$link,"添加失败:error02","../admin/add.php");
                $sameTags = array();
                while($row = mysqli_fetch_array($res)){
                    $sameTags[] = $row;
                }

                //Find the same tags id & name
                foreach ($sameTags as $value) {
                    $arr_id[] = $value["id"];
                    $arr_name[] = $value["name"];
                }
                $arr_diff = array_diff($tags, $arr_name);
            } else {
                $arr_diff = array();
            }

            //If appear new tags
            if (!empty($arr_diff)) {
                // Insert2: new tag (match, and del the same tag)
                $sql_tag = "INSERT into tag(name) VALUES ";
                foreach ($arr_diff as $value) {
                    $value = mysqli_real_escape_string($link,$value);
                    $sql_tag .= "('$value'),";
                }
                $sql_tag = trim($sql_tag,",");
                $res = mysqli_query($link,$sql_tag);
                resCheck($res,$link,"添加失败:error03","../admin/add.php");

                //Select diff tags id 
                $param = "";
                foreach ($arr_diff as $value) {
                    $value = mysqli_real_escape_string($link,$value);
                    $param .= "'$value',";
                }
                $param = trim($param,",");
                $sql_sel = "SELECT * from tag WHERE name in ('$param')";
                $res = mysqli_query($link,$sql_sel);
                resCheck($res,$link,"添加失败:error04","../admin/add.php");

                $diffTags = array();
                while($row = mysqli_fetch_array($res,MYSQLI_BOTH)){
                    $diffTags[] = $row;
                }

                //Find the same tags id & name
                foreach ($diffTags as $value) {
                    $arr_id[] = $value["id"];
                    $arr_name[] = $value["name"];
                }
            }

			// Insert3: new tag & article (table tag_mid)
            $sql_mid = "INSERT into tag_mid(tag_id,article_id) VALUES ";
            foreach ($arr_id as $value) {
                $value = mysqli_real_escape_string($link,$value);
                $sql_mid .= "($value,'$articleId'),";
            }
            $sql_mid = trim($sql_mid,",");
            $res = mysqli_query($link,$sql_mid);

            resCheck($res,$link,"添加失败:error05","../admin/add.php");
            
            if (!mysqli_commit($link)) {
                echo "<script>alert('添加失败:error06');window.location.href='../admin/add.php';</script>";
                exit;
            } else {
                echo "<script>alert('添加成功');window.location.href='../admin/index.php';</script>";
            }

			break;

		//修改文章
		case "edit":
			$user_id = loginCheck();

			//Null check
			if (!isset($_POST["title"]) || !isset($_POST["formaltext"])) {
 				echo "<script>alert('Please fill the blank!');window.location.href='../admin/add.php';</script>";
				exit;
 			}

 			//Text length check
 			textCheck($_POST["title"],"varchar",64,"../admin/edit.php");
 			textCheck($_POST["formaltext"],"text",65535,"../admin/edit.php");

			$title = mysqli_real_escape_string($link,$_POST["title"]);
			$ftext = mysqli_real_escape_string($link,$_POST["formaltext"]);
			$column = filter_var($_POST["column"],FILTER_VALIDATE_INT,array("options" => array("min_range" => 1)));
			$article_id =  filter_var(($_GET["id"]),FILTER_VALIDATE_INT,array("options" => array("min_range" => 1)));
            if (!empty($_POST["tag"])) {
                $tags_get = explode(",", $_POST["tag"]);
                foreach ($tags_get as $value) {
                    textCheck($value,"varchar",32,"../admin/add.php");
                }
            } else {
                $tags_get = array();
            }

			//Illegal check
			if (!$article_id || !$column) {
			    echo "<script>alert('Ivalid rules!');window.location.href='../admin/index.php';</script>";
			    exit;
			}

            // User check
            $sql = "SELECT * from article where id = $article_id and user_id = $user_id";
            $res = mysqli_query($link,$sql);
            if (!$res && mysqli_affected_rows($link) == 0) {
                echo "<script>alert('Illegal user operation!');window.location.href='../admin/index.php';</script>";
                exit;
            }
            
            // All checks finished, mysql start.
            mysqli_autocommit($link,FALSE);

            // 1:Select * from tag_mid 
            $sql_midS = "SELECT * from tag_mid where article_id = $article_id ";
            $res = mysqli_query($link,$sql_midS);
            resCheck($res,$link,"修改失败:error01","../admin/edit.php?id=$article_id");
            $midTags = array();
            $midTags_id = array();
            while($row = mysqli_fetch_array($res,MYSQLI_BOTH)){
                $midTags[] = $row;
            }
            foreach ($midTags as $value) {
                $midTags_id[] = $value["tag_id"];
            }

            // 2:Update1: article
            $sql = "UPDATE article set title='$title',formaltext='$ftext',`column`='$column' where id=$article_id;";
            $res = mysqli_query($link,$sql);
            resCheck($res,$link,"修改失败:error02","../admin/edit.php?id=$article_id");

            // 3:Delete or get old tags
            if (empty($tags_get)) {
                // 3.1:If empty tags, delete all in tag_mid, finish.
                $sql = "DELETE from tag_mid where article_id = $article_id";
                $res = mysqli_query($link,$sql);
                resCheck($res,$link,"修改失败:error031","../admin/edit.php?id=$article_id");
            } else {
                // 3.2:If have tags, select in all tags ,match the same 
                $param = "";
                foreach ($tags_get as $value) {
                    $param .= "'{$value}',";
                }
                $param = trim($param,",");
                $sql = "SELECT * from tag WHERE name in ($param)";
                $res = mysqli_query($link,$sql);
                resCheck($res,$link,"修改失败:error032","../admin/edit.php?id=$article_id");
                $sameTags = array();
                while($row = mysqli_fetch_array($res)){
                    $sameTags[] = $row;
                }
                
                //Get the old tags id & name
                foreach ($sameTags as $value) {
                    $arr_id[] = $value["id"];
                    $arr_name[] = $value["name"];
                }

                if (!empty($midTags_id)) {
                    $param = "";
                    foreach ($midTags_id as $value) {
                        $param .= "'{$value}',";
                    }
                    $param = trim($param,",");
                    $sql = "SELECT * from tag WHERE id in ($param)";
                    $res = mysqli_query($link,$sql);
                    resCheck($res,$link,"修改失败:error033","../admin/edit.php?id=$article_id");
                    $oldTags = array();
                    while($row = mysqli_fetch_array($res)){
                        $oldTags[] = $row;
                    }
                    
                    //Get the old tags id & name
                    foreach ($oldTags as $value) {
                        $tags_original[] = $value["name"];
                    }
                } else {
                    $tags_original = array();
                }
                
                $arr_insert = array_diff($tags_get, $tags_original); 
                $arr_delete = array_diff($tags_original, $tags_get); 
                $arr_real_insert = array_diff($arr_insert, $arr_name); 
                $arr_real_delete = array_diff($arr_delete, $arr_name);
            }


            // If not really same, insert it, or delete it
            // 4:Insert
            if (!empty($arr_insert)) {
                if (!empty($arr_real_insert)) {
                    $sql = "INSERT into tag(name) VALUES ";
                    foreach ($arr_real_insert as $value) {
                        $sql .= "('$value'),";
                    }
                    $sql = trim($sql,",");
                    $res = mysqli_query($link,$sql);
                    resCheck($res,$link,"修改失败:error041","../admin/edit.php?id=$article_id");
                }

                //Select diff tags id 
                $param = "";
                foreach ($arr_insert as $value) {
                    $param .= "'$value',";
                }
                $param = trim($param,",");
                $sql = "SELECT * from tag WHERE name in ($param)";
                $res = mysqli_query($link,$sql);
                resCheck($res,$link,"修改失败:error042","../admin/edit.php?id=$article_id");
                $diffTags = array();
                while($row = mysqli_fetch_array($res,MYSQLI_BOTH)){
                    $diffTags[] = $row;
                }
                

                //Find the same tags id & name
                // var_dump($arr_id);
                foreach ($diffTags as $value) {
                    $arr_id_insert[] = $value["id"];
                    $arr_name_insert[] = $value["name"];
                }
                // var_dump($arr_id);die;
                
                // Insert3: new tag & article (table tag_mid)
                $sql = "INSERT into tag_mid(tag_id,article_id) VALUES ";
                foreach ($arr_id_insert as $value) {
                    $sql .= "($value,$article_id),";
                }
                $sql = trim($sql,",");
                $sql = mysqli_real_escape_string($link,$sql);
                $res = mysqli_query($link,$sql);
                resCheck($res,$link,"修改失败:error043","../admin/edit.php?id=$article_id");
            }

            // 5:Delete
            if (!empty($arr_delete)) {
                //Select diff tags id 
                $param = "";
                foreach ($arr_delete as $value) {
                    $param .= "'$value',";
                }
                $param = trim($param,",");
                $sql = "SELECT * from tag WHERE name in ($param)";
                $res = mysqli_query($link,$sql);
                resCheck($res,$link,"修改失败:error051","../admin/edit.php?id=$article_id");
                $diffTags = array();
                while($row = mysqli_fetch_array($res,MYSQLI_BOTH)){
                    $diffTags[] = $row;
                }

                //Put id & name into array
                foreach ($diffTags as $value) {
                    $arr_id_delete[] = $value["id"];
                    $arr_name_delete[] = $value["name"];
                }

                // Delete: new tag & article (table tag_mid)
                $param = "";
                foreach ($arr_id_delete as $value) {
                    $param .= "'$value',";
                }
                $param = trim($param,",");
                $sql = "DELETE from tag_mid where tag_id in ($param) and article_id = $article_id";
                $res = mysqli_query($link,$sql);
                resCheck($res,$link,"修改失败:error052","../admin/edit.php?id=$article_id");
            }

            resCheck(mysqli_commit($link),$link,"修改失败:error05","../admin/edit.php?id=$article_id");
            echo "<script>alert('修改成功');window.location.href='../admin/index.php';</script>";

			break;

		//删除文章
		case "delete":
			loginCheck();

            //Null id check
            if (!isset($_GET["id"])) {
                header("Location: /admin/index.php");
                exit;
            }

            //Valid id check
			$id = filter_var(($_GET["id"]),FILTER_VALIDATE_INT,array("options" => array("min_range" => 1)));
            $user_id = filter_var($_SESSION["user"],FILTER_VALIDATE_INT,array("options" => array("min_range" => 1)));
			if (!$id || !$user_id) {
			    echo "<script>alert('Ivalid rules!');window.location.href='../admin/index.php';</script>";
                exit;
			}
			
			//Delete article
			$sql = "DELETE from article where id={$id} and user_id=$user_id";
			$res = mysqli_query($link,$sql);
			if ($res && mysqli_affected_rows($link)==1) {
				echo "<script>alert('删除成功');window.location.href='../admin/index.php';</script>";
			} else {
				echo "<script>alert('删除失败:The article don\'t exist or incorrect user');window.location.href='../admin/index.php';</script>";
			}

			break;
	}

	function loginCheck() 
	{
		if(!isset($_SESSION["user"])){
			echo "<script>alert('请登录');window.location.href='../admin/login.php';</script>";
			exit;
		} 
        return $_SESSION["user"];
	}

	function textCheck($string,$type,$length=65535,$url)
	{
		switch ($type) {
		 	case "text":
		 		$string = trim($string);
		 		$num = mb_strlen($string,"UTF-8");
		 		if ($num == 0 || $num > 65535) {
		 			echo "<script>alert('Invalid param length!');window.location.href='".$url."';</script>";
		 			exit;
		 		}
		 		break;
		 	
		 	default:
		 		$string = trim($string);
				$num = mb_strlen($string,"UTF-8");
				if ($num == 0 || $num > $length) {
					//error reporting
					echo "<script>alert('Invalid param length!');window.location.href='".$url."';</script>";
					exit;
				}
		 		break;
		 }
	}

    function resCheck($res,$link,$string,$url){
        if (!$res) {
            mysqli_rollback($link);
            echo "<script>alert('$string');window.location.href='$url';</script>";
            exit;
        }
    }