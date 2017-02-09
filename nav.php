<?php 
    //查询 遍历所有导航栏
    $sql = "SELECT * from types;";
    $sth = $dbh->query($sql);
    $types = $sth->fetchAll();
 ?>
<a id="logo" href="./index.php">Ourblog</a>
<a class="nav" href="./index.php">HomePage</a>
 <?php
    foreach ($types as $value) {
        echo "<a class='nav' href='./index.php?type={$value['id']}'>{$value['name']}</a>";
    }
?>
<hr style="margin-bottom: 50px;">