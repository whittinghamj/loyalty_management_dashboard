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


// get queue
$domains = @file_get_contents( 'https://cloudshield.io/dashboard/api/?c=admin_get_domain_names' );
$domains = json_decode( $domains, true );

foreach( $domains as $domain ){
	if( $globals['dev'] == true ) {
        console_output( '========================================' );
        console_output( 'Domain Name: '.$domain['domain_name'] );
        console_output( '========================================' );
        console_output( '' );
    }

    $current_nameserver = exec( "whois ".$domain['domain_name']." | grep 'Name Server:' | head -1 ");
    $current_nameserver = str_replace( array( ' ', 'NameServer:' ), '', $current_nameserver );
    $current_nameserver = strtolower( $current_nameserver );

    if( $globals['dev'] == true ) {
        console_output( ' - Nameserver: '.$current_nameserver );
        console_output( '' );
    }

    // complete job
	$result = @file_get_contents( 'https://cloudshield.io/dashboard/api/?c=receive_domain_name_update&id='.$domain['id'].'&nameserver='.$current_nameserver );

}