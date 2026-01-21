# MANUAL PARA LA INSTALACIÓN DE PHOENIX Y SUS DEPENDENCIAS

## REQUISITOS

Antes de comenzar, asegúrate de cumplir con los siguientes requisitos:

### Conocimientos Previos
- Uso básico de terminal en Linux.
- Configuración de Apache, MySQL y PHP.
- Gestión de servidores y DNS.

### Infraestructura Necesaria
- **Sistema Operativo:** Linux 9 (Oracle Linux o CentOS).
- **Arquitectura:** Recomendable ARM64 (AArch64) o Intel X86_64.
- **Proveedor:** Preferiblemente Oracle Cloud Infrastructure (OCI).

### Herramientas Requeridas
- **Privilegios:** Acceso como root o sudo.
- **Software:** 
  - Apache 2.4 (`httpd`).
  - MySQL.
  - PHP 8.2 con soporte para OCI8.
  - Git.
  - Oracle Instant Client.
- **Conexión:** Acceso a Internet para descargar dependencias.

### Consideraciones
- **Firewall:** Asegúrate de que los puertos necesarios (HTTP: 80, MySQL: 3306) estén abiertos.
- **Archivos:** Repositorio del proyecto y respaldos SQL disponibles.
- **Compatibilidad:** Verifica la disponibilidad de paquetes en sistemas ARM o X86_64.

### Configuración Previa
sudo setenforce 0
sudo nano /etc/selinux/config
SELINUX=permissive
sudo touch /.autorelabel && sudo reboot

## INSTALACIÓN DEL SERVIDOR

Para instalar Apache, MySQL (o MySQL) y PHP con soporte para Oracle (OCI8) en Linux 8 AArch64 (como Oracle Linux9 o CentOS 9) en OCI (Oracle Cloud Infrastructure), puedes seguir estos pasos. Ten en cuenta que en ARM, ciertos paquetes pueden variar, pero generalmente los repositorios estándar deberían funcionar.

### Paso 1: Actualiza el sistema

Primero, actualiza los paquetes del sistema para asegurar que tienes la última versión de todo:

```bash
sudo dnf update -y
```

### Paso 2: Instala Apache (httpd)

Instala Apache con el siguiente comando:

```bash
sudo dnf install httpd -y
```

Inicia el servicio de Apache y habilítalo para que arranque al iniciar el sistema:

```bash
sudo systemctl start httpd
sudo systemctl enable httpd
```

### Paso 3: Instala MySQL

Para instalar MySQL Server:

```bash
sudo dnf install mysql-server -y
```

Inicia el servicio de MySQL y habilítalo para que arranque automáticamente:

```bash
sudo systemctl start mysqld
sudo systemctl enable mysqld
```

Configura MySQL con un script de seguridad inicial:

```bash
sudo mysql_secure_installation
```

Sigue las instrucciones en pantalla para configurar la contraseña y la seguridad de MySQL.

### Paso 4: Instala PHP con soporte para OCI8

1. **Instala PHP y módulos necesarios:**

   ```bash
   sudo dnf install php php-mysqlnd php-pear php-devel php-cli php-common php-fpm -y
   ```

