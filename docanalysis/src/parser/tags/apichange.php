<?php

class ezcDocBlockApichangeTag extends ezcDocBlockBaseTag implements ezcDocBlockTag
{
    public static function getPattern()
    {
        return '/^@apichange(\s|$)/';
    }

    public function __construct( $docLine )
    {
        if ( preg_match( '/^@apichange\s*(\S+\s*)*$/', $docLine, $matches ) !== 1 )
        {
            throw new ezcDocInvalidDocTagException( "apichange", $docLine );
        }
        parent::__construct(
            array(
                "ref"  => ( isset( $matches[1] ) ? $matches[1] : 'none' ),
            )
        );
    }
}

?>
