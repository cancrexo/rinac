<?php

declare(strict_types=1);

namespace Rinac\Ajax;

use Throwable;

final class AjaxRequests
{
    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->init_hooks();
    }

    /**
     * Registrar endpoints AJAX (centralizado).
     */
    private function init_hooks(): void
    {
        // Endpoints unificados (futuro): lectura / escritura.
        \add_action('wp_ajax_rinac_ajax_read', array($this, 'handle_read'));
        \add_action('wp_ajax_nopriv_rinac_ajax_read', array($this, 'handle_read'));

        \add_action('wp_ajax_rinac_ajax_write', array($this, 'handle_write'));
        \add_action('wp_ajax_nopriv_rinac_ajax_write', array($this, 'handle_write'));

        // Endpoints legacy (compatibilidad): se siguen atendiendo aquí.
        \add_action('wp_ajax_rinac_get_rango_horas', array($this, 'legacy_get_rango_horas'));
        \add_action('wp_ajax_rinac_save_rango', array($this, 'legacy_save_rango'));
        \add_action('wp_ajax_rinac_delete_rango', array($this, 'legacy_delete_rango'));
        \add_action('wp_ajax_rinac_get_rango_details', array($this, 'legacy_get_rango_details'));

        \add_action('wp_ajax_rinac_save_calendar_data', array($this, 'legacy_save_calendar_data'));
        \add_action('wp_ajax_rinac_get_calendar_data', array($this, 'legacy_get_calendar_data'));
        \add_action('wp_ajax_rinac_bulk_calendar_operation', array($this, 'legacy_bulk_calendar_operation'));

        \add_action('wp_ajax_rinac_get_horarios', array($this, 'legacy_get_horarios'));
        \add_action('wp_ajax_nopriv_rinac_get_horarios', array($this, 'legacy_get_horarios'));

        \add_action('wp_ajax_rinac_check_availability', array($this, 'legacy_check_availability'));
        \add_action('wp_ajax_nopriv_rinac_check_availability', array($this, 'legacy_check_availability'));

        \add_action('wp_ajax_rinac_render_quick_booking_modal', array($this, 'legacy_render_quick_booking_modal'));
        \add_action('wp_ajax_nopriv_rinac_render_quick_booking_modal', array($this, 'legacy_render_quick_booking_modal'));

        \add_action('wp_ajax_rinac_render_booking_details_modal', array($this, 'legacy_render_booking_details_modal'));
        \add_action('wp_ajax_nopriv_rinac_render_booking_details_modal', array($this, 'legacy_render_booking_details_modal'));

        // Endpoint usado por frontend.js cuando recarga fechas (aunque normalmente llegan ya desde PHP).
        \add_action('wp_ajax_rinac_get_available_dates', array($this, 'legacy_get_available_dates'));
        \add_action('wp_ajax_nopriv_rinac_get_available_dates', array($this, 'legacy_get_available_dates'));
    }

    /**
     * Endpoint unificado de lectura.
     */
    public function handle_read(): void
    {
        $this->dispatch('read');
    }

    /**
     * Endpoint unificado de escritura.
     */
    public function handle_write(): void
    {
        $this->dispatch('write');
    }

    /**
     * Dispatcher central (read/write) con try/catch.
     *
     * Request esperado:
     * - op: string (operación)
     * - payload: array (datos de entrada)
     * - nonce: string
     */
    private function dispatch(string $mode): void
    {
        try {
            $post = (isset($GLOBALS['_POST']) && \is_array($GLOBALS['_POST'])) ? $GLOBALS['_POST'] : array();

            $op = isset($post['op']) ? (string) $post['op'] : '';
            $payload = isset($post['payload']) && \is_array($post['payload']) ? $post['payload'] : array();

            if ($op === '') {
                throw new \RuntimeException(__('Falta el parámetro "op".', 'rinac'));
            }

            $result = $this->execute_operation($mode, $op, $payload, $post);

            \wp_send_json_success($result);
        } catch (Throwable $e) {
            $this->send_exception($e);
        }
    }

    /**
     * Ejecutar operación (read/write) en función de `op`.
     *
     * @param array $payload Datos de entrada
     * @param array $raw_post POST completo (por si se necesita compatibilidad)
     */
    private function execute_operation(string $mode, string $op, array $payload, array $raw_post)
    {
        // Mapa de operaciones (se puede ir ampliando).
        $ops = array(
            // Admin: rangos horarios
            'get_rango_horas' => array($this, 'op_get_rango_horas'),
            'get_rango_details' => array($this, 'op_get_rango_details'),
            'save_rango' => array($this, 'op_save_rango'),
            'delete_rango' => array($this, 'op_delete_rango'),

            // Admin: calendario producto
            'save_calendar_data' => array($this, 'op_save_calendar_data'),
            'get_calendar_data' => array($this, 'op_get_calendar_data'),
            'bulk_calendar_operation' => array($this, 'op_bulk_calendar_operation'),

            // Frontend: disponibilidad
            'get_horarios' => array($this, 'op_get_horarios'),
            'check_availability' => array($this, 'op_check_availability'),
            'get_available_dates' => array($this, 'op_get_available_dates'),

            // Modales
            'render_quick_booking_modal' => array($this, 'op_render_quick_booking_modal'),
            'render_booking_details_modal' => array($this, 'op_render_booking_details_modal'),

            // Admin.js (si se usa en el futuro)
            'bulk_action' => array($this, 'op_bulk_action'),
        );

        if (!isset($ops[$op]) || !\is_callable($ops[$op])) {
            throw new \RuntimeException(sprintf(__('Operación AJAX no soportada: %s', 'rinac'), $op));
        }

        // Validación base por modo (si en el futuro quieres separar read/write estrictamente).
        if (!\in_array($mode, array('read', 'write'), true)) {
            throw new \RuntimeException(__('Modo AJAX inválido.', 'rinac'));
        }

        /** @var callable $callable */
        $callable = $ops[$op];
        return \call_user_func($callable, $payload, $raw_post);
    }

    /**
     * Respuesta de error estandarizada.
     */
    private function send_exception(Throwable $e): void
    {
        $message = $e->getMessage() !== '' ? $e->getMessage() : __('Ha ocurrido un error.', 'rinac');

        \wp_send_json_error(array(
            'message' => $message,
        ));
    }

    /**
     * Verificar nonce probando múltiples acciones (evita incompatibilidades actuales).
     */
    private function verify_any_nonce(array $raw_post, array $nonce_actions): void
    {
        $nonce = isset($raw_post['nonce']) ? (string) $raw_post['nonce'] : '';
        if ($nonce === '') {
            throw new \RuntimeException(__('Falta el nonce.', 'rinac'));
        }

        foreach ($nonce_actions as $action) {
            if (\wp_verify_nonce($nonce, (string) $action)) {
                return;
            }
        }

        throw new \RuntimeException(__('Nonce inválido.', 'rinac'));
    }

    /**
     * ===== Legacy handlers (mantienen contract actual action=rinac_*) =====
     */
    public function legacy_get_rango_horas(): void
    {
        $this->legacy_dispatch('get_rango_horas', 'read');
    }

    public function legacy_get_rango_details(): void
    {
        $this->legacy_dispatch('get_rango_details', 'read');
    }

    public function legacy_save_rango(): void
    {
        $this->legacy_dispatch('save_rango', 'write');
    }

    public function legacy_delete_rango(): void
    {
        $this->legacy_dispatch('delete_rango', 'write');
    }

    public function legacy_save_calendar_data(): void
    {
        $this->legacy_dispatch('save_calendar_data', 'write');
    }

    public function legacy_get_calendar_data(): void
    {
        $this->legacy_dispatch('get_calendar_data', 'read');
    }

    public function legacy_bulk_calendar_operation(): void
    {
        $this->legacy_dispatch('bulk_calendar_operation', 'write');
    }

    public function legacy_get_horarios(): void
    {
        $this->legacy_dispatch('get_horarios', 'read');
    }

    public function legacy_check_availability(): void
    {
        $this->legacy_dispatch('check_availability', 'read');
    }

    public function legacy_render_quick_booking_modal(): void
    {
        $this->legacy_dispatch('render_quick_booking_modal', 'read');
    }

    public function legacy_render_booking_details_modal(): void
    {
        $this->legacy_dispatch('render_booking_details_modal', 'read');
    }

    public function legacy_get_available_dates(): void
    {
        $this->legacy_dispatch('get_available_dates', 'read');
    }

    private function legacy_dispatch(string $op, string $mode): void
    {
        try {
            $post = (isset($GLOBALS['_POST']) && \is_array($GLOBALS['_POST'])) ? $GLOBALS['_POST'] : array();
            $payload = $post;
            unset($payload['action'], $payload['nonce']);

            $result = $this->execute_operation($mode, $op, (array) $payload, (array) $post);
            \wp_send_json_success($result);
        } catch (Throwable $e) {
            $this->send_exception($e);
        }
    }

    /**
     * ===== Operaciones (callbacks internos) =====
     */
    private function op_get_rango_horas(array $payload, array $raw_post)
    {
        $this->verify_any_nonce($raw_post, array('rinac_admin_nonce'));

        $rango_id = isset($payload['rango_id']) ? (int) $payload['rango_id'] : 0;
        if ($rango_id <= 0) {
            throw new \RuntimeException(__('ID de rango inválido', 'rinac'));
        }

        global $wpdb;
        $table_horas = $wpdb->prefix . 'rinac_horas';

        $horas = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_horas WHERE rango_id = %d ORDER BY orden",
            $rango_id
        ));

        // Legacy esperaba un array directo en response.data
        return $horas;
    }

    private function op_get_rango_details(array $payload, array $raw_post): array
    {
        $this->verify_any_nonce($raw_post, array('rinac_admin_nonce'));

        if (!\current_user_can('manage_woocommerce')) {
            throw new \RuntimeException(__('No tienes permisos para realizar esta acción.', 'rinac'));
        }

        $rango_id = isset($payload['rango_id']) ? (int) $payload['rango_id'] : 0;
        if ($rango_id <= 0) {
            throw new \RuntimeException(__('ID de rango inválido', 'rinac'));
        }

        global $wpdb;
        $table_rangos = $wpdb->prefix . 'rinac_rangos_horarios';
        $table_horas = $wpdb->prefix . 'rinac_horas';

        $rango = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_rangos WHERE id = %d",
            $rango_id
        ), ARRAY_A);

        if (!$rango) {
            throw new \RuntimeException(__('Rango no encontrado', 'rinac'));
        }

        $horas = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_horas WHERE rango_id = %d ORDER BY orden",
            $rango_id
        ));

        return array(
            'rango' => $rango,
            'horas' => $horas,
        );
    }

    private function op_save_rango(array $payload, array $raw_post): array
    {
        $this->verify_any_nonce($raw_post, array('rinac_admin_nonce'));

        if (!\current_user_can('manage_woocommerce')) {
            throw new \RuntimeException(__('No tienes permisos para realizar esta acción.', 'rinac'));
        }

        $nombre = isset($payload['nombre']) ? \sanitize_text_field((string) $payload['nombre']) : '';
        if ($nombre === '') {
            throw new \RuntimeException(__('El nombre es obligatorio.', 'rinac'));
        }

        global $wpdb;
        $table_rangos = $wpdb->prefix . 'rinac_rangos_horarios';

        $result = $wpdb->insert(
            $table_rangos,
            array(
                'nombre' => $nombre,
                'fecha_creacion' => \current_time('mysql'),
            ),
            array('%s', '%s')
        );

        if (!$result) {
            throw new \RuntimeException(__('Error al guardar el rango', 'rinac'));
        }

        return array(
            'message' => __('Rango guardado correctamente', 'rinac'),
            'rango_id' => (int) $wpdb->insert_id,
        );
    }

    private function op_delete_rango(array $payload, array $raw_post): array
    {
        $this->verify_any_nonce($raw_post, array('rinac_admin_nonce'));

        if (!\current_user_can('manage_woocommerce')) {
            throw new \RuntimeException(__('No tienes permisos para realizar esta acción.', 'rinac'));
        }

        $rango_id = isset($payload['rango_id']) ? (int) $payload['rango_id'] : 0;
        if ($rango_id <= 0) {
            throw new \RuntimeException(__('ID de rango inválido', 'rinac'));
        }

        global $wpdb;
        $table_rangos = $wpdb->prefix . 'rinac_rangos_horarios';
        $table_horas = $wpdb->prefix . 'rinac_horas';

        $wpdb->delete($table_horas, array('rango_id' => $rango_id), array('%d'));
        $result = $wpdb->delete($table_rangos, array('id' => $rango_id), array('%d'));

        if (!$result) {
            throw new \RuntimeException(__('Error al eliminar el rango', 'rinac'));
        }

        return array('message' => __('Rango eliminado correctamente', 'rinac'));
    }

    private function op_save_calendar_data(array $payload, array $raw_post): array
    {
        $this->verify_any_nonce($raw_post, array('rinac_admin_nonce'));

        if (!\current_user_can('manage_woocommerce')) {
            throw new \RuntimeException(__('No tienes permisos para realizar esta acción.', 'rinac'));
        }

        $product_id = isset($payload['product_id']) ? (int) $payload['product_id'] : 0;
        $date = isset($payload['date']) ? \sanitize_text_field((string) $payload['date']) : '';
        $disponible = isset($payload['disponible']) ? (int) $payload['disponible'] : 0;

        if ($product_id <= 0 || $date === '') {
            throw new \RuntimeException(__('Datos incompletos', 'rinac'));
        }

        global $wpdb;
        $table_disponibilidad = $wpdb->prefix . 'rinac_disponibilidad';

        $result = $wpdb->query($wpdb->prepare(
            "INSERT INTO $table_disponibilidad (product_id, fecha, disponible)
             VALUES (%d, %s, %d)
             ON DUPLICATE KEY UPDATE disponible = %d",
            $product_id,
            $date,
            $disponible,
            $disponible
        ));

        if ($result === false) {
            throw new \RuntimeException(__('Error al actualizar la disponibilidad.', 'rinac'));
        }

        return array(
            'message' => __('Disponibilidad actualizada correctamente.', 'rinac'),
            'date' => $date,
            'disponible' => $disponible,
        );
    }

    private function op_get_calendar_data(array $payload, array $raw_post): array
    {
        $this->verify_any_nonce($raw_post, array('rinac_admin_nonce'));

        if (!\current_user_can('manage_woocommerce')) {
            throw new \RuntimeException(__('No tienes permisos para realizar esta acción.', 'rinac'));
        }

        $product_id = isset($payload['product_id']) ? (int) $payload['product_id'] : 0;
        $year = isset($payload['year']) ? (int) $payload['year'] : 0;
        $month = isset($payload['month']) ? (int) $payload['month'] : 0;

        if ($product_id <= 0 || $year <= 0 || $month <= 0) {
            throw new \RuntimeException(__('Datos incompletos', 'rinac'));
        }

        // Reutilizamos la clase Calendar para renderizar HTML sin duplicar esa parte.
        if (!\class_exists(\Rinac\Calendar\Calendar::class)) {
            throw new \RuntimeException(__('Calendario no disponible.', 'rinac'));
        }

        $calendar = new \Rinac\Calendar\Calendar(false);
        $calendar_html = $calendar->render_admin_calendar($product_id, $year, $month);

        return array('calendar_html' => $calendar_html);
    }

    private function op_bulk_calendar_operation(array $payload, array $raw_post): array
    {
        $this->verify_any_nonce($raw_post, array('rinac_admin_nonce'));

        if (!\current_user_can('manage_woocommerce')) {
            throw new \RuntimeException(__('No tienes permisos para realizar esta acción.', 'rinac'));
        }

        $product_id = isset($payload['product_id']) ? (int) $payload['product_id'] : 0;
        $operation = isset($payload['operation']) ? \sanitize_text_field((string) $payload['operation']) : '';
        $start_date = isset($payload['start_date']) ? \sanitize_text_field((string) $payload['start_date']) : '';
        $end_date = isset($payload['end_date']) ? \sanitize_text_field((string) $payload['end_date']) : '';

        if ($product_id <= 0 || $operation === '' || $start_date === '' || $end_date === '') {
            throw new \RuntimeException(__('Datos incompletos', 'rinac'));
        }

        if (!\class_exists(\Rinac\Calendar\Calendar::class)) {
            throw new \RuntimeException(__('Calendario no disponible.', 'rinac'));
        }

        $calendar = new \Rinac\Calendar\Calendar(false);

        switch ($operation) {
            case 'enable_range':
                $count = $calendar->set_date_range_availability($product_id, $start_date, $end_date, 1);
                $message = sprintf(__('Se habilitaron %d fechas.', 'rinac'), $count);
                break;
            case 'disable_range':
                $count = $calendar->set_date_range_availability($product_id, $start_date, $end_date, 0);
                $message = sprintf(__('Se deshabilitaron %d fechas.', 'rinac'), $count);
                break;
            case 'enable_weekends':
                $count = $calendar->set_weekends_availability($product_id, $start_date, $end_date, 1);
                $message = sprintf(__('Se habilitaron %d fines de semana.', 'rinac'), $count);
                break;
            case 'disable_weekends':
                $count = $calendar->set_weekends_availability($product_id, $start_date, $end_date, 0);
                $message = sprintf(__('Se deshabilitaron %d fines de semana.', 'rinac'), $count);
                break;
            default:
                throw new \RuntimeException(__('Operación no válida.', 'rinac'));
        }

        return array('message' => $message);
    }

    private function op_get_horarios(array $payload, array $raw_post)
    {
        // Hay dos nonces conviviendo en el plugin: rinac_nonce y rinac_frontend_nonce.
        $this->verify_any_nonce($raw_post, array('rinac_nonce', 'rinac_frontend_nonce'));

        $product_id = isset($payload['product_id']) ? (int) $payload['product_id'] : 0;
        $fecha = isset($payload['fecha']) ? \sanitize_text_field((string) $payload['fecha']) : '';

        if ($product_id <= 0 || $fecha === '') {
            throw new \RuntimeException(__('Datos incompletos', 'rinac'));
        }

        global $wpdb;
        $table_horas = $wpdb->prefix . 'rinac_producto_horas';

        $horarios = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_horas WHERE product_id = %d ORDER BY orden",
            $product_id
        ));

        $available_times = array();
        foreach ($horarios as $horario) {
            $reservadas = $this->get_reserved_persons($product_id, $fecha, (string) $horario->hora);
            $disponibles = (int) $horario->capacidad - $reservadas;

            if ($disponibles > 0) {
                $available_times[] = array(
                    'hora' => (string) $horario->hora,
                    'hora_formatted' => \date('H:i', \strtotime((string) $horario->hora)),
                    'capacidad' => (int) $horario->capacidad,
                    'disponibles' => $disponibles,
                );
            }
        }

        // Legacy esperaba un array directo en response.data
        return $available_times;
    }

    private function op_check_availability(array $payload, array $raw_post): array
    {
        $this->verify_any_nonce($raw_post, array('rinac_frontend_nonce', 'rinac_nonce'));

        $product_id = isset($payload['product_id']) ? (int) $payload['product_id'] : 0;
        $fecha = isset($payload['fecha']) ? \sanitize_text_field((string) $payload['fecha']) : '';
        $horario = isset($payload['horario']) ? \sanitize_text_field((string) $payload['horario']) : '';
        $personas = isset($payload['personas']) ? (int) $payload['personas'] : 0;

        if ($product_id <= 0 || $fecha === '' || $horario === '' || $personas <= 0) {
            throw new \RuntimeException(__('Datos incompletos', 'rinac'));
        }

        $max_personas = $this->get_max_personas_for_slot($product_id, $horario);
        $personas_reservadas = $this->get_reserved_persons($product_id, $fecha, $horario);
        $disponibles = $max_personas - $personas_reservadas;

        return array(
            'available' => $disponibles >= $personas,
            'max_personas' => $max_personas,
            'personas_reservadas' => $personas_reservadas,
            'disponibles' => $disponibles,
        );
    }

    private function op_get_available_dates(array $payload, array $raw_post)
    {
        $this->verify_any_nonce($raw_post, array('rinac_frontend_nonce', 'rinac_nonce'));

        $product_id = isset($payload['product_id']) ? (int) $payload['product_id'] : 0;
        if ($product_id <= 0) {
            throw new \RuntimeException(__('ID de producto inválido', 'rinac'));
        }

        global $wpdb;
        $table_disponibilidad = $wpdb->prefix . 'rinac_disponibilidad';

        $fechas_disponibles = $wpdb->get_col($wpdb->prepare(
            "SELECT fecha FROM $table_disponibilidad
             WHERE product_id = %d AND disponible = 1 AND fecha >= CURDATE()
             ORDER BY fecha",
            $product_id
        ));

        // Legacy esperaba un array directo en response.data
        return $fechas_disponibles;
    }

    private function op_render_quick_booking_modal(array $payload, array $raw_post): array
    {
        $this->verify_any_nonce($raw_post, array('rinac_nonce'));

        $product_id = isset($payload['product_id']) ? (int) $payload['product_id'] : 0;
        if ($product_id <= 0) {
            throw new \RuntimeException(__('ID de producto inválido', 'rinac'));
        }

        $template_data = array(
            'product_id' => $product_id,
            'horarios' => $this->get_product_horarios($product_id),
            'fechas_disponibles' => $this->get_available_dates_for_product($product_id),
            'strings' => array(
                'modal_title' => __('Reserva Rápida', 'rinac'),
                'select_date' => __('Seleccionar fecha', 'rinac'),
                'select_time' => __('Seleccionar horario', 'rinac'),
                'persons' => __('Personas', 'rinac'),
                'book_now' => __('Reservar Ahora', 'rinac'),
                'cancel' => __('Cancelar', 'rinac'),
            ),
        );

        $modal_html = \Rinac\Template\TemplateHelper::get_template('modals/quick-booking-modal.php', $template_data);

        return array('html' => $modal_html);
    }

    private function op_render_booking_details_modal(array $payload, array $raw_post): array
    {
        $this->verify_any_nonce($raw_post, array('rinac_nonce'));

        $booking_id = isset($payload['booking_id']) ? (int) $payload['booking_id'] : 0;
        if ($booking_id <= 0) {
            throw new \RuntimeException(__('ID de reserva inválido', 'rinac'));
        }

        global $wpdb;
        $table_reservas = $wpdb->prefix . 'rinac_reservas';

        $booking_data = $wpdb->get_row($wpdb->prepare(
            "SELECT r.*, p.post_title as producto_nombre
             FROM $table_reservas r
             LEFT JOIN {$wpdb->posts} p ON r.product_id = p.ID
             WHERE r.id = %d",
            $booking_id
        ));

        if (!$booking_data) {
            throw new \RuntimeException(__('Reserva no encontrada', 'rinac'));
        }

        $template_data = array(
            'booking' => $booking_data,
            'strings' => array(
                'modal_title' => __('Detalles de la Reserva', 'rinac'),
                'date' => __('Fecha', 'rinac'),
                'time' => __('Horario', 'rinac'),
                'persons' => __('Personas', 'rinac'),
                'status' => __('Estado', 'rinac'),
                'comments' => __('Comentarios', 'rinac'),
                'edit' => __('Editar', 'rinac'),
                'cancel' => __('Cancelar Reserva', 'rinac'),
                'close' => __('Cerrar', 'rinac'),
            ),
        );

        $modal_html = \Rinac\Template\TemplateHelper::get_template('modals/reserva-details-modal.php', $template_data);

        return array('html' => $modal_html);
    }

    private function op_bulk_action(array $payload, array $raw_post): array
    {
        $this->verify_any_nonce($raw_post, array('rinac_admin_nonce'));

        if (!\current_user_can('manage_woocommerce')) {
            throw new \RuntimeException(__('No tienes permisos para realizar esta acción.', 'rinac'));
        }

        // Este endpoint existe en el JS de admin, pero no estaba implementado en PHP.
        // Dejamos un error explícito para evitar comportamientos silenciosos.
        throw new \RuntimeException(__('Acción masiva no implementada todavía.', 'rinac'));
    }

    /**
     * ===== Helpers internos (copiados del comportamiento existente) =====
     */
    private function get_reserved_persons(int $product_id, string $fecha, string $horario): int
    {
        global $wpdb;
        $table_reservas = $wpdb->prefix . 'rinac_reservas';

        $reservadas = $wpdb->get_var($wpdb->prepare(
            "SELECT COALESCE(SUM(num_personas), 0) FROM $table_reservas
             WHERE product_id = %d AND fecha_reserva = %s AND hora = %s
             AND status IN ('pendiente', 'confirmada')",
            $product_id,
            $fecha,
            $horario
        ));

        return (int) $reservadas;
    }

    private function get_max_personas_for_slot(int $product_id, string $horario): int
    {
        global $wpdb;
        $table_horas = $wpdb->prefix . 'rinac_producto_horas';

        $max = $wpdb->get_var($wpdb->prepare(
            "SELECT capacidad FROM $table_horas WHERE product_id = %d AND hora = %s LIMIT 1",
            $product_id,
            $horario
        ));

        return $max !== null ? (int) $max : 0;
    }

    private function get_product_horarios(int $product_id)
    {
        global $wpdb;
        $table_horas = $wpdb->prefix . 'rinac_producto_horas';

        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_horas WHERE product_id = %d ORDER BY orden",
            $product_id
        ));
    }

    private function get_available_dates_for_product(int $product_id): array
    {
        global $wpdb;
        $table_disponibilidad = $wpdb->prefix . 'rinac_disponibilidad';

        $fechas_disponibles = $wpdb->get_col($wpdb->prepare(
            "SELECT fecha FROM $table_disponibilidad
             WHERE product_id = %d AND disponible = 1 AND fecha >= CURDATE()
             ORDER BY fecha",
            $product_id
        ));

        return \is_array($fechas_disponibles) ? $fechas_disponibles : array();
    }
}

