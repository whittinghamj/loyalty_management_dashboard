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

// get cluster
$cluster = get_cluster( $cluster_id );

// security check
if( !isset( $cluster['id'] ) ) { go( 'dashboard.php?c=not_found' ); }

// set headers to force domain
header( "Content-Type: application/octet-stream" );
header( "Content-Transfer-Encoding: Binary" ); 
header( "Content-Disposition: attachment; filename=".$cluster['iptv_main_server_domain']."_vpn.ovpn" );

// build openvpn configuration file
print 'client'."\n";
print 'proto udp'."\n";
print 'explicit-exit-notify'."\n";
print 'remote '.md5( $cluster['id'] ).'.'.$cluster['iptv_main_server_domain'].' 1194'."\n";
print 'dev tun'."\n";
print 'resolv-retry infinite'."\n";
print 'nobind'."\n";
print 'persist-key'."\n";
print 'persist-tun'."\n";
print 'remote-cert-tls server'."\n";
print 'verify-x509-name server_eWUgJl8ZXID3teHA name'."\n";
print 'auth SHA256'."\n";
print 'auth-nocache'."\n";
print 'cipher AES-128-GCM'."\n";
print 'tls-client'."\n";
print 'tls-version-min 1.2'."\n";
print 'tls-cipher TLS-ECDHE-ECDSA-WITH-AES-128-GCM-SHA256'."\n";
print 'ignore-unknown-option block-outside-dns'."\n";
print 'setenv opt block-outside-dns # Prevent Windows 10 DNS leak'."\n";
print '#auth-user-pass'."\n";
print 'verb 3'."\n";
print '<ca>'."\n";
print '-----BEGIN CERTIFICATE-----'."\n";
print 'MIIB1zCCAX2gAwIBAgIUdz45EdaytrleE0pK1vkGliVo08YwCgYIKoZIzj0EAwIw'."\n";
print 'HjEcMBoGA1UEAwwTY25fUVJzc3JBYzhnVUVIU1VmaDAeFw0yMDEwMzEwNzAyNDNa'."\n";
print 'Fw0zMDEwMjkwNzAyNDNaMB4xHDAaBgNVBAMME2NuX1FSc3NyQWM4Z1VFSFNVZmgw'."\n";
print 'WTATBgcqhkjOPQIBBggqhkjOPQMBBwNCAAQ6MBljwGWKk9YLdGRb/IAht/MQH6o8'."\n";
print 'bPJIqc4HL0I0GSM9BhsIJviLLpBD3HTA9MWGPSh4AEWGJ+YZFN1CR9TIo4GYMIGV'."\n";
print 'MB0GA1UdDgQWBBT0xTOk6ahtGk3SOQiDxvr5ZHcmMDBZBgNVHSMEUjBQgBT0xTOk'."\n";
print '6ahtGk3SOQiDxvr5ZHcmMKEipCAwHjEcMBoGA1UEAwwTY25fUVJzc3JBYzhnVUVI'."\n";
print 'U1VmaIIUdz45EdaytrleE0pK1vkGliVo08YwDAYDVR0TBAUwAwEB/zALBgNVHQ8E'."\n";
print 'BAMCAQYwCgYIKoZIzj0EAwIDSAAwRQIhAPOYnwnMAerovi4PBiDgw0pS+f4baK4B'."\n";
print 'R8G0z0fzU/7lAiBfaA7Vn0CfebuejcWumecwoIHnjVhq3MxVlK8ftMqdAA=='."\n";
print '-----END CERTIFICATE-----'."\n";
print '</ca>'."\n";
print '<cert>'."\n";
print '-----BEGIN CERTIFICATE-----'."\n";
print 'MIIB1zCCAX6gAwIBAgIQRI06G9wbzhj2pirqcrX2lDAKBggqhkjOPQQDAjAeMRww'."\n";
print 'GgYDVQQDDBNjbl9RUnNzckFjOGdVRUhTVWZoMB4XDTIwMTAzMTA3MDI0NFoXDTIz'."\n";
print 'MDIwMzA3MDI0NFowETEPMA0GA1UEAwwGY2xpZW50MFkwEwYHKoZIzj0CAQYIKoZI'."\n";
print 'zj0DAQcDQgAEpi7BVAq1MzZktbwYZm7QxP8bneTEbeTrUZrmNOdY/Q5SCCT0StO5'."\n";
print 'ZfqMMgAFHr+SI42gHMUnJIpJTbi+Gs0+V6OBqjCBpzAJBgNVHRMEAjAAMB0GA1Ud'."\n";
print 'DgQWBBTuHuq9uL69h8yHAShw+6v3PJ9IwTBZBgNVHSMEUjBQgBT0xTOk6ahtGk3S'."\n";
print 'OQiDxvr5ZHcmMKEipCAwHjEcMBoGA1UEAwwTY25fUVJzc3JBYzhnVUVIU1VmaIIU'."\n";
print 'dz45EdaytrleE0pK1vkGliVo08YwEwYDVR0lBAwwCgYIKwYBBQUHAwIwCwYDVR0P'."\n";
print 'BAQDAgeAMAoGCCqGSM49BAMCA0cAMEQCIBIJ6wphabeZCfIg6O7RBavHZfBu1c8g'."\n";
print 'kxB4xj/M0mfAAiA/ESRKPKihsja7wEmr5tx2KgPpxiJnfs8t91Zma8hyRA=='."\n";
print '-----END CERTIFICATE-----'."\n";
print '</cert>'."\n";
print '<key>'."\n";
print '-----BEGIN PRIVATE KEY-----'."\n";
print 'MIGHAgEAMBMGByqGSM49AgEGCCqGSM49AwEHBG0wawIBAQQg4oXGWurR5LgYOUH4'."\n";
print 'pVY6FgT6Zh8D1g2nOYpFEF4aHwahRANCAASmLsFUCrUzNmS1vBhmbtDE/xud5MRt'."\n";
print '5OtRmuY051j9DlIIJPRK07ll+owyAAUev5IjjaAcxSckiklNuL4azT5X'."\n";
print '-----END PRIVATE KEY-----'."\n";
print '</key>'."\n";
print 'key-direction 1'."\n";
print '<tls-auth>'."\n";
print '#'."\n";
print '# 2048 bit OpenVPN static key'."\n";
print '#'."\n";
print '-----BEGIN OpenVPN Static key V1-----'."\n";
print 'd67ede92a90e49ec55a815b00c27f9fe'."\n";
print 'dd0dc4db037eb3d0b26da47a75a63d8b'."\n";
print '03f5fea4ba1b1850767ff6d5dc86a6bb'."\n";
print '0f5a1c2060c2b3401543575d0b88ec59'."\n";
print '4d99c9701189292b65173afab92be42c'."\n";
print 'cd1304d02854cd9ba0ebfe7d2bc82c49'."\n";
print '0fdee0125e0e5bd1974e5769cf3c18fb'."\n";
print '5d6227a79111afcac60bd360b4254240'."\n";
print 'b4f1ed06d4c4c5d34a02a838b6260eca'."\n";
print '5413c67ac26a6f2705f3cabde04d6bdd'."\n";
print '345372dcda3b2275fba28f5a4716ec9b'."\n";
print 'd567c62359f4c6389a6fdef701f3b59e'."\n";
print '7c9a492cc56feb354b04335887bba878'."\n";
print '0cbe2b1c6a2b7992805d71e6e5b7cfe4'."\n";
print 'c0f73525cb3f8e835b5f445f2838ce11'."\n";
print 'c3bc2fc21b7e8b750c179f61a6dfda10'."\n";
print '-----END OpenVPN Static key V1-----'."\n";
print '</tls-auth>'."\n";
print ' '."\n";

