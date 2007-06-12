<?php

class ezcDocMethodAnalysisGenerator implements ezcDocAnalysisElementGenerator
{
    private $methdod;

    public function __construct( Reflector $method )
    {
        if ( ( $method instanceof ReflectionMethod ) === false )
        {
            throw new ezcBaseValueException( "method", $method, "ReflectionMethod" );
        }
        $this->method = $method;
    }

    public function generate()
    {
        $analysis = ezcDocAnalysisElement::get( $this->method );
        try
        {
            $analysis->docBlock = ezcDocBlockParser::parse( $this->method->getDocComment() );
        }
        catch ( ezcDocException $e )
        {
            $analysis->addMessage( new ezcDocAnalysisMessage( $e->getMessage() ) );
        }
        return $analysis;
    }
}

?>
