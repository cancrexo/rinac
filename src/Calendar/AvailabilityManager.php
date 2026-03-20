<?php

namespace rinac\Calendar;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Calcula disponibilidad y evita sobre-reserva.
 *
 * Nota importante sobre metadatos (se usarán en fases posteriores cuando
 * se cree `rinac_booking` y se guarde la selección del usuario):
 *
 * Slot/Turno:
 * - `rinac_slot_capacity_max_equiv` (float, opcional)
 * - `rinac_slot_booking_min_equiv` (float, opcional)
 * - `rinac_slot_booking_max_equiv` (float, opcional)
 * - `rinac_turno_capacity_max_equiv` (float, opcional)
 * - `rinac_turno_booking_min_equiv` (float, opcional)
 * - `rinac_turno_booking_max_equiv` (float, opcional)
 *
 * Tipos de participante:
 * - `rinac_equiv_fraction` (float, opcional; default 1.0)
 *
 * Booking (CPT `rinac_booking`):
 * - `rinac_booking_product_id` (int, requerido para filtrar)
 * - `rinac_booking_slot_id` o `rinac_booking_turno_id` (int)
 * - `rinac_booking_date` (string Y-m-d, opcional; para reservas de fecha única)
 * - `rinac_booking_start_date` / `rinac_booking_end_date` (string, opcional; rango)
 * - `rinac_booking_equiv_total` (float, opcional; si no existe se recalcula desde participantes)
 * - `rinac_booking_participant_counts` (array|json string tipo_id => qty, opcional)
 */
final class AvailabilityManager {
    private const META_SLOT_MAX = 'rinac_slot_capacity_max_equiv';
    private const META_SLOT_MIN = 'rinac_slot_booking_min_equiv';
    private const META_SLOT_RESERVATION_MAX = 'rinac_slot_booking_max_equiv';

    private const META_TURNO_MAX = 'rinac_turno_capacity_max_equiv';
    private const META_TURNO_MIN = 'rinac_turno_booking_min_equiv';
    private const META_TURNO_RESERVATION_MAX = 'rinac_turno_booking_max_equiv';

    private const META_PARTICIPANT_FRACTION = 'rinac_equiv_fraction';

    private const META_BOOKING_PRODUCT_ID = 'rinac_booking_product_id';
    private const META_BOOKING_SLOT_ID = 'rinac_booking_slot_id';
    private const META_BOOKING_TURNO_ID = 'rinac_booking_turno_id';
    private const META_BOOKING_DATE = 'rinac_booking_date';
    private const META_BOOKING_START = 'rinac_booking_start_date';
    private const META_BOOKING_END = 'rinac_booking_end_date';
    private const META_BOOKING_EQUIV_TOTAL = 'rinac_booking_equiv_total';
    private const META_BOOKING_PARTICIPANT_COUNTS = 'rinac_booking_participant_counts';

    private const CACHE_TTL_SECONDS = 300;

    /**
     * Devuelve el contexto de capacidad para un rango/fecha y un slot/turno.
     *
     * @param int $product_id
     * @param int|null $slot_id
     * @param int|null $turno_id
     * @param string $start_date
     * @param string|null $end_date
     *
     * @return array{capacidad_max_equiv: float, ocupado_actual_equiv: float, capacidad_restante_equiv: float, min_equiv: float|null, max_equiv: float|null}
     */
    public static function get_capacity_context(int $product_id, ?int $slot_id, ?int $turno_id, string $start_date, ?string $end_date): array {
        if (($slot_id === null && $turno_id === null) || ($slot_id !== null && $turno_id !== null)) {
            throw new \InvalidArgumentException(__('Debes indicar exactamente uno entre `slot_id` o `turno_id`.', 'rinac'));
        }

        $start_ts = self::parse_to_timestamp($start_date);
        $end_ts = $end_date !== null && $end_date !== ''
            ? self::parse_to_timestamp($end_date)
            : $start_ts;

        if ($end_ts < $start_ts) {
            throw new \InvalidArgumentException(__('`end_date` no puede ser anterior a `start_date`.', 'rinac'));
        }

        $cache_key = self::cache_key($product_id, $slot_id, $turno_id, $start_ts, $end_ts);
        $cached = get_transient($cache_key);
        if (is_array($cached)) {
            return $cached;
        }

        $capacidad_max_equiv = self::get_capacity_max_equiv($slot_id, $turno_id);
        $min_equiv = self::get_min_equiv($product_id, $slot_id, $turno_id);
        $max_equiv = self::get_max_equiv($product_id, $slot_id, $turno_id);

        $ocupado_actual_equiv = self::calculate_occupied_equiv($product_id, $slot_id, $turno_id, $start_ts, $end_ts);
        $capacidad_restante_equiv = max(0.0, $capacidad_max_equiv - $ocupado_actual_equiv);

        $result = [
            'capacidad_max_equiv' => (float) $capacidad_max_equiv,
            'ocupado_actual_equiv' => (float) $ocupado_actual_equiv,
            'capacidad_restante_equiv' => (float) $capacidad_restante_equiv,
            'min_equiv' => $min_equiv,
            'max_equiv' => $max_equiv,
        ];

        set_transient($cache_key, $result, self::CACHE_TTL_SECONDS);

        return $result;
    }

