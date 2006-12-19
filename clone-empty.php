<?php
/**
 * Autoload ezc classes 
 * 
 * @param string $class_name 
 */

function __autoload( $className )
{
	require_once("trunk/Base/src/base.php");
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
function getInputOptions()
{
	$parameters = new ezcConsoleInput();
   
    $parameters->registerOption( $helpOption = new ezcConsoleOption( 'h', 'help' ) );
    $helpOption->shorthelp = "This help.";
    $helpOption->longhelp = "This help.";

	$parameters->registerOption( new ezcConsoleOption( 
        't', 
        'target', 
		ezcConsoleInput::TYPE_STRING,
        'java_classes',
        false,
        'Target directory.',
        'Target directory where the to java converted classes should be stored. Default is \'java_classes\'.'
	) );

	$parameters->registerOption( new ezcConsoleOption( 
        's', 
        'source', 
		ezcConsoleInput::TYPE_STRING,
        "trunk",
        true,
        'Source directory.',
        'Source component directory. By default it will process \'trunk\'.'
	) );

	try 
	{
		$parameters->process();
	}
	catch ( ezcConsoleParameterException $e )
	{
		echo $e->getMessage(), "\n";
	}

    if( $helpOption->value )
    {
          echo $parameters->getSynopsis() . "\n";
          foreach ( $parameters->getOptions() as $option )
          {
              echo "-{$option->short}/{$option->long}: \t\t\t {$option->longhelp}\n";
          }

          exit();
    }

    return $parameters;

/*
    echo "\n";
    echo <<<AAA
Update this description when the console tools are finished.

To let this script work, you should have performed the following steps:
- Install PEAR (Or compile PHP with PEAR support).
- Make sure that the PHP executables are in the PATH.
- Make sure that autoconf (preferable version 2.13) is installed.
- pecl install docblock-alpha
AAA;
 */
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

function processDocComment( $rc, $type, $class = null )
{
    $comment = $rc->getDocComment(); 

    if( $type == "class" )
    {
        $n = substr( $comment, 0, -4);
        $n .= "\n *\n * @class $class\n *";
        $n .= substr( $comment, -4);
        $comment = $n;
    }

    $tokens = docblock_tokenize( $comment );

    $new = ""; 

    /*
    for( $i = 0; $i < 100; $i++)
    {
        echo docblock_token_name( $i ) . "\n";
    }
    exit();
     */
 

    for ( $i = 0; $i < sizeof( $tokens ); $i++ )
    {

        if ( docblock_token_name( $tokens[$i][0] ) == 'DOCBLOCK_CODEOPEN' )
        {
            $tokens[$i][1] = "@code"; 
        } 
        elseif ( docblock_token_name( $tokens[$i][0] ) == 'DOCBLOCK_CODECLOSE' )
        {
            $tokens[$i][1] = "@endcode";
        }
        elseif( docblock_token_name( $tokens[$i][0] ) == 'DOCBLOCK_TAG' )
        {
            if( $tokens[$i][1] == "@param" || $tokens[$i][1] == "@return")
            {
                $pos = strpos( $tokens[$i + 1][1], " ", 1 );
                if( $pos )
                {
                    // Remove the dollar sign.
                    if( $tokens[$i][1] == "@param" && $tokens[$i + 1][1][$pos + 1] == '$')
                    {
                        $tokens[$i + 1][1] = " " . substr( $tokens[$i + 1][1], $pos + 2 );
                    }
                    else
                    {
                        $tokens[$i + 1][1] = substr( $tokens[$i + 1][1], $pos );
                    }
                }
                else
                {
                    // XXX: check Skip the crap + tab.
                    $i += 3;
                    continue;
                }
            }
            elseif( $tokens[$i][1] == "@var" || $tokens[$i][1] == "@access" )
            {
                // Skip the @var, <type>, tab
                $i += 3;
                //echo "SKIP: <".$tokens[$i + 2][1].">";
                continue;
            }
            else
            {
            }
  

            //$tokens[$i + 1][1] = substr( $tokens[$i + 1][1], );

            //echo "SKIPPING: <" . $tokens[$i][1]   . ">";
            //continue;
/*
            $tokens[$i][1]
            if( $tokens[$i][1] == "@param" )
            {
                echo "next token: " . $tokens[$i][1];
            }
 */
        }

        //$new .= str_replace( '$', '\a ', $tokens[$i][1] );
        $new .= $tokens[$i][1];
    }
    

    return $new;


/*
        // Found a tag?
        if ( docblock_token_name( $tokens[$i][0] ) == 'DOCBLOCK_TAG' )
        {
            var_dump( $tokens[$i] );

           //$result[$tokens[$i][1]][] = trim( $tokens[$i + 1][1] );

        }
 */


//    var_dump( $new );




} 

function cloneFile( $file, $targetDir )
{
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
		if ( preg_match( '@(class|interface)(\s+)(ezc[a-z_0-9]+)(\s+(extends)\s+(\w+))?(\s+(implements)\s+(\w+(\s*,\s*\w+)*))?@i', $line, $match ) )
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

    if ( isset( $match[8] ) && ( $match[8] == "implements" ) )
    {
        $implements = "implements " . $match[9];
    }
    else
    {
        $implements = "";
    }


	$rc = new ReflectionClass( $class );
    
    $classTags = getTags( $rc );
    
    // Create the namespace
    echo "package ".( isset( $classTags["@package"] ) ? $classTags["@package"][0] : "PACKAGE_NOT_SET" ).";\n\n";

    echo processDocComment($rc, "class", $class );
    echo ("\n");

    // Set the access type of the class.
    echo ( isset( $classTags[ "@access" ] ) ? $classTags["@access"][0] : "public" ) ." ";

	echo
		$rc->isAbstract() ? 'abstract ' : '',
		$rc->isFinal() ? 'final ' : '',
		$rc->isInterface() ? 'interface ' : 'class ';
	echo "$class";

    $c = $rc->getParentClass();
    if( is_object( $c ) )
    {
      echo " extends " . $c->getName();
    }

    echo " " . $implements;

    echo "\n{\n";

	foreach ( $rc->getProperties() as $property )
	{
        // Don't show the parent property methods.
        if( $property->getDeclaringClass()->getName() ==  $class )
        {
            echo "";

            echo processDocComment($property, "property");
            echo ("\n");

            $propertyTags = getTags( $property );

            if ( isset( $propertyTag["@access"] ) )
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

            if ( isset( $propertyTags["@var"][0] ) )
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
	}
	echo "\n";

	foreach ( $rc->getMethods() as $method )
	{
        // Don't show the parent class methods.
        if( $method->getDeclaringClass()->getName() ==  $class )
        {

            echo processDocComment($method, "method");
            echo ("\n\t");

            $methodTags = getTags( $method );
            echo
                $method->isAbstract() ? 'abstract ' : '',
                $method->isFinal() ? 'final ' : '',
                $method->isPublic() ? 'public ' : '',
                $method->isPrivate() ? 'private ' : '',
                $method->isProtected() ? 'protected ' : '',
                $method->isStatic() ? 'static ' : '';
            
            $returnType = getReturnValue( $method );

            if ( strcmp( $method->name, "__construct" ) == 0 )
            {
                // Constructor has no return type.
                // Replace the method name.
                echo "$class ( ";
            }
            else
            {
                echo $returnType ? fixType( $returnType ) . ' ' : 'RETURN_TYPE_MISSING ';
                echo "{$method->name}( ";
            }


            $parameterTypes = getParameterTypes( $method );
            foreach ( $method->getParameters() as $i => $param )
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

            echo " ) ";  

            echo ( isset( $methodTags["@throws"] ) ? getThrowsString( $methodTags["@throws"] ) : "" );
            
            
            echo ($method->isAbstract() ? ";" : " {}" ) . "\n";
        }
	}
	
	echo "}\n";
	fwrite( $f, ob_get_contents() );
	ob_end_clean();
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

function fixType( $type )
{
    $type = trim( $type );

    // Only one word allowed.
    if ( strpos( $type, " " ) !== false )
    {
        $type = substr( $type, 0, strpos( $type,  " " ) );
    }
   
    // Pick the first type if it can have multiple values: int|bool.
    if ( ( $pos = strpos( $type, "|" ) ) !== false )
    {
        $type = substr( $type, 0, $pos );
    }

    if ( strncmp( $type, "array(", 6 ) == 0 )
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

    for ( $i = 0; $i < sizeof( $tokens ); $i++ )
    {
        // Found a tag?
        if ( docblock_token_name( $tokens[$i][0] ) == 'DOCBLOCK_TAG' )
        {
           $result[$tokens[$i][1]][] = trim( $tokens[$i + 1][1] );

        }
    }
    return $result;
}

function getThrowsString(  $tags )
{
        if ( isset( $tags ) )
        {
            $str = "throws ";

            for( $i = 0; $i < sizeof( $tags ); $i++ )
            {
                $tags[$i] = trim( $tags[$i] );

                // Only one word allowed.
                if ( ( $pos = strpos( $tags[$i], " " ) ) !== false )
                {
                    $tags[$i] = substr( $tags[$i], 0, $pos );
                }

                // Sometimes: myException::MyConstType is used. Remove the second part.
                if( ( $pos = strpos( $tags[$i], ":" ) ) !== false )
                {
                    $tags[$i] = substr( $tags[$i], 0, $pos );
                }
            }

            $str .= implode( $tags, ", " );

            return $str;
        }

        return false;
}

function status( $str )
{
    echo $str . "\n";
}

$consoleInput = getInputOptions();
$directory = $consoleInput->getOption("target");
$source = $consoleInput->getOption("source");

// If source is not set, read all the components.

if( !is_array( $source->value ) ) 
{
    $source->value = array( $source->value );
}

$files = array();
foreach( $source->value as $s )
{
    if( is_file( $s ) )
    {
        $files = array_merge( $files, array($s));
    }
    else
    {
        $files = array_merge( $files, findRecursive( $s, array( '/\.php$/', '/src/' ) ) );
    }
}

if( count( $files ) == 0 )
{
    status("Could not find any source files");
    exit( -1 );
}

status( "Processing files ");
foreach ( $files as $file )
{
    echo (".");
	cloneFile( $file, $directory->value );
}
?>
