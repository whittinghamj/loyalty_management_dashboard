<?php

$incoming_url = $_SERVER['REQUEST_URI'];

// split the url
$url = parse_url( $incoming_url );

echo '<pre>';
print_r( $url );
die();

// create the new url with test in the path
$newUrl = $url['path']."?".$url['query'];
header( "Location:" .$newUrl );