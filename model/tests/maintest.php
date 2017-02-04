<?php
include __DIR__."/../main.php";
require __DIR__."/database.php";
include __DIR__."/MyApp_DbUnit_ArrayDataSet.php";
require __DIR__."/dataset.php";

class mainTest extends PHPUnit_Extensions_Database_TestCase
{   
    public function getConnection()
    {
        $pdo = new PDO("mysql:host=127.0.0.1;dbname=blog_test;charset=utf8", "root", "123456");
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $this->createDefaultDBConnection($pdo, "blog_test");
    }

    public function getDataSet()
    {
        $EmpArrSet = array(
            "user"=>array(
                array(
                    "id"         => 1,
                    "email"      => "tianyi@163.com",
                    "password"   => md5("123456")
                    ),
                array(
                    "id"         => 2,
                    "email"      => "'or''@163.com",
                    "password"   => md5("123456")
                    ),
                array(
                    "id"         => 3,
                    "email"      => "abc@163.com",
                    "password"   => md5("'or''")
                    )
            ),
            "article"=>array(
                array(
                    "id"         => 1,
                    "title"      => "test article",
                    "formaltext" => "wojiushi zhengwen",
                    "column"     => 1,
                    "user_id"    => 1
                    )
            ),
            "tag"=>array(
                array("id"=>1, "name"=>"php", "user_id"=>1),
                array("id"=>2, "name"=>"java", "user_id"=>1)
            ),
            "tag_mid"=>array(
                array("id"=>1, "tag_id"=>1, "article_id"=>1),
                array("id"=>2, "tag_id"=>2, "article_id"=>1)
            )
        );
        return new MyApp_DbUnit_ArrayDataSet($EmpArrSet);
    }

    // Login test

    /**
     * @expectedException   InvalidArgumentException
     * @expectedExceptionMessage Miss reuqire key: Password
     */
    public function testLoginLostPassword()
    {
        $data = array("email"=>"tianyi@163.com");
        login($data,PDOStart());
    }

    /**
     * @expectedException   InvalidArgumentException
     * @expectedExceptionMessage Miss reuqire key: Email
     */
    public function testLoginLostEmail()
    {
        $data = array("password"=>"123456");
        login($data,PDOStart());
    }

    /**
     * @expectedException   InvalidArgumentException
     * @expectedExceptionMessage Please fill the email
     */
    public function testLoginEmptyEmail()
    {
        $data = array("email"=>"", "password"=>"");
        login($data, PDOStart());
    }

    /**
     * @expectedException   InvalidArgumentException
     * @expectedExceptionMessage Please fill the password
     */
    public function testLoginEmptyPassword()
    {
        $data = array("email"=>"aaa@163.com", "password"=>"");
        login($data, PDOStart());
    }

    /**
     * @expectedException   InvalidArgumentException
     * @expectedExceptionMessage Illegal Email address
     */
    public function testLoginIllegalRules()
    {
        $data = array("email"=>"tianyi@163", "password"=>"!@#$%^^&*(");
        login($data, PDOStart());
    }

    public function testLoginEmailInject()
    {
        $data = array("email"=>"'or''@163.com", "password"=>"123456");
        $user_id = login($data, PDOStart());
        $this->assertEquals(2, $user_id);
    }

    public function testLoginPasswordInject()
    {
        $data = array("email"=>"abc@163.com", "password"=>"'or''");
        $user_id = login($data, PDOStart());
        $this->assertEquals(3, $user_id);
    }

    public function testLoginIncorrectEmail()
    {
        $data = array("email"=>"aaaaa@163.com", "password"=>"123456");
        $user_id = login($data, PDOStart());
        $this->assertEquals(False, $user_id);
    }

    public function testLoginIncorrectPassword()
    {
        $data = array("email"=>"tianyi@163.com", "password"=>"000000");
        $user_id = login($data, PDOStart());
        $this->assertEquals(False, $user_id);
    }

    public function testLogin()
    {
        $data    = array("email"=>"tianyi@163.com", "password"=>"123456");
        $user_id = login($data,PDOStart());
        $this->assertEquals(1, $user_id);
    }

    // Add article test

    /**
     * @expectedException   InvalidArgumentException
     * @expectedExceptionMessage Missing requied key
     */
    public function testAddArticleUnset()
    {
        $data1   = array();
        $user_id = 1;
        addArticle($data1, PDOStart(), $user_id);
    }


