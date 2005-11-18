#!/bin/sh

for i in `find . -name \*.php`; do php -l $i | grep -v "No syntax errors"; done
