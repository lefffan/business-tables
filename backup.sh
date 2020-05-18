#!/bin/sh

file="OE"
file=$file"-"
file=$file`date "+%Y.%m.%d-%H-%M-%S"`
file=$file"-"$1
file=$file".tgz"

tar -czf /var/tmp/$file ./ 2>&1 >/dev/null
ftp -u ftp://lefan:J,kfrj29@np.gcom.ru/OE/$file /var/tmp/$file
rm -f /var/tmp/$file