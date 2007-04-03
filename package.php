#!/usr/local/bin/php
<?php
include 'scripts/get-packages-for-version.php';

if ( $argc != 2 )
{
    echo "Usage:\n\tscripts/package.php <version>\n\tscripts/package.php 1.0beta1\n\n";
    die();
}
$version = $argv[1];
$fileName = "release-info/$version";
if ( !file_exists( "$fileName" ) )
{
    echo "The releases file <$fileName> does not exist!\n\n";
    die();
}


$basePackageDir = "/tmp/ezc". md5( time() );
$packageDir = $basePackageDir . "/ezcomponents-$version";
$packageList = array();

mkdir( $packageDir, 0777, true );

grabChangelog( $fileName, $packageDir );
addPackages( $fileName, $packageDir );
setupAutoload( $packageDir, $packageList );
addAditionalFiles( $packageDir, $packageList );
setBaseNonDevel( $packageDir );

echo "Creating Archives: ";
`cd $basePackageDir; tar cvjf /tmp/ezcomponents-$version.tar.bz2 .`;
echo "tar.bz2 ";
`cd $basePackageDir; zip -r /tmp/ezcomponents-$version.zip ezcomponents-$version`;
echo "zip ";
echo "Done\n";

echo "Generating HTML version of changelog: ";
`cd $basePackageDir; rst2html ezcomponents-$version/ChangeLog > /tmp/ezcomponents-$version.changelog.html`;
echo "Done\n";

echo "scp-ing to tequila: ";
`scp /tmp/ezcomponents-$version* tequila:/home/httpd/html/components/downloads`;
echo "Done\n\n";
`rm -rf $basePackageDir`;

function grabChangelog( $fileName, $packageDir )
{
    // Open ChangeLog file
    $fp = fopen( "$packageDir/ChangeLog", "w" );
    
    $cl = file( $fileName );
    $i = 2;
    do {
        fwrite( $fp, $cl[$i] );
        $i++; 
    } while ( $cl[$i] != "PACKAGES\n" );
    fclose( $fp );
}

function addPackages( $fileName, $packageDir )
{
    echo "Exporting packages from SVN: \n";

    $elements = fetchVersionsFromReleaseFile( $fileName );
    foreach ( $elements as $component => $versionNr )
    {
        addPackage( $packageDir, $component, $versionNr );
    }
}

function addPackage( $packageDir, $name, $version )
{
    echo sprintf( '* %-40s %-12s: ', $name, $version );
    
    $dirName = "releases/$name/$version";
    if ( !is_dir( $dirName ) )
    {
        echo "release directory not found\n";
        return false;
    }
    $GLOBALS['packageList'][] = $name;

    /* exporting */
    echo "E ";
    `svn export http://svn.ez.no/svn/ezcomponents/releases/$name/$version $packageDir/$name`;

    /* remove crappy files */
    echo "RR ";
    @unlink( "$packageDir/$name/review.txt" );

    /* remove design directory */
    echo "RD ";
    `rm -rf "$packageDir/$name/design"`;
    
    echo "Done\n";
}

function setupAutoload( $packageDir, $packageList )
{
    echo "Setting up autoload structure: ";
    mkdir( "$packageDir/autoload" );
    foreach ( $packageList as $packageName )
    {
        echo "$packageName ";
        $glob = glob( "$packageDir/$packageName/src/*_autoload.php" );
        foreach( $glob as $fileName )
        {
            $targetName = basename( $fileName );
            copy( $fileName, "$packageDir/autoload/$targetName" );
            unlink( $fileName );
        }
    }
    echo "\n";
}

function addAditionalFiles( $packageDir, $packageList )
{
    echo "Adding additional files: ";
    echo "LICENSE ";
    copy( "LICENSE", "$packageDir/LICENSE" );

    echo "descriptions.txt ";
    $f = fopen( "$packageDir/descriptions.txt", "w" );
    foreach ( $packageList as $packageName )
    {
        $descFileName = "$packageDir/$packageName/DESCRIPTION";
        if ( file_exists( $descFileName ) )
        {
            fwrite( $f, "$packageName\n" . str_repeat( '-', strlen( $packageName ) ) . "\n" );
            $desc = file_get_contents( $descFileName );
            fwrite( $f, "$desc\n" );
        }
    }
    fclose( $f );

    echo "\n";
}

function setBaseNonDevel( $packageDir )
{
    echo "Configuring Base package in release mode: ";
    file_put_contents( "$packageDir/Base/src/base.php", str_replace( "libraryMode = \"devel\"", "libraryMode = \"tarball\"", file_get_contents( "$packageDir/Base/src/base.php" ) ) );
    echo "Done\n";
}

?>
