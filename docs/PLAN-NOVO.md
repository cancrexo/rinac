## Plan de trabajo (RINAC)

### Paso 1 del desarrollo

1. **Base del plugin / bootstrap (ficheros raíz)**
   - Crear `composer.json` con autoload PSR-4 para el namespace `RINAC\...`.
   - Crear `rinac.php` como punto de entrada del plugin (define constantes, carga el autoloader de Composer y arranca la inicialización).
   - Registrar `register_activation_hook` y `register_deactivation_hook` para:
     - Crear datos mínimos de prueba SOLO si `WP_DEBUG` o `RINAC_LOAD_DEMO_ON_ACTIVATION` está activo.
     - Dejar el plugin en estado consistente al desactivar (limpieza mínima/acciones reversibles).

2. **Estructura exacta de carpetas (según el documento)**
   - Mantener esta estructura:
     - `rinac/`
       - `composer.json`
       - `rinac.php`
       - `src/`
         - `Core/`
         - `Admin/`
         - `Frontend/`
         - `Models/`
         - `Calendar/`
         - `Booking/`
         - `Ajax/`
         - `Utils/`
       - `assets/`
       - `templates/`
       - `languages/`

3. **Clases que se van a crear (inventario completo)**
   - `RINAC\Core\Plugin` (orquesta el arranque del plugin y registra hooks principales).
   - `RINAC\Core\Loader` (helper para centralizar `add_action`/`add_filter` si conviene mantener el bootstrap limpio).
   - `RINAC\Core\I18n` (carga `textdomain` y assets traducibles).
   - `RINAC\Core\PostTypesRegistrar` (registra CPTs: `rinac_slot`, `rinac_turno`, `rinac_participant_type`, `rinac_resource`, `rinac_booking`).
   - `RINAC\Core\MenuRegistrar` (crea menú “RINAC” y submenús en el orden exacto).
   - `RINAC\Core\ProductTypeRegistrar` (registra el tipo de producto WooCommerce `rinac_reserva` y el mapeo a la clase).
   - `RINAC\Models\ReservaProduct` (clase del tipo producto; expone getters usados por disponibilidad/formularios).
   - `RINAC\Ajax\AjaxHandler` (única clase centralizada para TODOS los endpoints AJAX con nonce y checks).
   - `RINAC\Calendar\AvailabilityManager` (capacidad/ocupación y cálculo de disponibilidad; con caché transient).
   - `RINAC\Booking\BookingManager` (creación/validación de reservas, persistencia y reglas de negocio).
   - `RINAC\Booking\DepositManager` (lógica de “depósito” y adaptación a hooks de WooCommerce).
   - `RINAC\Booking\ResourceManager` (relación/selección de recursos en la reserva).
   - `RINAC\Booking\ParticipantManager` (tipos de participantes, precio/fracción, totalizadores).
   - `RINAC\Admin\GlobalCalendarPage` (pantalla admin calendario global + listado).
   - `RINAC\Admin\BookingListTable` (listado/paginación de `rinac_booking` en admin).
   - `RINAC\Admin\BookingMetaBoxes` (meta boxes y settings para `rinac_reserva`).
   - `RINAC\Admin\DemoDataImporter` (importador “Importar datos de prueba” para admin).
   - `RINAC\Frontend\BookingForm` (render del formulario frontend + integración con FullCalendar).
   - `RINAC\Frontend\Assets` (enqueue de JS/CSS y `wp_localize_script`).
   - `RINAC\Frontend\TemplateLoader` (incluye templates del plugin).
   - `RINAC\Utils\Sanitizer` (sanitización/normalización consistente).
   - `RINAC\Utils\Nonce` (helpers para generar/validar nonces si conviene).
   - `RINAC\Utils\Cache` (helpers para claves de transient y TTL).

