<?php
include __DIR__.'/../main.php';
require __DIR__.'/database.php';
include __DIR__.'/MyApp_DbUnit_ArrayDataSet.php';
require __DIR__.'/dataset.php';

class mainTest extends PHPUnit_Extensions_Database_TestCase
{   
    public function getConnection()
    {
        $pdo = new PDO('mysql:host=127.0.0.1;dbname=blog_test;charset=utf8', 'root', '123456');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $this->createDefaultDBConnection($pdo, 'blog_test');
    }

    public function getDataSet()
    {
        $ArrSet = array(
            'user'=>array(
                array(
                    'id'         => 1,
                    'email'      => 'tianyi@163.com',
                    'password'   => md5('123456')
                    ),
                array(
                    'id'         => 2,
                    'email'      => "'or''@163.com",
                    'password'   => md5('123456')
                    ),
                array(
                    'id'         => 3,
                    'email'      => 'abc@163.com',
                    'password'   => md5("'or''")
                    )
            ),
            'article'=>array(
                array(
                    'id'         => 1,
                    'title'      => 'test article',
                    'formaltext' => 'wojiushi zhengwen',
                    'column'     => 1,
                    'user_id'    => 1,
                    'link'       => null,
                    'is_link'    => 0
                    )
            ),
            'tag'=>array(
                array('id'=>1, 'name'=>'php', 'user_id'=>1),
                array('id'=>2, 'name'=>'java', 'user_id'=>1)
            ),
            'tag_mid'=>array(
                array('id'=>1, 'tag_id'=>1, 'article_id'=>1),
                array('id'=>2, 'tag_id'=>2, 'article_id'=>1)
            )
        );
        return new MyApp_DbUnit_ArrayDataSet($ArrSet);
    }

    // Login test

    /**
     * @expectedException   InvalidArgumentException
     * @expectedExceptionMessage Miss reuqire key: Email
     */
    public function testLoginMissEmail()
    {
        $data = array('password'=>'123456');
        login($data,PDOStart());
    }

    /**
     * @expectedException   InvalidArgumentException
     * @expectedExceptionMessage Please fill the Email
     */
    public function testLoginEmptyEmail()
    {
        $data = array('email'=>'', 'password'=>'');
        login($data, PDOStart());
    }

    /**
     * @expectedException   InvalidArgumentException
     * @expectedExceptionMessage Illegal Email address
     */
    public function testLoginIllegalEmail()
    {
        $data = array('email'=>'tianyi@163', 'password'=>'!@#$%^^&*(');
        login($data, PDOStart());
    }

    public function testLoginEmailInject()
    {
        $data = array('email'=>"'or''@163.com", 'password'=>'123456');
        $user_id = login($data, PDOStart());
        $this->assertEquals(2, $user_id);
    }

    /**
     * @expectedException   InvalidArgumentException
     * @expectedExceptionMessage Miss reuqire key: Password
     */
    public function testLoginMissPassword()
    {
        $data = array('email'=>'tianyi@163.com');
        login($data,PDOStart());
    }

    /**
     * @expectedException   InvalidArgumentException
     * @expectedExceptionMessage Please fill the password
     */
    public function testLoginEmptyPassword()
    {
        $data = array('email'=>'aaa@163.com', 'password'=>'');
        login($data, PDOStart());
    }

    public function testLoginIncorrectEmail()
    {
        $data = array('email'=>'aaaaa@163.com', 'password'=>'123456');
        $user_id = login($data, PDOStart());
        $this->assertEquals(False, $user_id);
    }

    public function testLoginPasswordInject()
    {
        $data = array('email'=>'abc@163.com', 'password'=>"'or''");
        $user_id = login($data, PDOStart());
        $this->assertEquals(3, $user_id);
    }

    public function testLoginIncorrectPassword()
    {
        $data = array('email'=>'tianyi@163.com', 'password'=>'000000');
        $user_id = login($data, PDOStart());
        $this->assertEquals(False, $user_id);
    }

    public function testLogin()
    {
        $data    = array('email'=>'tianyi@163.com', 'password'=>'123456');
        $user_id = login($data,PDOStart());
        $this->assertEquals(1, $user_id);
    }

    // Add article test

    /**
     * @expectedException   InvalidArgumentException
     * @expectedExceptionMessage Missing requied key title
     */
    public function testAddArticleUnsetTitle()
    {
        $data   = array(
            'formaltext' => 'testFormaltext',
            'column'     => 1,
            'tag'        => 'java,php',
            'link'       => null,
            'is_link'    => 0
            );
        $user_id = 1;
        addArticle($data, PDOStart(), $user_id);
    }

