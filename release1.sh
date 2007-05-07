#!/bin/bash

if test $# != 1; then
	echo "Usage: ./scripts/release1.sh [component]";
	exit;
fi

component=$1
echo

# figure out if we need to release from a branch or not
parts=`echo $component | cut -d / -s -f 2`;
if test "$parts" == ""; then
	branch='trunk';
	prefix='../..';
	unittestcmd='UnitTest/src/runtests.php';
	logfilename=$component
else
	branch='stable';
	prefix='../../..';
	unittestcmd='../trunk/UnitTest/src/runtests.php -r stable';
	logfilename=`echo $component | tr / -`;
fi

# figure out the DSNs to use
if test "$component" == "Database" \
	-o "$component" == "DatabaseSchema" \
	-o "$component" == "EventLogDatabaseTiein" \
	-o "$component" == "PersistentObject" \
	-o "$component" == "PersistentObjectDatabaseSchemaTiein"; then
	dsns="mysql://root@localhost/ezc sqlite://:memory: sqlite:///tmp/test.sqlite pgsql://ezc:ezc@localhost/ezc"
else
	dsns="sqlite://:memory:";
fi

cd $branch

echo "* Checking line endings"
cd $component
status=`$prefix/scripts/check-end-of-file-marker.sh`
if test "$status" != ""; then
	echo
	echo "Aborted: Line ending problems in:"
	echo $status
	exit
fi
cd - >/dev/null

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
for dsn in $dsns; do
	echo "  - $dsn"
	php $unittestcmd --verbose -D $dsn $component |tee /tmp/test-$logfilename.log
	testresult=`cat /tmp/test-$logfilename.log | grep FAILURES`;
	if test "$testresult" == "FAILURES!"; then
		echo
		echo "Aborted: TESTS FAILED";
		exit
	fi
done

echo
echo "All clear"
