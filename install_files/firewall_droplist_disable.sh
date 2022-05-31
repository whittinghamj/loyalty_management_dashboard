#!/usr/bin/env bash

# list of known spammers
URL1="http://www.spamhaus.org/drop/drop.lasso";
URL2="http://www.spamhaus.org/drop/edrop.lasso";

# save local copy here
FILE1="/tmp/drop.lasso";
FILE2="/tmp/edrop.lasso";
COMBINED="/tmp/drop-edrop.combined"

# unban old entries
if [ -f $COMBINED ]; then
    for IP in $( cat $COMBINED ); do
        ufw delete deny from $IP to any
    done
fi
