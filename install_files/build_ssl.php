<?php

// build ssl file
print '#!/bin/bash '."\n";
print ' '."\n";
print 'FILE=/etc/letsencrypt/live/SERVER_HOSTNAME.INSTALL_MAIN_SERVER_DOMAIN/fullchain.pem '."\n";
print 'if [ -f "$FILE" ]; then '."\n";
print "		echo Installing existing SSL"."\n";
print "		sed -i 's/#ssl placeholder 1/ssl_certificate \/etc\/letsencrypt\/live\/SERVER_HOSTNAME.INSTALL_MAIN_SERVER_DOMAIN\/fullchain.pem; /' /etc/nginx/nginx.conf; "."\n";
print "		sed -i 's/#ssl placeholder 2/ssl_certificate_key \/etc\/letsencrypt\/live\/SERVER_HOSTNAME.INSTALL_MAIN_SERVER_DOMAIN\/privkey.pem; /' /etc/nginx/nginx.conf; "."\n";
print "		sed -i 's/#ssl placeholder 1/ssl_certificate \/etc\/letsencrypt\/live\/SERVER_HOSTNAME.INSTALL_MAIN_SERVER_DOMAIN\/fullchain.pem; /' /etc/nginx/sites-available/reverse-proxy.conf; "."\n";
print "		sed -i 's/#ssl placeholder 2/ssl_certificate_key \/etc\/letsencrypt\/live\/SERVER_HOSTNAME.INSTALL_MAIN_SERVER_DOMAIN\/privkey.pem; /' /etc/nginx/sites-available/reverse-proxy.conf; "."\n";
print 'else '."\n";
print "		echo Generating New SSL"."\n";
print " 	certbot register -m 'webmaster@cloudshield.io' --agree-tos -n --server 'https://api.buypass.com/acme/directory' "."\n";
print " 	certbot -n --agree-tos --no-redirect --no-eff-email --nginx -m webmaster@cloudshield.io --nginx -d SERVER_HOSTNAME.INSTALL_MAIN_SERVER_DOMAIN --server 'https://api.buypass.com/acme/directory'"."\n";
print 'fi '."\n";
print ' '."\n";
