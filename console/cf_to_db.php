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

$data 				= array();
// get cluster
$query          	= $conn->query( "SELECT * FROM `clusters` WHERE `cloudflare_zone_id` != '' " );
$clusters    		= $query->fetchAll( PDO::FETCH_ASSOC );

// loop clusters
foreach( $clusters as $cluster ) {
	$data['cluster']	= $cluster;

	// get servers
	$query          	= $conn->query( "SELECT * FROM `servers` WHERE `cluster_id` = '".$data['cluster']['id']."' " );
	$data['servers']    = $query->fetchAll( PDO::FETCH_ASSOC );

	// get all dns records for this zone
	$dns_records = shell_exec( 'curl -X GET "https://api.cloudflare.com/client/v4/zones/'.$data['cluster']['cloudflare_zone_id'].'/dns_records?per_page=200" \
	    				-H "Content-Type: application/json" \
	    				-H "X-Auth-Email: '.$data['cluster']['cloudflare_email'].'" \
	    				-H "X-Auth-Key: '.$data['cluster']['cloudflare_api_key'].'"
	    				' );
	$dns_records = json_decode( $dns_records, true );

	// loop over dns_records
	foreach( $dns_records['result'] as $dns_record ) {
		// loop over servers
		foreach( $data['servers'] as $server ) {
			if( $dns_record['content'] == $server['ip_address'] ) {
				// update the database
				$update = $conn->exec( "UPDATE `servers` SET `cloudflare_host_id` = '".$dns_record['id']."' WHERE `id` = '".$server['id']."' " );
				if( $dns_record['proxied'] == '' ) {
					$update = $conn->exec( "UPDATE `servers` SET `cloudflare_proxied` = 'no' WHERE `id` = '".$server['id']."' " );
				} else {
					$update = $conn->exec( "UPDATE `servers` SET `cloudflare_proxied` = 'yes' WHERE `id` = '".$server['id']."' " );
				}

				echo $dns_record['content'].' = '.$dns_record['id']."\n";
				break;
			}
		}
	}
}