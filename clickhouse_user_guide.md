# Guía para Crear Usuario en ClickHouse

## Problema
El usuario `phoenix` no existe o la contraseña es incorrecta en ClickHouse.

## Solución

### Opción 1: Usar el script automatizado

```bash
chmod +x create_clickhouse_user.sh
sudo ./create_clickhouse_user.sh
```

### Opción 2: Crear el usuario manualmente

1. **Conectarse a ClickHouse como administrador:**
```bash
clickhouse-client --host localhost --port 9000
```

2. **Verificar si el usuario existe:**
```sql
SELECT name, auth_type FROM system.users WHERE name = 'phoenix';
```

3. **Si el usuario NO existe, créalo:**
```sql
CREATE USER IF NOT EXISTS phoenix IDENTIFIED WITH sha256_password BY 'Solid256!';
```

4. **Otorgar permisos sobre la base de datos:**
```sql
GRANT ALL ON POM_Aplicaciones.* TO phoenix;
GRANT SHOW DATABASES ON *.* TO phoenix;
```

5. **Verificar permisos:**
```sql
SHOW GRANTS FOR phoenix;
```

6. **Probar la conexión:**
```bash
clickhouse-client --host localhost --port 9000 --user phoenix --password 'Solid256!' --query "SELECT 1"
```

### Opción 3: Si el usuario existe pero la contraseña es incorrecta

```sql
ALTER USER phoenix IDENTIFIED WITH sha256_password BY 'Solid256!';
```

### Opción 4: Editar archivo de configuración directamente

Si no puedes acceder como administrador, puedes editar el archivo de configuración:

1. **Editar el archivo de usuarios:**
```bash
sudo nano /etc/clickhouse-server/users.xml
```

2. **Agregar el usuario (dentro de `<users>`):**
```xml
<phoenix>
    <password_sha256_hex>HASH_AQUI</password_sha256_hex>
    <networks>
        <ip>::/0</ip>
    </networks>
    <databases>
        <database>POM_Aplicaciones</database>
    </databases>
    <allow_databases>
        <database>POM_Aplicaciones</database>
    </allow_databases>
</phoenix>
```

3. **Para generar el hash SHA256 de la contraseña:**
```bash
echo -n 'Solid256!' | sha256sum | tr -d ' -'
```

4. **Reiniciar ClickHouse:**
```bash
sudo systemctl restart clickhouse-server
```

## Verificación Final

Después de crear el usuario, prueba la conexión desde PHP:

```bash
# Desde el servidor ClickHouse
clickhouse-client --host localhost --port 9000 --user phoenix --password 'Solid256!' --query "SELECT * FROM POM_Aplicaciones.PC_Catalogo_Filtros_Telefono LIMIT 1"
```

Si funciona, entonces el archivo `test_clickhouse.php` debería funcionar también.

## Notas Importantes

- El puerto **9000** es para el cliente nativo de ClickHouse
- El puerto **8123** es para HTTP API (lo que usa PHP)
- La autenticación debe funcionar en ambos puertos si el usuario está configurado correctamente
- Si cambias la contraseña, asegúrate de actualizarla también en el archivo de prueba PHP