    /**
     * @expectedException   InvalidArgumentException
     * @expectedExceptionMessage Please fill the title
     */
    public function testAddArticleEmptyTitle()
    {
        $data   = array(
            'title'      => '',
            'formaltext' => 'testFormaltext',
            'column'     => 1,
            'tag'        => 'java,php',
            'link'       => null,
            'is_link'    => 0
            );
        $user_id = 1;
        addArticle($data, PDOStart(), $user_id);
    }

    /**
     * @expectedException   InvalidArgumentException
     * @expectedExceptionMessage Title is over range(64)!
     */
    public function testAddArticleOverRangeTitle()
    {
        $data = array(
            'title'      => '1234567890123456789012345678901234567890123456789012345678901234567890',
            'formaltext' => 'testFormaltext',
            'column'     => 1,
            'tag'        => 'java,php',
            'link'       => null,
            'is_link'    => 0
            ); 
        $user_id = 1;
        addArticle($data, PDOStart(), $user_id);
    }

    /**
     * @expectedException   InvalidArgumentException
     * @expectedExceptionMessage Missing requied key formaltext
     */
    public function testAddArticleUnsetFormaltext()
    {
        $data   = array(
            'title'      => 'title',
            'column'     => 1,
            'tag'        => 'java,php',
            'link'       => null,
            'is_link'    => 0
            );
        $user_id = 1;
        addArticle($data, PDOStart(), $user_id);
    }

    /**
     * @expectedException   InvalidArgumentException
     * @expectedExceptionMessage Please fill the formaltext
     */
    public function testAddArticleEmptyFormaltext()
    {
        $data = array(
            'title'      => 'title',
            'formaltext' => '',
            'column'     => 1,
            'tag'        => 'java,php',
            'link'       => 'http://www.baidu.com',
            'is_link'    => 0
            );
        $user_id = 1;
        addArticle($data, PDOStart(), $user_id);
    }

    /**
     * @expectedException   InvalidArgumentException
     * @expectedExceptionMessage Please fill the formaltext or set a link
     */
    public function testAddArticleEmptyLink()
    {
        $data = array(
            'title'      => 'title',
            'formaltext' => '',
            'column'     => 1,
            'tag'        => 'java,php',
            'link'       => '',
            'is_link'    => 0
            );
        $user_id = 1;
        addArticle($data, PDOStart(), $user_id);
    }

    /**
     * @expectedException   InvalidArgumentException
     * @expectedExceptionMessage Please fill the formaltext or set a link
     */
    public function testAddArticleEmptyBothFormaltextAndLink()
    {
        $data = array(
            'title'      => 'title',
            'formaltext' => '',
            'column'     => 1,
            'tag'        => 'java,php',
            'link'       => '',
            'is_link'    => 0
            );
        $user_id = 1;
        addArticle($data, PDOStart(), $user_id);
    }

    /**
     * @expectedException   InvalidArgumentException
     * @expectedExceptionMessage One of argument(formaltext, link) must be empty
     */
    public function testAddArticleSetBothFormaltextAndLink()
    {
        $data = array(
            'title'      => 'title',
            'formaltext' => 'formaltext',
            'column'     => 1,
            'tag'        => 'java,php',
            'link'       => 'http;//www.baidu.com',
            'is_link'    => 0
            );
        $user_id = 1;
        addArticle($data, PDOStart(), $user_id);
    }


    /**
     * @expectedException   InvalidArgumentException
     * @expectedExceptionMessage Missing requied key column
     */
    public function testAddArticleUnsetColumn()
    {
        $data   = array(
            'title'      => 'title',
            'formaltext' => 'testFormaltext',
            'tag'        => 'java,php',
            'link'       => '',
            'is_link'    => 0
            );
        $user_id = 1;
        addArticle($data, PDOStart(), $user_id);
    }

    /**
     * @expectedException   InvalidArgumentException
     * @expectedExceptionMessage Column is invalid
     */
    public function testAddArticleEmptyColumn()
    {
        $data = array(
            'title'      => 'testTitle',
            'formaltext' => 'testFormaltext',
            'column'     => '',
            'tag'        => 'java,php',
            'link'       => '',
            'is_link'    => 0
            );

        $user_id = 1;
        addArticle($data, PDOStart(), $user_id);
    }
    
    /**
     * @expectedException   InvalidArgumentException
     * @expectedExceptionMessage Don't use over 10 tags
     */
    public function testAddArticle10MoreTags()
    {
        $data = array(
            'title'      => 'testTitle',
            'formaltext' => 'testFormaltext',
            'column'     => 1,
            'tag'        => 'java,php,php3,php4,php5,php6,php7,php8,php9,php10,php11',
            'link'       => '',
            'is_link'    => 0
            );

        $user_id = 1;
        addArticle($data, PDOStart(), $user_id);
    }

