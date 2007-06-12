<?php

interface ezcDocAnalysisElementGenerator
{
    public function __construct( Reflector $element );

    public function generate();
}

?>
