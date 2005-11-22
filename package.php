#!/usr/local/bin/php
<?php
if ( $argc != 2 )
{
    echo "Usage:\n\tscripts/package.php <version>\n\tscripts/package.php 1.0beta1\n\n";
    die();
}
$fileName = "releases/{$argv[1]}";
if ( !file_exists( "$fileName" ) )
{
    echo "The releases file <$fileName> does not exist!\n\n";
    die();
}

//$packageDir = "/tmp/ezc". md5( time() );

`rm -rf /tmp/ezcTemp`;
$packageDir = "/tmp/ezcTemp";
$packageList = array();

mkdir( $packageDir );
addPackages( $fileName, $packageDir );
setupAutoload( $packageDir, $packageList );
addAditionalFiles( $packageDir, $packageList );
setBaseNonDevel( $packageDir );

function addPackages( $fileName, $packageDir )
{
    echo "Exporting packages from SVN: \n";
    $definition = file( $fileName );
    foreach ( $definition as $defLine )
    {
        if ( preg_match( '@([A-Za-z]+):\s+([A-Za-z0-9.]+)@', $defLine, $matches ) )
        {
            addPackage( $packageDir, $matches[1], $matches[2] );
        }
    }
}

function addPackage( $packageDir, $name, $version )
{
    echo sprintf( '* %-20s %-8s: ', $name, $version );
    
    $dirName = "packages/$name/releases/$version";
    if ( !is_dir( $dirName ) )
    {
        echo "release directory not found\n";
        return false;
    }
    $GLOBALS['packageList'][] = $name;

    /* exporting */
    echo "E ";
    `svn export http://svn.ez.no/svn/ezcomponents/packages/$name/releases/$version $packageDir/$name`;

    /* remove crappy files */
    echo "R ";
    @unlink( "$packageDir/$name/review.txt" );
    
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
    file_put_contents( "$packageDir/Base/src/base.php", str_replace( "developmentMode = true", "developmentMode = false", file_get_contents( "$packageDir/Base/src/base.php" ) ) );
    echo "Done\n";
}
?>
