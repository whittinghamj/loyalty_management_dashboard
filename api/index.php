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
ini_set("default_socket_timeout", 15);
ini_set("memory_limit", -1);
$globals['dev']             = true;
$globals['basedir']         = '/home/cloudshield/public_html/dashboard/';

// include main functions
include( $globals['basedir'].'includes/db.php' );
include( $globals['basedir'].'includes/globals.php' );
include( $globals['basedir'].'includes/functions.php' );

// geoip database
require( $globals['basedir'].'assets/geoip/MaxMind-DB-Reader-php/autoload.php' );
use MaxMind\Db\Reader;
$geoip = new Reader( $globals['basedir'].'assets/geoip/GeoLite2-City.mmdb' );
$geoisp = new Reader( $globals['basedir'].'assets/geoip/GeoIP2-ISP.mmdb' );


header( "Content-Type:application/json; charset=utf-8" );

$c = get( 'c' );
switch ($c) {

	// admin_get_servers
	case "admin_get_servers":
		admin_get_servers();
		break;

	// admin_get_deployment_queue
	case "admin_get_deployment_queue":
		admin_get_deployment_queue();
		break;

	// admin_get_reboot_queue
	case "admin_get_reboot_queue":
		admin_get_reboot_queue();
		break;

	// admin_get_controllers_rebuild
	case "admin_get_controllers_rebuild":
		admin_get_controllers_rebuild();
		break;

	// admin_get_domain_names
	case "admin_get_domain_names":
		admin_get_domain_names();
		break;

	// receive_server_status
	case "receive_server_status":
		receive_server_status();
		break;

	// receive_server_stats
	case "receive_server_stats":
		receive_server_stats();
		break;

	// receive_deployment_status
	case "receive_deployment_status":
		receive_deployment_status();
		break;

	// receive_reboot_status
	case "receive_reboot_status":
		receive_reboot_status();
		break;

	// receive_domain_name_update
	case "receive_domain_name_update":
		receive_domain_name_update();
		break;

	// home
	default:
		home();
		break;
}
       
function home() {
	global $site;
	$data['status']				= 'success';
	$data['message']			= 'you have successfully connected to the API. now try a few other commands to pull / push additional data.';
	
	json_output( $data );
}

