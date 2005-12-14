<?php
/**
 * Autoload ezc classes 
 * 
 * @param string $class_name 
 */

function __autoload( $className )
{
	require_once("packages/Base/trunk/src/base.php");
	if ( strpos( $className, "_" ) !== false )
	{
		$file = str_replace( "_", "/", $className ) . ".php";
		$val = require_once( $file );
		if ( $val == 0 )
			return true;
		return false;
	}
	ezcBase::autoload( $className );
}

// Parse options
function fetchDirectoryName()
{
/*
	$parameters = new ezcConsoleParameter();
	$parameters->registerParam( new ezcConsoleParameterStruct( 
		'd', 'directory', 
		array(
			'type'      => ezcConsoleParameter::TYPE_STRING,
			'shorthelp' => 'Output directory.',
			'longhelp'  => 'Name of the directory where the cloned copy should be saved to. (defaults to /tmp/ezc-clone)',
			'default'   => '/tmp/ezc-clone',
		) )
	);
	try 
	{
		$parameters->process();
	}
	catch ( ezcConsoleParameterException $e )
	{
		echo $e->getMessage(), "\n";
	}

	$directory = $parameters->getParam( '-d' );
*/
    echo "\n";
    echo <<<AAA
Update this description when the console tools are finished.

To let this script work, you should have performed the following steps:
- Install PEAR (Or compile PHP with PEAR support).
- Make sure that the PHP executables are in the PATH.
- Make sure that autoconf (preferable version 2.13) is installed.
- pecl install docblock-alpha
AAA;
	if ( !$directory )
	{
		$directory = '/tmp/ezc-clone';
	}

	return $directory;
}

function findRecursive( $sourceDir, $filters )
{
	$elements = array();
	$dir = glob( "$sourceDir/*" );
	foreach( $dir as $entry )
	{
		if ( is_dir( $entry ) )
		{
			$subList = findRecursive( $entry, $filters );
			$elements = array_merge( $elements, $subList );
		}
		else
		{
			$ok = true;
			foreach( $filters as $filter )
			{
				if ( !preg_match( $filter, $entry ) )
				{
					$ok = false;
					break;
				}
			}
			if ( $ok )
			{
				$elements[] = $entry;
			}
		}
	}
	return $elements;
}

function cloneFile( $file, $targetDir )
{
	echo $file, "\n";
	$dir = dirname( $file );
	if ( !is_dir( $targetDir . "/" . $dir ) )
	{
		mkdir ( $targetDir . "/" . $dir, 0777, true );
	}
	$f = fopen( $targetDir . "/" . str_replace(".php", ".java", $file ), "w" );
	ob_start();
	$found = false;
	$lines = file( $file );
	foreach ( $lines as $line )
	{
		if ( preg_match( '@(class|interface)(\s+)(ezc[a-z_0-9]+)@i', $line, $match ) )
		{
			$class = $match[3];
			$found = true;
			break;
		}
	}
	if ( !$found )
	{
		return;
	}
	$rc = new ReflectionClass( $class );
    
    $classTags = getTags( $rc );
    
    // Create the namespace
    echo "package ".( isset( $classTags["@package"] ) ? $classTags["@package"][0] : "PACKAGE_NOT_SET" ).";\n\n";

    // Set the access type of the class.
    echo ( isset( $classTags[ "@access" ] ) ? $classTags["@access"][0] : "public" ) ." ";

	echo
		$rc->isAbstract() ? 'abstract ' : '',
		$rc->isFinal() ? 'final ' : '',
		$rc->isInterface() ? 'interface ' : 'class ';
	echo "$class\n{\n";

	foreach ( $rc->getProperties() as $property )
	{
		echo "\t";

        $propertyTags = getTags( $property );

        if( isset( $propertyTag["@access"] ) )
        {
            echo ( $propertyTag["@access"] );
        }
        else
        {
            echo
                $property->isPublic() ? 'public ' : '',
                $property->isPrivate() ? 'private ' : '',
                $property->isProtected() ? 'protected ' : '',
                $property->isStatic() ? 'static ' : '';
        }

        if( isset( $propertyTags["@var"][0] ) )
        {
            $var = fixType( $propertyTags["@var"][0] );
            echo $var . " "; 
        }
        else
        {
            echo "PROPERTY_TYPE_MISSING ";
        }

		//$propertyType = getPropertyType( $property );
		//echo $propertyType ? $propertyType : "PROPERTY_TYPE_MISSING", " ";
		
		echo $property->getName();
		echo ";\n";
	}
	echo "\n";

	foreach ( $rc->getMethods() as $method )
	{
		echo "\t";

		echo
			$method->isAbstract() ? 'abstract ' : '',
			$method->isFinal() ? 'final ' : '',
			$method->isPublic() ? 'public ' : '',
			$method->isPrivate() ? 'private ' : '',
			$method->isProtected() ? 'protected ' : '',
			$method->isStatic() ? 'static ' : '';
		
		$returnType = getReturnValue( $method );

        if( strcmp( $method->name, "__construct" ) == 0 )
        {
            // Constructor has no return type.
            // Replace the method name.
            echo "$class ( ";
        }
        else
        {
		    echo $returnType ? fixType($returnType) . ' ' : 'RETURN_TYPE_MISSING ';
            echo "{$method->name}( ";
        }


		$parameterTypes = getParameterTypes( $method );
		foreach ($method->getParameters() as $i => $param)
		{
			if ( $i != 0 )
			{
				echo ", ";
			}
			if ( isset( $parameterTypes[$param->getName()] ) )
			{ 
                echo fixType( $parameterTypes[$param->getName()] ), " ";
			}
			else
			{
				echo "PARAM_TYPE_MISSING ";
			}
			echo $param->getName();
            /*
			if ( $param->isDefaultValueAvailable() )
			{
				echo ' = ';
				switch( strtolower( gettype( $param->getDefaultValue() ) ) )
				{
					case 'boolean':
						echo $param->getDefaultValue() ? 'true' : 'false';
						break;
					case 'null':
						echo 'null';
						break;
					default:
						echo $param->getDefaultValue();
				}
			}
            */
		}
        echo " )" . ($method->isAbstract() ? ";" : "{}" ) . "\n";
	}
	
	echo "}\n";
	fwrite( $f, ob_get_contents() );
	ob_end_clean();
}
/*
function getPropertyType( $method )
{
	$types = array();
	$db = $method->getDocComment();
	$nextTextParamType = false;
	foreach ( docblock_tokenize( $db ) as $docItem )
	{
		if ( $nextTextParamType )
		{
			if ( docblock_token_name( $docItem[0] ) == 'DOCBLOCK_TEXT' )
			{
				if ( preg_match( '@\s([^\s]+)@', $docItem[1], $match ) )
				{
					return trim( $match[1] );
				}
			}
			$nextTextParamType = false;
		}
		if ( docblock_token_name( $docItem[0] ) == 'DOCBLOCK_TAG' && $docItem[1] == '@var' )
		{
			$nextTextParamType = true;
		}
		else
		{
			$nextTextParamType = false;
		}
	}
	return false;
}
*/

