#!/usr/local/bin/php
<?php
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


$basePackageDir = "/tmp/ezc". md5( time() );
$packageDir = $basePackageDir . "/ezcomponents-$version";
$packageList = array();

mkdir( $packageDir, 0777, true );
addPackages( $fileName, $packageDir );
setupAutoload( $packageDir, $packageList );
addAditionalFiles( $packageDir, $packageList );
setBaseNonDevel( $packageDir );

echo "Creating Archives: ";
`cd $basePackageDir; tar cvjf /tmp/ezcomponents-$version.tar.bz2 .`;
echo "tar.bz2 ";
`cd $basePackageDir; zip -r /tmp/ezcomponents-$version.zip ezcomponents-$version`;
echo "zip ";
`rm -rf $basePackageDir`;
echo "Done\n\n";

function addPackages( $fileName, $packageDir )
{
    // Open ChangeLog file
    $fp = fopen( "$packageDir/ChangeLog", "w" );
    echo "Exporting packages from SVN: \n";
    $definition = file( $fileName );
    foreach ( $definition as $defLine )
    {
        if ( preg_match( '@([A-Za-z]+):\s+([A-Za-z0-9.]+)@', $defLine, $matches ) )
        {
            $changeLog = addPackage( $packageDir, $matches[1], $matches[2] );
            $title = "Component: {$matches[1]}";
            $titleHeader = str_repeat( '=', strlen( $title ) );
            fwrite( $fp, "$titleHeader\n$title\n$titleHeader\n" );
            fwrite( $fp, $changeLog );
            fwrite( $fp, "\n\n" );
        }
    }
    fclose( $fp );
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
    echo "RR ";
    @unlink( "$packageDir/$name/review.txt" );

    /* grab changelog */
    echo "C ";
    $changelog = grabChangelog( "$packageDir/$name/ChangeLog", $version );

    /* remove design directory */
    echo "RD ";
    `rm -rf "$packageDir/$name/design"`;
    
    echo "Done\n";
    return $changelog;
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
    file_put_contents( "$packageDir/Base/src/base.php", str_replace( "libraryMode = \"devel\"", "libraryMode = \"tarball\"", file_get_contents( "$packageDir/Base/src/base.php" ) ) );
    echo "Done\n";
}

function grabChangelog( $path, $version )
{
    $data = array();
    $data = file( $path );
    $changelogData = array();
    $versionFound = false;
    foreach ( $data as $line )
    {
        $versionString = preg_quote( $version );
        if ( $versionFound && preg_match( "@^[012]\.[0-9](.+)\s-\s([A-Z][a-z]+)|(\[RELEASEDATE\])@", $line ) )
        {
            $versionFound = false;
        }
        if ( preg_match( "@^$versionString\s-\s@", $line ) )
        {
            $versionFound = true;
        }
        if ( $versionFound )
        {
            $changelogData[] = $line;
        }
    }
    // Remove version string from text itself
    unset( $changelogData[0] );
    return "\n" . trim( implode( '', $changelogData ) ) . "\n";
}
?>
