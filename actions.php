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

function cluster_delete() {
	global $conn, $globals, $account_details;

	// map fields
	$id 							= get( 'id' );

	// get cluster
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

function cluster_state() {
	global $conn, $globals, $account_details;

	// map fields
	$id 							= get( 'id' );
	$state 							= get( 'state' );

	// get cluster
	$cluster = get_cluster( $id );

	// security check
	if( !isset( $cluster['id'] ) ) { go( 'dashboard.php?c=not_found' ); }

	// save data
	$update = $conn->exec( "UPDATE `clusters` SET `state` = '".$state."' WHERE `id` = '".$id."' AND `user_id` = '".$_SESSION['account']['id']."' " );

	// set status message
	status_message( "success", "Cluster state has been updated." );

	// redirect to dashboard.php
	go( $_SERVER['HTTP_REFERER'] );
}

function controller_add() {
	global $conn, $globals, $account_details;

	// map fields
	$cluster_id 					= post( 'cluster_id' );
	$ip_address 					= post( 'ip_address' );
	$ssh_port 						= post( 'ssh_port' );
	$ssh_username 					= post( 'ssh_username' );
	$ssh_password 					= post( 'ssh_password' );
	$notes 							= post( 'notes' );
	$type 							= 'controller';

	// does ip already exist
	$query = $conn->query( "
		SELECT `id` 
		FROM `servers` 
		WHERE `ip_address` = '".$ip_address."' 
	" );
	$data		   = $query->fetch( PDO::FETCH_ASSOC );
	if( isset( $data['id'] ) ) {
		status_message( "danger", $ip_address." is already in use, please choose check your IP address and try again." );
		go( 'dashboard.php?c=controller_add&cluster_id='.$cluster_id );
	}

	// generate random hostname
	$hostname = random_string( rand( 8,32 ) );
	$hostname = strtolower( $hostname );

	// save data
	$insert = $conn->exec( "INSERT INTO `servers` 
		(`user_id`,`cluster_id`,`added`,`hostname`,`ip_address`,`ssh_port`,`ssh_username`,`ssh_password`,`notes`,`type`)
		VALUE
		('".$_SESSION['account']['id']."', 
		'".$cluster_id."', 
		'".time()."', 
		'".$hostname."', 
		'".$ip_address."', 
		'".$ssh_port."', 
		'".$ssh_username."', 
		'".$ssh_password."', 
		'".$notes."',
		'".$type."'
	)" );

	// get new record id
	$server_id = $conn->lastInsertId();

	// get cluster domain name
	$cluster = get_cluster( $cluster_id );
	$domain = get_domain_name( $cluster['domain_name_id'] );

	// create powerdns record - main domain / dns round robin
	$insert = $conn->exec( "INSERT INTO `cloudshield_dns_server`.`records_cloudshield` 
		(`user_id`,`domain_id`,`name`,`type`,`content`,`ttl`)
		VALUE
		('".$_SESSION['account']['id']."',
		'".$domain['powerdns_id']."', 
		'".$domain['domain_name']."', 
		'A',
		'".$ip_address."',
		'120'
	)" );

	// create powerdns record - controller hostname
	$insert = $conn->exec( "INSERT INTO `cloudshield_dns_server`.`records_cloudshield` 
		(`user_id`,`domain_id`,`name`,`type`,`content`,`ttl`,`server_type`)
		VALUE
		('".$_SESSION['account']['id']."',
		'".$domain['powerdns_id']."', 
		'".$hostname.".".$domain['domain_name']."', 
		'A',
		'".$ip_address."',
		'120',
		'controller'
	)" );

	// create powerdns record - main domain / dns round robin
	$insert = $conn->exec( "INSERT INTO `cloudshield_dns_server`.`records` 
		(`user_id`,`domain_id`,`name`,`type`,`content`,`ttl`)
		VALUE
		('".$_SESSION['account']['id']."',
		'".$domain['powerdns_id']."', 
		'".$domain['domain_name']."', 
		'A',
		'".$ip_address."',
		'120'
	)" );

	// create powerdns record - controller hostname
	$insert = $conn->exec( "INSERT INTO `cloudshield_dns_server`.`records` 
		(`user_id`,`domain_id`,`name`,`type`,`content`,`ttl`)
		VALUE
		('".$_SESSION['account']['id']."',
		'".$domain['powerdns_id']."', 
		'".$hostname.".".$domain['domain_name']."', 
		'A',
		'".$ip_address."',
		'120'
	)" );

	// insert proxy into deployment queue
	$insert = $conn->exec( "INSERT INTO `server_deployment_queue` 
		(`added`,`user_id`,`cluster_id`,`server_id`,`server_type`,`job_type`)
		VALUE
		('".time()."', 
		'".$_SESSION['account']['id']."', 
		'".$cluster_id."',
		'".$server_id."',
		'".$type."',
		'install'
	)" );

	// set status message
	status_message( "success", "Controller has been added and will be deployed shortly." );

	// redirect to dashboard.php
	go( 'dashboard.php?c=cluster&id='.$cluster_id );
}

function controller_delete() {
	global $conn, $globals, $account_details;

	// map fields
	$id 							= get( 'id' );

	// get server
	$server = get_server( $id );

	// security check
	if( !isset( $server['id'] ) ) { go( 'dashboard.php?c=not_found' ); }

	// delete pending jobs
	$delete = $conn->exec( "DELETE FROM `server_deployment_queue` WHERE `server_id` = '".$id."' AND `user_id` = '".$_SESSION['account']['id']."' " );

	// delete dns record
	$delete = $conn->exec( "DELETE FROM `cloudshield_dns_server`.`records_cloudshield` WHERE `content` = '".$server['ip_address']."' AND `user_id` = '".$_SESSION['account']['id']."' " );

	// delete dns record
	$delete = $conn->exec( "DELETE FROM `cloudshield_dns_server`.`records` WHERE `content` = '".$server['ip_address']."' AND `user_id` = '".$_SESSION['account']['id']."' " );

	// delete server
	$delete = $conn->exec( "DELETE FROM `servers` WHERE `id` = '".$id."' AND `user_id` = '".$_SESSION['account']['id']."' " );

	// set status message
	status_message( "success", "Proxy Server has been deleted." );

	// redirect to dashboard.php
	go( 'dashboard.php?c=cluster&id='.$server['cluster_id'] );
}

function controller_rebuild() {
	global $conn, $globals, $account_details;

	// map fields
	$id 							= get( 'id' );
	$type 							= get( 'type' );

	// get server
	$server = get_server( $id );

	// security check
	if( !isset( $server['id'] ) ) { go( 'dashboard.php?c=not_found' ); }
	
	// set server status to pending
	$update = $conn->exec( "UPDATE `servers` SET `status` = 'pending' WHERE `id` = '".$id."' " );

	// remove any pending jobs
	$delete = $conn->exec( "DELETE FROM `server_deployment_queue` WHERE `server_id` = '".$id."' AND `status` = 'pending' " );

	// add job
	$insert = $conn->exec( "INSERT INTO `server_deployment_queue` 
		(`added`,`user_id`,`cluster_id`,`server_id`,`server_type`,`job_type`)
		VALUE
		('".time()."', 
		'".$_SESSION['account']['id']."', 
		'".$server['cluster_id']."',
		'".$id."',
		'controller',
		'".$type."'
	)" );

	// set status message
	status_message( "success", "Controller will be redeployed shortly." );

	// redirect to dashboard.php
	go( 'dashboard.php?c=cluster&id='.$server['cluster_id'] );
}

function domain_name_add() {
	global $conn, $globals, $account_details;

	// map fields
	$domain_name 					= post( 'domain_name' );

	// clean domain_name
    $domain_name = strtolower( trim( $domain_name ) );
    $domain_name = preg_replace( '/^http:\/\//i', '', $domain_name );
    $domain_name = preg_replace( '/^www\./i', '', $domain_name );
    $domain_name = explode( '/', $domain_name );
    $domain_name = trim( $domain_name[0] );

	// does domain already exist
	$query = $conn->query( "
		SELECT `id` 
		FROM `domain_names` 
		WHERE `domain_name` = '".$domain_name."' 
	" );
	$data		   = $query->fetch( PDO::FETCH_ASSOC );
	if( isset( $data['id'] ) ) {
		status_message( "danger", $domain_name." is already in use, please choose check the domain name you entered and try again." );
		go( 'dashboard.php?c=domain_names' );
	}

	// get whois data for this domain
	$whois_data = domain_whois_query( $domain_name );

	// sanity check
	if( !is_array( $whois_data ) || !isset( $whois_data['WhoisRecord'] ) ) {
		status_message( "danger", "We were unable to obtain the required information about this domain. Please try again." );
		go( 'dashboard.php?c=domain_names' );
	}

	// map fields
	$expires = $whois_data['WhoisRecord']['registryData']['expiresDate'];
	$registrar = $whois_data['WhoisRecord']['registrarName'];

	// create power_dns domain record
	$insert = $conn->exec( "INSERT INTO `cloudshield_dns_server`.`domains` 
		(`user_id`,`name`,`type`)
		VALUE
		('".$_SESSION['account']['id']."', 
		'".$domain_name."', 
		'NATIVE'
	)" );
	
	// get new record id
	$powerdns_id = $conn->lastInsertId();

	// create powerdns initial records
	$insert = $conn->exec( "INSERT INTO `cloudshield_dns_server`.`records_cloudshield` 
		(`user_id`,`domain_id`,`name`,`type`,`content`,`ttl`)
		VALUE
		('".$_SESSION['account']['id']."', 
		'".$powerdns_id."', 
		'".$domain_name."', 
		'SOA',
		'ns1.cloudshield.io admin.cloudshield.io 1 10800 3600 604800 3600',
		'120'
	)" );

	$insert = $conn->exec( "INSERT INTO `cloudshield_dns_server`.`records_cloudshield` 
		(`user_id`,`domain_id`,`name`,`type`,`content`,`ttl`)
		VALUE
		('".$_SESSION['account']['id']."', 
		'".$powerdns_id."', 
		'www', 
		'CNAME',
		'".$domain_name."', 
		'120'
	)" );

	$insert = $conn->exec( "INSERT INTO `cloudshield_dns_server`.`records_cloudshield` 
		(`user_id`,`domain_id`,`name`,`type`,`content`,`ttl`)
		VALUE
		('".$_SESSION['account']['id']."', 
		'".$powerdns_id."', 
		'".$domain_name."', 
		'NS',
		'ns1.cloudshield.io', 
		'120'
	)" );

	$insert = $conn->exec( "INSERT INTO `cloudshield_dns_server`.`records_cloudshield` 
		(`user_id`,`domain_id`,`name`,`type`,`content`,`ttl`)
		VALUE
		('".$_SESSION['account']['id']."', 
		'".$powerdns_id."', 
		'".$domain_name."', 
		'NS',
		'ns2.cloudshield.io', 
		'120'
	)" );

	// save data
	$insert = $conn->exec( "INSERT INTO `domain_names` 
		(`added`,`user_id`,`domain_name`,`expires`,`registrar`,`powerdns_id`)
		VALUE
		('".time()."',
		'".$_SESSION['account']['id']."', 
		'".$domain_name."', 
		'".$expires."',
		'".$registrar."',
		'".$powerdns_id."'
	)" );

	// get new record id
	$domain_name_id = $conn->lastInsertId();

	// set status message
	status_message( "success", "Domain Name has been added." );

	// redirect to dashboard.php
	go( 'dashboard.php?c=domain_name&id='.$domain_name_id );
}

function domain_name_delete() {
	global $conn, $globals, $account_details;

	// map fields
	$id 							= get( 'id' );

	// get domain
	$domain = get_domain_name( $id );

	// security check
	if( !isset( $domain['id'] ) ) { go( 'dashboard.php?c=not_found' ); }

	// update records
	$update = $conn->exec( "UPDATE `clusters` SET `iptv_main_server_domain` = '' WHERE `domain_name_id` = '".$domain['id']."' " );
	$update = $conn->exec( "UPDATE `clusters` SET `domain_name_id` = '' WHERE `domain_name_id` = '".$domain['id']."' " );

	// delete domain
	$delete = $conn->exec( "DELETE FROM `cloudshield_dns_server`.`domains` WHERE `id` = '".$domain['powerdns_id']."' " );
	$delete = $conn->exec( "DELETE FROM `cloudshield_dns_server`.`records_cloudshield` WHERE `domain_id` = '".$domain['powerdns_id']."' " );
	$delete = $conn->exec( "DELETE FROM `cloudshield_dns_server`.`records` WHERE `domain_id` = '".$domain['powerdns_id']."' " );
	$delete = $conn->exec( "DELETE FROM `domain_names` WHERE `id` = '".$id."' " );

	// set status message
	status_message( "success", "Domain Name has been deleted." );

	// redirect to dashboard.php
	go( 'dashboard.php?c=domain_names' );
}

function domain_name_record_add() {
	global $conn, $globals, $account_details;

	// map fields
	$domain_id 						= post( 'domain_id' );
	$name 							= post( 'name' );
	$type 							= post( 'type' );
	$name 							= post( 'name' );
	$content 						= post( 'content' );
	$proxied 						= post( 'proxied' );

	// get domain
	$domain = get_domain_name( $domain_id );

	// security check
	if( !isset( $domain['id'] ) ) { go( 'dashboard.php?c=not_found' ); }

	// prepare name
	$name = str_replace( $domain['domain_name'], '', $name );
	$name = rtrim( $name, '.' );
	$name = ltrim( $name, '.' );

	$name = $name.'.'.$domain['domain_name'];

	// get controller IP if this record should be proxied
	if( $proxied == 'yes' ) {
		$query = $conn->query( "
			SELECT `ip_address` 
			FROM `servers` 
			WHERE `cluster_id` = '".$domain['cluster_id']."' 
			AND `type` = 'controller' 
			LIMIT 1
		" );
		$data		   = $query->fetch( PDO::FETCH_ASSOC );

		$controller = stripslashes_deep( $data );
	}

	// save data
	$insert = $conn->exec( "INSERT INTO `cloudshield_dns_server`.`records_cloudshield` 
		(`user_id`,`domain_id`,`name`,`type`,`content`,`ttl`,`proxied`)
		VALUE
		('".$_SESSION['account']['id']."', 
		'".$domain['powerdns_id']."', 
		'".$name."', 
		'".$type."',
		'".$content."',
		'120',
		'".$proxied."'
	)" );

	// set status message
	status_message( "success", "DNS Record has been added." );

	// redirect to dashboard.php
	go( 'dashboard.php?c=domain_name&id='.$domain_id );
}

function domain_name_resync_dns_records() {
	global $conn, $globals, $account_details;

	// map fields
	$id 							= get( 'id' );

	// get domain
	$domain = get_domain_name( $id );

	// security check
	if( !isset( $domain['id'] ) ) { go( 'dashboard.php?c=not_found' ); }

	// get all hostnames from servers table
	$query = $conn->query( "
		SELECT `id`,`ip_address`,`hostname` 
		FROM `servers` 
		WHERE `cluster_id` = '".$domain['cluster_id']."' 
		AND `user_id` = '".$_SESSION['account']['id']."' 
	" );
	$servers		   = $query->fetchAll( PDO::FETCH_ASSOC );

	$servers = stripslashes_deep( $servers );

	// check server['hostname'] against existing dns records in powerdns
	foreach( $servers as $server ) {
		// does record already exist
		$query = $conn->query( "
			SELECT `id` 
			FROM `cloudshield_dns_server`.`records_cloudshield` 
			WHERE `name` = '".$server['hostname'].".".$domain['domain_name']."' 
		" );
		$dns_record		   = $query->fetch( PDO::FETCH_ASSOC );

		// if dns record is not found, create it
		if( !isset( $dns_record['id'] ) ) {
			$insert = $conn->exec( "INSERT INTO `cloudshield_dns_server`.`records_cloudshield` 
				(`user_id`,`domain_id`,`name`,`type`,`content`,`ttl`)
				VALUE
				('".$_SESSION['account']['id']."', 
				'".$domain['powerdns_id']."', 
				'".$server['hostname'].".".$domain['domain_name']."', 
				'A',
				'".$server['ip_address']."',
				'120'
			)" );
		}
	}

	// get all controllers
	$query = $conn->query( "
		SELECT `id`,`ip_address`,`hostname` 
		FROM `servers` 
		WHERE `cluster_id` = '".$domain['cluster_id']."' 
		AND `type` = 'controller' 
		AND `user_id` = '".$_SESSION['account']['id']."' 
	" );
	$controllers		   = $query->fetchAll( PDO::FETCH_ASSOC );

	$controllers = stripslashes_deep( $controllers );
	// if domain points to controller(s)
	foreach( $controllers as $controller ) {
		$query = $conn->query( "
			SELECT `id` 
			FROM `cloudshield_dns_server`.`records_cloudshield` 
			WHERE `name` = '".$domain['domain_name']."' 
			AND `content` = '".$controller['ip_address']."' 
		" );
		$dns_record		   = $query->fetch( PDO::FETCH_ASSOC );

		// if dns record is not found, create it
		if( !isset( $dns_record['id'] ) ) {
			$insert = $conn->exec( "INSERT INTO `cloudshield_dns_server`.`records_cloudshield` 
				(`user_id`,`domain_id`,`name`,`type`,`content`,`ttl`)
				VALUE
				('".$_SESSION['account']['id']."', 
				'".$domain['powerdns_id']."', 
				'".$domain['domain_name']."', 
				'A',
				'".$controller['ip_address']."',
				'120'
			)" );
		}
	}

	// check for default DNS records
	$query = $conn->query( "
			SELECT `id` 
			FROM `cloudshield_dns_server`.`records_cloudshield` 
			WHERE `name` = 'www' 
			AND `content` = '".$domain['domain_name']."'
		" );
	$dns_record		   = $query->fetch( PDO::FETCH_ASSOC );

	// if dns record is not found, create it
	if( !isset( $dns_record['id'] ) ) {
		$insert = $conn->exec( "INSERT INTO `cloudshield_dns_server`.`records_cloudshield` 
			(`user_id`,`domain_id`,`name`,`type`,`content`,`ttl`)
			VALUE
			('".$_SESSION['account']['id']."', 
			'".$domain['powerdns_id']."', 
			'www', 
			'CNAME',
			'".$domain['domain_name']."',
			'120'
		)" );
	}

	// check for default DNS records
	$query = $conn->query( "
			SELECT `id` 
			FROM `cloudshield_dns_server`.`records_cloudshield` 
			WHERE `name` = '".$domain['domain_name']."' 
			AND `content` = 'ns1.cloudshield.io'
		" );
	$dns_record		   = $query->fetch( PDO::FETCH_ASSOC );

	// if dns record is not found, create it
	if( !isset( $dns_record['id'] ) ) {
		$insert = $conn->exec( "INSERT INTO `cloudshield_dns_server`.`records_cloudshield` 
			(`user_id`,`domain_id`,`name`,`type`,`content`,`ttl`)
			VALUE
			('".$_SESSION['account']['id']."', 
			'".$domain['powerdns_id']."', 
			'".$domain['domain_name']."', 
			'NS',
			'ns1.cloudshield.io',
			'120'
		)" );
	}

	// check for default DNS records
	$query = $conn->query( "
			SELECT `id` 
			FROM `cloudshield_dns_server`.`records_cloudshield` 
			WHERE `name` = '".$domain['domain_name']."' 
			AND `content` = 'ns2.cloudshield.io'
		" );
	$dns_record		   = $query->fetch( PDO::FETCH_ASSOC );

	// if dns record is not found, create it
	if( !isset( $dns_record['id'] ) ) {
		$insert = $conn->exec( "INSERT INTO `cloudshield_dns_server`.`records_cloudshield` 
			(`user_id`,`domain_id`,`name`,`type`,`content`,`ttl`)
			VALUE
			('".$_SESSION['account']['id']."', 
			'".$domain['powerdns_id']."', 
			'".$domain['domain_name']."', 
			'NS',
			'ns2.cloudshield.io',
			'120'
		)" );
	}

	// set status message
	status_message( "success", "DNS Records have been synced." );

	// redirect to dashboard.php
	go( 'dashboard.php?c=domain_name&id='.$id );
}

function domain_name_record_delete() {
	global $conn, $globals, $account_details;

	// map fields
	$domain_id 							= get( 'domain_id' );
	$record_id 							= get( 'record_id' );

	// get domain
	$domain_name = get_domain_name( $domain_id );

	// security check
	if( !isset( $domain_name['id'] ) ) { go( 'dashboard.php?c=not_found' ); }

	// get the dns record we are about to delete
	$dns_record = get_dns_record( $record_id );

	// delete record
	$delete = $conn->exec( "DELETE FROM `cloudshield_dns_server`.`records_cloudshield` WHERE `id` = '".$record_id."' AND `domain_id` = '".$domain_name['powerdns_id']."' " );

	// delete record
	if( $dns_record['proxied'] == 'no' ) {
		$delete = $conn->exec( "DELETE FROM `cloudshield_dns_server`.`records` 
			WHERE `user_id` = '".$_SESSION['account']['id']."' 
			AND `domain_id` = '".$dns_record['domain_id']."' 
			AND `name` = '".$dns_record['name']."' 
			AND `type` = '".$dns_record['type']."' 
			AND `content` = '".$dns_record['content']."' 
		" );
	} else {
		$delete = $conn->exec( "DELETE FROM `cloudshield_dns_server`.`records` 
			WHERE `user_id` = '".$_SESSION['account']['id']."' 
			AND `domain_id` = '".$dns_record['domain_id']."' 
			AND `name` = '".$dns_record['name']."' 
			AND `type` = '".$dns_record['type']."' 
		" );
	}

	// set status message
	status_message( "success", "DNS Record has been deleted." );

	// redirect to dashboard.php
	go( 'dashboard.php?c=domain_name&id='.$domain_name['id'] );
}

function proxy_add() {
	global $conn, $globals, $account_details;

	// map fields
	$cluster_id 					= post( 'cluster_id' );
	$ip_address 					= post( 'ip_address' );
	$ssh_port 						= post( 'ssh_port' );
	$ssh_username 					= post( 'ssh_username' );
	$ssh_password 					= post( 'ssh_password' );
	$notes 							= post( 'notes' );
	$type 							= 'proxy';

	// does ip already exist
	$query = $conn->query( "
		SELECT `id` 
		FROM `servers` 
		WHERE `ip_address` = '".$ip_address."' 
	" );
	$data		   = $query->fetch( PDO::FETCH_ASSOC );
	if( isset( $data['id'] ) ) {
		status_message( "danger", $ip_address." is already in use, please check your IP address and try again." );
		go( 'dashboard.php?c=proxy_add&cluster_id='.$cluster_id );
	}

	// generate random hostname
	$hostname = random_string( rand( 8,32 ) );
	$hostname = strtolower( $hostname );

	// save data
	$insert = $conn->exec( "INSERT INTO `servers` 
		(`user_id`,`cluster_id`,`added`,`hostname`,`ip_address`,`ssh_port`,`ssh_username`,`ssh_password`,`notes`,`type`)
		VALUE
		('".$_SESSION['account']['id']."', 
		'".$cluster_id."', 
		'".time()."', 
		'".$hostname."', 
		'".$ip_address."', 
		'".$ssh_port."', 
		'".$ssh_username."', 
		'".$ssh_password."', 
		'".$notes."',
		'".$type."'
	)" );
	
	// get new record id
	$server_id = $conn->lastInsertId();

	// get cluster domain name
	$cluster = get_cluster( $cluster_id );
	$domain = get_domain_name( $cluster['domain_name_id'] );

	// create powerdns record - controller hostname
	$insert = $conn->exec( "INSERT INTO `cloudshield_dns_server`.`records_cloudshield` 
		(`user_id`,`domain_id`,`name`,`type`,`content`,`ttl`,`server_type`)
		VALUE
		('".$_SESSION['account']['id']."', 
		'".$domain['powerdns_id']."', 
		'".$hostname.".".$domain['domain_name']."', 
		'A',
		'".$ip_address."',
		'120',
		'proxy'
	)" );

	$insert = $conn->exec( "INSERT INTO `cloudshield_dns_server`.`records` 
		(`user_id`,`domain_id`,`name`,`type`,`content`,`ttl`)
		VALUE
		('".$_SESSION['account']['id']."', 
		'".$domain['powerdns_id']."', 
		'".$hostname.".".$domain['domain_name']."', 
		'A',
		'".$ip_address."',
		'120'
	)" );

	// insert proxy into deployment queue
	$insert = $conn->exec( "INSERT INTO `server_deployment_queue` 
		(`added`,`user_id`,`cluster_id`,`server_id`,`server_type`,`job_type`)
		VALUE
		('".time()."', 
		'".$_SESSION['account']['id']."', 
		'".$cluster_id."',
		'".$server_id."',
		'".$type."',
		'install'
	)" );

	// set status message
	status_message( "success", "Proxy has been added and will be deployed shortly." );

	// redirect to dashboard.php
	go( 'dashboard.php?c=cluster&id='.$cluster_id );
}

function proxy_delete() {
	global $conn, $globals, $account_details;

	$id 							= get( 'id' );

	// get server
	$server = get_server( $id );

	// security check
	if( !isset( $server['id'] ) ) { go( 'dashboard.php?c=not_found' ); }

	// delete pending jobs
	$delete = $conn->exec( "DELETE FROM `server_deployment_queue` WHERE `server_id` = '".$id."' AND `user_id` = '".$_SESSION['account']['id']."' " );

	// delete dns record
	$delete = $conn->exec( "DELETE FROM `cloudshield_dns_server`.`records_cloudshield` WHERE `content` = '".$server['ip_address']."' AND `user_id` = '".$_SESSION['account']['id']."' " );

	// delete dns record
	$delete = $conn->exec( "DELETE FROM `cloudshield_dns_server`.`records` WHERE `content` = '".$server['ip_address']."' AND `user_id` = '".$_SESSION['account']['id']."' " );

	// delete server
	$delete = $conn->exec( "DELETE FROM `servers` WHERE `id` = '".$id."' AND `user_id` = '".$_SESSION['account']['id']."' " );

	// set status message
	status_message( "success", "Proxy Server has been deleted." );

	// redirect to dashboard.php
	go( 'dashboard.php?c=cluster&id='.$server['cluster_id'] );
}

function proxy_rebuild() {
	global $conn, $globals, $account_details;

	// map fields
	$id 							= get( 'id' );
	$type 							= get( 'type' );

	// get server
	$server = get_server( $id );

	// security check
	if( !isset( $server['id'] ) ) { go( 'dashboard.php?c=not_found' ); }
	
	// set server status to pending
	$update = $conn->exec( "UPDATE `servers` SET `status` = 'pending' WHERE `id` = '".$id."' " );

	// remove any pending jobs
	$delete = $conn->exec( "DELETE FROM `server_deployment_queue` WHERE `server_id` = '".$id."' AND `status` = 'pending' " );

	// add job
	$insert = $conn->exec( "INSERT INTO `server_deployment_queue` 
		(`added`,`user_id`,`cluster_id`,`server_id`,`server_type`,`job_type`)
		VALUE
		('".time()."', 
		'".$_SESSION['account']['id']."', 
		'".$server['cluster_id']."',
		'".$id."',
		'proxy',
		'".$type."'
	)" );

	// set status message
	status_message( "success", "Proxy will be redeployed shortly." );

	// redirect to dashboard.php
	go( 'dashboard.php?c=cluster&id='.$server['cluster_id'] );
}

function proxy_rebuild_all() {
	global $conn, $globals, $account_details;

	// map fields
	$servers 			= get_servers( get( 'cluster_id' ) , 'proxy' );

	// security check
	if( !isset( $servers[0]['id'] ) ) { go( 'dashboard.php?c=not_found' ); }

	// loop over data
	foreach( $servers as $server ) {
		// set server status to pending
		$update = $conn->exec( "UPDATE `servers` SET `status` = 'pending' WHERE `id` = '".$server['id']."' " );

		// add job
		$insert = $conn->exec( "INSERT INTO `server_deployment_queue` 
			(`added`,`user_id`,`cluster_id`,`server_id`,`server_type`,`job_type`)
			VALUE
			('".time()."', 
			'".$_SESSION['account']['id']."', 
			'".$server['cluster_id']."',
			'".$server['id']."',
			'proxy',
			'rebuild'
		)" );
	}

	// set status message
	status_message( "success", "All Proxies will be redeployed shortly." );

	// redirect to dashboard.php
	go( 'dashboard.php?c=cluster&id='.$server['cluster_id'] );
}

function server_edit() {
	global $conn, $globals, $account_details;

	// map fields
	$id 							= post( 'id' );
	$ssh_port 						= post( 'ssh_port' );
	$ssh_username 					= post( 'ssh_username' );
	$ssh_password 					= post( 'ssh_password' );
	$notes 							= post( 'notes' );

	// get server
	$server = get_server( $id );

	// security check
	if( !isset( $server['id'] ) ) { go( 'dashboard.php?c=not_found' ); }

	// save data
	$update = $conn->exec( "UPDATE `servers` SET `ssh_port` = '".$ssh_port."' WHERE `id` = '".$id."' " );
	$update = $conn->exec( "UPDATE `servers` SET `ssh_username` = '".$ssh_username."' WHERE `id` = '".$id."' " );
	$update = $conn->exec( "UPDATE `servers` SET `ssh_password` = '".$ssh_password."' WHERE `id` = '".$id."' " );
	$update = $conn->exec( "UPDATE `servers` SET `notes` = '".$notes."' WHERE `id` = '".$id."' " );

	// set status message
	status_message( "success", ucfirst( $server['type'] )." has been updated, you may need to rebuild or reinstall this server." );

	// redirect to dashboard.php
	go( 'dashboard.php?c=cluster&id='.$server['cluster_id'] );
}

function server_reboot() {
	global $conn, $globals, $account_details;

	// map fields
	$id 							= get( 'id' );
	
	// get server
	$server = get_server( $id );

	// security check
	if( !isset( $server['id'] ) ) { go( 'dashboard.php?c=not_found' ); }
	
	// set server status to rebooting
	$update = $conn->exec( "UPDATE `servers` SET `status` = 'rebooting' WHERE `id` = '".$id."' AND `cluster_id` = '".$server['cluster_id']."' " );

	// remove any pending jobs
	$delete = $conn->exec( "DELETE FROM `server_deployment_queue` WHERE `server_id` = '".$id."' AND `status` = 'pending' " );

	// add job
	$insert = $conn->exec( "INSERT INTO `server_reboot_queue` 
		(`added`,`user_id`,`cluster_id`,`server_id`)
		VALUE
		('".time()."', 
		'".$_SESSION['account']['id']."', 
		'".$server['cluster_id']."',
		'".$id."'
	)" );

	// set status message
	status_message( "success", "Server will reboot shortly." );

	// redirect to dashboard.php
	go( 'dashboard.php?c=cluster&id='.$server['cluster_id'] );
}

function vpn_wizard() {
	global $conn, $globals, $account_details;

	// map fields
	$cluster_id 					= post( 'cluster_id' );
	$subdomain 						= post( 'subdomain' );

	// get cluster domain name
	$cluster = get_cluster( $cluster_id );

	// security check
	if( !isset( $cluster['id'] ) ) { go( 'dashboard.php?c=not_found' ); }

	# get domain name details
	$domain = get_domain_name( $cluster['domain_name_id'] );

	// prepare subdomain
	$subdomain = str_replace( $domain['domain_name'], '', $subdomain );
	$subdomain = rtrim( $subdomain, '.' );
	$subdomain = ltrim( $subdomain, '.' );

	$subdomain = $subdomain.'.'.$domain['domain_name'];

	// save data
	$insert = $conn->exec( "INSERT INTO `cloudshield_dns_server`.`records_cloudshield` 
		(`user_id`,`domain_id`,`name`,`type`,`content`,`ttl`,`proxied`)
		VALUE
		('".$_SESSION['account']['id']."', 
		'".$domain['powerdns_id']."', 
		'".$subdomain."', 
		'A',
		'".$cluster['iptv_main_server_ip_address']."',
		'120',
		'yes'
	)" );

	// download config file

	// set status message
	status_message( "success", "VPN Cluster has been configured." );

	// redirect to dashboard.php
	go( 'dashboard.php?c=cluster&id='.$cluster_id );
}

function whmcs_support() {
	$whmcsurl 			= "https://clients.deltacolo.com/dologin.php";
	$autoauthkey 		= "admin1372";
	$email 				= $_SESSION['account']['email'];
	
	$timestamp 			= time(); 
	$goto 				= "supporttickets.php";
	
	$hash 				= sha1($email.$timestamp.$autoauthkey);
	
	$url 				= $whmcsurl."?email=$email&timestamp=$timestamp&hash=$hash&goto=".urlencode($goto);
	
	header("Location: $url");
}
