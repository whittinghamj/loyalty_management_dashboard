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

// build nginx.conf for controller
print '# cloudshield.com v2 '."\n";
print ''."\n";
print 'limit_req_zone $binary_remote_addr zone=subshieldLimit-high:10m rate=10r/s; '."\n";
print 'limit_req_zone $binary_remote_addr zone=subshieldLimit-medium:10m rate=20r/s; '."\n";
print 'limit_req_zone $binary_remote_addr zone=subshieldLimit-low:10m rate=30r/s; '."\n";
print ''."\n";
print 'log_format subshield "$msec,$remote_addr,$scheme://$host$request_uri,$request_time,$status"; '."\n";
// print 'log_format upstream_logging "[$time_local] Remote IP: $remote_addr - Server Name: $server_name <-> Upstream: $upstream_addr - Request: $request - Respose Time: $upstream_response_time - Request Time: $request_time - Status: $status - Domain: $sent_http_x_domain OR $cookie_domain"; '."\n";
print 'log_format upstream_logging "Remote IP: $remote_addr - Server Name: $server_name <-> Upstream: $upstream_addr - Status: $status - Domain: $http_x_domain OR $sent_http_x_domain OR $cookie_domain"; '."\n";
print ' '."\n";

// uplink to origin server
print '# uplink to origin server '."\n";
print 'upstream origin_server { '."\n";
print '    ip_hash; '."\n";
print '    server '.$cluster['iptv_main_server_ip_address'].':'.$cluster['iptv_main_server_port'].'; '."\n";
print '} '."\n";
print ' '."\n";

// production proxy
print '# production proxy '."\n";
print 'server { '."\n";
print '	   server_name '.$server['hostname'].'.'.$cluster['iptv_main_server_domain'].'; '."\n";
print ' '."\n";
print '	   listen 80; # http port '."\n";
print '	   listen 443 ssl; # ssl port '."\n";
print '    listen 1372; # management port '."\n";

// custom listen port
if( $cluster['iptv_main_server_port'] != 80 ) {
	print '    listen '.$cluster['iptv_main_server_port'].'; # customer specific proxy port '."\n";
}
print ' '."\n";

// build upstream switch
print 'set $upstream_server origin_server; '."\n";
print ' '."\n";

// log files
print '    access_log /var/log/nginx/upstream_logging.log upstream_logging; '."\n";
print '    error_log /var/log/nginx/error.log crit; '."\n";
print ' '."\n";

// proxo options
print '    proxy_buffering off; '."\n";
print '    proxy_buffers 16 16k; '."\n";
print '    # proxy_buffer_size 8k; '."\n";
print '    # proxy_busy_buffers_size 8k; '."\n";
print '    proxy_max_temp_file_size 0; '."\n";
print ' '."\n";
print '    server_tokens off; '."\n";
print '    chunked_transfer_encoding off; '."\n";
print ' '."\n";
print '    real_ip_header X-Forwarded-For; '."\n";
print '    set_real_ip_from 0.0.0.0/0; '."\n";
print '    real_ip_recursive on; '."\n";
print ' '."\n";

// stalker portal
print '    # stalker portal '."\n";
print '    location =/c { '."\n";
print '        return 302 $scheme://$host:$server_port/c/; '."\n";
print '    } '."\n";
print ' '."\n";

// block xc client_portal
print '    # client portal '."\n";
print '    location =/client_area { '."\n";
print '        return 404; '."\n";
print '    } '."\n";
print '    location =/client_area/ { '."\n";
print '        return 404; '."\n";
print '    } '."\n";
print ' '."\n";

