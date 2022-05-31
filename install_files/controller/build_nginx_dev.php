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
$server_id = get( 'server_id' );

// get cluster
$cluster = get_cluster_admin( $cluster_id );

// get server
$server = get_server_admin( $server_id );

// get cluster proxies
$raw_proxies = get_servers_admin( $cluster_id , 'proxy' );

if( isset( $raw_proxies[0]['id'] ) && is_array( $raw_proxies ) ) {
	// create array
	$proxies = array();

	// remove proxies over 80% RAM
	foreach( $raw_proxies as $raw_proxy ) {
		$stats = json_decode( $raw_proxy['stats'], true );
		if( $stats['ram_usage'] < 80 && $raw_proxy['status_proxy'] == 'running' ) {
			$proxies[] = $raw_proxy;
		}
	}

	// sort $proxies by hostname
	usort( $proxies, function( $a, $b ) {
	    return $a['hostname'] <=> $b['hostname'];
	});

	// total proxies
	$total_proxies = count( $proxies );

	// calculate percentage per proxy
	if( $total_proxies > 0 ) {
		$percentage_per_proxy = ( 100 / $total_proxies );
		$percentage_per_proxy = number_format( $percentage_per_proxy, 10 );
		$percentage_per_proxy = floor( $percentage_per_proxy * 100 ) / 100;
	} else {
		$percentage_per_proxy = 100;
	}

	// percentage sanity check
	$total_percentage = ( $percentage_per_proxy * $total_proxies );
	if( $total_percentage > '100' ) {
		$percentage_per_proxy = lower_percentage( $percentage_per_proxy );
	}

	// percentage sanity check
	$total_percentage = ( $percentage_per_proxy * $total_proxies );
	if( $total_percentage > '100' ) {
		$percentage_per_proxy = lower_percentage( $percentage_per_proxy );
	}

	// percentage sanity check
	$total_percentage = ( $percentage_per_proxy * $total_proxies );
	if( $total_percentage > '100' ) {
		$percentage_per_proxy = lower_percentage( $percentage_per_proxy );
	}

	// percentage sanity check
	$total_percentage = ( $percentage_per_proxy * $total_proxies );
	if( $total_percentage > '100' ) {
		$percentage_per_proxy = lower_percentage( $percentage_per_proxy );
	}

	// percentage sanity check
	$total_percentage = ( $percentage_per_proxy * $total_proxies );
	if( $total_percentage > '100' ) {
		$percentage_per_proxy = lower_percentage( $percentage_per_proxy );
	}

	// echo 'Total Active Proxies: '.$total_proxies.' <br>';
	// echo 'Percentage Per Proxy: '.$percentage_per_proxy.'% <br>';
	// echo 'Total Combined Percentage: '.$total_percentage.'% <hr>';
}

// build nginx.conf for controller
print 'user www-data; '."\n";
print 'worker_processes auto; '."\n";
print 'pid /run/nginx.pid; '."\n";
print 'include /etc/nginx/modules-enabled/*.conf; '."\n";
print ' '."\n";
print 'worker_rlimit_nofile 1048576; '."\n";
print 'events { '."\n";
print '		worker_connections 1048576; '."\n";
print '		multi_accept on; '."\n";
print '		accept_mutex on; '."\n";
print '		use epoll; '."\n";
print '} '."\n";
print ' '."\n";
print 'http { '."\n";
print '		# GeoIP databases '."\n";
print '     geoip_country /usr/share/GeoIP/GeoIP.dat; '."\n";
print '     geoip_city /usr/share/GeoIP/GeoIPCity.dat; '."\n";
print ' '."\n";
print '		sendfile on; '."\n";
print '		tcp_nopush on; '."\n";
print '		tcp_nodelay on; '."\n";
print '		keepalive_timeout 65; '."\n";
print '		types_hash_max_size 2048; '."\n";
print ' '."\n";
print '		include /etc/nginx/mime.types; '."\n";
print '		default_type application/octet-stream; '."\n";
print ' '."\n";
print '		ssl_protocols TLSv1 TLSv1.1 TLSv1.2; # Dropping SSLv3, ref: POODLE '."\n";
print '		ssl_prefer_server_ciphers on; '."\n";
print ' '."\n";
print ' 	resolver             1.1.1.1 1.0.0.1 8.8.8.8 8.8.4.4 208.67.222.222 208.67.220.220 valid=60s ipv6=off; '."\n";
print ' 	resolver_timeout     5s; '."\n";
print ' '."\n";
print '		# access_log /var/log/nginx/access.log; '."\n";
print '		error_log /var/log/nginx/error.log crit; '."\n";
print ' '."\n";
print '		gzip off; '."\n";
print ' '."\n";
print ' 	# allow the server to close connection on non responding client, this will free up memory '."\n";
print ' 	reset_timedout_connection on; '."\n";
print ' '."\n";
print ' 	# if client stop responding, free up memory -- default 60 '."\n";
print ' 	send_timeout 2; '."\n";
print ' '."\n";
print '		include /etc/nginx/conf.d/*.conf; '."\n";
print ' '."\n";

