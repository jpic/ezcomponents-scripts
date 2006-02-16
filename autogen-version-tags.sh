#!/bin/sh

# Usage:
#   You should run this from *within* the
#   releases/<packagename>/<version> directory.

version=`pwd | sed 's/.*\///g'`
comp=`pwd | cut -d / -f 2`

echo $version

for i in `find . -name \*.php`; do perl -p -i -e "s/\/\/autogentag\/\//$version/g" $i; done
for i in `find . -name \*.php`; do perl -p -i -e "s/\/\/autogen\/\//$version/g" $i; done

date=`date +"%A %d %B %Y"`
perl -p -i -e "s/$version\ \-\ \[RELEASEDATE\]/$version - $date/" ChangeLog ../../../trunk/$comp/ChangeLog
