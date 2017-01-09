<html>
<head>
	<meta charset="utf-8">
	<link rel="stylesheet" type="text/css" href="../main.css">
</head>
<body>
	<div id="body">
		<!-- header -->
		<?php
			include("./header.php");
			include("../config/database.php");

			//查找 遍历所有的栏目，并存入数组
			$sql = "SELECT * from types";
			$res = mysqli_query($link,$sql);
			$column = array();
			while($row = mysqli_fetch_array($res)){
				$column[] = $row;
			};

			//查找 遍历article
			$id = $_GET['id'];
			$sql = "SELECT * from article where id={$id}";
			$res = mysqli_query($link,$sql);
			if($res && mysqli_num_rows($res)>0){
				$article = mysqli_fetch_assoc($res);
			}

            //查找 遍历所有的tag，并存入数组
            $sql = "SELECT * from tag";
            $res = mysqli_query($link,$sql);
            $tag_all = array();
            while($row = mysqli_fetch_array($res)){
                $tag_all[] = $row;
            };

            //查找 遍历article
            $tag_id =  explode(',',$article['tag']);
            foreach ($tag_all as $value) {
                # code...
                foreach ($tag_id as $value_id) {
                    # code...
                    if ($value_id == $value['id']) {
                        $tag_res["{$value['id']}"] = $value['name'];
                        $tag_resid[] = $value['id'];
                    }
                }
            }
		?>
		<!-- content -->
		<form id="form" action="../config/functions.php?action=edit&id=<?php echo $article['id']; ?>" method="post">
            <input type="hidden" id="article" value="<?php echo $id ?>">
			<select id="column" name="column" style="min-width:200px;background: white;">
				<option value="0">所属栏目</option>
				<?php
					foreach($column as $v){
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
            <div id="tagRegion">
            <?php 

                echo "<p>标签:</p>";

                //checked tag
                foreach ($tag_res as $key => $value) {
                    echo "<span id='tag{$key}' class='tag1' onclick='reducetag({$key})' value='{$value}'>{$value}</span> ";
                }

                // echo "<br><br>";

                //unchecked tag
                foreach ($tag_all as $tag_all_value) {
                    echo in_array($tag_all_value['id'],$tag_resid) ? null :
                    "<span id='tag{$tag_all_value['id']}' class='tag2' onclick='addtag({$tag_all_value['id']})' value='{$tag_all_value['name']}'>{$tag_all_value['name']}</span>";
                }
             ?>
            </div><br><br>
            <span class="tag">+ 添加新标签</span>
            <input id='tag_<?php echo $id ?>' class='tag' type='text' placeholder='添加标签'>
            <input type="button" value="添加" onclick='input("<?php echo $id ?>")'>
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
                $("#tag_"+id).attr("value",text);
                $("#tagRegion").append("<span id='tag"+data.id+"' class='tag1' onclick='reducetag("+data.id+")' value='"+data.text+"'>"+data.text+"</span>")
            },
            error:function(data){
                console.log(data);
            }
        })

    }
</script>
</html>
