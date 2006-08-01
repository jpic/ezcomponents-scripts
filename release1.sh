#!/bin/bash

if test $# != 1; then
	echo "Usage: ./scripts/release1.sh [component]";
	exit;
fi

component=$1
echo
cd trunk

echo "* Checking line endings"
cd $component
status=`../../scripts/check-end-of-file-marker.sh`
if test "$status" != ""; then
	echo
	echo "Aborted: Line ending problems in:"
	echo $status
	exit
fi
cd ..

echo "* Checking for local modifications"
status=`svn st $component`
if test "$status" != ""; then
	echo
	echo "Aborted: Local modifications:";
	echo $status
	exit
fi

echo "* Checking RST syntax in ChangeLog"
rst2html -q --exit-status=warning $component/ChangeLog > /home/httpd/html/test.html
if test $? != 0; then
	echo
	echo "Aborted: RST Failed"
	exit
fi

echo "* Running tests"
php UnitTest/src/runtests.php -D "mysql://root:wee123@localhost/ezc" $component |tee /tmp/test-$component.log
testresult=`cat /tmp/test-$component.log | grep FAILURES`;
if test "$testresult" == "FAILURES!"; then
	echo
	echo "Aborted: TESTS FAILED";
	exit
fi

echo
echo "All clear"
