<?php
include __DIR__."/../main.php";
require __DIR__."/../../config/database.php";
include __DIR__."/MyApp_DbUnit_ArrayDataSet.php";

class mainTest extends PHPUnit_Extensions_Database_TestCase
{   
    public function getConnection()
    {
        $pdo = new PDO("mysql:host=127.0.0.1;dbname=blog_test","root","123456");
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $this->createDefaultDBConnection($pdo, "blog_test");
    }

    public function getDataSet()
    {
        $EmpArrSet = array(
            "user"=>array(
                array(
                    "id"        =>1,
                    "email"     =>"tianyi@163.com",
                    "password"  =>md5("123456")
                    )
            ),
            "article"=>array(
                array(
                    "id"        =>1,
                    "title"     =>"test article",
                    "formaltext"=>"wojiushi zhengwen",
                    "user_id"   =>1
                    )
            ),
            "tag"=>array(
                array("id"=>1,"name"=>"php","user_id"=>1),
                array("id"=>2,"name"=>"java","user_id"=>1)
            ),
            "tag_mid"=>array(
                array("id"=>1,"tag_id"=>1,"article_id"=>1),
                array("id"=>2,"tag_id"=>2,"article_id"=>1)
            )
        );
        return new MyApp_DbUnit_ArrayDataSet($EmpArrSet);
    }

    //Login test
    public function testLogin()
    {
        $data = array("email"=>"tianyi@163.com","password"=>"123456");
        $dbh = PDOStart();
        $user_id = login($data,$dbh);

        $this->assertEquals(1,$user_id);
    }

    /**
     * @expectedException   InvalidArgumentException
     * @expectedExceptionMessage Invalid Email or Password!
     */
    public function testLoginLostparam()
    {
        $data = array("email"=>"tianyi@163.com");
        $dbh = PDOStart();
        login($data,$dbh);
    }

    /**
     * @expectedException   InvalidArgumentException
     * @expectedExceptionMessage Please fill the email
     */
    public function testLoginEmptyEmail()
    {
        $data = array("email"=>"","password"=>"");
        $dbh = PDOStart();
        login($data,$dbh);
    }

    /**
     * @expectedException   InvalidArgumentException
     * @expectedExceptionMessage Please fill the password
     */
    public function testLoginEmptyPassword()
    {
        $data = array("email"=>"aaa@163.com","password"=>"");
        $dbh = PDOStart();
        login($data,$dbh);
    }

    /**
     * @expectedException   InvalidArgumentException
     * @expectedExceptionMessage Illegal Email address
     */
    public function testLoginIllegalRules()
    {
        $data = array("email"=>"tianyi@163","password"=>"!@#$%^^&*(");
        $dbh = PDOStart();
        login($data,$dbh);
    }

    /**
     * @expectedException   InvalidArgumentException
     * @expectedExceptionMessage Incorrect Email or Password!
     */
    public function testLoginSQLInject()
    {
        $data = array("email"=>"'or''@163.com","password"=>"123456");
        $dbh = PDOStart();
        login($data,$dbh);
    }

    /**
     * @expectedException   InvalidArgumentException
     * @expectedExceptionMessage Incorrect Email or Password!
     */
    public function testLoginSQLInjectPassword()
    {
        $data = array("email"=>"tianyi@163.com","password"=>"'or''");
        $dbh = PDOStart();
        login($data,$dbh);
    }

    /**
     * @expectedException   InvalidArgumentException
     * @expectedExceptionMessage Incorrect Email or Password!
     */
    public function testLoginIncorrectEmail()
    {
        $data = array("email"=>"aaaaa@163.com","password"=>"123456");
        $dbh = PDOStart();
        login($data,$dbh);
    }

    /**
     * @expectedException   InvalidArgumentException
     * @expectedExceptionMessage Incorrect Email or Password!
     */
    public function testLoginIncorrectPassword()
    {
        $data = array("email"=>"tianyi@163.com","password"=>"000000");
        $dbh = PDOStart();
        login($data,$dbh);
    }


    // Add article test
    public function testAddArticle()
    {
        $data1 = array(
            "id"        =>2,
            "title"     =>"testTitle",
            "formaltext"=>"testFormaltext",
            "column"    =>1,
            "tag"       =>""
            );
        $data2 = array(
            "id"        =>2,
            "title"     =>"testTitle",
            "formaltext"=>"testFormaltext",
            "column"    =>1,
            "tag"       =>"php,java,js");
        $dbh = PDOStart();
        $user_id = 1;
        $result1 = addArticle($data1,$dbh,$user_id);
        $result2 = addArticle($data2,$dbh,$user_id);

        $this->assertEquals(true,$result1);
        $this->assertEquals(true,$result2);
    }

