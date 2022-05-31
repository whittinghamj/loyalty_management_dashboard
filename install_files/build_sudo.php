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
$server_id = get( 'server_id' );

// get server
$server = get_server_admin( $server_id );

// build firewall rules
print '#!/bin/bash '."\n";
print ' '."\n";

print 'sudo_status=$(cat /etc/sudoers | grep '.$server['ssh_username'].' | wc -l)'."\n";
print 'if [ "$sudo_status" -eq "0" ]; then'."\n";
print '   echo "'.$server['ssh_username'].'    ALL=(ALL:ALL) NOPASSWD:ALL" >> /etc/sudoers'."\n";
print 'fi'."\n";
print ' '."\n";