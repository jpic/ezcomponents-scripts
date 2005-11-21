#!/bin/sh

for i in `find . -name \*.php`; do php -l $i | grep -v "No syntax errors"; done

for i in `find . -name \*.php`; do
	cat $i | \
		sed -e 's/[[:space:]]while(/ while (/' | \
		sed -e 's/[[:space:]]if(/ if (/' | \
		sed -e 's/[[:space:]]catch(/ catch (/' | \
		sed -e 's/[[:space:]]foreach(/ foreach (/' | \
		sed -e 's/[[:space:]]switch(/ switch (/' > /tmp/temporary.php
	cp /tmp/temporary.php $i
done

echo "Checking for wrong braces placement for functions"
grep -rn "function" * | grep "{" | grep -v svn | grep "\.php:"
grep -rn "class" * | grep "{" | grep -v svn | grep "\.php:"
grep -rn "interface" * | grep "{" | grep -v svn | grep "\.php:"

echo "Checking for wrong if/else + brackets"
grep -rn "if" * | grep "{" | grep -v svn | grep "\.php:"
grep -rn "else" * | grep "{" | grep -v svn | grep "\.php:"


echo "Checking for wrong 'try' syntax':"
grep -nr "try" * | grep "[}{]" | grep -v svn-base | grep "\.php"

echo "Checking for wrong 'catch' syntax':"
grep -nr "catch" * | grep "[}{]" | grep -v svn-base | grep "\.php"

echo "Checking for wrong closing bracket:"
grep -nr "[^[:space:](]);" * | grep -v svn-base | grep -v tests | grep "\.php"

echo "Checking for wrong opening bracket:"
grep -nr "([^[:space:]C)]" * | grep -v svn-base | grep -v tests | grep -v "(string)" | grep -v "(int)" | grep -v "(float)" | grep -v "*" | grep "\.php:"

for i in `find . -name \*.php`; do php -l $i | grep -v "No syntax errors"; done
