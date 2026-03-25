Eres un senior WordPress/WooCommerce developer experto en plugins complejos. Vas a crear desde cero un plugin completo llamado "RINAC" (Rinac Is Not Another Calendar) (prefijo rinac_ para todo: post types, meta keys, opciones, AJAX actions, etc.).

IMPORTANTE: cada vez que se modifique el plan de trabajo, hay que actualizar también `PLAN-NOVO-checklist.md` y `PLAN-NOVO.md` para mantenerlos sincronizados.

DEFINICIÓN OFICIAL DE SLOT (REFERENCIA ÚNICA):
- En RINAC, `slot` es la única entidad temporal/operativa para reservar.
- Un `slot` puede representar:
  - una etiqueta de servicio (p. ej. `comida`, `cena`), o
  - una franja horaria concreta (p. ej. `12:00-12:30`, `12:30-13:00`).
- `turno` se entiende como alias/sinónimo de `slot`.
- Todas las reglas de disponibilidad/capacidad deben modelarse en torno a `slot`.

REQUISITOS TÉCNICOS OBLIGATORIOS:
- Usa Composer con PSR-4 autoloading (namespace RINAC\...)
- Desde el inicio del proyecto ejecutar:
  - `composer install`
- Los stubs de WordPress y WooCommerce se instalarán como `require-dev` y quedarán en `vendor/` (no versionar en git).
- Incluir desde el principio:
  - `composer require --dev php-stubs/wordpress-stubs php-stubs/woocommerce-stubs`
- Verificar desde el inicio:
  - `composer.json` con `require-dev` para ambos stubs.
  - `composer.lock` con ambos paquetes en `packages-dev`.
  - carpetas `vendor/php-stubs/wordpress-stubs` y `vendor/php-stubs/woocommerce-stubs`.
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
  4. Tipos de Participantes
  5. Recursos
  6. Calendario Global
  7. Reservas
  8. Ajustes (incluirá el botón "Importar datos de prueba")
- Todas las páginas deben usar las clases y funciones nativas de WordPress (add_menu_page, add_submenu_page, Screen Options, etc.)

La idea es que el plugin sirva para crear reservas hoteleras, reservas de actividades, reservas de transportes, reservas de restaurantes, reservas de eventos, etc.

[El resto de la descripción de casos de uso (bodega, restaurante opción1 y opción2, alquiler de coches, alquiler de habitaciones) se mantiene exactamente igual que en la versión anterior – no la repito aquí por brevedad pero debe estar incluida completa]

TIPOS DE PRODUCTO Y POST PERSONALIZADOS:
- rinac_reserva: tipo de producto WooCommerce personalizado (NO CPT separado). Registrado correctamente con woocommerce_register_product_type y filtro woocommerce_product_class → clase RINAC\Models\ReservaProduct
- rinac_slot → CPT con admin page independiente
- rinac_participant → CPT (precio, fracción, etc.)
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
3. Pestañas `woocommerce-product-data` y settings para productos de tipo rinac_reserva
4. Lógica de disponibilidad y cálculo de capacidad (clase AvailabilityManager)
5. Gestión de recursos y participantes
6. Sistema de pago depósito + hooks de WooCommerce
7. Calendario global admin + listado de reservas + botón "Importar datos de prueba"
8. Concurrencia (quote/hold) para bloqueo temporal antes de confirmar reserva
9. Frontend booking form + FullCalendar integración
10. Templates y overrides
11. Documentación completa (README.md + inline docs)

Detalle Paso 3 (admin UX):
- En lugar de un meta box propio, la configuración del producto `rinac_reserva` se gestiona con pestañas dentro de `woocommerce-product-data`.
- Pestaña `Configuración de producto`:
  - modo de reserva (`rinac_booking_mode`)
  - capacidad base (`_rinac_base_capacity`)
  - capacidad global máxima (`_rinac_capacity_total_max`)
  - depósito (`_rinac_deposit_percentage`)
- Pestaña `Configuración base`:
  - capacidad mínima por reserva (`_rinac_capacity_min_booking`)
- Pestaña `Slots`:
  - selección multivalor de slots permitidos (`_rinac_allowed_slots`)
