<html>
<head>
	<meta charset="utf-8">
	<link rel="stylesheet" type="text/css" href="../main.css">
	<style type="text/css">
		#column {
			min-width:200px;
			background: white;
		}
		#link {
			width: 50px;
			height: 30px;
			border: 1px;
			background: grey;
		}
	</style>
</head>
<body>
	<div id="body">
		<!-- header -->
		<?php
			include("./header.php");
			include("../config/database.php");

			//查找 遍历所有的栏目，并存入数组
			$sql = "SELECT * from `types`";
			$res = mysqli_query($link,$sql);
			$column = array();
			while($row = mysqli_fetch_array($res)){
				$column[] = $row;
			};
		?>
		<!-- content -->
		<form id="form" action="../config/functions.php?action=add" method="post">
			<input type="text" name="user_id" value="<?php echo $user_id ?>">
			<select id="column" name="column">
				<option value="0">所属栏目</option>
				<?php
					foreach($column as $v){
						echo "<option value=".$v['id'].">".$v['name']."</option>";
					};
				?>
			</select>
			<br><br>
			<input id="title" type="text" name="title" placeholder="标题" style="width: 40%;">
			<br><br>
			<textarea id="formaltext" name="formaltext" placeholder="正文" style="width: 80%;height: 500px;overflow-y: scroll;resize: none;"></textarea>
			<br><br>
			<button onclick="check()" type="button">提交</button>
			<input type="button" onclick="link()" value="插入链接" />
		</form>
	</div>
</body>
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
				alert("请选择一个栏目");
			} else {
				if (value2 == '' || value3 == '') {
					alert("请填写标题和内容");
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
	</script>
</html>