4. **Hooks principales (add_action/add_filter) que se van a usar**
   - En inicialización del plugin (`RINAC\Core\Plugin`):
     - `init`: registrar CPTs (o `init`/`wp_loaded` según convenga) + cargar i18n.
       - **Requisito estricto:** el `text_domain` (vía `load_plugin_textdomain()`) se debe cargar **exclusivamente** dentro de `init` y **nunca antes** (ni en el bootstrap del archivo principal ni fuera de hooks).
       - Además, dentro de `init` se inicializarán:
         - el `textdomain`/`domain path` del plugin,
         - la carga de traducciones para JS (si se usa) vía `wp_set_script_translations()` (cuando aplique),
         - y cualquier registro adicional relacionado con i18n.
     - `admin_menu`: crear menú/submenús “RINAC” (solo admin).
     - `admin_init`: registrar meta boxes y ajustes para `rinac_reserva`.
     - `wp_enqueue_scripts`: cargar assets frontend y localizar endpoints/nonce.
   - WooCommerce:
     - `woocommerce_register_product_type` (vía registro en `ProductTypeRegistrar`).
     - `woocommerce_product_class` (mapea `rinac_reserva` a `RINAC\Models\ReservaProduct`).
     - Hooks de cálculo/creación de orden para depósito y persistencia (mencionados más adelante).
   - AJAX (en `RINAC\Ajax\AjaxHandler`, de forma centralizada):
     - `wp_ajax_{action}` para cada endpoint (y si aplica `wp_ajax_nopriv_{action}`).

5. **Estructura exacta del menú “RINAC” (orden y pantallas)**
   - Crear menú principal: `RINAC` (icono `calendar-alt`).
   - Submenús (en el orden exacto requerido):
     1. `Dashboard`
     2. `Productos Reservables`
     3. `Slots`
     4. `Turnos`
     5. `Tipos de Participantes`
     6. `Recursos`
     7. `Calendario Global`
     8. `Reservas`
     9. `Ajustes` (incluye botón “Importar datos de prueba” con advertencia roja fuerte).
   - Cada página del menú se implementa con su clase correspondiente en `RINAC\Admin\...`.

   - **Requisito estricto de callbacks (importante con `private`)**
     - Los métodos usados como callback en `add_menu_page()` / `add_submenu_page()` deben ser **public** (o usar closures).
     - Evitar `private` o `protected` en esos callbacks: WordPress los invoca desde fuera del scope de la clase y puede producir un fatal error al no ser accesibles.
     - Ejemplo de patrón recomendado:
       - `RINAC\Admin\SomePage::render()` => `public function render() : void`
       - y registrar con `add_submenu_page(..., array( $instancia, 'render' ))`.
     - Alternativa segura: `add_menu_page(..., function() use ($instancia) { $instancia->render(); });`.

   - **Requisito estricto de callbacks en hooks/filters (global)**
     - Cualquier método pasado como callback a `add_action()` / `add_filter()` / `remove_action()` / `remove_filter()` debe ser **public** si se usa la forma `array( $obj, 'método' )`.
     - Evitar pasar métodos `private`/`protected` como callback en hooks/filters (además del menú): WordPress llamará el callback desde fuera del scope y puede provocar errores de accesibilidad en runtime.
     - Regla práctica:
       - `public` para callbacks invocados por WordPress.
       - `private`/`protected` solo para helpers internos llamados desde un callback público.

   - **Requisito estricto de slugs y redirecciones**
     - Mantener `parent_slug`/`menu_slug` consistentes para que `admin.php?page=...` funcione.
     - Al usar la opción “redirigir” de `Productos Reservables` hacia el listado de WooCommerce, la URL de destino debe construirse con `admin_url()` y preservando el query var correcto (`product_type`).

6. **Integración de `wc_get_product()` en todo el flujo**
   - Regla general: siempre que haya que leer configuración/capacidad de un “producto reservable”:
     - Obtener la instancia con `wc_get_product( $product_id )`.
     - Validar que sea instancia de `RINAC\Models\ReservaProduct` (o fallar con `WP_Error`/mensaje seguro).
   - Dónde se usa típicamente:
     - Disponibilidad: `AvailabilityManager` usa la instancia del producto.
     - Formulario: `BookingForm` usa `ReservaProduct` para construir opciones.
     - Creación de reserva/pedido: `BookingManager` re-lee el producto para recalcular totales y validar.

