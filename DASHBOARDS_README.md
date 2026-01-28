# CRUD de Dashboards - Phoenix

Este módulo permite crear, editar, eliminar y gestionar dashboards y sus widgets en Phoenix.

## Archivos Creados

- `dashboards.php` - Punto de entrada principal
- `controllers/dashboards.php` - Controlador con la lógica de negocio
- `views/dashboards_list.php` - Vista de listado de dashboards
- `views/dashboards_form.php` - Vista de formulario para crear/editar dashboards
- `views/dashboards_widgets.php` - Vista para gestionar widgets de un dashboard

## Cómo Usar

### Acceder al CRUD

Navega a: `http://tu-dominio/phoenix/dashboards.php`

### Crear un Dashboard

1. Haz clic en "Nuevo Dashboard"
2. Completa el formulario:
   - **Título**: Nombre del dashboard (obligatorio)
   - **Descripción**: Descripción opcional
   - **Categoría**: Selecciona la categoría donde aparecerá
   - **Orden**: Orden de aparición en el menú
   - **Estado**: Activo, Inactivo o Mantenimiento
3. Haz clic en "Crear Dashboard"

### Agregar Widgets a un Dashboard

1. Desde el listado, haz clic en el botón "Gestionar Widgets" (ícono de cuadrícula)
2. Haz clic en "Nuevo Widget"
3. Completa el formulario:
   - **Título**: Nombre del widget (obligatorio)
   - **Descripción**: Descripción opcional
   - **Conexión**: Selecciona la conexión a la base de datos (obligatorio)
   - **Query SQL**: Consulta SQL que devolverá los datos (obligatorio)
   - **Diseño**: Tamaño del widget (25%, 50%, 100%, etc.)
   - **Totalizar Eje X/Y**: Activa si quieres mostrar totales
   - **Orden**: Orden de aparición en el dashboard
   - **Estado**: Activo, Inactivo o Mantenimiento
4. Haz clic en "Guardar Widget"

### Editar un Dashboard

1. Desde el listado, haz clic en el botón "Editar" (ícono de lápiz)
2. Modifica los campos necesarios
3. Haz clic en "Actualizar Dashboard"

### Editar un Widget

1. Desde la vista de widgets, haz clic en el botón "Editar" del widget
2. Modifica los campos necesarios
3. Haz clic en "Guardar Widget"

### Eliminar un Dashboard

1. Desde el listado, haz clic en el botón "Eliminar" (ícono de basura)
2. Confirma la eliminación
3. **Nota**: Esto también eliminará todos los widgets asociados

### Eliminar un Widget

1. Desde la vista de widgets, haz clic en el botón "Eliminar" del widget
2. Confirma la eliminación

### Ver un Dashboard

1. Desde el listado, haz clic en el botón "Ver" (ícono de ojo)
2. O navega directamente a `reports.php?Id=[ReportsId]`

## Estructura de Datos

### Dashboard (TypeId = 2)
- `ReportsId`: ID único del dashboard
- `Title`: Título del dashboard
- `Description`: Descripción
- `CategoryId`: ID de la categoría
- `TypeId`: Siempre 2 para dashboards
- `ParentId`: Siempre 0 para dashboards
- `Status`: Estado (1=Activo, 0=Inactivo, 2=Mantenimiento)

### Widget (TypeId = 1)
- `ReportsId`: ID único del widget
- `Title`: Título del widget
- `Description`: Descripción
- `ParentId`: ID del dashboard padre (obligatorio)
- `ConnectionId`: ID de la conexión a la base de datos
- `Query`: Consulta SQL
- `LayoutGridClass`: Clase CSS para el tamaño (col-md-6, col-md-4, etc.)
- `TotalAxisX`: Totalizar eje X (0=No, 1=Sí)
- `TotalAxisY`: Totalizar eje Y (0=No, 1=Sí)
- `Order`: Orden de aparición
- `Status`: Estado (1=Activo, 0=Inactivo, 2=Mantenimiento)

## Opciones de Diseño (LayoutGridClass)

- `col`: Predeterminado (ancho automático)
- `col-md-3`: 25% del ancho
- `col-md-4`: 33% del ancho
- `col-md-6`: 50% del ancho
- `col-md-8`: 66% del ancho
- `col-md-9`: 75% del ancho
- `col-md-12`: 100% del ancho
- `col-auto`: Ancho automático según contenido

## Notas Importantes

1. **Conexiones**: Asegúrate de tener conexiones configuradas antes de crear widgets
2. **Queries SQL**: Las consultas deben ser válidas y devolver datos compatibles con el sistema
3. **Permisos**: El usuario debe tener permisos adecuados para crear/editar reportes
4. **Eliminación**: La eliminación de un dashboard elimina todos sus widgets automáticamente
5. **Orden**: Los widgets se muestran según el campo `Order` (ascendente)

## Ejemplo de Query SQL para Widget

```sql
SELECT 
    DATE_FORMAT(fecha, '%Y-%m') as mes,
    COUNT(*) as total_ventas,
    SUM(monto) as monto_total
FROM ventas
WHERE fecha >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
GROUP BY DATE_FORMAT(fecha, '%Y-%m')
ORDER BY mes DESC
```

## Solución de Problemas

### El dashboard no muestra widgets
- Verifica que los widgets tengan `Status = 1` (Activo)
- Verifica que el `ParentId` del widget coincida con el `ReportsId` del dashboard
- Verifica que las queries SQL sean válidas y devuelvan datos

### Error al guardar
- Verifica que todos los campos obligatorios estén completos
- Verifica que la conexión seleccionada exista y esté activa
- Verifica los permisos del usuario

### Los widgets no se muestran en el orden correcto
- Ajusta el campo `Order` de cada widget
- Los widgets se ordenan de menor a mayor según el campo `Order`
