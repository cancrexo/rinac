Eres un senior WordPress/WooCommerce developer experto en plugins complejos. Vas a crear desde cero un plugin completo llamado "RINAC : Reservas Is Not Another Calendar" (prefijo rinac_).

REQUISITOS TÉCNICOS OBLIGATORIOS:
- Usa Composer con PSR-4 autoloading (namespace rinac\...)
- Estructura de carpetas estándar moderna:
  rinac/
  ├── composer.json
  ├── plugin.php (punto de entrada)
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
- Centraliza TODOS los endpoints AJAX en una sola clase rinac\Ajax\AjaxHandler (método register() + handle() con nonce y capability check)
- Usa WooCommerce hooks modernos (2024-2025): woocommerce_product_class, woocommerce_add_to_cart_validation, woocommerce_cart_item_name, etc.
- Soporta WP 6.6+ y WooCommerce 9.0+

TIPOS DE POST PERSONALIZADOS (Custom Post Types):
1. rinac_reserva → nuevo tipo de producto WooCommerce ("Reserva")
2. rinac_slot → slots/horarios (mañana, 11:00-12:00, etc.). Admin page independiente con listado + formulario
3. rinac_turno → turnos (comida, cena, etc.). Admin page independiente
4. rinac_participant_type → tipos de participantes (adulto, niño, bebé, senior…). Permite precio, fracción (0.5), etc.
5. rinac_resource → recursos extras (visita guiada, limpieza, parking, chofer…). Precio opcional
6. rinac_booking → reservas confirmadas (relacionadas con order WooCommerce)

FUNCIONALIDADES DEL PRODUCTO "RESERVA":
Cada producto rinac_reserva tendrá su propio calendario de disponibilidad.

Opciones de reserva configurables por producto (meta boxes en el editor):
- Tipo de reserva: fecha única / rango de fechas / fecha + hora / rango de fechas + slot
- Slots asociados (múltiples rinac_slot)
- Turnos asociados (para restaurantes)
- Tipos de participantes permitidos + precios por tipo
- Precio base + precio por participante
- Capacidad del slot:
  - Máximo total de "personas equivalentes" (suma de fracciones)
  - Mínimo y máximo por reserva
  - Overbooking prevention en tiempo real (consulta global de reservas)
- Recursos extras (checkboxes con precio adicional)
- Opción de pago:
  - 100% al reservar
  - Depósito % (configurable) + resto al check-in (se guarda en meta del order)

LÓGICA DE CAPACIDAD Y SOBRERESERVAS (CRÍTICA):
- Cada slot/turno tiene capacidad en "unidades de persona"
- Un niño de 0.5 cuenta como 0.5
- Al validar una reserva nueva se calcula:
  $ocupado_actual = suma_de_todas_las_reservas_del_slot * fraccion_por_tipo
  $capacidad_restante = capacidad_slot - $ocupado_actual
- Si la nueva reserva supera → error "No hay suficiente capacidad"

INTERFAZ DE ADMINISTRACIÓN:
- Panel unificado "RINAC" en el menú admin con pestañas:
  - Productos Reservables
  - Slots (gestor completo)
  - Turnos (gestor completo)
  - Tipos de Participantes
  - Recursos
  - Calendario Global (FullCalendar + filtro por producto)
  - Reservas (listado con filtro)
- Meta boxes avanzados en rinac_reserva (usar CMB2 o React si prefieres)
- Calendar global con vista mes/día y colores por estado (disponible / parcial / completo)

FRONTEND (tienda):
- Reemplaza el formulario de añadir al carrito por un booking form dinámico
- Usa FullCalendar v6 (o Flatpickr + timepicker) según el tipo de reserva
- Selección de fechas → slots/turnos disponibles → participantes → recursos → precio total en vivo (AJAX)
- Todo el flujo de AJAX centralizado en AjaxHandler
- Plantillas overrideables (templates/booking-form.php, etc.)

CASOS DE USO QUE DEBE SOPORTAR SIN MODIFICAR CÓDIGO:
1. Bodega/visitas guiadas → slots por hora + tipos participante + recursos (degustación)
2. Restaurante opción 1 → 1 día + turno (comida/cena) + precio por comensal
3. Restaurante opción 2 → producto = mesa con capacidad X + accesibilidad/alergias (campos extra)
4. Alquiler coches → rango de fechas + producto por modelo + recursos (chofer, limpieza)
5. Hoteles/habitaciones → rango de fechas + producto por tipo de habitación + recursos (parking, desayuno)

FLUJO COMPLETO DE RESERVA:
1. Usuario selecciona producto → ve calendario
2. Elige fecha(s) + slot/turno → ve capacidad restante
3. Elige participantes (adultos, niños…) → precio en vivo
4. Elige recursos opcionales
5. Añade al carrito (como producto variable interno)
6. Checkout normal de WooCommerce
7. Al crear el order → se crea rinac_booking y se actualiza ocupación del slot
8. Opción depósito: woocommerce_order_status_completed o custom hook para cobrar resto

TAREAS QUE TE PIDO (modo Composer paso a paso):
Desarrolla el plugin paso a paso en este orden (responde con cada paso completo y luego espera mi "siguiente"):

1. Configuración inicial: composer.json, plugin.php, PSR-4, activación/desactivación, custom post types (todos)
2. Clase AjaxHandler centralizada + ejemplo de 3 endpoints (disponibilidad, precio en vivo, validar capacidad)
3. Meta boxes y settings del producto rinac_reserva
4. Lógica de disponibilidad y cálculo de capacidad (clase AvailabilityManager)
5. Frontend booking form + FullCalendar integración
6. Gestión de recursos y participantes
7. Sistema de pago depósito + hooks de WooCommerce
8. Calendario global admin + listado de reservas
9. Templates y overrides
10. Documentación completa (README.md + inline docs)

Reglas estrictas:
- Todo código limpio, comentado, con namespaces
- Security: nonces, sanitization, capability checks
- Performance: caches transient para disponibilidad
- Internacionalización (i18n) desde el principio
- No uses plugins externos de reservas (todo custom)

### INSTRUCCIÓN OBLIGATORIA: PLAN DE TRABAJO ANTES DE CODIFICAR
Antes de generar cualquier código, migration, componente o archivo:

1. Crea un PLAN DE TRABAJO detallado y estructurado en formato markdown claro.
    - Lista de fases / hitos recomendados 
   - Orden lógico de implementación (qué debe ir antes de qué).
   - Decisiones clave pendientes o que propones
   - Estimación aproximada de complejidad por fase.
   - Posibles riesgos o puntos de atención

2. Presenta el plan completo y pregúntame si estoy de acuerdo, si quiero modificar algo o si prefiero cambiar el orden/prioridades.

3. SOLO después de que yo apruebe o ajuste el plan, empieza a implementar fase por fase, indicando claramente "Implementando Fase X según plan aprobado" y mostrando el código correspondiente.

4. Al finalizar cada fase importante, resume lo hecho, confirma que funciona con los requisitos y pregunta si continuar a la siguiente.

Sigue estrictamente este flujo: PLAN → APROBACIÓN → IMPLEMENTACIÓN POR FASES.

Empieza YA generando el proyecto paso a paso. Indica claramente cuando termines cada sección con "✅ Backend – Migraciones y Modelos completados", etc.

¡Comienza!