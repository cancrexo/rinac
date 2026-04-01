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

    /**
     * TTL por defecto del bloqueo (segundos).
     *
     * @var int
     */
    private int $defaultTtl = 15 * MINUTE_IN_SECONDS;
    private BookingRecordRepository $bookingRepository;

    public function __construct() {
        $this->bookingRepository = new BookingRecordRepository();
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
        $expires_at = time() + $ttl;
        $token = wp_generate_uuid4();

        $booking_id = $this->bookingRepository->create(
            array(
                'post_status' => 'pending',
                'post_title' => sprintf(
                    /* translators: 1: product id, 2: token. */
                    __( 'Hold producto #%1$d (%2$s)', 'rinac' ),
                    $product_id,
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
}
