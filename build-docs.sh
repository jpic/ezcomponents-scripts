#!/bin/sh

if test $# -lt 1; then
	echo "Usage: scripts/build-docs.sh <version> ..."
	exit 0;
fi

wd=`pwd`

rm -rf /home/httpd/html/components/phpdoc_gen || exit 6
rm -rf /home/httpd/html/components/cdocs.tgz || exit 7

rm -rf /home/httpd/ezcomponents.docfix || exit 1

mkdir -p /home/httpd/html/components/phpdoc_gen/ezcomponents

echo "Copying overview"
cp docs/overview.tpl /home/httpd/html/components/phpdoc_gen/ezcomponents || exit 12

echo "Preparing top left_menu_comp.tpl"
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
<li><a href="{concat(\$indexDir, '/components/view/(file)/latest/tutorials.html')}">Tutorials</a></li>
</ul>

<h2>Versions</h2>
<ul>
EOF
cat > /home/httpd/html/components/phpdoc_gen/ezcomponents/left_menu_comp.html << EOF
<div class="attribute-heading">
<h2 class="bullet">eZ components</h2>
</div>

<div class="boxcontent">
<div id="quicklinks">
<h2>Getting Started</h2>
<ul>
<li><a href="http://ez.no/community/articles/an_introduction_to_ez_components">Installation</a></li>
<li><a href="/components/phpdoc_gen/ezcomponents/$1/tutorials.html">Tutorials</a></li>
</ul>

<h2>Versions</h2>
<ul>
EOF

for release in "trunk latest $@"; do

mkdir -p /home/httpd/html/components/phpdoc_gen/ezcomponents/$release

if test ! $release == "trunk";
then
	echo "Update main index file"
	cat >> /home/httpd/html/components/phpdoc_gen/ezcomponents/left_menu_comp.tpl << EOF
<li><a href="{concat(\$indexDir, '/components/view/(file)/$release/')}">eZ components $release</a></li>
EOF
	cat >> /home/httpd/html/components/phpdoc_gen/ezcomponents/left_menu_comp.html << EOF
<li><a href="/components/phpdoc_gen/ezcomponents/$release/">eZ components $release</a></li>
EOF
fi

echo "Writing config file"
cd $wd
php scripts/build-php-doc-config.php $release $release > /tmp/doc-components.ini || exit 1

j=`php scripts/list-export-dirs.php $release`

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
mkdir -p /home/httpd/html/components/phpdoc_gen/ezcomponents/$release || exit 8

echo "Copying overview"
cp docs/overview_$release.tpl /home/httpd/html/components/phpdoc_gen/ezcomponents || 12

echo "Running php documentor"
/usr/local/bin/phpdoc -q -c /tmp/doc-components.ini | grep -v Ignored || exit 8
./scripts/setup-env.sh

echo "Writing left_menu_comp_$release.tpl"
cat > /home/httpd/html/components/phpdoc_gen/ezcomponents/left_menu_comp_$release.tpl << EOF
<div class="attribute-heading">
<h2 class="bullet">eZ components</h2>
</div>

<div class="boxcontent">
<div id="quicklinks">
{let \$indexDir = ezsys( 'indexdir' )}
<h2>Getting Started</h2>
<ul>
<li><a href="http://ez.no/community/articles/an_introduction_to_ez_components">Installation</a></li>
<li><a href="{concat(\$indexDir, '/components/view/(file)/$release/tutorials.html')}">Tutorials</a></li>
</ul>

<h2>Components</h2>
<ul>
EOF
cat > /home/httpd/html/components/phpdoc_gen/ezcomponents/left_menu_comp_$release.html << EOF
<div class="attribute-heading">
<h2 class="bullet">eZ components</h2>
</div>

<div class="boxcontent">
<div id="quicklinks">
<h2>Getting Started</h2>
<ul>
<li><a href="http://ez.no/community/articles/an_introduction_to_ez_components">Installation</a></li>
<li><a href="/components/phpdoc_gen/ezcomponents/$release/tutorials.html">Tutorials</a></li>
</ul>

<h2>Components</h2>
<ul>
EOF


cat > /home/httpd/html/components/phpdoc_gen/ezcomponents/$release/index.php << EOF
<?php
include '../overview_$release.tpl';
?>
EOF


echo "Generating Tutorials:"
echo "* Tutorials overview page start"

cat >> /home/httpd/html/components/phpdoc_gen/ezcomponents/$release/tutorials.tpl <<EOF
<div class="attribute-heading"><h1>Tutorials</h1></div>
<ul>
EOF

