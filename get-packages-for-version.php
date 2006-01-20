<?php
function fetchVersionsFromReleaseFile( $fileName )
{
    $versions = array();
    $definition = file( $fileName );
    foreach ( $definition as $defLine )
    {
        if ( preg_match( '@([A-Za-z]+):\s+([A-Za-z0-9.]+)@', $defLine, $matches ) )
        {
            $versions[$matches[1]] = $matches[2];
        }
    }
    return $versions;
}
?>
