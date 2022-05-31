<?php
// cf dev page

$iptv_domain = 'goldbricker77.com';

// jamie cf
/*
$get_all_records = shell_exec( 'curl -X GET "https://api.cloudflare.com/client/v4/zones" \
    -H "X-Auth-Email: jamie.whittingham@gmail.com" \
    -H "X-Auth-Key: 03b21dd784e6f1cd5a8bdc0372bb20db64a8f" \
    -H "Content-Type: application/json" ' );
    */

// power cf
$domains = shell_exec( 'curl -X GET "https://api.cloudflare.com/client/v4/zones?per_page=500" \
    				-H "Content-Type: application/json" \
    				-H "X-Auth-Email: kayhaver478@gmail.com" \
    				-H "X-Auth-Key: d6e54821401c0d2a896e70100fa8b02b89989"
    				' );

$domains = json_decode( $domains, true );

echo "========================================================================================== \n";

// find cluster main_domain
foreach( $domains['result'] as $domain ) {
	if( $domain['name'] == $iptv_domain ) {
		echo "Domain found ".$iptv_domain." \n";

		break;
	}
}

$dns_records = shell_exec( 'curl -X GET "https://api.cloudflare.com/client/v4/zones/'.$domain['id'].'/dns_records?per_page=200" \
    				-H "Content-Type: application/json" \
    				-H "X-Auth-Email: kayhaver478@gmail.com" \
    				-H "X-Auth-Key: d6e54821401c0d2a896e70100fa8b02b89989"
    				' );
$dns_records = json_decode( $dns_records, true );

// find hammertime01
foreach( $dns_records['result'] as $dns_record ) {
	// update dns record
	$update_dns_record = shell_exec( 'curl -X PUT "https://api.cloudflare.com/client/v4/zones/'.$domain['id'].'/dns_records/'.$dns_record['id'].'" \
     					-H "Content-Type: application/json" \
     					-H "X-Auth-Email: kayhaver478@gmail.com" \
    					-H "X-Auth-Key: d6e54821401c0d2a896e70100fa8b02b89989" \
     					--data \'{"type":"A","name":"'.$dns_record['name'].'","content":"'.$dns_record['content'].'","ttl":120,"proxied":false}\'
    				' );
	/*
	if( $dns_record['name'] == 'hammertime01.'.$iptv_domain ) {
		echo "DNS Record found ".$dns_record['name']." \n";

		break;
	}
	*/
}

// update dns record
/*
$update_dns_record = shell_exec( 'curl -X PUT "https://api.cloudflare.com/client/v4/zones/'.$domain['id'].'/dns_records/'.$dns_record['id'].'" \
     					-H "Content-Type: application/json" \
     					-H "X-Auth-Email: kayhaver478@gmail.com" \
    					-H "X-Auth-Key: d6e54821401c0d2a896e70100fa8b02b89989" \
     					--data \'{"type":"A","name":"'.$dns_record['name'].'","content":"'.$dns_record['content'].'","ttl":120,"proxied":false}\'
    				' );
// $update_dns_record = json_decode( $update_dns_record, true );

print_r( $update_dns_record );
*/