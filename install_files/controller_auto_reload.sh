#!/bin/bash

# get cluster_id
CLUSTER_ID="$(cat /opt/cluster_id.txt)";

# get server_id
SERVER_ID="$(cat /opt/server_id.txt)";

# backup existing conf file
today=`date '+%Y_%m_%d__%H_%M_%S'`;
cp /etc/nginx/nginx.conf /etc/nginx/nginx.conf.$today.backup

# download nginx.conf
sudo wget -q -O /etc/nginx/nginx.conf "https://cloudshield.io/dashboard/install_files/controller/build_nginx.php?cluster_id=$CLUSTER_ID&server_id=$SERVER_ID"

# nginx sanity check
/usr/sbin/nginx -t
if [ $? -eq 0 ]
then
	# reload nginx
    sudo nginx -s reload

    echo "NGINX Sanity check passed"
else
	# looks like something went wrong, revert
	mv /etc/nginx/nginx.conf.$today.backup /etc/nginx.conf

    echo "NGINX Sanity check failed, reverted"
fi

# run ssl patch
sudo bash /opt/ssl.sh 

# reload nginx
sudo nginx -s reload
