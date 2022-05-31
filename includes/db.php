<?php

$host			= '173.248.140.254';
$db 			= 'ufx_loyalty_dashboard';
$username 		= 'whittinghamj';
$password 		= 'admin1372Dextor!#&@Mimi!#&@';

$dsn			= "mysql:host=$host;dbname=$db";

try{
	$conn = new PDO( $dsn, $username, $password );
	$conn->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
}catch( PDOException $e ) {
	echo $e->getMessage();
}