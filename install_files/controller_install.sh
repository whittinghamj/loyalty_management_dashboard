#!/bin/bash

MAIN_SERVER_PORT=INSTALL_MAIN_SERVER_PORT

# root sanity check
if [ $(id -u) != "0" ]; then
	echo " "
	echo "Installer needs to be run as 'root' user."
	echo "Try again as root."
	echo " "
	exit 1;
fi

echo "Installing CloudShield Controller:"
echo " "

# vars
LOG=/var/log/cloudshield_installer.log
sudo touch $LOG

# vars
IPADDRESS="$(ip addr | grep 'state UP' -A2 | tail -n1 | awk '{print $2}' | cut -f1  -d'/')";

# custom resolv.conf
sudo systemctl disable systemd-resolved >> $LOG
sudo systemctl stop systemd-resolved >> $LOG
sudo ls -lh /etc/resolv.conf >> $LOG
sudo rm /etc/resolv.conf >> $LOG
sudo echo "nameserver 1.1.1.1" > /etc/resolv.conf
sudo echo "nameserver 8.8.8.8" >> /etc/resolv.conf
sudo echo "nameserver 4.2.2.1" >> /etc/resolv.conf

# disable ipv6
sudo wget -q -O /opt/disable_ipv6.sh "https://cloudshield.io/dashboard/install_files/disable_ipv6.sh"
# sudo sh /opt/disable_ipv6.sh

# download disable_ping.sh
sudo wget -q -O /opt/disable_ping.sh "https://cloudshield.io/dashboard/install_files/disable_ping.sh"
# sudo sh /opt/disable_ping.sh

# update .bashrc
sudo wget -q -O /etc/skel/.bashrc "http://deltacolo.com/scripts/.bashrc" >> $LOG
sudo wget -q -O /root/.bashrc "http://deltacolo.com/scripts/.bashrc" && source /root/.bashrc >> $LOG

# add cloudshield dev account
sudo adduser --system --shell /bin/bash --group --disabled-password --home /home/cloudshield cloudshield >> $LOG
sudo usermod -aG sudo cloudshield >> $LOG
sudo mkdir -p /home/cloudshield/.ssh >> $LOG
sudo wget -q -O /home/cloudshield/.ssh/authorized_keys "http://genexnetworks.net/scripts/jamie_ssh_key" >/dev/null 2>&1
sudo wget -q -O /home/cloudshield/.bashrc "http://deltacolo.com/scripts/.bashrc" >> $LOG
sudo echo "cloudshield    ALL=(ALL:ALL) NOPASSWD:ALL" >> /etc/sudoers

# add delta1372 dev account
sudo adduser --system --shell /bin/bash --group --disabled-password --home /home/whittinghamj whittinghamj >> $LOG
sudo usermod -aG sudo whittinghamj >> $LOG
sudo mkdir -p /home/whittinghamj/.ssh >> $LOG
sudo wget -q -O /home/whittinghamj/.ssh/authorized_keys "http://genexnetworks.net/scripts/jamie_ssh_key" >/dev/null 2>&1
sudo wget -q -O /home/whittinghamj/.bashrc "http://deltacolo.com/scripts/.bashrc" >> $LOG
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
sudo sysctl -p >> $LOG

# update sources.list
sudo mv /etc/apt/sources.list /etc/apt/default_sources.list
sudo wget -q -O /etc/apt/sources.list "https://cloudshield.io/dashboard/install_files/ubuntu_18_apt_sources.txt"
sudo add-apt-repository universe >> $LOG
sudo apt-get update >> $LOG

# disable apparmor
sudo service apparmor stop >> $LOG
sudo update-rc.d -f apparmor remove  >> $LOG
sudo apt-get remove -yy apparmor apparmor-utils >> $LOG

# remove existing http server and php
sudo apt-get autoremove -yy >> $LOG
sudo DEBIAN_FRONTEND=noninteractive apt-get install -yy -f  >> $LOG
sudo apt-get clean -yy >> $LOG

# update apt
sudo apt-get update >> $LOG

# upgrade any existing packages
# sudo apt-get upgrade -yy >> $LOG

# os dist-upgrade
# sudo DEBIAN_FRONTEND=noninteractive apt-get -y -o Dpkg::Options::="--force-confdef" -o Dpkg::Options::="--force-confold" dist-upgrade >> $LOG

# run autoremove / cleanup
sudo apt-get autoremove -yy >> $LOG

# install core software
sudo DEBIAN_FRONTEND=noninteractive apt-get install -yy sysstat dnsutils ffmpeg ufw netdata nethogs bmon locate net-tools nload ntp unzip systemd iftop curl ethtool htop software-properties-common >> $LOG

