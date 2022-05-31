#!/bin/bash

# check and install curl and mpstat
command -v curl >/dev/null 2>&1 || { sudo apt-get -yy -qq install curl; }
command -v mpstat >/dev/null 2>&1 || { sudo apt-get -yy -qq install sysstat; }

# kernel
KERNEL=$(uname -r)

# hostname
HOSTNAME=$(hostname);

# uptime
UPTIME="$(awk '{print $1}' /proc/uptime)";

# ssh port
SSHPORT="$(sshd -T | head -n 1 | awk '{print $2}')";

# cpu model
CPU_MODEL=$(cat /proc/cpuinfo | grep 'model name' | awk -F\: '{print $2}' | uniq)

# cpu cores
CPU_CORES=$(cat /proc/cpuinfo | grep processor | wc -l)

# cpu speed
CPU_SPEED=$(lscpu | grep 'CPU MHz' | awk -F\: '{print $2}' | sed -e 's/^ *//g' -e 's/ *$//g')

# cpu usage
CPU_USAGE="$(mpstat 1 1 | awk 'END{print 100-$NF}')";

# total ram
RAM_TOTAL="$(cat /proc/meminfo | grep ^MemTotal: | awk '{print $2}')";

# ram usage
RAM_USAGE="$(free -m | awk 'NR==2{printf "%.2f%%", $3*100/$2 }' | sed 's/ //g' | sed 's/%//g')";

# hard drive usage
DISK_USAGE="$(df -h | awk '$NF=="/"{printf "%s", $5}' | sed 's/%//g')";

# network / bandwidth stats
# IPADDRESS="$(ip addr | grep 'state UP' -A2 | tail -n1 | awk '{print $2}' | cut -f1  -d'/')";
# IPADDRESS="$(hostname -i)";
IPADDRESS="$(curl -s -4 ifconfig.co)";

NIC="$(ip route get 8.8.8.8 | sed -nr 's/.*dev ([^\ ]+).*/\1/p')";
NIC="${NIC##*( )}"
IF=$NIC
R1=`cat /sys/class/net/$NIC/statistics/rx_bytes`
T1=`cat /sys/class/net/$NIC/statistics/tx_bytes`
sleep 1
R2=`cat /sys/class/net/$NIC/statistics/rx_bytes`
T2=`cat /sys/class/net/$NIC/statistics/tx_bytes`
TBPS=`expr $T2 - $T1`
RBPS=`expr $R2 - $R1`
TKBPS=`expr $TBPS / 1024`
RKBPS=`expr $RBPS / 1024`
TMBPS=`expr $TKBPS / 1024`
RMBPS=`expr $RKBPS / 1024`

# active connections
if [ -n "$(command -v ss)" ]; then
	ACTIVE_CONNECTIONS="$(ss -tun | tail -n +2 | wc -l)"
else
	ACTIVE_CONNECTIONS="$(netstat -tun | tail -n +3 | wc -l)"
fi

# is nginx running
NGINX_STATUS="$(pgrep -x nginx >/dev/null && echo "running" || echo "not_running")";

# nginx active connections
NGINX_ACTIVE_CONNECTIONS="$(curl -s 'http://127.0.0.1/nginx_status' | grep 'connections' | awk -F\: '{print $2}' | sed 's/ //g')";

# echo '{"hostname": "'$HOSTNAME'","uptime": "'$UPTIME'", "active_connections": "'$ACTIVE_CONNECTIONS'", "kernel": "'$KERNEL'", "ram_total": "'$RAM_TOTAL'", "cpu_model": "'$CPU_MODEL'", "cpu_cores": "'$CPU_CORES'", "cpu_speed": "'$CPU_SPEED'", "cpu_usage": "'$CPU_USAGE'", "ram_usage": "'$RAM_USAGE'", "disk_usage": "'$DISK_USAGE'", "ip_address": "'$IPADDRESS'", "bandwidth_down": "'$RKBPS'", "bandwidth_up": "'$TKBPS'" }'

# post to cms
sudo curl -s 'https://cloudshield.io/dashboard/api/?c=receive_server_stats' \
    -H "User-Agent: Mozilla/2.2" \
    -d "ip_address="$IPADDRESS \
    -d "uptime="$UPTIME \
    -d "cpu_cores="$CPU_CORES \
    -d "cpu_speed="$CPU_SPEED \
    -d "cpu_usage="$CPU_USAGE \
    -d "ram_total="$RAM_TOTAL \
    -d "ram_usage="$RAM_USAGE \
    -d "bandwidth_download="$RKBPS \
    -d "bandwidth_upload="$TKBPS \
    -d "nginx_status="$NGINX_STATUS \
    -d "nginx_active_connections="$NGINX_ACTIVE_CONNECTIONS 
