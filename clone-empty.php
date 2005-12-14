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
	$f = fopen( $targetDir . "/" . $file, "w" );
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
	echo "<?php\n";
	$rc = new ReflectionClass( $class );
	echo
		$rc->isAbstract() ? 'abstract ' : '',
		$rc->isFinal() ? 'final ' : '',
		$rc->isInterface() ? 'interface ' : 'class ';
	echo "$class\n{\n";

	foreach ( $rc->getProperties() as $property )
	{
		echo "\t";

		echo
			$property->isPublic() ? 'public ' : '',
			$property->isPrivate() ? 'private ' : '',
			$property->isProtected() ? 'protected ' : '',
			$property->isStatic() ? 'static ' : '';

		$propertyType = getPropertyType( $property );
		echo $propertyType ? $propertyType : "PROPERTY_TYPE_MISSING", " ";
		
		echo "$", $property->getName();
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
		echo $returnType ? $returnType . ' ' : 'RETURN_TYPE_MISSING ';

		echo "function {$method->name}( ";

		$parameterTypes = getParameterTypes( $method );
		foreach ($method->getParameters() as $i => $param)
		{
			if ( $i != 0 )
			{
				echo ", ";
			}
			if ( isset( $parameterTypes[$param->getName()] ) )
			{
				echo $parameterTypes[$param->getName()], " ";
			}
			else
			{
				echo "PARAM_TYPE_MISSING ";
			}
			echo '$', $param->getName();
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
		}
		echo " );\n";
	}
	
	echo "}\n?>\n";
	fwrite( $f, ob_get_contents() );
	ob_end_clean();
}

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

$targetDir = fetchDirectoryName();

// Fetch all files ending in *.php and in the "trunk/src" directories
$files = findRecursive( 'packages', array( '/\.php$/', '/trunk\/src/' ) );

foreach ( $files as $file )
{
	cloneFile( $file, $targetDir );
}
?>
