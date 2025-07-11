
# Esturctura carpetas del plugin Rinac para WooCommerce
rinac/
├── rinac.php (archivo principal)
├── includes/
│   ├── class-rinac-product-type.php
│   ├── class-rinac-admin.php
│   ├── class-rinac-frontend.php
│   ├── class-rinac-calendar.php
│   └── class-rinac-validation.php
├── assets/
│   ├── css/
│   ├── js/
│   └── images/
└── templates/
    ├── admin/
    │   ├── settings.php
    │   └── calendar.php
    └── frontend/
        ├── product-template.php
        └── cart-template.php


Análisis de Funcionalidades
BACKEND (Administración):

✅ Configuración Global:

MAXIMO_PERSONAS_HORA: Bien pensado como valor por defecto
RANGOS_HORARIOS: Sistema modular excelente
✅ Gestión de Rangos Horarios:

Nombre del rango (30 chars máx.)
Sistema de HORAS dinámico
Drag & drop para ordenación
Campo maximo_personas_slot heredable
✅ Calendario de Disponibilidad:

Rango de 2 años es apropiado
Sistema binario DISPONIBLE (1/0) es eficiente
Fácil de gestionar para administradores
FRONTEND (Compra):

✅ Selección de Parámetros:

Fecha (calendario visual)
Rango horario (select/radio)
Número de personas (input number)
✅ Validación Crítica:

Control de capacidad máxima por hora/día
Validación antes de añadir al carrito
Consideraciones Técnicas Importantes
1. Base de Datos: Necesitarás tablas personalizadas:

2. Hooks de WooCommerce a Utilizar:

woocommerce_product_data_tabs
woocommerce_product_data_panels
woocommerce_add_to_cart_validation
woocommerce_before_single_product_summary
3. Consideraciones de UX:

AJAX para actualizaciones dinámicas
Validación en tiempo real
Feedback visual de disponibilidad
Mejoras Sugeridas
Gestión de Precios Dinámicos: ¿Diferentes precios por rango horario?
Notificaciones: Email de confirmación automático
Lista de Espera: Para fechas completas
Exportación: Para gestionar reservas externamente
Integración con Calendario Externo: Google Calendar, Outlook
Complejidad Técnica
Nivel: Intermedio-Avanzado
Tiempo estimado: 3-4 semanas de desarrollo
Testing crítico: Validación de concurrencia de reservas