    /**
     * @expectedException   InvalidArgumentException
     * @expectedExceptionMessage Please fill the title
     */
    public function testAddArticleEmptyTitle()
    {
        $data1   = array(
            "title"      => "",
            "formaltext" => "testFormaltext",
            "column"     => 1,
            "tag"        => "java,php"
            );
        $user_id = 1;
        addArticle($data1, PDOStart(), $user_id);
    }

    /**
     * @expectedException   InvalidArgumentException
     * @expectedExceptionMessage Please fill the formaltext
     */
    public function testAddArticleEmptyFormaltext()
    {
        $data2 = array(
            "title"      => "title",
            "formaltext" => "",
            "column"     => 1,
            "tag"        => "java,php"
            );
        $user_id = 1;
        addArticle($data2, PDOStart(), $user_id);
    }

    /**
     * @expectedException   InvalidArgumentException
     * @expectedExceptionMessage Title is over range(64)!
     */
    public function testAddArticleOverRange()
    {
        $data3 = array(
            "title"      =>  "1234567890123456789012345678901234567890123456789012345678901234567890",
            "formaltext" => "testFormaltext",
            "column"     => 1,
            "tag"        => "java,php"
            ); 
        $user_id = 1;
        addArticle($data3, PDOStart(), $user_id);
    }

    /**
     * @expectedException   InvalidArgumentException
     * @expectedExceptionMessage Column is invalid
     */
    public function testAddArticleEmptyColumn()
    {
        $data3 = array(
            "title"      => "testTitle",
            "formaltext" => "testFormaltext",
            "column"     => '',
            "tag"        => "java,php"
            );

        $user_id = 1;
        addArticle($data3, PDOStart(), $user_id);
    }

    public function testAddArticleTitleInjection()
    {
        $data1 = array(
            "title"      => "testTitle'or''", 
            "formaltext" => "testFormaltext", 
            "column"     => 1, 
            "tag"        => ""
        );
        $user_id = 1;
        $result  = arrset();
        $result['article'][1]['title'] = "testTitle'or''";
        addArticle($data1, PDOStart(), $user_id);
        $expectedTable = new MyApp_DbUnit_ArrayDataSet($result);
        $actualTable   = $this->getConnection()->createDataSet(array("article","tag","tag_mid"));

        $this->assertDataSetsEqual($expectedTable,$actualTable);
    }

    public function testAddArticleSQLinjection()
    {
        $data = array(
            "title"      => "testTitle", 
            "formaltext" => "testFormaltext'or''", 
            "column"     => 1, 
            "tag"        => "");
        $user_id = 1;
        $result  = arrset();
        $result['article'][1]['formaltext'] = "testFormaltext'or''";
        addArticle($data, PDOStart(), $user_id);
        $expectedTable = new MyApp_DbUnit_ArrayDataSet($result);
        $actualTable   = $this->getConnection()->createDataSet(array("article","tag","tag_mid"));

        $this->assertDataSetsEqual($expectedTable,$actualTable);
    }

    public function testAddArticleWithTag()
    {
        $data = array(
            "title"      => "testTitle", 
            "formaltext" => "testFormaltext", 
            "column"     => 1, 
            "tag"        => "java"
            );
        $user_id = 1;
        $result  = arrset();
        $result['tag_mid'][2] = array('id'=>3, 'tag_id'=>2, 'article_id'=>2);
        addArticle($data, PDOStart(), $user_id);
        $expectedTable = new MyApp_DbUnit_ArrayDataSet($result);
        $actualTable   = $this->getConnection()->createDataSet(array("article","tag","tag_mid"));

        $this->assertDataSetsEqual($expectedTable,$actualTable);
    }
    public function testAddArticleEmptyTag()
    {
        $data = array(
            "title"      => "testTitle",
            "formaltext" => "testFormaltext",
            "column"     => 1,
            "tag"        => ""
            );
        $user_id = 1;
        $result  = arrset();
        addArticle($data, PDOStart(), $user_id);
        $expectedTable = new MyApp_DbUnit_ArrayDataSet($result);
        $actualTable   = $this->getConnection()->createDataSet(array("article","tag","tag_mid"));

        $this->assertDataSetsEqual($expectedTable,$actualTable);
    }