// loop over proxies for redirect lines
print '		# pick random proxy '."\n";
print '		split_clients "${remote_addr}" $destination { '."\n";
if( isset( $proxies[0] ) ) {
	foreach( $proxies as $proxy ) {
		if( $proxy['status'] == 'installed' && $proxy['status_server'] == 'online' && $proxy['status_proxy'] == 'running' ) {
			// http or https
			if( $cluster['enable_ssl'] == 'no' ) {
				// forward to proxy ip and customer defined port
				// print '		'.$percentage_per_proxy.'% http://'.$proxy['ip_address'].':'.$cluster['iptv_main_server_port'].'; # '.$proxy['hostname']."\n";

				// forward to proxy domain and customer defined port
				print '		'.$percentage_per_proxy.'% http://'.$proxy['hostname'].'.'.$cluster['iptv_main_server_domain'].':'.$cluster['iptv_main_server_port'].'; '."\n";
			} else {
				// forward to proxy domain and https port
				print '		'.$percentage_per_proxy.'% https://'.$proxy['hostname'].'.'.$cluster['iptv_main_server_domain'].'; '."\n";
			}
		}
	}
}
print '	'."\n";
print '		} '."\n";
print ' '."\n";

// uplink to origin server
print '# uplink to origin server '."\n";
print 'upstream origin_server { '."\n";
print '    ip_hash; '."\n";
print '    server '.$cluster['iptv_main_server_ip_address'].':'.$cluster['iptv_main_server_port'].'; '."\n";
print '} '."\n";
print ' '."\n";

// build uplinks to each proxy
/*
if( isset( $proxies[0] ) ) {
	foreach( $proxies as $proxy ) {
		if( $proxy['type'] == 'proxy' && $proxy['status'] == 'installed' && $proxy['status_server'] == 'online' && $proxy['status_proxy'] == 'running' ) {
			print '# uplink to '.$proxy['hostname'].' server '."\n";
			print 'upstream '.$proxy['hostname'].' { '."\n";
			print '    ip_hash; '."\n";
			print '    server '.$proxy['ip_address'].':'.$cluster['iptv_main_server_port'].'; '."\n";
			print '} '."\n";
			print ' '."\n";
		}
	}
}
*/

// loop over geo_countries routing
print '		# GEO IP Filtering '."\n";
print '		map $geoip_country_code $proxy_traffic { '."\n";
print '    	default yes; '."\n";
print ' '."\n";
if( isset( $cluster['geo_countries'][0] ) ) {
	foreach( $cluster['geo_countries'] as $geo_country ) {
		// print '		'.$geo_country.' yes; '."\n";
	}
}
print '} '."\n";
print ' '."\n";

// main server block
print '		server { '."\n";
print '	    	server_name '.$cluster['iptv_main_server_domain'].' '.$server['hostname'].'.'.$cluster['iptv_main_server_domain'].'; '."\n";
print ' '."\n";

