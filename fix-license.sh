#!/bin/sh

for i in `find . -name \*.php`; do perl -p -i -e "s/^\s\*\s\@license(.*)/ * \@license BSD {\@link http:\/\/ez.no\/licenses\/bsd}/g" $i; done
