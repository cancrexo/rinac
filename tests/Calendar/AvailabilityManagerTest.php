<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use RINAC\Booking\BookingRecordRepository;
use RINAC\Calendar\AvailabilityManager;

final class AvailabilityManagerTest extends TestCase {
    protected function setUp(): void {
        parent::setUp();
        RinacWpTestStore::reset();
    }

    public function test_get_availability_ignores_expired_and_cancelled_bookings(): void {
        RinacWpTestStore::$meta[ 100 ] = array(
            'rinac_booking_mode' => 'date',
            '_rinac_base_capacity' => 10,
            '_rinac_capacity_total_max' => 0,
            '_rinac_capacity_min_booking' => 1,
        );

        $repository = new BookingRecordRepository();

        $confirmedId = $repository->create(
            array(
                'post_status' => 'publish',
                'post_title' => 'Confirmed',
                'product_id' => 100,
                'booking_status' => 'confirmed',
                'equivalent_qty' => 3.0,
            )
        );
        $this->assertIsInt( $confirmedId );

        $expiredHoldId = $repository->create(
            array(
                'post_status' => 'pending',
                'post_title' => 'Expired hold',
                'product_id' => 100,
                'booking_status' => 'hold',
                'equivalent_qty' => 4.0,
                'hold_token' => 'expired-token',
                'hold_expires_at' => time() - 60,
            )
        );
        $this->assertIsInt( $expiredHoldId );

        $cancelledId = $repository->create(
            array(
                'post_status' => 'publish',
                'post_title' => 'Cancelled',
                'product_id' => 100,
                'booking_status' => 'cancelled',
                'equivalent_qty' => 2.0,
            )
        );
        $this->assertIsInt( $cancelledId );

        $manager = new AvailabilityManager();
        $availability = $manager->getAvailability( 100, '2026-04-10', '2026-04-10' );

        $this->assertIsArray( $availability );
        $this->assertSame( 7.0, (float) $availability['remaining_capacity'] );
        $this->assertTrue( (bool) $availability['available'] );
    }
}
