<?php

// error logging
ini_set( 'display_startup_errors', 1 );
ini_set( 'display_errors', 1 );
ini_set( 'error_reporting', E_ALL );
error_reporting( E_ALL );

// vars
ini_set("default_socket_timeout", 15);
ini_set("memory_limit", -1);
$globals['dev']             = true;
$globals['basedir']         = '/home/cloudshield/public_html/dashboard/';

// include main functions
include( $globals['basedir'].'includes/db.php' );
include( $globals['basedir'].'includes/globals.php' );
include( $globals['basedir'].'includes/functions.php' );

// run deamon
while( 1 ) {
	// get all proxies
	$servers = @file_get_contents( 'https://cloudshield.io/dashboard/api/?c=admin_get_servers' );
	$servers = json_decode( $servers, true );

	if( $globals['dev'] == true ) {
        console_output( '========================================' );
        console_output( 'Number of Proxies: "'.count( $servers ).'"' );
        console_output( '========================================' );
        console_output( '' );
    }

    // loop over proxy servers
	foreach( $servers as $server ) {
		if( $globals['dev'] == true ) {
			console_output( 'Proxy: '.$server['ip_address'] );
		}

		// ping check
		$ping_check = exec( "ping -c 1 ".$server['ip_address'] );
		if( $ping_check == 0 ) {
			if( $globals['dev'] == true ) {
				console_output( '-> Server: Online' );
			}

			$postfields["status_server"] 		= 'online';
		} else {
			if( $globals['dev'] == true ) {
				console_output( '-> Server: Offline' );
			}

			$postfields["status_server"] 		= 'offline';
		}

	    // post status to dashboard
	    $postfields["id"] 				= $server['id'];
	    $postfields["ip_address"] 		= $server['ip_address'];

		$ch = curl_init();
		curl_setopt( $ch, CURLOPT_URL, 'https://cloudshield.io/dashboard/api/?c=receive_server_status' );
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

	    if( $globals['dev'] == true ) {
			console_output( '' );
		}
	}

	if( $globals['dev'] == true ) {
		console_output( '========================================' );
		console_output( 'Flood Protection.' );
		console_output( '========================================' );
	}

	sleep( 5 );
}

if( $globals['dev'] == true ) {
    console_output( 'Deamon Ended.' );
}