function getParameterTypes( $method )
{
	$types = array();
	$db = $method->getDocComment();
	$nextTextParamType = false;
	foreach ( docblock_tokenize( $db ) as $docItem )
	{
		if ( $nextTextParamType )
		{
			if ( docblock_token_name( $docItem[0] ) == 'DOCBLOCK_TEXT' )
			{
				if ( preg_match( '@\s([^\s]+)\s+\$([^\s]+)@', $docItem[1], $match ) )
				{
					$types[$match[2]] = $match[1];
				}
			}
			$nextTextParamType = false;
		}
		if ( docblock_token_name( $docItem[0] ) == 'DOCBLOCK_TAG' && $docItem[1] == '@param' )
		{
			$nextTextParamType = true;
		}
		else
		{
			$nextTextParamType = false;
		}
	}
	return $types;
}

function getReturnValue( $method )
{
	$types = array();
	$db = $method->getDocComment();
	$nextTextParamType = false;
	foreach ( docblock_tokenize( $db ) as $docItem )
	{
		if ( $nextTextParamType )
		{
			if ( docblock_token_name( $docItem[0] ) == 'DOCBLOCK_TEXT' )
			{
				if ( preg_match( '@\s([^\s]+)@', $docItem[1], $match ) )
				{
					return trim( $match[1] );
				}
			}
			$nextTextParamType = false;
		}
		if ( docblock_token_name( $docItem[0] ) == 'DOCBLOCK_TAG' && $docItem[1] == '@return' )
		{
			$nextTextParamType = true;
		}
		else
		{
			$nextTextParamType = false;
		}
	}
	return false;
}

function fixType( $type )
{
    // Pick the first type if it can have multiple values: int|bool.
    if( ( $pos = strpos( $type, "|" ) ) !== false )
    {
        $type = substr( $type, 0, $pos );
    }

    if( strncmp( $type, "array(", 6 ) == 0 )
    {
        $type = substr( $type, 6, -1) . "[]";
        $type = str_replace( "=>", "_", $type );
    }

    return $type;
}

/** 
 * Returns an array with tags and value.
 */
function getTags( $reflectionItem )
{
    $result = array();
	$dc = $reflectionItem->getDocComment();
    
    // Go through the comment block.
    $tokens = docblock_tokenize( $dc );

    for( $i = 0; $i < sizeof( $tokens ); $i++ )
    {
        // Found a tag?
        if ( docblock_token_name( $tokens[$i][0] ) == 'DOCBLOCK_TAG' )
        {
           $result[ $tokens[$i][1] ][] = trim( $tokens[$i + 1][1] );

        }
    }
    return $result;
}

$targetDir = fetchDirectoryName();

// Fetch all files ending in *.php and in the "trunk/src" directories
$files = findRecursive( 'packages', array( '/\.php$/', '/trunk\/src/' ) );

foreach ( $files as $file )
{
	cloneFile( $file, $targetDir );
}
?>
