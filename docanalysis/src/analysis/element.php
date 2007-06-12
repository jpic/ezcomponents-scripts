<?php

class ezcDocAnalysisElement
{
    protected $properties = array(
        "element"       => null,
        "docBlock"      => null,
        "messages"      => array(),
        "children"      => array(),
        "docBlockValid" => true,
    );

    public function __construct( Reflector $element )
    {
        $this->properties["element"] = $element;
    }
 
    public static function get( Reflector $element )
    {
        //if ( ( $analysisElement = ezcDocAnalysisCache::get( $element ) ) === false )
        //{
            $analysisElement = new ezcDocAnalysisElement( $element );
            ezcDocAnalysisCache::add( $analysisElement );
        //}
        return $analysisElement;
    }

    public function addChild( ezcDocAnalysisElement $element )
    {
        $this->properties["children"][] = $element;
    }

    public function addMessage( ezcDocAnalysisMessage $message )
    {
        $this->properties["messages"][] = $message;
    }

    public function __get( $propertyName )
    {
        if ( $this->__isset( $propertyName ) )
        {
            return $this->properties[$propertyName];
        }
        if ( $propertyName === "name" )
        {
            return $this->getName();
        }
        throw new ezcBasePropertyNotFoundException( $propertyName );
    }

    public function __set( $propertyName, $propertyValue )
    {
        switch ( $propertyName )
        {
            case 'docBlock':
                if ( ( $propertyValue instanceof ezcDocBlock ) === false )
                {
                    throw new ezcBaseValueException( $propertyName, $propertyValue, 'ezcDocBlock' );
                }
                break;
            case 'docBlockValid':
                if ( is_bool( $propertyValue ) === false )
                {
                    throw new ezcBaseValueException( $propertyName, $propertyValue, 'bool' );
                }
                break;
            case 'element':
            case 'messages':
            case 'children':
                throw new ezcBasePropertyPermissionException( $propertyName, ezcBasePropertyPermissionException::READ );
            default:
                throw new ezcBasePropertyNotFoundException( $propertyName );
        }
        $this->properties[$propertyName] = $propertyValue;
    }

    public function __isset( $propertyName )
    {
        return ( array_key_exists( $propertyName, $this->properties ) );
    }

    protected function getName()
    {
        switch ( get_class( $this->element ) )
        {
            case 'ezcDocFileReflection':
            case 'ezcDocComponentReflection':
            case 'ReflectionClass':
                return $this->element->getName();
            case 'ReflectionProperty':
                return $this->element->getDeclaringClass()->getName() . "->$" . $this->element->getName();
            case 'ReflectionMethod':
                return $this->element->getDeclaringClass()->getName() . "->" . $this->element->getName() . "()";
        }
        return "<<unkown>>";
    }
}

?>
