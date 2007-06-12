<?php

class ezcDocClassAnalysisGenerator implements ezcDocAnalysisElementGenerator
{

    private $properties = array();

    private $methods = array();

    private $class;

    public function __construct( Reflector $class )
    {
        if ( ( $class instanceof ReflectionClass ) === false )
        {
            throw new ezcBaseValueException( "class", $class, "ReflectionClass" );
        }
        $this->class = $class;
        $this->properties = $class->getProperties();
        $this->methods = $class->getMethods();
    }

    public function generate()
    {
        $analysis = ezcDocAnalysisElement::get( $this->class );
        try 
        {
            $analysis->docBlock = ezcDocBlockParser::parse( $this->class->getDocComment() );
        }
        catch ( ezcDocException $e )
        {
            $analysis->addMessage( new ezcDocAnalysisMessage( $e->getMessage() ) );
        }
        foreach ( $this->properties as $property )
        {
            if ( $property->getDeclaringClass()->isUserDefined() )
            {
                $analyser = new ezcDocPropertyAnalysisGenerator( $property );
                $analysis->addChild( $analyser->generate() );
            }
        }
        foreach ( $this->methods as $method )
        {
            if ( $method->isUserDefined() )
            {
                $analyser = new ezcDocMethodAnalysisGenerator( $method );
                $analysis->addChild( $analyser->generate() );
            }
        }
        return $analysis;
    }
}


?>
