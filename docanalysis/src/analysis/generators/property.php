<?php

class ezcDocPropertyAnalysisGenerator implements ezcDocAnalysisElementGenerator
{
    private $property;

    public function __construct( Reflector $property )
    {
        if ( ( $property instanceof ReflectionProperty ) === false )
        {
            throw new ezcBaseValueException( "property", $property, "ReflectionProperty" );
        }
        $this->property = $property;
    }

    public function generate()
    {
        $analysis = ezcDocAnalysisElement::get( $this->property );
        try
        {
            $analysis->docBlock = ezcDocBlockParser::parse( $this->property->getDocComment() );
        }
        catch( ezcDocException $e )
        {
            $analysis->addMessage( new ezcDocAnalysisMessage( $e->getMessage() ) );
        }
        return $analysis;
    }
}

?>
