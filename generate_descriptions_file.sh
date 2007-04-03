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
		echo $packagename
		php -r "echo str_repeat('-', strlen('$packagename'));"
		echo

		cat $i/DESCRIPTION
		echo
		echo Documentation__
		echo
		echo __ 'http://ez.no/doc/components/view/latest/(file)/introduction_'$packagename.html

		echo
	fi
done

rst2xml docs/website/components_descriptions_marketing.txt > /tmp/desc.xml
xsltproc docs/rstxml2ezxml.xsl /tmp/desc.xml > docs/website/components_descriptions_marketing.ezxml
rm -rf /tmp/desc.xml
