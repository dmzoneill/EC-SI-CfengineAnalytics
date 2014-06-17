#!/bin/bash

STATUSES=`/usr/bin/find ~/Cfengine/GLOBAL/packages/*/*/ -name "STATUS"`

for X in $STATUSES; do
    HEAD=`head -q -n 1 $X`;
    echo $HEAD" : "$X
done

