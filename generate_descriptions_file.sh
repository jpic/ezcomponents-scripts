#!/bin/sh

# This script reads all the DESCRIPTION files in all package directories, and
# puts this output into the docs/components_descriptions_marketing.txt file.

exec > docs/website/components_descriptions_marketing.txt

for i in packages/*; do
	packagename=`echo $i | sed 's/packages\///'`
	if test $packagename == 'autoload'; then
		continue;
	fi
	if test -f $i/trunk/DESCRIPTION; then
		echo $packagename;

		cat $i/trunk/DESCRIPTION
		echo
	fi
done
