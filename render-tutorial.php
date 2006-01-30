<?php
/**
 * Load the base package to boot strap the autoloading
 */
require_once 'Base/trunk/src/base.php';

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

ini_set( 'highlight.string', '#335533' );
ini_set( 'highlight.keyword', '#0000FF' );
ini_set( 'highlight.default', '#000000' );
ini_set( 'highlight.comment', '#007700' );

// Setup console parameters
$params = new ezcConsoleInput();
$componentOption = new ezcConsoleOption( 'c', 'component', ezcConsoleInput::TYPE_STRING );
$componentOption->mandatory = true;
$componentOption->shorthelp = "The name of the component.";
$params->registerOption( $componentOption );

$targetOption = new ezcConsoleOption( 't', 'target', ezcConsoleInput::TYPE_STRING );
$targetOption->mandatory = true;
$targetOption->shorthelp = "The directory to where the generated documentation should be written.";
$params->registerOption( $targetOption );

$versionOption = new ezcConsoleOption( 'v', 'version', ezcConsoleInput::TYPE_STRING );
$versionOption->mandatory = true;
$versionOption->shorthelp = "The version of the component that should be read. E.g. trunk, 1.0rc1, etc.";
$params->registerOption( $versionOption );

// Process console parameters
try
{
    $params->process();
}
catch ( ezcConsoleOptionException $e )
{
    print( $e->getMessage(). "\n" );
    print( "\n" );

    echo $params->getSynopsis() . "\n";
    foreach ( $params->getOptions() as $option )
    {
        echo "-{$option->short}, --{$option->long}\t    {$option->shorthelp}\n";
    }

    echo "\n";
    exit();
}

$component = $params->getOption( 'component' )->value;
$version = $params->getOption( 'version' )->value;

if ( $version == 'trunk' )
{
    $componentDir = "$component/trunk";
}
else
{
    $componentDir = "$component/releases/$version";
}

$output = getRstOutput( $componentDir );
$output = removeHeaderFooter( $output );
$output = addNewHeader( $component, $output );
$output = addExampleLineNumbers( $output );
$output = addLinks( $component, $output, $version );

$targetDir = $params->getOption( 'target' )->value;
file_put_contents( "$targetDir/introduction_$component.html", $output );
// Copying images
`mkdir -p $targetDir/img`;
`cp $componentDir/docs/img/*.* $targetDir/img/ 2>/dev/null`;

function getRstOutput( $componentDir )
{
    $fileName = "$componentDir/docs/tutorial.txt";
    $output = shell_exec( "rst2html $fileName" );
    return $output;
}

function removeHeaderFooter( $output )
{
    $output = preg_replace( '@.*?<body>@ms', '', $output );
    $output = preg_replace( '@<h1 class="title">eZ components - [A-Za-z]+</h1>@', '', $output );
    $output = preg_replace( '@<\/body>.*@ms', '', $output );
    return $output;
}

function addNewHeader( $component, $output )
{
    $outputHeader = <<<FOO
<div class="attribute-heading"><h1>$component</h1></div>


<b>[ <a href="introduction_$component.html" class="menu">Tutorial</a> ]</b>
<b>[ <a href="classtrees_$component.html" class="menu">Class tree</a> ]</b>
<b>[ <a href="elementindex_$component.html" class="menu">Element index</a> ]</b>
<h2>Tutorial for Component $component</h2>
<hr class="separator" />
FOO;
    return $outputHeader . $output;
}

function addLinks( $component, $output, $version )
{
//    $base = "http://ez.no/doc/components/view/(file)/$version/$component/";
    $base = "$component/";

    $output = preg_replace( '@(ezc[A-Z][a-zA-Z]+)::\$([A-Za-z0-9]+)@', "<a href='{$base}\\1.html#\$\\2'>\\0</a>", $output );
    $output = preg_replace( "@(ezc[A-Z][a-zA-Z]+)::([A-Za-z0-9_]+)(?=\()@", "<a href='{$base}\\1.html#\\2'>\\0</a>", $output );
    $output = preg_replace( "@(ezc[A-Z][a-zA-Z]+)-(>|\&gt;)([A-Za-z0-9_]+)(?=\()@", "<a href='{$base}\\1.html#\\3'>\\0</a>", $output );
    $output = preg_replace( "@(ezc[A-Z][a-zA-Z]+)::([A-Z_]+)\\b@", "<a href='{$base}\\1.html#const\\2'>\\0</a>", $output );
    $output = preg_replace( "@(?<![/>])(ezc[A-Z][a-zA-Z]+)@", "<a href='{$base}\\1.html'>\\0</a>", $output );
    $output = preg_replace( "@(<span style=\"color: #[0-9A-F]+\">)(ezc[A-Z][a-zA-Z]+)(</span><span style=\"color: #[0-9A-F]+\">\()@", "\\1<a href='{$base}\\2.html'>\\2</a>\\3", $output );
    $output = preg_replace( "@(ezc[A-Z][a-zA-Z]+)(</span><span style=\"color: #[0-9A-F]+\">::</span><span style=\"color: #[0-9A-F]+\">)([A-Z_]+)@", "<a href='{$base}\\1.html#const\\3'>\\1::\\3</a>", $output );
    $output = preg_replace( "@(<span style=\"color: #[0-9A-F]+\">)(ezc[A-Z][a-zA-Z]+)(</li>)@", "\\1<a href='{$base}\\2.html'>\\2</a>\\3", $output );
    $output = preg_replace( "@(<span style=\"color: #[0-9A-F]+\">)(ezc[A-Z][a-zA-Z]+)(</span><span style=\"color: #[0-9A-Z]+\">::</span><span style=\"color: #[0-9A-F]+\">)([A-Za-z]+)(</span>)@", "\\1<a href='{$base}\\2.html#\\4'>\\2::\\4</a>\\5", $output );
    $output = preg_replace( "@(<span style=\"color: #[0-9A-F]+\">)(ezc[A-Z][a-zA-Z]+Exception)(\&nbsp;\\$)@", "\\1<a href='{$base}\\2.html'>\\2</a>\\3", $output );
    return $output;
}

function addExampleLineNumbers( $output )
{
    return preg_replace_callback( '@<pre class=\"literal-block\">(.+?)<\/pre>@ms', 'callbackAddLineNumbers', $output );
}

$lineNr = 0;

function callbackAddLineNr( $args )
{
    global $lineNr;

    $nrString = str_replace( ' ', '&nbsp;', sprintf( '%3d', $lineNr ) );
    if ( $lineNr == 0 )
    {
        $val = '';
    }
    else
    {
        $val = $nrString . ". {$args[1]}<br />";
    }
    $lineNr++;
    return $val;
}

function callbackAddLineNumbers( $args )
{
    global $lineNr;

    $lineNr = 0;
    
    if ( strstr( $args[1], '&lt;?php' ) !== false )
    {
        $listing = '<pre class="listing">';
        $highlighted = highlight_string( html_entity_decode( $args[1] ), true );
        $highlighted = preg_replace( '@^<code><span style="color: #000000">.<br />@ms', '<code><span><br />', $highlighted );
        $highlighted = preg_replace( '@<br /></span>.</code>$@ms', "</span></code>", $highlighted );
        $listing .= preg_replace_callback( '@(.*?)<br />@', "callbackAddLineNr", $highlighted );
        $listing .= '</pre>';
        return $listing;
    } else {
        return $args[0];
    }
}
