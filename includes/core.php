<?php

// error logging
ini_set( 'display_startup_errors', 1 );
ini_set( 'display_errors', 1 );
ini_set( 'error_reporting', E_ALL );
error_reporting( E_ALL );

// env vars
ini_set("default_socket_timeout", 15);
ini_set("memory_limit", -1);

// cli check
function core_is_cli() {
    if( defined( 'STDIN' ) ) {
        return 'cli';
    }

    if( php_sapi_name() === 'cli' ) {
        return 'cli';
    }

    if( array_key_exists( 'SHELL' , $_ENV) ) {
        return 'cli';
    }

    if( empty( $_SERVER['REMOTE_ADDR'] ) and !isset( $_SERVER['HTTP_USER_AGENT'] ) and count( $_SERVER['argv'] ) > 0 ) {
        return 'cli';
    } 

    if( !array_key_exists( 'REQUEST_METHOD' , $_SERVER) ) {
        return 'cli';
    }

    return 'web';
}

// session start
if( core_is_cli() != 'cli' ) {
    // server should keep session data for AT LEAST 1 day
    ini_set( 'session.gc_maxlifetime', 86400 );

    // each client should remember their session id for EXACTLY 1 day
    session_set_cookie_params( 86400 );
	
    // start php session
    session_start();
}

// start timer for page loaded var
$time = microtime();
$time = explode( ' ', $time );
$time = $time[1] + $time[0];
$start = $time;

// load database
$database['hostname']			= '173.248.140.254';
$database['database'] 			= 'ufx_loyalty_dashboard';
$database['username'] 			= 'whittinghamj';
$database['password'] 			= 'admin1372Dextor!#&@Mimi!#&@';

$dsn			= "mysql:host=".$database['hostname'].";dbname=".$database['database'];

try{
	$conn = new PDO( $dsn, $database['username'], $database['password'] );
	$conn->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
}catch( PDOException $e ) {
	echo $e->getMessage();
}

// site db vars
$query = $conn->query( "SELECT `name`,`value` FROM `system_settings` " );
$globals_temp = $query->fetchAll( PDO::FETCH_ASSOC );

foreach( $globals_temp as $bits ) {
	$globals[$bits['name']] = $bits['value'];
}

// get clients ip address
if( isset( $_SERVER["HTTP_CF_CONNECTING_IP"] ) ) {
    $globals['client_ip'] = $_SERVER["HTTP_CF_CONNECTING_IP"];
} else {
    $globals['client_ip'] = $_SERVER['REMOTE_ADDR'];
}

// site static vars
$globals['copyright']			= 'Written by OTTO1372.';