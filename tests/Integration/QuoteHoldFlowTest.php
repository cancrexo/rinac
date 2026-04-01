<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use RINAC\Booking\BookingRecordRepository;
use RINAC\Calendar\AvailabilityManager;
use RINAC\Concurrency\HoldManager;
use RINAC\Payment\DepositManager;

final class QuoteHoldFlowTest extends TestCase {
    protected function setUp(): void {
        parent::setUp();
        RinacWpTestStore::reset();
    }

    public function test_quote_hold_confirm_and_cancel_release_capacity(): void {
        RinacWpTestStore::$meta[ 100 ] = array(
            'rinac_booking_mode' => 'date',
            '_rinac_base_capacity' => 5,
            '_rinac_capacity_total_max' => 0,
            '_rinac_capacity_min_booking' => 1,
        );

        $holdManager = new HoldManager();
        $hold = $holdManager->createHold( 100, 0, '2026-04-10', '2026-04-10', 2.0 );
        $this->assertIsArray( $hold );

        $token = (string) $hold['hold_token'];
        $confirm = $holdManager->confirmHold( $token );
        $this->assertIsArray( $confirm );
        $this->assertSame( 'confirmed', $confirm['status'] ?? null );

        $bookingId = (int) $confirm['booking_id'];
        $repository = new BookingRecordRepository();
        $repository->update(
            $bookingId,
            array(
                'product_id' => 100,
                'slot_id' => 0,
                'order_id' => 900,
                'start' => '2026-04-10',
                'end' => '2026-04-10',
                'equivalent_qty' => 2.0,
                'booking_status' => 'confirmed',
            )
        );

        $availabilityBeforeCancel = ( new AvailabilityManager() )->getAvailability( 100, '2026-04-10', '2026-04-10' );
        $this->assertSame( 3.0, (float) $availabilityBeforeCancel['remaining_capacity'] );

        $depositManager = new DepositManager();
        $depositManager->syncBookingsFromOrderStatus( 900, 'processing', 'cancelled', null );

        $availabilityAfterCancel = ( new AvailabilityManager() )->getAvailability( 100, '2026-04-10', '2026-04-10' );
        $this->assertSame( 5.0, (float) $availabilityAfterCancel['remaining_capacity'] );
    }
}
