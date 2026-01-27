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
10. [Soporte](#soporte)

---

## Descripción General

Phoenix es un sistema de gestión de reportes y análisis de datos que permite crear, gestionar y visualizar reportes desde múltiples fuentes de datos. El sistema soporta conexiones a diferentes tipos de bases de datos incluyendo MySQL, SQL Server, ClickHouse y Oracle.

---

## Requisitos del Sistema

### Sistema Operativo
- Linux 9 (Oracle Linux o CentOS)
- Arquitectura: ARM64 (AArch64) o Intel X86_64
- Recomendado: Oracle Cloud Infrastructure (OCI)

### Software Requerido
- Apache 2.4 (httpd)
- MySQL Server
- PHP 8.2 con soporte para OCI8
- Git
- Oracle Instant Client (para conexiones Oracle)

### Conocimientos Previos
- Uso básico de terminal en Linux
- Configuración de Apache, MySQL y PHP
- Gestión de servidores y DNS

### Configuración Previa del Sistema

Antes de comenzar la instalación, configura SELinux en modo permisivo:

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

### Paso 1: Actualizar el Sistema

```bash
sudo dnf update -y
```

### Paso 2: Instalar Apache

```bash
sudo dnf install httpd -y
sudo systemctl start httpd
sudo systemctl enable httpd
```

### Paso 3: Instalar MySQL

```bash
sudo dnf install mysql-server -y
sudo systemctl start mysqld
sudo systemctl enable mysqld
sudo mysql_secure_installation
```

### Paso 4: Instalar PHP con Soporte OCI8

1. Instalar PHP y módulos necesarios:

```bash
sudo dnf install php php-mysqlnd php-pear php-devel php-cli php-common php-fpm -y
```

2. Instalar Oracle Instant Client:

   - Descargar el Basic Package en formato RPM desde [Oracle Instant Client Downloads](https://www.oracle.com/database/technologies/instant-client/downloads.html)
   - Instalar el paquete RPM descargado

3. Instalar OCI8:

```bash
sudo pecl install oci8
```

   Durante la instalación, proporciona la ruta del Oracle Instant Client (normalmente `/usr/lib/oracle/<version>/client64/lib`)

4. Configurar PHP:

   Editar `/etc/php.ini` y agregar:

```ini
extension=oci8.so
```

5. Reiniciar Apache:

```bash
sudo systemctl restart httpd
```

### Paso 5: Configurar Firewall

```bash
sudo firewall-cmd --permanent --add-service=http
sudo firewall-cmd --permanent --add-service=https
sudo firewall-cmd --permanent --add-service=mysql
sudo firewall-cmd --reload
```

### Paso 6: Verificar Instalación

Crear un archivo de prueba:

```bash
echo "<?php phpinfo(); ?>" | sudo tee /var/www/html/phpinfo.php
```

Acceder a `http://<tu_ip>/phpinfo.php` y verificar que OCI8 aparezca en los módulos cargados.

---

## Instalación del Código Fuente

1. Instalar Git:

```bash
sudo dnf install git -y
```

2. Navegar al directorio de trabajo:

```bash
cd /var/www
```

3. Clonar el repositorio:

```bash
git clone https://[usuario_bitbucket]@bitbucket.org/project/phoenix.git
```

El código fuente se instalará en `/var/www/phoenix`.

---

## Configuración del VirtualHost

### Paso 1: Configurar DNS

Asegúrate de que el dominio apunte a la IP pública del servidor mediante un registro A en tu proveedor de DNS.

### Paso 2: Crear Configuración de VirtualHost

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

### Paso 3: Reiniciar Apache

```bash
sudo systemctl restart httpd
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
sudo chown -R apache:apache /var/www/phoenix
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
├── tools/            # Herramientas adicionales
├── views/           # Vistas y plantillas
├── config.php       # Configuración principal
└── index.php        # Punto de entrada
```

---

## Características Principales

### Gestión de Reportes
- Creación y edición de reportes con consultas SQL personalizadas
- Soporte para múltiples tipos de bases de datos
- Filtrado, agrupación y agregación de datos
- Exportación a Excel

### Gestión de Conexiones
- Configuración de múltiples conexiones a bases de datos
- Soporte para MySQL, SQL Server, ClickHouse y Oracle
- Prueba de conectividad en tiempo real
- Estadísticas de tablas, vistas y procedimientos almacenados

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

## Soporte

Para más información o soporte técnico, contactar al equipo de desarrollo.

---

**Versión:** 1.0  
**Última actualización:** 2024
