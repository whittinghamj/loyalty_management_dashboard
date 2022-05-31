<?php

// error logging
ini_set( 'display_startup_errors', 1 );
ini_set( 'display_errors', 1 );
ini_set( 'error_reporting', E_ALL );
error_reporting( E_ALL );

// vars
ini_set("default_socket_timeout", 15);
ini_set("memory_limit", -1);
$globals['basedir']         = '/home/cloudshield/public_html/dashboard/';

// include main functions
include( $globals['basedir'].'includes/db.php' );
include( $globals['basedir'].'includes/globals.php' );
include( $globals['basedir'].'includes/functions.php' );

// set service nice
// cli_set_process_title( 'IPTVShield Deamon - Remote Installer' );

// run deamon
while( 1 ) {
	// get queue job
	$job = @file_get_contents( 'https://cloudshield.io/dashboard/api/?c=admin_get_cloudflare_queue' );
	$job = json_decode( $job, true );

	if( isset( $job['id'] ) ) {
		if( $globals['dev'] == true ) {
	        console_output( '========================================' );
	        console_output( 'Cluster Name: '.$job['cluster']['name'] );
	        console_output( 'Server Hostname: '.$job['server']['hostname'].'.'.$job['cluster']['iptv_main_server_domain'] );
	        console_output( 'Server IP: '.$job['server']['ip_address'] );
	        console_output( '' );
	        console_output( 'Domain ID: '.$job['cluster']['cloudflare_zone_id'] );
	        console_output( 'Host ID: '.$job['server']['cloudflare_host_id'] );
	        console_output( '========================================' );
	        console_output( '' );
	    }

    	if( $job['proxied'] == 'yes' ) {
    		$proxied = 'true';
    	} else {
    		$proxied = 'false';
    	}

    	// requested and current state dont match, update cloudflare
    	$update_dns_record = shell_exec( 'curl -s -X PUT "https://api.cloudflare.com/client/v4/zones/'.$job['cluster']['cloudflare_zone_id'].'/dns_records/'.$job['server']['cloudflare_host_id'].'" \
 					-H "Content-Type: application/json" \
 					-H "X-Auth-Email: '.$job['cluster']['cloudflare_email'].'" \
					-H "X-Auth-Key: '.$job['cluster']['cloudflare_api_key'].'" \
 					--data \'{"type":"A","name":"'.$job['server']['hostname'].'","content":"'.$job['server']['ip_address'].'","ttl":120,"proxied":'.$proxied.'}\'
				' );

    	if( $globals['dev'] == true ) {
    		$update_dns_record = json_decode( $update_dns_record, true );
    		print_r( $update_dns_record );
    	}

	    // complete job
    	$result = @file_get_contents( 'https://cloudshield.io/dashboard/api/?c=receive_get_cloudflare_queue_update&id='.$job['id'] );
	} else {
		if( $globals['dev'] == true ) {
			console_output( '========================================' );
	        console_output( 'No Jobs Found' );
	        console_output( '========================================' );
	        die();
		}
	}

	if( $globals['dev'] == true ) {
		console_output( '' );
		console_output( '========================================' );
		console_output( 'Job Finished' );
		console_output( '========================================' );
	}
}

if( $globals['dev'] == true ) {
    console_output( 'Deamon Ended' );
}