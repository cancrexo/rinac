# Migración a Composer y Namespaces (`Rinac\`)

Objetivo: migrar el plugin a una estructura moderna con Composer, autoload PSR-4 y clases con namespace `Rinac\`, eliminando `require_once`/`include` del código del plugin.

## Checklist de pasos

- [x] **Paso 1 — Definir namespace base**: usar `Rinac\` como namespace raíz del plugin.
- [x] **Paso 2 — Añadir Composer (PSR-4)**: crear `composer.json` con `autoload.psr-4` mapeando `Rinac\\` a `src/`.
- [x] **Paso 3 — Crear estructura `src/`**: crear carpeta `src/` y una primera clase namespaced (`Rinac\Plugin`) como base.
- [x] **Paso 4 — Excluir `vendor/` del repo**: añadir `.gitignore` para `/vendor/` (el proyecto se despliega ejecutando `composer install --no-dev`).
- [x] **Paso 5 — Bootstrap del plugin con autoload**: actualizar `rinac.php` para cargar `vendor/autoload.php` y arrancar `Rinac\Plugin` (sin tocar todavía la lógica interna).
- [x] **Paso 6 — Migración incremental de clases**: mover cada clase de `includes/` a `src/` con namespaces y actualizar referencias.
    - [x] Migrar `RINAC_Template_Helper` → `Rinac\Template\TemplateHelper` (con puente `class_alias` en `includes/`).
    - [x] Migrar `RINAC_Install` → `Rinac\Install\Install` (puente `class_alias` en `includes/`).
    - [x] Migrar `RINAC_Calendar` → `Rinac\Calendar\Calendar` (puente `class_alias` en `includes/`).
    - [x] Migrar `RINAC_Database` → `Rinac\Database\Database` (puente `class_alias` en `includes/`).
    - [x] Migrar `RINAC_Admin` → `Rinac\Admin\Admin` (puente `class_alias` en `includes/`).
    - [x] Migrar `RINAC_Product_Type` → `Rinac\Product\ProductType` (puente `class_alias` en `includes/`).
    - [x] Migrar `RINAC_Frontend` → `Rinac\Frontend\Frontend` (puente `class_alias` en `includes/`).
    - [x] Migrar `RINAC_Validation` → `Rinac\Validation\Validation` (puente `class_alias` en `includes/`).
- [ ] **Paso 7 — Eliminar `require_once`/`include`**: cuando todas las clases estén en `src/` y autoload funcione, eliminar las cargas manuales.
- [ ] **Paso 8 — Compatibilidad WooCommerce**: mantener llamadas a clases externas como `\Automattic\WooCommerce\...` sin incorporarlas como dependencias en Composer.
- [ ] **Paso 9 — Validación final**: activar/desactivar plugin, revisar admin/frontend, y verificar que no hay `Class not found` ni avisos críticos.

## Notas importantes

- **WooCommerce no se incorpora a Composer**: se usa como dependencia externa disponible cuando WooCommerce está activo (con `class_exists()` donde aplique).
- **`vendor/` no se versiona**: es obligatorio ejecutar `composer install --no-dev` en el entorno de despliegue.

## Estado actual (resumen)

- Hay `composer.json` con PSR-4 `Rinac\\` → `src/`.
- Existe `src/Plugin.php` con `namespace Rinac;`.
- Existe `.gitignore` ignorando `/vendor/`.
- `rinac.php` ya intenta cargar `vendor/autoload.php` (si existe) y arranca `Rinac\Plugin` de forma segura.
- Paso 6 completado: todas las clases de `includes/` ya tienen equivalente en `src/` con namespace `Rinac\...` y puente `class_alias`.

