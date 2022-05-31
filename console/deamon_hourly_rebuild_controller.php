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

function post_install_status( $job_id, $status, $message ) {
	$postfields["id"] 				= $job_id;
	$postfields["status"] 			= $status;
	$postfields["message"] 			= $message;

	$ch = curl_init();
	curl_setopt( $ch, CURLOPT_URL, 'https://cloudshield.io/dashboard/api/?c=receive_deployment_status' );
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

// clear .ssh/known_hosts for next job
exec( "echo '' > /home/cloudshield/.ssh/known_hosts" );

// get queue job
$jobs = @file_get_contents( 'https://cloudshield.io/dashboard/api/?c=admin_get_controllers_rebuild' );
$jobs = json_decode( $jobs, true );

foreach( $jobs as $job) {
	if( $globals['dev'] == true ) {
        console_output( '================================================================================' );
        console_output( 'Cluster Name: '.$job['cluster']['name'] );
        console_output( 'Server Hostname: '.$job['server']['hostname'].'.'.$job['cluster']['iptv_main_server_domain'] );
        console_output( 'Server IP: '.$job['server']['ip_address'] );
        console_output( 'Server Port: '.$job['server']['ssh_port'] );
        console_output( 'Server User: '.$job['server']['ssh_username'] );
        console_output( 'Server Pass: '.$job['server']['ssh_password'] );
        console_output( '================================================================================' );
        console_output( '' );
    }

	// ping check
	$ping_check = exec( "ping -c 1 ".$job['server']['ip_address'] );
	if( $ping_check == 0 ) {
		if( $globals['dev'] == true ) {
			console_output( '-> Server: Online' );
		}

		// build remote ssh command
		$cmd  = "sshpass -p '".$job['server']['ssh_password']."' ";
		$cmd .= "ssh -o StrictHostKeyChecking=no ";
		$cmd .= $job['server']['ssh_username']."@".$job['server']['ip_address']." ";
		$cmd .= "-p ".$job['server']['ssh_port']." ";
		$cmd .= "'";
		$cmd .= "sudo touch /opt/installer_logged_in.txt; ";
		$cmd .= "sudo wget -O setup.sh https://cloudshield.io/dashboard/install_files/".$job['server_type']."_".$job['job_type'].".sh; ";
		
		if( $job['server_type'] == 'controller' ) {
			$cmd .= "sudo sed -i 's/INSTALL_MAIN_SERVER_PORT/".$job['cluster']['iptv_main_server_port']."/' setup.sh; ";
		} elseif( $job['server_type'] == 'proxy' ) {
			$cmd .= "sudo sed -i 's/INSTALL_MAIN_SERVER_DOMAIN/".$job['cluster']['iptv_main_server_domain']."/' setup.sh; ";
			$cmd .= "sudo sed -i 's/INSTALL_MAIN_SERVER_IP/".$job['cluster']['iptv_main_server_ip_address']."/' setup.sh; ";
			$cmd .= "sudo sed -i 's/INSTALL_MAIN_SERVER_PORT/".$job['cluster']['iptv_main_server_port']."/' setup.sh; ";
		}

		$cmd .= "sudo sed -i 's/INSTALL_CLUSTER_ID/".$job['cluster']['id']."/' setup.sh; ";

		$cmd .= "sudo bash setup.sh; ";
		// $cmd .= "sudo rm -rf setup.sh; ";

		if( $job['server_type'] == 'controller' ) {
			$cmd .= "sudo hostnamectl set-hostname controller; ";
		} elseif( $job['server_type'] == 'proxy' ) {
			$cmd .= "sudo hostnamectl set-hostname ".$job['server']['hostname']."; ";
		}

		$cmd .= "' ";
		// $cmd .= "sudo reboot;' ";
		$cmd .= "2>/dev/null";

		// set installer start time
		$installer_start = time();

		// execute remote ssh command
		if( $globals['dev'] == true ) {
			console_output( '-> Job: Installing CloudShield '.ucfirst( $job['server_type'] ) );
		}
		
		exec( $cmd );

		// set installer finish time
		$installer_finish = time();

		if( $globals['dev'] == true ) {
			console_output( '-> Installation Time: '.( $installer_finish - $installer_start ).' seconds' );
		}

		// did the installer work
		if( $globals['dev'] == true ) {
			console_output( '-> Job: Checking Installation Status' );
		}

		// open connection to port
	    $connection = @fsockopen( $job['server']['ip_address'], $job['cluster']['iptv_main_server_port'] );

	    // handle results
	    if( is_resource( $connection ) ) {
	    	// close the connection
	        fclose( $connection );

	        // post status to dashboard
			post_install_status( $job['id'], 'installed', 'server installed' );

	        if( $globals['dev'] == true ) {
				console_output( '-> Installation Complete' );
			}
	    } else {
	    	// post status to dashboard
			post_install_status( $job['id'], 'failed', 'installation failed' );

	        if( $globals['dev'] == true ) {
				console_output( '-> Installation Failed' );
			}
	    }
	} else {
		// post status to dashboard
		// post_install_status( $job['id'], 'failed', 'unable to reach server' );

		if( $globals['dev'] == true ) {
			console_output( '-> Server: Offline' );
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