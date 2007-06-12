<?php

class ezcDocInvalidDocBlockException extends ezcDocException
{
    public function __construct( $docLine, $msg = null )
    {
        parent::__construct(
            "Invalid doc block line '$docLine'." .
                ( $msg !== null ? " $msg" : "" )
        );
    }
}

?>
