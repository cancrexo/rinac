# RINAC - Guía de Activación Rápida

## ✅ Estado Actual
- ✅ Archivos principales del plugin creados
- ✅ Sistema de plantillas integrado
- ✅ Base de datos preparada
- ✅ Clases PHP implementadas
- ✅ JavaScript y CSS incluidos

## 🚀 Cómo Activar y Probar

### Paso 1: Verificar Estado
```bash
# Acceder a tu sitio web
http://tu-sitio.com/wp-content/plugins/rinac/check-status.php?check=rinac
```

### Paso 2: Activar Plugin
1. Ir al panel de administración de WordPress
2. Menú **Plugins** → **Plugins instalados**
3. Buscar "RINAC" y hacer clic en **Activar**

### Paso 3: Configuración Inicial
1. En el panel admin aparecerá el menú **RINAC**
2. Ir a **RINAC** → **Configuración**
3. Configurar:
   - Máximo personas por hora: `10`
   - Rango del calendario: `2` años
   - Activar notificaciones por email
   - Configurar si el teléfono es obligatorio

### Paso 4: Crear Producto de Prueba
1. Ir a **Productos** → **Añadir nuevo**
2. Nombre: "Visita a la Bodega"
3. En **Datos del producto**:
   - Tipo de producto: Seleccionar **"Visitas"**
4. Configurar pestañas de RINAC:
   - **Configuración RINAC**: Máximo 15 personas por hora
   - **Calendario**: Marcar fechas disponibles
   - **Horarios**: Añadir horarios (ej: 10:00, 11:00, 16:00, 17:00)

### Paso 5: Probar Frontend
1. Ir a la página del producto creado
2. Verificar que aparece el formulario de reserva
3. Probar seleccionar fecha y horario
4. Intentar añadir al carrito

## 🔧 Si Hay Problemas

### Plugin no se activa
- Verificar que WooCommerce esté instalado y activado
- Revisar logs de errores: `/wp-content/debug.log`
- Verificar versión PHP (mínimo 7.4)

### Formulario no aparece
- Verificar que el producto es tipo "Visitas"
- Revisar consola del navegador para errores JavaScript
- Verificar que las plantillas se cargan correctamente

### Base de datos
Si hay errores de base de datos, ejecutar manualmente:
```sql
-- Las tablas se crean automáticamente al activar
-- Verificar en phpMyAdmin que existen:
-- wp_rinac_rangos_horarios
-- wp_rinac_horas  
-- wp_rinac_disponibilidad
-- wp_rinac_producto_horas
-- wp_rinac_reservas
```

### Debug Mode
Activar debug en `wp-config.php`:
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

## 🎯 Funcionalidades para Probar

### Backend (Admin)
- [x] Activación del plugin
- [x] Menú RINAC en admin
- [x] Configuración general
- [x] Tipo de producto "Visitas"
- [x] Configuración de horarios por producto
- [x] Calendario de disponibilidad
- [x] Dashboard con estadísticas

### Frontend (Cliente)
- [x] Formulario de reserva
- [x] Calendario interactivo
- [x] Selección de horarios
- [x] Validación de disponibilidad
- [x] Integración con carrito WooCommerce
- [x] Proceso de checkout

### Plantillas
- [x] Formulario de reserva personalizable
- [x] Modales para reserva rápida
- [x] Dashboard administrativo
- [x] Personalización desde el tema

## 📊 Datos de Prueba

### Horarios de Ejemplo
```
10:00 - Capacidad: 10 personas
11:00 - Capacidad: 10 personas  
12:00 - Capacidad: 15 personas
16:00 - Capacidad: 10 personas
17:00 - Capacidad: 10 personas
18:00 - Capacidad: 8 personas
```

### Productos de Prueba
1. **Visita Básica** - 1 hora, máximo 10 personas
2. **Visita Premium** - 2 horas, máximo 8 personas
3. **Visita Nocturna** - 1.5 horas, máximo 12 personas

## 🔍 Verificaciones Finales

### Checklist de Funcionamiento
- [ ] Plugin se activa sin errores
- [ ] Aparece menú RINAC en admin
- [ ] Se pueden crear productos tipo "Visitas"
- [ ] Formulario aparece en frontend
- [ ] JavaScript funciona (calendario, validaciones)
- [ ] Se pueden hacer reservas de prueba
- [ ] Datos aparecen en carrito
- [ ] Proceso de checkout completo
- [ ] Reservas se guardan en base de datos

### Performance
- [ ] Páginas cargan en menos de 2 segundos
- [ ] No hay errores en consola JavaScript
- [ ] No hay errores PHP en logs
- [ ] Consultas de base de datos optimizadas

## 📞 Soporte

Si encuentras algún problema:

1. **Revisar logs**: `/wp-content/debug.log`
2. **Verificar consola**: F12 en el navegador
3. **Comprobar base de datos**: Las tablas rinac_* deben existir
4. **Probar con tema por defecto**: Para descartar conflictos

## 🚀 ¡Ya está listo para usar!

El plugin RINAC está completamente funcional y listo para gestionar reservas de visitas, tours y experiencias con WooCommerce.
