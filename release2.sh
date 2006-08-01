#!/bin/bash

if test $# != 3; then
	echo "Usage: ./scripts/release1.sh [component] [baseversion] [version]";
	exit;
fi

component=$1
baseversion=$2
version=$3
echo

echo "* Copying to release branch"
svn cp trunk/$component releases/$component/$version
cd releases/$component/$version
../../../scripts/autogen-version-tags.sh
cd ../../..

echo "* Updating release-info/latest"
cat release-info/latest | sed "s/$component:.*/$component: $version/" > /tmp/release-info
mv /tmp/release-info release-info/latest

echo "* Committing component to SVN"
svn commit -m "- Released $component version $version" trunk/$component releases/$component/$version release-info/latest

echo "* Creating PEAR package"
scripts/create_pear_package.php -v $version -b $baseversion -p $component

echo
echo "All clear"
