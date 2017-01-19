<?php

function login ($data,$dbh)
{
    //Param check
    if (!isset($data['email']) || !isset($data['password'])) {
        throw new InvalidArgumentException('Invalid Email or Password!');
    }
    if (empty($data['email'])) {
        throw new InvalidArgumentException('Please fill the email');
    }
    if (empty($data['password'])) {
        throw new InvalidArgumentException('Please fill the password');
    }

    $email = filter_var(($data['email']),FILTER_VALIDATE_EMAIL);
    $password = md5($data['password']);

    if (!$email) {
        throw new InvalidArgumentException('Illegal Email address');
    }

    //PDO start
    $sth = $dbh->prepare('SELECT * from user where `email` = :email and password = :password');
    $sth->bindValue(':email',$email,PDO::PARAM_STR);
    $sth->bindValue(':password',$password,PDO::PARAM_STR);
    $sth->execute();

    if ($sth->rowCount()==0) {
        throw new InvalidArgumentException("Incorrect Email or Password!");
    }
    $user = $sth->fetch();

    return $user['id'];
}

function addArticle ($data,$dbh,$user_id) 
{
    $requiredKeys = array('column', 'title', 'formaltext', 'tag');
    foreach ($requiredKeys as $key) {
        if (!isset($data[$key])) {
            throw new InvalidArgumentException('missing requied key $key');
        }
    }
    $title = trim($data['title']);
    if (empty($title)) {
        throw new InvalidArgumentException('Please fill the title');
    }
    if (empty($data['formaltext'])) {
        throw new InvalidArgumentException('Please fill the formaltext');
    }

    $num = mb_strlen($title,"UTF-8");
    if ($num > 64) {
        throw new InvalidArgumentException('Title is over range(64)!');
    }

    $num = mb_strlen($data['formaltext'],"UTF-8");
    if ($num > 65534) {
        throw new InvalidArgumentException('Formaltext is over range(65535)!');
    }
    $formaltext = $data['formaltext'];

    $column = filter_var($data['column'],FILTER_VALIDATE_INT,array('options' => array('min_range' => 1)));
    if (!$column) {
        throw new InvalidArgumentException('Column is invalid');
    }
    

    if (!empty($data['tag'])) {
        $tags = explode(',', $data['tag']);
        foreach ($tags as $value) {
            $num = mb_strlen($value,"UTF-8");
            if ($num > 32) {
                throw new InvalidArgumentException('Some of tags is over range(64)!');
            }
        }
    } else {
        $tags = array();
    }


    $dbh->beginTransaction();

    // 1.insert new article
    $sth = $dbh->prepare("INSERT into article(title,formaltext,`column`,user_id) VALUES (:title,:ftext,:column,:user_id);");
    $sth->bindValue(':title',$title,PDO::PARAM_STR);
    $sth->bindValue(':ftext',$formaltext,PDO::PARAM_STR);
    $sth->bindValue(':column',$column,PDO::PARAM_INT);
    $sth->bindValue(':user_id',$user_id,PDO::PARAM_INT);
    $sth->execute();

    $article_id = $dbh->lastInsertId();

    // If have tags
    if (!empty($tags)) {
        // Select all tags ,match the same 
        $param = "";
        foreach ($tags as $value) {
            $value = $dbh->quote($value);
            $param .= "$value,";
        }
        $param = trim($param,",");

        // 2.select tags which already have
        $sth = $dbh->prepare("SELECT * from tag WHERE name in ($param)");
        $sth->execute();
      
        $sameTags = $sth->fetchAll();
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
        // 3.insert new tag (match, and del the same tag)
        $user_id = intval($user_id);
        $sqlValues  = '';

        foreach ($arr_diff as $v) {
            $sqlValues .= "(".$dbh->quote($v).",$user_id),";
        }
        $sqlValues = trim($sqlValues,",");

        $sth = $dbh->prepare("INSERT into tag(name,user_id) VALUES $sqlValues");
        $sth->execute();

        // 4.Select diff tags id 
        $param = '';
        foreach ($arr_diff as $value) {
            $param .= $dbh->quote($value) . ',';
        }
        $param = trim($param,",");

        $sth = $dbh->prepare("SELECT * from tag WHERE name in ($param)");
        $sth->execute();
        $diffTags = $sth->fetchAll();

        //Find the same tags id & name
        foreach ($diffTags as $value) {
            $arr_id[] = $value["id"];
            $arr_name[] = $value["name"];
        }
        
        // 5.insert new tag & article (table tag_mid)
        $sqlValues = '';
        foreach ($arr_id as $value) {
            $value = $dbh->quote($value);
            $sqlValues .= "($value,'$article_id'),";
        }
        $sqlValues = trim($sqlValues,",");
        $sth = $dbh->prepare("INSERT into tag_mid(tag_id,article_id) VALUES $sqlValues");
        $sth->execute();
    }

    $dbh->commit();

    return true;
}

