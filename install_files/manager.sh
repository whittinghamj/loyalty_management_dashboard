#!/bin/bash
export NCURSES_NO_UTF8_ACS=1
HEIGHT=17
WIDTH=60
CHOICE_HEIGHT=11
BACKTITLE="D-Proxy V1"
TITLE="D-Proxy Options"
MENU="Choose one of the following options:"

while :

OPTIONS=(1 "Restart D-Proxy"
		 2 "Reboot Server (5 mins downtime)"
		 7 "Extend Memory with 12GB SWAP"
         9 "Exit" )

CHOICE=$(dialog --clear \
                --backtitle "$BACKTITLE" \
                --title "$TITLE" \
                --menu "$MENU" \
                $HEIGHT $WIDTH $CHOICE_HEIGHT \
                "${OPTIONS[@]}" \
                2>&1 >/dev/tty)

if test $? -eq 0
then 
   echo ""
else
   break
fi
				
clear
do
case $CHOICE in
        1)
            echo "Restarting Proxy..."
            sudo service nginx restart > /dev/null
            echo "Done. Press enter to continue."
            read enter
            ;;
        2)  echo "Rebooting the server now. Please allow 5-10 mins for reboot to take place."
            sudo reboot
            ;;
        3)
            echo "Downloading Standard Config Files..."
            sudo wget -O /etc/nginx/nginx.conf "http://files.subportal.io/proxy_nginx.conf" &> /dev/null 
	        sudo wget -O /etc/nginx/sites-available/default "http://files.subportal.io/proxy_nginx_default.conf" &> /dev/null 
	        sudo service nginx restart 
			sudo wget -O /etc/netdata/python.d/nginx.conf "http://files.subportal.io/proxy_netdata_nginx.conf" &> /dev/null 
			sudo systemctl restart netdata
            echo "Done. Press enter to continue."
            read enter
			;;
        4)
            echo "Downloading Larger Cache Config Files..."
            sudo wget -O /etc/nginx/nginx.conf "http://files.subportal.io/proxy_nginx.conf" &> /dev/null 
	        sudo wget -O /etc/nginx/sites-available/default "http://files.subportal.io/proxy_nginx_default_large_cache.conf" &> /dev/null 
	        sudo service nginx restart 
			sudo wget -O /etc/netdata/python.d/nginx.conf "http://files.subportal.io/proxy_netdata_nginx.conf" &> /dev/null 
			sudo systemctl restart netdata
            echo "Done. Press enter to continue."
            read enter
			;;
        5)  echo "Enabling automatic daily restart."
            sudo sudo wget -q -O /etc/cron.daily/nginx_proxy.sh "http://files.subportal.io/nginx_restart.sh" > /dev/null && sudo chmod 755 /etc/cron.daily/nginx_proxy.sh > /dev/null
	        echo "Done. Press enter to continue."
            read enter
            ;;
        6)  echo "Disabling automatic daily restart."
            sudo rm /etc/cron.daily/nginx_proxy.sh > /dev/null
	        echo "Done. Press enter to continue."
            read enter
            ;;
        7)  echo "Adding 12GB SWAP file."
			if sudo grep -Fxq "root  soft    sigpending  16382" /etc/security/limits.conf
			then
				# code present, do nothing
				sudo echo "..."
			else
				sudo echo "*     soft    sigpending  16382" >> /etc/security/limits.conf
				sudo echo "root  soft    sigpending  16382" >> /etc/security/limits.conf
			fi

			if sudo grep -Fxq "root  hard    nofile      4096" /etc/security/limits.conf
			then
				# code present, do nothing
				sudo echo "..."
			else
				sudo echo "*     hard    nofile      4096" >> /etc/security/limits.conf
				sudo echo "root  hard    nofile      4096" >> /etc/security/limits.conf
			fi

			if sudo grep -Fxq "root  hard    nproc       7881" /etc/security/limits.conf
			then
				# code present, do nothing
				sudo echo "..."
			else
				sudo echo "*     hard    nproc       7881" >> /etc/security/limits.conf
				sudo echo "root  hard    nproc       7881" >> /etc/security/limits.conf
			fi

			# swap file
			if sudo grep -Fxq "/swapfile   none    swap    sw    0   0" /etc/fstab
			then
				# code present, do nothing
				sudo echo "..."
			else
				sudo fallocate -l 12G /swapfile
				sudo chmod 600 /swapfile
				sudo mkswap /swapfile
				sudo swapon /swapfile
				sudo echo "/swapfile   none    swap    sw    0   0" >> /etc/fstab
			fi
			# swap tweakings
			if sudo grep -Fxq "vm.swappiness" /etc/sysctl.conf
			then
				# code present, do nothing
				sudo echo "..."
			else
				sudo echo "vm.swappiness=20" >> /etc/sysctl.conf
			fi

			if sudo grep -Fxq "vm.vfs_cache_pressure" /etc/sysctl.conf
			then
				# code present, do nothing
				sudo echo "..."
			else
				sudo echo "vm.vfs_cache_pressure=50" >> /etc/sysctl.conf
			fi
	        echo "Done. Press enter to continue."
            read enter
            ;;
        8)  echo "Downloading latest SubShield Proxy SSH Menu..."
            sudo wget -O /home/subshield/subshield.sh "http://files.subportal.io/subshield_light.sh" > /dev/null
            sudo chmod 775 /home/subshield/subshield.sh > /dev/null
            sudo chmod u+s /home/subshield/subshield.sh > /dev/null
            echo "Done. Please exit SSH and access SSH again to load new SSH Menu."
            read enter
            ./home/subshield/subshield.sh  > /dev/null
            ;;
        9)  break
            ;;
esac

done