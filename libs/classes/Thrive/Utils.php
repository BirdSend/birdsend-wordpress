<?php
namespace BSWP\Thrive;

class Utils
{
    /**
     * @param $data
     *
     * @return bool
     */
    public static function is_base64_encoded( $data ) {
        return $data === base64_encode( base64_decode( $data, true ) );
    }

    /**
     * @param string $data
     *
     * @return mixed
     */
    public static function safe_unserialize( $data ) {
        if ( ! is_serialized( $data ) ) {
            return $data;
        }

        if ( version_compare( '7.0', PHP_VERSION, '<=' ) ) {
            return unserialize( $data, array( 'allowed_classes' => false ) );
        }

        /* on php <= 5.6, we need to check if the serialized string contains an object instance */
        if ( ! is_string( $data ) ) {
            return false;
        }

        if ( preg_match( '#(^|;)o:\d+:"[a-z0-9\\\_]+":\d+:#i', $data, $m ) ) {
            return false;
        }

        return unserialize( $data );
    }
}