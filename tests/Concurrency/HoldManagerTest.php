<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use RINAC\Concurrency\HoldManager;

final class HoldManagerTest extends TestCase {
    protected function setUp(): void {
        parent::setUp();
        RinacWpTestStore::reset();
    }

    public function test_create_and_confirm_hold_are_idempotent(): void {
        $manager = new HoldManager();

        $created = $manager->createHold( 100, 0, '2026-04-10', '2026-04-10', 2.5 );
        $this->assertIsArray( $created );
        $this->assertNotEmpty( $created['hold_token'] ?? '' );

        $firstConfirm = $manager->confirmHold( (string) $created['hold_token'] );
        $this->assertIsArray( $firstConfirm );
        $this->assertSame( 'confirmed', $firstConfirm['status'] ?? null );

        $secondConfirm = $manager->confirmHold( (string) $created['hold_token'] );
        $this->assertIsArray( $secondConfirm );
        $this->assertTrue( (bool) ( $secondConfirm['idempotent'] ?? false ) );
    }

    public function test_cleanup_expired_holds_marks_booking_expired(): void {
        $manager = new HoldManager();
        $created = $manager->createHold( 100, 0, '2026-04-10', '2026-04-10', 1.0 );
        $this->assertIsArray( $created );
        update_post_meta( (int) $created['booking_id'], '_rinac_hold_expires_at', time() - 10 );

        $manager->cleanupExpiredHolds();

        $bookingId = (int) $created['booking_id'];
        $status = (string) get_post_meta( $bookingId, '_rinac_booking_status', true );
        $this->assertSame( 'expired', $status );
    }

    public function test_create_hold_uses_configured_cart_ttl(): void {
        update_option(
            'rinac_settings',
            array(
                'cart_hold_ttl_minutes' => 2,
                'cart_hold_max_lifetime_minutes' => 10,
                'cart_hold_refresh_min_interval_seconds' => 60,
            )
        );

        $before = time();
        $manager = new HoldManager();
        $created = $manager->createHold( 100, 0, '2026-04-10', '2026-04-10', 1.0 );
        $after = time();

        $this->assertIsArray( $created );
        $expiresAt = (int) ( $created['expires_at'] ?? 0 );
        $this->assertGreaterThanOrEqual( $before + 120, $expiresAt );
        $this->assertLessThanOrEqual( $after + 121, $expiresAt );
    }

    public function test_refresh_cart_hold_applies_rate_limit_from_settings(): void {
        update_option(
            'rinac_settings',
            array(
                'cart_hold_ttl_minutes' => 5,
                'cart_hold_max_lifetime_minutes' => 30,
                'cart_hold_refresh_min_interval_seconds' => 600,
            )
        );

        $manager = new HoldManager();
        $created = $manager->createHold( 100, 0, '2026-04-10', '2026-04-10', 1.0 );
        $this->assertIsArray( $created );

        $refreshed = $manager->refreshCartHold( (string) $created['hold_token'] );
        $this->assertIsArray( $refreshed );
        $this->assertTrue( (bool) ( $refreshed['rate_limited'] ?? false ) );
    }
}
