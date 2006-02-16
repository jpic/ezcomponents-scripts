<?php
include 'scripts/get-packages-for-version.php';

if ( $argc < 2 )
{
    echo "Usage:\n\tscripts/build-php-doc-config.php <targetversion> <releaseversion>\n\tscripts/package.php 1.0beta1 trunk\n\n";
    die();
}
$targetversion = $releaseversion = $argv[1];
if ( $argc == 3 )
{
    $releaseversion = $argv[2];
}
$fileName = "release-info/$releaseversion";
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
        $componentVersion = "releases/$component/$componentVersion";
    }
    else
    {
        $componentVersion = "trunk/$component";
    }
    $directories .= "/home/httpd/ezcomponents.docfix/$componentVersion,";
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

target = /home/httpd/html/components/phpdoc_gen/ezcomponents/$targetversion
directory = $directories

ignore = autoload/,*autoload.php,tests/,docs/
output=HTML:ezComp:ezdocs
sourcecode = on

ECHOEND;

?>
