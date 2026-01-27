#!/bin/bash
# Script para crear o verificar el usuario phoenix en ClickHouse

echo "=== Verificación y Creación de Usuario ClickHouse ==="
echo ""

# Verificar si el usuario existe
echo "1. Verificando si el usuario 'phoenix' existe..."
clickhouse-client --host localhost --port 9000 --query "SELECT name, auth_type, host_ip, host_names FROM system.users WHERE name = 'phoenix'" 2>/dev/null

if [ $? -eq 0 ]; then
    USER_EXISTS=$(clickhouse-client --host localhost --port 9000 --query "SELECT count() FROM system.users WHERE name = 'phoenix'" 2>/dev/null)
    
    if [ "$USER_EXISTS" = "1" ]; then
        echo "✅ El usuario 'phoenix' existe."
        echo ""
        echo "2. Verificando permisos del usuario..."
        clickhouse-client --host localhost --port 9000 --query "SHOW GRANTS FOR phoenix" 2>/dev/null
        echo ""
        echo "3. Para cambiar la contraseña del usuario, ejecuta:"
        echo "   clickhouse-client --host localhost --port 9000 --query \"ALTER USER phoenix IDENTIFIED WITH sha256_password BY 'Solid256!'\""
    else
        echo "❌ El usuario 'phoenix' NO existe."
        echo ""
        echo "2. Creando usuario 'phoenix'..."
        echo ""
        
        # Crear el usuario con contraseña SHA256
        clickhouse-client --host localhost --port 9000 --query "CREATE USER IF NOT EXISTS phoenix IDENTIFIED WITH sha256_password BY 'Solid256!'" 2>&1
        
        if [ $? -eq 0 ]; then
            echo "✅ Usuario 'phoenix' creado exitosamente."
            echo ""
            echo "3. Otorgando permisos sobre la base de datos POM_Aplicaciones..."
            
            # Otorgar permisos
            clickhouse-client --host localhost --port 9000 --query "GRANT ALL ON POM_Aplicaciones.* TO phoenix" 2>&1
            clickhouse-client --host localhost --port 9000 --query "GRANT SHOW DATABASES ON *.* TO phoenix" 2>&1
            
            echo "✅ Permisos otorgados."
            echo ""
            echo "4. Verificando permisos..."
            clickhouse-client --host localhost --port 9000 --query "SHOW GRANTS FOR phoenix" 2>&1
        else
            echo "❌ Error al crear el usuario. Verifica que tengas permisos de administrador."
        fi
    fi
else
    echo "❌ No se pudo conectar a ClickHouse. Verifica que el servicio esté corriendo."
    echo "   Intenta: sudo systemctl status clickhouse-server"
fi

echo ""
echo "=== Prueba de Conexión ==="
echo "Probando conexión con el usuario phoenix..."
clickhouse-client --host localhost --port 9000 --user phoenix --password 'Solid256!' --query "SELECT 1 as test" 2>&1

if [ $? -eq 0 ]; then
    echo "✅ Conexión exitosa!"
else
    echo "❌ Error en la conexión. Verifica las credenciales."
fi
