Eres un senior WordPress/WooCommerce developer experto en plugins complejos. Vas a crear desde cero un plugin completo llamado "RINAC" (Rinac Is Not Another Calendar) (prefijo rinac_ para todo: post types, meta keys, opciones, AJAX actions, etc.).

IMPORTANTE: cada vez que se modifique el plan de trabajo, hay que actualizar también `PLAN-NOVO-checklist.md` y `PLAN-NOVO.md` para mantenerlos sincronizados.

REQUISITOS TÉCNICOS OBLIGATORIOS:
- Usa Composer con PSR-4 autoloading (namespace RINAC\...)
- Los stubs de WordPress y WooCommerce se instalarán como `require-dev` y quedarán en `vendor/` (no versionar en git).
- Estructura de carpetas estándar moderna:
  rinac/
  ├── composer.json
  ├── rinac.php (punto de entrada)
  ├── src/
  │   ├── Core/
  │   ├── Admin/
  │   ├── Frontend/
  │   ├── Models/
  │   ├── Calendar/
  │   ├── Booking/
  │   ├── Ajax/
  │   └── Utils/
  ├── assets/
  ├── templates/
  └── languages/
- Centraliza TODOS los endpoints AJAX en una sola clase RINAC\Ajax\AjaxHandler (método register() + handle() con nonce y capability check)
- Usa SIEMPRE que sea posible las funciones nativas de WordPress y WooCommerce (wc_get_product(), wc_get_order(), wc_price(), etc.)
- Soporta WP 6.6+ y WooCommerce 9.0+
- i18n (requisito estricto de carga):
  - El `text_domain` del plugin se debe cargar **exclusivamente** dentro del hook `init` (vía `load_plugin_textdomain()`).
  - Nunca se debe cargar antes del hook `init` (ni en el bootstrap del archivo principal, ni fuera de hooks).
- i18n (proceso):
  - Generar el `.pot` puede hacerse **después** de tener el “básico” ya funcionando (primero se completa la funcionalidad y luego se extraen/codifican traducciones).
- Callback visibility (requisito estricto para evitar errores):
  - Los métodos pasados como callback en `add_menu_page()` / `add_submenu_page()` deben ser **public** (o usar closures).
  - Igualmente, cualquier método pasado como callback a `add_action()` / `add_filter()` / `remove_action()` / `remove_filter()` usando `array($obj, 'metodo')` debe ser **public**.
  - Evitar `private`/`protected` en callbacks invocados por WordPress (puede causar errores de accesibilidad en runtime).

 - Regla específica para lint de superglobales:
   - Si el lint se queja de `$_POST`, `$_GET` o `$_REQUEST`:
     - No usar `$GLOBALS`.
     - Copiar primero a variables locales con validación de tipo:
       - `$post = isset( $_POST ) && is_array( $_POST ) ? $_POST : array();`
       - `$get = isset( $_GET ) && is_array( $_GET ) ? $_GET : array();`
       - `$request = isset( $_REQUEST ) && is_array( $_REQUEST ) ? $_REQUEST : array();`
     - Opcional: añadir `/** @noinspection PhpUndefinedVariableInspection */` justo antes de las líneas donde el lint marque esos accesos.

MENÚ DE ADMINISTRACIÓN DE WORDPRESS:
- Crear un menú principal llamado "RINAC" (icon: calendar-alt)
- Dentro de este menú las siguientes subopciones (en este orden exacto):
  1. Dashboard (resumen de reservas y ocupación)
  2. Productos Reservables
  3. Slots
  4. Turnos
  5. Tipos de Participantes
  6. Recursos
  7. Calendario Global
  8. Reservas
  9. Ajustes (incluirá el botón "Importar datos de prueba")
- Todas las páginas deben usar las clases y funciones nativas de WordPress (add_menu_page, add_submenu_page, Screen Options, etc.)

La idea es que el plugin sirva para crear reservas hoteleras, reservas de actividades, reservas de transportes, reservas de restaurantes, reservas de eventos, etc.

[El resto de la descripción de casos de uso (bodega, restaurante opción1 y opción2, alquiler de coches, alquiler de habitaciones) se mantiene exactamente igual que en la versión anterior – no la repito aquí por brevedad pero debe estar incluida completa]

TIPOS DE PRODUCTO Y POST PERSONALIZADOS:
- rinac_reserva: tipo de producto WooCommerce personalizado (NO CPT separado). Registrado correctamente con woocommerce_register_product_type y filtro woocommerce_product_class → clase RINAC\Models\ReservaProduct
- rinac_slot → CPT con admin page independiente
- rinac_turno → CPT con admin page independiente
- rinac_participant_type → CPT (precio, fracción, etc.)
- rinac_resource → CPT (precio opcional)
- rinac_booking → CPT (relacionado con WC orders)

Asegurarse en todo momento de que:
- El tipo de producto rinac_reserva se registra correctamente y wc_get_product() devuelve una instancia de RINAC\Models\ReservaProduct
- Todos los CPTs se registran correctamente con labels, capacidades, show_in_menu = false (porque van dentro del menú RINAC), etc.

