<?php

namespace rinac\Admin\MetaBoxes;

use rinac\Core\Constants;
use WP_Post;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Meta boxes nativas para configurar el producto WooCommerce "Reserva" (product_type rinac_reserva).
 *
 * NOTA: por simplicidad, los precios y fracciones de participantes se toman del CPT
 * `rinac_participant_type` (cuando exista su gestión en futuras fases).
 */
final class RinacReservationProductMetaBoxes {
    private const NONCE_ACTION = 'rinac_reserva_meta_save';
    private const NONCE_NAME = 'rinac_reserva_meta_nonce';

    private const META_RESERVATION_MODE = 'rinac_reservation_mode';
    private const META_SLOT_IDS = 'rinac_slot_ids';
    private const META_TURNO_IDS = 'rinac_turno_ids';
    private const META_PARTICIPANT_TYPE_IDS = 'rinac_participant_type_ids';
    private const META_RESOURCE_IDS = 'rinac_resource_ids';

    private const META_PRICE_BASE = 'rinac_price_base';
    private const META_BOOKING_MIN_EQUIV = 'rinac_booking_min_equiv';
    private const META_BOOKING_MAX_EQUIV = 'rinac_booking_max_equiv';

    private const META_PAYMENT_MODE = 'rinac_payment_mode';
    private const META_DEPOSIT_PERCENT = 'rinac_deposit_percent';

    /**
     * Hook de registro.
     */
    public static function register(): void {
        add_action('add_meta_boxes', [__CLASS__, 'register_meta_boxes']);
        add_action('save_post_product', [__CLASS__, 'save'], 10, 3);
    }

    /**
     * Añade el meta box solo cuando el producto es de tipo `rinac_reserva`.
     */
    public static function register_meta_boxes(): void {
        global $post;

        if (!$post instanceof WP_Post) {
            return;
        }

        if (!function_exists('wc_get_product')) {
            return;
        }

        /** @noinspection PhpUndefinedFunctionInspection */
        $product = \wc_get_product($post->ID);
        if (!$product) {
            return;
        }

        if ($product->get_type() !== 'rinac_reserva') {
            return;
        }

        add_meta_box(
            'rinac_reserva_settings',
            __('RINAC - Configuración de la reserva', Constants::TEXT_DOMAIN),
            [__CLASS__, 'render_meta_box'],
            'product',
            'normal',
            'high'
        );
    }

