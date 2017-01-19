<?php

//PDO Connection
function PDOStart() {
    $dsn      = 'mysql:dbname=blog_test;host=127.0.0.1;charset=utf8';
    $user     = 'root';
    $password = '123456';

    try {
        $dbh = new PDO($dsn,$user,$password);
        $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (PDOException $e) {
        echo 'Connect failed: ' . $e->getMessage();
    }
    return $dbh;
}
$dbh = PDOStart();
