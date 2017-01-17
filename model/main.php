<?php

function login ($data,$link)
{
    //Param check
    if (!isset($data["email"]) || !isset($data["password"])) {
        throw new InvalidArgumentException("Invalid param");
    }
    paramCheck($data["email"]);
    paramCheck($data["password"]);

    $email = filter_var(($data["email"]),FILTER_VALIDATE_EMAIL);
    $password = md5($data["password"]);

    //Email valid check
    if (!$email) {
        throw new InvalidArgumentException("Illegal rules");
    }
    $email = mysqli_real_escape_string($link,$email);

    //User check
    $sql = "SELECT * from user where `email`='$email' and password='$password' ";
    $res = mysqli_query($link,$sql);
    if (!$res || mysqli_affected_rows($link)==0) {
        throw new InvalidArgumentException("Incorrect Email or Password!");
    }
    $user = mysqli_fetch_assoc($res);

    return $user['id'];
}

function addArticle ($data,$link,$user_id) 
{
    $requiredKeys = array('column', 'title', 'formaltext', 'tag');
    foreach ($requiredKeys as $key) {
        if (!isset($data[$key])) {
            throw new InvalidArgumentException("missing requied key");
        }
    }
    paramCheck($data["title"],64);
    paramCheck($data["formaltext"],65535);
    paramCheck($data["column"]);

    if (!isset($data["tag"])) {
        throw new InvalidArgumentException("Invalid param:Tag");
    }

    $column = filter_var($data["column"],FILTER_VALIDATE_INT,array("options" => array("min_range" => 1)));

    //Valid check
    if (!$column) {
        throw new InvalidArgumentException("Invalid column");
    }
    $title = mysqli_real_escape_string($link,$data["title"]);
    $ftext = mysqli_real_escape_string($link,$data["formaltext"]);

    if (!empty($data["tag"])) {
        $tags = explode(",", $data["tag"]);
        foreach ($tags as $value) {
            paramCheck($value,32);
        }
    } else {
        $tags = array();
    }

    //Data check finish, mysql start
    mysqli_autocommit($link,FALSE);

    // Insert1: new article
    $sql = "INSERT into article(title,formaltext,`column`,user_id) VALUES ('$title','$ftext','$column',$user_id);";
    $res = mysqli_query($link,$sql);
    mysqliResultCheck($res,$link,"添加失败:error01");

    $article_id = mysqli_insert_id($link);

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
        mysqliResultCheck($res,$link,"添加失败:error02");
        
        $sameTags = mysqliArrayResult($res);
        $arr_id   = array();
        $arr_name = array();

        //Find the same tags id & name
        foreach ($sameTags as $value) {
            $arr_id[] = $value["id"];
            $arr_name[] = $value["name"];
        }

        //Tags:which is not in table
        $arr_diff = array_diff($tags, $arr_name);
    } else {
        $arr_diff = array();
    }

    //If appear new tags
    if (!empty($arr_diff)) {
        // Insert2: new tag (match, and del the same tag)
        $user_id = intval($user_id);
        $sql = "INSERT into tag(name,user_id) VALUES ";
        foreach ($arr_diff as $value) {
            $value = mysqli_real_escape_string($link,$value);
            $sql .= "('$value',$user_id),";
        }
        $sql = trim($sql,",");
        $res = mysqli_query($link,$sql);
        // echo $sql."<br>";
        mysqliResultCheck($res,$link,"添加失败:error03");

        //Select diff tags id 
        $param = "";
        foreach ($arr_diff as $value) {
            $value = mysqli_real_escape_string($link,$value);
            $param .= "'$value',";
        }
        $param = trim($param,",");
        $sql = "SELECT * from tag WHERE name in ($param)";
        $res = mysqli_query($link,$sql);
        // echo $sql ;
        mysqliResultCheck($res,$link,"添加失败:error04");

        $diffTags = mysqliArrayResult($res);

        //Find the same tags id & name
        foreach ($diffTags as $value) {
            $arr_id[] = $value["id"];
            $arr_name[] = $value["name"];
        }
        
        // Insert3: new tag & article (table tag_mid)
        $sql_mid = "INSERT into tag_mid(tag_id,article_id) VALUES ";
        foreach ($arr_id as $value) {
            $value = mysqli_real_escape_string($link,$value);
            $sql_mid .= "($value,'$article_id'),";
        }
        $sql_mid = trim($sql_mid,",");
        $res = mysqli_query($link,$sql_mid);

        mysqliResultCheck($res,$link,"添加失败:error05");
    }

    
    if (!mysqli_commit($link)) {
        throw new InvalidArgumentException("添加失败:error06");
    }

    return true;
}

