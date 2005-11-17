#!/bin/sh

for i in `find . -name \*.php`; do
	tail=`tail -1 $i`;
	if test "$tail" != "?>"; then
		echo -n $i ""
	fi
done
echo
