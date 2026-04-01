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
        $this->assertSame( 'publish', RinacWpTestStore::$posts[ (int) $bookingId ]->post_status );
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
        $this->assertSame( 'publish', RinacWpTestStore::$posts[ (int) $bookingId ]->post_status );
    }
}
