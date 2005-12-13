#!/usr/bin/php -derror_reporting=E_ALL
<?php
/**
 * Script for generating package.xml files for eZ Enterprise Components.
 *
 * @package Base
 * @version //autogentag//
 * @copyright Copyright (C) 2005 eZ systems as. All rights reserved.
 * @license LGPL {@link http://www.gnu.org/copyleft/lesser.html}
 * @filesource
 */

/**
 * Package file manager for package.xml 2.
 */
require_once 'PEAR/PackageFileManager2.php';

// {{{ __autoload()

/**
 * Autoload ezc classes 
 * 
 * @param string $class_name 
 */
function __autoload( $class_name )
{
    require_once("Base/trunk/src/base.php");
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

// }}}

class ezcPackageManager {

    protected $paths = array( 
        'package' => '',
        'install' => '',
    );

    // {{{ CHANNEL

    /**
     * Channel name to use in pakage.xml files
     */
    const CHANNEL = 'components.ez.no';

    // }}}
    // {{{ LICENSE

    /**
     * License. 
     */
    const LICENSE = 'New BSD';

    // }}}

    // {{{ $output

    /**
     * ezcConsoleOutput object. 
     * 
     * @var object(ezcConsoleOutput)
     */
    protected $output;

    // }}}
    // {{{ $parameter

    /**
     * ezcConsoleParameter object. 
     * 
     * @var object(ezcConsoleParameter)
     */
    protected $parameter;

    // }}}
    // {{{ $validStates

    /**
     * Valid stability states. 
     * 
     * @var array
     * @access protected
     */
    protected $validStates = array( 
        'devel',
        'alpha',
        'beta',
        'stable',
        'guess',
    );

    // }}}

    // {{{ __construct()

    /**
     * Create a new package manager
     */
    public function __construct()
    {
        // Init
        $this->output = new ezcConsoleOutput(
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
        $this->processParameter();
    }

    // }}}
    // {{{ run()

    /**
     * Run the package manager. 
     */
    public function run()
    {
        // General info output
        $this->output->outputText( "\neZ Enterprise Components package manager.\n", 'info' );
        $this->output->outputText( "Version: ", 'info' );
        $this->output->outputText( "0.1.0\n\n", 'version' );
        
        $paramValues = $this->parameter->getParams();

        
        switch ( true )
        {
            case count( $paramValues ) === 0 || isset( $paramValues['h'] ):
                $this->showHelp();
                break;
            default:
                $version = $this->parameter->getParam( '-v' );
                if ( !preg_match( '/[0-9]+\.[0-9]+(\.|beta)[0-9]+/', $version ) )
                {
                    $this->raiseError( 'Invalid version number <'.$version.'>, must be in format <x.y.z>.');
                }
                $this->paths['package'] = realpath( $this->parameter->getParam( '-p' ) );
                $this->paths['install'] = DIRECTORY_SEPARATOR . 'tmp' . DIRECTORY_SEPARATOR . 'ezc' . DIRECTORY_SEPARATOR . $this->parameter->getParam( '-p' );
                if ( is_dir( $this->paths['install'] ) )
                {
                    `rm -rf {$this->paths['install']}`;
                }
                if ( mkdir( $this->paths['install'], 0700, true ) === false )
                {
                    $this->raiseError( "Could not create installation directory <".$this->paths['install'].">.");
                }
                $this->createLinkMess( $version );
                $this->processPackage( $version );
                break;
        }

        $this->output->outputText( "\nOperation successfully performed.\n", 'success' );
    }

    // }}}
        
    // protected
 
    // {{{ raiseError()

    /**
     * An error occured. Output it and die().
     * 
     * @param mixed $err Exception or string error message. 
     */
    protected function raiseError( $err )
    {
        if ( is_a( $err, 'Exception' ) )
        {
            $err = $err->getMessage();
        }
        $this->output->outputText( $err . "\n", 'failure' );
        die();
    }

    // }}}
    // {{{ processParameter()

    /**
     * Process expected parameters. 
     */
    protected function processParameter()
    {
        $this->parameter = new ezcConsoleParameter();
        $this->parameter->registerParam( 
            'p', 
            'package', 
            array(
                'type'      => ezcConsoleParameter::TYPE_STRING,
                'shorthelp' => 'Package name.',
                'longhelp'  => 'Name of the package to generate the package.xml files for.
The package name must reflect the directory structure and you must be in the <packages/> directory of your SVN checkout.',
                'depends'   => array( 'v' ),
            ) 
        );
        $this->parameter->registerParam( 
            'v', 
            'version', 
            array(
                'type'      => ezcConsoleParameter::TYPE_STRING,
                'shorthelp' => 'Package version.',
                'longhelp'  => 'Version of the release to generate a package.xml files for.',
                'depends'   => array( 'p' ),
            ) 
        );
        $this->parameter->registerParam( 
            's', 
            'stability', 
            array(
                'type'      => ezcConsoleParameter::TYPE_STRING,
                'shorthelp' => 'Stability of the package.',
                'longhelp'  => 'Stability status of the release to package: devel, alpha, beta, or stable (default is guess from version string).',
                'default'   => 'guess',
                'depends'   => array( 'v', 'p' ),
            ) 
        );
        $this->parameter->registerParam( 
            'h', 
            'help', 
            array(
                'type'      => ezcConsoleParameter::TYPE_STRING,
                'shorthelp' => 'Display help. Use "-h <ParameterName>" to display detailed info on a parameter.',
                'longhelp'  => 'Display help information in general or for a specific parameter. Use as "-h <ParameterName> to receive help for a specific parameter".',
                'default'   => '',
            ) 
        );
        $this->parameter->registerParam( 
            'd', 
            'debug', 
            array(
                'shorthelp' => 'Display debugging output.',
                'longhelp'  => 'Display debugging output on the console instead of writing it to the package file. The installation infrastructure will be created anyway.',
            ) 
        );
        
        // Process parameters
        try 
        {
            $this->parameter->process();
        }
        catch ( ezcConsoleParameterException $e )
        {
            $this->raiseError( $e );
        }
    }

    // }}} 
    // {{{ grabReadme()

    /**
     * Returns package information from README file. 
     * Extracts information from the packages README file. Returns an array of
     * short description (index 0) and long description (index 1).
     * 
     * @param string $path Path to package base directory.
     * @return array Array with package descriptions (0=>short, 1=>long).
     */
    protected function grabReadme()
    {
        $readmePath = $this->paths['package'] . DIRECTORY_SEPARATOR . 'trunk' . DIRECTORY_SEPARATOR . 'DESCRIPTION';
        if ( !is_file( $readmePath ) || !is_readable( $readmePath ) )
        {
            $this->raiseError( 'Could not find README file <'.$readmePath.'>.' );
        }
        $readme = file( $readmePath );
        return array( 
            $readme[0],
            implode( '', $readme ),
        );
    }

    // }}}
    // {{{ grabChangelog()

    /**
     * Extract latest changes from changelog. 
     * 
     * @param string $path Package path.
     * @return string Latest changes.
     */
    protected function grabChangelog( $path, $version )
    {
        $changelogPath = $this->paths['package'] . DIRECTORY_SEPARATOR . 'releases' . DIRECTORY_SEPARATOR . $version . DIRECTORY_SEPARATOR . 'ChangeLog';
        if ( !is_file( $changelogPath ) || !is_readable( $changelogPath ) )
        {
            $this->raiseError( 'Could not find ChangeLog file <'.$changelogPath.'>.' );
        }
        $data = array();
        $data = file( $changelogPath );
        unset( $data[0] );
        unset( $data[0] );
        return implode( '', $data );
    }

    // }}}
    // {{{ showHelp()

    /**
     * Print help information. 
     * 
     * @access protected
     * @return void
     */
    protected function showHelp()
    {
        $helpTopic = $this->parameter->getParam( '-h' );
        
        $this->output->outputText( "Usage: $ generate_package_xml.php -p <PackageName> -v <PackageVersion> -s <PackageStatus>\n", 'help' );
        $this->output->outputText( "Must be run from within /your/svn/co/ezcomponents/packages .\n", 'help' );
        
        if ( $helpTopic !== '' && $helpTopic !== false )
        {
            try
            {
                $options = $this->parameter->getParamDef( $helpTopic );
            }
            catch ( ezcConsoleParameterException $e )
            {
                $this->raiseError( 'Invalid help topic: <' . $helpTopic . '>.' );
            }
            $this->output->outputText( "\nUsage of $ generate_package_xml.php parameter $helpTopic:", 'help' );
            $this->output->outputText( "\n" . $options['longhelp'], 'help' );
            if( is_array( $options['depends'] ) && count( $options['depends'] ) > 0 ) 
            {
                $this->output->outputText( "\nMust be used together with parameters: ", 'help' );
                foreach( $options['depends'] as $dependency )
                {
                    $this->output->outputText( "-$dependency, ", 'help' );
                }
                $this->output->outputText( "...", 'help' );
            }
        }
        else
        {
            $table = ezcConsoleTable::create( $this->parameter->getHelp(),  $this->output, array( 'width' => 77, 'cols' => 2 ), array( 'lineFormat' => 'help' ) );
            $table->outputTable();
        }
        $this->output->outputText( "\n", 'help' );
    }

    // }}}
     // {{{  createLinkMess()

    /**
     * Check for and create/update installation dir structure.
     * This method creates a new directory 'install/' below the packages
     * 'trunk/'. This dir reflects the latter installation dir structure,
     * needed by the installer. The method additionally ckecks if the necessary
     * links are in place, creates the if needed, and renews the autoload
     * links.
     * 
     * @access protected
     * @return void
     */
    protected function createLinkMess( $version )
    {
        // prepare mess of links and dirs to create
        $installDir = $this->paths['install'];
        $packageDir = $this->paths['package'] . DIRECTORY_SEPARATOR . 'releases' . DIRECTORY_SEPARATOR . $version;

        // directory paths which have to be really created
        $realPaths              = array();
        $realPaths['ezc']       = $installDir . DIRECTORY_SEPARATOR . 'ezc';
        $realPaths['autoload']  = $realPaths['ezc'] . DIRECTORY_SEPARATOR . 'autoload';

        // paths which have to be linked from their original source
        $linkPaths = array(
            $realPaths['ezc'] . DIRECTORY_SEPARATOR . $this->parameter->getParam( '-p' ) 
            => $packageDir . DIRECTORY_SEPARATOR . 'src',
        );
        if ( is_dir( $packageDir . DIRECTORY_SEPARATOR . 'docs' ) )
        {
            $linkPaths[$installDir . DIRECTORY_SEPARATOR . 'docs'] = $packageDir . DIRECTORY_SEPARATOR . 'docs';
        }

        // autoload files must be linked
        foreach( glob( $packageDir . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . '*autoload*' ) as $autoloadFile ) 
        {
            $linkPaths[$realPaths['autoload'] . DIRECTORY_SEPARATOR . basename( $autoloadFile )] = $autoloadFile;
        }

        // add license file
        $linkPaths[$installDir . DIRECTORY_SEPARATOR . 'docs' . DIRECTORY_SEPARATOR . 'LICENSE'] = $packageDir . '/../../../../LICENSE';
        
        // create real dir structure
        foreach ( $realPaths as $path ) 
        {
            if( !is_dir( $path ) ) 
            {
                if( mkdir( $path, 0777, true ) === false ) 
                {
                    $this->raiseError( 'Could not create basic install directory infrastructure in <' . $path . '>.' );
                }
            }
        }

        // clean up autoload links, if necessary
        foreach( glob( $realPaths['autoload'] . DIRECTORY_SEPARATOR . '*' ) as $autoloadFile ) {
            if (!file_exists( $autoloadFile ) || !is_writeable( $autoloadFile ) || !unlink( $autoloadFile ) ) {
                $this->raiseError( 'Cannot remove former autoload link: <' . $autoloadFile . '>.' );
            }
        }

        // create linked dir structure
        foreach ( $linkPaths as $link => $target ) 
        {
            if( !is_link( $link ) ) 
            {
                // unfortunately we have to copy here, as we need to modify files sometimes :S
                `cp -vfR $target $link`;
                $basePhp = $link . DIRECTORY_SEPARATOR . 'base.php';
                if ( file_exists( $basePhp ) )
                {
                    file_put_contents( "$basePhp", str_replace( "libraryMode = \"devel\"", "libraryMode = \"pear\"", file_get_contents( $basePhp ) ) );
                }
                /*
                if( symlink( $target, $link ) === false ) 
                {
                    $this->raiseError( 'Could not create basic install link infrastructure <' . $link . '> to <' . $target . '>.' );
                }
                */
            }
        }
    }

    // }}}

    private function guessFromVersion( $version )
    {
        if ( preg_match( '@beta@', $version ) )
        {
            return 'beta';
        }
        return 'stable';
    }
    // {{{ processPackage()

    /**
     * Process the package given.
     * Processes the given package and creates a package.xml.
     */
    protected function processPackage( $version )
    {
        $packageName = $this->parameter->getParam( '-p' );
        $packageDir  = $this->paths['install'];
        
        if ( !is_dir( $packageDir ) )
            $this->raiseError( "Package dir <' . $packageDir . '> is invalid.");
        
        $state = $this->parameter->getParam( '-s' ) !== false ? $this->parameter->getParam( '-s' ) : $this->guessFromVersion( $version );
        if ( $state == 'guess' )
        {
            $state = $this->guessFromVersion( $version );
        }
        
        if ( !in_array( $state, $this->validStates ) )
            $this->raiseError( 'Invalid package state: <'.$state.'>.' );
        
        $info = $this->grabReadme( $packageDir );

        $descShort = $info[0];
        $descLong  = $info[1];

        $changelog = $this->grabChangelog( $packageDir, $version );

        $installDir = $packageDir . DIRECTORY_SEPARATOR . 'install';

        $this->generatePackageXml( $packageName, $packageDir, $state, $version, $descShort, $descLong, $changelog );
    }

    // }}}
    // {{{ generatePackageXml()

    /**
     * Generate the final package.xml. 
     * 
     * @param string $name      Name of the package.
     * @param string $path      Path to the packages base directory.
     * @param string $state     Stability state.
     * @param string $version   Version number.
     * @param string $short     Short description.
     * @param string $long      Long description.
     * @param string $changelog Changelog information 
     */
    protected function generatePackageXml( $name, $path, $state, $version, $short, $long, $changelog )
    {
        $autoloadDir = $this->paths['install'] . DIRECTORY_SEPARATOR . 'ezc' . DIRECTORY_SEPARATOR . 'autoload';
        if ( !is_dir( $path ) )
        {
            $this->raiseError( 'Package source directory <'.$path.'> invalid.' );
        }

        $pkg = new PEAR_PackageFileManager2;
        $e = $pkg->setOptions(
            array(
                'packagedirectory'  => $path,
                'pathtopackagefile' => $path,
                'baseinstalldir'    => '/',
                'simpleoutput'      => true,
                'filelistgenerator' => 'file',
                'dir_roles' => array( 
                    'docs'   => 'doc',
                ),
            )
        );

        if ( PEAR::isError( $e ) )
            $this->raiseError( 'PackageFileManager error <'.$e->getMessage().'>.' );

        $e = $pkg->setPackage( $name );
        if ( PEAR::isError( $e ) )
            $this->raiseError( 'PackageFileManager error <'.$e->getMessage().'>.' );
        $e = $pkg->setSummary( $short );
        if ( PEAR::isError( $e ) )
            $this->raiseError( 'PackageFileManager error <'.$e->getMessage().'>.' );
        $e = $pkg->setDescription( $long );
        if ( PEAR::isError( $e ) )
            $this->raiseError( 'PackageFileManager error <'.$e->getMessage().'>.' );
        $e = $pkg->setChannel( self::CHANNEL );
        if ( PEAR::isError( $e ) )
            $this->raiseError( 'PackageFileManager error <'.$e->getMessage().'>.' );
        
        $e = $pkg->setReleaseStability( $state );
        if ( PEAR::isError( $e ) )
            $this->raiseError( 'PackageFileManager error <'.$e->getMessage().'>.' );
        $e = $pkg->setAPIStability( 'stable' );
        if ( PEAR::isError( $e ) )
            $this->raiseError( 'PackageFileManager error <'.$e->getMessage().'>.' );
        $e = $pkg->setReleaseVersion( $version );
        if ( PEAR::isError( $e ) )
            $this->raiseError( 'PackageFileManager error <'.$e->getMessage().'>.' );
        $e = $pkg->setAPIVersion( $version );
        if ( PEAR::isError( $e ) )
            $this->raiseError( 'PackageFileManager error <'.$e->getMessage().'>.' );

        $e = $pkg->setLicense( self::LICENSE );
        if ( PEAR::isError( $e ) )
            $this->raiseError( 'PackageFileManager error <'.$e->getMessage().'>.' );
        $e = $pkg->setNotes( $changelog );
        if ( PEAR::isError( $e ) )
            $this->raiseError( 'PackageFileManager error <'.$e->getMessage().'>.' );

        $e = $pkg->setPackageType( 'php' );
        if ( PEAR::isError( $e ) )
            $this->raiseError( 'PackageFileManager error <'.$e->getMessage().'>.' );

        $e = $pkg->setPhpDep( '5.1.0' );
        if ( PEAR::isError( $e ) )
            $this->raiseError( 'PackageFileManager error <'.$e->getMessage().'>.' );
        $e = $pkg->setPearinstallerDep( '1.4.2' );
        if ( PEAR::isError( $e ) )
            $this->raiseError( 'PackageFileManager error <'.$e->getMessage().'>.' );

        $pkg->addGlobalReplacement( 'php-const', 'libraryMode = "devel"', 'libraryMode = "pear"' );

        $e = $pkg->addRelease();
        if ( PEAR::isError( $e ) )
            $this->raiseError( 'PackageFileManager error <'.$e->getMessage().'>.' );

        $e = $pkg->addMaintainer( 'lead', 'ezc', 'eZ systems', 'ezc@ez.no' );
        if ( PEAR::isError( $e ) )
            $this->raiseError( 'PackageFileManager error <'.$e->getMessage().'>.' );

        $e = $pkg->generateContents();
        if ( PEAR::isError( $e ) )
            $this->raiseError( 'PackageFileManager error <'.$e->getMessage().'>.' );
        
        $debug = $this->parameter->getParam( '-d' ) !== false ? true : false;
        if ( $debug )
        {
            $e = $pkg->debugPackageFile();
            if ( PEAR::isError( $e ) )
                $this->raiseError( 'PackageFileManager error <'.$e->getMessage().'>.' );
        }
        else 
        {
            $e = $pkg->writePackageFile();
            if ( PEAR::isError( $e ) )
                $this->raiseError( 'PackageFileManager error <'.$e->getMessage().'>.' );
        }

        $this->output->outputText( "\nFinished processing. You can now do <$ cd /tmp; pear package {$path}/package.xml; cd - >. \n\n", 'success' );
    }

    // }}}

}

$manager = new ezcPackageManager();
$manager->run();

?>
