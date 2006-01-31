<?php

include ( "packages/Base/trunk/src/base.php" );
function __autoload( $class_name )
{
    if ( ezcBase::autoload( $class_name ) )
    {
        return;
    }
}

// Setup console parameters
$params = new ezcConsoleInput();
$file = new ezcConsoleOption( 'f', 'file', ezcConsoleInput::TYPE_STRING );
$file->shorthelp = "File that should be checked with ispell.";
$file->mandatory = true;
$params->registerOption( $file );

try
{
    $params->process();
}
catch ( ezcConsoleOptionException $e )
{
    echo $e->getMessage(). "\n\n";
    echo $params->getSynopsis() . "\n\n";

    foreach ( $params->getOptions() as $option )
    {
        echo "-{$option->short}, --{$option->long}\t    {$option->shorthelp}\n";
    }

    echo "\n";
    exit();
}


// We should have a file name.
$file = $params->getOption( "file" )->value;

$fp = fopen( $file, "r" );
if( $fp === false )
{
    exit( "Couldn't open the file: $file" );
}


$ispell = new ISpell();
$i = 0;

$inDocBlock = false;
$skip = false;


while( $sentence = fgets( $fp ) )
{
    if( preg_match( "@^\s*/\*\*\s*$@", $sentence ) )
    {
        $inDocBlock = true;
    }
    else if( $inDocBlock && preg_match( "@\s*\*/\s*$@", $sentence ) )
    {
        $inDocBlock = false;
        $skip = false;
    }
    else if( $inDocBlock && !$skip)
    {
        // If something contains an @, skip the rest until the new docblock.
        if( preg_match( "|@|", $sentence ) ) 
        {
            $skip = true;
        }
        else if( preg_match( "|<code>|", $sentence ) ) 
        {
            $skip = true;
        }
        else
        {
            $pos = strpos( $sentence, "*" ) + 1;

            $testForSpelling = substr( $sentence, $pos );
            $correct = $ispell->check( $testForSpelling ); 

            $sentence = substr( $sentence, 0, $pos ) . $correct;
        }
    }

    $correctedSentences[$i] = $sentence;
    $i++;
}

fclose( $fp );


// Backup
copy( $file, $file.".bak" );

$fp = fopen( "$file", "w" );

if( $fp === false )
{
    exit ( "Cannot open the file <$file> for writing." );
}

// Write the changes
for( $i = 0; $i < sizeof( $correctedSentences ); $i++)
{
    fwrite( $fp, $correctedSentences[$i] );
}

// And close.
fclose( $fp);

   

class ISpell
{
    private $stdin = null;

    private $pipes;

    private $ispell;


    public function __construct()
    {
        $this->stdin = fopen("php://stdin","r");

        $descriptorspec = array(
            0 => array("pipe", "r"),  // stdin is a pipe that the child will read from
            1 => array("pipe", "w"),  // stdout is a pipe that the child will write to
            2 => array("file", "/tmp/error-output.txt", "a") // stderr is a file to write to
         );

        $this->ispell = proc_open( '/usr/bin/ispell -a', $descriptorspec, $this->pipes ); 

        if ( !is_resource( $this->ispell ) ) 
        {
            die ("Cannot open Ispell\n");
        }
    }

    public function __destruct()
    {
        fclose( $this->stdin );
    }


    /**
     * Returns the corrected sentence.
     * 
     * This function requires input from the user.
     */
    public function check( $sentence )
    {
        $newSentence = "";

        fread( $this->pipes[1] , 1024); // read introduction, or anything and ignore.
        fwrite( $this->pipes[0], "$sentence\n" ); // write the sentence to ispell.

        $prefPos = 0;

        // Read the output
        while ( ($result = fgets( $this->pipes[1], 1024 ) ) && $result != "\n" )
        {
            // Each word is on a new line.
            if( !$this->isOk( $result ) )
            {
                list( $word, $position, $suggestions ) = $this->parseResult( $result );

                $this->showHelp( $sentence, $word, $position, $suggestions );
                $line = $this->getCorrection();

                // Update the word, if something is filled in.
                if( $line != "" )
                {
                    $newSentence .= substr( $sentence, $prefPos, $position - $prefPos ) .  $line;
                    $prefPos = $position + strlen( $word );
                }
            }
        }

        $position = strlen( $sentence );
        $newSentence .= substr( $sentence, $prefPos, $position - $prefPos );

        return $newSentence;
    }

    private function showHelp( $sentence, $word, $position, $suggestions )
    {
        echo ("\n");
        echo ( $sentence . "\n" );

        echo ("Word not recognized: " . $word . "\n\n" );
        echo $suggestions;
    }
    
    private function getCorrection()
    {
        echo ("\nType replacement (return to accept): ");
        $line = rtrim( fgets($this->stdin, 1024) );
        return $line;
    }


    private function isOk( $result )
    {
        if( $result[0] == "*" ) return true;
        if( $result[0] == "+" ) return true;
        if( $result[0] == "-" ) return true;
        if( $result[0] == "\n" ) return true;

        return false;
    }

    private function parseResult( $result )
    {
        if( $result[0] == "&" )
        {
            $split =  split( " ", $result );
            $position = substr( $split[3], 0, -1 );

            return array( $split[1], $position, $result ); // $result should be suggestions.
        }
        else if( $result[0] == "#" )
        {
            $split =  split( " ", $result );

            return array( $split[1], $split[2], false );

        }
    }

}



?>
