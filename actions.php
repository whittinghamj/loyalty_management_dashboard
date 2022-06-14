<?php

// include main functions
include( dirname(__FILE__).'/includes/core.php' );
include( dirname(__FILE__).'/includes/functions.php' );

// login check
if( !isset( $_SESSION['logged_in'] ) || $_SESSION['logged_in'] != true ) {
	// redirect to index.php
	go( 'index.php' );
}

$a = get( 'a' );

switch( $a ) {
	// accept_terms
    case "accept_terms":
        accept_terms();
        break;

	// project_add
	case "project_add":
		project_add();
		break;

	// project_delete
	case "project_delete":
		project_delete();
		break;

	// settings_edit
	case "settings_edit":
		settings_edit();
		break;

	// whmcs_support
	case "whmcs_support":
		whmcs_support();
		break;

	// default		
	default:
		home();
		break;
}

function home() {
	global $conn, $globals, $account_details;

	die( 'access denied to function name '.get( 'a' ) );
}

function accept_terms() {
	global $conn, $globals, $account_details;

	// save data
	$update = $conn->exec( "UPDATE `users` SET `accept_terms` = 'yes' WHERE `id` = '".$_SESSION['account']['id']."' " );
	$update = $conn->exec( "UPDATE `users` SET `accept_terms_timestamp` = '".time()."' WHERE `id` = '".$_SESSION['account']['id']."' " );
	$update = $conn->exec( "UPDATE `users` SET `accept_terms_ip` = '".$globals['client_ip']."' WHERE `id` = '".$_SESSION['account']['id']."' " );

	$_SESSION['account_details']['accept_terms'] == 'yes';

	// set status message
	status_message( "success", "Terms &amp; Conditions have been accepted." );

	// redirect to dashboard.php
	go( 'dashboard.php' );
}

function settings_edit() {
	global $conn, $globals, $account_details;

	// map fields
	$platform_name 						= post( 'platform_name' );
	$url 								= post( 'url' );
	$smtp_username 						= post( 'smtp_username' );
	$smtp_password 						= post( 'smtp_password' );
	$smtp_domain 						= post( 'smtp_domain' );
	$smtp_name 							= post( 'smtp_name' );
	$dev 								= post( 'dev' );

	// save data
	$update = $conn->exec( "UPDATE `system_settings` SET `config_value` = '".$platform_name."' WHERE `config_name` = 'platform_name' " );
	$update = $conn->exec( "UPDATE `system_settings` SET `config_value` = '".$url."' WHERE `config_name` = 'url' " );
	$update = $conn->exec( "UPDATE `system_settings` SET `config_value` = '".$smtp_username."' WHERE `config_name` = 'smtp_username' " );
	$update = $conn->exec( "UPDATE `system_settings` SET `config_value` = '".$smtp_password."' WHERE `config_name` = 'smtp_password' " );
	$update = $conn->exec( "UPDATE `system_settings` SET `config_value` = '".$smtp_domain."' WHERE `config_name` = 'smtp_domain' " );
	$update = $conn->exec( "UPDATE `system_settings` SET `config_value` = '".$smtp_name."' WHERE `config_name` = 'smtp_name' " );
	$update = $conn->exec( "UPDATE `system_settings` SET `config_value` = '".$dev."' WHERE `config_name` = 'dev' " );
	

	// set status message
	status_message( "success", "Settings has been updated." );

	// redirect to dashboard.php
	go( 'dashboard.php?c=settings' );
}

