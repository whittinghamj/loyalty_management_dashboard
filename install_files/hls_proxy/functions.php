<?php

function get_data( $url ) {
    // make the curl request
    $curl = curl_init();
    curl_setopt( $curl, CURLOPT_RETURNTRANSFER, 1 );
    curl_setopt( $curl, CURLOPT_FOLLOWLOCATION, 0 );
    curl_setopt( $curl, CURLOPT_SSL_VERIFYPEER, false );
    curl_setopt( $curl, CURLOPT_TIMEOUT, 30 );
    curl_setopt( $curl, CURLOPT_URL, $url );
    $data = curl_exec( $curl) ;

    // return data
    return $data;
}

function get_data_media( $url ) {
    // make the curl request
    $curl = curl_init();
    curl_setopt( $curl, CURLOPT_FOLLOWLOCATION, true );
    curl_setopt( $curl, CURLOPT_SSL_VERIFYPEER, false );
    curl_setopt( $curl, CURLOPT_TIMEOUT, 30 );
    curl_setopt( $curl, CURLOPT_URL, $url );
    $data = curl_exec($curl);

    // return data
    return $data;
}

function stripslashes_deep( $value ){
    // $value = is_array( $value ) ? array_map( 'stripslashes_deep', $value ) : stripslashes( $value );
    // return $value;

    /*
    foreach( $value as &$val ) {
        if( is_array( $val ) ) {
            $val = unstrip_array( $val );
        }else{
            $val = stripslashes( $val );
        }
    }

    return $value;
    */

    return map_deep( $value, 'stripslashes_from_strings_only' );
}

function stripslashes_from_strings_only( $value )
{
    return is_string( $value ) ? stripslashes( $value ) : $value;
}

function map_deep( $value, $callback )
{
    if ( is_array( $value ) ) {
        foreach ( $value as $index => $item ) {
            $value[ $index ] = map_deep( $item, $callback );
        }
    }
 
    return $value;
}

function json_output( $data )
{
	$data 					= json_encode( $data );
	echo $data;
	die();
}

function encrypt( $string, $key = 25 )
{
    $result = '';
    for( $i = 0, $k = strlen( $string ); $i < $k; $i++ ) {
        $char = substr( $string, $i, 1 );
        $keychar = substr( $key, ($i % strlen( $key ) ) -1, 1 );
        $char = chr( ord( $char ) + ord( $keychar ) );
        $result .= $char;
    }
    return base64_encode( $result );
}

function decrypt( $string, $key = 25 )
{
    $result = '';
    $string = base64_decode( $string );
    for( $i = 0, $k = strlen( $string ); $i < $k; $i++ ) {
        $char = substr( $string, $i, 1 );
        $keychar = substr( $key, ( $i % strlen( $key ) ) -1, 1 );
        $char = chr( ord( $char ) - ord( $keychar ) );
        $result .= $char;
    }
    return $result;
}

function get( $key = null )
{
    if( is_null( $key ) ) {
        return $_GET;
    }
    $get = isset( $_GET[$key] ) ? $_GET[$key] : null;
    if ( is_string( $get) ) {
        $get = trim( $get );
    }
    // $get = addslashes($get);
    return $get;
}

function post( $key = null )
{
	if ( is_null($key) ) {
		return $_POST;
	}
	$post = isset($_POST[$key]) ? $_POST[$key] : null;
	if ( is_string($post) ) {
		$post = trim($post);
	}

    $post = addslashes($post);
	return $post;
}

function request( $key = null )
{
    if ( is_null($key) ) {
        return $_REQUEST;
    }
    $request = isset($_REQUEST[$key]) ? $_REQUEST[$key] : null;
    if ( is_string($request) ) {
        $request = trim($request);
    }
    // $get = addslashes($get);
    return $request;
}

function debug($input)
{
	$output = '<pre>';
	if ( is_array($input) || is_object($input) ) {
		$output .= print_r($input, true);
	} else {
		$output .= $input;
	}
	$output .= '</pre>';
	echo $output;
}

function random_string( $length = 10 )
{
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen( $characters );
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand( 0, $charactersLength - 1 )];
    }
    return $randomString;
}