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
$params->registerOption( $componentOption );

$targetOption = new ezcConsoleOption( 't', 'target', ezcConsoleInput::TYPE_STRING );
$targetOption->mandatory = true;
$params->registerOption( $targetOption );

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
$output = removeHeaderFooter( $output );
$output = addNewHeader( $component, $output );
$output = addExampleLineNumbers( $output );
$output = addLinks( $component, $output );

$targetDir = $params->getOption( 'target' )->value;
file_put_contents( "$targetDir/introduction_$component.html", $output );

// Copying images
`mkdir -p $targetDir/img`;
`cp $component/trunk/docs/img/*.png $targetDir/img/`;

function getRstOutput( $component )
{
    $fileName = "$component/trunk/docs/tutorial.txt";
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


<b>[ <a href="introduction_$component.html" class="menu">Introduction</a> ]</b>
<b>[ <a href="classtrees_$component.html" class="menu">Class tree</a> ]</b>
<b>[ <a href="elementindex_$component.html" class="menu">Element index</a> ]</b>
<h2>Introduction for Component $component</h2>
<hr class="separator" />
FOO;
    return $outputHeader . $output;
}

function addLinks( $component, $output )
{
    $base = "http://ez.no/doc/components/view/(file)/1.0rc1/$component/";

    $output = preg_replace( '@(ezc[A-Z][a-zA-Z]+)::\$([A-Za-z0-9]+)@', "<a href='{$base}\\1.html#\$\\2'>\\0</a>", $output );
    $output = preg_replace( "@(ezc[A-Z][a-zA-Z]+)::([A-Z_]+)@", "<a href='{$base}\\1.html#const\\2'>\\0</a>", $output );
    $output = preg_replace( "@(ezc[A-Z][a-zA-Z]+)::([A-Za-z0-9]+)\(\)@", "<a href='{$base}\\1.html#\\2'>\\0</a>", $output );
    $output = preg_replace( "@(ezc[A-Z][a-zA-Z]+)->([A-Za-z0-9]+)\(\)@", "<a href='{$base}\\1.html#\\2'>\\0</a>", $output );
    $output = preg_replace( "@(?<![/>])(ezc[A-Z][a-zA-Z]+)@", "<a href='{$base}\\1.html'>\\0</a>", $output );
    $output = preg_replace( "@(<span style=\"color: #[0-9A-F]+\">)(ezc[A-Z][a-zA-Z]+)(</span><span style=\"color: #[0-9A-F]+\">\()@", "\\1<a href='{$base}\\2.html'>\\2</a>\\3", $output );

    return $output;
}

function addExampleLineNumbers( $output )
{
    return preg_replace_callback( '@<pre class=\"literal-block\">(.+?)<\/pre>@ms', 'callbackAddLineNumbers', $output );
}

function callbackAddLineNumbers( $args )
{
    if ( strstr( $args[1], '&lt;?php' ) !== false )
    {
        $listing = '<div class="listing"><pre><ol>';
        $highlighted = highlight_string( html_entity_decode( $args[1] ), true );
        $highlighted = preg_replace( '@^<code><span style="color: #000000">.<br />@ms', '<code>', $highlighted );
        $highlighted = preg_replace( '@<br /></span>.</code>$@ms', "</code>", $highlighted );
        $listing .= preg_replace( '@(.*?)<br />@ms', "<li>\\1</li>\n", $highlighted );
        $listing .= '</ol></pre></div>';
        return $listing;
    } else {
        return $args[0];
    }
}
