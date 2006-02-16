#!/bin/sh

if test $# -lt 1; then
	echo "Usage: scripts/build-docs.sh targetversion <releaseversion>"
	exit 0;
fi

if test $# == 1; then
	writeas=$1
	release=$1
fi

if test $# == 2; then
	writeas=$1
	release=$2
fi

j=`php scripts/list-export-dirs.php $release`

echo "Writing config file"
php scripts/build-php-doc-config.php $writeas $release > /tmp/doc-components.ini || exit 1

rm -rf /home/httpd/ezcomponents.docfix || exit 1
cd /home/httpd || exit 2

for i in $j; do
	echo "Checking out $i"
	svn co -q http://svn.ez.no/svn/ezcomponents/$i/src ezcomponents.docfix/$i/src || exit 3
	svn co -q http://svn.ez.no/svn/ezcomponents/$i/docs ezcomponents.docfix/$i/docs || exit 3
done
echo "Checking out scripts"
svn co -q http://svn.ez.no/svn/ezcomponents/scripts ezcomponents.docfix/scripts || exit 3
svn co -q http://svn.ez.no/svn/ezcomponents/docs ezcomponents.docfix/docs || exit 3

cd ezcomponents.docfix || exit 4
echo "Removing 'array' keyword because of a bug in phpdoc"
scripts/fix-docs-array.sh || exit 5
rm -rf /home/httpd/html/components/phpdoc_gen || exit 6
rm -rf /home/httpd/html/components/cdocs.tgz || exit 7
mkdir -p /home/httpd/html/components/phpdoc_gen/ezcomponents/$writeas || exit 8

echo "Copying overview"
cp docs/overview.tpl /home/httpd/html/components/phpdoc_gen/ezcomponents || 12

echo "Running php documentor"
/usr/local/bin/phpdoc -q -c /tmp/doc-components.ini | grep -v Ignored || exit 8
./scripts/setup-env.sh

echo "Writing left_menu_comp.tpl"
cat > /home/httpd/html/components/phpdoc_gen/ezcomponents/left_menu_comp.tpl << EOF
<div class="attribute-heading">
<h2 class="bullet">eZ components</h2>
</div>

<div class="boxcontent">
<div id="quicklinks">
{let \$indexDir = ezsys( 'indexdir' )}
<h2>Getting Started</h2>
<ul>
<li><a href="http://ez.no/community/articles/an_introduction_to_ez_components">Installation</a></li>
<li><a href="{concat(\$indexDir, '/components/view/(file)/$writeas/tutorials.html')}">Tutorials</a></li>
</ul>

<h2>Components</h2>
<ul>
EOF
cat > /home/httpd/html/components/phpdoc_gen/ezcomponents/$writeas/left_menu_comp.html << EOF
<div class="attribute-heading">
<h2 class="bullet">eZ components</h2>
</div>

<div class="boxcontent">
<div id="quicklinks">
<h2>Getting Started</h2>
<ul>
<li><a href="http://ez.no/community/articles/an_introduction_to_ez_components">Installation</a></li>
<li><a href="/components/phpdoc_gen/ezcomponents/$writeas/tutorials.html">Tutorials</a></li>
</ul>

<h2>Packages</h2>
<ul>
EOF

echo "Generating Tutorials:"
echo "* Tutorials overview page start"

cat >> /home/httpd/html/components/phpdoc_gen/ezcomponents/$writeas/tutorials.tpl <<EOF
<div class="attribute-heading"><h1>Tutorials</h1></div>
<ul>
EOF

cp /home/httpd/html/components/phpdoc_gen/ezcomponents/$writeas/tutorials.tpl /home/httpd/html/components/phpdoc_gen/ezcomponents/$writeas/tutorials.html

for i in $j; do
	comp=`echo $i | cut -d / -f 2`
	if test -f $i/docs/tutorial.txt; then
		version=`echo "$i" | sed "s/\/$comp//"`
		echo "* $comp ($version)"
		php scripts/render-tutorial.php -c $comp -t /home/httpd/html/components/phpdoc_gen/ezcomponents/$writeas -v $version

		cat >> /home/httpd/html/components/phpdoc_gen/ezcomponents/$writeas/tutorials.tpl << EOF
<li><a href="introduction_$comp.html')}">$comp</a></li>
EOF
		cat >> /home/httpd/html/components/phpdoc_gen/ezcomponents/$writeas/tutorials.html << EOF
<li><a href="introduction_$comp.html">$comp</a></li>
EOF

	else
		echo '<div class="attribute-heading"><h1>'$comp'</h1></div>' > /home/httpd/html/components/phpdoc_gen/ezcomponents/$writeas/introduction_$comp.html
		echo '<b>[ <a href="introduction_'$comp'.html" class="menu">Tutorial</a> ]</b>' >> /home/httpd/html/components/phpdoc_gen/ezcomponents/$writeas/introduction_$comp.html
		echo '<b>[ <a href="classtrees_'$comp'.html" class="menu">Class tree</a> ]</b>' >> /home/httpd/html/components/phpdoc_gen/ezcomponents/$writeas/introduction_$comp.html
		echo '<b>[ <a href="elementindex_'$comp'.html" class="menu">Element index</a> ]</b>' >> /home/httpd/html/components/phpdoc_gen/ezcomponents/$writeas/introduction_$comp.html
		echo "<h1>No introduction available for $comp</h1>" >> /home/httpd/html/components/phpdoc_gen/ezcomponents/$writeas/introduction_$comp.html
	fi

	cat >> /home/httpd/html/components/phpdoc_gen/ezcomponents/left_menu_comp.tpl << EOF
<li><a href="{concat(\$indexDir, '/components/view/(file)/$writeas/classtrees_$comp.html')}">$comp</a></li>
EOF
	cat >> /home/httpd/html/components/phpdoc_gen/ezcomponents/$writeas/left_menu_comp.html << EOF
<li><a href="/components/phpdoc_gen/ezcomponents/$writeas/classtrees_$comp.html">$comp</a></li>
EOF
done

cat >> /home/httpd/html/components/phpdoc_gen/ezcomponents/left_menu_comp.tpl << EOF
</ul>
<hr/>

<ul>
<li><a href="{concat(\$indexDir, '/components/view/(file)/$writeas/allclassesindex.html')}">All Classes</a></li>
<li><a href="{concat(\$indexDir, '/components/view/(file)/$writeas/elementindex.html')}">All Elements</a></li>
</ul>
{/let}

</div>
</div>
EOF

cat >> /home/httpd/html/components/phpdoc_gen/ezcomponents/$writeas/left_menu_comp.html << EOF
</ul>
<hr/>

<ul>
<li><a href="/components/phpdoc_gen/ezcomponents/$writeas/allclassesindex.html">All Classes</a></li>
<li><a href="/components/phpdoc_gen/ezcomponents/$writeas/elementindex.html">All Elements</a></li>
</ul>

</div>
</div>
EOF

echo "* Tutorials overview page end"

cat >> /home/httpd/html/components/phpdoc_gen/ezcomponents/$writeas/tutorials.tpl << EOF
</ul>
EOF
cat >> /home/httpd/html/components/phpdoc_gen/ezcomponents/$writeas/tutorials.html << EOF
</ul>
EOF

cd ..
cd /home/httpd/html/components || exit 10
tar -czf cdocs.tgz phpdoc_gen || exit 11
