#!/bin/sh

# Usage:
#   You should run this from *within* the
#   packages/<packagename>/releases/<version> directory.

version=`pwd | sed 's/.*\///g'`

echo $version

for i in `find . -name \*.php`; do perl -p -i -e "s/\/\/autogentag\/\//$version/g" $i; done
for i in `find . -name \*.php`; do perl -p -i -e "s/\/\/autogen\/\//$version/g" $i; done

date=`date +"%A %d %B %Y"`
perl -p -i -e "s/\[RELEASEDATE\]/$date/" ChangeLog
