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
			
			//查询 遍历导航栏
			$sql = "SELECT * from article";
			$article = $dbh->query($sql)->fetchAll();
			
		?>
		<!-- content -->
		<table width="90%" style="margin: 0 auto;">
			<?php 
				foreach ($article as $value) {
					echo "	<tr>
								<td width='80%'><h4>".$value['title']."</h4></td>
								<td>
									<a href='./edit.php?id=".$value['id']."'>编辑</a>
									<a href='../config/functions.php?action=delete&id=".$value['id']."'>删除</a>
								</td>
							</tr>";
				}
			 ?>
		</table>
	</div>
</body>
<script type="text/javascript" src="../config/jquery-1.8.3.min.js"></script>
<script type="text/javascript">
	//Useless method
	function input(id){
		var text = $("#tag_"+id).val();
		$.ajax({
			 url:'../config/functions.php?action=tag',
			type:'post',
			dataType:'text',
			data:{
				'text':text,
				  'id':id
			},
			success:function(data){
				if (data == 'success') {
					$("#tag_"+id).attr("value",text);
					alert('success');
				}
			},
			error:function(data){
				console.log(data);
			}
		})
	}
</script>
</html>
