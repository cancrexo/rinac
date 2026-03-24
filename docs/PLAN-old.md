# RINAC - Plan de trabajo por fases

Este documento sigue el flujo acordado: **PLAN -> APROBACION -> IMPLEMENTACION por fases**.

## Decisiones clave

- [x] `rinac_reserva` como **tipo de producto WooCommerce custom** (no CPT separado).
- [ ] Integración checkout/booking (definir estrategia exacta para cart/order item meta y validación).
- [ ] Depósito (%): estrategia de “cobrar el resto” (hook `completed` u otra).

## Fase 1 — Scaffolding + base WP/Woo + CPTs

Objetivo: dejar el plugin “arrancando” con estructura PSR-4 (Composer), namespace y registro de CPTs + product type custom “Reserva”.

- [x] Crear `composer.json` con autoload PSR-4 (`rinac\` -> `src/`).
- [x] Crear `plugin.php` como punto de entrada (carga `vendor/autoload.php` si existe).
- [x] Crear bootstrap `src/Core` con inicialización del plugin.
- [x] Activación/desactivación con `flush_rewrite_rules()`.
- [x] Internacionalización desde el inicio (`load_plugin_textdomain()` apuntando a `languages/`).
- [x] Registrar CPTs:
  - [x] `rinac_slot`
  - [x] `rinac_turno`
  - [x] `rinac_participant`
  - [x] `rinac_resource`
  - [x] `rinac_booking`
- [x] Registrar tipo de producto WooCommerce custom `rinac_reserva` y clase `rinac\Booking\RinacReservationProduct`.
- [x] Ejecutar `composer install` para generar `vendor/autoload.php` (requisito para que el plugin cargue).

## Fase 2 — `AjaxHandler` centralizado + 3 endpoints ejemplo

Objetivo: centralizar endpoints AJAX con nonce + sanitización + control seguro (capability o flujo para invitados).

- [x] Crear `rinac\Ajax\AjaxHandler` con `register()` y enrutado por `action` (un solo action `rinac_ajax` + `endpoint`).
- [x] Endpoint: disponibilidad + capacidad restante (stub pendiente Fase 4).
- [x] Endpoint: precio en vivo (stub pendiente Fase 4).
- [x] Endpoint: validar capacidad (stub pendiente Fase 4).
- [ ] Cache con transients para disponibilidad (cuando aplique).

## Fase 3 — Meta boxes y settings del producto `rinac_reserva`

- [x] Meta boxes para configuración del producto reservable.
- [x] Sanitización/validación `save_post`.

## Fase 4 — Lógica de disponibilidad y cálculo de capacidad (AvailabilityManager)

- [x] Implementar cálculo de ocupación por slot/turno con fracciones.
- [x] Prevención de sobre-reserva en tiempo real.
- [x] Búsqueda de reservas existentes (CPT `rinac_booking`) + caching.

## Fase 5 — Frontend booking form + FullCalendar

- [ ] Plantilla `templates/booking-form.php` (overrideable).
- [ ] Integración FullCalendar (v6) para selección de fechas/slots/turnos.
- [ ] Consumo de endpoints AJAX vía `AjaxHandler`.

## Fase 6 — Gestión de recursos y participantes

- [ ] Persistir selección de participantes (cantidades y fracciones).
- [ ] Persistir recursos con precio adicional.
- [ ] Asegurar coherencia UI->server->order.

## Fase 7 — Sistema de pago depósito + hooks WooCommerce

- [ ] Soportar 100% o depósito (% configurable).
- [ ] Guardar resto/deposito en meta del order.
- [ ] Hook para disparar cobro del resto en el momento acordado.

## Fase 8 — Calendario global admin + listado de reservas

- [ ] Panel unificado “RINAC” con pestañas.
- [ ] Calendario global admin (FullCalendar) + filtro por producto.
- [ ] Listado de reservas con filtros.

## Fase 9 — Templates y overrides

- [ ] Separar lógica y vista para booking form y componentes relacionados.
- [ ] Documentar overrides.

## Fase 10 — Documentación completa

- [ ] `README.md` + inline docs.
- [ ] Guía de configuración inicial (slots/turnos/participantes/recursos + reservas).
- [ ] Flujo reserva->carrito->order->booking->capacidad.

