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

// vars
$cluster_id = get( 'cluster_id' );

// get cluster
$cluster = get_cluster_admin( $cluster_id );

// build firewall rules
print '#!/bin/bash '."\n";
print ' '."\n";

// remove existing blackholes
print '# Remove existing blackholes '."\n";
print ' '."\n";
print "BLACKHOLES=$(ip route show | grep 'blackhole' | ip route show | grep blackhole | cut -d\   -f2); "."\n";
print ' '."\n";
print 'for item in $BLACKHOLES'."\n";
print 'do'."\n";
print '        sudo ip route del blackhole $item'."\n";
print 'done'."\n";
print ' '."\n";
print ' '."\n";

// get network prefixes
foreach( $cluster['blocked_networks'] as $blocked_network ) {
	print '# Network: '.$blocked_network['network_name']."\n";
	print '# AS Number: '.$blocked_network['asn']."\n";
	print ' '."\n";

	// get ip blocks for this asn
	$asn_data = @file_get_contents( 'https://api.bgpview.io/asn/'.$blocked_network['asn'].'/prefixes' );
	$asn_data = json_decode( $asn_data, true );

	print '# ipv4 prefixes '."\n";
	// loop over prefixes - ip4
	foreach( $asn_data['data']['ipv4_prefixes'] as $data ) {
		print 'sudo ip route add blackhole '.$data['prefix']."\n";
	}

	print ' '."\n";

	print '# ipv6 prefixes '."\n";
	// loop over prefixes - ip6
	foreach( $asn_data['data']['ipv6_prefixes'] as $data ) {
		print 'sudo ip route add blackhole '.$data['prefix']."\n";
	}

	print ' '."\n";
	print ' '."\n";
}

print ' '."\n";