// main proxy 
print '    # main proxy '."\n";
print '    location / { '."\n";
print '    	   expires 60s; '."\n";
print '        # limit_req zone=subshieldLimit-OFF burst=20 nodelay; '."\n";
print ' '."\n";
print ' 	   resolver            1.1.1.1 1.0.0.1 8.8.8.8 8.8.4.4 208.67.222.222 208.67.220.220 valid=60s; '."\n";
print ' 	   resolver_timeout    5s; '."\n";
print ' '."\n";
print '        proxy_hide_header   Referer; '."\n";
print '        proxy_hide_header   Origin; '."\n";
print '        proxy_set_header    Referer                 ""; '."\n";
print '        proxy_set_header    Origin                  ""; '."\n";
print '         '."\n";
print '        add_header "Access-Control-Allow-Origin" "*"; '."\n";
print '        add_header "Access-Control-Allow-Methods" "GET, POST, PUT, DELETE"; '."\n";
print '        add_header "Access-Control-Allow-Headers" "DNT, User-Agent, X-Requested-With, If-Modified-Since, Cache-Control, Content-Type, Range, Origin, X-Requested-With, Content-Type, Accept, Api-Password"; '."\n";
print '        add_header "Access-Control-Expose-Headers" "Content-Length, Content-Range"; '."\n";
print '        add_header "Access-Control-Allow-Credentials" "true"; '."\n";
print '        add_header "Access-Control-Max-Age" "600"; '."\n";
print '        add_header "Vary" "Origin"; '."\n";
print ' 	   add_header "Cache-Control" "no-cache"; '."\n";
print '         '."\n";
print '        if ($request_method = OPTIONS) { '."\n";
print '            # Handle OPTIONS method '."\n";
print '            add_header Content-Length 0; '."\n";
print '            add_header Content-Type text/plain; '."\n";
print '            return 200; '."\n";
print '        } '."\n";
print ' '."\n";
print ' 	   types { '."\n";
print ' 	       application/vnd.apple.mpegurl m3u8; '."\n";
print ' 	       video/mp2t ts; '."\n";
print ' 	       text/html html; '."\n";
print ' 	       application/dash+xml mpd; '."\n";
print ' 	   } '."\n";
print ' '."\n";

// cookie check / modify upstream
// print '		   # is there a cookie '."\n";
// print '		   if ( $cookie_domain) { '."\n";
// print '		       set $upstream_server origin_$cookie_domain; '."\n";
// print '		   } '."\n";
// print ' '."\n";

// http or https
print '		   # ask main server for content / proxy call '."\n";
if( $cluster['enable_ssl_out'] == 'no' ) {
	print '        proxy_pass http://origin_server; '."\n";
} else {
	print '        proxy_pass https://origin_server; '."\n";
}

