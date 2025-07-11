# RINAC - RINAC Is Not Another Calendar

Plugin de reservas para WordPress y WooCommerce que permite crear productos de tipo VISITAS con sistema de calendario y gestión de horarios.

## Descripción

RINAC es un plugin completo para gestionar reservas de visitas, tours, experiencias y cualquier tipo de servicio que requiera selección de fecha y hora. Diseñado específicamente para integrarse perfectamente con WooCommerce.

## Características Principales

### Backend (Administración)

- **Tipo de Producto VISITAS**: Nuevo tipo de producto virtual especializado en reservas
- **Configuración Global**: Máximo de personas por hora configurable
- **Rangos Horarios**: Sistema modular para definir horarios reutilizables
- **Calendario de Disponibilidad**: Control visual de fechas disponibles
- **Gestión de Horarios por Producto**: Asignación flexible de horarios específicos
- **Panel de Administración**: Interface completa para gestionar todas las funcionalidades

### Frontend (Cliente)

- **Formulario de Reserva**: Interface intuitiva para seleccionar fecha, hora y personas
- **Calendario Interactivo**: Selector de fechas con disponibilidad en tiempo real
- **Validación en Tiempo Real**: Verificación automática de disponibilidad
- **Integración con Carrito**: Los datos de reserva se muestran en carrito y checkout
- **Responsive**: Compatible con dispositivos móviles

### Funcionalidades Técnicas

- **Validación de Concurrencia**: Previene sobrerreservas
- **Base de Datos Optimizada**: Estructura eficiente para alto rendimiento
- **AJAX Dinámico**: Experiencia fluida sin recargas de página
- **Hooks y Filtros**: Extensible para desarrolladores
- **Multiidioma**: Preparado para traducciones

## Instalación

1. Subir la carpeta `rinac` al directorio `/wp-content/plugins/`
2. Activar el plugin desde el panel de administración de WordPress
3. Asegurarse de que WooCommerce esté instalado y activado
4. Ir a **RINAC → Configuración** para configurar las opciones básicas

## Configuración Inicial

### 1. Configuración General
- Acceder a **RINAC → Configuración**
- Establecer el máximo de personas por hora por defecto
- Configurar notificaciones por email
- Ajustar otros parámetros según necesidades

### 2. Crear Rangos Horarios
- Ir a **RINAC → Rangos Horarios**
- Crear rangos predefinidos (ej: "Horario Verano", "Horario Invierno")
- Definir las horas específicas de cada rango
- Establecer capacidad máxima por slot

### 3. Configurar Productos
- Crear un nuevo producto o editar uno existente
- Seleccionar tipo de producto **"Visitas"**
- Configurar en la pestaña **"Configuración RINAC"**:
  - Máximo de personas por hora específico
- En la pestaña **"Calendario"**:
  - Marcar fechas disponibles/no disponibles
- En la pestaña **"Horarios"**:
  - Asignar rangos predefinidos o crear horarios específicos

## Uso del Plugin

### Para Administradores

1. **Gestión de Disponibilidad**:
   - Acceder a la edición del producto
   - Usar el calendario para marcar fechas disponibles
   - Las fechas se pueden activar/desactivar con un clic

2. **Configuración de Horarios**:
   - Seleccionar rangos predefinidos del dropdown
   - Añadir horarios manuales si es necesario
   - Ajustar capacidad específica por horario
   - Reordenar horarios con drag & drop

3. **Monitoreo de Reservas**:
   - Ver reservas en **RINAC → Reservas**
   - Revisar estadísticas de uso
   - Exportar datos si es necesario

### Para Clientes

1. **Realizar una Reserva**:
   - Acceder a la página del producto tipo VISITAS
   - Seleccionar fecha en el calendario
   - Elegir horario del dropdown
   - Indicar número de personas
   - Añadir al carrito

2. **Proceso de Compra**:
   - Los datos de reserva aparecen en el carrito
   - Se mantienen durante todo el proceso de checkout
   - Se guardan en el pedido final

## Estructura de Archivos

```
rinac/
├── rinac.php                          # Archivo principal del plugin
├── includes/
│   ├── class-rinac-install.php        # Instalación y configuración inicial
│   ├── class-rinac-product-type.php   # Tipo de producto VISITAS
│   ├── class-rinac-admin.php          # Panel de administración
│   ├── class-rinac-frontend.php       # Funcionalidades de frontend
│   ├── class-rinac-calendar.php       # Gestión del calendario
│   ├── class-rinac-validation.php     # Validaciones y verificaciones
│   └── class-rinac-database.php       # Operaciones de base de datos
├── assets/
│   ├── css/
│   │   ├── admin.css                  # Estilos de administración
│   │   ├── frontend.css               # Estilos de frontend
│   │   └── product.css                # Estilos de configuración de producto
│   └── js/
│       ├── admin.js                   # JavaScript de administración
│       ├── frontend.js                # JavaScript de frontend
│       └── product.js                 # JavaScript de configuración de producto
└── templates/                         # Plantillas personalizables
```