cp /home/httpd/html/components/phpdoc_gen/ezcomponents/$release/tutorials.tpl /home/httpd/html/components/phpdoc_gen/ezcomponents/$release/tutorials.html

for i in $j; do
	comp=`echo $i | cut -d / -f 2`
	if test -f $i/docs/tutorial.txt; then
		version=`echo "$i" | sed "s/\/$comp//" | sed "s/releases\///"`
		echo "* $comp ($version)"
		php scripts/render-tutorial.php -c $comp -t /home/httpd/html/components/phpdoc_gen/ezcomponents/$release -v $version

		cat >> /home/httpd/html/components/phpdoc_gen/ezcomponents/$release/tutorials.tpl << EOF
<li><a href="introduction_$comp.html')}">$comp</a></li>
EOF
		cat >> /home/httpd/html/components/phpdoc_gen/ezcomponents/$release/tutorials.html << EOF
<li><a href="introduction_$comp.html">$comp</a></li>
EOF

	else
		echo '<div class="attribute-heading"><h1>'$comp'</h1></div>' > /home/httpd/html/components/phpdoc_gen/ezcomponents/$release/introduction_$comp.html
		echo '<b>[ <a href="introduction_'$comp'.html" class="menu">Tutorial</a> ]</b>' >> /home/httpd/html/components/phpdoc_gen/ezcomponents/$release/introduction_$comp.html
		echo '<b>[ <a href="classtrees_'$comp'.html" class="menu">Class tree</a> ]</b>' >> /home/httpd/html/components/phpdoc_gen/ezcomponents/$release/introduction_$comp.html
		echo '<b>[ <a href="elementindex_'$comp'.html" class="menu">Element index</a> ]</b>' >> /home/httpd/html/components/phpdoc_gen/ezcomponents/$release/introduction_$comp.html
		echo "<h1>No introduction available for $comp</h1>" >> /home/httpd/html/components/phpdoc_gen/ezcomponents/$release/introduction_$comp.html
	fi

	cat >> /home/httpd/html/components/phpdoc_gen/ezcomponents/left_menu_comp_$release.tpl << EOF
<li><a href="{concat(\$indexDir, '/components/view/(file)/$release/classtrees_$comp.html')}">$comp</a> ($version)</li>
EOF
	cat >> /home/httpd/html/components/phpdoc_gen/ezcomponents/left_menu_comp_$release.html << EOF
<li><a href="/components/phpdoc_gen/ezcomponents/$release/classtrees_$comp.html">$comp</a> ($version)</li>
EOF
done

cat >> /home/httpd/html/components/phpdoc_gen/ezcomponents/left_menu_comp_$release.tpl << EOF
</ul>
<hr/>

<ul>
<li><a href="{concat(\$indexDir, '/components/view/(file)/$release/allclassesindex.html')}">All Classes</a></li>
<li><a href="{concat(\$indexDir, '/components/view/(file)/$release/elementindex.html')}">All Elements</a></li>
</ul>
{/let}

</div>
</div>
EOF

cat >> /home/httpd/html/components/phpdoc_gen/ezcomponents/left_menu_comp_$release.html << EOF
</ul>
<hr/>

<ul>
<li><a href="/components/phpdoc_gen/ezcomponents/$release/allclassesindex.html">All Classes</a></li>
<li><a href="/components/phpdoc_gen/ezcomponents/$release/elementindex.html">All Elements</a></li>
</ul>

</div>
</div>
EOF

echo "* Tutorials overview page end"

cat >> /home/httpd/html/components/phpdoc_gen/ezcomponents/$release/tutorials.tpl << EOF
</ul>
EOF
cat >> /home/httpd/html/components/phpdoc_gen/ezcomponents/$release/tutorials.html << EOF
</ul>
EOF

done

echo "Wrapping up index files"
cat >> /home/httpd/html/components/phpdoc_gen/ezcomponents/left_menu_comp.tpl << EOF
</ul>
</div>
</div>
EOF

cat >> /home/httpd/html/components/phpdoc_gen/ezcomponents/left_menu_comp.html << EOF
</ul>
</div>
</div>
EOF

cat > /home/httpd/html/components/phpdoc_gen/ezcomponents/index.php <<
<?php
include 'overview.tpl';
?>
EOF

cd ..
cd /home/httpd/html/components || exit 10
tar -czf cdocs.tgz phpdoc_gen || exit 11
