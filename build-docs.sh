#!/bin/sh

if test $# != 1; then
	echo "Usage: scripts/build-docs.sh <version>"
	exit 0;
fi

j=`php scripts/list-export-dirs.php $1`

echo "Writing config file"
php scripts/build-php-doc-config.php $1 > /tmp/doc-components.ini || exit 1

rm -rf /home/httpd/ezcomponents.docfix || exit 1
cd /home/httpd || exit 2
for i in $j; do
	echo "Checking out $i"
	svn co -q http://svn.ez.no/svn/ezcomponents/packages/$i/src ezcomponents.docfix/packages/$i/src || exit 3
	svn co -q http://svn.ez.no/svn/ezcomponents/packages/$i/docs ezcomponents.docfix/packages/$i/docs || exit 3
done
echo "Checking out scripts"
svn co -q http://svn.ez.no/svn/ezcomponents/scripts ezcomponents.docfix/scripts || exit 3

cd ezcomponents.docfix || exit 4
echo "Removing 'array' keyword because of a bug in phpdoc"
scripts/fix-docs-array.sh || exit 5
rm -rf /home/httpd/html/components/phpdoc_gen || exit 6
rm -rf /home/httpd/html/components/cdocs.tgz || exit 7

echo "Running php documentor"
/usr/local/bin/phpdoc -q -c /tmp/doc-components.ini | grep -v Ignored | grep -v Writing | grep -v Notice || exit 8
./scripts/setup-env.sh
cd packages
echo "Generating Tutorials:"
for i in $j; do
	if test -f $i/docs/tutorial.txt; then
		comp=`echo $i | cut -d / -f 1`
		echo "* $comp"
		php ../scripts/render-tutorial.php -c $comp -t /home/httpd/html/components/phpdoc_gen/ezcomponents/$1 -v $1
	else
		echo '<div class="attribute-heading"><h1>'$i'</h1></div>' > /home/httpd/html/components/phpdoc_gen/ezcomponents/$1/introduction_$comp.html
		echo '<b>[ <a href="introduction_'$i'.html" class="menu">Introduction</a> ]</b>' >> /home/httpd/html/components/phpdoc_gen/ezcomponents/$1/introduction_$comp.html
		echo '<b>[ <a href="classtrees_'$i'.html" class="menu">Class tree</a> ]</b>' >> /home/httpd/html/components/phpdoc_gen/ezcomponents/$1/introduction_$comp.html
		echo '<b>[ <a href="elementindex_'$i'.html" class="menu">Element index</a> ]</b>' >> /home/httpd/html/components/phpdoc_gen/ezcomponents/$1/introduction_$comp.html
		echo "<h1>No introduction available for $i</h1>" >> /home/httpd/html/components/phpdoc_gen/ezcomponents/$1/introduction_$comp.html
	fi
done
cd ..
cd /home/httpd/html/components || exit 10
tar -czf cdocs.tgz phpdoc_gen || exit 11
