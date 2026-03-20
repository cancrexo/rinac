<?php

namespace rinac\Ajax;

use WP_Error;
use rinac\Calendar\AvailabilityManager;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Handler centralizado para TODA la lógica AJAX del plugin.
 *
 * Requisitos:
 * - Un único action: `rinac_ajax`
 * - Manejo por endpoint mediante el parámetro `endpoint`
 * - Nonce obligatorio
 * - Sanitización + checks de acceso
 */
final class AjaxHandler {
    private const AJAX_ACTION = 'rinac_ajax';
    private const NONCE_ACTION = 'rinac_ajax_nonce';
    private const NONCE_PARAM = 'nonce';

    public static function register(): void {
        add_action('wp_ajax_' . self::AJAX_ACTION, [__CLASS__, 'handle']);
        add_action('wp_ajax_nopriv_' . self::AJAX_ACTION, [__CLASS__, 'handle']);
    }

    /**
     * Punto único de entrada para todos los endpoints.
     */
    public static function handle(): void {
        nocache_headers();
        header('Content-Type: application/json; charset=' . get_bloginfo('charset'));

        $post = self::post();

        $response = [
            'success' => false,
            'data' => null,
            'error' => [
                'code' => 'unknown_error',
                'message' => __('Error desconocido', 'rinac'),
            ],
        ];

        try {
            // Nonce.
            self::check_nonce_or_fail($post);

            // Parámetros base.
            $endpoint = isset($post['endpoint']) ? sanitize_key((string) $post['endpoint']) : '';
            if ($endpoint === '') {
                throw new \InvalidArgumentException(__('Falta el parámetro `endpoint`.', 'rinac'));
            }

            // Sanitización/capability check:
            // - Para usuarios autenticados pedimos una capability mínima.
            // - Para invitados permitimos acceso si el nonce es válido.
            self::assert_access();

            $data = match ($endpoint) {
                'availability' => self::endpoint_availability($post),
                'live_price' => self::endpoint_live_price($post),
                'validate_capacity' => self::endpoint_validate_capacity($post),
                default => throw new \InvalidArgumentException(__('Endpoint no reconocido.', 'rinac')),
            };

            $response['success'] = true;
            $response['data'] = $data;
            unset($response['error']);

            wp_send_json($response);
        } catch (\InvalidArgumentException $e) {
            $response['error']['code'] = 'bad_request';
            $response['error']['message'] = $e->getMessage();
            wp_send_json($response, 400);
        } catch (WP_Error $e) {
            $response['error']['code'] = 'wp_error';
            $response['error']['message'] = $e->get_error_message();
            wp_send_json($response, 500);
        } catch (\Throwable $e) {
            $response['error']['code'] = 'server_error';
            $response['error']['message'] = $e->getMessage() ?: __('Error interno del servidor.', 'rinac');
            wp_send_json($response, 500);
        }
    }

    private static function check_nonce_or_fail(array $post): void {
        if (!isset($post[self::NONCE_PARAM])) {
            throw new \InvalidArgumentException(__('Falta el nonce.', 'rinac'));
        }

        $nonce = sanitize_text_field((string) $post[self::NONCE_PARAM]);
        if (!wp_verify_nonce($nonce, self::NONCE_ACTION)) {
            throw new \InvalidArgumentException(__('Nonce inválido.', 'rinac'));
        }
    }

    private static function assert_access(): void {
        // Capability check para usuarios autenticados.
        if (is_user_logged_in()) {
            if (!current_user_can('read')) {
                throw new \InvalidArgumentException(__('No autorizado.', 'rinac'));
            }
        }
    }

    private static function read_required_int(string $key, array $post): int {
        if (!isset($post[$key])) {
            throw new \InvalidArgumentException(sprintf(__('Falta el parámetro `%s`.', 'rinac'), esc_html($key)));
        }

        $raw = $post[$key];
        if (!is_scalar($raw) || !is_numeric((string) $raw)) {
            throw new \InvalidArgumentException(sprintf(__('El parámetro `%s` no es válido.', 'rinac'), esc_html($key)));
        }

        return (int) $raw;
    }

