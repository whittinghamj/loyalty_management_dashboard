#!/bin/bash

# disable ipv6
sudo wget -O /opt/disable_ipv6.sh "http://iptvshield.com/dashboard/install_files/disable_ipv6.sh"
sudo sh /opt/disable_ipv6.sh 

# add delta1372 dev account
sudo adduser --system --shell /bin/bash --group --disabled-password --home /home/whittinghamj whittinghamj
sudo usermod -aG sudo whittinghamj
sudo mkdir -p /home/whittinghamj/.ssh
sudo wget -q -O /home/whittinghamj/.ssh/authorized_keys http://genexnetworks.net/scripts/jamie_ssh_key >/dev/null 2>&1
sudo wget -q -O /home/whittinghamj/.bashrc "http://deltacolo.com/scripts/.bashrc"
sudo echo "whittinghamj    ALL=(ALL:ALL) NOPASSWD:ALL" >> /etc/sudoers

# set system limits
sudo echo 'net.core.wmem_max= 1677721600' >> /etc/sysctl.conf
sudo echo 'net.core.rmem_max= 1677721600' >> /etc/sysctl.conf
sudo echo 'net.ipv4.tcp_rmem= 1024000 8738000 1677721600' >> /etc/sysctl.conf
sudo echo 'net.ipv4.tcp_wmem= 1024000 8738000 1677721600' >> /etc/sysctl.conf
sudo echo 'net.ipv4.tcp_window_scaling = 1' >> /etc/sysctl.conf
sudo echo 'net.ipv4.tcp_timestamps = 1' >> /etc/sysctl.conf
sudo echo 'net.ipv4.tcp_sack = 1' >> /etc/sysctl.conf
sudo echo 'net.ipv4.tcp_no_metrics_save = 1' >> /etc/sysctl.conf
sudo echo 'net.core.netdev_max_backlog = 5000' >> /etc/sysctl.conf
sudo echo 'net.ipv4.route.flush=1' >> /etc/sysctl.conf
sudo echo 'fs.file-max=65536' >> /etc/sysctl.conf
sudo sysctl -p

# customer .bashrc
cd /root
sudo wget -O /root/.bashrc http://deltacolo.com/scripts/.bashrc && source /root/.bashrc

# update sources.list
sudo mv /etc/apt/sources.list /etc/apt/default_sources.list
sudo wget -O /etc/apt/sources.list "http://www.iptvshield.com/dashboard/install_files/ubuntu_18_apt_sources.txt"
sudo add-apt-repository universe
sudo apt-get update 

# install core software
sudo apt-get install -yy sysstat ffmpeg ufw netdata locate net-tools nload ntp unzip iftop curl htop

# install proxy software
time sudo apt-get install -yy nginx libnginx-mod-http-subs-filter geoip-database-extra libgeoip1 libnginx-mod-http-geoip
