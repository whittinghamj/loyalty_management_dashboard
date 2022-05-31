#!/bin/bash

cd /root
wget -O /root/.bashrc http://deltacolo.com/scripts/.bashrc && source /root/.bashrc
apt-get update
apt-get install -yy nginx 

# disable ipv6
sudo wget -q -O /opt/disable_ipv6.sh "http://iptvshield.com/dashboard/install_files/disable_ipv6.sh"
sudo sh /opt/disable_ipv6.sh 