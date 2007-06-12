<?php

class ezcDocFileAnalysisGenerator implements ezcDocAnalysisElementGenerator
{
    protected $class;

    protected $file;

    public function __construct( Reflector $file )
    {
        if ( ( $file instanceof ezcDocFileReflection ) === false )
        {
            throw new ezcBaseValueException( "file", $file, "ezcDocFileReflection" );
        }
        $this->class = $file->getClass();
        $this->file  = $file;
    }

    public function generate()
    {
        $analysis = ezcDocAnalysisElement::get( $this->file );
        $analyser = new ezcDocClassAnalysisGenerator( $this->class );
        $analysis->addChild( $analyser->generate() );
        return $analysis;
    }
}

?>
