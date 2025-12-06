<?php

try{
	$pdo = new PDO("mysql:dbname=rosset85_bancoverdanna;host=localhost","rosset85_root","XZsawq21$$$");

}catch(PDOException $e){
	echo "ERRO:".$e->getMessage();
	exit;

}


?>