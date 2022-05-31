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

// sanity check
if( empty( $cluster_id) || empty( $server_id ) ) {
	http_response_code( 404 );
	die();
}

// get cluster
$cluster = get_cluster_admin( $cluster_id );

// get server
$server = get_server_admin( $server_id );

// get domain
$domain_name = get_domain_name_by_cluster_admin( $cluster_id );

// get dns records
$dns_records = get_dns_records_admin( $domain_name['powerdns_id'] );

// generate random keys
$cs_uuid 					= random_string( 16 );
$cs_ray_id 					= random_string( 32 );
$cs_report_abuse_string 	= random_string( 128 );
$cs_ray_id 					= random_string( 16 );

// build nginx.conf for controller
print '# cloudshield.com v3 '."\n";
print ' '."\n";

# tracking cookie
print 'add_header Set-Cookie "domain=$host;Domain=.'.$cluster['iptv_main_server_domain'].';Max-Age=100000"; '."\n";
print 'add_header Set-Cookie "__cfuuid='.$cs_uuid.'_$host;Domain=.'.$cluster['iptv_main_server_domain'].';Max-Age=100000"; '."\n";
print 'add_header CF-Cache-Status: DYNAMIC; '."\n";
print 'add_header CF-Request-ID: '.$cs_ray_id.'; '."\n";
print 'add_header Report-To: ["report_abuse":"https:\/\/a.nel.cloudflare.com\/report?s='.$cs_report_abuse_string.']; '."\n";
print 'add_header CF-RAY: '.$cs_ray_id.'-DUB; '."\n";
print ' '."\n";

# public default server
print 'server { '."\n";

// server name
print '    server_name _; '."\n";
print ' '."\n";

// default listen ports
print '    listen 80 default_server; '."\n";

// custom listen port
if( $cluster['iptv_main_server_port'] != 80 ) {
	print '    listen '.$cluster['iptv_main_server_port'].' default_server; '."\n";
}
print ' '."\n";

// block xc client_portal
print '    location ~ /client_area/?(.*)$ { return 404; } '."\n";
print ' '."\n";

# default location
print '    location / { '."\n";

# inline content rewrites
print '        sub_filter_once off; '."\n";
print '        subs_filter_types "*"; '."\n";
print ' '."\n";
print '        # sub_filter "'.$cluster['iptv_main_server_ip_address'].'" "'.$cluster['iptv_main_server_domain'].'"; '."\n";
print '        # subs_filter "'.$cluster['iptv_main_server_ip_address'].'" "'.$cluster['iptv_main_server_domain'].'"; '."\n";
print ' '."\n";
print '        # sub_filter "'.$server['ip_address'].'" "'.$cluster['iptv_main_server_domain'].'"; '."\n";
print '        # subs_filter "'.$server['ip_address'].'" "'.$cluster['iptv_main_server_domain'].'"; '."\n";
print ' '."\n";
print '        # sub_filter "'.$server['hostname'].'.'.$cluster['iptv_main_server_domain'].'" "'.$cluster['iptv_main_server_domain'].'"; '."\n";
print '        # subs_filter "'.$server['hostname'].'.'.$cluster['iptv_main_server_domain'].'" "'.$cluster['iptv_main_server_domain'].'"; '."\n";
print ' '."\n";

# http or https connection
if( $cluster['enable_ssl_out'] == 'no' ) {
	print '        proxy_pass http://'.$cluster['iptv_main_server_ip_address'].':'.$cluster['iptv_main_server_port'].'; '."\n";
} else {
	print '        proxy_pass https://'.$cluster['iptv_main_server_ip_address'].':'.$cluster['iptv_main_server_port'].'; '."\n";
}
print '    } '."\n";
print ' '."\n";

# nginx stats
print '    location /nginx_status { stub_status on; access_log off; allow all; } '."\n";
print ' '."\n";

# ssl placeholder
print '    #ssl placeholder 1'."\n";
print '    #ssl placeholder 2'."\n";
print '} '."\n";
print ' '."\n";

// lets look for proxied domains to build
foreach( $dns_records as $dns_record ) {
	// only work with records marked as 'proxied'
	if( $dns_record['proxied'] == 'yes' ) {
		# build server for clustered domain 
		print 'server { '."\n";

		// server name
		print '    server_name '.$dns_record['name'].'; '."\n";
		print ' '."\n";

		// default listen ports
		print '    listen 80; '."\n";
		print '    # listen 443 ssl; '."\n";

		// custom listen port
		if( $cluster['iptv_main_server_port'] != 80 ) {
			print '    listen '.$cluster['iptv_main_server_port'].'; '."\n";
		}
		print ' '."\n";

		// block xc client_portal
		print '    location ~ /client_area/?(.*)$ { return 404; } '."\n";
		print ' '."\n";

		# default location
		print '    location / { '."\n";

		# inline content rewrites
		print '        sub_filter_once off; '."\n";
		print '        subs_filter_types "*"; '."\n";
		print ' '."\n";
		print '        # sub_filter "'.$cluster['iptv_main_server_ip_address'].'" "'.$cluster['iptv_main_server_domain'].'"; '."\n";
		print '        # subs_filter "'.$cluster['iptv_main_server_ip_address'].'" "'.$cluster['iptv_main_server_domain'].'"; '."\n";
		print ' '."\n";
		print '        # sub_filter "'.$server['ip_address'].'" "'.$cluster['iptv_main_server_domain'].'"; '."\n";
		print '        # subs_filter "'.$server['ip_address'].'" "'.$cluster['iptv_main_server_domain'].'"; '."\n";
		print ' '."\n";
		print '        # sub_filter "'.$server['hostname'].'.'.$cluster['iptv_main_server_domain'].'" "'.$cluster['iptv_main_server_domain'].'"; '."\n";
		print '        # subs_filter "'.$server['hostname'].'.'.$cluster['iptv_main_server_domain'].'" "'.$cluster['iptv_main_server_domain'].'"; '."\n";
		print ' '."\n";

		# http or https connection
		if( $cluster['enable_ssl_out'] == 'no' ) {
			print '        proxy_pass http://'.$dns_record['content'].':'.$cluster['iptv_main_server_port'].'; '."\n";
		} else {
			print '        proxy_pass https://'.$dns_record['content'].':'.$cluster['iptv_main_server_port'].'; '."\n";
		}
		print '    } '."\n";
		print ' '."\n";

		# nginx stats
		print '    location /nginx_status { stub_status on; access_log off; allow all; } '."\n";
		print ' '."\n";

		# ssl placeholder
		print '    #ssl placeholder 1'."\n";
		print '    #ssl placeholder 2'."\n";
		print '} '."\n";
		print ' '."\n";
	}
}
print ' '."\n";