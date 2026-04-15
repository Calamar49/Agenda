# Agenda Web Dinámica — Guía de Despliegue
**Laboratorio de Servidores de Red · Fedora Server 43**

---

## Arquitectura de Red

```
Cliente (Navegador)
        │ HTTPS
        ▼
┌───────────────────────────────┐        ┌───────────────────────────────┐
│  NODO 1 – Servidor Web       │  TCP   │  NODO 2 – Servidor BD         │
│  IP: 192.168.56.104          │──3306──▶│  IP: 192.168.56.105          │
│  Apache 2.4 + PHP 8.4        │        │  MariaDB 10.11+               │
│  SELinux Enforcing           │        │  Base de datos: uacdb         │
└───────────────────────────────┘        └───────────────────────────────┘
```

## Estructura del Proyecto

```
agenda/
├── app/
│   ├── Controllers/
│   │   └── ContactoController.php  ← Lógica de acciones (CRUD)
│   └── Models/
│       └── Contacto.php            ← Acceso a BD (prepared statements)
├── config/
│   └── conexion.php                ← Conexión remota mysqli (Singleton)
├── database/
│   └── schema.sql                  ← DDL: tabla contactos
├── deploy/
│   └── agenda-ssl.conf             ← VirtualHost Apache HTTPS
└── public/
    ├── css/
    │   └── style.css               ← Glassmorphism / Dark Theme
    └── index.php                   ← Front Controller + Vista
```

---

## 🖥️ NODO 2 — Configuración del Servidor de Base de Datos (192.168.56.105)

### 1. Instalar MariaDB

```bash
sudo dnf install -y mariadb-server
sudo systemctl enable --now mariadb
sudo mysql_secure_installation
```

### 2. Crear la base de datos y el usuario remoto

```sql
-- Conectar como root
sudo mysql -u root -p

-- Crear la base de datos
CREATE DATABASE IF NOT EXISTS uacdb
    CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Crear usuario con acceso SOLO desde el Nodo Web
CREATE USER 'manuel'@'192.168.56.104' IDENTIFIED BY 'calamar123';
GRANT ALL PRIVILEGES ON uacdb.* TO 'manuel'@'192.168.56.104';
FLUSH PRIVILEGES;
EXIT;
```

### 3. Importar el esquema de la tabla

```bash
mysql -u root -p uacdb < /ruta/del/proyecto/database/schema.sql
```

### 4. Permitir conexiones remotas en MariaDB

Editar `/etc/my.cnf.d/mariadb-server.cnf`:

```ini
[mysqld]
bind-address = 0.0.0.0   # Escuchar en todas las interfaces
```

```bash
sudo systemctl restart mariadb
```

### 5. Abrir puerto 3306 en el firewall (Nodo 2)

```bash
# Permitir conexiones al puerto MariaDB solo desde el Nodo Web
sudo firewall-cmd --permanent --add-rich-rule='
  rule family="ipv4"
  source address="192.168.56.104"
  port port="3306" protocol="tcp" accept'
sudo firewall-cmd --reload

# Verificar
sudo firewall-cmd --list-all
```

---

## 🌐 NODO 1 — Configuración del Servidor Web (192.168.56.104)

### 1. Instalar Apache + PHP 8.4

```bash
sudo dnf install -y httpd mod_ssl php php-mysqlnd php-mbstring
sudo systemctl enable --now httpd
```

### 2. Desplegar el proyecto

```bash
# Copiar el proyecto al DocumentRoot
sudo cp -r /ruta/local/agenda /var/www/html/agenda

# Asignar propietario al usuario de Apache
sudo chown -R apache:apache /var/www/html/agenda

# Permisos: directorios 755, archivos 644
sudo find /var/www/html/agenda -type d -exec chmod 755 {} \;
sudo find /var/www/html/agenda -type f -exec chmod 644 {} \;
```

### 3. ⚡ SELinux — Permitir conexión saliente hacia MariaDB

> Este es el paso más crítico en Fedora. Sin él, Apache no puede conectarse
> al Nodo 2 aunque el firewall esté abierto.

```bash
# Habilitar la política de SELinux para conexiones de httpd a DB remotas
sudo setsebool -P httpd_can_network_connect_db 1

# Verificar que quedó activo
getsebool httpd_can_network_connect_db
# Resultado esperado: httpd_can_network_connect_db --> on
```

### 4. Aplicar el contexto SELinux correcto a los archivos del proyecto

```bash
sudo restorecon -Rv /var/www/html/agenda
```

### 5. Configurar VirtualHost HTTPS

```bash
# Generar certificado autofirmado (para laboratorio)
sudo openssl req -x509 -nodes -days 365 -newkey rsa:2048 \
    -keyout /etc/pki/tls/private/agenda.key \
    -out /etc/pki/tls/certs/agenda.crt \
    -subj "/CN=192.168.56.104/O=Laboratorio/C=MX"

# Copiar configuración de Apache
sudo cp deploy/agenda-ssl.conf /etc/httpd/conf.d/agenda-ssl.conf

# Verificar sintaxis y recargar Apache
sudo apachectl configtest
sudo systemctl reload httpd
```

### 6. Abrir puertos HTTP/HTTPS en el firewall (Nodo 1)

```bash
sudo firewall-cmd --permanent --add-service=http
sudo firewall-cmd --permanent --add-service=https
sudo firewall-cmd --reload
```

---

## ✅ Verificación del Sistema

```bash
# Desde el Nodo 1, probar conexión TCP al servidor de BD
telnet 192.168.56.105 3306

# Probar conexión MySQL manualmente desde el Nodo 1:
mysql -h 192.168.56.105 -u manuel -p uacdb

# Verificar logs de Apache ante cualquier error:
sudo tail -f /var/log/httpd/agenda_error.log
```

Acceder en el navegador: **https://192.168.56.104/agenda/**

---

## Resumen de Comandos Clave

| Acción | Comando |
|---|---|
| Habilitar SELinux para DB | `sudo setsebool -P httpd_can_network_connect_db 1` |
| Permisos de carpeta | `sudo chown -R apache:apache /var/www/html/agenda` |
| Recargar Apache | `sudo systemctl reload httpd` |
| Reiniciar MariaDB | `sudo systemctl restart mariadb` |
| Ver logs Apache | `sudo tail -f /var/log/httpd/agenda_error.log` |
| Ver errores PHP | `sudo tail -f /var/log/httpd/error_log` |