    /**
     * Valida si la capacidad restante soporta la solicitud.
     *
     * @param int $product_id
     * @param int|null $slot_id
     * @param int|null $turno_id
     * @param string $start_date
     * @param string|null $end_date
     * @param array<int,int|float> $participant_counts
     *
     * @return array{capacidad_ok: bool, solicitada_equiv: float, capacidad_restante_equiv: float, capacidad_max_equiv: float, ocupado_actual_equiv: float, min_equiv: float|null, max_equiv: float|null, message: string}
     */
    public static function validate_capacity(int $product_id, ?int $slot_id, ?int $turno_id, string $start_date, ?string $end_date, array $participant_counts): array {
        $context = self::get_capacity_context($product_id, $slot_id, $turno_id, $start_date, $end_date);

        $solicitada_equiv = self::calculate_requested_equiv($participant_counts);

        $min_equiv = $context['min_equiv'];
        $max_equiv = $context['max_equiv'];

        if ($min_equiv !== null && $solicitada_equiv < (float) $min_equiv) {
            return [
                'capacidad_ok' => false,
                'solicitada_equiv' => $solicitada_equiv,
                'capacidad_restante_equiv' => $context['capacidad_restante_equiv'],
                'capacidad_max_equiv' => $context['capacidad_max_equiv'],
                'ocupado_actual_equiv' => $context['ocupado_actual_equiv'],
                'min_equiv' => $min_equiv,
                'max_equiv' => $max_equiv,
                'message' => __('No cumple el mínimo por reserva.', 'rinac'),
            ];
        }

        if ($max_equiv !== null && $solicitada_equiv > (float) $max_equiv) {
            return [
                'capacidad_ok' => false,
                'solicitada_equiv' => $solicitada_equiv,
                'capacidad_restante_equiv' => $context['capacidad_restante_equiv'],
                'capacidad_max_equiv' => $context['capacidad_max_equiv'],
                'ocupado_actual_equiv' => $context['ocupado_actual_equiv'],
                'min_equiv' => $min_equiv,
                'max_equiv' => $max_equiv,
                'message' => __('Supera el máximo permitido por reserva.', 'rinac'),
            ];
        }

        if ($solicitada_equiv > $context['capacidad_restante_equiv']) {
            return [
                'capacidad_ok' => false,
                'solicitada_equiv' => $solicitada_equiv,
                'capacidad_restante_equiv' => $context['capacidad_restante_equiv'],
                'capacidad_max_equiv' => $context['capacidad_max_equiv'],
                'ocupado_actual_equiv' => $context['ocupado_actual_equiv'],
                'min_equiv' => $min_equiv,
                'max_equiv' => $max_equiv,
                'message' => __('No hay suficiente capacidad.', 'rinac'),
            ];
        }

        return [
            'capacidad_ok' => true,
            'solicitada_equiv' => $solicitada_equiv,
            'capacidad_restante_equiv' => $context['capacidad_restante_equiv'],
            'capacidad_max_equiv' => $context['capacidad_max_equiv'],
            'ocupado_actual_equiv' => $context['ocupado_actual_equiv'],
            'min_equiv' => $min_equiv,
            'max_equiv' => $max_equiv,
            'message' => __('Capacidad disponible.', 'rinac'),
        ];
    }

    /**
     * Calcula ocupación actual (equiv) sumando reservas existentes que solapen el rango.
     */
    private static function calculate_occupied_equiv(int $product_id, ?int $slot_id, ?int $turno_id, int $start_ts, int $end_ts): float {
        $meta_query = [
            [
                'key' => self::META_BOOKING_PRODUCT_ID,
                'value' => $product_id,
                'compare' => '=',
                'type' => 'NUMERIC',
            ],
        ];

        if ($slot_id !== null) {
            $meta_query[] = [
                'key' => self::META_BOOKING_SLOT_ID,
                'value' => $slot_id,
                'compare' => '=',
                'type' => 'NUMERIC',
            ];
        }

        if ($turno_id !== null) {
            $meta_query[] = [
                'key' => self::META_BOOKING_TURNO_ID,
                'value' => $turno_id,
                'compare' => '=',
                'type' => 'NUMERIC',
            ];
        }

        $booking_ids = get_posts(
            [
                'post_type' => 'rinac_booking',
                'post_status' => ['publish'],
                'numberposts' => -1,
                'fields' => 'ids',
                'meta_query' => $meta_query,
            ]
        );

        $ocupado = 0.0;
        foreach ($booking_ids as $booking_id) {
            [$booking_start_ts, $booking_end_ts] = self::get_booking_range_ts($booking_id);
            if ($booking_end_ts < $start_ts) {
                continue;
            }
            if ($booking_start_ts > $end_ts) {
                continue;
            }

            $equiv_total = (float) get_post_meta($booking_id, self::META_BOOKING_EQUIV_TOTAL, true);
            if ($equiv_total > 0) {
                $ocupado += $equiv_total;
                continue;
            }

            $counts = get_post_meta($booking_id, self::META_BOOKING_PARTICIPANT_COUNTS, true);
            if (is_string($counts)) {
                $decoded = json_decode($counts, true);
                $counts = is_array($decoded) ? $decoded : [];
            }
            if (is_array($counts) && !empty($counts)) {
                $ocupado += self::calculate_requested_equiv($counts);
            }
        }

        return (float) $ocupado;
    }