- Pestaña `Participantes`:
  - selección multivalor de tipos de participante permitidos (`_rinac_allowed_participant_types`)
- Pestaña `Recursos`:
  - selección multivalor de recursos permitidos (`_rinac_allowed_resources`)
- Pestaña `Disponibilidad`:
  - reglas de disponibilidad (`_rinac_availability_rules`, texto o JSON)

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
  - Reserva por fecha y slot.
  - Capacidad por franja.
  - Participantes por tipo con fracción configurable.
  - Recursos opcionales (visita guiada, degustación, etc.).
- Restaurante (opción 1):
  - Reserva por día y slot (p. ej. comida/cena o franjas de 30 minutos).
  - Límite global por franja y límite opcional por slot.
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
  - Calendario individual por producto (cada producto con configuración y disponibilidad independientes).
  - Calendario global como vista agregada/filtrable de múltiples productos, sin forzar una configuración única compartida.

ANEXO: CAMBIOS DE ARQUITECTURA ACORDADOS

1) Modo de reserva por producto (`rinac_booking_mode`)
- Definir un metadato principal para el producto reservable:
  - `date`
  - `date_range`
  - `datetime`
  - `date_range_same_time`
  - `slot_dia`
  - `unidad_rango`
- El cálculo de disponibilidad debe resolverse por estrategia según ese modo.

2) Capacidad en dos niveles
- Capacidad global del producto.
- Capacidad por slot/unidad.
- La capacidad efectiva será el mínimo entre límites aplicables.

3) Participantes formalizados
- `rinac_participant` debe soportar:
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

5) Casos de uso orientativos (no cerrados)
- Los casos de uso son guía funcional y no restringen la configuración final del producto.
- No implementar un metadato `rinac_business_profile`.

6) Concurrencia y bloqueo temporal (quote/hold)
- Incluir endpoint de prevalidación (`rinac_quote_booking`) que:
  - valide disponibilidad,
  - calcule precio preliminar,
  - reserve temporalmente capacidad.
- Confirmar luego con `rinac_create_booking_request` reutilizando el bloqueo vigente.

7) Escalabilidad de datos
- Mantener CPTs para administración y edición.
- Prever tabla técnica para consultas intensivas de disponibilidad y solapes.

ANEXO: ORDEN DE EJECUCIÓN (BACKEND-FIRST)

- Priorizar backend completo antes del frontend:
  1) Base técnica y disponibilidad (pasos 1-4).
  2) Backend de negocio (recursos/participantes, depósito, admin de reservas y concurrencia).
  3) Frontend (formulario + FullCalendar).
  4) Cierre (templates y documentación).

ANEXO: PENDIENTE DE IMPLEMENTACIÓN DE SLOTS (ESTADO ACTUAL)

- Definición operativa de `slot`:
  - Es una franja/unidad reservable reutilizable (`rinac_slot`) asociable a productos `rinac_reserva`.
  - Puede representar hora/franja (p.e. `11:00-12:00`) o unidad según la configuración del producto.

- Lo que falta para cerrar Slots end-to-end:
  1) Campos propios de slot en admin:
     - Añadir metadatos editables para `rinac_slot` (inicio, fin/etiqueta, capacidad máxima, capacidad mínima opcional, prioridad, activo/inactivo).
  2) Validaciones de negocio en slot:
     - Validar coherencia de datos (rangos de hora, mínimos/máximos y estados).
     - Validar solapes cuando aplique y compatibilidad con `rinac_booking_mode`.
  3) Contrato de datos y persistencia:
     - Formalizar meta keys de slot y su lectura/escritura en una capa consistente.
  4) Disponibilidad avanzada por slot:
     - Completar reglas por modo (`date`, `datetime`, `slot_dia`, `unidad_rango`, etc.) usando `slot_id`.
     - Definir precedencia clara entre capacidad global y slot.
  5) Frontend de selección real:
     - Exponer slots disponibles por fecha en AJAX y pintar selector con estados (disponible/completo/no permitido).
  6) Operación en backoffice:
     - Mejorar listado de slots con columnas y filtros (franja, capacidad, ocupación, estado, producto).
  7) Concurrencia:
     - Endurecer control de colisiones por `slot_id` con `quote/hold` en alta concurrencia.
  8) Pruebas y documentación:
     - Cubrir tests de capacidad/solapes/bloqueos por slot y documentar configuración funcional para admin.

