<?php

namespace RINAC\Concurrency;

use RINAC\Booking\BookingRecordRepository;
use WP_Error;

/**
 * Gestiona bloqueos temporales (quote/hold) para evitar sobre-reservas.
 */
class HoldManager {
    private const CRON_HOOK = 'rinac_cleanup_expired_holds';
    private const CONFIRM_LOCK_META = '_rinac_hold_confirm_lock';
    private const CART_HOLD_SCOPE = 'cart';
    private const ORDER_HOLD_SCOPE = 'order';

    /**
     * TTL por defecto del bloqueo (segundos).
     *
     * @var int
     */
    private int $defaultTtl = 15 * MINUTE_IN_SECONDS;
    private int $cartMaxLifetime = 90 * MINUTE_IN_SECONDS;
    private int $refreshMinInterval = 60;
    private BookingRecordRepository $bookingRepository;

    public function __construct() {
        $this->bookingRepository = new BookingRecordRepository();
        $settings = $this->getConcurrencySettings();
        $this->defaultTtl = max( 60, (int) $settings['cart_hold_ttl_minutes'] * MINUTE_IN_SECONDS );
        $this->cartMaxLifetime = max( $this->defaultTtl, (int) $settings['cart_hold_max_lifetime_minutes'] * MINUTE_IN_SECONDS );
        $this->refreshMinInterval = max( 10, (int) $settings['cart_hold_refresh_min_interval_seconds'] );
    }

    /**
     * Registra limpieza lazy + cron.
     *
     * @return void
     */
    public function register(): void {
        add_action( self::CRON_HOOK, array( $this, 'cleanupExpiredHolds' ) );
        add_filter( 'cron_schedules', array( $this, 'addCronSchedules' ) );
        add_action( 'init', array( $this, 'ensureCleanupSchedule' ) );
        add_action( 'init', array( $this, 'cleanupExpiredHolds' ) );
    }

    /**
     * Crea un hold temporal en `rinac_booking`.
     *
     * @param int $product_id
     * @param int $slot_id
     * @param string $start
     * @param string $end
     * @param float $equivalent_qty
     * @param int $ttl_seconds
     * @return array<string,mixed>|WP_Error
     */
    public function createHold(
        int $product_id,
        int $slot_id,
        string $start,
        string $end,
        float $equivalent_qty,
        int $ttl_seconds = 0
    ) {
        $this->cleanupExpiredHolds();
        $ttl = $ttl_seconds > 0 ? $ttl_seconds : $this->defaultTtl;
        $now = time();
        $expires_at = $now + $ttl;
        $max_expires_at = $now + $this->cartMaxLifetime;
        $token = wp_generate_uuid4();
        $product_title = get_the_title( $product_id );
        $product_label = is_string( $product_title ) && '' !== $product_title
            ? $product_title
            : sprintf(
                /* translators: %d product id. */
                __( 'Producto #%d', 'rinac' ),
                $product_id
            );

        $booking_id = $this->bookingRepository->create(
            array(
                'post_status' => 'private',
                'post_title' => sprintf(
                    /* translators: 1: product title, 2: start date, 3: token short. */
                    __( 'Hold %1$s - %2$s (%3$s)', 'rinac' ),
                    $product_label,
                    $start,
                    substr( $token, 0, 8 )
                ),
                'product_id' => $product_id,
                'slot_id' => $slot_id,
                'start' => $start,
                'end' => $end,
                'equivalent_qty' => max( 0.0, $equivalent_qty ),
                'booking_status' => 'hold',
                'hold_token' => $token,
                'hold_expires_at' => $expires_at,
                'hold_scope' => self::CART_HOLD_SCOPE,
                'hold_last_refresh_at' => $now,
                'hold_max_expires_at' => $max_expires_at,
            )
        );

        if ( is_wp_error( $booking_id ) || ! is_numeric( $booking_id ) ) {
            return new WP_Error(
                'rinac_hold_create_failed',
                esc_html__( 'No se pudo crear el bloqueo temporal.', 'rinac' )
            );
        }
        $booking_id = (int) $booking_id;

        $this->invalidateAvailabilityTransients();

        return array(
            'booking_id' => $booking_id,
            'hold_token' => $token,
            'expires_at' => $expires_at,
            'ttl_seconds' => $ttl,
            'hold_scope' => self::CART_HOLD_SCOPE,
        );
    }

