<?php
	include("./header.php");
	include("../config/database.php");

	$column = $dbh->query('SELECT * from types');
?>
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
		#formaltext {
			width: 80%;
			height: 500px;
			overflow-y: scroll;
			resize: none;
		}
		#url {
			width: 40%;
		}
	</style>
</head>
<body>
	<div id="body">
		<!-- header -->
		<?php include("./nav.php"); ?>
		<!-- content -->
		<form id="form" action="../config/functions.php?action=add" method="post">
			<select id="column" name="column">
				<option value="0">所属栏目</option>
				<?php
					foreach($column as $v){
						echo "<option value=".$v['id'].">".htmlspecialchars($v['name'])."</option>";
					};
				?>
			</select>
			<br><br>
			<input id="title" type="text" name="title" placeholder="标题" style="width: 40%;">
			<input type="button" onclick="insertLink()" value="插入链接" />
			<input type="button" onclick="deleteLink()" value="取消插入" />
			<input type="hidden" id="isLink" name="isLink" value="0" />
			<br><br>
			<textarea id="formaltext" name="formaltext" placeholder="正文"></textarea>
			<input id="url" type="text" name="link" placeholder="链接" style="display: none;">
			<br><br>
			<input id="tags" type='text' name='tag' placeholder='添加标签' >请用英文“，”分割，32个字符以内，不超过10个标签
			<br><br>
			<button onclick="checkNull()" type="button">提交</button>
		</form>
	</div>
</body>
<script type="text/javascript">
	//提交时check是否为空
	function checkNull(){
		//获取栏目
		var column = document.getElementById('column');
		var selected = column.selectedIndex;

		//分别获取栏目的value、标题value、正文value
		var value1 = column.options[selected].value;
		var value2 = document.getElementById('title').value
		var value3 = document.getElementById('formaltext').value
		var value4 = document.getElementById('url').value

		//判断并弹窗，成功则submit表单
		if (value1 == 0) {
			alert("请选择一个栏目");
		} else {
			if (value2 == '' || (value3 == '' && value4 == '')) {
				alert("请填写标题和内容");
			} else {
				document.getElementById('form').submit();
			}
		}
	}

	function insertLink() {
		document.getElementById("formaltext").setAttribute("style","display:none");
		document.getElementById("formaltext").value = "";
		document.getElementById('url').setAttribute("style","display:block");
		document.getElementById('url').value = "http://";
		document.getElementById('isLink').value = "1";
	}

	function deleteLink() {
		document.getElementById("formaltext").setAttribute("style","display:block");
		document.getElementById('url').setAttribute("style","display:none");
		document.getElementById('url').value = "";
		document.getElementById('isLink').value = "0";
	}

</script>
</html>
