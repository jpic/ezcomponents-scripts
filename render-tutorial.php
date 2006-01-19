<?php
/**
 * Load the base package to boot strap the autoloading
 */
require_once 'packages/Base/trunk/src/base.php';

// {{{ __autoload()

/**
 * Autoload ezc classes 
 * 
 * @param string $class_name 
 */
function __autoload( $class_name )
{
    if ( ezcBase::autoload( $class_name ) )
    {
        return;
    }
}

// Setup console parameters
$params = new ezcConsoleInput();
$componentOption = new ezcConsoleOption( 'c', 'component', ezcConsoleInput::TYPE_STRING );
$componentOption->mandatory = true;
$params->registerOption( $componentOption );

// Process console parameters
try
{
	$params->process();
}
catch ( ezcConsoleOptionException $e )
{
	die( $e->getMessage(). "\n" );
}

$component = $params->getOption( 'component' )->value;
$output = getRstOutput( $component );
$output = addLinks( $component, $output );

file_put_contents( "/tmp/test.html", $output );

function getRstOutput( $component )
{
	$fileName = "packages/$component/trunk/docs/tutorial.txt";
	$output = shell_exec( "rst2html.py $fileName" );
	return $output;
}

function addLinks( $component, $output )
{
	$base = "http://ez.no/doc/components/view/(file)/1.0rc1/$component/";

	$output = preg_replace( '@(ezc[A-Z][a-zA-Z]+)::\$([A-Za-z0-9]+)@', "<a href='{$base}\\1.html#\$\\2'>\\0</a>", $output );
	$output = preg_replace( "@(ezc[A-Z][a-zA-Z]+)::([A-Z_]+)@", "<a href='{$base}\\1.html#const\\2'>\\0</a>", $output );
	$output = preg_replace( "@(ezc[A-Z][a-zA-Z]+)::([A-Za-z0-9]+)\(\)@", "<a href='{$base}\\1.html#\\2'>\\0</a>", $output );
	$output = preg_replace( "@(ezc[A-Z][a-zA-Z]+)->([A-Za-z0-9]+)\(\)@", "<a href='{$base}\\1.html#\\2'>\\0</a>", $output );
	$output = preg_replace( "@(?<![/>])(ezc[A-Z][a-zA-Z]+)@", "<a href='{$base}\\1.html'>\\0</a>", $output );

	return $output;
}