    /**
     * Refresca TTL de un hold de carrito activo (sliding expiration).
     *
     * @param string $token
     * @param int $ttl_seconds
     * @return array<string,mixed>|WP_Error
     */
    public function refreshCartHold( string $token, int $ttl_seconds = 0 ) {
        $hold = $this->getHoldByToken( $token );
        if ( is_wp_error( $hold ) ) {
            return $hold;
        }

        $booking_id = (int) $hold['booking_id'];
        $hold_scope = (string) get_post_meta( $booking_id, '_rinac_hold_scope', true );
        if ( '' !== $hold_scope && self::CART_HOLD_SCOPE !== $hold_scope ) {
            return new WP_Error( 'rinac_hold_scope_not_cart', esc_html__( 'El bloqueo no es de tipo carrito.', 'rinac' ) );
        }

        $now = time();
        $last_refresh_at = (int) get_post_meta( $booking_id, '_rinac_hold_last_refresh_at', true );
        $max_expires_at = (int) get_post_meta( $booking_id, '_rinac_hold_max_expires_at', true );
        $current_expires_at = (int) get_post_meta( $booking_id, '_rinac_hold_expires_at', true );
        $ttl = $ttl_seconds > 0 ? $ttl_seconds : $this->defaultTtl;

        if ( $max_expires_at <= 0 ) {
            $max_expires_at = $now + $this->cartMaxLifetime;
            update_post_meta( $booking_id, '_rinac_hold_max_expires_at', $max_expires_at );
        }

        $rate_limited = ( $last_refresh_at > 0 && ( $now - $last_refresh_at ) < $this->refreshMinInterval );
        $new_expires_at = $current_expires_at;
        if ( ! $rate_limited ) {
            $candidate_expires_at = $now + $ttl;
            $new_expires_at = min( $candidate_expires_at, $max_expires_at );

            if ( $new_expires_at > $current_expires_at ) {
                update_post_meta( $booking_id, '_rinac_hold_expires_at', $new_expires_at );
            }
            update_post_meta( $booking_id, '_rinac_hold_last_refresh_at', $now );
        }

        return array(
            'booking_id' => $booking_id,
            'hold_token' => $token,
            'expires_at' => $new_expires_at,
            'ttl_seconds' => $ttl,
            'hold_scope' => self::CART_HOLD_SCOPE,
            'rate_limited' => $rate_limited,
        );
    }

    /**
     * Confirma un hold existente.
     *
     * @param string $token
     * @return array<string,mixed>|WP_Error
     */
    public function confirmHold( string $token ) {
        $this->cleanupExpiredHolds();
        $hold = $this->getHoldByToken( $token );
        if ( is_wp_error( $hold ) ) {
            if ( 'rinac_hold_not_active' === $hold->get_error_code() ) {
                $booking_id = $this->bookingRepository->findByHoldToken( $token );
                if ( $booking_id > 0 ) {
                    $status = (string) get_post_meta( $booking_id, '_rinac_booking_status', true );
                    if ( in_array( $status, array( 'confirmed', 'completed' ), true ) ) {
                        return array(
                            'booking_id' => $booking_id,
                            'hold_token' => $token,
                            'status' => $status,
                            'idempotent' => true,
                        );
                    }
                }
            }
            return $hold;
        }

        $booking_id = (int) $hold['booking_id'];
        if ( ! add_post_meta( $booking_id, self::CONFIRM_LOCK_META, time(), true ) ) {
            return new WP_Error(
                'rinac_hold_confirm_race',
                esc_html__( 'El hold está siendo confirmado por otra solicitud. Reintenta.', 'rinac' )
            );
        }

        $current_status = (string) get_post_meta( $booking_id, '_rinac_booking_status', true );
        if ( 'hold' !== $current_status ) {
            delete_post_meta( $booking_id, self::CONFIRM_LOCK_META );
            return new WP_Error( 'rinac_hold_not_active', esc_html__( 'El bloqueo ya no está en estado hold.', 'rinac' ) );
        }

        delete_post_meta( $booking_id, '_rinac_hold_expires_at' );
        $this->bookingRepository->setStatuses( $booking_id, 'confirmed', 'private' );
        delete_post_meta( $booking_id, self::CONFIRM_LOCK_META );

        $this->invalidateAvailabilityTransients();

        return array(
            'booking_id' => $booking_id,
            'hold_token' => $token,
            'status' => 'confirmed',
        );
    }

