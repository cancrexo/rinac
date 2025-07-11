# Integración de Plantillas RINAC - Guía de Uso

## Resumen de la Integración

Las plantillas del plugin RINAC han sido completamente integradas en el flujo PHP, proporcionando un sistema robusto y flexible para renderizar modales, formularios y componentes administrativos.

## Estructura de Integración

### 1. Clase Helper (`RINAC_Template_Helper`)
- **Ubicación**: `/includes/class-rinac-template-helper.php`
- **Propósito**: Centralizar la lógica de renderizado de plantillas
- **Características**:
  - Fallback al tema activo (tema hijo > tema padre > plugin)
  - Sistema de filtros para personalización
  - Funciones utilitarias (formateo de fechas, estados, etc.)

### 2. Funciones Globales (`functions.php`)
- **Ubicación**: `/includes/functions.php`
- **Propósito**: Proporcionar funciones fáciles de usar para desarrolladores
- **Funciones principales**:
  - `rinac_get_template()` - Renderizar plantilla
  - `rinac_include_template()` - Incluir plantilla directamente
  - `rinac_format_date()` - Formatear fechas
  - `rinac_check_booking_availability()` - Verificar disponibilidad

## Plantillas Integradas

### Frontend

#### 1. Formulario de Reserva (`templates/forms/booking-form.php`)
**Integración**: Clase `RINAC_Frontend::show_visitas_booking_form()`

**Variables disponibles**:
```php
$product          // Objeto WooCommerce del producto
$product_id       // ID del producto
$horarios         // Array de horarios disponibles
$fechas_disponibles // Array de fechas disponibles
$max_personas     // Máximo de personas permitidas
$require_phone    // Si el teléfono es obligatorio
$strings          // Array de cadenas de texto traducidas
```

**Uso en PHP**:
```php
// Ejemplo de uso directo
$template_data = array(
    'product_id' => 123,
    'max_personas' => 15,
    'require_phone' => true,
    'strings' => array(
        'date_label' => __('Fecha de la visita:', 'rinac'),
        'time_label' => __('Horario:', 'rinac')
    )
);

echo rinac_get_template('forms/booking-form.php', $template_data);
```

#### 2. Modal de Reserva Rápida (`templates/modals/quick-booking-modal.php`)
**Integración**: Clase `RINAC_Frontend::render_quick_booking_modal()`

**AJAX Endpoint**: `wp_ajax_rinac_render_quick_booking_modal`

**JavaScript**:
```javascript
// Ejemplo de uso desde JavaScript
jQuery.post(ajaxurl, {
    action: 'rinac_render_quick_booking_modal',
    product_id: 123,
    nonce: rinac_nonce
}, function(response) {
    if (response.success) {
        jQuery('body').append(response.data.html);
    }
});
```

#### 3. Modal de Detalles de Reserva (`templates/modals/reserva-details-modal.php`)
**Integración**: Clase `RINAC_Frontend::render_booking_details_modal()`

**AJAX Endpoint**: `wp_ajax_rinac_render_booking_details_modal`

### Backend (Administración)

#### 1. Dashboard Principal (`templates/admin/dashboard.php`)
**Integración**: Clase `RINAC_Admin::admin_page()`

**Variables disponibles**:
```php
$dashboard_data   // Estadísticas y datos del dashboard
$settings         // Configuraciones del plugin
$strings          // Cadenas de texto traducidas
```

**Datos del dashboard**:
```php
$dashboard_data = array(
    'total_reservas' => 150,
    'reservas_mes_actual' => 25,
    'reservas_pendientes' => 5,
    'reservas_confirmadas' => 20,
    'productos_visitas' => 8,
    'proximas_reservas' => array(/* reservas próximas */)
);
```

#### 2. Formulario de Rangos Horarios (`templates/admin/rangos-horarios-form.php`)
**Integración**: Clase `RINAC_Admin::rangos_page()`

#### 3. Item de Hora (`templates/admin/hora-item.php`)
**Integración**: Clase `RINAC_Admin::render_hora_item()`

## Ejemplos Prácticos

### 1. Personalizar Formulario de Reserva en el Tema

**Paso 1**: Copiar plantilla al tema
```bash
# Crear directorio en el tema
mkdir -p wp-content/themes/tu-tema/rinac/forms/

# Copiar plantilla
cp wp-content/plugins/rinac/templates/forms/booking-form.php wp-content/themes/tu-tema/rinac/forms/
```

**Paso 2**: Personalizar la plantilla
```php
<!-- En wp-content/themes/tu-tema/rinac/forms/booking-form.php -->

<!-- Agregar contenido personalizado antes del formulario -->
<div class="mi-mensaje-personalizado">
    <p>¡Oferta especial! Reserva ahora y obtén un 10% de descuento.</p>
</div>

<!-- El resto de la plantilla original... -->
```

### 2. Usar Filtros para Modificar Datos

