<?php
include 'scripts/get-packages-for-version.php';

if ( $argc < 3 )
{
    echo "Usage:\n\tscripts/build-php-doc-config.php <targetversion> <releaseversion> <source:on off>\n\tscripts/package.php 1.0beta1 trunk on\n\n";
    die();
}
$targetversion = $argv[1];
$releaseversion = $argv[2];
$source = $argv[3];
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
    $directories .= "/home/httpd/ezcomponents/$componentVersion,";
}

// strip last ,
$directories = substr( $directories, 0, -1 );

echo <<<ECHOEND
[Parse Data]
title = eZ Components Manual
hidden = false
parseprivate = off
javadocdesc = off
defaultcategoryname = NoCategoryName
defaultpackagename = NoPackageName

target = /home/httpd/html/components/phpdoc_gen/ezcomponents-$targetversion
directory = $directories

ignore = autoload/,*autoload.php,tests/,docs/,design/
output=HTML:ezComp:ezdocs
sourcecode = $source

ECHOEND;

?>
