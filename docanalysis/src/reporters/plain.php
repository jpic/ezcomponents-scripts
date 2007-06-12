<?php

class ezcDocAnalysisPlainReporter implements ezcDocAnalysisReporter
{
    public static function output( ezcDocAnalysisElement $analysisElement, $level = 0 )
    {
        echo ( count( $analysisElement->messages ) !== 0 ) ? self::indent( "{$analysisElement->name} (" . count( $analysisElement->messages ) . " messages)\n", $level ) : "";
        foreach( $analysisElement->messages as $message )
        {
            echo self::indent( "{$message->message} ({$message->level})\n", $level + 2 );
        }
        foreach( $analysisElement->children as $child )
        {
            self::output( $child, $level + 4 );
        }
    }

    protected static function indent( $text, $level )
    {
        return str_repeat( " ", $level ) . $text;
    }
}

?>
