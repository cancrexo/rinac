<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use RINAC\Booking\BookingManager;

final class BookingManagerTest extends TestCase {
    protected function setUp(): void {
        parent::setUp();
        RinacWpTestStore::reset();
    }

    public function test_validate_booking_request_returns_normalized_payload_when_valid(): void {
        $this->seedProductMeta();

        RinacWpTestStore::$posts[ 201 ] = (object) array(
            'ID' => 201,
            'post_type' => 'rinac_participant',
        );
        RinacWpTestStore::$posts[ 301 ] = (object) array(
            'ID' => 301,
            'post_type' => 'rinac_resource',
        );

        RinacWpTestStore::$meta[ 201 ] = array(
            '_rinac_pt_is_active' => 1,
            '_rinac_pt_min_qty' => 1,
            '_rinac_pt_max_qty' => 5,
            '_rinac_pt_price_type' => 'fixed',
            '_rinac_pt_price_value' => 10,
            '_rinac_pt_capacity_fraction' => 1.5,
        );

        RinacWpTestStore::$meta[ 301 ] = array(
            '_rinac_resource_is_active' => 1,
            '_rinac_resource_min_qty' => 1,
            '_rinac_resource_max_qty' => 3,
            '_rinac_resource_type' => 'addon',
            '_rinac_resource_price_policy' => 'per_person',
            '_rinac_resource_price_value' => 5,
        );

        $manager = new BookingManager();

        $result = $manager->validateBookingRequest(
            $this->fakeProduct( 100 ),
            array(
                array( 'id' => 201, 'qty' => 2 ),
            ),
            array(
                array( 'id' => 301, 'qty' => 1 ),
            ),
            array(
                'days' => 2,
                'nights' => 1,
            )
        );

        $this->assertIsArray( $result );
        $this->assertSame( 20.0, $result['pricing']['subtotal_participants'] );
        $this->assertSame( 10.0, $result['pricing']['subtotal_resources'] );
        $this->assertSame( 30.0, $result['pricing']['total_estimated'] );
        $this->assertSame( 2, $result['capacity']['participants_units'] );
        $this->assertSame( 3.0, $result['capacity']['equivalent_total'] );
        $this->assertSame( 5.0, $result['capacity']['effective_global_capacity'] );
        $this->assertSame( 2.0, $result['capacity']['remaining_capacity_after'] );
    }

    public function test_validate_booking_request_fails_when_participant_limits_are_exceeded(): void {
        $this->seedProductMeta();

        RinacWpTestStore::$posts[ 201 ] = (object) array(
            'ID' => 201,
            'post_type' => 'rinac_participant',
        );
        RinacWpTestStore::$meta[ 201 ] = array(
            '_rinac_pt_is_active' => 1,
            '_rinac_pt_min_qty' => 1,
            '_rinac_pt_max_qty' => 2,
            '_rinac_pt_price_type' => 'free',
            '_rinac_pt_price_value' => 0,
            '_rinac_pt_capacity_fraction' => 1,
        );

        $manager = new BookingManager();
        $result = $manager->validateBookingRequest(
            $this->fakeProduct( 100 ),
            array(
                array( 'id' => 201, 'qty' => 3 ),
            ),
            array()
        );

        $this->assertInstanceOf( WP_Error::class, $result );
        $error_data = $result->get_error_data( 'rinac_booking_validation_failed' );
        $this->assertIsArray( $error_data );
        $this->assertSame( 'participant_above_max', $error_data['errors'][0]['code'] ?? null );
    }

    public function test_validate_booking_request_fails_when_unit_resource_is_incompatible_with_mode(): void {
        $this->seedProductMeta();
        RinacWpTestStore::$meta[ 100 ]['rinac_booking_mode'] = 'date';

        RinacWpTestStore::$posts[ 201 ] = (object) array(
            'ID' => 201,
            'post_type' => 'rinac_participant',
        );
        RinacWpTestStore::$posts[ 301 ] = (object) array(
            'ID' => 301,
            'post_type' => 'rinac_resource',
        );

        RinacWpTestStore::$meta[ 201 ] = array(
            '_rinac_pt_is_active' => 1,
            '_rinac_pt_min_qty' => 1,
            '_rinac_pt_max_qty' => 10,
            '_rinac_pt_price_type' => 'free',
            '_rinac_pt_price_value' => 0,
            '_rinac_pt_capacity_fraction' => 1,
        );

        RinacWpTestStore::$meta[ 301 ] = array(
            '_rinac_resource_is_active' => 1,
            '_rinac_resource_min_qty' => 1,
            '_rinac_resource_max_qty' => 2,
            '_rinac_resource_type' => 'unit',
            '_rinac_resource_price_policy' => 'fixed',
            '_rinac_resource_price_value' => 25,
        );

        $manager = new BookingManager();
        $result = $manager->validateBookingRequest(
            $this->fakeProduct( 100 ),
            array(
                array( 'id' => 201, 'qty' => 1 ),
            ),
            array(
                array( 'id' => 301, 'qty' => 1 ),
            )
        );

        $this->assertInstanceOf( WP_Error::class, $result );
        $error_data = $result->get_error_data( 'rinac_booking_validation_failed' );
        $this->assertIsArray( $error_data );
        $this->assertSame( 'resource_mode_incompatible', $error_data['errors'][0]['code'] ?? null );
    }

    public function test_validate_booking_request_fails_when_capacity_equivalent_exceeds_global_capacity(): void {
        $this->seedProductMeta();
        RinacWpTestStore::$meta[ 100 ]['_rinac_base_capacity'] = 2;
        RinacWpTestStore::$meta[ 100 ]['_rinac_capacity_total_max'] = 0;

        RinacWpTestStore::$posts[ 201 ] = (object) array(
            'ID' => 201,
            'post_type' => 'rinac_participant',
        );
        RinacWpTestStore::$meta[ 201 ] = array(
            '_rinac_pt_is_active' => 1,
            '_rinac_pt_min_qty' => 1,
            '_rinac_pt_max_qty' => 10,
            '_rinac_pt_price_type' => 'free',
            '_rinac_pt_price_value' => 0,
            '_rinac_pt_capacity_fraction' => 1.5,
        );

        $manager = new BookingManager();
        $result = $manager->validateBookingRequest(
            $this->fakeProduct( 100 ),
            array(
                array( 'id' => 201, 'qty' => 2 ),
            ),
            array()
        );

        $this->assertInstanceOf( WP_Error::class, $result );
        $error_data = $result->get_error_data( 'rinac_booking_validation_failed' );
        $this->assertIsArray( $error_data );
        $codes = array_column( $error_data['errors'] ?? array(), 'code' );
        $this->assertContains( 'capacity_exceeded_total', $codes );
        $this->assertContains( 'insufficient_capacity', $codes );
    }

    private function seedProductMeta(): void {
        RinacWpTestStore::$meta[ 100 ] = array(
            '_rinac_allowed_participant_types' => array( 201 ),
            '_rinac_allowed_resources' => array( 301 ),
            'rinac_booking_mode' => 'unidad_rango',
            '_rinac_base_capacity' => 5,
            '_rinac_capacity_total_max' => 0,
            '_rinac_capacity_min_booking' => 1,
        );
    }

    /**
     * @return object
     */
    private function fakeProduct( int $id ) {
        return new class( $id ) {
            private int $id;

            public function __construct( int $id ) {
                $this->id = $id;
            }

            public function get_id(): int {
                return $this->id;
            }
        };
    }
}