    /**
     * Render del meta box.
     *
     * @param WP_Post $post
     */
    public static function render_meta_box(WP_Post $post): void {
        // Valores actuales.
        $reservation_mode = (string) get_post_meta($post->ID, self::META_RESERVATION_MODE, true);
        if ($reservation_mode === '') {
            $reservation_mode = 'single_date';
        }

        $slot_ids = self::ensure_int_array(get_post_meta($post->ID, self::META_SLOT_IDS, true));
        $turno_ids = self::ensure_int_array(get_post_meta($post->ID, self::META_TURNO_IDS, true));
        $participant_type_ids = self::ensure_int_array(get_post_meta($post->ID, self::META_PARTICIPANT_TYPE_IDS, true));
        $resource_ids = self::ensure_int_array(get_post_meta($post->ID, self::META_RESOURCE_IDS, true));

        $price_base = (float) get_post_meta($post->ID, self::META_PRICE_BASE, true);
        $booking_min_equiv = (float) get_post_meta($post->ID, self::META_BOOKING_MIN_EQUIV, true);
        $booking_max_equiv = (float) get_post_meta($post->ID, self::META_BOOKING_MAX_EQUIV, true);

        $payment_mode = (string) get_post_meta($post->ID, self::META_PAYMENT_MODE, true);
        if ($payment_mode === '') {
            $payment_mode = 'full';
        }

        $deposit_percent = (float) get_post_meta($post->ID, self::META_DEPOSIT_PERCENT, true);

        wp_nonce_field(self::NONCE_ACTION, self::NONCE_NAME);

        $reservation_modes = [
            'single_date' => __('Fecha única', Constants::TEXT_DOMAIN),
            'date_range' => __('Rango de fechas', Constants::TEXT_DOMAIN),
            'date_time' => __('Fecha + hora', Constants::TEXT_DOMAIN),
            'date_range_with_slots' => __('Rango de fechas + slot', Constants::TEXT_DOMAIN),
        ];

        $slots = self::get_posts_for_meta('rinac_slot');
        $turnos = self::get_posts_for_meta('rinac_turno');
        $participant_types = self::get_posts_for_meta('rinac_participant_type');
        $resources = self::get_posts_for_meta('rinac_resource');
        ?>
        <div class="rinac-meta-box">
            <p>
                <label for="rinac_reservation_mode"><strong><?php echo esc_html(__('Tipo de reserva', Constants::TEXT_DOMAIN)); ?></strong></label>
                <select id="rinac_reservation_mode" name="rinac_reservation_mode">
                    <?php foreach ($reservation_modes as $value => $label) : ?>
                        <option value="<?php echo esc_attr($value); ?>" <?php selected($reservation_mode, $value); ?>>
                            <?php echo esc_html($label); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </p>

            <hr />

            <h4><?php echo esc_html(__('Slots asociados', Constants::TEXT_DOMAIN)); ?></h4>
            <?php if (empty($slots)) : ?>
                <p><?php echo esc_html(__('No hay slots creados.', Constants::TEXT_DOMAIN)); ?></p>
            <?php else : ?>
                <div>
                    <?php foreach ($slots as $slot) : ?>
                        <label style="display:block;margin:4px 0;">
                            <input
                                type="checkbox"
                                name="rinac_slot_ids[]"
                                value="<?php echo esc_attr($slot->ID); ?>"
                                <?php checked(in_array((int) $slot->ID, $slot_ids, true), true); ?>
                            />
                            <?php echo esc_html($slot->post_title); ?>
                        </label>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <hr />

            <h4><?php echo esc_html(__('Turnos asociados (restaurantes)', Constants::TEXT_DOMAIN)); ?></h4>
            <?php if (empty($turnos)) : ?>
                <p><?php echo esc_html(__('No hay turnos creados.', Constants::TEXT_DOMAIN)); ?></p>
            <?php else : ?>
                <div>
                    <?php foreach ($turnos as $turno) : ?>
                        <label style="display:block;margin:4px 0;">
                            <input
                                type="checkbox"
                                name="rinac_turno_ids[]"
                                value="<?php echo esc_attr($turno->ID); ?>"
                                <?php checked(in_array((int) $turno->ID, $turno_ids, true), true); ?>
                            />
                            <?php echo esc_html($turno->post_title); ?>
                        </label>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <hr />

            <h4><?php echo esc_html(__('Tipos de participantes permitidos', Constants::TEXT_DOMAIN)); ?></h4>
            <?php if (empty($participant_types)) : ?>
                <p><?php echo esc_html(__('No hay tipos de participantes creados.', Constants::TEXT_DOMAIN)); ?></p>
            <?php else : ?>
                <div>
                    <?php foreach ($participant_types as $pt) : ?>
                        <label style="display:block;margin:4px 0;">
                            <input
                                type="checkbox"
                                name="rinac_participant_type_ids[]"
                                value="<?php echo esc_attr($pt->ID); ?>"
                                <?php checked(in_array((int) $pt->ID, $participant_type_ids, true), true); ?>
                            />
                            <?php echo esc_html($pt->post_title); ?>
                        </label>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <hr />

            <h4><?php echo esc_html(__('Recursos extras (opcionales)', Constants::TEXT_DOMAIN)); ?></h4>
            <?php if (empty($resources)) : ?>
                <p><?php echo esc_html(__('No hay recursos creados.', Constants::TEXT_DOMAIN)); ?></p>
            <?php else : ?>
                <div>
                    <?php foreach ($resources as $resource) : ?>
                        <label style="display:block;margin:4px 0;">
                            <input
                                type="checkbox"
                                name="rinac_resource_ids[]"
                                value="<?php echo esc_attr($resource->ID); ?>"
                                <?php checked(in_array((int) $resource->ID, $resource_ids, true), true); ?>
                            />
                            <?php echo esc_html($resource->post_title); ?>
                        </label>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <hr />

            <h4><?php echo esc_html(__('Pricing y capacidad por reserva', Constants::TEXT_DOMAIN)); ?></h4>

            <p>
                <label for="rinac_price_base"><?php echo esc_html(__('Precio base', Constants::TEXT_DOMAIN)); ?></label>
                <input
                    type="number"
                    step="0.01"
                    min="0"
                    id="rinac_price_base"
                    name="rinac_price_base"
                    value="<?php echo esc_attr($price_base); ?>"
                />
            </p>

            <p>
                <label for="rinac_booking_min_equiv"><?php echo esc_html(__('Mínimo por reserva (personas equivalentes)', Constants::TEXT_DOMAIN)); ?></label>
                <input
                    type="number"
                    step="0.5"
                    min="0"
                    id="rinac_booking_min_equiv"
                    name="rinac_booking_min_equiv"
                    value="<?php echo esc_attr($booking_min_equiv); ?>"
                />
            </p>

            <p>
                <label for="rinac_booking_max_equiv"><?php echo esc_html(__('Máximo por reserva (personas equivalentes)', Constants::TEXT_DOMAIN)); ?></label>
                <input
                    type="number"
                    step="0.5"
                    min="0"
                    id="rinac_booking_max_equiv"
                    name="rinac_booking_max_equiv"
                    value="<?php echo esc_attr($booking_max_equiv); ?>"
                />
            </p>

            <hr />

            <h4><?php echo esc_html(__('Pago', Constants::TEXT_DOMAIN)); ?></h4>

            <p>
                <label for="rinac_payment_mode"><strong><?php echo esc_html(__('Tipo de pago', Constants::TEXT_DOMAIN)); ?></strong></label>
                <select id="rinac_payment_mode" name="rinac_payment_mode">
                    <option value="full" <?php selected($payment_mode, 'full'); ?>>
                        <?php echo esc_html(__('100% al reservar', Constants::TEXT_DOMAIN)); ?>
                    </option>
                    <option value="deposit" <?php selected($payment_mode, 'deposit'); ?>>
                        <?php echo esc_html(__('Depósito % y resto al check-in', Constants::TEXT_DOMAIN)); ?>
                    </option>
                </select>
            </p>

            <p>
                <label for="rinac_deposit_percent"><?php echo esc_html(__('Depósito (%)', Constants::TEXT_DOMAIN)); ?></label>
                <input
                    type="number"
                    step="0.01"
                    min="0"
                    max="100"
                    id="rinac_deposit_percent"
                    name="rinac_deposit_percent"
                    value="<?php echo esc_attr($deposit_percent); ?>"
                />
            </p>
        </div>
        <?php
    }