function project_add() {
	global $conn, $globals, $account_details;

	// map fields
	$name 							= post( 'name' );

	// save data
	$insert = $conn->exec( "INSERT INTO `projects` 
		(`added`,`owner_id`,`name`)
		VALUE
		('".time()."',
		'".$_SESSION['account']['id']."', 
		'".$name."' 
	)" );
	
	// get new record id
	$project_id = $conn->lastInsertId();

	// set status message
	status_message( "success", "Project has been added." );

	// redirect to dashboard.php
	go( 'dashboard.php?c=project&id='.$project_id );
}

function project_join() {
	global $conn, $globals, $account_details;

	// map fields
	$name 							= post( 'name' );

	// save data
	$insert = $conn->exec( "INSERT INTO `projects` 
		(`added`,`user_id`,`name`)
		VALUE
		('".time()."',
		'".$_SESSION['account']['id']."', 
		'".$name."' 
	)" );
	
	// get new record id
	$project_id = $conn->lastInsertId();

	// set status message
	status_message( "success", "Project has been added." );

	// redirect to dashboard.php
	go( 'dashboard.php?c=project&id='.$project_id );
}

function project_delete() {
	global $conn, $globals, $account_details;

	// map fields
	$id 							= get( 'id' );

	// get project
	$cluster = get_cluster( $id );

	// security check
	if( !isset( $cluster['id'] ) ) { go( 'dashboard.php?c=not_found' ); }

	// delete pending jobs
	$delete = $conn->exec( "DELETE FROM `server_deployment_queue` WHERE `cluster_id` = '".$id."' AND `user_id` = '".$_SESSION['account']['id']."' " );

	// delete servers
	$delete = $conn->exec( "DELETE FROM `servers` WHERE `cluster_id` = '".$id."' AND `user_id` = '".$_SESSION['account']['id']."' " );

	// delete cluster
	$delete = $conn->exec( "DELETE FROM `clusters` WHERE `id` = '".$id."' AND `user_id` = '".$_SESSION['account']['id']."' " );

	// get domain
	$domain = get_domain_name( $cluster['domain_name_id'] );

	// delete dns records
	$delete = $conn->exec( "DELETE FROM `cloudshield_dns_server`.`records_cloudshield` WHERE `user_id` = '".$_SESSION['account']['id']."' AND `domain_id` = '".$domain['powerdns_id']."' AND `type` = 'A' " );
	$delete = $conn->exec( "DELETE FROM `cloudshield_dns_server`.`records` WHERE `user_id` = '".$_SESSION['account']['id']."' AND `domain_id` = '".$domain['powerdns_id']."' AND `type` = 'A' " );

	// remove cluster_id from domain_name record
	$uptime = $conn->exec( "UPDATE `domain_names` SET `cluster_id` = '' WHERE `id` = '".$domain['id']."' AND `user_id` = '".$_SESSION['account']['id']."' " );

	// set status message
	status_message( "success", "Cluster and all associated assets have been deleted." );

	// redirect to dashboard.php
	go( 'dashboard.php?c=clusters' );
}

function cluster_edit() {
	global $conn, $globals, $account_details;

	// map fields
	$id 							= post( 'id' );
	$name 							= post( 'name' );
	$iptv_main_server_domain 		= post( 'iptv_main_server_domain' );
	$iptv_main_server_ip_address 	= post( 'iptv_main_server_ip_address' );
	$iptv_main_server_port 			= post( 'iptv_main_server_port' );
	$notes 							= post( 'notes' );
	$enable_stalker 				= post( 'enable_stalker' );
	$enable_cache 					= post( 'enable_cache' );
	$geo_countries 					= post_array( 'geo_countries' );
	$geo_countries 					= json_encode( $geo_countries );
	$enable_firewall_droplist 		= post( 'enable_firewall_droplist' );
	$enable_ssl 					= post( 'enable_ssl' );
	$enable_ssl_out 				= post( 'enable_ssl_out' );
	$enable_evpn 					= post( 'enable_evpn' );

	// get cluster
	$cluster = get_cluster( $id );

	// security check
	if( !isset( $cluster['id'] ) ) { go( 'dashboard.php?c=not_found' ); }

	// get domain name details
	$domain = get_domain_name( $iptv_main_server_domain );

	// sanity checks
	if( !isset( $_POST['geo_countries'] ) || empty( $_POST['geo_countries'] ) ) { $geo_countries = '[]'; }
	
	// save data
	$update = $conn->exec( "UPDATE `clusters` SET `name` = '".$name."' WHERE `id` = '".$id."' AND `user_id` = '".$_SESSION['account']['id']."' " );
	$update = $conn->exec( "UPDATE `clusters` SET `iptv_main_server_domain` = '".$domain['domain_name']."' WHERE `id` = '".$id."' AND `user_id` = '".$_SESSION['account']['id']."' " );
	$update = $conn->exec( "UPDATE `clusters` SET `iptv_main_server_ip_address` = '".$iptv_main_server_ip_address."' WHERE `id` = '".$id."' AND `user_id` = '".$_SESSION['account']['id']."' " );
	$update = $conn->exec( "UPDATE `clusters` SET `iptv_main_server_port` = '".$iptv_main_server_port."' WHERE `id` = '".$id."' AND `user_id` = '".$_SESSION['account']['id']."' " );
	$update = $conn->exec( "UPDATE `clusters` SET `notes` = '".$notes."' WHERE `id` = '".$id."' AND `user_id` = '".$_SESSION['account']['id']."' " );
	$update = $conn->exec( "UPDATE `clusters` SET `enable_stalker` = '".$enable_stalker."' WHERE `id` = '".$id."' AND `user_id` = '".$_SESSION['account']['id']."' " );
	$update = $conn->exec( "UPDATE `clusters` SET `enable_cache` = '".$enable_cache."' WHERE `id` = '".$id."' AND `user_id` = '".$_SESSION['account']['id']."' " );
	$update = $conn->exec( "UPDATE `clusters` SET `geo_countries` = '".$geo_countries."' WHERE `id` = '".$id."' AND `user_id` = '".$_SESSION['account']['id']."' " );
	$update = $conn->exec( "UPDATE `clusters` SET `enable_firewall_droplist` = '".$enable_firewall_droplist."' WHERE `id` = '".$id."' AND `user_id` = '".$_SESSION['account']['id']."' " );
	$update = $conn->exec( "UPDATE `clusters` SET `enable_ssl` = '".$enable_ssl."' WHERE `id` = '".$id."' AND `user_id` = '".$_SESSION['account']['id']."' " );
	$update = $conn->exec( "UPDATE `clusters` SET `enable_ssl_out` = '".$enable_ssl_out."' WHERE `id` = '".$id."' AND `user_id` = '".$_SESSION['account']['id']."' " );
	$update = $conn->exec( "UPDATE `clusters` SET `domain_name_id` = '".$domain['id']."' WHERE `id` = '".$id."' AND `user_id` = '".$_SESSION['account']['id']."' " );
	$update = $conn->exec( "UPDATE `clusters` SET `enable_evpn` = '".$enable_evpn."' WHERE `id` = '".$id."' AND `user_id` = '".$_SESSION['account']['id']."' " );

	$update = $conn->exec( "UPDATE `domain_names` SET `cluster_id` = '".$id."' WHERE `id` = '".$domain['id']."' AND `user_id` = '".$_SESSION['account']['id']."' " );

	// evpn options
	if( $enable_evpn == 'yes' ) {
		// save data
		$insert = $conn->exec( "INSERT INTO `cloudshield_dns_server`.`records_cloudshield` 
			(`user_id`,`domain_id`,`name`,`type`,`content`,`ttl`,`proxied`,`server_type`)
			VALUE
			('".$_SESSION['account']['id']."', 
			'".$domain['powerdns_id']."', 
			'".md5( $cluster['id'] ).".".$domain['domain_name']."', 
			'A',
			'".$cluster['iptv_main_server_ip_address']."',
			'60',
			'yes',
			'vpn'
		)" );
	} else {
		$delete = $conn->exec( "
			DELETE FROM `cloudshield_dns_server`.`records_cloudshield` 
			WHERE `user_id` = '".$_SESSION['account']['id']."' 
			AND `domain_id` = '".$domain['powerdns_id']."' 
			AND `server_type` = 'vpn' 
		" );
	}

	// set status message
	status_message( "success", "Cluster has been updated." );

	// redirect to dashboard.php
	go( 'dashboard.php?c=cluster_edit&id='.$id );
}


