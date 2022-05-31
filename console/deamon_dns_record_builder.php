<?php

// error logging
ini_set( 'display_startup_errors', 1 );
ini_set( 'display_errors', 1 );
ini_set( 'error_reporting', E_ALL );
error_reporting( E_ALL );

// vars
ini_set("default_socket_timeout", 15);
ini_set("memory_limit", -1);
$globals['basedir']         = '/home/cloudshield/public_html/dashboard/';

// include main functions
include( $globals['basedir'].'includes/db.php' );
include( $globals['basedir'].'includes/globals.php' );
include( $globals['basedir'].'includes/functions.php' );

// get domains to check and sync
$query          = $conn->query( "
    SELECT * 
    FROM `domain_names` 
" );
$data           = $query->fetchAll( PDO::FETCH_ASSOC );

$domains = array();
$count = 0;
foreach( $data as $bit ) {
    $domains[$count]                       = $bit;
    $domains[$count]['dns_records']        = get_dns_records_admin( $bit['powerdns_id'] );

    $count++;
}

foreach( $domains as $domain ){
	if( $globals['dev'] == true ) {
        console_output( '========================================' );
        console_output( 'Domain Name: '.$domain['domain_name'] );
        console_output( '========================================' );
        console_output( '' );
    }

    // work with dns records
    foreach( $domain['dns_records'] as $dns_record ) {
        // dns or proxied record
        if( $dns_record['proxied'] == 'no' ) {
            if( $globals['dev'] == true ) {
                console_output( ' -| DNS - Record: '.$dns_record['type'].' Name: '.$dns_record['name'].' Content: '.$dns_record['content'] );
            }

            // check for existing record
            $query = $conn->query( "
                SELECT `id` 
                FROM `cloudshield_dns_server`.`records` 
                WHERE `type` = '".$dns_record['type']."' 
                AND `name` = '".$dns_record['name']."' 
                AND `content` = '".$dns_record['content']."' 
                AND `user_id` = '".$domain['user_id']."' 
            " );
            $existing_dns_record        = $query->fetch( PDO::FETCH_ASSOC );

            // if the record doesn ot exist, add it
            if( !isset( $existing_dns_record['id'] ) ) {
                $insert = $conn->exec( "INSERT INTO `cloudshield_dns_server`.`records` 
                    (`user_id`,`domain_id`,`name`,`type`,`content`,`ttl`,`server_type`)
                    VALUE
                    ('".$domain['user_id']."', 
                    '".$domain['powerdns_id']."', 
                    '".$dns_record['name']."', 
                    '".$dns_record['type']."', 
                    '".$dns_record['content']."', 
                    '120',
                    '".$dns_record['server_type']."'
                )" );
            }
        } else {
            if( $globals['dev'] == true ) {
                console_output( ' -| CLUSTER - Record: '.$dns_record['type'].' Name: '.$dns_record['name'].' Content: '.$dns_record['content'] );
            }

            // get proxies for this domains cluster to build cluster dns round robin
            $proxies                = get_servers_admin( $domain['cluster_id'] , 'proxy' );

            // check if each dns record has an assigned proxy ip for this round robin
            foreach( $proxies as $proxy ) {
                // check for existing record
                $query = $conn->query( "
                    SELECT `id` 
                    FROM `cloudshield_dns_server`.`records` 
                    WHERE `type` = '".$dns_record['type']."' 
                    AND `name` = '".$dns_record['name']."' 
                    AND `content` = '".$proxy['ip_address']."' 
                    AND `user_id` = '".$domain['user_id']."' 
                " );
                $existing_dns_record        = $query->fetch( PDO::FETCH_ASSOC );

                // if the record doesn ot exist, add it
                if( !isset( $existing_dns_record['id'] ) ) {
                    $insert = $conn->exec( "INSERT INTO `cloudshield_dns_server`.`records` 
                        (`user_id`,`domain_id`,`name`,`type`,`content`,`ttl`)
                        VALUE
                        ('".$domain['user_id']."', 
                        '".$domain['powerdns_id']."', 
                        '".$dns_record['name']."', 
                        '".$dns_record['type']."', 
                        '".$proxy['ip_address']."', 
                        '120'
                    )" );
                }
            }
        }
    }

    if( $globals['dev'] == true ) {
        console_output( '' );
    }
}