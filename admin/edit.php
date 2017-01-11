<html>
<head>
	<meta charset="utf-8">
	<link rel="stylesheet" type="text/css" href="../main.css">
</head>
<body>
	<div id="body">
		<!-- header -->
		<?php
            if (!isset($_GET['id'])) {
                echo "<center><h1>Forbidden!</h1></center>";
                exit;
            }

            include("./header.php");
            include("../config/database.php");

            //查找 遍历所有的栏目，并存入数组
            $sql = "SELECT * from types";
            $res = mysqli_query($link,$sql);
            $column = array();
            while ($row = mysqli_fetch_array($res)) {
                $column[] = $row;
            }

            //查找 遍历article
			$id = $_GET['id'];
			$sql = "SELECT * from article where id={$id} and user_id={$user_id}";
			$res = mysqli_query($link,$sql);
			if ($res && mysqli_num_rows($res)>0) {
				$article = mysqli_fetch_assoc($res);
			} else {
                echo "<center>Article don't exists or illegal user !</center>";
                die;
            }

            //Select from tag_mid 
            $sql = "SELECT * from tag_mid where article_id = '$id' ";
            $res = mysqli_query($link,$sql);
            $tag_mid = array();
            while ($row = mysqli_fetch_array($res)) {
                $tag_mid[] = $row;
            }

            if (!empty($tag_mid)){
                foreach ($tag_mid as $value) {
                    $tag_id[] = $value['tag_id'];
                }

                $param = "";
                foreach ($tag_id as $value) {
                    $param .= "'$value',";
                }
                $param = trim($param,",");
                $sql = "SELECT * from tag where id in ($param) ";
                $res = mysqli_query($link,$sql);
                $tag_all = array();
                while ($row = mysqli_fetch_array($res)) {
                    $tag_all[] = $row;
                }
                foreach ($tag_all as $value) {
                    $tag_name[] = $value['name'];
                }
                $tag_name = implode(',',$tag_name);
            } else {
                $tag_name = '';
            }
            

		?>
		<!-- content -->
		<form id="form" action="../config/functions.php?action=edit&id=<?php echo $article['id']; ?>" method="post">
            <input type="hidden" id="article" value="<?php echo $id ?>">
			<select id="column" name="column" style="min-width:200px;background: white;">
				<option value="0">所属栏目</option>
				<?php
					foreach ($column as $v) {
						if ($v['id'] == $article['column']) {
							echo "<option value=".$v['id']." selected>".$v['name']."</option>";
						} else {
							echo "<option value=".$v['id'].">".$v['name']."</option>";
						}
					};
				?>
            </select><br><br>
            <input id="title" type="text" name="title" placeholder="标题" value="<?php echo $article['title'] ?>" style="width:80%"><br><br>
            <textarea id="formaltext" name="formaltext" placeholder="正文" style="width: 80%;height: 500px;overflow-y: scroll;resize: none;"><?php echo $article['formaltext'] ?></textarea><br><br>
            
            <input id='tag_<?php echo $id ?>' name='tag' type='text' placeholder='标签' value="<?php echo $tag_name ?>">Use "," to split tag, it's impossible to use more than 32 char.
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
				alert("Please fill the blank!");
			} else {
				document.getElementById('form').submit();
			}
		}
	}

	//加入链接
	function link(){
		var textarea = document.getElementById('formaltext');
		var value = "<a href='http://请在这里输入url'>请在这里输入文本</a>";
		insertAtCursor(textarea,value);
	}

	//获取textbox的光标，并把新内容加上去
	function insertAtCursor(myField, myValue) {
		if (myField.selectionStart || myField.selectionStart == '0') {
			var startPos = myField.selectionStart;
			var endPos = myField.selectionEnd;
			// save scrollTop before insert 
			var restoreTop = myField.scrollTop;
			myField.value = myField.value.substring(0, startPos) + myValue + myField.value.substring(endPos, myField.value.length);
			if (restoreTop > 0) {
				myField.scrollTop = restoreTop;
			}
			myField.focus();
			myField.selectionStart = startPos + myValue.length;
			myField.selectionEnd = startPos + myValue.length;
		} else {
			myField.value += myValue;
			myField.focus();
		}
	}

    //addTag
    function addtag(id){
        // console.log(id);
        var articleId = $("#article").val();
        $.ajax({
             url:'../config/functions.php?action=addTag',
            type:'post',
            dataType:'text',
            data:{
                       'id':id,
                'articleId':articleId
            },
            success:function(data){
                    $("#tag"+id).attr("onclick","reducetag("+id+")");
                    $("#tag"+id).attr("class","tag1");
            },
            error:function(data){
                console.log(data);
            }
        })
    }

    //reduceTag
    function reducetag(id){
        // console.log(id);
        var articleId = $("#article").val();
        $.ajax({
             url:'../config/functions.php?action=reduceTag',
            type:'post',
            dataType:'text',
            data:{
                       'id':id,
                'articleId':articleId
            },
            success:function(data){
                    $("#tag"+id).attr("onclick","addtag("+id+")");
                    $("#tag"+id).attr("class","tag2");
            },
            error:function(data){
                console.log(data);
            }
        })
    }

    //put a new tag in
    function input(id){
        var text = $("#tag_"+id).val();
        $.ajax({
             url:'../config/functions.php?action=newTag',
            type:'post',
            dataType:'json',
            data:{
                'text':text,
                  'id':id
            },
            success:function(data){
                if (data != false) {
                    $("#tag_"+id).attr("value",text);
                    $("#tagRegion").append("<span id='tag"+data.id+"' class='tag1' onclick='reducetag("+data.id+")' value='"+data.text+"'>"+data.text+"</span>")
                } else {
                    alert('Param error~');
                }
            },
            error:function(data){
                console.log(data);
            }
        })

    }
</script>
</html>