    /**
     * Guardado del meta box.
     */
    public static function save(int $post_id, WP_Post $post, bool $update): void {
        /** @noinspection PhpUndefinedVariableInspection */
        $post_data = wp_unslash($_POST);

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if ($post->post_type !== 'product') {
            return;
        }

        if (!isset($post_data[self::NONCE_NAME])) {
            return;
        }

        $nonce = sanitize_text_field((string) $post_data[self::NONCE_NAME]);
        if (!wp_verify_nonce($nonce, self::NONCE_ACTION)) {
            return;
        }

        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        if (!function_exists('wc_get_product')) {
            return;
        }

        /** @noinspection PhpUndefinedFunctionInspection */
        $product = \wc_get_product($post_id);
        if (!$product || $product->get_type() !== 'rinac_reserva') {
            return;
        }

        $reservation_mode = isset($post_data[self::META_RESERVATION_MODE]) ? sanitize_key((string) $post_data[self::META_RESERVATION_MODE]) : 'single_date';
        if (!in_array($reservation_mode, ['single_date', 'date_range', 'date_time', 'date_range_with_slots'], true)) {
            $reservation_mode = 'single_date';
        }
        update_post_meta($post_id, self::META_RESERVATION_MODE, $reservation_mode);

        $slot_ids = isset($post_data['rinac_slot_ids']) ? self::ensure_int_array($post_data['rinac_slot_ids']) : [];
        update_post_meta($post_id, self::META_SLOT_IDS, $slot_ids);

        $turno_ids = isset($post_data['rinac_turno_ids']) ? self::ensure_int_array($post_data['rinac_turno_ids']) : [];
        update_post_meta($post_id, self::META_TURNO_IDS, $turno_ids);

        $participant_type_ids = isset($post_data['rinac_participant_type_ids']) ? self::ensure_int_array($post_data['rinac_participant_type_ids']) : [];
        update_post_meta($post_id, self::META_PARTICIPANT_TYPE_IDS, $participant_type_ids);

        $resource_ids = isset($post_data['rinac_resource_ids']) ? self::ensure_int_array($post_data['rinac_resource_ids']) : [];
        update_post_meta($post_id, self::META_RESOURCE_IDS, $resource_ids);

        $price_base = isset($post_data['rinac_price_base']) ? (float) $post_data['rinac_price_base'] : 0.0;
        if ($price_base < 0) {
            $price_base = 0.0;
        }
        update_post_meta($post_id, self::META_PRICE_BASE, $price_base);

        $booking_min_equiv = isset($post_data['rinac_booking_min_equiv']) ? (float) $post_data['rinac_booking_min_equiv'] : 0.0;
        if ($booking_min_equiv < 0) {
            $booking_min_equiv = 0.0;
        }
        update_post_meta($post_id, self::META_BOOKING_MIN_EQUIV, $booking_min_equiv);

        $booking_max_equiv = isset($post_data['rinac_booking_max_equiv']) ? (float) $post_data['rinac_booking_max_equiv'] : 0.0;
        if ($booking_max_equiv < 0) {
            $booking_max_equiv = 0.0;
        }
        update_post_meta($post_id, self::META_BOOKING_MAX_EQUIV, $booking_max_equiv);

        $payment_mode = isset($post_data['rinac_payment_mode']) ? sanitize_key((string) $post_data['rinac_payment_mode']) : 'full';
        if (!in_array($payment_mode, ['full', 'deposit'], true)) {
            $payment_mode = 'full';
        }
        update_post_meta($post_id, self::META_PAYMENT_MODE, $payment_mode);

        if ($payment_mode === 'deposit') {
            $deposit_percent = isset($post_data['rinac_deposit_percent']) ? (float) $post_data['rinac_deposit_percent'] : 0.0;
            if ($deposit_percent < 0) {
                $deposit_percent = 0.0;
            }
            if ($deposit_percent > 100) {
                $deposit_percent = 100.0;
            }
            update_post_meta($post_id, self::META_DEPOSIT_PERCENT, $deposit_percent);
        } else {
            delete_post_meta($post_id, self::META_DEPOSIT_PERCENT);
        }
    }

    /**
     * Devuelve un array de IDs enteros.
     *
     * @param mixed $value
     *
     * @return int[]
     */
    private static function ensure_int_array($value): array {
        if (is_string($value)) {
            $value = json_decode($value, true) ?: [];
        }

        if (!is_array($value)) {
            return [];
        }

        $out = [];
        foreach ($value as $item) {
            $i = (int) $item;
            if ($i > 0) {
                $out[] = $i;
            }
        }

        return array_values(array_unique($out));
    }

    /**
     * Obtiene posts del CPT para listarlos en el meta box.
     *
     * @param string $post_type
     *
     * @return WP_Post[]
     */
    private static function get_posts_for_meta(string $post_type): array {
        return get_posts(
            [
                'post_type' => $post_type,
                'numberposts' => -1,
                'orderby' => 'title',
                'order' => 'ASC',
                'post_status' => ['publish', 'draft', 'pending', 'private'],
            ]
        );
    }
}

