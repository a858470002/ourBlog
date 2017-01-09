<?php
	//define database config
	@define(db_host,"localhost");
	@define(db_user,"root");
	@define(db_pass,"123456");
	@define(db_name,"blog");
	@define(db_charset,"utf8");

	$link = mysqli_connect(db_host,db_user,db_pass) or die("mysql_connect failed!");

	mysqli_select_db($link,db_name);

	mysqli_set_charset($link,db_charset);
