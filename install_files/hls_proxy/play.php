<?php

// error logging
ini_set( 'display_startup_errors', 1 );
ini_set( 'display_errors', 1 );
ini_set( 'error_reporting', E_ALL );
error_reporting( E_ALL );

// set headers
header( 'Content-Type: application/octet-stream' );
header( "Content-Transfer-Encoding: Binary" ); 

// includes
foreach( glob('./inc/*.php') as $filename ) {
    include_once( $filename );
}

// map vars
$vars                       = get( 'vars' );
$load_balancer_ip_hash      = get( '1' );
$load_balancer_ip           = base64_decode( $load_balancer_ip_hash );

// build segment url
$segment_url                = 'http://'.$load_balancer_ip.'/hlsr/'.$vars;

// rebuild url to remove additional vars
$segment_url = str_replace( '&1='.$load_balancer_ip_hash, '', $segment_url );

// download segment and sanity check
while(!$data = get_data_media( $segment_url ) ) {                        
    sleep(1);
}

// sanity check
if( !isset( $data ) || empty( $data ) ) {
    http_response_code(404);
}

// output
header( "Content-disposition: attachment; filename=\"/tmp/" . $data . "\"" ); 
readfile( $data ); 

exit;
?>