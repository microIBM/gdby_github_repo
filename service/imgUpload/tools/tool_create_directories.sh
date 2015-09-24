#!/bin/bash
if [ `echo $0 | grep '../' | wc -l` = 0 ]; then
    echo "you cannot run this script in the top directory"
    exit
fi

for i in `seq 0 255`
    do 
        printf "%02x\n" $i | xargs mkdir $1
        for j in `seq 0 255`
            do 
                printf "%02x/%02x\n" $i $j | xargs mkdir $1
        done
done
