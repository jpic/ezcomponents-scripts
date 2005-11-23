#!/usr/bin/php
<?php

define( 'CHANNEL_URI',          'components.ez.no' );
define( 'PACKAGE_NAME',         'eZComponents' );
define( 'PACKAGE_SUMMARY',      'Super package to install a complete release of eZ Enterprise Components.' );
define( 'PACKAGE_DESCRIPTION',  'This super package provides dependencies to every other eZ Enterprise Component to install those all at once. To perform this, simply do <$ pear install -a ' . PACKAGE_NAME . '>.');
define( 'PACKAGE_LICENSE',      'New BSD');

$releasesPath = realpath( '.' . DIRECTORY_SEPARATOR . 'releases' );

/**
 * Package file manager for package.xml 2.
 */
require_once 'PEAR/PackageFileManager2.php';

/**
 * Autoload ezc classes 
 * 
 * @param string $class_name 
 */
function __autoload( $class_name )
{
    require_once("packages/Base/trunk/src/base.php");
    if ( strpos( $class_name, "_" ) !== false )
    {
        $file = str_replace( "_", "/", $class_name ) . ".php";
        $val = require_once( $file );
        if ( $val == 0 )
            return true;
        return false;
    }
    ezcBase::autoload( $class_name );
}

// Output handler
$output = new ezcConsoleOutput(
    array( 
        'format' => array( 
            'help' => array( 
                'color' => 'magenta',
            ),
            'info' => array( 
                'color' => 'blue',
                'style' => 'bold',
            ),
            'version' => array( 
                'color' => 'red',
            ),
        ),
    )
);

// Standard text
$output->outputText( "\neZ Enterprise Components super-package creator\n", 'info' );
$output->outputText( "Version: ", 'info' );
$output->outputText( "0.1.0\n\n", 'version' );

// Parameter handling
$parameter = new ezcConsoleParameter();
$parameter->registerParam('v', 'version', 
    array( 
        'type'      => ezcConsoleParameter::TYPE_STRING,
        'shorthelp' => 'Version number of the release version to create.',
        'longhelp'  => 'Version number of the release version to create. The number must reflect a release file with the named version number below <svn/releases/>.',
    )
);
$parameter->registerParam('h', 'help', 
    array( 
        'type'      => ezcConsoleParameter::TYPE_NONE,
        'shorthelp' => 'Create a super-package package.xml file for the given version number.',
        'longhelp'  => 'This tool can reate a super-package package.xml file that has dependencies to every other component package. Provide the current releases version number to the -v parameter to run the script.',
    )
);
$parameter->registerParam('d', 'debug', 
    array( 
        'type'      => ezcConsoleParameter::TYPE_NONE,
        'shorthelp' => 'Switch tool into debugging mode.',
        'longhelp'  => 'Sets the tool to debugging mode. Instead of writing the package.xml file it will be dumped to stdout.',
    )
);

// Attempt to process parameters
try
{
    $parameter->process();
}
catch ( ezcConsoleParameterException $e )
{
    die( $options->styleText( $e->getMessage(), 'failure' ) );
}

// Output help
if ($parameter->getParam( '-h' ) !== false || $parameter->getParam( '-v' ) === false ) 
{
    $output->outputText( "Usage:\n\n", 'help' );
    $output->outputText( "$ " . __FILE__ . " -v <version> -s <state>\n", 'help' );
    $output->outputText( "Creates a super-package package file for the named release version and stability.\n\n", 'help' );
    $table = ezcConsoleTable::create( $parameter->getHelp( true ), $output, array( 'width' => 80, 'cols' => 2), array( 'lineFormat' => 'help' ) );
    $table->outputTable();
    die( "\n\n" );
}

// Grab releases info
$releasePath = $releasesPath . DIRECTORY_SEPARATOR . $parameter->getParam( '-v' );

if ( !file_exists( $releasePath ) || !is_readable( $releasePath ) )
{
    die( $output->styleText( "Release file <$releasePath> is not readable or does not exist.\n", 'failure' ) );
}

if ( ( $releaseDef = file( $releasePath ) ) === false )
{
    die( $output->styleText( "Release file <$releasePath> could not be read.", 'failure' ) );
}

// Create release dir, if not exists
$packagePath = DIRECTORY_SEPARATOR . 'tmp' . DIRECTORY_SEPARATOR . 'Components';
if ( !is_dir( $packagePath ) && mkdir( $packagePath, 0700, true ) === false )
{
    die( $output->styleText( "Error creating packaging directory <$packagePath>.", 'failure' ) );
}
$packagePath = realpath( $packagePath );
// Add dummy file
file_put_contents( $packagePath . DIRECTORY_SEPARATOR . 'DUMMY', 'ezc' );

// Package file manager
$pkg = new PEAR_PackageFileManager2;
$e = $pkg->setOptions(
    array(
        'packagedirectory'  => $packagePath,
        'baseinstalldir'    => 'ezc',
        'simpleoutput'      => true,
        'filelistgenerator' => 'file',
    )
);
if ( PEAR::isError( $e ) )
    die( $output->styleText( "Error creating file manager: <" . $e->getMessage() . ">.\n", 'failure' ) );