    public function testAddArticleSQLinjection()
    {
        $data1 = array(
            "id"        =>2,
            "title"     =>"testTitle'or''",
            "formaltext"=>"testFormaltext",
            "column"    =>1,
            "tag"       =>""
            );
        $data2 = array(
            "id"        =>2,
            "title"     =>"testTitle",
            "formaltext"=>"testFormaltext'or''",
            "column"    =>1,
            "tag"       =>"php,java,js");
        $dbh = PDOStart();
        $user_id = 1;
        $result1 = addArticle($data1,$dbh,$user_id);
        $result2 = addArticle($data2,$dbh,$user_id);

        $this->assertEquals(true,$result1);
        $this->assertEquals(true,$result2);
    }



    /**
     * @expectedException   InvalidArgumentException
     * @expectedExceptionMessage Please fill the title
     */
    public function testAddArticleEmptyTitle()
    {
        $data1 = array(
            "id"        =>2,
            "title"     =>"",
            "formaltext"=>"testFormaltext",
            "column"    =>1,
            "tag"       =>"java,php"
            );
        $dbh = PDOStart();
        $user_id = 1;
        addArticle($data1,$dbh,$user_id);
    }

    /**
     * @expectedException   InvalidArgumentException
     * @expectedExceptionMessage Please fill the formaltext
     */
    public function testAddArticleEmptyFormaltext()
    {
        $data2 = array(
            "id"        =>2,
            "title"     =>"title",
            "formaltext"=>"",
            "column"    =>1,
            "tag"       =>"java,php"
            );
        $dbh = PDOStart();
        $user_id = 1;
        addArticle($data2,$dbh,$user_id);
    }

    /**
     * @expectedException   InvalidArgumentException
     * @expectedExceptionMessage Title is over range(64)!
     */
    public function testAddArticleOverRange()
    {
        $data3 = array(
            "id"        =>2,
            "title"     => "12345678901234567890
                            12345678901234567890
                            12345678901234567890
                            1234567890",
            "formaltext"=>"testFormaltext",
            "column"    =>1,
            "tag"       =>"java,php"
            );

        $dbh = PDOStart();
        $user_id = 1;
        addArticle($data3,$dbh,$user_id);
    }

    /**
     * @expectedException   InvalidArgumentException
     * @expectedExceptionMessage Column is invalid
     */
    public function testAddArticleEmptyColumn()
    {
        $data3 = array(
            "id"        =>2,
            "title"     =>"testTitle",
            "formaltext"=>"testFormaltext",
            "column"    =>'',
            "tag"       =>"java,php"
            );

        $dbh = PDOStart();
        $user_id = 1;
        addArticle($data3,$dbh,$user_id);
    }

    public function testAddArticleEmptyTag()
    {
        $data4 = array(
            "id"        =>2,
            "title"     =>"testTitle",
            "formaltext"=>"testFormaltext",
            "column"    =>1,
            "tag"       =>""
            );
        $dbh = PDOStart();
        $user_id = 1;
        $result = addArticle($data4,$dbh,$user_id);
        $this->assertEquals(true,$result);
    }

    /**
    * @expectedException   InvalidArgumentException
    * @expectedExceptionMessage missing requied key
    */
    public function testAddArticleUnset()
    {
        $data1 = array();
        $dbh = PDOStart();
        $user_id = 1;
        addArticle($data1,$dbh,$user_id);
    }

    /**
     * @expectedException   InvalidArgumentException
     * @expectedExceptionMessage Column is invalid
     */
    public function testAddArticleErrorParam()
    {
        $data1 = array(
            "id"        =>2,
            "title"     =>"normaltext",
            "formaltext"=>"!@#$%^&*([];',./",
            "column"    =>1.123321,
            "tag"       =>""
            );
        $dbh = PDOStart();
        $user_id = 1;
        addArticle($data1,$dbh,$user_id);
    }


    // Edit article
    public function testEditArticle()
    {
        $data1 = array(
            "id"=>1,
            "title"=>"testTitle",
            "formaltext"=>"testFormaltext",
            "column"=>1,
            "tag"=>"php,java,js"
            );
        $dbh = PDOStart();
        $user_id = 1;
        $article_id = 1;
        $result1 = editArticle($data1,$dbh,$user_id,$article_id);

        $this->assertEquals(true,$result1);
    }

    public function testEditArticleAddTag()
    {
        $data1 = array(
            "id"=>1,
            "title"=>"testTitle",
            "formaltext"=>"testFormaltext",
            "column"=>1,
            "tag"=>"php,java,js"
            );
        $dbh = PDOStart();
        $user_id = 1;
        $article_id = 1;
        $result = editArticle($data1,$dbh,$user_id,$article_id);

        $this->assertEquals(true,$result);
    }

    public function testEditArticleReduceTag()
    {
        $data1 = array("id"=>1,"title"=>"testTitle","formaltext"=>"testFormaltext","column"=>1,"tag"=>"php");
        $dbh = PDOStart();
        $user_id = 1;
        $article_id = 1;
        $result = editArticle($data1,$dbh,$user_id,$article_id);

        $this->assertEquals(true,$result);
    }