function editArticle ($data,$dbh,$user_id,$article_id) 
{
    $requiredKeys = array('column', 'title', 'formaltext', 'tag');
    foreach ($requiredKeys as $key) {
        if (!isset($data[$key])) {
            throw new InvalidArgumentException("missing requied key $key");
            
        }
    }
    $title      = trim($data['title']);
    $formaltext = $data['formaltext'];

    // Empty
    if (empty($title)) {
        throw new InvalidArgumentException('Please fill the title');
    }
    if (empty($formaltext)) {
        throw new InvalidArgumentException('Please fill the formaltext');
    }

    // Length
    $num = mb_strlen($title,"UTF-8");
    if ($num > 64) {
        throw new InvalidArgumentException('Title is over range(64)!');
    }

    $num = mb_strlen($formaltext,"UTF-8");
    if ($num > 65534) {
        throw new InvalidArgumentException('Formaltext is over range(65535)!');
    }

    $column = filter_var($data['column'],FILTER_VALIDATE_INT,array('options' => array('min_range' => 1)));
    if (!$column) {
        throw new InvalidArgumentException('Column is invalid');
    }
    $article_id = filter_var($article_id,FILTER_VALIDATE_INT,array("options" => array("min_range" => 1)));

    if (!empty($data["tag"])) {
        $tags_get = explode(',', $data['tag']);
        foreach ($tags_get as $value) {
            $num = mb_strlen($value,"UTF-8");
            if ($num > 32) {
                throw new InvalidArgumentException('Some of tags is over range(64)!');
            }
        }
    } else {
        $tags_get = array();
    }

    // PDO Start

    // User check
    $sql = "SELECT count(*) from article where id = $article_id and user_id = $user_id";
    $result = $dbh->query($sql)->fetchColumn();

    if ($result == 0) {
        throw new InvalidArgumentException("Illegal user operation!");
    }

    // 1:Select * from tag_mid 
    $sth = $dbh->prepare("SELECT * from tag_mid where article_id = :article_id");
    $sth->bindValue(':article_id',$article_id,PDO::PARAM_INT);
    $sth->execute();

    $midTags = $sth->fetchAll();
    $midTags_id = array();

    foreach ($midTags as $value) {
        $midTags_id[] = $value["tag_id"];
    }

    $dbh->beginTransaction();
    // 2:Update1: article
    $sth = $dbh->prepare("UPDATE article set title = :title,formaltext = :ftext,`column` = :column where id=:article_id");
    $sth->bindValue(':title',$title,PDO::PARAM_STR);
    $sth->bindValue(':ftext',$formaltext,PDO::PARAM_STR);
    $sth->bindValue(':column',$column,PDO::PARAM_INT);
    $sth->bindValue(':article_id',$article_id,PDO::PARAM_INT);
    $sth->execute();

    // 3:Delete or get old tags
    if (empty($tags_get)) {
        // 3.1:If empty tags, delete all in tag_mid, finish.
        $sth = $dbh->prepare("DELETE from tag_mid where article_id = :article_id");
        $sth->bindValue(':article_id',$article_id,PDO::PARAM_INT);
        $sth->execute();
    } else {
        // 3.2:If have tags, select in all tags ,match the same 
        $param = "";
        foreach ($tags_get as $value) {
            $param .= $dbh->quote($value).",";
        }
        $param = trim($param,",");
        $sth = $dbh->prepare("SELECT * from tag WHERE name in ($param)");
        $sth->bindValue(':article_id',$article_id,PDO::PARAM_STR);
        $sth->execute();

        $sameTags = $sth->fetchAll();
        $arr_id = array();
        $arr_name = array();

        //Get the old tags id & name
        foreach ($sameTags as $value) {
            $arr_id[] = $value["id"];
            $arr_name[] = $value["name"];
        }

        if (!empty($midTags_id)) {
            $param = "";
            foreach ($midTags_id as $value) {
                $param .= $dbh->quote($value).",";
            }
            $param = trim($param,",");
            $sth = $dbh->prepare("SELECT * from tag WHERE id in ($param)");
            $sth->bindValue(':article_id',$article_id,PDO::PARAM_STR);
            $sth->execute();

            $oldTags = array();
            $oldTags = $sth->fetchAll();
            
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
            $sqlValues = '';
            foreach ($arr_real_insert as $value) {
                $sqlValues .= "(".$dbh->quote($value)."),";
            }
            $sqlValues = trim($sqlValues,",");
            $sth = $dbh->prepare("INSERT into tag(name) VALUES $sqlValues");
            $sth->execute();
        }

        //Select diff tags id 
        $param = "";
        foreach ($arr_insert as $value) {
            $param .= $dbh->quote($value).",";
        }
        $param = trim($param,",");
        $sth = $dbh->prepare("SELECT * from tag WHERE name in ($param)");
        $sth->execute();
        $diffTags = $sth->fetchAll();

        //Find the same tags id & name
        foreach ($diffTags as $value) {
            $arr_id_insert[] = $value["id"];
            $arr_name_insert[] = $value["name"];
        }
        
        // Insert3: new tag & article (table tag_mid)
        $sqlValues = '';
        foreach ($arr_id_insert as $value) {
            $sqlValues .= "($value,$article_id),";
        }
        $sqlValues = trim($sqlValues,",");
        $sth = $dbh->prepare("INSERT into tag_mid(tag_id,article_id) VALUES $sqlValues");
        $sth->execute();
    }

    // 5:Delete
    if (!empty($arr_delete)) {
        //Select diff tags id 
        $param = '';
        foreach ($arr_delete as $value) {
            $param .= $dbh->quote($value).',';
        }
        $param = trim($param,',');
        $sth = $dbh->prepare("SELECT * from tag WHERE name in ($param)");
        $sth->execute();
        $diffTags = $sth->fetchAll();

        //Put id & name into array
        foreach ($diffTags as $value) {
            $arr_id_delete[] = $value["id"];
            $arr_name_delete[] = $value["name"];
        }

        // Delete: new tag & article (table tag_mid)
        $param = '';
        foreach ($arr_id_delete as $value) {
            $param .= $dbh->quote($value).',';
        }
        $param = trim($param,',');
        $sth = $dbh->prepare("DELETE from tag_mid where tag_id in ($param) and article_id = $article_id");
        $sth->execute();
    }

    $dbh->commit();

    return true;
}

function deleteArticle ($dbh,$user_id,$article_id) 
{
    //Valid id check
    $user_id = filter_var($user_id,FILTER_VALIDATE_INT,array("options" => array("min_range" => 1)));
    $id = filter_var(($article_id),FILTER_VALIDATE_INT,array("options" => array("min_range" => 1)));
    if (!$id || !$user_id) {
        throw new InvalidArgumentException("Illegal operation");
    }
    
    $sql = "SELECT * from article where id = $id ";
    $result = $dbh->query($sql)->fetchColumn();
    if ($result == 1) {
        //Delete article
        $sql = "DELETE from article where id=$id and user_id=$user_id";
        $result = $dbh->query($sql)->rowCount();
        if ($result == 0) {
            throw new InvalidArgumentException("Delete failed: incorrect user");
        } else {
            return true;
        }
    } else {
        throw new InvalidArgumentException("Delete failed: article don't exist");
        
    }
}
