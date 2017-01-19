<?php
function arrset() {
    $ArrSet = array(
            "article"=>array(
                array(
                    "id"         => 1,
                    "title"      => "test article",
                    "formaltext" => "wojiushi zhengwen",
                    "column"     => 1,
                    "user_id"    => 1,
                    "link"       => null
                    ),
                array(
                    "id"         => 2,
                    "title"      => "testTitle", 
                    "formaltext" => "testFormaltext", 
                    "column"     => 1,
                    "user_id"    => 1,
                    "link"       => null
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
    return $ArrSet;
}
function arrset2() {
    $ArrSet = array(
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
                    "user_id"    => 1,
                    "link"       => null
                    ),
                array(
                    "id"         => 2,
                    "title"      => "testTitle", 
                    "formaltext" => "testFormaltext", 
                    "column"     => 1,
                    "user_id"    => 1,
                    "link"       => null
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
    return $ArrSet;
}