print ' '."\n";
print '        proxy_pass_request_headers on; '."\n";
print '        proxy_http_version 1.1; '."\n";
print '        proxy_set_header Upgrade $http_upgrade; '."\n";
print '        proxy_set_header Connection "upgrade"; '."\n";
print '        proxy_cache_bypass $http_upgrade; '."\n";
print '        proxy_set_header Referer $http_referer; '."\n";
print '        proxy_set_header X-Forwarded-For $remote_addr; '."\n";
print '        proxy_set_header X-Forwarded-Proto $scheme; '."\n";
print '        proxy_set_header Host $host:$server_port; '."\n";
print '        proxy_set_header X-Forwarded-Host $host:$server_port; '."\n";
print '        proxy_set_header X-Real-IP  $remote_addr; '."\n";
print '        proxy_set_header REMOTE_ADDR $remote_addr; '."\n";
print '        proxy_set_header User-Agent $http_user_agent; '."\n";
print '        proxy_read_timeout 36000s; '."\n";
print '        proxy_set_header Accept-Encoding ""; '."\n";
print '        proxy_redirect off; '."\n";
print ' '."\n";
print '        subs_filter_types "*"; '."\n";
print '        sub_filter_once off; '."\n";
print ' '."\n";
print '        # sub_filter "MAIN_SERVER_DOMAIN_NAME" "$host"; '."\n";
print '        # subs_filter "MAIN_SERVER_DOMAIN_NAME" "$host"; '."\n";
print ' '."\n";
print '        sub_filter "MAIN_SERVER_IP_ADDRESS" "'.$cluster['iptv_main_server_domain'].'"; '."\n";
print '        subs_filter "MAIN_SERVER_IP_ADDRESS" "'.$cluster['iptv_main_server_domain'].'"; '."\n";
print ' '."\n";
print '        sub_filter "PROXY_IP_ADDRESS" "'.$cluster['iptv_main_server_domain'].'"; '."\n";
print '        subs_filter "PROXY_IP_ADDRESS" "'.$cluster['iptv_main_server_domain'].'"; '."\n";
print ' '."\n";
print '        sub_filter "ADDITIONAL_SERVER_DOMAIN_2" "$host"; '."\n";
print '        subs_filter "ADDITIONAL_SERVER_DOMAIN_2" "$host"; '."\n";
print ' '."\n";
print '        sub_filter "ADDITIONAL_SERVER_DOMAIN_3" "$host"; '."\n";
print '        subs_filter "ADDITIONAL_SERVER_DOMAIN_3" "$host"; '."\n";
print ' '."\n";
print '        sub_filter "'.$server['hostname'].'.'.$cluster['iptv_main_server_domain'].'" "'.$cluster['iptv_main_server_domain'].'"; '."\n";
print '        subs_filter "'.$server['hostname'].'.'.$cluster['iptv_main_server_domain'].'" "'.$cluster['iptv_main_server_domain'].'"; '."\n";
print ' '."\n";
print '        # set_real_ip_from PROXY_IP_ADDRESS/32; # Ip/network of the reverse proxy '."\n";
print '        # real_ip_header X-Forwarded-For; '."\n";
print '    	   set_real_ip_from 0.0.0.0/0; '."\n";
print ' '."\n";
print '        # inline content rewrites '."\n";
print '        # subs_filter "Your STB is not supported" "Unsupported Device"; '."\n";
print '        # subs_filter "Nothing to see here. Move along now." "No Content Available"; '."\n";
print '        # subs_filter "Access Denied." "Access Denied."; '."\n";
print '        # subs_filter "Access Denied!!!" "Access Denied."; '."\n";
print '        # subs_filter "Hello" "Access Denied."; '."\n";
print '        subs_filter "Xtream Codes Reborn" "Access Denied."; '."\n";
print ' '."\n";
print '        recursive_error_pages on; '."\n";
print '        proxy_intercept_errors on; '."\n";
print ' '."\n";
print ' 	   error_page 401 404 /custom_404.html; '."\n";
print ' 	   location = /custom_404.html { '."\n";
print ' 	       root /var/www/html; '."\n";
print ' 		   internal; '."\n";
print ' 	   }'."\n";
print ' '."\n";
print '        error_page 301 302 303 307 308 = @handle_redirect; '."\n";
print '    } '."\n";
print ' '."\n";
print '    # proxied redirects '."\n";
print '    location @handle_redirect { '."\n";
print ' 	   resolver            1.1.1.1 1.0.0.1 8.8.8.8 8.8.4.4 208.67.222.222 208.67.220.220 valid=60s ipv6=off; '."\n";
print '        resolver_timeout 10s; '."\n";
print ' '."\n";
print '        set $saved_redirect_location $upstream_http_location; '."\n";
print '        proxy_set_header X-Real-IP  $remote_addr; '."\n";
print '        proxy_set_header X-Forwarded-For $remote_addr; '."\n";
print '        proxy_set_header Host $host; '."\n";
print '        proxy_pass $saved_redirect_location; '."\n";
print '        proxy_redirect off; '."\n";
print '        recursive_error_pages on; '."\n";
print '        proxy_intercept_errors on; '."\n";
print '        error_page 301 302 303 307 308 = @handle_redirect; '."\n";
print '    } '."\n";
print '    '."\n";
print '    location /nginx_status { '."\n";
print '        stub_status on; '."\n";
print '        access_log   off;'."\n";
print '        allow 127.0.0.1; '."\n";
print '        allow all; '."\n";
print '    } '."\n";
print '    '."\n";
print '    #ssl placeholder 1'."\n";
print '    #ssl placeholder 2'."\n";
print '    '."\n";
print '} '."\n";

