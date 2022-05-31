<?php

// error logging
ini_set( 'display_startup_errors', 1 );
ini_set( 'display_errors', 1 );
ini_set( 'error_reporting', E_ALL );
error_reporting( E_ALL );

// session start
session_start();

// start timer for page loaded var
$time = microtime();
$time = explode( ' ', $time );
$time = $time[1] + $time[0];
$start = $time;

// vars
ini_set( "default_socket_timeout", 15 );
ini_set( "memory_limit", -1 );
$globals['dev']             = true;
$globals['basedir']         = '/home/cloudshield/public_html/dashboard/';

// include main functions
include( $globals['basedir'].'includes/db.php' );
include( $globals['basedir'].'includes/globals.php' );
include( $globals['basedir'].'includes/functions.php' );

$ip 							= $_SERVER['REMOTE_ADDR'];
$user_agent     				= $_SERVER['HTTP_USER_AGENT'];

$now 							= time();

// vars
$email 							= post( 'email' );
$password 						= post( 'password' );

$postfields["username"] 		= $whmcs['username']; 
$postfields["password"] 		= $whmcs['password'];
$postfields["action"] 			= "validatelogin";
$postfields["email"] 			= $email;
$postfields["password2"] 		= $password;
$postfields["responsetype"] 	= 'json';
$postfields['accesskey']		= $whmcs['accesskey'];

$ch = curl_init();
curl_setopt( $ch, CURLOPT_URL, $whmcs['url'] );
curl_setopt( $ch, CURLOPT_POST, 1 );
curl_setopt( $ch, CURLOPT_TIMEOUT, 30 );
curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, 1 );
curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST, 2 );
curl_setopt( $ch, CURLOPT_POSTFIELDS, http_build_query( $postfields ) );
$data = curl_exec( $ch );
if( curl_error( $ch) ) {
    die('Connection Error: '.curl_errno( $ch ).' - '.curl_error( $ch ) );
}

$results = json_decode( $data, true );

if( $results["result"] == "success" ) {
    // login confirmed
	
	$_SESSION['account']['id'] 			= $results['userid'];
	$_SESSION['account']['email'] 		= $email;
	$user_id 							= $results['userid'];

	// lets get client details
	$postfields["username"] 		= $whmcs['username']; 
	$postfields["password"] 		= $whmcs['password'];
	$postfields["responsetype"] 		= "json";
	$postfields["action"] 				= "getclientsdetails";
	$postfields["clientid"] 			= $user_id;
	
	$ch = curl_init();
	curl_setopt( $ch, CURLOPT_URL, $whmcs['url'] );
	curl_setopt( $ch, CURLOPT_POST, 1 );
	curl_setopt( $ch, CURLOPT_TIMEOUT, 30 );
	curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
	curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, 1 );
	curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST, 2 );
	curl_setopt( $ch, CURLOPT_POSTFIELDS, http_build_query( $postfields ) );
	$client_data = curl_exec( $ch );
	if( curl_error( $ch) ) {
    	die('Connection Error: '.curl_errno( $ch ).' - '.curl_error( $ch ) );
	}
	curl_close( $ch );

	$client_data = json_decode( $client_data, true );

	// lets check their product status for late / non payment
	$postfields["username"] 			= $whmcs['username']; 
	$postfields["password"] 			= $whmcs['password'];
	$postfields["responsetype"] 		= "json";
	$postfields["action"] 				= "getclientsproducts";
	$postfields["clientid"] 			= $user_id;
	
	$ch = curl_init();
	curl_setopt( $ch, CURLOPT_URL, $whmcs['url'] );
	curl_setopt( $ch, CURLOPT_POST, 1 );
	curl_setopt( $ch, CURLOPT_TIMEOUT, 30 );
	curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
	curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, 1 );
	curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST, 2 );
	curl_setopt( $ch, CURLOPT_POSTFIELDS, http_build_query( $postfields ) );
	$data = curl_exec( $ch );
	if( curl_error( $ch) ) {
    	die('Connection Error: '.curl_errno( $ch ).' - '.curl_error( $ch ) );
	}
	curl_close( $ch );

	$data = json_decode( $data, true );

	// check for suspended products
	$valid_product = false;
	foreach( $data['products']['product'] as $product ) {
		if( in_array( $product['pid'], $product_ids ) ) {
		    // product match for this platform

		    if( $product['status'] == 'Suspended'){
				// forward to billing area
				$whmcsurl 			= "https://clients.deltacolo.com/dologin.php";
				$autoauthkey 		= "admin1372";
				$email 				= $email;
				
				$timestamp 			= time(); 
				$goto 				= "clientarea.php";
				
				$hash 				= sha1( $email.$timestamp.$autoauthkey );
				
				$url 				= $whmcsurl."?email=$email&timestamp=$timestamp&hash=$hash&goto=".urlencode( $goto );
				go( $url );
			}

			// product for this platform found
			$valid_product = true;
		}
	}

	// valid product sanity check
	if( $valid_product == false ) {
		go( 'https://clients.deltacolo.com/cart.php?a=add&pid=82' );
	}

	// login allowed
	$_SESSION['logged_in'] 		= true;
	$_SESSION['account']['id'] 	= $user_id;

	// create local user record
	$insert = $conn->exec( "INSERT IGNORE INTO `users` (`user_id`) VALUE ('".$_SESSION['account']['id']."') " );

	// save this login
	$update = $conn->exec( "UPDATE `users` SET `last_login_timestamp` = '".time()."' WHERE `user_id` = '".$_SESSION['account']['id']."' " );
	$update = $conn->exec( "UPDATE `users` SET `last_login_ip` = '".$_SERVER['REMOTE_ADDR']."' WHERE `user_id` = '".$_SESSION['account']['id']."' " );

	// redirect to dashboard.php
	go( $globals['url'].'dashboard/dashboard.php' );
} else {
	// login rejected
	go( $globals['url'].'dashboard/?bad_login_details' );
}

?>