    // Edit article

    /**
     * @expectedException   InvalidArgumentException
     * @expectedExceptionMessage missing requied key title
     */
    public function testEditArticleNullTitle()
    {
        $data1 = array(
            "id"         => 1,
            "formaltext" => "testFormaltext",
            "column"     => 1,
            "tag"        => "php,java"
            );
        $user_id    = 1;
        $article_id = 1;
        editArticle($data1, PDOStart(), $user_id, $article_id);
    }

    /**
     * @expectedException   InvalidArgumentException
     * @expectedExceptionMessage missing requied key formaltext
     */
    public function testEditArticleNullFormaltext()
    {
        $data  = array(
            "id"     => 1,
            "title"  => "testTitle",
            "column" => 1,
            "tag"    => "php,java"
            );
        $user_id    = 1;
        $article_id = 1;
        editArticle($data, PDOStart(), $user_id, $article_id);
    }

    /**
     * @expectedException   InvalidArgumentException
     * @expectedExceptionMessage missing requied key column
     */

    public function testEditArticleNullColumn()
    {
        $data = array(
            "id"         => 1,
            "title"      => "testTitle",
            "formaltext" => "testFormaltext",
            "tag"        => "php,java,js"
            );
        $user_id    = 1;
        $article_id = 1;
        editArticle($data, PDOStart(), $user_id, $article_id);
    }

    /**
     * @expectedException   InvalidArgumentException
     * @expectedExceptionMessage Please fill the title
     */
    public function testEditArticleEmptyTitle()
    {
        $data = array(
            "id"         => 1,
            "title"      => "",
            "formaltext" => "testFormaltext",
            "column"     => 1,
            "tag"        => "php,java,js"
            );
        $user_id    = 1;
        $article_id = 1;
        $result     = arrset();
        editArticle($data, PDOStart(), $user_id, $article_id);
    }

    /**
     * @expectedException   InvalidArgumentException
     * @expectedExceptionMessage Please fill the formaltext
     */
    public function testEditArticleEmptyFormaltext()
    {
        $data = array(
            "id"         => 1,
            "title"      => "testTitle",
            "formaltext" => "",
            "column"     => 1,
            "tag"        => "php,java,js");
        $user_id    = 1;
        $article_id = 1;
        editArticle($data, PDOStart(), $user_id, $article_id);
    }


    /**
     * @expectedException   InvalidArgumentException
     * @expectedExceptionMessage Column is invalid
     */
    public function testEditArticleIllegalParam()
    {
        $data = array(
            "id"         => 1.1,
            "title"      => "'or''",
            "formaltext" => "'or''",
            "column"     => 1.2,
            "tag"        => "'or''"
            );
        $user_id    = 1;
        $article_id = 1;
        editArticle($data, PDOStart(), $user_id, $article_id);
    }

    /**
     * @expectedException   InvalidArgumentException
     * @expectedExceptionMessage Illegal user operation!
     */
    public function testEditArticleWrongUser()
    {
        $data = array(
            "id"         => 1,
            "title"      => "testTitle",
            "formaltext" => "testFormaltext",
            "column"     => 1,
            "tag"        => "php,java,js"
            );
        $user_id    = 2;
        $article_id = 1;
        editArticle($data, PDOStart(), $user_id, $article_id);
    }

    public function testEditArticle()
    {
        $data1 = array(
            "id"         => 1,
            "title"      => "testTitle",
            "formaltext" => "testFormaltext",
            "column"     => 1,
            "tag"        => "php,java"
            );
        $user_id    = 1;
        $article_id = 1;
        $result     = arrset();
        $result['article'] = array(
                array(
                    "id"         => 1,
                    "title"      => "testTitle",
                    "formaltext" => "testFormaltext",
                    "column"     => 1,
                    "user_id"    => 1,
                    "link"       => null
                    )
            );
        editArticle($data1, PDOStart(), $user_id, $article_id);
        $expectedTable = new MyApp_DbUnit_ArrayDataSet($result);
        $actualTable   = $this->getConnection()->createDataSet(array("article","tag","tag_mid"));

        $this->assertDataSetsEqual($expectedTable,$actualTable);
    }