function editArticle ($data,$link,$user_id,$article_id) 
{
    $requiredKeys = array('column', 'title', 'formaltext', 'tag');
    foreach ($requiredKeys as $key) {
        if (!isset($data[$key])) {
            throw new InvalidArgumentException("missing requied key $key");
            
        }
    }
    paramCheck($data["title"],64);
    paramCheck($data["formaltext"],65535);
    paramCheck($data["column"]);

    $column = filter_var($data["column"],FILTER_VALIDATE_INT,array("options" => array("min_range" => 1)));
    $article_id = filter_var($article_id,FILTER_VALIDATE_INT,array("options" => array("min_range" => 1)));

    if (!empty($data["tag"])) {
        $tags_get = explode(",", $data["tag"]);
        foreach ($tags_get as $value) {
            paramCheck($value,32);
        }
    } else {
        $tags_get = array();
    }

    $title = mysqli_real_escape_string($link,$data["title"]);
    $ftext = mysqli_real_escape_string($link,$data["formaltext"]);

    // User check
    $sql = "SELECT * from article where id = $article_id and user_id = $user_id";
    $res = mysqli_query($link,$sql);
    if ($num = mysqli_num_rows($res) == 0) {
        throw new InvalidArgumentException("Illegal user operation!$num");
    }
    
    // All checks finished, mysql start.
    mysqli_autocommit($link,FALSE);

    // 1:Select * from tag_mid 
    $sql = "SELECT * from tag_mid where article_id = $article_id ";
    $res = mysqli_query($link,$sql);
    mysqliResultCheck($res,$link,"修改失败:error01");

    $midTags = mysqliArrayResult($res);
    $midTags_id = array();

    foreach ($midTags as $value) {
        $midTags_id[] = $value["tag_id"];
    }

    // 2:Update1: article
    $sql = "UPDATE article set title='$title',formaltext='$ftext',`column`='$column' where id=$article_id;";
    $res = mysqli_query($link,$sql);
    mysqliResultCheck($res,$link,"修改失败:error02");

    // 3:Delete or get old tags
    if (empty($tags_get)) {
        // 3.1:If empty tags, delete all in tag_mid, finish.
        $sql = "DELETE from tag_mid where article_id = $article_id";
        $res = mysqli_query($link,$sql);
        mysqliResultCheck($res,$link,"修改失败:error031");
    } else {
        // 3.2:If have tags, select in all tags ,match the same 
        $param = "";
        foreach ($tags_get as $value) {
            $tname = mysqli_real_escape_string($link,$value);
            $param .= "'$tname',";
        }
        $param = trim($param,",");
        $sql = "SELECT * from tag WHERE name in ($param)";
        // echo "\n".$sql."\n";
        $res = mysqli_query($link,$sql);
        mysqliResultCheck($res,$link,"修改失败:error032");

        $sameTags = mysqliArrayResult($res);
        $arr_id = array();
        $arr_name = array();
        //Get the old tags id & name
        foreach ($sameTags as $value) {
            $arr_id[] = $value["id"];
            $arr_name[] = $value["name"];
        }
        // var_dump($sameTags);die;

        if (!empty($midTags_id)) {
            $param = "";
            foreach ($midTags_id as $value) {
                $param .= "'$value',";
            }
            $param = trim($param,",");
            $sql = "SELECT * from tag WHERE id in ($param)";
            $res = mysqli_query($link,$sql);
            mysqliResultCheck($res,$link,"修改失败:error033");

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
                $tname = mysqli_real_escape_string($link,$value);
                $sql .= "('$tname'),";
            }
            $sql = trim($sql,",");
            $res = mysqli_query($link,$sql);
            mysqliResultCheck($res,$link,"修改失败:error041");
        }

        //Select diff tags id 
        $param = "";
        foreach ($arr_insert as $value) {
            $tname = mysqli_real_escape_string($link,$value);
            $param .= "'$tname',";
        }
        $param = trim($param,",");
        $sql = "SELECT * from tag WHERE name in ($param)";
        $res = mysqli_query($link,$sql);
        mysqliResultCheck($res,$link,"修改失败:error042");

        $diffTags = mysqliArrayResult($res);
        

        //Find the same tags id & name
        foreach ($diffTags as $value) {
            $arr_id_insert[] = $value["id"];
            $arr_name_insert[] = $value["name"];
        }
        
        // Insert3: new tag & article (table tag_mid)
        $sql = "INSERT into tag_mid(tag_id,article_id) VALUES ";
        foreach ($arr_id_insert as $value) {
            $sql .= "($value,$article_id),";
        }
        $sql = trim($sql,",");
        $sql = mysqli_real_escape_string($link,$sql);
        $res = mysqli_query($link,$sql);
        mysqliResultCheck($res,$link,"修改失败:error043");
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
        mysqliResultCheck($res,$link,"修改失败:error051");

        $diffTags = mysqliArrayResult($res);

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
        mysqliResultCheck($res,$link,"修改失败:error052");
    }

    mysqliResultCheck(mysqli_commit($link),$link,"修改失败:error05");

    return true;
}