7. **AJAX centralizado: diseño de `RINAC\Ajax\AjaxHandler`**
   - `AjaxHandler::register()` registra todas las acciones `wp_ajax_...` (y nopriv si aplica).
   - `AjaxHandler::handle()`:
     - valida nonce,
     - valida capability/csrf según admin vs frontend,
     - enrutado interno por endpoint/acción.
   - Ejemplo de 3 endpoints (arranque del paso 2):
     1. `rinac_get_availability`
     2. `rinac_get_calendar_events`
     3. `rinac_create_booking_request`
   - Respuesta JSON consistente y sanitizada.

8. **Meta boxes y settings para `rinac_reserva`**
   - Añadir meta boxes en edición del producto `rinac_reserva`:
     - Capacidad base / reglas.
     - Qué CPTs aplica (slots/turnos/resources/participant types permitidos).
     - Reglas de disponibilidad.
     - Ajustes para depósito (si aplica).
   - Guardado con `nonce` + capability check + sanitización.

9. **Disponibilidad y capacidad (AvailabilityManager)**
   - Calcula capacidad restante: total - ocupación.
   - Maneja solapamientos según el modelo (slots/turnos).
   - Cache con transients (clave determinística por producto + rango + parámetros relevantes).

10. **Frontend: booking form + FullCalendar**
   - `Frontend\BookingForm` render del formulario.
   - `FullCalendar` consulta disponibilidad/eventos vía endpoints AJAX.

11. **Gestión de recursos y participantes**
   - `ResourceManager` relaciona recursos seleccionados.
   - `ParticipantManager` gestiona tipos, precio/fracción, totalizadores.
   - `BookingManager` valida todo junto.

12. **Sistema de pago con depósito + hooks WooCommerce**
   - `DepositManager` define depósito y transiciones de estado.
   - Integración con hooks WooCommerce para aplicar lógica sin plugins externos.

13. **Calendario global admin + listado de reservas + Importación demo**
   - Página admin `GlobalCalendarPage`.
   - Listado `BookingListTable`.
   - Botón “Importar datos de prueba” dentro de `Ajustes`.

14. **Templates y overrides**
   - `templates/` contendrá vistas del plugin.
   - `TemplateLoader` carga templates de forma segura (y se define estrategia de override si aplica).

15. **Documentación completa**
   - `README.md` con instalación, flujo de reservas, endpoints (conceptual), seguridad y i18n.
   - Inline docs en clases críticas (`AjaxHandler`, `AvailabilityManager`, etc.).

16. **Nota importante sobre “versión anterior”**
   - El documento base menciona casos de uso (bodega, restaurante opción1/2, alquiler coches/habitaciones) “exactamente igual que en la versión anterior”.
   - Para respetar esa parte, hace falta que se incluya el texto/documento de la versión anterior.

### Reglas estrictas de i18n (para todo el código)
1. **Definir y usar siempre un `text_domain` único** (prefijo `rinac_` para todo) y que sea el mismo en:
   - PHP `__()`, `_e()`, `_x()`, `_n()`, `esc_html__()`, etc.
   - `load_plugin_textdomain()` (dentro de `init`).
   - `wp_set_script_translations()` (para traducciones en JS si se usa).
2. **No traducir cadenas dinámicas “a mano”**: siempre usar funciones nativas de WordPress (gettext) y cuidar el escapado (`esc_html__`, `esc_attr__`, `wp_kses_post` cuando aplique).
3. **Las respuestas de AJAX también deben ser traducibles**: devolver mensajes con traducciones en backend (usando el mismo `text_domain`) para que el frontend solo renderice.
4. **Cargar el dominio en `init` y nunca antes**:
   - No se debe llamar `load_plugin_textdomain()` en el bootstrap del archivo principal.
   - No se debe llamar antes de `init` aunque parezca “funcionar” en local.
5. **Estructura de traducciones**:
   - Mantener `.pot/.po` (si se generan) bajo `languages/` y empaquetar `.mo` en `languages/` cuando corresponda.
   - Usar el mismo `languages/` definido por `domain_path` en el `load_plugin_textdomain()`.

### Siguiente paso

Tras revisar esto y cuando quieras continuar, responde **“SIGUIENTE”** y empezamos con el **paso 1**: `composer.json`, `rinac.php`, registro del tipo de producto `rinac_reserva`, registro de CPTs, menú y datos mínimos demo.

