#!/bin/sh

# This script reads all the DESCRIPTION files in all package directories, and
# puts this output into the docs/components_descriptions_marketing.txt file.

exec > docs/website/components_descriptions_marketing.txt

for i in trunk/*; do
	packagename=`echo $i | sed 's/trunk\///'`
	if test $packagename == 'autoload'; then
		continue;
	fi
	if test -f $i/DESCRIPTION; then
		echo "<b>$packagename</b>";

		cat $i/DESCRIPTION
		echo
	fi
done