```php
// En functions.php del tema
add_filter('rinac_template_data', 'mi_personalizar_datos_plantilla', 10, 2);

function mi_personalizar_datos_plantilla($data, $template_name) {
    if ($template_name === 'forms/booking-form.php') {
        // Agregar datos personalizados
        $data['mi_descuento'] = get_option('mi_descuento_activo', false);
        $data['mi_mensaje_especial'] = 'Reserva con confianza';
    }
    
    return $data;
}
```

### 3. Crear Modal Personalizado

```php
// En tu plugin o tema
function mi_modal_personalizado() {
    $content = '<p>Contenido de mi modal personalizado</p>';
    
    return rinac_render_modal(
        'mi-modal-id',
        'Mi Modal Personalizado',
        $content,
        'large' // tamaño: small, medium, large
    );
}

// Usar el modal
echo mi_modal_personalizado();
```

### 4. Integrar Plantilla en Widget

```php
class Mi_Widget_Rinac extends WP_Widget {
    public function widget($args, $instance) {
        $template_data = array(
            'horarios_destacados' => $this->get_horarios_destacados(),
            'promocion_activa' => true
        );
        
        echo $args['before_widget'];
        echo rinac_get_template('widgets/horarios-destacados.php', $template_data);
        echo $args['after_widget'];
    }
    
    private function get_horarios_destacados() {
        // Lógica para obtener horarios destacados
        return array();
    }
}
```

## Hooks y Filtros Disponibles

### Filtros de Plantillas
```php
// Modificar ruta de plantilla
add_filter('rinac_template_path', 'mi_ruta_plantilla_personalizada', 10, 2);

// Modificar datos de plantilla
add_filter('rinac_template_data', 'mis_datos_plantilla_personalizados', 10, 2);
```

### Acciones de Renderizado
```php
// Antes de renderizar cualquier plantilla
add_action('rinac_before_template_render', 'mi_accion_antes_plantilla', 10, 2);

// Después de renderizar plantilla
add_action('rinac_after_template_render', 'mi_accion_despues_plantilla', 10, 2);
```

## Funciones Utilitarias

### Verificación de Disponibilidad
```php
// Verificar si hay disponibilidad para una reserva
$availability = rinac_check_booking_availability(123, '2024-12-25', '10:00', 4);

if ($availability['available']) {
    echo $availability['message']; // "4 plazas disponibles"
} else {
    echo $availability['message']; // "Solo quedan 2 plazas disponibles"
}
```

### Formateo de Datos
```php
// Formatear fecha
echo rinac_format_date('2024-12-25'); // "25/12/2024"

// Formatear hora
echo rinac_format_time('14:30:00'); // "14:30"

// Obtener estado de reserva
echo rinac_get_booking_status_text('confirmada'); // "Confirmada"
echo rinac_get_booking_status_class('pendiente'); // "rinac-status-pending"
```

### Obtener Datos de Reserva
```php
// Obtener horarios disponibles para una fecha
$horarios = rinac_get_available_times(123, '2024-12-25');

foreach ($horarios as $horario) {
    echo $horario['formatted_time'] . ' (' . $horario['disponibles'] . ' disponibles)';
}
```

## Mejores Prácticas

### 1. Seguridad
- Siempre usar `esc_html()`, `esc_attr()`, `esc_url()` en las plantillas
- Verificar nonces en formularios
- Validar datos de entrada

### 2. Rendimiento
- Usar caché para datos que no cambian frecuentemente
- Minimizar consultas a la base de datos en plantillas
- Cargar JavaScript/CSS solo cuando sea necesario

### 3. Mantenibilidad
- Usar variables descriptivas en plantillas
- Documentar personalizaciones
- Seguir convenciones de nomenclatura

### 4. Compatibilidad
- Probar con diferentes temas
- Verificar compatibilidad con plugins populares
- Usar fallbacks apropiados

## Resolución de Problemas

### Plantilla no se encuentra
```php
// Debug: verificar ruta de plantilla
$template_path = rinac_locate_template('forms/booking-form.php');
if (!$template_path) {
    error_log('RINAC: Plantilla no encontrada');
}
```

### Datos no se pasan correctamente
```php
// Debug: verificar datos en plantilla
add_filter('rinac_template_data', function($data, $template_name) {
    error_log('RINAC: Datos para ' . $template_name . ': ' . print_r($data, true));
    return $data;
}, 10, 2);
```

### JavaScript no recibe datos
```php
// Verificar que los datos se estén pasando correctamente
add_action('wp_footer', function() {
    if (is_product()) {
        echo '<script>console.log("RINAC Data:", window.rinacBookingData);</script>';
    }
});
```

## Extensibilidad

El sistema de plantillas está diseñado para ser completamente extensible:

1. **Temas** pueden sobrescribir cualquier plantilla
2. **Plugins** pueden usar filtros para modificar datos
3. **Desarrolladores** pueden crear nuevas plantillas y registrarlas
4. **Administradores** pueden personalizar sin tocar código

Esta integración proporciona una base sólida para el desarrollo y personalización del plugin RINAC, manteniendo la flexibilidad y siguiendo las mejores prácticas de WordPress.
