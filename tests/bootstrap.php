<?php

require_once dirname( __DIR__ ) . '/vendor/autoload.php';

class RinacWpTestStore {
    /** @var array<int,array<string,mixed>> */
    public static array $meta = array();

    /** @var array<int,object> */
    public static array $posts = array();

    public static function reset(): void {
        self::$meta = array();
        self::$posts = array();
    }
}

if ( ! class_exists( 'WP_Error' ) ) {
    class WP_Error {
        private string $code;
        private string $message;
        /** @var mixed */
        private $data;

        /**
         * @param mixed $data
         */
        public function __construct( string $code = '', string $message = '', $data = null ) {
            $this->code = $code;
            $this->message = $message;
            $this->data = $data;
        }

        public function get_error_message(): string {
            return $this->message;
        }

        /**
         * @return mixed
         */
        public function get_error_data( ?string $code = null ) {
            if ( null === $code || $code === $this->code ) {
                return $this->data;
            }
            return null;
        }
    }
}

if ( ! function_exists( 'absint' ) ) {
    function absint( $maybeint ): int {
        return abs( (int) $maybeint );
    }
}

if ( ! function_exists( 'esc_html__' ) ) {
    function esc_html__( string $text, string $domain = '' ): string {
        return $text;
    }
}

if ( ! function_exists( 'get_post_meta' ) ) {
    function get_post_meta( int $post_id, string $key, bool $single = false ) {
        $value = RinacWpTestStore::$meta[ $post_id ][ $key ] ?? '';
        if ( ! $single && ! is_array( $value ) ) {
            return array( $value );
        }
        return $value;
    }
}

if ( ! function_exists( 'get_post' ) ) {
    function get_post( int $post_id ) {
        return RinacWpTestStore::$posts[ $post_id ] ?? null;
    }
}
