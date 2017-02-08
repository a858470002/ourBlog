<?php

function login ($data,$dbh)
{
    //Param check
    if (!isset($data['email'])) {
        throw new InvalidArgumentException('Miss reuqire key: Email');
    }
    if (empty($data['email'])) {
        throw new InvalidArgumentException('Please fill the Email');
    }
    $email = filter_var(($data['email']),FILTER_VALIDATE_EMAIL);
    if (!$email) {
        throw new InvalidArgumentException('Illegal Email address');
    }

    if (!isset($data['password'])) {
        throw new InvalidArgumentException('Miss reuqire key: Password');
    }
    if (empty($data['password'])) {
        throw new InvalidArgumentException('Please fill the password');
    }
    $password = md5($data['password']);

    //PDO start
    $sth = $dbh->prepare("SELECT * from user where `email` = :email and password = :password");
    $sth->bindValue(':email',$email,PDO::PARAM_STR);
    $sth->bindValue(':password',$password,PDO::PARAM_INT);
    $sth->execute();

    if ($sth->rowCount()==0) {
        return False;
    }
    $user = $sth->fetch();
    return $user['id'];
}

function addArticle ($data,$dbh,$user_id) 
{
    $requiredKeys = array('title', 'formaltext', 'column', 'tag', 'link', 'isLink');
    foreach ($requiredKeys as $key) {
        if (!isset($data[$key])) {
            throw new InvalidArgumentException("Missing requied key $key");
        }
    }
    $title = trim($data['title']);
    if (empty($title)) {
        throw new InvalidArgumentException('Please fill the title');
    }
    $length = mb_strlen($title,'UTF-8');
    if ($length > 64) {
        throw new InvalidArgumentException('Title is over range(64)!');
    }

    if ($data['link'] == ''){
        $link = null;
    } else {
        $link = $data['link'];
    }
    $is_link = $data['isLink'];
    if (!($is_link == 0 || $is_link == 1)) {
        throw new InvalidArgumentException("'IsLink' is invalid");
    }
    $formaltext = $data['formaltext'];
    if ($is_link == 0 && empty($formaltext)) {
        throw new InvalidArgumentException('Please fill the formaltext');
    }
    if ($is_link == 1 && empty($link)) {
        throw new InvalidArgumentException('Please set a link');
    }
    if (!empty($formaltext) && !empty($link)) {
        throw new InvalidArgumentException('One of params(formaltext, link) must be empty');
    }
    $length  = mb_strlen($formaltext,'UTF-8');
    if ($length > 65534) {
        throw new InvalidArgumentException('Formaltext is over range(65535)!');
    }
    $column = filter_var($data['column'],FILTER_VALIDATE_INT,array('options' => array('min_range' => 1)));
    if (!$column) {
        throw new InvalidArgumentException('Column is invalid');
    }
    
    if (!empty($data['tag'])) {
        $tags = explode(',', $data['tag']);
        if (count($tags) >= 10) {
            throw new InvalidArgumentException('Don\'t use over 10 tags');
        }
        foreach ($tags as $value) {
            $length = mb_strlen($value,'UTF-8');
            if ($length > 32) {
                throw new InvalidArgumentException('Some of tags is over range(32)!');
            }
        }
    } else {
        $tags = array();
    }

    $dbh->beginTransaction();

    try {
        // 1.insert new article
        $sth = $dbh->prepare("INSERT into article(title,formaltext,`column`,user_id,link,is_link) VALUES (:title,:ftext,:column,:user_id,:link,:is_link);");
        $sth->bindValue(':title',$title,PDO::PARAM_STR);
        $sth->bindValue(':ftext',$formaltext,PDO::PARAM_STR);
        $sth->bindValue(':column',$column,PDO::PARAM_INT);
        $sth->bindValue(':user_id',$user_id,PDO::PARAM_INT);
        $sth->bindValue(':link',$link,PDO::PARAM_STR);
        $sth->bindValue(':is_link',$is_link,PDO::PARAM_INT);
        $sth->execute();

        $article_id = $dbh->lastInsertId();

        // If have tags
        if (!empty($tags)) {
            // Select all tags ,match the same 
            $param = array();
            foreach ($tags as $value) {
                $param[] = $dbh->quote($value);
            }
            $param = implode(',', $param);

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
            $sqlValues  = array();

            foreach ($arr_diff as $v) {
                $sqlValues[] = "(".$dbh->quote($v).",$user_id),";
            }
            $sqlValues = implode(',', $sqlValues);

            $sth = $dbh->prepare("INSERT into tag(name,user_id) VALUES $sqlValues");
            $sth->execute();

        }

        if (!empty($tags)) {
            // 4.Select diff tags id 
            $param = array();
            foreach ($tags as $value) {
                $param[] = $dbh->quote($value);
            }
            $param = implode(',', $param);
            $sql = "SELECT * from tag WHERE name in ($param)";
            $sth = $dbh->query($sql);
            $diffTags = $sth->fetchAll();

            $arr_id   = array();
            $arr_name = array();
            //Find the same tags id & name
            foreach ($diffTags as $value) {
                $arr_id[] = $value["id"];
                $arr_name[] = $value["name"];
            }
            
            // 5.insert new tag & article (table tag_mid)
            $sqlValues = array();
            foreach ($arr_id as $value) {
                $sqlValues[] = "(".$dbh->quote($value).",'$article_id')";
            }
            $sqlValues = implode(',', $sqlValues);
            $sth = $dbh->prepare("INSERT into tag_mid(tag_id,article_id) VALUES $sqlValues");
            $sth->execute();
        }

        $dbh->commit();

    } catch (Exception $e) {
        $dbh->rollBack();
        throw $e;
    }
}

