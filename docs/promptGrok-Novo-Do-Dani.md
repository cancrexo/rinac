Eres un senior WordPress/WooCommerce developer experto en plugins complejos. Vas a crear desde cero un plugin completo llamado "RINAC" (Rinac Is Not Another Calendar) (prefijo rinac_ para todo: post types, meta keys, opciones, AJAX actions, etc.).

REQUISITOS TÉCNICOS OBLIGATORIOS:
- Usa Composer con PSR-4 autoloading (namespace RINAC\...)
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
- Usa WooCommerce hooks modernos (2024-2025): woocommerce_product_class, woocommerce_add_to_cart_validation, woocommerce_cart_item_name, etc.
- Soporta WP 6.6+ y WooCommerce 9.0+

La idea es que el plugin sirva para crear reservas hoteleras, reservas de actividades, reservas de transportes, reservas de restaurantes, reservas de eventos, etc.

_Por ejemplo, para una bodega:_
- el usuario podría crear un producto woocommerce de tipo "RESERVA" (tipo de producto personalizado rinac_reserva). Ejemplo: "Mi visita a la Bodega" con su propio calendario de disponibilidad.
- La reserva se podría realizar ciertos días en ciertos slots (horas) definidos. Por ejemplo "mañana" "tarde" "noche" o bien esos slots llamarlos "11:00 - 12:00", "12:00 - 13:00", "13:00 - 14:00", etc. Eso se definiría a la hora de crear el producto en base a ciertos slots ya definidos previamente aunque se podría crear nuevos slots en cualquier momento. Tendremos que crear un post type rinac_slot y que se puedan crear nuevos slots en cualquier momento. Quizás un gestor de slots en el panel de admin.
- El tipo de participantes lo he definido como adulto y niño pero será mejor definir una serie de tipos de participantes (p.e., adulto, niño, bebé, etc.) y que se puedan definir los precios de cada tipo de participante y la fracción de persona que representa.
- El usuario podría definir los slots y sus capacidades: número máximo/mínimo de personas que participan (indicando adultos, niños, bebés, etc.).
- El usuario podría definir los precios por slot y/o por persona/niño/bebé.
- El usuario podría definir si los niños tienen un precio reducido o gratis e indicar el porcentaje de reducción o el precio reducido.
- El usuario podría definir si los niños computan como 1 persona o como una fracción de persona (p.e., 0.5 persona)
- Los slots tendrán un número de personas máximo, p.e., 10 personas. Los participantes totales serían la suma de los adultos y los niños (fracción! p.e., 0.5 persona) de todas las reservas de ese slot. Queremos evitar sobrereservas. Entonces si se hace una reserva de 4 personas en un slot con capacidad para 10 personas, la siguiente reserva no admitiría más de 6 personas. Por ejemplo, para un slot de 10 personas podemos tener 2 reservas de 4 y 1 reserva de 2 personas (si los límites mínimos así lo permiten). Si se definen varios tipos de participantes, el número total de participantes sería la suma de los participantes de cada tipo.
- Las visitas tendrán ciertos recursos o assets gestionables en el panel de admin que se asignarían a la reserva y que pueden modificar opcionalmente el precio de la reserva. Por ejemplo: Tipo o descripción de la visita. Se podría definir el label o nombre del recurso, su descripción y precio de este recurso que se sumaría al precio de la reserva (opcionalmente). Ejemplos: "Visita Guiada" o "Visita + degustación".

_por ejemplo para un restaurante (opción1):_
- Solo se admitirían reservas de 1 día y en un turno concreto (comida, cena, etc.).
- Indicar el número de comensales máximo por turno.
- Máximo de comensales por slot (opcional o hasta que se complete el número de comensales máximo por turno)
- Indicar el precio por comensal (sería un precio que se sumaría al precio base del producto).

_por ejemplo para un restaurante (opción2):_
- consideraríamos cada producto como una mesa con X comensales
- Se admitirían reservas de 1 día y en un turno concreto (comida, cena, etc.).
- Indicar el número máximo de comensales por mesa
- Indicar el precio por comensal.
- Tendríamos que poder indicar si existen comensales con problemas de accesibilidad (silla de ruedas, etc.), alergias, intolerancias, etc.

- En ambos casos se deberían poder gestionar los turnos (quizás creando un post type rinac_turno y que se puedan crear nuevos turnos en cualquier momento). Quizás un gestor de turnos en el panel de admin. La idea es que el sistema sirva para distintos tipos de establecimientos. Por ejemplo uno que dé comidas en 4 turnos otro que lo haga en 2 turnos, etc.

_por ejemplo para un servicio de alquiler de coches_
- Se admitirían reservas de 1 día o de un rango de fechas determinado.
- En este caso los slots serían las unidades de alquiler de coches disponibles. Tendríamos que crear un producto por cada modelo de coche disponible.
- Tendríamos también recursos/assets que se podrían asignar y modificar el precio de la reserva. Por ejemplo: servicio de limpieza, chofer, etc.

_por ejemplo para alquiler de habitaciones_
- Se admitirían reservas de 1 día o de un rango de fechas determinado.
- En este caso los slots serían las habitaciones disponibles. Tendríamos que crear un producto por cada tipo de habitación disponible.
- Tendríamos también recursos/assets gestionables en el panel de admin que se podrían asignar y que pueden modificar opcionalmente el precio de la reserva. Por ejemplo: servicio de habitación, parking, lavandería, ...

Mi idea es que el mismo interfaz de admin se pueda usar para gestionar todos los productos reservables. Tendríamos que tener un panel de admin para gestionar todos los productos reservables, un calendario global/individual de disponibilidad y reservas.

TIPOS DE PRODUCTO Y POST PERSONALIZADOS:
- rinac_reserva: tipo de producto WooCommerce personalizado (NO CPT separado). Registrado con woocommerce_register_product_type y filtro woocommerce_product_class → clase RINAC\Models\ReservaProduct
- rinac_slot → CPT con admin page independiente
- rinac_turno → CPT con admin page independiente
- rinac_participant_type → CPT (precio, fracción, etc.)
- rinac_resource → CPT (precio opcional)
- rinac_booking → CPT (relacionado con WC orders)

Adicionalmente, implementa manejo de datos de prueba de la siguiente forma:

- En el paso 1 (activación del plugin):
  - Usa register_activation_hook para crear datos MÍNIMOS de prueba SOLO si WP_DEBUG o RINAC_LOAD_DEMO_ON_ACTIVATION
  - Datos mínimos: 3 tipos de participantes, 4 slots, 2 turnos, 3 recursos y 1-2 productos rinac_reserva de ejemplo.

- En un paso posterior (paso 8 o 10): botón "Importar datos de prueba" en RINAC → Ajustes con advertencia roja fuerte:
  "¡ATENCIÓN! Esta acción ELIMINARÁ TODOS los registros existentes... (rinac_slot, rinac_turno, rinac_participant_type, rinac_resource, rinac_booking y TODOS los productos de tipo rinac_reserva)"

TAREAS QUE TE PIDO (modo Composer paso a paso):
Desarrolla el plugin paso a paso en este orden (responde con cada paso completo y luego espera mi "siguiente"):

1. Configuración inicial: composer.json, rinac.php, PSR-4, activación/desactivación, registro del tipo de producto rinac_reserva, registro de CPTs restantes + datos mínimos de prueba en activación
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

Empieza por el paso 1 (composer.json + estructura + registro del tipo de producto rinac_reserva + CPTs + hook de activación con datos mínimos). Cuando termine, dime "SIGUIENTE" y continuamos.