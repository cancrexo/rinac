<?php

require_once dirname( __DIR__ ) . '/vendor/autoload.php';

class RinacWpTestStore {
    /** @var array<int,array<string,mixed>> */
    public static array $meta = array();

    /** @var array<int,object> */
    public static array $posts = array();

    /** @var array<string,array<int,callable>> */
    public static array $hooks = array();

    /** @var array<string,array<string,mixed>> */
    public static array $cronSchedules = array();

    /** @var array<string,int> */
    public static array $scheduledHooks = array();

    /** @var int */
    public static int $nextPostId = 1000;

    /** @var array<string,mixed> */
    public static array $transients = array();

    public static function reset(): void {
        self::$meta = array();
        self::$posts = array();
        self::$hooks = array();
        self::$cronSchedules = array();
        self::$scheduledHooks = array();
        self::$nextPostId = 1000;
        self::$transients = array();
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

        public function get_error_code(): string {
            return $this->code;
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

if ( ! function_exists( '__' ) ) {
    function __( string $text, string $domain = '' ): string {
        return $text;
    }
}

if ( ! defined( 'MINUTE_IN_SECONDS' ) ) {
    define( 'MINUTE_IN_SECONDS', 60 );
}

if ( ! function_exists( 'sanitize_key' ) ) {
    function sanitize_key( string $key ): string {
        return strtolower( preg_replace( '/[^a-z0-9_\-]/', '', $key ) ?? '' );
    }
}

if ( ! function_exists( 'sanitize_text_field' ) ) {
    function sanitize_text_field( string $text ): string {
        return trim( $text );
    }
}

if ( ! function_exists( 'wp_unslash' ) ) {
    function wp_unslash( string $text ): string {
        return $text;
    }
}

if ( ! function_exists( 'wp_generate_uuid4' ) ) {
    function wp_generate_uuid4(): string {
        return uniqid( 'uuid_', true );
    }
}

if ( ! function_exists( 'is_wp_error' ) ) {
    function is_wp_error( $value ): bool {
        return $value instanceof WP_Error;
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

if ( ! function_exists( 'update_post_meta' ) ) {
    function update_post_meta( int $post_id, string $key, $value ): void {
        if ( ! isset( RinacWpTestStore::$meta[ $post_id ] ) ) {
            RinacWpTestStore::$meta[ $post_id ] = array();
        }
        RinacWpTestStore::$meta[ $post_id ][ $key ] = $value;
    }
}

if ( ! function_exists( 'add_post_meta' ) ) {
    function add_post_meta( int $post_id, string $key, $value, bool $unique = false ): bool {
        if ( ! isset( RinacWpTestStore::$meta[ $post_id ] ) ) {
            RinacWpTestStore::$meta[ $post_id ] = array();
        }
        if ( $unique && array_key_exists( $key, RinacWpTestStore::$meta[ $post_id ] ) ) {
            return false;
        }
        RinacWpTestStore::$meta[ $post_id ][ $key ] = $value;
        return true;
    }
}

if ( ! function_exists( 'delete_post_meta' ) ) {
    function delete_post_meta( int $post_id, string $key ): void {
        if ( isset( RinacWpTestStore::$meta[ $post_id ][ $key ] ) ) {
            unset( RinacWpTestStore::$meta[ $post_id ][ $key ] );
        }
    }
}

if ( ! function_exists( 'wp_insert_post' ) ) {
    function wp_insert_post( array $postarr, bool $wp_error = false ) {
        $id = RinacWpTestStore::$nextPostId++;
        RinacWpTestStore::$posts[ $id ] = (object) array(
            'ID' => $id,
            'post_type' => (string) ( $postarr['post_type'] ?? 'post' ),
            'post_status' => (string) ( $postarr['post_status'] ?? 'draft' ),
            'post_title' => (string) ( $postarr['post_title'] ?? '' ),
            'post_content' => (string) ( $postarr['post_content'] ?? '' ),
        );
        return $id;
    }
}

if ( ! function_exists( 'wp_update_post' ) ) {
    function wp_update_post( array $postarr ) {
        $id = isset( $postarr['ID'] ) ? (int) $postarr['ID'] : 0;
        if ( $id <= 0 || ! isset( RinacWpTestStore::$posts[ $id ] ) ) {
            return 0;
        }
        foreach ( $postarr as $key => $value ) {
            if ( 'ID' === $key ) {
                continue;
            }
            RinacWpTestStore::$posts[ $id ]->{$key} = $value;
        }
        return $id;
    }
}

if ( ! class_exists( 'WP_Query' ) ) {
    class WP_Query {
        /** @var array<int,mixed> */
        public array $posts = array();

        public function __construct( array $args = array() ) {
            $post_type = (string) ( $args['post_type'] ?? '' );
            $post_statuses = $args['post_status'] ?? array( 'publish' );
            if ( ! is_array( $post_statuses ) ) {
                $post_statuses = array( (string) $post_statuses );
            }
            $meta_query = $args['meta_query'] ?? array();
            $fields = (string) ( $args['fields'] ?? '' );
            $posts_per_page = isset( $args['posts_per_page'] ) ? (int) $args['posts_per_page'] : -1;

            $matches = array();
            foreach ( RinacWpTestStore::$posts as $id => $post ) {
                if ( '' !== $post_type && $post->post_type !== $post_type ) {
                    continue;
                }
                if ( ! in_array( $post->post_status, $post_statuses, true ) ) {
                    continue;
                }
                if ( ! $this->matchesMetaQuery( (int) $id, $meta_query ) ) {
                    continue;
                }
                $matches[] = ( 'ids' === $fields ) ? (int) $id : $post;
            }

            if ( $posts_per_page > -1 ) {
                $matches = array_slice( $matches, 0, $posts_per_page );
            }

            $this->posts = $matches;
        }

        private function matchesMetaQuery( int $post_id, array $meta_query ): bool {
            if ( empty( $meta_query ) ) {
                return true;
            }
            foreach ( $meta_query as $condition ) {
                if ( ! is_array( $condition ) ) {
                    continue;
                }
                $key = (string) ( $condition['key'] ?? '' );
                $value = $condition['value'] ?? '';
                $compare = (string) ( $condition['compare'] ?? '=' );
                $meta_value = RinacWpTestStore::$meta[ $post_id ][ $key ] ?? '';
                if ( '=' === $compare && (string) $meta_value !== (string) $value ) {
                    return false;
                }
            }
            return true;
        }
    }
}

if ( ! function_exists( 'add_action' ) ) {
    function add_action( string $hook, callable $callback, int $priority = 10, int $accepted_args = 1 ): void {
        if ( ! isset( RinacWpTestStore::$hooks[ $hook ] ) ) {
            RinacWpTestStore::$hooks[ $hook ] = array();
        }
        RinacWpTestStore::$hooks[ $hook ][] = $callback;
    }
}

if ( ! function_exists( 'add_filter' ) ) {
    function add_filter( string $hook, callable $callback, int $priority = 10, int $accepted_args = 1 ): void {
        add_action( $hook, $callback, $priority, $accepted_args );
    }
}

if ( ! function_exists( 'wp_next_scheduled' ) ) {
    function wp_next_scheduled( string $hook ) {
        return RinacWpTestStore::$scheduledHooks[ $hook ] ?? false;
    }
}

if ( ! function_exists( 'wp_schedule_event' ) ) {
    function wp_schedule_event( int $timestamp, string $recurrence, string $hook ): void {
        RinacWpTestStore::$scheduledHooks[ $hook ] = $timestamp;
    }
}

if ( ! function_exists( 'wp_clear_scheduled_hook' ) ) {
    function wp_clear_scheduled_hook( string $hook ): void {
        unset( RinacWpTestStore::$scheduledHooks[ $hook ] );
    }
}

if ( ! function_exists( 'is_admin' ) ) {
    function is_admin(): bool {
        return false;
    }
}

if ( ! function_exists( 'wc_format_decimal' ) ) {
    function wc_format_decimal( float $value, int $decimals = 2 ): string {
        return number_format( $value, $decimals, '.', '' );
    }
}

if ( ! function_exists( 'wc_price' ) ) {
    function wc_price( float $value ): string {
        return '$' . number_format( $value, 2, '.', '' );
    }
}

if ( ! function_exists( 'get_transient' ) ) {
    function get_transient( string $key ) {
        return RinacWpTestStore::$transients[ $key ] ?? false;
    }
}

if ( ! function_exists( 'set_transient' ) ) {
    function set_transient( string $key, $value, int $expiration = 0 ): void {
        RinacWpTestStore::$transients[ $key ] = $value;
    }
}
