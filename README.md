# Sistema de Banca en Línea - PHP & MySQL

Sistema web bancario desarrollado en **PHP** con persistencia en **MySQL/MariaDB**, administrado a través de **XAMPP / phpMyAdmin**. Incluye gestión de roles, transacciones seguras mediante procedimientos almacenados y control de operaciones diarias.

---

## 🚀 Requisitos del Sistema

- **XAMPP** (Apache 2.4+ / PHP 8.x / MySQL 8.x o MariaDB)
- **Navegador Web** (Chrome, Edge, Firefox)

---

## 🗄️ Configuración de la Base de Datos

1. Abre **phpMyAdmin** (`http://localhost/phpmyadmin/`).
2. Ve a la pestaña **SQL** en el panel superior.
3. Copia y ejecuta el script completo contenido en el archivo `db.sql` del repositorio.

Este script realizará lo siguiente de forma automática:
- Crear la base de datos `banca_db` con codificación `utf8mb4`.
- Generar la estructura de las 7 tablas base (`administradores`, `cajeros`, `cuentas`, `usuarios`, `cuentas_terceros`, `movimientos`, `transferencias`).
- Insertar el usuario administrador inicial.
- Crear los índices de optimización e integridad de datos.
- Registrar los **Procedimientos Almacenados** para transacciones seguras (`sp_deposito`, `sp_retiro`, `sp_transferir`, reportes, etc.).

---

## 🛠️ Instalación y Despliegue Local

1. Clona o descarga este repositorio dentro del directorio público de Apache:
   ```bash
   C:\xampp\htdocs\Banca-PHP-DW
