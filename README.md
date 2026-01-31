# Phoenix - Sistema de Reportes y Gestión de Datos

## Índice

1. [Descripción General](#descripción-general)
2. [Requisitos del Sistema](#requisitos-del-sistema)
3. [Instalación del Servidor](#instalación-del-servidor)
4. [Instalación del Código Fuente](#instalación-del-código-fuente)
5. [Configuración del VirtualHost](#configuración-del-virtualhost)
6. [Configuración de la Base de Datos](#configuración-de-la-base-de-datos)
7. [Configuración de la Aplicación](#configuración-de-la-aplicación)
8. [Estructura del Proyecto](#estructura-del-proyecto)
9. [Características Principales](#características-principales)
10. [SQL Server en Linux (producción)](#sql-server-en-linux-producción)
11. [Soporte](#soporte)

---

## Descripción General

Phoenix es un sistema de gestión de reportes y análisis de datos que permite crear, gestionar y visualizar reportes desde múltiples fuentes de datos. El sistema soporta conexiones a diferentes tipos de bases de datos incluyendo MySQL, SQL Server, ClickHouse y Oracle.

Phoenix está diseñado para funcionar en cualquier infraestructura, desde servidores locales hasta entornos cloud, y ha sido probado exitosamente en diferentes distribuciones Linux incluyendo Debian minimal.

---

## Requisitos del Sistema

### Sistema Operativo
- Linux (cualquier distribución moderna)
- Probado y funcionando en:
  - Debian (incluyendo Debian minimal)
  - Oracle Linux / CentOS / RHEL
  - Ubuntu
  - Otras distribuciones basadas en Debian o RedHat

### Arquitectura
- x86 (32 bits)
- x64 (64 bits)
- ARM64 (AArch64)

Phoenix funciona correctamente en todas estas arquitecturas.

### Software Requerido
- Apache 2.4 (httpd) o Nginx
- MySQL Server o MariaDB
- PHP 8.3 (también compatible con PHP 8.2)
- Git
- Oracle Instant Client (solo para conexiones Oracle, opcional)

### Bases de Datos Soportadas
Phoenix soporta conexiones a múltiples tipos de bases de datos:
- **MySQL / MariaDB** - Soporte completo
- **SQL Server** - Soporte completo (en Linux requiere [Microsoft ODBC Driver for SQL Server](#sql-server-en-linux-producción))
- **ClickHouse** - Soporte completo
- **Oracle** - Requiere Oracle Instant Client

### Conocimientos Previos
- Uso básico de terminal en Linux
- Configuración de Apache, MySQL y PHP
- Gestión de servidores y DNS

### Configuración Previa del Sistema

**Nota:** La configuración de SELinux solo aplica para sistemas basados en RedHat (CentOS, Oracle Linux, RHEL). En sistemas basados en Debian no es necesario.

Para sistemas RedHat, antes de comenzar la instalación, configura SELinux en modo permisivo:

```bash
sudo setenforce 0
sudo nano /etc/selinux/config
```

Establece `SELINUX=permissive` y luego:

```bash
sudo touch /.autorelabel && sudo reboot
```

---

## Instalación del Servidor

### Para Sistemas basados en RedHat (CentOS, Oracle Linux, RHEL)

#### Paso 1: Actualizar el Sistema

```bash
sudo dnf update -y
```

#### Paso 2: Instalar Apache

```bash
sudo dnf install httpd -y
sudo systemctl start httpd
sudo systemctl enable httpd
```

#### Paso 3: Instalar MySQL

```bash
sudo dnf install mysql-server -y
sudo systemctl start mysqld
sudo systemctl enable mysqld
sudo mysql_secure_installation
```

#### Paso 4: Instalar PHP

```bash
sudo dnf install php php-mysqlnd php-pear php-devel php-cli php-common php-fpm -y
```

#### Paso 5: Instalar Git

```bash
sudo dnf install git -y
```

### Para Sistemas basados en Debian (Debian, Ubuntu)

#### Paso 1: Actualizar el Sistema

```bash
sudo apt update && sudo apt upgrade -y
```

#### Paso 2: Instalar Apache

```bash
sudo apt install apache2 -y
sudo systemctl start apache2
sudo systemctl enable apache2
```

#### Paso 3: Instalar MySQL

```bash
sudo apt install mysql-server -y
sudo systemctl start mysql
sudo systemctl enable mysql
sudo mysql_secure_installation
```

#### Paso 4: Instalar PHP

```bash
sudo apt install php php-mysql php-pear php-dev php-cli php-common php-fpm -y
```

#### Paso 5: Instalar Git

```bash
sudo apt install git -y
```

### Instalación de Oracle Instant Client (Opcional)

Solo es necesario si planeas usar conexiones a Oracle. Para sistemas RedHat:

1. Descargar el Basic Package en formato RPM desde [Oracle Instant Client Downloads](https://www.oracle.com/database/technologies/instant-client/downloads.html)
2. Instalar el paquete RPM descargado
3. Instalar OCI8:

```bash
sudo pecl install oci8
```

Durante la instalación, proporciona la ruta del Oracle Instant Client (normalmente `/usr/lib/oracle/<version>/client64/lib`)

4. Configurar PHP:

Editar `/etc/php.ini` (o `/etc/php/8.3/apache2/php.ini` en Debian) y agregar:

```ini
extension=oci8.so
```

5. Reiniciar Apache:

```bash
# RedHat
sudo systemctl restart httpd

# Debian
sudo systemctl restart apache2
```

### Instalación de Microsoft ODBC Driver for SQL Server (Opcional)

Solo es necesario si usas conexiones a **SQL Server** en un servidor **Linux**. En Windows el driver suele estar ya disponible; en Linux la extensión PHP `pdo_sqlsrv` depende del driver ODBC instalado en el sistema.

**Debian / Ubuntu:**

```bash
curl https://packages.microsoft.com/keys/microsoft.asc | sudo gpg --dearmor -o /usr/share/keyrings/microsoft-prod.gpg
echo "deb [arch=amd64 signed-by=/usr/share/keyrings/microsoft-prod.gpg] https://packages.microsoft.com/ubuntu/22.04/prod $(lsb_release -cs) main" | sudo tee /etc/apt/sources.list.d/mssql-release.list
sudo apt-get update
sudo ACCEPT_EULA=Y apt-get install -y msodbcsql18
```

*(En Ubuntu 20.04 usar `20.04` en la URL del repositorio.)*

**RHEL / CentOS / Rocky:**

```bash
curl https://packages.microsoft.com/config/rhel/8/prod.repo | sudo tee /etc/yum.repos.d/mssql-release.repo
sudo ACCEPT_EULA=Y dnf install -y msodbcsql18
```

Reiniciar Apache (o PHP-FPM) tras la instalación. Para comprobar la conectividad, usar el script de diagnóstico en `tests/sqlserver_diagnostics.php`.

### Configurar Firewall

**Para sistemas RedHat:**

```bash
sudo firewall-cmd --permanent --add-service=http
sudo firewall-cmd --permanent --add-service=https
sudo firewall-cmd --permanent --add-service=mysql
sudo firewall-cmd --reload
```

**Para sistemas Debian:**

```bash
sudo ufw allow 'Apache Full'
sudo ufw allow mysql
```

### Verificar Instalación

Crear un archivo de prueba:

```bash
# RedHat
echo "<?php phpinfo(); ?>" | sudo tee /var/www/html/phpinfo.php

# Debian
echo "<?php phpinfo(); ?>" | sudo tee /var/www/html/phpinfo.php
```

Acceder a `http://<tu_ip>/phpinfo.php` y verificar que PHP esté funcionando correctamente.

---

## Instalación del Código Fuente

1. Navegar al directorio de trabajo:

```bash
cd /var/www
```

2. Clonar el repositorio:

```bash
git clone https://[usuario_bitbucket]@bitbucket.org/project/phoenix.git
```

El código fuente se instalará en `/var/www/phoenix`.

---

## Configuración del VirtualHost

### Paso 1: Configurar DNS

Asegúrate de que el dominio apunte a la IP pública del servidor mediante un registro A en tu proveedor de DNS.

### Paso 2: Crear Configuración de VirtualHost

**Para sistemas RedHat:**

Crear o editar `/etc/httpd/conf.d/vhosts.conf`:

```apache
<VirtualHost *:80>
    ServerAdmin webmaster@local
    ServerName DOMINIO.COM
    DocumentRoot /var/www/phoenix

    ErrorLog /var/log/httpd/phoenix-error.log
    CustomLog /var/log/httpd/phoenix-access.log combined

    <Directory /var/www/phoenix>
        Options Indexes FollowSymLinks
        AllowOverride All
    </Directory>
</VirtualHost>
```

**Para sistemas Debian:**

Crear o editar `/etc/apache2/sites-available/phoenix.conf`:

```apache
<VirtualHost *:80>
    ServerAdmin webmaster@local
    ServerName DOMINIO.COM
    DocumentRoot /var/www/phoenix

    ErrorLog ${APACHE_LOG_DIR}/phoenix-error.log
    CustomLog ${APACHE_LOG_DIR}/phoenix-access.log combined

    <Directory /var/www/phoenix>
        Options Indexes FollowSymLinks
        AllowOverride All
    </Directory>
</VirtualHost>
```

Habilitar el sitio:

```bash
sudo a2ensite phoenix.conf
sudo a2enmod rewrite
```

### Paso 3: Reiniciar Apache

```bash
# RedHat
sudo systemctl restart httpd

# Debian
sudo systemctl restart apache2
```

---

## Configuración de la Base de Datos

### Paso 1: Crear Usuario MySQL

```bash
sudo mysql -u root -p
```

Ejecutar en MySQL:

```sql
CREATE USER 'phoenix'@'%' IDENTIFIED BY 'Solid256!';
GRANT ALL PRIVILEGES ON *.* TO 'phoenix'@'%';
FLUSH PRIVILEGES;
EXIT;
```

### Paso 2: Crear Bases de Datos

```bash
sudo mysql -u root -p
```

Ejecutar en MySQL:

```sql
CREATE DATABASE phoenix;
CREATE DATABASE pipelines;
CREATE DATABASE datawerehouse;
ALTER DATABASE phoenix CHARACTER SET = utf8mb4 COLLATE = utf8mb4_0900_ai_ci;
ALTER DATABASE pipelines CHARACTER SET = utf8mb4 COLLATE = utf8mb4_0900_ai_ci;
ALTER DATABASE datawerehouse CHARACTER SET = utf8mb4 COLLATE = utf8mb4_0900_ai_ci;
EXIT;
```

### Paso 3: Importar Respaldo SQL

```bash
sudo mysql -u phoenix -p phoenix < /var/www/phoenix/sql/phoenix.sql
```

---

## Configuración de la Aplicación

1. Copiar el archivo de configuración de ejemplo:

```bash
cp /var/www/phoenix/config_example.php /var/www/phoenix/config.php
```

2. Editar `config.php` con los datos de conexión a la base de datos y otras configuraciones necesarias.

3. Asegurar permisos adecuados:

```bash
# RedHat
sudo chown -R apache:apache /var/www/phoenix
sudo chmod -R 755 /var/www/phoenix

# Debian
sudo chown -R www-data:www-data /var/www/phoenix
sudo chmod -R 755 /var/www/phoenix
```

---

## Estructura del Proyecto

```
phoenix/
├── assets/          # Recursos estáticos (CSS, JS, imágenes)
├── conn/            # Archivos de conexión a bases de datos
├── controllers/     # Controladores de la aplicación
├── data/            # Datos y archivos CSV
├── functions/       # Funciones auxiliares
├── lib/             # Librerías externas
├── models/          # Modelos de datos
├── scripts/         # Scripts de mantenimiento
├── sql/             # Scripts SQL de base de datos
├── tests/           # Scripts de diagnóstico (SQL Server, etc.)
├── tools/            # Herramientas adicionales
├── views/           # Vistas y plantillas
├── config.php       # Configuración principal
└── index.php        # Punto de entrada
```

---

## Características Principales

### Gestión de Reportes
- Creación y edición de reportes con consultas SQL personalizadas
- Soporte para múltiples tipos de bases de datos (MySQL, SQL Server, ClickHouse, Oracle)
- Filtrado, agrupación y agregación de datos
- Exportación a Excel

### Gestión de Conexiones
- Configuración de múltiples conexiones a bases de datos
- Soporte completo para MySQL, SQL Server y ClickHouse
- Soporte para Oracle (requiere Oracle Instant Client)
- Prueba de conectividad en tiempo real
- Estadísticas de tablas, vistas y procedimientos almacenados
- Creación automática de reportes desde tablas y vistas

### Gestión de Usuarios
- Sistema de autenticación y autorización
- Roles de usuario (Administrador, Usuario estándar)
- Gestión de perfiles y avatares
- Control de acceso a reportes

### Configuración
- Gestión de secciones, grupos y reportes del menú
- Convenciones de nombres de campos
- Enmascaramiento de datos
- Asignación de permisos de acceso

### Modo Debug
- Herramientas de depuración para administradores
- Visualización de consultas SQL ejecutadas
- Información detallada de filtros aplicados

---

## SQL Server en Linux (producción)

En entornos **Linux**, la extensión PHP `pdo_sqlsrv` requiere el **Microsoft ODBC Driver for SQL Server** instalado en el servidor. Sin él, las conexiones a SQL Server fallan con mensajes como *"This extension requires the Microsoft ODBC Driver for SQL Server"* o *"ODBC: could not find driver"*. En Windows el driver suele estar ya disponible; en Linux hay que instalarlo (ver [Instalación de Microsoft ODBC Driver for SQL Server](#instalación-de-microsoft-odbc-driver-for-sql-server-opcional)).

### Requisito
- Instalar el driver ODBC en el servidor y reiniciar Apache o PHP-FPM después.

### Diagnóstico
Script de comprobación (sin usar la UI): `tests/sqlserver_diagnostics.php`

- Uso: `https://tu-dominio/phoenix/tests/sqlserver_diagnostics.php`
- Opcional: `?id=8` para probar solo la conexión con ID 8.

Muestra versión de PHP, extensiones cargadas, estado TCP (host:puerto) y resultado de `class_connSqlServer` y `pdo_sqlsrv` directo para cada conexión SQL Server de la tabla `connections`. Tras instalar el driver, deberías ver **Conexión OK** cuando el servidor SQL sea alcanzable.

### Nota sobre hostnames
Si una conexión usa un hostname de desarrollo (por ejemplo `SRV-DESA\SQLEXPRESS`), en producción puede fallar con *"Temporary failure in name resolution"*: el servidor Linux no resuelve ese nombre. Es esperado si ese SQL Server solo existe en la red de desarrollo; en producción usar la conexión que apunte al host/IP accesible desde el servidor (por ejemplo una IP como 192.168.100.241).

---

## Soporte

Para más información o soporte técnico, contactar al equipo de desarrollo.

---

**Versión:** 1.0  
**Última actualización:** 2025