foreach ( $releaseDef as $release )
{
    if ( substr( $release, 0, 1 ) === '#' ) 
    {
        continue;    
    }
    $releaseData = array_map( 'trim', explode( ': ', $release ) );
    $e = $pkg->addPackageDepWithChannel( 'required', $releaseData[0], CHANNEL_URI, $releaseData[1], false, $releaseData[1] );
    if ( PEAR::isError( $e ) )
        die( $output->styleText( "Error in PackageFileManager2: <" . $e->getMessage() . ">.\n", 'failure' ) );
}


$e = $pkg->setPackage( PACKAGE_NAME );
if ( PEAR::isError( $e ) )
    die( $output->styleText( "Error in PackageFileManager2: <" . $e->getMessage() . ">.\n", 'failure' ) );
$e = $pkg->setSummary( PACKAGE_SUMMARY );
if ( PEAR::isError( $e ) )
    die( $output->styleText( "Error in PackageFileManager2: <" . $e->getMessage() . ">.\n", 'failure' ) );
$e = $pkg->setDescription( PACKAGE_DESCRIPTION );
if ( PEAR::isError( $e ) )
    die( $output->styleText( "Error in PackageFileManager2: <" . $e->getMessage() . ">.\n", 'failure' ) );
$e = $pkg->setChannel( CHANNEL_URI );
if ( PEAR::isError( $e ) )
    die( $output->styleText( "Error in PackageFileManager2: <" . $e->getMessage() . ">.\n", 'failure' ) );

$version   = $parameter->getParam( '-v' );
$stability = ( strpos( $version, 'beta' ) !== false ) ? 'beta' : 'stable';

$e = $pkg->setReleaseStability( $stability );
if ( PEAR::isError( $e ) )
    die( $output->styleText( "Error in PackageFileManager2: <" . $e->getMessage() . ">.\n", 'failure' ) );
$e = $pkg->setAPIStability( $stability );
if ( PEAR::isError( $e ) )
    die( $output->styleText( "Error in PackageFileManager2: <" . $e->getMessage() . ">.\n", 'failure' ) );
$e = $pkg->setReleaseVersion( $version );
if ( PEAR::isError( $e ) )
    die( $output->styleText( "Error in PackageFileManager2: <" . $e->getMessage() . ">.\n", 'failure' ) );
$e = $pkg->setAPIVersion( $version );
if ( PEAR::isError( $e ) )
    die( $output->styleText( "Error in PackageFileManager2: <" . $e->getMessage() . ">.\n", 'failure' ) );

$e = $pkg->setLicense( PACKAGE_LICENSE );
if ( PEAR::isError( $e ) )
    die( $output->styleText( "Error in PackageFileManager2: <" . $e->getMessage() . ">.\n", 'failure' ) );

$e = $pkg->setNotes( 'Meta package to install all eZ Enterprise Components at once.' );
if ( PEAR::isError( $e ) )
    die( $output->styleText( "Error in PackageFileManager2: <" . $e->getMessage() . ">.\n", 'failure' ) );

$e = $pkg->setPackageType( 'php' );
if ( PEAR::isError( $e ) )
    die( $output->styleText( "Error in PackageFileManager2: <" . $e->getMessage() . ">.\n", 'failure' ) );

$e = $pkg->setPhpDep( '5.1.0' );
if ( PEAR::isError( $e ) )
    die( $output->styleText( "Error in PackageFileManager2: <" . $e->getMessage() . ">.\n", 'failure' ) );
$e = $pkg->setPearinstallerDep( '1.4.2' );
if ( PEAR::isError( $e ) )
    die( $output->styleText( "Error in PackageFileManager2: <" . $e->getMessage() . ">.\n", 'failure' ) );

$e = $pkg->addGlobalReplacement( 'pear-config', '@php_dir@', 'php_dir' );
if ( PEAR::isError( $e ) )
    die( $output->styleText( "Error in PackageFileManager2: <" . $e->getMessage() . ">.\n", 'failure' ) );

$e = $pkg->addRelease();
if ( PEAR::isError( $e ) )
    die( $output->styleText( "Error in PackageFileManager2: <" . $e->getMessage() . ">.\n", 'failure' ) );

$e = $pkg->addMaintainer( 'lead', 'ezc', 'eZ components team', 'ezc@ez.no' );
if ( PEAR::isError( $e ) )
    die( $output->styleText( "Error in PackageFileManager2: <" . $e->getMessage() . ">.\n", 'failure' ) );

$e = $pkg->generateContents();
if ( PEAR::isError( $e ) )
    die( $output->styleText( "Error in PackageFileManager2: <" . $e->getMessage() . ">.\n", 'failure' ) );

$debug = $parameter->getParam( '-d' ) !== false ? true : false;
if ( $debug )
{
    $e = $pkg->debugPackageFile();
    if ( PEAR::isError( $e ) )
        die( $output->styleText( "Error in PackageFileManager2: <" . $e->getMessage() . ">.\n", 'failure' ) );
}
else 
{
    $e = $pkg->writePackageFile();
    if ( PEAR::isError( $e ) )
        die( $output->styleText( "Error in PackageFileManager2: <" . $e->getMessage() . ">.\n", 'failure' ) );
}

// Output success
$output->outputText( "\nSuccesfully finished operation. Thanks for using this hacky script!\n\n", 'success' );

?>