    public function testEditArticleAddTag()
    {
        $data1 = array(
            "id"         => 1,
            "title"      => "testTitle",
            "formaltext" => "testFormaltext",
            "column"     => 1,
            "tag"        => "php,java,js"
            );
        $user_id    = 1;
        $article_id = 1;
        $result     = arrset();
        $result['article'] = array(
            array(
                "id"         => 1,
                "title"      => "testTitle",
                "formaltext" => "testFormaltext",
                "column"     => 1,
                "user_id"    => 1,
                "link"       => null
                )
            );
        $result['tag'][2]     = array("id"=>3, "name"=>"js", "user_id"=>1);
        $result['tag_mid'][2] = array("id"=>3, "tag_id"=>3, "article_id"=>1);
        editArticle($data1, PDOStart(), $user_id, $article_id);
        $expectedTable = new MyApp_DbUnit_ArrayDataSet($result);
        $actualTable   = $this->getConnection()->createDataSet(array("article","tag","tag_mid"));

        $this->assertDataSetsEqual($expectedTable,$actualTable);
    }

    public function testEditArticleReduceTag()
    {
        $data1 = array(
            "id"         =>1,
            "title"      =>"testTitle",
            "formaltext" =>"testFormaltext",
            "column"     =>1,
            "tag"        =>"php"
            );
        $user_id    = 1;
        $article_id = 1;
        $result     = arrset();
        $result['article'] = array(
            array(
                "id"         => 1,
                "title"      => "testTitle",
                "formaltext" => "testFormaltext",
                "column"     => 1,
                "user_id"    => 1,
                "link"       => null
                )
            );
        $result['tag_mid'] = array(
            array("id"=>1, "tag_id"=>1, "article_id"=>1)
            );
        editArticle($data1, PDOStart(), $user_id, $article_id);
        $expectedTable = new MyApp_DbUnit_ArrayDataSet($result);
        $actualTable   = $this->getConnection()->createDataSet(array("article","tag","tag_mid"));

        $this->assertDataSetsEqual($expectedTable,$actualTable);
    }

    public function testEditArticleEmptyTag()
    {
        $data1 = array(
            "id"         => 1,
            "title"      => "testTitle",
            "formaltext" => "testFormaltext",
            "column"     => 1,
            "tag"        => ""
            );
        $user_id    = 1;
        $article_id = 1;
        $result     = arrset();
        $result['article'] = array(
            array(
                "id"         => 1,
                "title"      => "testTitle",
                "formaltext" => "testFormaltext",
                "column"     => 1,
                "user_id"    => 1,
                "link"       => null
                )
            );
        unset($result['tag_mid']);
        editArticle($data1, PDOStart(), $user_id, $article_id);
        $expectedTable = new MyApp_DbUnit_ArrayDataSet($result);
        $actualTable   = $this->getConnection()->createDataSet(array("article","tag"));

        $this->assertEquals(0,$this->getConnection()->getRowCount('tag_mid'));
        $this->assertDataSetsEqual($expectedTable,$actualTable);
    }

    //Delete article

    /**
     * @expectedException   InvalidArgumentException
     * @expectedExceptionMessage Illegal operation
     */
    public function testDeleteArticleIllegalArticle()
    {
        $user_id    = 1;
        $article_id = "aaa";
        deleteArticle (PDOStart(), $user_id, $article_id);
    }

    /**
     * @expectedException   InvalidArgumentException
     * @expectedExceptionMessage Delete failed: incorrect user
     */
    public function testDeleteArticleWrongUser()
    {
        $user_id    = 2;
        $article_id = 1;
        deleteArticle (PDOStart(), $user_id, $article_id);
    }

    /**
     * @expectedException   InvalidArgumentException
     * @expectedExceptionMessage Delete failed: article don't exist
     */
    public function testDeleteArticleWrongArticle()
    {
        $user_id    = 1;
        $article_id = 2;
        deleteArticle (PDOStart(), $user_id, $article_id);
    }

    public function testDeleteArticle()
    {
        $user_id    = 1;
        $article_id = 1;
        deleteArticle (PDOStart(), $user_id, $article_id);
        $this->assertEquals(0,$this->getConnection()->getRowCount('article'));
        $this->assertEquals(0,$this->getConnection()->getRowCount('tag_mid'));

    }
}