    /**
     * @expectedException   InvalidArgumentException
     * @expectedExceptionMessage Some of tags is over range(32)!
     */
    public function testAddArticleOverRangeTags()
    {
        $data = array(
            'title'      => 'testTitle',
            'formaltext' => 'testFormaltext',
            'column'     => 1,
            'tag'        => 'java,php,php4567890123456789012345678901234',
            'link'       => '',
            'is_link'    => 0
            );

        $user_id = 1;
        addArticle($data, PDOStart(), $user_id);
    }

    public function testAddArticleTitleInjection()
    {
        $data = array(
            'title'      => "testTitle'or''", 
            'formaltext' => 'testFormaltext', 
            'column'     => 1, 
            'tag'        => '',
            'link'       => '',
            'is_link'    => 0
        );
        $user_id = 1;
        $result  = arrset();
        $result['article'][1]['title'] = "testTitle'or''";
        addArticle($data, PDOStart(), $user_id);
        $expectedTable = new MyApp_DbUnit_ArrayDataSet($result);
        $actualTable   = $this->getConnection()->createDataSet(array('article','tag','tag_mid'));

        $this->assertDataSetsEqual($expectedTable,$actualTable);
    }

    public function testAddArticleSQLinjection()
    {
        $data = array(
            'title'      => 'testTitle', 
            'formaltext' => "testFormaltext'or''", 
            'column'     => 1, 
            'tag'        => '',
            'link'       => '',
            'is_link'    => 0
            );
        $user_id = 1;
        $result  = arrset();
        $result['article'][1]['formaltext'] = "testFormaltext'or''";
        addArticle($data, PDOStart(), $user_id);
        $expectedTable = new MyApp_DbUnit_ArrayDataSet($result);
        $actualTable   = $this->getConnection()->createDataSet(array('article','tag','tag_mid'));

        $this->assertDataSetsEqual($expectedTable,$actualTable);
    }

    public function testAddArticleWithTag()
    {
        $data = array(
            'title'      => 'testTitle', 
            'formaltext' => 'testFormaltext', 
            'column'     => 1, 
            'tag'        => 'java',
            'link'       => '',
            'is_link'    => 0
            );
        $user_id = 1;
        $result  = arrset();
        $result['tag_mid'][2] = array('id'=>3, 'tag_id'=>2, 'article_id'=>2);
        addArticle($data, PDOStart(), $user_id);
        $expectedTable = new MyApp_DbUnit_ArrayDataSet($result);
        $actualTable   = $this->getConnection()->createDataSet(array('article','tag','tag_mid'));

        $this->assertDataSetsEqual($expectedTable,$actualTable);
    }

    // Edit article

    /**
     * @expectedException   InvalidArgumentException
     * @expectedExceptionMessage missing requied key title
     */
    public function testEditArticleNullTitle()
    {
        $data = array(
            'formaltext' => 'testFormaltext',
            'column'     => 1,
            'tag'        => 'php,java',
            'link'       => '',
            'is_link'    => 0
            );
        $user_id    = 1;
        $article_id = 1;
        editArticle($data, PDOStart(), $user_id, $article_id);
    }

    /**
     * @expectedException   InvalidArgumentException
     * @expectedExceptionMessage Missing requied key formaltext
     */
    public function testEditArticleNullFormaltext()
    {
        $data  = array(
            'title'      => 'testTitle',
            'column'     => 1,
            'tag'        => 'php,java',
            'link'       => '',
            'is_link'    => 0
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
            'title'      => 'testTitle',
            'formaltext' => 'testFormaltext',
            'tag'        => 'php,java,js',
            'link'       => null,
            'is_link'    => 0
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
            'title'      => '',
            'formaltext' => 'testFormaltext',
            'column'     => 1,
            'tag'        => 'php,java,js',
            'link'       => null,
            'is_link'    => 0
            );
        $user_id    = 1;
        $article_id = 1;
        $result     = arrset();
        editArticle($data, PDOStart(), $user_id, $article_id);
    }

