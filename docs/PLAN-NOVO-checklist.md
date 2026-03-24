## PLAN-NOVO-checklist (RINAC)

IMPORTANTE: cada vez que se modifique el plan de trabajo, hay que actualizar también este archivo `PLAN-NOVO-checklist.md` y `GROK.md` para mantenerlos sincronizados.

### Pasos del desarrollo (checklist)

1. [x] Configuración inicial: `composer.json`, `rinac.php`, PSR-4, activación/desactivación, registro `rinac_reserva`, registro CPTs + menú “RINAC” + datos mínimos demo en activación
2. [x] Clase `AjaxHandler` centralizada + ejemplo de 3 endpoints (`rinac_get_availability`, `rinac_get_calendar_events`, `rinac_create_booking_request`)
3. [x] Meta boxes y settings para productos `rinac_reserva`
4. [x] Lógica de disponibilidad y cálculo de capacidad (clase `AvailabilityManager`)
5. [x] Gestión de recursos y participantes
6. [x] Sistema de pago depósito + hooks de WooCommerce
7. [x] Calendario global admin + listado de reservas + botón “Importar datos de prueba”
8. [x] Control de concurrencia y bloqueos temporales (quote/hold antes de confirmar reserva)
9. [ ] Frontend booking form + FullCalendar integración
10. [ ] Templates y overrides
11. [ ] Documentación completa (`README.md` + inline docs)

### Casos de uso incluidos (alcance funcional)

- [x] Bodega: reserva por día/slot con capacidad por franja.
- [x] Restaurante (opción 1): reservas por turno y día.
- [x] Restaurante (opción 2): reservas por mesa/unidad.
- [x] Alquiler de coches: reservas por día o rango de fechas.
- [x] Alquiler de habitaciones: reservas por día o rango de fechas.
- [x] Interfaz de administración unificada para todos los productos reservables.

### Cambios de diseño acordados

- [x] Añadir `rinac_booking_mode` para soportar múltiples modos de reserva por producto.
- [x] Separar capacidad global del producto y capacidad por slot/turno/unidad.
- [x] Formalizar tipos de participante con fracción de capacidad y reglas de precio.
- [x] Formalizar recursos como `addon` o `unit` con política de precio.
- [x] Añadir “perfil de negocio” para simplificar configuración del admin.
- [x] Introducir flujo quote/hold para reducir sobreserva en concurrencia.
- [x] Mantener CPT para gestión y prever tabla técnica para consultas intensivas de disponibilidad.
- [x] Aclaración de calendario: cada producto tiene calendario/configuración propios y el calendario global es una vista agregada.

### Reglas clave (recordatorio)
- `text_domain` cargado **exclusivamente** dentro de `init` (nunca antes).
- Callbacks pasados a `add_menu_page`/`add_submenu_page` y a `add_action`/`add_filter` deben ser `public` si se pasan como `array($obj, 'metodo')` (evitar `private/protected`).
- Si un lint se queja de `$_POST`/`$_GET`/`$_REQUEST`: no usar `$GLOBALS`, copiar primero a variables locales con validación de tipo (y opcionalmente `@noinspection`).
- Stubs de WordPress y WooCommerce: instalar como `require-dev` en `vendor/` (no versionar).
- Desde el inicio del proyecto: ejecutar `composer install` y añadir stubs con `composer require --dev php-stubs/wordpress-stubs php-stubs/woocommerce-stubs`.

