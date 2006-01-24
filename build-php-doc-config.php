<?php
include 'scripts/get-packages-for-version.php';

if ( $argc != 2 )
{
    echo "Usage:\n\tscripts/package.php <version>\n\tscripts/package.php 1.0beta1\n\n";
    die();
}
$version = $argv[1];
$fileName = "releases/$version";
if ( !file_exists( "$fileName" ) )
{
    echo "The releases file <$fileName> does not exist!\n\n";
    die();
}

$directories = '';

$elements = fetchVersionsFromReleaseFile( $fileName );

foreach ( $elements as $component => $componentVersion )
{
    if ( $componentVersion != 'trunk' )
    {
        $componentVersion = "releases/$componentVersion";
    }
    $directories .= "/home/httpd/ezcomponents.docfix/packages/$component/$componentVersion,";
}

// strip last ,
$directories = substr( $directories, 0, -1 );

echo <<<ECHOEND
[Parse Data]
title = eZ components Manual
hidden = false
parseprivate = off
javadocdesc = off
defaultcategoryname = NoCategoryName
defaultpackagename = NoPackageName

target = /home/httpd/html/components/phpdoc_gen/ezcomponents/$version
directory = $directories

ignore = autoload/,*autoload.php,tests/,docs/
output=HTML:Smarty:ezdocs
sourcecode = on

ECHOEND;

?>