    /**
     * Obtiene hold por token y valida expiración.
     *
     * @param string $token
     * @return array<string,mixed>|WP_Error
     */
    public function getHoldByToken( string $token ) {
        if ( '' === $token ) {
            return new WP_Error( 'rinac_hold_token_missing', esc_html__( 'Falta hold_token.', 'rinac' ) );
        }

        $booking_id = $this->bookingRepository->findByHoldToken( $token );
        if ( $booking_id <= 0 ) {
            return new WP_Error( 'rinac_hold_not_found', esc_html__( 'No existe bloqueo activo para este token.', 'rinac' ) );
        }
        $booking_status = (string) get_post_meta( $booking_id, '_rinac_booking_status', true );
        if ( 'hold' !== $booking_status ) {
            return new WP_Error( 'rinac_hold_not_active', esc_html__( 'El bloqueo ya no está en estado hold.', 'rinac' ) );
        }

        $expires_at = (int) get_post_meta( $booking_id, '_rinac_hold_expires_at', true );
        if ( $expires_at > 0 && $expires_at < time() ) {
            $this->bookingRepository->setStatuses( $booking_id, 'expired', 'draft' );
            $this->invalidateAvailabilityTransients();
            return new WP_Error( 'rinac_hold_expired', esc_html__( 'El bloqueo temporal ha expirado.', 'rinac' ) );
        }

        return array(
            'booking_id' => $booking_id,
            'product_id' => (int) get_post_meta( $booking_id, '_rinac_booking_product_id', true ),
            'slot_id' => (int) get_post_meta( $booking_id, '_rinac_booking_slot_id', true ),
            'start' => (string) get_post_meta( $booking_id, '_rinac_booking_start', true ),
            'end' => (string) get_post_meta( $booking_id, '_rinac_booking_end', true ),
            'equivalent_qty' => (float) get_post_meta( $booking_id, '_rinac_booking_equivalent_qty', true ),
            'expires_at' => $expires_at,
            'hold_scope' => (string) get_post_meta( $booking_id, '_rinac_hold_scope', true ),
        );
    }

    /**
     * Limpia holds expirados.
     *
     * @return void
     */
    public function cleanupExpiredHolds(): void {
        $query = new \WP_Query(
            array(
                'post_type' => 'rinac_booking',
                'post_status' => array( 'pending', 'private', 'publish' ),
                'posts_per_page' => -1,
                'fields' => 'ids',
                'no_found_rows' => true,
                'meta_query' => array(
                    array(
                        'key' => '_rinac_booking_status',
                        'value' => 'hold',
                        'compare' => '=',
                    ),
                ),
            )
        );

        if ( empty( $query->posts ) ) {
            return;
        }

        $updated = false;
        foreach ( $query->posts as $booking_id ) {
            $booking_id = (int) $booking_id;
            $expires_at = (int) get_post_meta( $booking_id, '_rinac_hold_expires_at', true );
            if ( $expires_at > 0 && $expires_at < time() ) {
                $this->bookingRepository->setStatuses( $booking_id, 'expired', 'draft' );
                $updated = true;
            }
        }

        if ( $updated ) {
            $this->invalidateAvailabilityTransients();
        }
    }

    /**
     * Programa cron de limpieza si no existe.
     *
     * @return void
     */
    public function ensureCleanupSchedule(): void {
        if ( wp_next_scheduled( self::CRON_HOOK ) ) {
            return;
        }

        wp_schedule_event( time() + 60, 'five_minutes', self::CRON_HOOK );
    }

    /**
     * Permite limpiar el cron al desactivar plugin.
     *
     * @return void
     */
    public static function clearCleanupSchedule(): void {
        wp_clear_scheduled_hook( self::CRON_HOOK );
    }

    /**
     * Añade intervalos cron necesarios.
     *
     * @param array<string,array<string,mixed>> $schedules
     * @return array<string,array<string,mixed>>
     */
    public function addCronSchedules( array $schedules ): array {
        if ( ! isset( $schedules['five_minutes'] ) ) {
            $schedules['five_minutes'] = array(
                'interval' => 5 * MINUTE_IN_SECONDS,
                'display' => __( 'Every five minutes', 'rinac' ),
            );
        }
        return $schedules;
    }

    /**
     * Limpia caché de disponibilidad.
     */
    private function invalidateAvailabilityTransients(): void {
        global $wpdb;
        if ( ! $wpdb || ! isset( $wpdb->options ) ) {
            return;
        }

        $like_transient = $wpdb->esc_like( '_transient_rinac_availability_' ) . '%';
        $like_timeout = $wpdb->esc_like( '_transient_timeout_rinac_availability_' ) . '%';
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
                $like_transient,
                $like_timeout
            )
        );
    }

    /**
     * Lee ajustes de concurrencia definidos en admin.
     *
     * @return array<string,int>
     */
    private function getConcurrencySettings(): array {
        $settings = get_option( 'rinac_settings', array() );
        if ( ! is_array( $settings ) ) {
            $settings = array();
        }

        return array(
            'cart_hold_ttl_minutes' => isset( $settings['cart_hold_ttl_minutes'] ) ? (int) $settings['cart_hold_ttl_minutes'] : 15,
            'cart_hold_max_lifetime_minutes' => isset( $settings['cart_hold_max_lifetime_minutes'] ) ? (int) $settings['cart_hold_max_lifetime_minutes'] : 90,
            'cart_hold_refresh_min_interval_seconds' => isset( $settings['cart_hold_refresh_min_interval_seconds'] ) ? (int) $settings['cart_hold_refresh_min_interval_seconds'] : 60,
        );
    }
}