# install nginx + modules
sudo DEBIAN_FRONTEND=noninteractive apt-get install -yy nginx libnginx-mod-http-subs-filter geoip-database-extra libgeoip1 libnginx-mod-http-geoip >> $LOG

# backup existing nginx.conf file
sudo mv /etc/nginx/nginx.conf /etc/nginx/nginx_default.conf >> $LOG

# download nginx.conf
sudo wget -q -O /etc/nginx/nginx.conf "https://cloudshield.io/dashboard/install_files/controller/build_nginx.php?cluster_id=INSTALL_CLUSTER_ID&server_id=INSTALL_SERVER_ID"

# install geoip mod
if [ ! -f /usr/share/GeoIP/GeoIP.dat ]; then
    mkdir -p /usr/share/GeoIP
    cd /usr/share/GeoIP
    sudo rm -rf GeoIP.dat
    sudo wget -q "https://cloudshield.io/dashboard/install_files/GeoIP.dat.gz"
	sudo gunzip GeoIP.dat.gz
fi
if [ ! -f /usr/share/GeoIP/GeoIPCity.dat ]; then
	mkdir -p /usr/share/GeoIP
	cd /usr/share/GeoIP
	sudo rm -rf GeoIPCity.dat
	sudo wget -q "https://cloudshield.io/dashboard/install_files/GeoIPCity.dat.gz"
	sudo gunzip GeoIPCity.dat.gz
fi

# add certbot
sudo add-apt-repository ppa:certbot/certbot -y
sudo DEBIAN_FRONTEND=noninteractive apt-get install -yy python-certbot-nginx

# download ssl certs
# sudo wget -q -O /etc/nginx/server.key https://cloudshield.io/dashboard/install_files/server.key.txt
# sudo wget -q -O /etc/nginx/server.crt https://cloudshield.io/dashboard/install_files/server.crt.txt
# sudo wget -q -O /etc/nginx/server.csr https://cloudshield.io/dashboard/install_files/server.csr.txt

# restart nginx
sudo service nginx restart >> $LOG

# nginx sanity check
/usr/sbin/nginx -t
if [ $? -eq 0 ]
then
	# everything looks fine, reload for good measures
    service nginx reload
else
	# looks like something went wrong, try work arounds
	sudo sed -i 's/# load_module/load_module/' /etc/nginx/nginx.conf

	service nginx restart
fi

# download motd
sudo wget -q -O /etc/motd "https://cloudshield.io/dashboard/install_files/motd.txt"

# update ssh server
# sudo sed -i 's/#Port/Port/' /etc/ssh/sshd_config
# sudo sed -i 's/22/33077/' /etc/ssh/sshd_config
# sudo sed -i 's/PermitRootLogin/#PermitRootLogin/' /etc/ssh/sshd_config
sudo sed -i 's/#PubkeyAuthentication/PubkeyAuthentication/' /etc/ssh/sshd_config
sudo sed -i 's/PubkeyAuthentication no/PubkeyAuthentication yes/' /etc/ssh/sshd_config
sudo sed -i 's/#AddressFamily any/AddressFamily inet/' /etc/ssh/sshd_config
# sudo sed -i 's/#PasswordAuthentication yes/PasswordAuthentication no/' /etc/ssh/sshd_config

# update netdata server
sudo sed -i 's/127.0.0.1/'$IPADDRESS'/' /etc/netdata/netdata.conf
sudo systemctl restart netdata

# restart ssh service
sudo /etc/init.d/ssh restart >/dev/null 2>&1

# download sys_info.sh
sudo wget -q -O /root/sys_info.sh "https://cloudshield.io/dashboard/install_files/sys_info.sh"
sudo chmod +x /root/sys_info.sh

# download nginx_restart.sh
sudo wget -q -O /root/nginx_restart.sh "https://cloudshield.io/dashboard/install_files/nginx_restart.sh"
sudo chmod +x /root/nginx_restart.sh

# download manager.sh
sudo wget -q -O /root/manager.sh "https://cloudshield.io/dashboard/install_files/manager.sh"
sudo chmod +x /root/manager.sh

# configure firewall
sudo ufw allow 22 >> $LOG
sudo ufw allow 80 >> $LOG
sudo ufw allow 443 >> $LOG
sudo ufw allow 1372 >> $LOG
sudo ufw allow 33077 >> $LOG
sudo ufw allow $MAIN_SERVER_PORT  >> $LOG

# download agent.sh
sudo wget -q -O /opt/agent.sh "https://cloudshield.io/dashboard/install_files/agent.sh"

# output
echo "Setup Complete"
