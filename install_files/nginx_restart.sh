#!/bin/bash

# restart nginx
sudo service nginx stop
sudo service nginx start

# output
echo "NGINX has been restarted."
echo " "