// hide server_tokens
print '			server_tokens none; '."\n";
print ' '."\n";

// setup listen ports
print '			listen 80; # http port '."\n";
print '			listen 443 ssl; # ssl port '."\n";
print '			listen 1372; # management port '."\n";
if( $cluster['iptv_main_server_port'] != 80 ) {
	print '     	listen '.$cluster['iptv_main_server_port'].'; # customer specific proxy port '."\n";
}
print ' '."\n";

// set custom headers
print ' add_header X-cs-requested-domain $host; '."\n";

// grab get.php
print '    location ~ ^/(get.php|player_api.php|panel_api.php|xmltv.php) { '."\n";
print '    	   expires 10s; '."\n";
print ' '."\n";
print ' 	   resolver            1.1.1.1 1.0.0.1 8.8.8.8 8.8.4.4 208.67.222.222 208.67.220.220 valid=60s; '."\n";
print ' 	   resolver_timeout    5s; '."\n";
print ' '."\n";
print '		   # ask main server for content / proxy call '."\n";

// live or maintenance mode
if( $cluster['state'] == 'live' ) {
	// http or https
	if( $cluster['enable_ssl_out'] == 'no' ) {
		print '        proxy_pass http://origin_server; '."\n";
	} else {
		print '        proxy_pass https://origin_server; '."\n";
	}
} else {
	print '        return 404; '."\n";
}

print ' '."\n";
print '    } '."\n";

// block client_area
print '    		# block client portal '."\n";
print '    		location =/client_area { '."\n";
print '        		return 404; '."\n";
print '    		} '."\n";
print '    		location =/client_area/ { '."\n";
print '        		return 404; '."\n";
print '    		} '."\n";
print ' '."\n";

// is stalker enabled
if( $cluster['enable_stalker'] == 'no') {
	print '    		# stalker portal '."\n";
	print '    		location =/c { '."\n";
	print '        		return 404; '."\n";
	print '    		} '."\n";
	print '    		location =/c/ { '."\n";
	print '        		return 404; '."\n";
	print '    		} '."\n";
	print ' '."\n";
}

// geo routing
print '    		# is country allowed to proxy or should we redirect '."\n";
print '    		if ($proxy_traffic = no) { '."\n";
print ' '."\n";
print '        		# forward to main server due to GEO IP rules '."\n";
print '        		# rewrite (.*) http://'.$cluster['iptv_main_server_ip_address'].':'.$cluster['iptv_main_server_port'].'$1 redirect; '."\n";
print '    		} '."\n";
print ' '."\n";

// default location
print '  		location /  { '."\n";
print '    			expires 1s; '."\n";

// live or maintenance mode
if( $cluster['state'] == 'live' ) {
	print '    			return 302 $destination$request_uri; '."\n";
} else {
	print ' 			root /var/www/html; '."\n";
}
print '  		} '."\n";
print ' '."\n";

// catch m3u8 for hls
print '    		location ~ [^/]\.m3u8(/|$) { '."\n";
print '        		expires 10s; '."\n";
print '        		resolver            1.1.1.1 1.0.0.1 8.8.8.8 8.8.4.4 208.67.222.222 208.67.220.220 valid=60s; '."\n";
print '        		resolver_timeout    5s; '."\n";
if( $cluster['enable_ssl_out'] == 'no' ) {
	print '        		proxy_pass http://origin_server; '."\n";
} else {
	print '        		proxy_pass https://origin_server; '."\n";
}
print '        	} '."\n";
print ' '."\n";

// nginx stats
print '    		location /nginx_status { '."\n";
print '        		stub_status on; '."\n";
print '        		access_log   off;'."\n";
print '        		allow 127.0.0.1; '."\n";
print '        		allow all; '."\n";
print '    		} '."\n";
print ' '."\n";

// ssl hooks
print ' 		#ssl placeholder 1'."\n";
print ' 		#ssl placeholder 2'."\n";
print ' '."\n";
print '  	} '."\n";
print '} '."\n";
