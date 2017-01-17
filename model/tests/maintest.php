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
                array("id"=>1,"email"=>"tianyi@163.com","password"=>md5("123456"))
            ),
            "article"=>array(
                array("id"=>1,"title"=>"test article","formaltext"=>"wojiushi zhengwen","user_id"=>1)
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
        $link = databaseConnect("blog_test");
        $user_id = login($data,$link);

        $this->assertEquals(1,$user_id);
    }

    /**
     * @expectedException   InvalidArgumentException
     * @expectedExceptionMessage Incorrect Email or Password!
     */
    public function testLoginIncorrectEmail()
    {
        $data = array("email"=>"tianyi@163.c","password"=>"123456");
        $link = databaseConnect("blog_test");
        login($data,$link);
    }

    /**
     * @expectedException   InvalidArgumentException
     * @expectedExceptionMessage Illegal rules
     */
    public function testLoginIllegalRules()
    {
        $data = array("email"=>"tianyi@163","password"=>"!@#$%^^&*(");
        $link = databaseConnect("blog_test");
        login($data,$link);
    }

    /**
     * @expectedException   InvalidArgumentException
     * @expectedExceptionMessage Please fill the blank
     */
    public function testLoginEmpty()
    {
        $data = array("email"=>"","password"=>"");
        $link = databaseConnect("blog_test");
        login($data,$link);
    }

    /**
     * @expectedException   InvalidArgumentException
     * @expectedExceptionMessage Invalid param
     */
    public function testLoginLostparam()
    {
        $data = array("email"=>"tianyi@163.com");
        $link = databaseConnect("blog_test");
        login($data,$link);
    }

    /**
     * @expectedException   InvalidArgumentException
     * @expectedExceptionMessage Illegal rules
     */
    public function testLoginSQLInject()
    {
        $data1 = array("email"=>"tianyi@163.'or''","password"=>"123456");
        $data2 = array("email"=>"tianyi@163.com","password"=>"'or''");
        $link = databaseConnect("blog_test");
        login($data1,$link);
        login($data2,$link);
    }

    //Add article test
    public function testAddArticle()
    {
        $data1 = array("id"=>2,"title"=>"testTitle","formaltext"=>"testFormaltext","column"=>1,"tag"=>"");
        $data2 = array("id"=>2,"title"=>"testTitle","formaltext"=>"testFormaltext","column"=>1,"tag"=>"php,java,js");
        $link = databaseConnect("blog_test");
        $user_id = 1;
        $result1 = addArticle($data1,$link,$user_id);
        $result2 = addArticle($data2,$link,$user_id);

        $this->assertEquals(true,$result1);
        $this->assertEquals(true,$result2);
    }

    /**
     * @expectedException   InvalidArgumentException
     * @expectedExceptionMessage Please fill the blank
     */
    public function testAddArticleEmpty()
    {
        $data1 = array("id"=>2,"title"=>"","formaltext"=>"testFormaltext","column"=>1,"tag"=>"");
        $data2 = array("id"=>"","title"=>"","formaltext"=>"testFormaltext","column"=>1,"tag"=>"");
        $data3 = array("id"=>"","title"=>"testTitle","formaltext"=>"","column"=>1,"tag"=>"");
        $data4 = array("id"=>"","title"=>"testTitle","formaltext"=>"testFormaltext","column"=>"","tag"=>"");
        $link = databaseConnect("blog_test");
        $user_id = 1;
        addArticle($data1,$link,$user_id);
        addArticle($data2,$link,$user_id);
        addArticle($data3,$link,$user_id);
        addArticle($data4,$link,$user_id);
    }

    /**
     * @expectedException   InvalidArgumentException
     * @expectedExceptionMessage missing requied key
     */
    public function testAddArticleUnset()
    {
        $data1 = array();
        $link = databaseConnect("blog_test");
        $user_id = 1;
        addArticle($data1,$link,$user_id);
    }

    /**
     * @expectedException   InvalidArgumentException
     * @expectedExceptionMessage Invalid column
     */
    public function testAddArticleErrorParam()
    {
        $data1 = array("id"=>2,"title"=>"'or''","formaltext"=>"!@#$%^&*([];',./","column"=>1.123321,"tag"=>"");
        $link = databaseConnect("blog_test");
        $user_id = 1;
        addArticle($data1,$link,$user_id);
    }

    /**
     * @expectedException   InvalidArgumentException
     * @expectedExceptionMessage 添加失败:error01
     */
    public function testAddArticleWrongUser()
    {
        $data1 = array("id"=>2,"title"=>"testTitle","formaltext"=>"testFormaltext","column"=>1,"user_id"=>1,"tag"=>"");
        $link = databaseConnect("php_manual_test");
        $user_id = 2;
        addArticle($data1,$link,$user_id);
    }


    //Edit article
    public function testEditArticle()
    {
        $data1 = array("id"=>1,"title"=>"testTitle","formaltext"=>"testFormaltext","column"=>1,"tag"=>"php,java,js");
        $link = databaseConnect("blog_test");
        $user_id = 1;
        $article_id = 1;
        $result1 = editArticle($data1,$link,$user_id,$article_id);

        $this->assertEquals(true,$result1);
    }

    public function testEditArticleAddTag()
    {
        $data1 = array("id"=>1,"title"=>"testTitle","formaltext"=>"testFormaltext","column"=>1,"tag"=>"php,java,js");
        $link = databaseConnect("blog_test");
        $user_id = 1;
        $article_id = 1;
        $result = editArticle($data1,$link,$user_id,$article_id);

        $this->assertEquals(true,$result);
    }

    public function testEditArticleReduceTag()
    {
        $data1 = array("id"=>1,"title"=>"testTitle","formaltext"=>"testFormaltext","column"=>1,"tag"=>"php");
        $link = databaseConnect("blog_test");
        $user_id = 1;
        $article_id = 1;
        $result = editArticle($data1,$link,$user_id,$article_id);

        $this->assertEquals(true,$result);
    }

    public function testEditArticleEmptyTag()
    {
        $data1 = array("id"=>1,"title"=>"testTitle","formaltext"=>"testFormaltext","column"=>1,"tag"=>"");
        $link = databaseConnect("blog_test");
        $user_id = 1;
        $article_id = 1;
        $result = editArticle($data1,$link,$user_id,$article_id);

        $this->assertEquals(true,$result);
    }

    /**
     * @expectedException   InvalidArgumentException
     * @expectedExceptionMessage Illegal user operation!
     */
    public function testEditArticleWrongUser()
    {
        $data1 = array("id"=>1,"title"=>"testTitle","formaltext"=>"testFormaltext","column"=>1,"tag"=>"php,java,js");
        $link = databaseConnect("blog_test");
        $user_id = 2;
        $article_id = 1;
        editArticle($data1,$link,$user_id,$article_id);
    }

    /**
     * @expectedException   InvalidArgumentException
     * @expectedExceptionMessage missing requied key title
     */
    public function testEditArticleNullParam()
    {
        $data1 = array("id"=>1,"formaltext"=>"testFormaltext","column"=>1,"tag"=>"php,java,js");
        $data2 = array("id"=>1,"title"=>"","column"=>1,"tag"=>"php,java,js");
        $data3 = array("id"=>1,"title"=>"","formaltext"=>"testFormaltext","tag"=>"php,java,js");
        $data4 = array("id"=>1,"title"=>"","formaltext"=>"testFormaltext","column"=>1);
        $data5 = array("title"=>"","formaltext"=>"testFormaltext","column"=>1);
        $link = databaseConnect("blog_test");
        $user_id = 1;
        $article_id = 1;
        editArticle($data1,$link,$user_id,$article_id);
        editArticle($data2,$link,$user_id,$article_id);
        editArticle($data3,$link,$user_id,$article_id);
        editArticle($data4,$link,$user_id,$article_id);
        editArticle($data5,$link,$user_id,$article_id);
    }

    /**
     * @expectedException   InvalidArgumentException
     * @expectedExceptionMessage Please fill the blank
     */
    public function testEditArticleEmptyParam()
    {
        $data1 = array("id"=>1,"title"=>"","formaltext"=>"","column"=>1,"tag"=>"php,java,js");
        $link = databaseConnect("blog_test");
        $user_id = 1;
        $article_id = 1;
        editArticle($data1,$link,$user_id,$article_id);
    }

    public function testEditArticleIllegalParam()
    {
        $data1 = array("id"=>1.1,"title"=>"'or''","formaltext"=>"'or''","column"=>1.2,"tag"=>"'or''");
        $link = databaseConnect("blog_test");
        $user_id = 1;
        $article_id = 1;
        editArticle($data1,$link,$user_id,$article_id);
    }


    //Delete article
    public function testDeleteArticle()
    {
        $link = databaseConnect("blog_test");
        $user_id = 1;
        $article_id = 1;
        $result =  deleteArticle ($link,$user_id,$article_id);

        $this->assertEquals(true,$result);
    }

    /**
     * @expectedException   InvalidArgumentException
     * @expectedExceptionMessage The article don\'t exist or incorrect user
     */
    public function testDeleteArticleWrongUser()
    {
        $link = databaseConnect("blog_test");
        $user_id = 2;
        $article_id = 1;
        deleteArticle ($link,$user_id,$article_id);
    }

    /**
     * @expectedException   InvalidArgumentException
     * @expectedExceptionMessage The article don\'t exist or incorrect user
     */
    public function testDeleteArticleWrongArticle()
    {
        $link = databaseConnect("blog_test");
        $user_id = 1;
        $article_id = 2;
        deleteArticle ($link,$user_id,$article_id);
    }

    /**
     * @expectedException   InvalidArgumentException
     * @expectedExceptionMessage Ivalid rules
     */
    public function testDeleteArticleIllegalArticle()
    {
        $link = databaseConnect("blog_test");
        $user_id = 1;
        $article_id = "aaa";
        deleteArticle ($link,$user_id,$article_id);
    }

    /**
     * @expectedException   InvalidArgumentException
     * @expectedExceptionMessage Ivalid rules
     */
    public function testDeleteArticleIllegalUser()
    {
        $link = databaseConnect("blog_test");
        $user_id = "aaa";
        $article_id = 1;
        deleteArticle ($link,$user_id,$article_id);
    }

    //function paramcheck test
    public function testParamcheck()
    {
        $param = "abcdefg";
        paramCheck($param,7);
    }

    /**
     * @expectedException   InvalidArgumentException
     * @expectedExceptionMessage Illegal param length
     */
    public function testParamcheckLength()
    {
        $param = "abcdefg";
        paramCheck($param,-1);
        paramCheck($param,0);
        paramCheck($param,1);
    }

    /**
     * @expectedException   InvalidArgumentException
     * @expectedExceptionMessage Please fill the blank
     */
    public function testParamcheckNull()
    {
        $param = "";
        paramCheck($param,7);
    }

}