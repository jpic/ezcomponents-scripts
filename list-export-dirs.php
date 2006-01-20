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

foreach ( $elements as $component => $version)
{
	echo "$component/releases/$version\n";
}

?>
