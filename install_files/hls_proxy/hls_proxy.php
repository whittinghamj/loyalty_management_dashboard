<?php

// error logging
ini_set( 'display_startup_errors', 1 );
ini_set( 'display_errors', 1 );
ini_set( 'error_reporting', E_ALL );
error_reporting( E_ALL );

// includes
foreach( glob('./inc/*.php') as $filename ) {
    include_once( $filename );
}

// map vars
$username       = get( 'username' );
$password       = get( 'password' );
$stream_id      = get( 'stream_id' );
$customer_ip    = $_SERVER['REMOTE_ADDR'];

// get main server to lb redirect url to find ip address
$playlist_redirect      = exec( 'curl -Ls -o /dev/null -w %{url_effective} http://MAIN_SERVER_IP_ADDRESS:MAIN_SERVER_PORT/live/'.$username.'/'.$password.'/'.$stream_id.'.m3u8' );

// sanity check
if( !isset( $playlist_redirect ) || empty( $playlist_redirect ) ) {
    http_response_code(404);
}

// find ip
if (preg_match('/\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}:\d{0,9}/', $playlist_redirect, $ip_match)) {
    $load_balancer_ip           	= $ip_match[0];
    $load_balancer_ip_hash         	= base64_encode( $load_balancer_ip );
}

// sanity check
if( !isset( $load_balancer_ip ) || empty( $load_balancer_ip ) ) {
    http_response_code(404);
}

// download playlist and sanity check
while(!$playlist = @file_get_contents( $playlist_redirect ) ) {                        
    sleep(1);
}

// sanity check
if( !isset( $playlist ) || empty( $playlist ) ) {
    http_response_code(404);
}

// break playlist into lines / array
$playlist = explode( "\n", $playlist );

// remove empty values
$playlist = array_filter( $playlist );

// get the vars from the first stream / ts line
if( !isset( $playlist[6] ) ) {

}
$line_bits = explode( "/", $playlist[6] );

// remove empty values
$line_bits = array_filter( $line_bits );

// build a storage array
$data[0]['username']                = $username;
$data[0]['password']                = $password;
$data[0]['customer_ip']             = $customer_ip;
$data[0]['stream_id']               = $stream_id;
$data[0]['load_balancer_ip']        = $load_balancer_ip;
$data[0]['token']                   = $line_bits[2];

// set headers
header( "Content-type: text/plain" );
header( "Content-Disposition: attachment; filename=playlist.m3u8" );

// sample hls line = /hlsr/ShNQBUZeFA4TBFYDUVFVB1FcDlMDAgQNA1cBBFJRWwNXBVlWVg4PVwBEThUWFhMBA1VoDFYTWwNbGBcUEQZKblhVGl4XBwQNBVZRFUlAFQ0PXRVfAh1DRQtQF1tAUg0GBAkaSBdTTkBUFAxWCT0FARRRVAAQCw9ADlgZQw0NZ1VUXFkKURQMFgFEThUMERFGWBp2FkJYE1BCYFACCg1XXV5WQURmWVpBRQ8NWRZCLRAGGhtHUFgVRwNAUENYQQsJBwYaSBdVWUFfEhBOR1hDIyAaG0dXSRVQDEdcDgxBAhNcAk1cFxoUXUI5EFIWFhMBA1VSFxALQwVAGBcMAxtnUl5fVgFWQl9bXxVADUdTQ0hAVVgLW0UORz1EXAVAWRoABwAMXBdL/Delta1372@gmail.com/bHxTTTyc/69/d8a8165fb3c6b4f8e581bb290d2a0a79/69_1450.ts

// samle rewrite hls line = http://1.2.3.4:1372/play.php?vars=

// build the new playlist
foreach( $playlist as $playlist_item ) {
    $new_playlist_item = str_replace( '/hlsr/', 'http://'.$_SERVER['HTTP_HOST'].'/play.php?vars=', $playlist_item );
    $new_playlist_item = str_replace( '.ts', '.ts&1='.$load_balancer_ip_hash, $new_playlist_item );
    print $new_playlist_item."\n";
}

exit;
?>