    /**
     * Calcula personas equivalentes pedidas a partir de fracciones por tipo.
     *
     * @param array<int,int|float> $participant_counts
     */
    /**
     * Calcula personas equivalentes pedidas a partir de fracciones por tipo.
     *
     * @param array<int,int|float> $participant_counts
     */
    public static function calculate_requested_equiv(array $participant_counts): float {
        $equiv = 0.0;

        foreach ($participant_counts as $participant_type_id => $qty) {
            $type_id = (int) $participant_type_id;
            if ($type_id <= 0) {
                continue;
            }

            $quantity = (float) $qty;
            if ($quantity <= 0) {
                continue;
            }

            $fraction = (float) get_post_meta($type_id, self::META_PARTICIPANT_FRACTION, true);
            if ($fraction <= 0) {
                $fraction = 1.0;
            }

            $equiv += $quantity * $fraction;
        }

        return (float) $equiv;
    }

    private static function get_capacity_max_equiv(?int $slot_id, ?int $turno_id): float {
        if ($slot_id !== null) {
            $max = (float) get_post_meta($slot_id, self::META_SLOT_MAX, true);
        } else {
            $max = (float) get_post_meta($turno_id, self::META_TURNO_MAX, true);
        }

        // Por defecto no permitimos sobre-reserva "infinita".
        return $max > 0 ? $max : 0.0;
    }

    private static function get_min_equiv(int $product_id, ?int $slot_id, ?int $turno_id): ?float {
        if ($slot_id !== null) {
            $min = (float) get_post_meta($slot_id, self::META_SLOT_MIN, true);
        } else {
            $min = (float) get_post_meta($turno_id, self::META_TURNO_MIN, true);
        }

        if ($min > 0) {
            return (float) $min;
        }

        $fallback = (float) get_post_meta($product_id, 'rinac_booking_min_equiv', true);
        return $fallback > 0 ? (float) $fallback : null;
    }

    private static function get_max_equiv(int $product_id, ?int $slot_id, ?int $turno_id): ?float {
        if ($slot_id !== null) {
            $max = (float) get_post_meta($slot_id, self::META_SLOT_RESERVATION_MAX, true);
        } else {
            $max = (float) get_post_meta($turno_id, self::META_TURNO_RESERVATION_MAX, true);
        }

        if ($max > 0) {
            return (float) $max;
        }

        $fallback = (float) get_post_meta($product_id, 'rinac_booking_max_equiv', true);
        return $fallback > 0 ? (float) $fallback : null;
    }

    /**
     * Obtiene rango temporal de una reserva existente.
     *
     * @return array{0:int,1:int} [start_ts, end_ts]
     */
    private static function get_booking_range_ts(int $booking_id): array {
        $date = (string) get_post_meta($booking_id, self::META_BOOKING_DATE, true);
        $start_date = (string) get_post_meta($booking_id, self::META_BOOKING_START, true);
        $end_date = (string) get_post_meta($booking_id, self::META_BOOKING_END, true);

        if ($start_date !== '') {
            $start_ts = self::parse_to_timestamp($start_date);
            $end_ts = $end_date !== '' ? self::parse_to_timestamp($end_date) : $start_ts;
            return [$start_ts, $end_ts];
        }

        if ($date !== '') {
            $ts = self::parse_to_timestamp($date);
            return [$ts, $ts];
        }

        // Fallback: si no hay fechas, no cuenta para el overlap.
        return [PHP_INT_MAX, PHP_INT_MIN];
    }

    private static function parse_to_timestamp(string $value): int {
        $value = trim($value);
        if ($value === '') {
            throw new \InvalidArgumentException(__('Fecha vacía.', 'rinac'));
        }

        $ts = strtotime($value);
        if ($ts === false) {
            throw new \InvalidArgumentException(__('Formato de fecha inválido.', 'rinac'));
        }

        return (int) $ts;
    }

    private static function cache_key(int $product_id, ?int $slot_id, ?int $turno_id, int $start_ts, int $end_ts): string {
        $scope = 'product=' . $product_id;
        if ($slot_id !== null) {
            $scope .= '&slot=' . $slot_id;
        }
        if ($turno_id !== null) {
            $scope .= '&turno=' . $turno_id;
        }

        // Reducimos granularidad de cache a día si vienen timestamps con hora.
        $day_start = date('Y-m-d', $start_ts);
        $day_end = date('Y-m-d', $end_ts);

        return 'rinac_avail_' . md5($scope . '&from=' . $day_start . '&to=' . $day_end);
    }
}