function admin_get_servers() {
	global $conn, $globals, $geoip, $geoisp;

	// get servers
	$query          = $conn->query( "
		SELECT `id`,`cluster_id`,`ip_address` 
		FROM `servers` 
		WHERE `status` = 'installed' 
		AND `type` = 'proxy' 
		AND `status_server` = 'online' 
	" );
    $data           = $query->fetchAll( PDO::FETCH_ASSOC );

    $data = stripslashes_deep( $data );
	
	json_output( $data );
}

function admin_get_deployment_queue() {
	global $conn, $globals, $geoip, $geoisp;

	// get deployment job
	$query          = $conn->query( "
		SELECT * 
		FROM `server_deployment_queue` 
		WHERE `status` = 'pending' 
		LIMIT 1 
	" );
    $data           = $query->fetch( PDO::FETCH_ASSOC );

    if( isset( $data['id'] ) ) {
	    // get cluster
	    $query          = $conn->query( "
			SELECT * 
			FROM `clusters` 
			WHERE `id` = '".$data['cluster_id']."' 
		" );
	    $data['cluster']           = $query->fetch( PDO::FETCH_ASSOC );

	    // get server
	    $query          = $conn->query( "
			SELECT * 
			FROM `servers` 
			WHERE `id` = '".$data['server_id']."' 
		" );
	    $data['server']           = $query->fetch( PDO::FETCH_ASSOC );

	    $data = stripslashes_deep( $data );
	} else {
		$data = array();
	}
	
	json_output( $data );
}

function admin_get_reboot_queue() {
	global $conn, $globals, $geoip, $geoisp;

	// get deployment job
	$query          = $conn->query( "
		SELECT * 
		FROM `server_reboot_queue` 
		WHERE `status` = 'pending' 
		LIMIT 1 
	" );
    $data           = $query->fetch( PDO::FETCH_ASSOC );

    if( isset( $data['id'] ) ) {
	    // get cluster
	    $query          = $conn->query( "
			SELECT * 
			FROM `clusters` 
			WHERE `id` = '".$data['cluster_id']."' 
		" );
	    $data['cluster']           = $query->fetch( PDO::FETCH_ASSOC );

	    // get server
	    $query          = $conn->query( "
			SELECT * 
			FROM `servers` 
			WHERE `id` = '".$data['server_id']."' 
		" );
	    $data['server']           = $query->fetch( PDO::FETCH_ASSOC );

	    $data = stripslashes_deep( $data );
	} else {
		$data = array();
	}
	
	json_output( $data );
}

function admin_get_domain_names() {
	global $conn, $globals, $geoip, $geoisp;

	// get job
	$query          = $conn->query( "
		SELECT * 
		FROM `domain_names` 
	" );
    $domains           = $query->fetchAll( PDO::FETCH_ASSOC );

    $count = 0;
    foreach( $domains as $domain ) {
    	$data[$count] 						= $domain;
    	$data[$count]['dns_records'] 		= get_dns_records_admin( $domain['powerdns_id'] );

    	$count++;
    }
	
	json_output( $data );
}

function receive_server_status() {
	global $conn, $globals, $geoip, $geoisp;

	// map fields
	$id					= post( 'id' );
	$ip_address 		= post( 'ip_address' );
	$status_server 		= post( 'status_server' );

	// save data
	$update = $conn->exec( "UPDATE `servers` SET `updated` = '".time()."' WHERE `id` = '".$id."' AND `ip_address` = '".$ip_address."' " );
	$update = $conn->exec( "UPDATE `servers` SET `status_server` = '".$status_server."' WHERE `id` = '".$id."' AND `ip_address` = '".$ip_address."' " );

	$data['status'] = 'success';

	json_output( $data );
}

function receive_server_stats() {
	global $conn, $globals, $geoip, $geoisp;

	// map fields
	$ip_address 						= post( 'ip_address' );
	$stats['uptime'] 					= post( 'uptime' );
	$stats['cpu_cores'] 				= post( 'cpu_cores' );
	$stats['cpu_speed'] 				= post( 'cpu_speed' );
	$stats['cpu_usage'] 				= post( 'cpu_usage' );
	$stats['ram_total'] 				= post( 'ram_total' );
	$stats['ram_usage'] 				= post( 'ram_usage' );
	$stats['bandwidth_download'] 		= ( empty( post( 'bandwidth_download' ) ) ? 0 : post( 'bandwidth_download' ) );
	$stats['bandwidth_upload'] 			= ( empty( post( 'bandwidth_upload' ) ) ? 0 : post( 'bandwidth_upload' ) );
	$stats['nginx_status'] 				= post( 'nginx_status' );
	$stats['nginx_active_connections'] 	= ( empty( post( 'nginx_active_connections' ) ) ? 0 : ( post( 'nginx_active_connections' ) - 1 ) );

	$json_stats = json_encode( $stats );

	// save data
	$update = $conn->exec( "UPDATE `servers` SET `updated` = '".time()."' WHERE `ip_address` = '".$ip_address."' " );
	$update = $conn->exec( "UPDATE `servers` SET `status` = 'installed' WHERE `ip_address` = '".$ip_address."' " );
	$update = $conn->exec( "UPDATE `servers` SET `status_server` = 'online' WHERE `ip_address` = '".$ip_address."' " );
	$update = $conn->exec( "UPDATE `servers` SET `status_proxy` = '".$stats['nginx_status']."' WHERE `ip_address` = '".$ip_address."' " );
	$update = $conn->exec( "UPDATE `servers` SET `stats` = '".$json_stats."' WHERE `ip_address` = '".$ip_address."' " );

	$data['status'] = 'success';

	json_output( $data );
}

function receive_deployment_status() {
	global $conn, $globals, $geoip, $geoisp;

	// map fields
	$id					= post( 'id' );
	$status 			= post( 'status' );
	$message 			= post( 'message' );

	// save data
	$update = $conn->exec( "UPDATE `server_deployment_queue` SET `status` = '".$status."' WHERE `id` = '".$id."' " );
	$update = $conn->exec( "UPDATE `server_deployment_queue` SET `message` = '".$message."' WHERE `id` = '".$id."' " );

	// get job
	$query = $conn->query( "
        SELECT * 
        FROM `server_deployment_queue` 
        WHERE `id` = '".$id."' 
    " );
    $job           = $query->fetch( PDO::FETCH_ASSOC );
    $job = stripslashes_deep( $job );

	// update server
	$update = $conn->exec( "UPDATE `servers` SET `status` = '".$status."' WHERE `id` = '".$job['server_id']."' " );
	// if( $status == 'installed' ) {
	// 	$update = $conn->exec( "UPDATE `servers` SET `ssh_port` = '33077' WHERE `id` = '".$job['server_id']."' " );
	// }
	
	$data['status'] = 'success';

	json_output( $data );
}

function receive_reboot_status() {
	global $conn, $globals, $geoip, $geoisp;

	// map fields
	$id					= post( 'id' );
	$status 			= post( 'status' );
	$message 			= post( 'message' );

	// save data
	$update = $conn->exec( "UPDATE `server_reboot_queue` SET `status` = '".$status."' WHERE `id` = '".$id."' " );
	$update = $conn->exec( "UPDATE `server_reboot_queue` SET `message` = '".$message."' WHERE `id` = '".$id."' " );

	// get job
	$query = $conn->query( "
        SELECT * 
        FROM `server_reboot_queue` 
        WHERE `id` = '".$id."' 
    " );
    $job           = $query->fetch( PDO::FETCH_ASSOC );
    $job = stripslashes_deep( $job );

	// update server
	$update = $conn->exec( "UPDATE `servers` SET `status` = '".$status."' WHERE `id` = '".$job['server_id']."' " );
	
	$data['status'] = 'success';

	json_output( $data );
}

function receive_domain_name_update() {
	global $conn, $globals, $geoip, $geoisp;

	// map fields
	$id					= get( 'id' );
	$nameserver 		= get( 'nameserver' );

	if( $nameserver == 'ns1.cloudshield.io' || $nameserver == 'ns2.cloudshield.io' ) {
		// save data
		$update = $conn->exec( "UPDATE `domain_names` SET `status` = 'active' WHERE `id` = '".$id."' " );
		$update = $conn->exec( "UPDATE `domain_names` SET `nameserver` = '".$nameserver."' WHERE `id` = '".$id."' " );
	}

	$data['status'] = 'success';

	json_output( $data );
}
