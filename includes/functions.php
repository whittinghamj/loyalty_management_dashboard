<?php

function lower_percentage( $number ) {
	if( $number > 100 ) {
		$number = $number - '0.01';
	}

	return $number;
}

function online_status_port_check( $host, $port ) {
	// open connection to host:port
	$connection = @fsockopen( $host, $port );

    // handle results
    if( is_resource( $connection ) ) {
    	// close the connection
        fclose( $connection );

        // return the status
        return true;
    } else {
    	// return the status
    	return false;
    }
}

function check_products( $billing_id ){
	global $whmcs, $site;
	
	$postfields["username"]			 = $whmcs['username'];
	$postfields["password"]			 = $whmcs['password'];
	$postfields["responsetype"]		 = "json";
	$postfields["action"]			   = "getclientsproducts";
	$postfields["clientid"]			 = $billing_id;
	
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $whmcs['url']);
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_TIMEOUT, 100);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $postfields);
	$data = curl_exec($ch);
	curl_close($ch);
	
	$data = json_decode( $data );
	$api_result = $data->result;
	
	$data = json_decode(json_encode( $data ), true );
	
	return $data['products']['product'];

	// return $data->products->product;

	// $clientid = $data->clientid;
	// $product_name = $data->products->product[0]->name;
	//$product_status = strtolower($data->products->product[0]->status);
}

function get_product_ids( $uid ){
	global $whmcs;
	$url							= $whmcs['url'];
	$postfields["username"]		 = $whmcs['username'];
	$postfields["password"]		 = $whmcs['password'];
	$postfields["responsetype"]	 = "json";
	$postfields["action"]		   = "getclientsproducts";
	$postfields["clientid"]		 = $uid;
	
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_TIMEOUT, 100);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $postfields);
	$data = curl_exec($ch);
	curl_close($ch);
	
	$data = json_decode($data);

	$api_result = $data->result;
		
	foreach($data->products->product as $product_data){
		$pids[] = $product_data->pid;
	}
	
	return $pids;
}

function get_date_diff( $time1, $time2, $precision = 2 ) {
	
	// If not numeric then convert timestamps
	if( !is_int( $time1 ) ) {
		$time1 = strtotime( $time1 );
	}
	if( !is_int( $time2 ) ) {
		$time2 = strtotime( $time2 );
	}

	// If time1 > time2 then swap the 2 values
	if( $time1 > $time2 ) {
		list( $time1, $time2 ) = array( $time2, $time1 );
	}

	// Set up intervals and diffs arrays
	$intervals = array( 'year', 'month', 'day', 'hour', 'minute', 'second' );
	$diffs = array();

	foreach( $intervals as $interval ) {
		// Create temp time from time1 and interval
		$ttime = strtotime( '+1 ' . $interval, $time1 );
		// Set initial values
		$add = 1;
		$looped = 0;
		// Loop until temp time is smaller than time2
		while ( $time2 >= $ttime ) {
			// Create new temp time from time1 and interval
			$add++;
			$ttime = strtotime( "+" . $add . " " . $interval, $time1 );
			$looped++;
		}

		$time1 = strtotime( "+" . $looped . " " . $interval, $time1 );
		$diffs[ $interval ] = $looped;
	}

	$count = 0;
	$times = array();
	foreach( $diffs as $interval => $value ) {
		// Break if we have needed precission
		if( $count >= $precision ) {
			break;
		}
		// Add value and interval if value is bigger than 0
		if( $value > 0 ) {
			if( $value != 1 ){
				$interval .= "s";
			}
			// Add value and interval to times array
			$times[] = $value . " " . $interval;
			$count++;
		}
	}

	// Return string with times
	return implode( ", ", $times );
}

function array_sort( $data ) {
	usort( $data, function( $a, $b ) {
		return $a['name'] <=> $b['name'];
	});
}

function print_all_vars() {
	$arr = get_defined_vars();
	return debug( $arr );
}

function objectToArray( $object ) {
	if( !is_object( $object ) && !is_array( $object ) ) {
		return $object;
	}
	return array_map( 'objectToArray', ( array ) $object );
}

function search_multi_array( $dataArray, $search_value, $key_to_search ) {
	// This function will search the revisions for a certain value
	// related to the associative key you are looking for.
	$keys = array();
	foreach( $dataArray as $key => $cur_value ) {
		if( $cur_value[$key_to_search] == $search_value ) {
			$keys[] = $key;
		}
	}
	return $keys;
}

