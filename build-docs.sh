#!/bin/sh

rm -rf /home/httpd/ezcomponents.docfix || exit 1
cd /home/httpd || exit 2
svn co http://svn.ez.no/svn/ezcomponents ezcomponents.docfix || exit 3
cd ezcomponents.docfix || exit 4
echo "Removing 'array' keyword because of a bug in phpdoc"
scripts/fix-docs-array.sh || exit 5
rm -rf /home/httpd/html/components/phpdoc_gen || exit 6
rm -rf /home/httpd/html/components/cdocs.tgz || exit 7
/usr/local/bin/phpdoc -c ezcomponents.ini | grep -v Ignored || exit 8
./scripts/setup-env.sh
cd packages
echo "Generating Tutorials:"
for i in *; do
	if test -f $i/trunk/docs/tutorial.txt; then
		echo "* $i"
		php ../scripts/render-tutorial.php -c $i -t /home/httpd/html/components/phpdoc_gen/ezcomponents/1.0rc1
	else
		echo "<h1>No introduction available for $i</h1>" > /home/httpd/html/components/phpdoc_gen/ezcomponents/1.0rc1/introduction_$i.html
	fi
done
cd ..
cd /home/httpd/html/components || exit 10
tar -czf cdocs.tgz phpdoc_gen || exit 11