2. **Instala Oracle Instant Client** para habilitar el soporte OCI8 en PHP.

   - Oracle proporciona el **Instant Client** que debes descargar desde [Oracle Instant Client Downloads](https://www.oracle.com/database/technologies/instant-client/downloads.html).
   - Descarga la versión ARM o x86_64 usando RPM.

   Si el cliente está en el mismo servidor, asegúrate de que puedes acceder al cliente.

   Para instalar el Oracle Instant Client en tu sistema Linux 9 ARM64 (aarch64) o X86_64 para habilitar el soporte OCI8 en PHP, te recomiendo las siguientes opciones:

   Paquete Básico RPM:
   Recomendado: Descarga el Basic Package en formato RPM (oracle-instantclientXX.XX-basic-XX.XX.0.0.0-1.XXXX.rpm). Este incluye todos los archivos necesarios para que funcione OCI, OCCI y JDBC-OCI, y es ideal para la mayoría de las instalaciones.
   El paquete RPM facilita la instalación en Linux y puede instalarse con un comando como sudo dnf install ./oracle-instantclientXX.XX-basic-XX.XX.0.0.0-1.XXXX.rpm si estás en Oracle Linux o una distribución compatible.


3. **Instala OCI8** utilizando `pecl`:

   ```bash
   sudo pecl install oci8
   ```

   Durante la instalación, se te pedirá la ruta de instalación del cliente Oracle Instant Client. Introduce la ruta, que normalmente es `/usr/lib/oracle/<version>/client64/lib` (ajusta según la versión).

4. **Configura PHP** para cargar la extensión OCI8:

   Edita el archivo de configuración de PHP (`/etc/php.ini`) y añade la línea:

   ```ini
   extension=oci8.so
   ```

### Paso 5: Verifica la instalación

1. Reinicia Apache para que cargue la configuración de PHP:

   ```bash
   sudo systemctl restart httpd
   ```

2. Crea un archivo `phpinfo.php` en el directorio raíz de tu servidor web (normalmente `/var/www/html/`) para verificar la instalación de PHP y OCI8:

   ```bash
   echo "<?php phpinfo(); ?>" | sudo tee /var/www/html/phpinfo.php
   ```

3. Abre un navegador y accede a `http://<tu_ip>/phpinfo.php`. Deberías ver la configuración de PHP, y OCI debería aparecer como cargado en el listado de módulos.

### Paso 6: Configura Firewall (si aplica)

Si tienes un firewall activado, permite el tráfico HTTP y HTTPS:

```bash
sudo firewall-cmd --permanent --add-service=http
sudo firewall-cmd --permanent --add-service=https
sudo firewall-cmd --permanent --add-service=mysql
sudo firewall-cmd --reload
```

Con esto, deberías tener Apache, MySQL y PHP con soporte OCI8 funcionando en tu instancia de Oracle Cloud Infrastructure en ARM (AArch64)!

## INSTALAR EL CÓDIGO FUENTE

Para instalar el proyecto desde la fuente, sigue estos pasos:

1. Instala Git si no lo tienes instalado:

    ```bash
    dnf install git
    ```

2. Navega al directorio `/var/www`:

    ```bash
    cd /var/www
    ```

3. Clona el repositorio de Bitbucket:

    ```bash
    git clone https://[usuario_bitbucket]@bitbucket.org/project/phoenix.git
    ```

Este proceso descargará el código fuente en el directorio `/var/www/phoenix`.


## CONFIGURACIÓN DEL VIRTUALHOST

Antes de configurar el `VirtualHost`, asegúrate de que el DNS esté correctamente configurado y apunte al servidor. El dominio `DOMINIO.COM` debe resolverse a la IP de tu servidor.

### Pasos para configurar el VirtualHost:

1. **Configura el DNS**:
   Asegúrate de que el dominio `dominio.com` esté apuntando a la IP pública de tu servidor. Puedes hacerlo desde el panel de administración de tu proveedor de DNS, añadiendo un registro `A` que apunte a la dirección IP del servidor.

2. **Crea o edita el archivo de configuración de Apache**:
   Abre o crea un archivo de configuración para tu sitio en `/etc/httpd/conf.d/vhosts.conf` (o la ruta correspondiente según tu distribución).

3. **Agrega la configuración del VirtualHost**:
   
    Añade la siguiente configuración al archivo de configuración de Apache:

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

4. **Guarda el archivo y reinicia Apache**:
   Después de guardar el archivo de configuración, reinicia Apache para aplicar los cambios:

    ```bash
    systemctl restart httpd
    ```

Esto configurará Apache para servir el contenido desde `/var/www/phoenix` bajo el nombre de dominio `DOMINIO.COM`, siempre y cuando el DNS esté correctamente configurado para apuntar a la IP del servidor. Los registros de acceso y error se almacenarán en los archivos correspondientes.

## INSTALAR LA BASE DE DATOS

### Paso 1: Crear el usuario en MySQL

1. Inicia sesión en MySQL como root:

    ```bash
    sudo mysql -u root -p
    ```

    Luego, ingresa la contraseña de root cuando se te solicite.

2. Crea el usuario `phoenix` y otórgale los privilegios necesarios sobre la base de datos `phoenix`:

    ```sql
      CREATE USER 'phoenix'@'%' IDENTIFIED BY 'Solid256!';
      GRANT ALL PRIVILEGES ON *.* TO 'phoenix'@'%';
      FLUSH PRIVILEGES;
      EXIT;
   ```

Esto le da todos los privilegios (ALL PRIVILEGES) al usuario phoenix en la base de datos phoenix desde cualquier host (%).

### Paso 2: Importar el Respaldo SQL

1. Inicia sesión en MySQL como usuario administrador (en este caso `root`):

   ```bash
   sudo mysql -u root -p
```
    Luego, ingresa la contraseña de root cuando se te solicite.

2. Crea la base de datos phoenix con la configuración de colación compatible (utf8mb4_general_ci):
```sql
CREATE DATABASE phoenix;
CREATE DATABASE pipelines;
CREATE DATABASE datawerehouse;
ALTER DATABASE phoenix CHARACTER SET = utf8mb4 COLLATE = utf8mb4_0900_ai_ci;
ALTER DATABASE pipelines CHARACTER SET = utf8mb4 COLLATE = utf8mb4_0900_ai_ci;
ALTER DATABASE datawerehouse CHARACTER SET = utf8mb4 COLLATE = utf8mb4_0900_ai_ci;
EXIT;
```
3. Ejecuta el siguiente comando para importar el archivo SQL en la base de datos phoenix:
```bash
sudo mysql -u phoenix -p phoenix < /var/www/phoenix/sql/phoenix.sql
```

Se te pedirá la contraseña del usuario phoenix.
Este comando importará el contenido de phoenix.sql en la base de datos phoenix recién creada.