- Priorización recomendada (sin romper lo ya hecho):
  1) Meta boxes/validaciones de `rinac_slot`.
  2) Endpoints con respuesta real de slots disponibles.
  3) Selector frontend de slots.
  4) Tests de concurrencia y regresión.

ANEXO: PENDIENTE DE IMPLEMENTACIÓN DE TIPOS DE PARTICIPANTE (ESTADO ACTUAL)

- Definición operativa de `tipo de participante`:
  - Es una entidad (`rinac_participant`) que define cómo computa una persona en capacidad y precio.
  - Ejemplos: adulto, niño, bebé, senior, etc.

- Lo que falta para cerrar Tipos de participante end-to-end:
  1) UI de campos en admin (`rinac_participant`):
     - Añadir metadatos editables: etiqueta pública, fracción de capacidad, tipo de precio, valor de precio, mínimos/máximos por tipo, estado activo y orden.
  2) Validaciones al guardar:
     - Validar fracción > 0, `price_type` permitido, `price_value` >= 0 y coherencia de mínimos/máximos.
  3) Validaciones en reserva/quote:
     - Validar que el tipo exista, esté activo y esté permitido en el producto (`_rinac_allowed_participant_types`).
     - Aplicar límites por tipo y validar consistencia global de capacidad solicitada.
  4) Motor de precio por estrategia:
     - Formalizar cálculo por `price_type` (al menos `free` y `fixed`; extensible a futuros tipos).
  5) Exposición frontend:
     - Exponer en AJAX los tipos permitidos con sus reglas (fracción, precio, límites y estado).
     - Pintar controles de cantidad por tipo y recalcular en vivo capacidad consumida y total estimado.
  6) UX y errores:
     - Mensajes claros para tipo no permitido/inactivo, límites por tipo y falta de capacidad.
  7) Calidad:
     - Tests unitarios e integración para normalización, capacidad, precio y validaciones.

- Criterio de “completo” para tipos de participante:
  - Admin puede gestionar todos los campos clave.
  - Producto limita tipos permitidos.
  - Quote/create booking valida reglas y límites.
  - Frontend refleja y recalcula correctamente.
  - Tests cubren reglas críticas.

- Priorización recomendada (sin romper lo ya hecho):
  1) Meta boxes + validaciones de `rinac_participant`.
  2) Validación estricta en `quote/create booking`.
  3) Endpoint/frontend con selector y cálculo en vivo.
  4) Tests y hardening final.

ANEXO: PENDIENTE DE IMPLEMENTACIÓN DE RECURSOS (ESTADO ACTUAL)

- Definición operativa de `recurso`:
  - Es una entidad (`rinac_resource`) asociable al producto reservable.
  - Puede ser `addon` (extra) o `unit` (unidad reservable).

- Lo que falta para cerrar Recursos end-to-end:
  1) UI de campos en admin (`rinac_resource`):
     - Añadir metadatos editables: tipo de recurso (`addon`/`unit`), política de precio, valor de precio, activo/inactivo, orden y límites opcionales por cantidad.
  2) Validaciones al guardar:
     - Validar `resource_type` permitido.
     - Validar `price_policy` permitido (`none`, `fixed`, `per_person`, `per_day`, `per_night`).
     - Validar `price_value` >= 0 y coherencia de límites.
  3) Validaciones en reserva/quote:
     - Validar que el recurso exista, esté activo y esté permitido en el producto (`_rinac_allowed_resources`).
     - Aplicar límites por recurso y reglas por modo/perfil.
  4) Motor de precio por estrategia:
     - Formalizar cálculo por política de precio (`none`, `fixed`, `per_person`, `per_day`, `per_night`).
  5) Exposición frontend:
     - Exponer en AJAX los recursos permitidos con sus reglas (tipo, política, precio, límites y estado).
     - Pintar selector de recursos (selección simple o cantidad) con recálculo en vivo.
  6) UX y errores:
     - Mensajes claros para recurso no permitido/inactivo, límites superados y reglas incompatibles.
  7) Calidad:
     - Tests unitarios e integración para normalización, validaciones y cálculo por política.