## Base de Datos

El plugin crea las siguientes tablas:

- `wp_rinac_rangos_horarios`: Rangos horarios globales
- `wp_rinac_horas`: Horas específicas de cada rango
- `wp_rinac_disponibilidad`: Fechas disponibles por producto
- `wp_rinac_producto_horas`: Horarios asignados a productos específicos
- `wp_rinac_reservas`: Registro de todas las reservas realizadas

## Hooks para Desarrolladores

### Actions
- `rinac_loaded`: Se ejecuta cuando el plugin está completamente inicializado
- `rinac_before_save_booking`: Antes de guardar una reserva
- `rinac_after_save_booking`: Después de guardar una reserva
- `rinac_booking_status_changed`: Cuando cambia el estado de una reserva

### Filters
- `rinac_booking_form_fields`: Modificar campos del formulario de reserva
- `rinac_validate_booking_data`: Validaciones personalizadas
- `rinac_calendar_availability`: Modificar disponibilidad del calendario
- `rinac_booking_summary`: Personalizar resumen de reserva

## Requisitos del Sistema

- WordPress 5.0 o superior
- WooCommerce 5.0 o superior
- PHP 7.4 o superior
- MySQL 5.6 o superior

## Compatibilidad

- ✅ WordPress Multisite
- ✅ Temas estándar de WordPress
- ✅ Plugins de cache populares
- ✅ WPML (preparado para traducciones)
- ✅ Gutenberg y editores de página

## Personalización

### CSS Personalizado
Los estilos pueden ser sobrescritos añadiendo CSS en el tema:

```css
/* Personalizar formulario de reserva */
.rinac-booking-form {
    /* Tus estilos personalizados */
}
```

### Plantillas
Las plantillas se pueden copiar a la carpeta del tema activo:
`tu-tema/rinac/template-name.php`

## Sistema de Plantillas Integrado

El plugin incluye un sistema de plantillas completamente integrado que permite personalización sin modificar archivos del plugin.

### Ubicación de Plantillas

```
rinac/templates/
├── modals/
│   ├── base-modal.php              # Modal base reutilizable
│   ├── quick-booking-modal.php     # Modal de reserva rápida
│   └── reserva-details-modal.php   # Modal de detalles de reserva
├── forms/
│   └── booking-form.php            # Formulario principal de reserva
└── admin/
    ├── dashboard.php               # Panel principal de administración
    ├── rangos-horarios-form.php    # Formulario de gestión de horarios
    └── hora-item.php               # Componente individual de hora
```

### Personalización en el Tema

Las plantillas pueden copiarse al tema activo para personalización:

```
tu-tema/rinac/
└── forms/
    └── booking-form.php            # Versión personalizada
```

### Funciones de Plantillas

```php
// Renderizar plantilla
echo rinac_get_template('forms/booking-form.php', $data);

// Formatear datos
echo rinac_format_date('2024-12-25');
echo rinac_format_time('14:30:00');

// Verificar disponibilidad
$availability = rinac_check_booking_availability($product_id, $date, $time, $persons);
```

Para más detalles, consulta `TEMPLATE_INTEGRATION.md`.

## Resolución de Problemas

### Problemas Comunes

1. **El calendario no carga**:
   - Verificar que jQuery UI esté cargado
   - Comprobar errores en la consola del navegador

2. **Las reservas no se guardan**:
   - Verificar permisos de base de datos
   - Comprobar logs de errores de WordPress

3. **Conflictos con otros plugins**:
   - Desactivar otros plugins temporalmente
   - Verificar compatibilidad de versiones

### Logs y Debug

Para activar el modo debug:
```php
// En wp-config.php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

## Soporte y Contribuciones

### Reportar Errores
- Descripción detallada del problema
- Pasos para reproducir el error
- Información del entorno (WordPress, PHP, tema, plugins)

### Contribuir al Desarrollo
1. Fork del repositorio
2. Crear una rama para la nueva funcionalidad
3. Commit de los cambios
4. Pull request con descripción detallada

## Changelog

### Versión 1.0.0
- Lanzamiento inicial
- Tipo de producto VISITAS
- Sistema de calendario
- Gestión de horarios
- Validación de reservas
- Panel de administración completo

## Licencia

GPL v2 o posterior. Ver archivo LICENSE para más detalles.

## Créditos

Desarrollado por Adegaeidos para la gestión de reservas de enoturismo y experiencias.

---

**¿Necesitas ayuda?** Consulta la documentación completa o contacta con el equipo de soporte.
