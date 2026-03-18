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
- [x] **Paso 7 — Eliminar `require_once`/`include`**: eliminar cargas manuales y depender solo de Composer.
    - [x] `rinac.php` deja de incluir `includes/*.php` y usa instancias `\Rinac\...`.
    - [x] Mover `includes/functions.php` a `src/functions.php` y cargarlo con `autoload.files` en `composer.json`.
    - [x] Completar implementación de `src/Frontend/Frontend.php` y `src/Validation/Validation.php` (sin heredar de clases legacy).
- [x] **Paso 8 — Limpieza legacy**:
    - [x] Eliminada la carpeta `includes/` y todo el código legacy (incluyendo puentes `class_alias`).
    - [x] Scripts auxiliares actualizados para Composer: `test-load.php`, `reset-plugin.php`, `check-status.php`.
- [ ] **(Opcional) Stubs de desarrollo**: añadir stubs en `require-dev` para que el IDE resuelva WordPress/WooCommerce sin falsos positivos.
- [ ] **Paso 9 — Validación final**: activar/desactivar plugin, revisar admin/frontend, y verificar que no hay `Class not found` ni avisos críticos.

## Notas importantes

- **WooCommerce no se incorpora a Composer**: se usa como dependencia externa disponible cuando WooCommerce está activo (con `class_exists()` donde aplique).
- **`vendor/` no se versiona**: es obligatorio ejecutar `composer install --no-dev` en el entorno de despliegue.

## Estado actual (resumen)

- Hay `composer.json` con PSR-4 `Rinac\\` → `src/`.
- Existe `src/Plugin.php` con `namespace Rinac;`.
- Existe `.gitignore` ignorando `/vendor/`.
- `rinac.php` depende de `vendor/autoload.php` y usa `\Rinac\...` (sin `includes()`).
- `src/functions.php` se carga por Composer (`autoload.files`).
- Paso 6, 7 y 8 completados.

## Commits relacionados

- Paso 1–6: commit `2a8d5c8`
- Paso 7: commit `c5b8e6a`
 - Paso 8: (pendiente de commit)