    public function testEditArticleEmptyTag()
    {
        $data1 = array("id"=>1,"title"=>"testTitle","formaltext"=>"testFormaltext","column"=>1,"tag"=>"");
        $dbh = PDOStart();
        $user_id = 1;
        $article_id = 1;
        $result = editArticle($data1,$dbh,$user_id,$article_id);

        $this->assertEquals(true,$result);
    }

    /**
     * @expectedException   InvalidArgumentException
     * @expectedExceptionMessage missing requied key title
     */
    public function testEditArticleNullTitle()
    {
        $data1 = array("id"=>1,"formaltext"=>"testFormaltext","column"=>1,"tag"=>"php,java,js");
        $dbh = PDOStart();
        $user_id = 1;
        $article_id = 1;
        editArticle($data1,$dbh,$user_id,$article_id);
    }

    /**
     * @expectedException   InvalidArgumentException
     * @expectedExceptionMessage missing requied key formaltext
     */
    public function testEditArticleNullFormaltext()
    {
        $data2 = array("id"=>1,"title"=>"testTitle","column"=>1,"tag"=>"php,java,js");
        $dbh = PDOStart();
        $user_id = 1;
        $article_id = 1;
        editArticle($data2,$dbh,$user_id,$article_id);
    }

    /**
     * @expectedException   InvalidArgumentException
     * @expectedExceptionMessage missing requied key column
     */

    public function testEditArticleNullColumn()
    {
        $data3 = array("id"=>1,"title"=>"testTitle","formaltext"=>"testFormaltext","tag"=>"php,java,js");
        $dbh = PDOStart();
        $user_id = 1;
        $article_id = 1;
        editArticle($data3,$dbh,$user_id,$article_id);
    }

    /**
     * @expectedException   InvalidArgumentException
     * @expectedExceptionMessage Please fill the title
     */
    public function testEditArticleEmptyTitle()
    {
        $data3 = array("id"=>1,"title"=>"","formaltext"=>"testFormaltext","column"=>1,"tag"=>"php,java,js");
        $dbh = PDOStart();
        $user_id = 1;
        $article_id = 1;
        editArticle($data3,$dbh,$user_id,$article_id);
    }

    /**
     * @expectedException   InvalidArgumentException
     * @expectedExceptionMessage Please fill the formaltext
     */
    public function testEditArticleEmptyFormaltext()
    {
        $data3 = array("id"=>1,"title"=>"testTitle","formaltext"=>"","column"=>1,"tag"=>"php,java,js");
        $dbh = PDOStart();
        $user_id = 1;
        $article_id = 1;
        editArticle($data3,$dbh,$user_id,$article_id);
    }


    /**
     * @expectedException   InvalidArgumentException
     * @expectedExceptionMessage Column is invalid
     */
    public function testEditArticleIllegalParam()
    {
        $data1 = array("id"=>1.1,"title"=>"'or''","formaltext"=>"'or''","column"=>1.2,"tag"=>"'or''");
        $dbh = PDOStart();
        $user_id = 1;
        $article_id = 1;
        editArticle($data1,$dbh,$user_id,$article_id);
    }

    /**
     * @expectedException   InvalidArgumentException
     * @expectedExceptionMessage Illegal user operation!
     */
    public function testEditArticleWrongUser()
    {
        $data1 = array("id"=>1,"title"=>"testTitle","formaltext"=>"testFormaltext","column"=>1,"tag"=>"php,java,js");
        $dbh = PDOStart();
        $user_id = 2;
        $article_id = 1;
        editArticle($data1,$dbh,$user_id,$article_id);
    }


    //Delete article
    public function testDeleteArticle()
    {
        $dbh = PDOStart();
        $user_id = 1;
        $article_id = 1;
        $result =  deleteArticle ($dbh,$user_id,$article_id);

        $this->assertEquals(true,$result);
    }

    /**
     * @expectedException   InvalidArgumentException
     * @expectedExceptionMessage Illegal operation
     */
    public function testDeleteArticleIllegalArticle()
    {
        $dbh = PDOStart();
        $user_id = 1;
        $article_id = "aaa";
        deleteArticle ($dbh,$user_id,$article_id);
    }

    /**
     * @expectedException   InvalidArgumentException
     * @expectedExceptionMessage Illegal operation
     */
    public function testDeleteArticleIllegalUser()
    {
        $dbh = PDOStart();
        $user_id = "aaa";
        $article_id = 1;
        deleteArticle ($dbh,$user_id,$article_id);
    }

    /**
     * @expectedException   InvalidArgumentException
     * @expectedExceptionMessage Delete failed: incorrect user
     */
    public function testDeleteArticleWrongUser()
    {
        $dbh = PDOStart();
        $user_id = 2;
        $article_id = 1;
        deleteArticle ($dbh,$user_id,$article_id);
    }

    /**
     * @expectedException   InvalidArgumentException
     * @expectedExceptionMessage Delete failed: article don't exist
     */
    public function testDeleteArticleWrongArticle()
    {
        $dbh = PDOStart();
        $user_id = 1;
        $article_id = 2;
        deleteArticle ($dbh,$user_id,$article_id);
    }

}