- Criterio de “completo” para recursos:
  - Admin puede gestionar campos clave de recurso.
  - Producto limita recursos permitidos.
  - Quote/create booking valida reglas y límites.
  - Frontend refleja selección y precio en vivo.
  - Tests cubren reglas críticas.

- Priorización recomendada (sin romper lo ya hecho):
  1) Meta boxes + validaciones de `rinac_resource`.
  2) Validación estricta en `quote/create booking`.
  3) Endpoint/frontend con selector y cálculo en vivo.
  4) Tests y hardening final.

ANEXO: MATRIZ DE META KEYS (CONTRATO CONGELADO)

- Convención final de naming (cerrada):
  - Se mantiene `_rinac_pt_*` para tipos de participante por compatibilidad con el código actual.
  - Se usa `_rinac_slot_*` para campos específicos de slot.
  - Se usa `_rinac_resource_*` para campos específicos de recurso.
  - Excepción histórica permitida: `_rinac_capacity_max`/`_rinac_capacity_min` en slot.

- Slot (`rinac_slot`):
  - `_rinac_slot_label` (string): etiqueta pública opcional (fallback al título del post).
  - `_rinac_slot_start_time` (string `HH:MM`): hora inicio opcional para franjas horarias.
  - `_rinac_slot_end_time` (string `HH:MM`): hora fin opcional para franjas horarias.
  - `_rinac_capacity_max` (int >= 0): capacidad máxima del slot.
  - `_rinac_capacity_min` (int >= 0): capacidad mínima opcional del slot.
  - `_rinac_slot_is_active` (bool 0/1): estado activo.
  - `_rinac_slot_sort_order` (int >= 0): orden de visualización.
  - Regla: si se informan inicio/fin, `start < end`.

- Tipo de participante (`rinac_participant`):
  - `_rinac_pt_label` (string): etiqueta pública opcional (fallback al título).
  - `_rinac_pt_capacity_fraction` (float > 0): fracción de capacidad consumida por unidad.
  - `_rinac_pt_price_type` (enum): `free`, `fixed` (extensible en el futuro).
  - `_rinac_pt_price_value` (decimal >= 0): valor de precio según estrategia.
  - `_rinac_pt_min_qty` (int >= 0): mínimo opcional por reserva.
  - `_rinac_pt_max_qty` (int >= 0): máximo opcional por reserva.
  - `_rinac_pt_is_active` (bool 0/1): estado activo.
  - `_rinac_pt_sort_order` (int >= 0): orden de visualización.
  - Regla: si min/max existen, `min <= max`.

- Recurso (`rinac_resource`):
  - `_rinac_resource_label` (string): etiqueta pública opcional (fallback al título).
  - `_rinac_resource_type` (enum): `addon`, `unit`.
  - `_rinac_resource_price_policy` (enum): `none`, `fixed`, `per_person`, `per_day`, `per_night`.
  - `_rinac_resource_price_value` (decimal >= 0): valor de precio según política.
  - `_rinac_resource_min_qty` (int >= 0): mínimo opcional por reserva.
  - `_rinac_resource_max_qty` (int >= 0): máximo opcional por reserva.
  - `_rinac_resource_is_active` (bool 0/1): estado activo.
  - `_rinac_resource_sort_order` (int >= 0): orden de visualización.
  - Regla: si min/max existen, `min <= max`.

- Producto `rinac_reserva` (relación con entidades):
  - `_rinac_allowed_slots` (array<int>): IDs permitidos de `rinac_slot`.
  - `_rinac_allowed_participant_types` (array<int>): IDs permitidos de `rinac_participant`.
  - `_rinac_allowed_resources` (array<int>): IDs permitidos de `rinac_resource`.
  - Regla: en quote/create booking solo se admiten IDs activos y permitidos en estas listas.