function editArticle ($data,$dbh,$user_id,$article_id) 
{
    $requiredKeys = array('title', 'formaltext', 'link', 'column', 'tag');
    foreach ($requiredKeys as $key) {
        if (!isset($data[$key])) {
            throw new InvalidArgumentException("missing requied key $key");
        }
    }
    $title = trim($data['title']);
    if (empty($title)) {
        throw new InvalidArgumentException('Please fill the title');
    }

    $length = mb_strlen($title,"UTF-8");
    if ($length > 64) {
        throw new InvalidArgumentException('Title is over range(64)!');
    }

    $column = filter_var($data['column'],FILTER_VALIDATE_INT,array('options' => array('min_range' => 1)));
    if (!$column) {
        throw new InvalidArgumentException('Column is invalid');
    }
    $article_id = filter_var($article_id,FILTER_VALIDATE_INT,array("options" => array("min_range" => 1)));
    if (!$article_id) {
        throw new InvalidArgumentException('Articleid is invalid');
    }

    if (!empty($data["tag"])) {
        $tags_get = explode(',', $data['tag']);
        if (count($tags_get)>=10) {
            throw new InvalidArgumentException("Don't use over 10 tags");
        }
        foreach ($tags_get as $value) {
            $length = mb_strlen($value,"UTF-8");
            if ($length > 32) {
                throw new InvalidArgumentException('Some of tags is over range(32)!');
            }
        }
    } else {
        $tags_get = array();
    }

    // PDO Start

    // User check
    $sql = "SELECT * from article where id = $article_id and user_id = $user_id";
    $result = $dbh->query($sql)->fetchAll();
    if (empty($result)) {
        throw new InvalidArgumentException("It's not your article");
    }
    if ($result[0]['is_link'] == 0) {
        if (!isset($data['formaltext'])) {
            throw new InvalidArgumentException('Missing requied key formaltext');
            
        }
        $formaltext = $data['formaltext'];
        if (empty($formaltext)) {
            throw new InvalidArgumentException('The formaltext can not be empty');
        }

        $length = mb_strlen($formaltext,'UTF-8');
        if ($length > 65534) {
            throw new InvalidArgumentException('Formaltext is over range(65535)!');
        }
        $link = NULL;
    } else {
        if (!isset($data['link'])) {
            throw new InvalidArgumentException('Missing requied key link');
            
        }
        $link = $data['link'];
        if (empty($link)) {
            throw new InvalidArgumentException('The link can not be empty');
        }
        $formaltext = '';
    }


    try {
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
        $sth = $dbh->prepare("UPDATE article set title = :title,formaltext = :ftext,`column` = :column, link = :link where id=:article_id");
        $sth->bindValue(':title',$title,PDO::PARAM_STR);
        $sth->bindValue(':ftext',$formaltext,PDO::PARAM_STR);
        $sth->bindValue(':column',$column,PDO::PARAM_INT);
        $sth->bindValue(':link',$link,PDO::PARAM_STR);
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
                    $sqlValues .= "(".$dbh->quote($value).",$user_id),";
                }
                $sqlValues = trim($sqlValues,",");
                $sth = $dbh->prepare("INSERT into tag(name,user_id) VALUES $sqlValues");
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
    } catch (Exception $e) {
        $dbh->rollBack();
        throw $e;
    }
}

function deleteArticle ($dbh,$user_id,$article_id) 
{
    //Valid id check
    $id = filter_var($article_id,FILTER_VALIDATE_INT,array("options" => array("min_range" => 1)));
    if (!$id) {
        throw new InvalidArgumentException("Illegal operation");
    }
    $sql = "SELECT id,user_id from article where id = $id ";
    $result = $dbh->query($sql)->fetchAll();
    if ($result == null) {
        throw new InvalidArgumentException("Delete failed: article don't exist");
    } else {
        if ($result[0]['user_id'] == $user_id) {
            //Delete article
            $sql = "DELETE from article where id =$id ;DELETE from tag_mid where article_id = $id";
            $result = $dbh->query($sql);
        } else {
            throw new InvalidArgumentException("Delete failed: incorrect user");
        }
    }
}
