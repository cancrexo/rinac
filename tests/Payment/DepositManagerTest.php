<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use RINAC\Booking\BookingRecordRepository;
use RINAC\Payment\DepositManager;

final class DepositManagerTest extends TestCase {
    protected function setUp(): void {
        parent::setUp();
        RinacWpTestStore::reset();
    }

    public function test_sync_bookings_from_order_status_updates_booking_status(): void {
        $repository = new BookingRecordRepository();
        $bookingId = $repository->create(
            array(
                'post_status' => 'pending',
                'post_title' => 'Reserva prueba',
                'product_id' => 100,
                'order_id' => 500,
                'booking_status' => 'hold',
            )
        );
        $this->assertIsInt( $bookingId );

        $manager = new DepositManager();
        $manager->syncBookingsFromOrderStatus( 500, 'pending', 'completed', null );

        $bookingStatus = (string) get_post_meta( (int) $bookingId, '_rinac_booking_status', true );
        $this->assertSame( 'completed', $bookingStatus );
        $this->assertSame( 'private', RinacWpTestStore::$posts[ (int) $bookingId ]->post_status );
    }

    public function test_sync_bookings_marks_partial_refund_without_releasing_capacity(): void {
        $repository = new BookingRecordRepository();
        $bookingId = $repository->create(
            array(
                'post_status' => 'publish',
                'post_title' => 'Reserva parcial refund',
                'product_id' => 100,
                'order_id' => 600,
                'booking_status' => 'confirmed',
            )
        );
        $this->assertIsInt( $bookingId );

        $order = new class {
            public function get_total(): float {
                return 100.0;
            }
            public function get_total_refunded(): float {
                return 20.0;
            }
        };

        $manager = new DepositManager();
        $manager->syncBookingsFromOrderStatus( 600, 'completed', 'refunded', $order );

        $bookingStatus = (string) get_post_meta( (int) $bookingId, '_rinac_booking_status', true );
        $this->assertSame( 'partially_refunded', $bookingStatus );
        $this->assertSame( 'private', RinacWpTestStore::$posts[ (int) $bookingId ]->post_status );
    }

    public function test_sync_bookings_applies_order_hold_ttl_from_settings(): void {
        update_option(
            'rinac_settings',
            array(
                'order_hold_ttl_pending_minutes' => 10,
                'order_hold_ttl_on_hold_hours' => 24,
                'order_hold_ttl_bacs_hours' => 72,
            )
        );

        $repository = new BookingRecordRepository();
        $bookingId = $repository->create(
            array(
                'post_status' => 'private',
                'post_title' => 'Reserva hold',
                'product_id' => 100,
                'order_id' => 700,
                'booking_status' => 'hold',
            )
        );
        $this->assertIsInt( $bookingId );

        $order = new class {
            public function get_payment_method(): string {
                return '';
            }
        };

        $before = time();
        $manager = new DepositManager();
        $manager->syncBookingsFromOrderStatus( 700, 'pending', 'pending', $order );
        $after = time();

        $expiresAt = (int) get_post_meta( (int) $bookingId, '_rinac_hold_expires_at', true );
        $scope = (string) get_post_meta( (int) $bookingId, '_rinac_hold_scope', true );
        $this->assertSame( 'order', $scope );
        $this->assertGreaterThanOrEqual( $before + ( 10 * MINUTE_IN_SECONDS ), $expiresAt );
        $this->assertLessThanOrEqual( $after + ( 10 * MINUTE_IN_SECONDS ) + 1, $expiresAt );
    }
}
