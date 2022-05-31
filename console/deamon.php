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


// get servers
$query          = $conn->query( "
	SELECT * 
	FROM `servers` 
	WHERE `status` = 'installed' 
" );
$servers           = $query->fetchAll( PDO::FETCH_ASSOC );

$servers = stripslashes_deep( $servers );

if( $globals['dev'] == true ) {
    console_output( '========================================' );
    console_output( 'Number of Servers: "'.count( $servers ).'"' );
    console_output( '========================================' );
    console_output( '' );
}

// loop over servers
foreach( $servers as $server ) {
	if( $globals['dev'] == true ) {
		console_output( 'Server: '.$server['ip_address'] );
	}

	// has the server checked in recently
	$last_seen = ( time() - $server['updated'] );
	if( $last_seen > 120 ) {
		// server missed a checkin
		if( $globals['dev'] == true ) {
			console_output( ' -> Missed a checkin' );
		}

		$update = $conn->exec( "UPDATE `servers` SET `status_server` = 'offline' WHERE `id` = '".$server['id']."' " );
	}    
}

if( $globals['dev'] == true ) {
	console_output( '========================================' );
	console_output( 'Finished.' );
	console_output( '========================================' );
}