function deleteArticle ($link,$user_id,$article_id) 
{
    paramCheck($article_id);

    //Valid id check
    $id = filter_var(($article_id),FILTER_VALIDATE_INT,array("options" => array("min_range" => 1)));
    $user_id = filter_var($user_id,FILTER_VALIDATE_INT,array("options" => array("min_range" => 1)));
    if (!$id || !$user_id) {
        throw new InvalidArgumentException("Ivalid rules");
    }
    
    //Delete article
    $sql = "DELETE from article where id={$id} and user_id=$user_id";
    $res = mysqli_query($link,$sql);
    if ($res && mysqli_affected_rows($link)==1) {
        return true;
    } else {
        throw new InvalidArgumentException("The article don\'t exist or incorrect user");
    }
}

function paramCheck ($param,$length = null) 
{
    //empty check
    if (empty($param)) {
        throw new InvalidArgumentException("Please fill the blank");
    }

    //length check
    if ($length != null) {
        $num = mb_strlen(trim($param),"UTF-8");
        if ($num == 0 || $num > $length) {
            throw new InvalidArgumentException("Illegal param length");
        }
    }
}

function mysqliResultCheck ($res,$link,$string)
{
    if (!$res) {
        mysqli_rollback($link);
        throw new InvalidArgumentException("$string");
    }
}

function mysqliArrayResult ($res) 
{
    $array = array();
    while($row = mysqli_fetch_array($res)){
        $array[] = $row;
    }

    return $array;
}

function databaseConnect ($dbname)
{
    //数据库基本信息
    $dbhost = "127.0.0.1";
    $dbuser = "root";
    $dbpwd  = "123456";
    // $dbname = "php_manual";
    
    //MySQLi链接
    $mysqli = new mysqli($dbhost,$dbuser,$dbpwd,$dbname);
    if(mysqli_connect_errno()){
        return "连接失败".mysqli_connect_error();
        exit;
    } else {
        return $mysqli;
    }
}