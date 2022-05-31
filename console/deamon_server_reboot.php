<?php

// error logging
ini_set( 'display_startup_errors', 1 );
ini_set( 'display_errors', 1 );
ini_set( 'error_reporting', E_ALL );
error_reporting( E_ALL );

// vars
ini_set( "max_execution_time", 0 );
ini_set( "default_socket_timeout", 15 );
ini_set( "memory_limit", -1 );
$globals['basedir']         = '/home/cloudshield/public_html/dashboard/';

// include main functions
include( $globals['basedir'].'includes/db.php' );
include( $globals['basedir'].'includes/globals.php' );
include( $globals['basedir'].'includes/functions.php' );

function post_install_status( $job_id, $status, $message ) {
	$postfields["id"] 				= $job_id;
	$postfields["status"] 			= $status;
	$postfields["message"] 			= $message;

	$ch = curl_init();
	curl_setopt( $ch, CURLOPT_URL, 'https://cloudshield.io/dashboard/api/?c=receive_reboot_status' );
	curl_setopt( $ch, CURLOPT_POST, 1 );
	curl_setopt( $ch, CURLOPT_TIMEOUT, 30 );
	curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
	curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, 1 );
	curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST, 2 );
	curl_setopt( $ch, CURLOPT_POSTFIELDS, http_build_query( $postfields ) );
	$data = curl_exec( $ch );
	if( curl_error( $ch) ) {
		if( $globals['dev'] == true ) {
		    console_output( 'CURL Connection Error: '.curl_errno( $ch ).' - '.curl_error( $ch ) );
		}
	}
}

// is deamon already running
$pid = exec( "ps aux | grep 'deamon_server_reboot.php' | grep -v 'grep' | grep -v '/bin/sh' | grep -v 'color=auto' | grep -v 'deamon_installer.log' | wc -l" );
// console_output( '' );
// console_output( 'Command: '."ps aux | grep 'deamon_installer.php' | grep -v 'grep' | grep -v '/bin/sh' | grep -v 'color=auto' | grep -v 'deamon_installer.log' | wc -l" );
// console_output( 'Total PIDs: '.$pid );
// console_output( '' );

if( $pid  < 2  ) {
	// run deamon
	while( 1 ) {
		// clear .ssh/known_hosts for next job
		exec( "echo '' > /home/cloudshield/.ssh/known_hosts" );

		// get queue job
		$job = @file_get_contents( 'https://cloudshield.io/dashboard/api/?c=admin_get_reboot_queue' );
		$job = json_decode( $job, true );

		if( isset( $job['id'] ) ) {
			// post status to dashboard
			post_install_status( $job['id'], 'rebooting', '' );

			// set ssh_port from database
			$ssh_port = $job['server']['ssh_port'];

			// online check and map ssh ports
			$online_status = online_status_port_check( $job['server']['ip_address'], $job['server']['ssh_port'] );
			if( $online_status == true ) {
				// database port is active, nothing more to do at this time
			} else {
				// try port 22 for ssh
				$online_status = online_status_port_check( $job['server']['ip_address'], 22 );
				if( $online_status == true ) {
					$ssh_port = 22;
				} else {
					// try port 33077 for ssh
					$online_status = online_status_port_check( $job['server']['ip_address'], 33077 );
					if( $online_status == true ) {
						$ssh_port = 33077;
					}
				}
			}

			if( $globals['dev'] == true ) {
		        console_output( '========================================' );
		        console_output( 'Cluster Name: '.$job['cluster']['name'] );
		        console_output( 'Server Hostname: '.$job['server']['hostname'].'.'.$job['cluster']['iptv_main_server_domain'] );
		        console_output( 'Server IP: '.$job['server']['ip_address'] );
		        console_output( 'Server Port: '.$ssh_port );
		        console_output( 'Server User: '.$job['server']['ssh_username'] );
		        console_output( 'Server Pass: '.$job['server']['ssh_password'] );
		        console_output( '========================================' );
		        console_output( '' );
		    }

			if( $online_status == true ) {
				if( $globals['dev'] == true ) {
					console_output( '-> Server: Online' );
				}

				// build remote ssh command
				$cmd  = "sshpass -p '".$job['server']['ssh_password']."' ";
				$cmd .= "ssh -o StrictHostKeyChecking=no ";
				$cmd .= $job['server']['ssh_username']."@".$job['server']['ip_address']." ";
				$cmd .= "-p ".$ssh_port." ";
				$cmd .= "'";
				
				// the installer was able to login
				$cmd .= "sudo touch /opt/installer_logged_in.txt; ";

				// issue reboot command
				$cmd .= "sudo shutdown -r now; ";
				
				$cmd .= "' ";

				// reboot the server
				// $cmd .= "sudo reboot;' ";
				
				// send the output the /dev/null
				$cmd .= "2>/dev/null";

				if( $globals['dev'] == true ) {
					console_output( '-> Job: Rebooting server' );
				}

				// execute remote ssh commands
				exec( $cmd );

				// post status to dashboard
				post_install_status( $job['id'], 'rebooted', 'server rebooted' );
			} else {
				// post status to dashboard
				post_install_status( $job['id'], 'failed', 'unable to reach server' );

				if( $globals['dev'] == true ) {
					console_output( '-> Server: Offline' );
				}
			}

			if( $globals['dev'] == true ) {
				console_output( '' );
				console_output( '========================================' );
				console_output( 'Job finished' );
				console_output( '========================================' );
				console_output( '' );
			}
		} else {
			if( $globals['dev'] == true ) {
				// console_output( '========================================' );
		        // console_output( 'No jobs found' );
		        // console_output( '========================================' );
		        // console_output( '' );
			}
		}
	}

	if( $globals['dev'] == true ) {
		console_output( '' );
	    console_output( 'Deamon ended.' );
	    console_output( '' );
	}
} else {
	if( $globals['dev'] == true ) {
		// console_output( '' );
	    // console_output( 'Deamon already running.' );
	    // console_output( '' );
	}
}