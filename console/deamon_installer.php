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

$globals['dev'] = true;

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

// is deamon already running
$pid = exec( "ps aux | grep 'deamon_installer.php' | grep -v 'grep' | grep -v '/bin/sh' | grep -v 'color=auto' | grep -v 'deamon_installer.log' | wc -l" );
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
		$job = @file_get_contents( 'https://cloudshield.io/dashboard/api/?c=admin_get_deployment_queue' );
		$job = json_decode( $job, true );

		if( isset( $job['id'] ) ) {
			// post status to dashboard
			post_install_status( $job['id'], 'installing', '' );

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
		        console_output( 'Server Type: '.$job['server_type'] );
		        console_output( 'Job Type: '.$job['job_type'] );
		        console_output( '========================================' );
		        console_output( '' );
		    }

			if( $online_status == true ) {
				if( $globals['dev'] == true ) {
					console_output( '-> Server Status: Online' );
				}

				// build ssh command to grand sudo access
				$auth_cmd  = "sshpass -p '".$job['server']['ssh_password']."' ";
				$auth_cmd .= "ssh -o StrictHostKeyChecking=no ";
				$auth_cmd .= $job['server']['ssh_username']."@".$job['server']['ip_address']." ";
				$auth_cmd .= "-p ".$ssh_port." ";
				$auth_cmd .= "'";
				$auth_cmd .= "echo ".$job['server']['ssh_password']." | sudo -S wget -O /root/sudo.sh https://cloudshield.io/dashboard/install_files/build_sudo.php?server_id=".$job['server']['id']."; echo ".$job['server']['ssh_password']." | sudo -S bash /root/sudo.sh;";
				$auth_cmd .= "' ";
				$auth_cmd .= "2>/dev/null";

				if( $globals['dev'] == true ) {
					console_output( '-> Job: Authenticating '.$job['server']['ssh_username'] );
				}
				exec( $auth_cmd );

				// build remote ssh command
				$cmd  = "sshpass -p '".$job['server']['ssh_password']."' ";
				$cmd .= "ssh -o StrictHostKeyChecking=no ";
				$cmd .= $job['server']['ssh_username']."@".$job['server']['ip_address']." ";
				$cmd .= "-p ".$ssh_port." ";
				$cmd .= "'";

				// the installer was able to login
				$cmd .= "sudo touch /opt/installer_logged_in.txt; ";
				
				// save cluster_id and server_id for later / future use
				$cmd .= 'sudo echo "'.$job['cluster']['id'].'" > /opt/cluster_id.txt; ';
				$cmd .= 'sudo echo "'.$job['server']['id'].'" > /opt/server_id.txt; ';
				
				// update /etc/hosts
				$cmd .= 'sudo echo "localhost '.$job['server']['hostname'].'" > /etc/hosts; ';
				$cmd .= 'sudo echo "127.0.0.1 '.$job['server']['hostname'].'" >> /etc/hosts; ';
				$cmd .= 'sudo echo "'.$job['server']['ip_address'].' '.$job['server']['hostname'].'" >> /etc/hosts; ';

				// update /etc/resolv.conf
				// $cmd .= "sudo apt-get install -yy resolvconf; ";
				// $cmd .= "sudo systemctl start resolvconf.service; ";
				// $cmd .= "sudo systemctl enable resolvconf.service; ";
				// $cmd .= "sudo systemctl status resolvconf.service; ";
				// $cmd .= "sudo wget -O /etc/resolvconf/resolv.conf.d/head https://cloudshield.io/dashboard/install_files/resolv.txt; ";
				// $cmd .= "sudo systemctl restart resolvconf.service; ";

				// update apt source list
				$cmd .= "sudo wget -O /etc/apt/sources.list https://cloudshield.io/dashboard/install_files/ubuntu_18_apt_sources.txt; ";

				// download setup.sh
				$cmd .= "sudo wget -O setup.sh https://cloudshield.io/dashboard/install_files/".$job['server_type']."_".$job['job_type'].".sh; ";
				
				// prep setup.sh with install vars
				if( $job['server_type'] == 'controller' ) {
					$cmd .= "sudo sed -i 's/INSTALL_MAIN_SERVER_PORT/".$job['cluster']['iptv_main_server_port']."/' setup.sh; ";
				} elseif( $job['server_type'] == 'proxy' ) {
					$cmd .= "sudo sed -i 's/INSTALL_MAIN_SERVER_DOMAIN/".$job['cluster']['iptv_main_server_domain']."/' setup.sh; ";
					$cmd .= "sudo sed -i 's/INSTALL_MAIN_SERVER_IP/".$job['cluster']['iptv_main_server_ip_address']."/' setup.sh; ";
					$cmd .= "sudo sed -i 's/INSTALL_MAIN_SERVER_PORT/".$job['cluster']['iptv_main_server_port']."/' setup.sh; ";
				}

				$cmd .= "sudo sed -i 's/INSTALL_CLUSTER_ID/".$job['cluster']['id']."/' setup.sh; ";
				$cmd .= "sudo sed -i 's/INSTALL_SERVER_ID/".$job['server']['id']."/' setup.sh; ";

				// run setup.sh
				$cmd .= "sudo bash setup.sh; ";
				
				// remove setup.sh
				$cmd .= "sudo rm -rf setup.sh; ";

				// configure ssl
				$cmd .= "sudo wget -O /opt/ssl.sh https://cloudshield.io/dashboard/install_files/build_ssl.php; ";
				$cmd .= "sudo sed -i 's/SERVER_HOSTNAME/".$job['server']['hostname']."/' /opt/ssl.sh; ";
				$cmd .= "sudo sed -i 's/INSTALL_MAIN_SERVER_DOMAIN/".$job['cluster']['iptv_main_server_domain']."/' /opt/ssl.sh; ";
				$cmd .= "sudo bash /opt/ssl.sh; ";
				$cmd .= "sudo nginx -s reload; ";

				// check if certbot is installed
				// $cmd .= "command -v certbot >/dev/null 2>&1 || { sudo add-apt-repository ppa:certbot/certbot -y; sudo apt-get install -yy python-certbot-nginx; }; ";

				// certbot ssl
				// $cmd .= "sudo service nginx stop; ";
				// $cmd .= "sudo killall nginx; ";
				// $cmd .= "sudo pkill nginx; ";

				// ssl for hotname.domain.com
				// $cmd .= "sudo certbot --agree-tos --no-redirect --no-eff-email --nginx -m webmaster@".$job['cluster']['iptv_main_server_domain']." --nginx -d ".$job['server']['hostname'].".".$job['cluster']['iptv_main_server_domain']." -d ".$job['server']['hostname'].".".$job['cluster']['iptv_main_server_domain']." -n; ";

				// ssl for TLD if server is a controller
				// if( $job['server_type'] == 'controller' ) {
				// 	$cmd .= "sudo certbot --agree-tos --no-redirect --no-eff-email --nginx -m webmaster@".$job['cluster']['iptv_main_server_domain']." --nginx -d ".$job['cluster']['iptv_main_server_domain']." -d ".$job['cluster']['iptv_main_server_domain']." -n; ";
				// }

				// $cmd .= "sudo certbot renew; ";
				// $cmd .= "sudo pkill nginx; ";
				// $cmd .= "sudo service nginx start; ";

				// update custom_404.html
				$cmd .= "sudo wget -O /var/www/html/custom_404.html https://cloudshield.io/dashboard/install_files/custom_404.html; ";

				// update maintenance.html
				$cmd .= "sudo wget -O /var/www/html/index.html https://cloudshield.io/dashboard/install_files/maintenance.html; ";

				// update firewall
				$cmd .= "sudo wget -O /opt/firewall.sh https://cloudshield.io/dashboard/install_files/build_firewall.php?cluster_id=".$job['cluster']['id']."; ";
				$cmd .= "sudo bash /opt/firewall.sh; ";

				// auto controller update
				if( $job['server_type'] == 'controller' ) {
					$cmd .= "sudo wget -O /opt/controller_auto_reload.sh https://cloudshield.io/dashboard/install_files/controller_auto_reload.sh; ";
				}

				# install crontab
				if( $job['server_type'] == 'controller' ) {
					$cmd .= "sudo wget -O /tmp/crontab.txt https://cloudshield.io/dashboard/install_files/crontab_controller.txt; ";
					$cmd .= "sudo crontab /tmp/crontab.txt; ";
				} else {
					$cmd .= "sudo wget -O /tmp/crontab.txt https://cloudshield.io/dashboard/install_files/crontab.txt; ";
					$cmd .= "sudo crontab /tmp/crontab.txt; ";
				}

				// set the hostname
				$cmd .= "sudo hostnamectl set-hostname ".$job['server']['hostname'].".".$job['cluster']['iptv_main_server_domain']."; ";

				// reboot the server
				// $cmd .= "sudo reboot;' ";
				
				// the installer has finished
				$cmd .= "sudo touch /opt/installer_finished.txt; ";

				$cmd .= "' ";

				// send the output the /dev/null
				// $cmd .= "2>/dev/null";

				// set installer start time
				$installer_start = time();

				if( $globals['dev'] == true ) {
					console_output( '-> Job: Installing CloudShield '.ucfirst( $job['server_type'] ) );
				}
				
				if( $globals['dev'] == true ) {
					// console_output( '' );
					// console_output( '' );
					// echo $cmd;
					// console_output( '' );
					// console_output( '' );
				}

				// execute remote ssh commands
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