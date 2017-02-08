<?php
    include("./header.php");
    include("../config/database.php");

    if (!isset($_GET['id'])) {
        echo "<center><h1>Forbidden!</h1></center>";
        exit;
    }
    $id = $_GET['id'];
    $id = filter_var($id,FILTER_VALIDATE_INT,array('options' => array('min_range' => 1)));
    if (!$id) {
        echo "<script>alert('非法的文章id');window.location.href='../admin/index.php'</script>";
        exit;
    } 

    $article = $dbh->query("SELECT * from article where id = $id and user_id = $user_id")->fetch();
    if ($article == null) {
        echo "<center>Article don't exists or illegal user !</center>";
        exit;
    }

    $column  = $dbh->query('SELECT * from types')->fetchAll();
    //Select from tag_mid 
    $tag_mid = $dbh->query("SELECT tag_id from tag_mid where article_id = $id")->fetchAll();

    if (!empty($tag_mid)){
        foreach ($tag_mid as $value) {
            $tag_id[] = $value['tag_id'];
        }
        $param = implode(',', $tag_id);
        
        $tag_all = $dbh->query("SELECT name from tag where id in ($param)")->fetchAll();

        foreach ($tag_all as $value) {
            $tag_name[] = $value['name'];
        }
        $tag_name = implode(',',$tag_name);
    } else {
        $tag_name = '';
    }
?>
<html>
<head>
    <meta charset="utf-8">
    <link rel="stylesheet" type="text/css" href="../main.css">
</head>
<body>
    <div id="body">
        <!-- header -->
        <?php include("./nav.php"); ?>
        <!-- content -->
        <form id="form" action="../config/functions.php?action=edit&id=<?php echo $article['id']; ?>" method="post">
            <input type="hidden" id="article" value="<?php echo $id ?>">
			<select id="column" name="column" style="min-width:200px;background: white;">
				<option value="0">所属栏目</option>
				<?php
					foreach ($column as $v) {
						if ($v['id'] == $article['column']) {
							echo "<option value=".$v['id']." selected>".htmlspecialchars($v['name'])."</option>";
						} else {
							echo "<option value=".$v['id'].">".htmlspecialchars($v['name'])."</option>";
						}
					};
				?>
            </select><br><br>
            <input id="title" type="text" name="title" placeholder="标题" value="<?php echo htmlspecialchars($article['title']) ?>" style="width:80%"><br><br>
            <?php
                if ($article['is_link'] == 0) {
                    echo "<textarea id='formaltext' name='formaltext' placeholder='正文' style='width: 80%;height: 500px;overflow-y: scroll;resize: none;'>";
                    echo htmlspecialchars($article['formaltext']);
                    echo '</textarea><br><br>';
                } else {
                    echo "<input id='formaltext' type='text' name='link' placeholder='链接' value=".htmlspecialchars($article['link'])." style='width: 40%;'><br><br>";
                }
            ?>
            <input id='tag_<?php echo $id ?>' name='tag' type='text' placeholder='标签' value="<?php echo htmlspecialchars($tag_name) ?>">请用英文“，”分割，32个字符以内，不超过10个标签
            <!-- <input type="button" value="添加" onclick='input("<?php echo $id ?>")'> -->
            <br><br>
            <button onclick="check()" type="button">提交</button>
            <input type="button" onclick="link()" value="插入文本" />
		</form>
	</div>
</body>
<script type="text/javascript" src="../config/jquery-1.8.3.min.js"></script>
<script type="text/javascript">
    //提交时check是否为空
    function check(){
        //获取栏目
        var column = document.getElementById('column');
        var selected = column.selectedIndex;

        //分别获取栏目的value、标题value、正文value
        var value1 = column.options[selected].value;
        var value2 = document.getElementById('title').value
        var value3 = document.getElementById('formaltext').value

        //判断并弹窗，成功则submit表单
        if (value1 == 0) {
            alert("Please choose a column!");
        } else {
            if (value2 == '' || value3 == '') {
                alert('Please fill the blank!');
            } else {
                document.getElementById('form').submit();
            }
        }
    }

</script>
</html>