Adicionalmente, implementa manejo de datos de prueba de la siguiente forma:
- En el paso 1 (activación del plugin): register_activation_hook para crear datos MÍNIMOS de prueba SOLO si WP_DEBUG o RINAC_LOAD_DEMO_ON_ACTIVATION
- En Ajustes: botón "Importar datos de prueba" con advertencia roja fuerte.

TAREAS QUE TE PIDO:
ANTES de empezar con cualquier código, genera un **PLAN DE TRABAJO DETALLADO POR PUNTOS NUMERADOS** que incluya:
- Todas las clases que se van a crear
- Todos los archivos necesarios
- Los hooks principales (add_action/add_filter) que se usarán
- La estructura exacta del menú RINAC
- Cómo se va a integrar wc_get_product() en todo el flujo

Después de mostrarme ese plan detallado, desarrolla el plugin paso a paso en este orden exacto (responde con cada paso completo y luego espera mi "SIGUIENTE"):

1. Configuración inicial: composer.json, rinac.php, PSR-4, activación/desactivación, registro correcto del tipo de producto rinac_reserva, registro de todos los CPTs + creación del menú "RINAC" + datos mínimos de prueba en activación
2. Clase AjaxHandler centralizada + ejemplo de 3 endpoints
3. Meta boxes y settings para productos de tipo rinac_reserva
4. Lógica de disponibilidad y cálculo de capacidad (clase AvailabilityManager)
5. Frontend booking form + FullCalendar integración
6. Gestión de recursos y participantes
7. Sistema de pago depósito + hooks de WooCommerce
8. Calendario global admin + listado de reservas + botón "Importar datos de prueba"
9. Templates y overrides
10. Documentación completa (README.md + inline docs)

Reglas estrictas:
- Todo código limpio, comentado, con namespaces
- Security: nonces, sanitization, capability checks
- Performance: caches transient para disponibilidad
- Internacionalización (i18n) desde el principio
- No uses plugins externos de reservas (todo custom)
- Siempre que sea posible usa funciones nativas de WooCommerce (wc_get_product(), etc.)

Empieza por generar primero el PLAN DE TRABAJO DETALLADO POR PUNTOS. Cuando termine, dime "SIGUIENTE" y continuamos con el paso 1.

ANEXO: CASOS DE USO FUNCIONALES (OBLIGATORIOS)

- Bodega:
  - Reserva por fecha y slot/turno.
  - Capacidad por franja.
  - Participantes por tipo con fracción configurable.
  - Recursos opcionales (visita guiada, degustación, etc.).
- Restaurante (opción 1):
  - Reserva por día y turno.
  - Límite global por turno y límite opcional por slot.
  - Precio por comensal.
- Restaurante (opción 2):
  - Reserva por mesa/unidad.
  - Límite por mesa.
  - Datos de accesibilidad, alergias e intolerancias.
- Alquiler de coches:
  - Reserva por día o rango de fechas.
  - Unidad por modelo/recurso.
  - Recursos opcionales (limpieza, chófer, etc.).
- Alquiler de habitaciones:
  - Reserva por día o rango de fechas.
  - Unidad por habitación/tipo.
  - Recursos opcionales (parking, lavandería, etc.).
- Objetivo común:
  - Mismo panel admin para todos los escenarios.
  - Calendario global e individual por producto.

ANEXO: CAMBIOS DE ARQUITECTURA ACORDADOS

1) Modo de reserva por producto (`rinac_booking_mode`)
- Definir un metadato principal para el producto reservable:
  - `date`
  - `date_range`
  - `datetime`
  - `date_range_same_time`
  - `turno_dia`
  - `unidad_rango`
- El cálculo de disponibilidad debe resolverse por estrategia según ese modo.

2) Capacidad en dos niveles
- Capacidad global del producto.
- Capacidad por slot/turno/unidad.
- La capacidad efectiva será el mínimo entre límites aplicables.

3) Participantes formalizados
- `rinac_participant_type` debe soportar:
  - tipo de precio,
  - valor de precio,
  - fracción de capacidad,
  - límites por tipo (opcional).

4) Recursos formalizados
- `rinac_resource` debe diferenciar:
  - `addon` (extra),
  - `unit` (unidad reservable).
- Política de precio por recurso:
  - `none`, `fixed`, `per_person`, `per_day`, `per_night`.

5) Perfil de negocio para simplificar UX
- Añadir perfiles:
  - `bodega`
  - `restaurante_turnos`
  - `restaurante_mesas`
  - `alquiler_coches`
  - `alquiler_habitaciones`
  - `generico`
- Cada perfil activa campos y validaciones por defecto.

6) Concurrencia y bloqueo temporal (quote/hold)
- Incluir endpoint de prevalidación (`rinac_quote_booking`) que:
  - valide disponibilidad,
  - calcule precio preliminar,
  - reserve temporalmente capacidad.
- Confirmar luego con `rinac_create_booking_request` reutilizando el bloqueo vigente.

7) Escalabilidad de datos
- Mantener CPTs para administración y edición.
- Prever tabla técnica para consultas intensivas de disponibilidad y solapes.