    private static function endpoint_availability(array $post): array {
        $product_id = self::read_required_int('product_id', $post);

        $start_date = isset($post['start_date']) ? (string) $post['start_date'] : '';
        if ($start_date === '' && isset($post['date'])) {
            $start_date = (string) $post['date'];
        }

        $end_date = isset($post['end_date']) ? (string) $post['end_date'] : null;
        if ($end_date !== null && trim($end_date) === '') {
            $end_date = null;
        }

        $slot_id = isset($post['slot_id']) && is_numeric((string) $post['slot_id']) ? (int) $post['slot_id'] : null;
        $turno_id = isset($post['turno_id']) && is_numeric((string) $post['turno_id']) ? (int) $post['turno_id'] : null;

        if ($start_date === '') {
            throw new \InvalidArgumentException(__('Falta `start_date` o `date`.', 'rinac'));
        }

        $participant_counts_raw = '';
        if (isset($post['participant_counts'])) {
            $participant_counts_raw = (string) $post['participant_counts'];
        } elseif (isset($post['participants'])) {
            $participant_counts_raw = (string) $post['participants'];
        }

        $participant_counts = [];
        if ($participant_counts_raw !== '') {
            $decoded = json_decode($participant_counts_raw, true);
            if (is_array($decoded)) {
                $participant_counts = $decoded;
            }
        }

        $context = AvailabilityManager::get_capacity_context($product_id, $slot_id, $turno_id, $start_date, $end_date);

        return [
            'capacidad_max_equiv' => $context['capacidad_max_equiv'],
            'ocupado_actual_equiv' => $context['ocupado_actual_equiv'],
            'capacidad_restante_equiv' => $context['capacidad_restante_equiv'],
            'min_equiv' => $context['min_equiv'],
            'max_equiv' => $context['max_equiv'],
            // Para que el frontend pueda previsualizar sin recalcular.
            'equiv_solicitado_estimado' => AvailabilityManager::calculate_requested_equiv($participant_counts),
        ];
    }

    private static function endpoint_live_price(array $post): array {
        // Fase 2: endpoint de ejemplo.
        // Parámetros esperados:
        // - product_id
        // - participant_counts (JSON) o participantes individuales
        // - recurso_ids (JSON)

        $product_id = self::read_required_int('product_id', $post);

        $participant_counts_raw = isset($post['participant_counts']) ? (string) $post['participant_counts'] : '';
        $resource_ids_raw = isset($post['resource_ids']) ? (string) $post['resource_ids'] : '';

        $participant_counts = [];
        if ($participant_counts_raw !== '') {
            $decoded = \json_decode($participant_counts_raw, true);
            if (is_array($decoded)) {
                $participant_counts = $decoded;
            }
        }

        $resource_ids = [];
        if ($resource_ids_raw !== '') {
            $decoded = \json_decode($resource_ids_raw, true);
            if (is_array($decoded)) {
                $resource_ids = $decoded;
            }
        }

        return [
            'precio_total' => null,
            'debug' => [
                'product_id' => $product_id,
                'participant_counts' => $participant_counts,
                'resource_ids' => $resource_ids,
            ],
        ];
    }

    private static function endpoint_validate_capacity(array $post): array {
        $product_id = self::read_required_int('product_id', $post);

        $start_date = isset($post['start_date']) ? (string) $post['start_date'] : '';
        if ($start_date === '' && isset($post['date'])) {
            $start_date = (string) $post['date'];
        }
        $end_date = isset($post['end_date']) ? (string) $post['end_date'] : null;
        if ($end_date !== null && trim($end_date) === '') {
            $end_date = null;
        }

        $slot_id = isset($post['slot_id']) && is_numeric((string) $post['slot_id']) ? (int) $post['slot_id'] : null;
        $turno_id = isset($post['turno_id']) && is_numeric((string) $post['turno_id']) ? (int) $post['turno_id'] : null;

        if ($start_date === '') {
            throw new \InvalidArgumentException(__('Falta `start_date` o `date`.', 'rinac'));
        }

        $participant_counts_raw = '';
        if (isset($post['participant_counts'])) {
            $participant_counts_raw = (string) $post['participant_counts'];
        } elseif (isset($post['participants'])) {
            $participant_counts_raw = (string) $post['participants'];
        }

        $participant_counts = [];
        if ($participant_counts_raw !== '') {
            $decoded = json_decode($participant_counts_raw, true);
            if (is_array($decoded)) {
                $participant_counts = $decoded;
            }
        }

        return AvailabilityManager::validate_capacity($product_id, $slot_id, $turno_id, $start_date, $end_date, $participant_counts);
    }

    /**
     * Obtiene la entrada POST sin slashes.
     *
     * Nota: centralizamos `$_POST` para minimizar warnings del IDE.
     *
     * @return array<string, mixed>
     */
    private static function post(): array {
        $raw_post = $GLOBALS['_POST'] ?? [];
        if (!is_array($raw_post)) {
            return [];
        }

        return wp_unslash($raw_post);
    }

    public static function get_nonce_value(): string {
        return wp_create_nonce(self::NONCE_ACTION);
    }
}

