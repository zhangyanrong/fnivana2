#!/bin/bash
phpbin="/usr/bin/php"
webenter="/mnt/www/nirvana.fruitday.com/index.php"

ctl=$1
act=$2
if [ -z "$ctl" ] || [ -z "$act" ]; then
echo "miss args"
exit 0
fi


single_process(){
    phpexec="$phpbin $webenter $ctl $act"

    r=`ps aux | grep "$phpexec" | grep -v grep`
    if [ -z "$r" ] ; then
        echo 'miss'
        `$phpbin "$webenter" "$ctl" "$act"`
        exit 0
    else
        echo 'hit'
        exit 0
    fi
}

single_process