function get_clusters() {
	global $conn, $account_details, $globals, $whmcs;

	$query = $conn->query( "
		SELECT * 
		FROM `clusters` 
		WHERE `user_id` = '".$_SESSION['account']['id']."' 
		AND `status` = 'active' 
	" );
	$data		   = $query->fetchAll( PDO::FETCH_ASSOC );

	$data = stripslashes_deep( $data );

	return $data;
}

function get_domain_name( $id ) {
	global $conn, $account_details, $globals, $whmcs;

	$query = $conn->query( "
		SELECT * 
		FROM `domain_names` 
		WHERE `id` = '".$id."' 
		AND `user_id` = '".$_SESSION['account']['id']."' 
	" );
	$data		   = $query->fetch( PDO::FETCH_ASSOC );

	$data = stripslashes_deep( $data );

	return $data;
}

function get_domain_name_by_cluster_admin( $id ) {
	global $conn, $account_details, $globals, $whmcs;

	$query = $conn->query( "
		SELECT * 
		FROM `domain_names` 
		WHERE `cluster_id` = '".$id."' 
	" );
	$data		   = $query->fetch( PDO::FETCH_ASSOC );

	$data = stripslashes_deep( $data );

	return $data;
}

function get_dns_record( $id ) {
	global $conn, $account_details, $globals, $whmcs;

	$query = $conn->query( "
		SELECT * 
		FROM `cloudshield_dns_server`.`records_cloudshield` 
		WHERE `id` = '".$id."' 
		AND `user_id` = '".$_SESSION['account']['id']."' 
	" );
	$data		   = $query->fetch( PDO::FETCH_ASSOC );

	$data = stripslashes_deep( $data );

	return $data;
}

function get_dns_record_admin( $id ) {
	global $conn, $account_details, $globals, $whmcs;

	$query = $conn->query( "
		SELECT * 
		FROM `cloudshield_dns_server`.`records_cloudshield` 
		WHERE `id` = '".$id."' 
	" );
	$data		   = $query->fetch( PDO::FETCH_ASSOC );

	$data = stripslashes_deep( $data );

	return $data;
}

function get_dns_records( $id ) {
	global $conn, $account_details, $globals, $whmcs;

	$query = $conn->query( "
		SELECT * 
		FROM `cloudshield_dns_server`.`records_cloudshield` 
		WHERE `domain_id` = '".$id."' 
		AND `user_id` = '".$_SESSION['account']['id']."' 
	" );
	$data		   = $query->fetchAll( PDO::FETCH_ASSOC );

	$data = stripslashes_deep( $data );

	return $data;
}

function get_dns_records_admin( $id ) {
	global $conn, $account_details, $globals, $whmcs;

	$query = $conn->query( "
		SELECT * 
		FROM `cloudshield_dns_server`.`records_cloudshield` 
		WHERE `domain_id` = '".$id."' 
	" );
	$data		   = $query->fetchAll( PDO::FETCH_ASSOC );

	$data = stripslashes_deep( $data );

	return $data;
}

function get_domain_names() {
	global $conn, $account_details, $globals, $whmcs;

	$query = $conn->query( "
		SELECT * 
		FROM `domain_names` 
		WHERE `user_id` = '".$_SESSION['account']['id']."' 
	" );
	$data		   = $query->fetchAll( PDO::FETCH_ASSOC );

	$data = stripslashes_deep( $data );

	return $data;
}

function get_cluster( $id ) {
	global $conn, $account_details, $globals, $whmcs;

	$query = $conn->query( "
		SELECT * 
		FROM `clusters` 
		WHERE `id` = '".$id."' 
		AND `user_id` = '".$_SESSION['account']['id']."'
	" );
	$data		   = $query->fetch( PDO::FETCH_ASSOC );
	$data['geo_countries'] = json_decode( $data['geo_countries'], true );

	// get blocked_networks
	$query = $conn->query( "
		SELECT * 
		FROM `cluster_blocked_networks` 
		WHERE `cluster_id` = '".$id."' 
		AND `user_id` = '".$_SESSION['account']['id']."'
	" );
	$data['blocked_networks']		   = $query->fetchAll( PDO::FETCH_ASSOC );

	$data = stripslashes_deep( $data );

	return $data;
}

function get_cluster_admin( $id ) {
	global $conn, $account_details, $globals, $whmcs;

	$query = $conn->query( "
		SELECT * 
		FROM `clusters` 
		WHERE `id` = '".$id."' 
	" );
	$data		   = $query->fetch( PDO::FETCH_ASSOC );
	$data['geo_countries'] = json_decode( $data['geo_countries'], true );

	// get blocked_networks
	$query = $conn->query( "
		SELECT * 
		FROM `cluster_blocked_networks` 
		WHERE `cluster_id` = '".$id."' 
	" );
	$data['blocked_networks']		   = $query->fetchAll( PDO::FETCH_ASSOC );

	$data = stripslashes_deep( $data );

	return $data;
}

function get_servers( $id , $type = '' ) {
	global $conn, $account_details, $globals, $whmcs;

    if( empty( $type ) ) {
        $query = $conn->query( "
            SELECT * 
            FROM `servers` 
            WHERE `cluster_id` = '".$id."' 
            AND `user_id` = '".$_SESSION['account']['id']."' 
        " );
    } else {
        $query = $conn->query( "
            SELECT * 
            FROM `servers` 
            WHERE `cluster_id` = '".$id."' 
            AND `user_id` = '".$_SESSION['account']['id']."' 
            AND `type` = '".$type."' 
        " );
    }

	$data		   = $query->fetchAll( PDO::FETCH_ASSOC );

	$data = stripslashes_deep( $data );

	return $data;
}

function get_servers_admin( $id , $type ) {
	global $conn, $account_details, $globals, $whmcs;

	$query = $conn->query( "
		SELECT * 
		FROM `servers` 
		WHERE `cluster_id` = '".$id."' 
		AND `type` = '".$type."' 
	" );
	$data		   = $query->fetchAll( PDO::FETCH_ASSOC );

	$data = stripslashes_deep( $data );

	return $data;
}

function get_server( $id ) {
	global $conn, $account_details, $globals, $whmcs;

	$query = $conn->query( "
		SELECT * 
		FROM `servers` 
		WHERE `id` = '".$id."' 
		AND `user_id` = '".$_SESSION['account']['id']."' 
	" );
	$data		   = $query->fetch( PDO::FETCH_ASSOC );

	$data = stripslashes_deep( $data );

	return $data;
}

function get_server_admin( $id ) {
    global $conn, $account_details, $globals, $whmcs;

    $query = $conn->query( "
        SELECT * 
        FROM `servers` 
        WHERE `id` = '".$id."' 
    " );
    $data          = $query->fetch( PDO::FETCH_ASSOC );

    $data = stripslashes_deep( $data );

    return $data;
}

function total_clusters() {
	global $conn, $account_details, $globals, $whmcs;

	$query = $conn->query( "
		SELECT count(`id`) as total_clusters 
		FROM `clusters` 
		WHERE `user_id` = '".$_SESSION['account']['id']."' 
	" );
	$results	= $query->fetch( PDO::FETCH_ASSOC );
	$data	   = $results['total_clusters'];

	return $data;
}

function total_domain_names() {
global $conn, $account_details, $globals, $whmcs;

	$query = $conn->query( "
		SELECT count(`id`) as total_domain_names 
		FROM `domain_names` 
		WHERE `user_id` = '".$_SESSION['account']['id']."' 
	" );
	$results	= $query->fetch( PDO::FETCH_ASSOC );
	$data	   = $results['total_domain_names'];

	return $data;
}

function total_servers( $id , $type ) {
	global $conn, $account_details, $globals, $whmcs;

	$query = $conn->query( "
		SELECT count(`id`) as total_servers 
		FROM `servers` 
		WHERE `cluster_id` = '".$id."'
		AND `user_id` = '".$_SESSION['account']['id']."' 
		AND `type` = '".$type."' 
	" );
	$results	= $query->fetch( PDO::FETCH_ASSOC );
	$data	   = $results['total_servers'];

	return $data;
}

function total_servers_account_wide() {
	global $conn, $account_details, $globals, $whmcs;

	$query = $conn->query( "
		SELECT count(`id`) as total_servers 
		FROM `servers` 
		WHERE `user_id` = '".$_SESSION['account']['id']."' 
		AND `type` = 'proxy' 
	" );
	$results	= $query->fetch( PDO::FETCH_ASSOC );
	$data	   = $results['total_servers'];

	return $data;
}

function total_controllers_account_wide() {
	global $conn, $account_details, $globals, $whmcs;

	$query = $conn->query( "
		SELECT count(`id`) as total_servers 
		FROM `servers` 
		WHERE `user_id` = '".$_SESSION['account']['id']."' 
		AND `type` = 'controller' 
	" );
	$results	= $query->fetch( PDO::FETCH_ASSOC );
	$data	   = $results['total_servers'];

	return $data;
}

function stripslashes_deep( $value ) {
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

function stripslashes_from_strings_only( $value ) {
	return is_string( $value ) ? stripslashes( $value ) : $value;
}

function map_deep( $value, $callback ) {
	if ( is_array( $value ) ) {
		foreach ( $value as $index => $item ) {
			$value[ $index ] = map_deep( $item, $callback );
		}
	}
 
	return $value;
}

function ip_in_range($ip, $range) {
	if (strpos($range, '/') == false) {
		$range .= '/32';
	}
	// $range is in IP/CIDR format eg 127.0.0.1/24
	list($range, $netmask) = explode('/', $range, 2);
	$ip_decimal = ip2long($ip);
	$range_decimal = ip2long($range);
	$wildcard_decimal = pow(2, (32 - $netmask)) - 1;
	$netmask_decimal = ~ $wildcard_decimal;
	return (($ip_decimal & $netmask_decimal) == ($range_decimal & $netmask_decimal));
}

function super_unique( $array,$key ) {
   $temp_array = [];
   foreach( $array as &$v ) {
	   if( !isset( $temp_array[$v[$key]] ) )
	   $temp_array[$v[$key]] =& $v;
   }
   $array = array_values( $temp_array );
   return $array;
}
	
function multi_unique( $src ) {
	$output = array_map( "unserialize" , array_unique( array_map( "serialize", $src ) ) );
	return $output;
}

function encrypt( $string, $key=32 ) {
	$result = '';
	for($i=0, $k= strlen($string); $i<$k; $i++) {
		$char = substr($string, $i, 1);
		$keychar = substr($key, ($i % strlen($key))-1, 1);
		$char = chr(ord($char)+ord($keychar));
		$result .= $char;
	}
	return base64_encode($result);
}

function decrypt( $string, $key=32 ) {
	$result = '';
	$string = base64_decode($string);
	for($i=0,$k=strlen($string); $i< $k ; $i++) {
		$char = substr($string, $i, 1);
		$keychar = substr($key, ($i % strlen($key))-1, 1);
		$char = chr(ord($char)-ord($keychar));
		$result.=$char;
	}
	return $result;
}

function cf_add_host( $hostname, $domain, $ip_address ) {
	global $cloudflare;

	$cloudflare['hostname']		 = $hostname;
	$cloudflare['domain']		   = $domain;
	$cloudflare['new_ip']		   = $ip_address;
	$quote_single				   = "'";

	if($cloudflare['domain'] == 'slipstreamiptv.com') {
		$cloudflare['zone_id']		  = '52d18db9c2d87e6c09195acbabf7266a';
	}

	// slipstreamiptv.com
	if($cloudflare['domain'] == 'akamaihdcdn.com') {
		$cloudflare['zone_id']		  = 'fd7faf9b5d7a2858a2178cb3d463afcf';
	}

	// slipdns.com
	if($cloudflare['domain'] == 'slipdns.com') {
		$cloudflare['zone_id']		  = '438de119f5768ba2a151a5d813613ebe';
	}

	// streamcdn.org
	if($cloudflare['domain'] == 'streamcdn.org') {
		$cloudflare['zone_id']		  = 'cd40b80a1078d35c7ba73494e1f2eecd';
	}

	$data = array(
		'type' => 'A',
		'name' => ''.$cloudflare['hostname'].'',
		'content' => ''.$cloudflare['new_ip'].'',
		'ttl' => 120,
		'priority' => 10,
		'proxied' => false
	);

	$data = json_encode($data);

	$curl_command = 'curl -s -X POST "https://api.cloudflare.com/client/v4/zones/'.$cloudflare['zone_id'].'/dns_records" -H "X-Auth-Email: '.$cloudflare['email'].'" -H "X-Auth-Key: '.$cloudflare['api_key'].'" -H "Content-Type: application/json" --data '.$quote_single.''.$data.''.$quote_single.' ';

	$results = shell_exec($curl_command);

	$results = json_decode($results, true);

	$cloudflare['domain_id'] = $results['result']['id'];

	return $cloudflare;
}

function formatSizeUnits($bytes) {
	if ($bytes >= 1073741824)
	{
		$bytes = number_format($bytes / 1073741824, 2) . ' GB';
	}
	elseif ($bytes >= 1048576)
	{
		$bytes = number_format($bytes / 1048576, 2) . ' MB';
	}
	elseif ($bytes >= 1024)
	{
		$bytes = number_format($bytes / 1024, 2) . ' KB';
	}
	elseif ($bytes > 1)
	{
		$bytes = $bytes . ' bytes';
	}
	elseif ($bytes == 1)
	{
		$bytes = $bytes . ' byte';
	}
	else
	{
		$bytes = '0 bytes';
	}

	return $bytes;
}

function ping( $host ) {
	exec( sprintf( 'ping -c 5 -W 5 %s', escapeshellarg( $host ) ), $res, $rval );
	return $rval === 0;
}

function geoip($ip) {
	global $conn, $account_details, $globals, $global_settings;

	// check for existing lat, lng
	$sql = "
		SELECT `id`,`lat`,`lng`,`country_code`,`country_name`,`region_name`,`city`,`zip_code`,`time_zone` 
		FROM `servers` 
		WHERE `wan_ip_address` = '".$ip."' 
		AND `lat` != '' 
		AND `lng` != '' 
		LIMIT 1 
	";
	$query	  = $conn->query($sql);
	$results	= $query->fetch( PDO::FETCH_ASSOC );

	if(isset($results['id'])) {
		$response['latitude']	   = $results['lat'];
		$response['longitude']	  = $results['lng'];
		$response['country_code']   = $results['country_code'];
		$response['country_name']   = $results['country_name'];
		$response['region_name']	= $results['region_name'];
		$response['city']		   = $results['city'];
		$response['zip_code']	   = $results['zip_code'];
		$response['time_zone']	  = $results['time_zone'];

		return $response;
	}else{
		$curl = curl_init();

		curl_setopt_array($curl, array(
			CURLOPT_URL => "https://freegeoip.app/json/".$ip,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_ENCODING => "",
			CURLOPT_MAXREDIRS => 10,
			CURLOPT_TIMEOUT => 30,
			CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
			CURLOPT_CUSTOMREQUEST => "GET",
			CURLOPT_HTTPHEADER => array(
				"accept: application/json",
				"content-type: application/json"
			),
		));

		$response = curl_exec($curl);
		$response = json_decode($response, true);

		$err = curl_error($curl);

		curl_close($curl);

		if($err) {
			return "cURL Error #:" . $err;
		}else{
			if($response['latitude'] != 0 && $response['longitude'] != 0) {
				// insert into db for later use
				$update = $conn->exec("UPDATE `servers` SET `lat` = '".$response['latitude']."' WHERE `wan_ip_address` = '".$ip."' " );
				$update = $conn->exec("UPDATE `servers` SET `lng` = '".$response['longitude']."' WHERE `wan_ip_address` = '".$ip."' " );
				$update = $conn->exec("UPDATE `servers` SET `country_code` = '".$response['country_code']."' WHERE `wan_ip_address` = '".$ip."' " );
				$update = $conn->exec("UPDATE `servers` SET `country_name` = '".$response['country_name']."' WHERE `wan_ip_address` = '".$ip."' " );
				$update = $conn->exec("UPDATE `servers` SET `region_name` = '".$response['region_name']."' WHERE `wan_ip_address` = '".$ip."' " );
				$update = $conn->exec("UPDATE `servers` SET `city` = '".$response['city']."' WHERE `wan_ip_address` = '".$ip."' " );
				$update = $conn->exec("UPDATE `servers` SET `zip_code` = '".$response['zip_code']."' WHERE `wan_ip_address` = '".$ip."' " );
				$update = $conn->exec("UPDATE `servers` SET `time_zone` = '".$response['time_zone']."' WHERE `wan_ip_address` = '".$ip."' " );
			}

			return $response;
		}
	}
}

function get_full_url() {
   $http=isset($_SERVER['HTTPS']) ? 'https://' : 'http://';

   $part=rtrim($_SERVER['SCRIPT_NAME'],basename($_SERVER['SCRIPT_NAME']));

   $domain=$_SERVER['SERVER_NAME'];

   return "$http"."$domain"."$part";
}

function uptime( int $seconds = null, int $requiredParts = null ) {
	if( $seconds != NULL ) {
		$from	 = new \DateTime('@0');
		$to	   = new \DateTime("@$seconds" );
		$interval = $from->diff($to);
		$str	  = '';

		$parts = [
			'y' => 'y',
			'm' => 'm',
			'd' => 'd',
			'h' => 'h',
			'i' => 'm',
			's' => 's',
		];

		$includedParts = 0;

		foreach ($parts as $key => $text) {
			if ($requiredParts && $includedParts >= $requiredParts) {
				break;
			}

			$currentPart = $interval->{$key};

			if (empty($currentPart)) {
				continue;
			}

			if (!empty($str)) {
				$str .= ', ';
			}

			$str .= sprintf('%d%s', $currentPart, $text);

			if ($currentPart > 1) {
				// handle plural
				$str .= '';
			}

			$includedParts++;
		}

		return $str;
	} else {
		return '';
	}
}

function code_to_country($code) {
	$code = strtoupper($code);

	$countryList = array(
		'AF' => 'Afghanistan',
		'AX' => 'Aland Islands',
		'AL' => 'Albania',
		'DZ' => 'Algeria',
		'AS' => 'American Samoa',
		'AD' => 'Andorra',
		'AO' => 'Angola',
		'AI' => 'Anguilla',
		'AQ' => 'Antarctica',
		'AG' => 'Antigua and Barbuda',
		'AR' => 'Argentina',
		'AM' => 'Armenia',
		'AW' => 'Aruba',
		'AU' => 'Australia',
		'AT' => 'Austria',
		'AZ' => 'Azerbaijan',
		'BS' => 'Bahamas the',
		'BH' => 'Bahrain',
		'BD' => 'Bangladesh',
		'BB' => 'Barbados',
		'BY' => 'Belarus',
		'BE' => 'Belgium',
		'BZ' => 'Belize',
		'BJ' => 'Benin',
		'BM' => 'Bermuda',
		'BT' => 'Bhutan',
		'BO' => 'Bolivia',
		'BA' => 'Bosnia and Herzegovina',
		'BW' => 'Botswana',
		'BV' => 'Bouvet Island (Bouvetoya)',
		'BR' => 'Brazil',
		'IO' => 'British Indian Ocean Territory (Chagos Archipelago)',
		'VG' => 'British Virgin Islands',
		'BN' => 'Brunei Darussalam',
		'BG' => 'Bulgaria',
		'BF' => 'Burkina Faso',
		'BI' => 'Burundi',
		'KH' => 'Cambodia',
		'CM' => 'Cameroon',
		'CA' => 'Canada',
		'CV' => 'Cape Verde',
		'KY' => 'Cayman Islands',
		'CF' => 'Central African Republic',
		'TD' => 'Chad',
		'CL' => 'Chile',
		'CN' => 'China',
		'CX' => 'Christmas Island',
		'CC' => 'Cocos (Keeling) Islands',
		'CO' => 'Colombia',
		'KM' => 'Comoros the',
		'CD' => 'Congo',
		'CG' => 'Congo the',
		'CK' => 'Cook Islands',
		'CR' => 'Costa Rica',
		'CI' => 'Cote d\'Ivoire',
		'HR' => 'Croatia',
		'CU' => 'Cuba',
		'CY' => 'Cyprus',
		'CZ' => 'Czech Republic',
		'DK' => 'Denmark',
		'DJ' => 'Djibouti',
		'DM' => 'Dominica',
		'DO' => 'Dominican Republic',
		'EC' => 'Ecuador',
		'EG' => 'Egypt',
		'SV' => 'El Salvador',
		'GQ' => 'Equatorial Guinea',
		'ER' => 'Eritrea',
		'EE' => 'Estonia',
		'ET' => 'Ethiopia',
		'FO' => 'Faroe Islands',
		'FK' => 'Falkland Islands (Malvinas)',
		'FJ' => 'Fiji the Fiji Islands',
		'FI' => 'Finland',
		'FR' => 'France, French Republic',
		'GF' => 'French Guiana',
		'PF' => 'French Polynesia',
		'TF' => 'French Southern Territories',
		'GA' => 'Gabon',
		'GM' => 'Gambia the',
		'GE' => 'Georgia',
		'DE' => 'Germany',
		'GH' => 'Ghana',
		'GI' => 'Gibraltar',
		'GR' => 'Greece',
		'GL' => 'Greenland',
		'GD' => 'Grenada',
		'GP' => 'Guadeloupe',
		'GU' => 'Guam',
		'GT' => 'Guatemala',
		'GG' => 'Guernsey',
		'GN' => 'Guinea',
		'GW' => 'Guinea-Bissau',
		'GY' => 'Guyana',
		'HT' => 'Haiti',
		'HM' => 'Heard Island and McDonald Islands',
		'VA' => 'Holy See (Vatican City State)',
		'HN' => 'Honduras',
		'HK' => 'Hong Kong',
		'HU' => 'Hungary',
		'IS' => 'Iceland',
		'IN' => 'India',
		'ID' => 'Indonesia',
		'IR' => 'Iran',
		'IQ' => 'Iraq',
		'IE' => 'Ireland',
		'IM' => 'Isle of Man',
		'IL' => 'Israel',
		'IT' => 'Italy',
		'JM' => 'Jamaica',
		'JP' => 'Japan',
		'JE' => 'Jersey',
		'JO' => 'Jordan',
		'KZ' => 'Kazakhstan',
		'KE' => 'Kenya',
		'KI' => 'Kiribati',
		'KP' => 'Korea',
		'KR' => 'Korea',
		'KW' => 'Kuwait',
		'KG' => 'Kyrgyz Republic',
		'LA' => 'Lao',
		'LV' => 'Latvia',
		'LB' => 'Lebanon',
		'LS' => 'Lesotho',
		'LR' => 'Liberia',
		'LY' => 'Libyan Arab Jamahiriya',
		'LI' => 'Liechtenstein',
		'LT' => 'Lithuania',
		'LU' => 'Luxembourg',
		'MO' => 'Macao',
		'MK' => 'Macedonia',
		'MG' => 'Madagascar',
		'MW' => 'Malawi',
		'MY' => 'Malaysia',
		'MV' => 'Maldives',
		'ML' => 'Mali',
		'MT' => 'Malta',
		'MH' => 'Marshall Islands',
		'MQ' => 'Martinique',
		'MR' => 'Mauritania',
		'MU' => 'Mauritius',
		'YT' => 'Mayotte',
		'MX' => 'Mexico',
		'FM' => 'Micronesia',
		'MD' => 'Moldova',
		'MC' => 'Monaco',
		'MN' => 'Mongolia',
		'ME' => 'Montenegro',
		'MS' => 'Montserrat',
		'MA' => 'Morocco',
		'MZ' => 'Mozambique',
		'MM' => 'Myanmar',
		'NA' => 'Namibia',
		'NR' => 'Nauru',
		'NP' => 'Nepal',
		'AN' => 'Netherlands Antilles',
		'NL' => 'Netherlands the',
		'NC' => 'New Caledonia',
		'NZ' => 'New Zealand',
		'NI' => 'Nicaragua',
		'NE' => 'Niger',
		'NG' => 'Nigeria',
		'NU' => 'Niue',
		'NF' => 'Norfolk Island',
		'MP' => 'Northern Mariana Islands',
		'NO' => 'Norway',
		'OM' => 'Oman',
		'PK' => 'Pakistan',
		'PW' => 'Palau',
		'PS' => 'Palestinian Territory',
		'PA' => 'Panama',
		'PG' => 'Papua New Guinea',
		'PY' => 'Paraguay',
		'PE' => 'Peru',
		'PH' => 'Philippines',
		'PN' => 'Pitcairn Islands',
		'PL' => 'Poland',
		'PT' => 'Portugal, Portuguese Republic',
		'PR' => 'Puerto Rico',
		'QA' => 'Qatar',
		'RE' => 'Reunion',
		'RO' => 'Romania',
		'RU' => 'Russian Federation',
		'RW' => 'Rwanda',
		'BL' => 'Saint Barthelemy',
		'SH' => 'Saint Helena',
		'KN' => 'Saint Kitts and Nevis',
		'LC' => 'Saint Lucia',
		'MF' => 'Saint Martin',
		'PM' => 'Saint Pierre and Miquelon',
		'VC' => 'Saint Vincent and the Grenadines',
		'WS' => 'Samoa',
		'SM' => 'San Marino',
		'ST' => 'Sao Tome and Principe',
		'SA' => 'Saudi Arabia',
		'SN' => 'Senegal',
		'RS' => 'Serbia',
		'SC' => 'Seychelles',
		'SL' => 'Sierra Leone',
		'SG' => 'Singapore',
		'SK' => 'Slovakia (Slovak Republic)',
		'SI' => 'Slovenia',
		'SB' => 'Solomon Islands',
		'SO' => 'Somalia, Somali Republic',
		'ZA' => 'South Africa',
		'GS' => 'South Georgia and the South Sandwich Islands',
		'ES' => 'Spain',
		'LK' => 'Sri Lanka',
		'SD' => 'Sudan',
		'SR' => 'Suriname',
		'SJ' => 'Svalbard & Jan Mayen Islands',
		'SZ' => 'Swaziland',
		'SE' => 'Sweden',
		'CH' => 'Switzerland, Swiss Confederation',
		'SY' => 'Syrian Arab Republic',
		'TW' => 'Taiwan',
		'TJ' => 'Tajikistan',
		'TZ' => 'Tanzania',
		'TH' => 'Thailand',
		'TL' => 'Timor-Leste',
		'TG' => 'Togo',
		'TK' => 'Tokelau',
		'TO' => 'Tonga',
		'TT' => 'Trinidad and Tobago',
		'TN' => 'Tunisia',
		'TR' => 'Turkey',
		'TM' => 'Turkmenistan',
		'TC' => 'Turks and Caicos Islands',
		'TV' => 'Tuvalu',
		'UG' => 'Uganda',
		'UA' => 'Ukraine',
		'AE' => 'United Arab Emirates',
		'GB' => 'United Kingdom',
		'US' => 'United States of America',
		'UM' => 'United States Minor Outlying Islands',
		'VI' => 'United States Virgin Islands',
		'UY' => 'Uruguay, Eastern Republic of',
		'UZ' => 'Uzbekistan',
		'VU' => 'Vanuatu',
		'VE' => 'Venezuela',
		'VN' => 'Vietnam',
		'WF' => 'Wallis and Futuna',
		'EH' => 'Western Sahara',
		'YE' => 'Yemen',
		'ZM' => 'Zambia',
		'ZW' => 'Zimbabwe'
	);

	if( !$countryList[$code] ) return $code;
	else return $countryList[$code];
}

function account_details( $id ){
	global $whmcs, $conn;
	
	// get local stored user record
    $query              = $conn->query("SELECT * FROM `users` WHERE `id` = '".$id."' ");
    $data            	= $query->fetch(PDO::FETCH_ASSOC);
        
	return $data;

}

function get_all_isps( ) {
	global $conn, $wp, $whmcs, $product_ids;

	// get channel control record
	$query = $conn->query( "SELECT * FROM `geoip_isps` ORDER BY `isp_name` " );
	$data = $query->fetchAll( PDO::FETCH_ASSOC );
	
	$data = stripslashes_deep( $data );

	return $data;
}

function console_output( $data ) {
	$timestamp = date( "Y-m-d H:i:s", time() );
	echo "[" . $timestamp . "] - " . $data . "\n";
}

function json_output( $data ) {
	// $data['timestamp']		= time();
	$data 					= json_encode($data);
	echo $data;
	die();
}

function formatbytes( $size, $precision = 2 ) {
	$base = log($size, 1024);
	$suffixes = array('', 'K', 'M', 'G', 'T');   

	// return round(pow(1024, $base - floor($base)), $precision) .' '. $suffixes[floor($base)];
	return round(pow(1024, $base - floor($base)), $precision);
}

function filesize_formatted( $path ) {
	$size = filesize($path);
	$units = array( 'B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB');
	$power = $size > 0 ? floor(log($size, 1024)) : 0;
	return number_format($size / pow(1024, $power), 2, '.', ',') . ' ' . $units[$power];
}

function percentage( $val1, $val2, $precision ) {
	// sanity - remove non-number chars
	$val1 = preg_replace("/[^0-9]/", "", $val1);
	$val2 = preg_replace("/[^0-9]/", "", $val2);

	$division = $val1 / $val2;
	$res = $division * 100;
	$res = round($res, $precision);
	return $res;
}

function go($link = '') {
	header("Location: " . $link);
	die();
}

function url($url = '') {
	$host = $_SERVER['HTTP_HOST'];
	$host = !preg_match('/^http/', $host) ? 'http://' . $host : $host;
	$path = preg_replace('/\w+\.php/', '', $_SERVER['REQUEST_URI']);
	$path = preg_replace('/\?.*$/', '', $path);
	$path = !preg_match('/\/$/', $path) ? $path . '/' : $path;
	if ( preg_match('/http:/', $host) && is_ssl() ) {
		$host = preg_replace('/http:/', 'https:', $host);
	}
	if ( preg_match('/https:/', $host) && !is_ssl() ) {
		$host = preg_replace('/https:/', 'http:', $host);
	}
	return $host . $path . $url;
}

function post( $key = null ) {
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

function post_array( $key = null ) {
	if ( is_null($key) ) {
		return $_POST;
	}
	$post = isset($_POST[$key]) ? $_POST[$key] : null;
	if ( is_string($post) ) {
		$post = trim($post);
	}

	return $post;
}

function get_gravatar( $email )  
{
	$image = 'http://www.gravatar.com/avatar.php?gravatar_id='.md5( $email );

	return $image;
}

function get( $key = null ) {
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

function request( $key = null ) {
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

function debug($input) {
	$output = '<pre>';
	if ( is_array($input) || is_object($input) ) {
		$output .= print_r($input, true);
	} else {
		$output .= $input;
	}
	$output .= '</pre>';
	echo $output;
}

function status_message($status, $message) {
	$_SESSION['alert']['status']		= $status;
	$_SESSION['alert']['message']		= $message;
}

function remote_content($url) {
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_URL,$url);
	$result=curl_exec($ch);
	curl_close($ch);

	return $result;
}

function random_string($length = 10) {
	$characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
	$charactersLength = strlen( $characters );
	$randomString = '';
	for ($i = 0; $i < $length; $i++) {
		$randomString .= $characters[rand( 0, $charactersLength - 1 )];
	}
	return $randomString;
}

function accept_terms_modal() {
	global $conn, $account_details, $globals, $global_settings;

	$modal = '
		<div class="modal fade" id="cms_terms_modal">
			<div class="modal-dialog modal-xl">
				<div class="modal-content">
					<div class="modal-header">
						<h4 class="modal-title">Terms and Conditions</h4>
					</div>
					<div class="modal-body">
						<p>
							<h2>Welcome to Stiliam CMS</h2>
							<p>These terms and conditions outline the rules and regulations for the use of Stiliam CMS\'s Website.</p> <br /> 

							<p>By accessing this website we assume you accept these terms and conditions in full. Do not continue to use Stiliam CMS\'s website 
							if you do not accept all of the terms and conditions stated on this page.</p>
							<p>The following terminology applies to these Terms and Conditions, Privacy Statement and Disclaimer Notice
							and any or all Agreements: "Client", "You" and "Your" refers to you, the person accessing this website
							and accepting the Company\'s terms and conditions. "The Company", "Ourselves", "We", "Our" and "Us", refers
							to our Company. "Party", "Parties", or "Us", refers to both the Client and ourselves, or either the Client
							or ourselves. All terms refer to the offer, acceptance and consideration of payment necessary to undertake
							the process of our assistance to the Client in the most appropriate manner, whether by formal meetings
							of a fixed duration, or any other means, for the express purpose of meeting the Client\'s needs in respect
							of provision of the Company\'s stated services/products, in accordance with and subject to, prevailing law
							of . Any use of the above terminology or other words in the singular, plural,
							capitalisation and/or he/she or they, are taken as interchangeable and therefore as referring to same.</p>
							<h4>Cookies</h4>
							<p>We employ the use of cookies. By using Stiliam CMS\'s website you consent to the use of cookies 
							in accordance with Stiliam CMS\'s privacy policy.</p><p>Most of the modern day interactive web sites
							use cookies to enable us to retrieve user details for each visit. Cookies are used in some areas of our site
							to enable the functionality of this area and ease of use for those people visiting. Some of our 
							affiliate / advertising partners may also use cookies.</p>
							<h4>License</h4>
							<p>Unless otherwise stated, Stiliam CMS and/or it\'s licensors own the intellectual property rights for
							all material on Stiliam CMS. All intellectual property rights are reserved. You may view and/or print
							pages from https://www.stiliam.com for your own personal use subject to restrictions set in these terms and conditions.</p>
							<p>You must not:</p>
							<ol>
								<li>Republish material from https://www.stiliam.com</li>
								<li>Sell, rent or sub-license material from https://www.stiliam.com</li>
								<li>Reproduce, duplicate or copy material from https://www.stiliam.com</li>
							</ol>
							<p>Redistribute content from Stiliam CMS (unless content is specifically made for redistribution).</p>
							<h4>Hyperlinking to our Content</h4>
							<ol>
								<li>The following organizations may link to our Web site without prior written approval:
									<ol>
									<li>Government agencies;</li>
									<li>Search engines;</li>
									<li>News organizations;</li>
									<li>Online directory distributors when they list us in the directory may link to our Web site in the same
										manner as they hyperlink to the Web sites of other listed businesses; and</li>
									<li>Systemwide Accredited Businesses except soliciting non-profit organizations, charity shopping malls,
										and charity fundraising groups which may not hyperlink to our Web site.</li>
									</ol>
								</li>
							</ol>
							<ol start="2">
								<li>These organizations may link to our home page, to publications or to other Web site information so long
									as the link: (a) is not in any way misleading; (b) does not falsely imply sponsorship, endorsement or
									approval of the linking party and its products or services; and (c) fits within the context of the linking
									party\'s site.
								</li>
								<li>We may consider and approve in our sole discretion other link requests from the following types of organizations:
									<ol>
										<li>commonly-known consumer and/or business information sources such as Chambers of Commerce, American
											Automobile Association, AARP and Consumers Union;</li>
										<li>dot.com community sites;</li>
										<li>associations or other groups representing charities, including charity giving sites,</li>
										<li>online directory distributors;</li>
										<li>internet portals;</li>
										<li>accounting, law and consulting firms whose primary clients are businesses; and</li>
										<li>educational institutions and trade associations.</li>
									</ol>
								</li>
							</ol>
							<p>We will approve link requests from these organizations if we determine that: (a) the link would not reflect
							unfavorably on us or our accredited businesses (for example, trade associations or other organizations
							representing inherently suspect types of business, such as work-at-home opportunities, shall not be allowed
							to link); (b)the organization does not have an unsatisfactory record with us; (c) the benefit to us from
							the visibility associated with the hyperlink outweighs the absence of Stiliam CMS; and (d) where the
							link is in the context of general resource information or is otherwise consistent with editorial content
							in a newsletter or similar product furthering the mission of the organization.</p>

							<p>These organizations may link to our home page, to publications or to other Web site information so long as
							the link: (a) is not in any way misleading; (b) does not falsely imply sponsorship, endorsement or approval
							of the linking party and it products or services; and (c) fits within the context of the linking party\'s
							site.</p>

							<p>If you are among the organizations listed in paragraph 2 above and are interested in linking to our website,
							you must notify us by sending an e-mail to <a href="mailto:info@stiliam.com" title="send an email to info@stiliam.com">info@stiliam.com</a>.
							Please include your name, your organization name, contact information (such as a phone number and/or e-mail
							address) as well as the URL of your site, a list of any URLs from which you intend to link to our Web site,
							and a list of the URL(s) on our site to which you would like to link. Allow 2-3 weeks for a response.</p>

							<p>Approved organizations may hyperlink to our Web site as follows:</p>

							<ol>
								<li>By use of our corporate name; or</li>
								<li>By use of the uniform resource locator (Web address) being linked to; or</li>
								<li>By use of any other description of our Web site or material being linked to that makes sense within the
									context and format of content on the linking party\'s site.</li>
							</ol>
							<p>No use of Stiliam CMS\'s logo or other artwork will be allowed for linking absent a trademark license
							agreement.</p>
							<h4>Iframes</h4>
							<p>Without prior approval and express written permission, you may not create frames around our Web pages or
							use other techniques that alter in any way the visual presentation or appearance of our Web site.</p>
							<h4>Reservation of Rights</h4>
							<p>We reserve the right at any time and in its sole discretion to request that you remove all links or any particular
							link to our Web site. You agree to immediately remove all links to our Web site upon such request. We also
							reserve the right to amend these terms and conditions and its linking policy at any time. By continuing
							to link to our Web site, you agree to be bound to and abide by these linking terms and conditions.</p>
							<h4>Removal of links from our website</h4>
							<p>If you find any link on our Web site or any linked web site objectionable for any reason, you may contact
							us about this. We will consider requests to remove links but will have no obligation to do so or to respond
							directly to you.</p>
							<p>Whilst we endeavour to ensure that the information on this website is correct, we do not warrant its completeness
							or accuracy; nor do we commit to ensuring that the website remains available or that the material on the
							website is kept up to date.</p>
							<h4>Content Liability</h4>
							<p>We shall have no responsibility or liability for any content appearing on your Web site. You agree to indemnify
							and defend us against all claims arising out of or based upon your Website. No link(s) may appear on any
							page on your Web site or within any context containing content or materials that may be interpreted as
							libelous, obscene or criminal, or which infringes, otherwise violates, or advocates the infringement or
							other violation of, any third party rights.</p>
							<h4>Disclaimer</h4>
							<p>To the maximum extent permitted by applicable law, we exclude all representations, warranties and conditions relating to our website and the use of this website (including, without limitation, any warranties implied by law in respect of satisfactory quality, fitness for purpose and/or the use of reasonable care and skill). Nothing in this disclaimer will:</p>
							<ol>
							<li>limit or exclude our or your liability for death or personal injury resulting from negligence;</li>
							<li>limit or exclude our or your liability for fraud or fraudulent misrepresentation;</li>
							<li>limit any of our or your liabilities in any way that is not permitted under applicable law; or</li>
							<li>exclude any of our or your liabilities that may not be excluded under applicable law.</li>
							</ol>
							<p>The limitations and exclusions of liability set out in this Section and elsewhere in this disclaimer: (a)
							are subject to the preceding paragraph; and (b) govern all liabilities arising under the disclaimer or
							in relation to the subject matter of this disclaimer, including liabilities arising in contract, in tort
							(including negligence) and for breach of statutory duty.</p>
							<p>We will not be liable for any loss or damage of any nature.</p>
						</p>
					</div>
					<div class="modal-footer justify-content-between">
						<a href="actions.php?a=accept_terms" class="btn btn-block btn-success">Accept Terms &amp; Conditions</a>
						<a href="logout.php" class="btn btn-block btn-danger">Reject Terms &amp; Conditions</a>
					</div>
				</div>
			</div>
		</div>
			';

	echo $modal;
}

function cf_fine_domain( $domains, $iptv_domain ) {
    foreach( $domains['result'] as $domain ) {
        if( $domain['name'] == $iptv_domain ) {
            break;
        }
    }

    if( isset( $domain['id'] ) ) {
        return $domain;
    } else {
        return false;
    }
}

function domain_whois_query( $domain ) {
    
    $data = @file_get_contents( 'https://www.whoisxmlapi.com/whoisserver/WhoisService?apiKey=at_Ok2O4wNodIKD86Yn9lobnzbEBm7es&outputFormat=json&domainName='.$domain );
    $data = json_decode( $data, true );
    
    return $data;
} 