    /**
     * @expectedException   InvalidArgumentException
     * @expectedExceptionMessage The formaltext can not be empty
     */
    public function testEditArticleEmptyFormaltext()
    {
        $data = array(
            'title'      => 'testTitle',
            'formaltext' => '',
            'column'     => 1,
            'tag'        => 'php,java,js',
            'link'       => null,
            'is_link'    => 0
            );
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
            'title'      => "'or''",
            'formaltext' => "'or''",
            'column'     => 1.2,
            'tag'        => "'or''",
            'link'       => null,
            'is_link'    => 0
            );
        $user_id    = 1;
        $article_id = 1;
        editArticle($data, PDOStart(), $user_id, $article_id);
    }

    /**
     * @expectedException   InvalidArgumentException
     * @expectedExceptionMessage It's not your article
     */
    public function testEditArticleWrongUser()
    {
        $data = array(
            'title'      => 'testTitle',
            'formaltext' => 'testFormaltext',
            'column'     => 1,
            'tag'        => 'php,java,js',
            'link'       => null,
            'is_link'    => 0
            );
        $user_id    = 2;
        $article_id = 1;
        editArticle($data, PDOStart(), $user_id, $article_id);
    }

    public function testEditArticle()
    {
        $data = array(
            'title'      => 'testTitle',
            'formaltext' => 'testFormaltext',
            'column'     => 1,
            'tag'        => 'php,java',
            'link'       => null,
            'is_link'    => 0
            );
        $user_id    = 1;
        $article_id = 1;
        $result     = arrset();
        $result['article'] = array(
                array(
                    'id'         => 1,
                    'title'      => 'testTitle',
                    'formaltext' => 'testFormaltext',
                    'column'     => 1,
                    'user_id'    => 1,
                    'link'       => null,
                    'is_link'    => 0
                    )
            );
        editArticle($data, PDOStart(), $user_id, $article_id);
        $expectedTable = new MyApp_DbUnit_ArrayDataSet($result);
        $actualTable   = $this->getConnection()->createDataSet(array('article','tag','tag_mid'));

        $this->assertDataSetsEqual($expectedTable,$actualTable);
    }

    public function testEditArticleAddTag()
    {
        $data = array(
            'title'      => 'testTitle',
            'formaltext' => 'testFormaltext',
            'column'     => 1,
            'tag'        => 'php,java,js',
            'link'       => null,
            'is_link'    => 0
            );
        $user_id    = 1;
        $article_id = 1;
        $result     = arrset();
        $result['article'] = array(
            array(
                'id'         => 1,
                'title'      => 'testTitle',
                'formaltext' => 'testFormaltext',
                'column'     => 1,
                'user_id'    => 1,
                'link'       => null,
                'is_link'    => 0
                )
            );
        $result['tag'][2]     = array('id'=>3, 'name'=>'js', 'user_id'=>1);
        $result['tag_mid'][2] = array('id'=>3, 'tag_id'=>3, 'article_id'=>1);
        editArticle($data, PDOStart(), $user_id, $article_id);
        $expectedTable = new MyApp_DbUnit_ArrayDataSet($result);
        $actualTable   = $this->getConnection()->createDataSet(array('article','tag','tag_mid'));

        $this->assertDataSetsEqual($expectedTable,$actualTable);
    }

    public function testEditArticleReduceTag()
    {
        $data = array(
            'title'      =>'testTitle',
            'formaltext' =>'testFormaltext',
            'column'     =>1,
            'tag'        =>'php',
            'link'       => null,
            'is_link'    => 0
            );
        $user_id    = 1;
        $article_id = 1;
        $result     = arrset();
        $result['article'] = array(
            array(
                'id'         => 1,
                'title'      => 'testTitle',
                'formaltext' => 'testFormaltext',
                'column'     => 1,
                'user_id'    => 1,
                'link'       => null,
                'is_link'    => 0
                )
            );
        $result['tag_mid'] = array(
            array('id'=>1, 'tag_id'=>1, 'article_id'=>1)
            );
        editArticle($data, PDOStart(), $user_id, $article_id);
        $expectedTable = new MyApp_DbUnit_ArrayDataSet($result);
        $actualTable   = $this->getConnection()->createDataSet(array('article','tag','tag_mid'));

        $this->assertDataSetsEqual($expectedTable,$actualTable);
    }

    public function testEditArticleEmptyTag()
    {
        $data = array(
            'title'      => 'testTitle',
            'formaltext' => 'testFormaltext',
            'column'     => 1,
            'tag'        => '',
            'link'       => null,
            'is_link'    => 0
            );
        $user_id    = 1;
        $article_id = 1;
        $result     = arrset();
        $result['article'] = array(
            array(
                'id'         => 1,
                'title'      => 'testTitle',
                'formaltext' => 'testFormaltext',
                'column'     => 1,
                'user_id'    => 1,
                'link'       => null,
                'is_link'    => 0
                )
            );
        unset($result['tag_mid']);
        editArticle($data, PDOStart(), $user_id, $article_id);
        $expectedTable = new MyApp_DbUnit_ArrayDataSet($result);
        $actualTable   = $this->getConnection()->createDataSet(array('article','tag'));

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
        $article_id = 'aaa';
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