# Sistema de Banca en Línea - PHP & MySQL

Sistema web bancario desarrollado en PHP con persistencia en MySQL/MariaDB. Incluye gestión de roles (Administrador, Cajero, Cliente), transacciones bancarias, control de operaciones diarias y un módulo de monitoreo de tráfico HTTP en tiempo real.

---

## Sitio en Línea (Despliegue Público)

El proyecto se encuentra totalmente operativo y desplegado en un entorno de hosting público:

* URL del Proyecto: http://bancadw.infinityfree.me
* Panel de Logs del Servidor: Accesible desde el menú del Administrador en la sección "Logs Servidor".

---

## Estructura del Repositorio

El repositorio se encuentra organizado en dos entornos principales:

* /Online: Contiene la versión de código fuente optimizada y desplegada en InfinityFree.
  - Implementa un middleware de registro de tráfico HTTP en tiempo real integrado en MySQL (tabla server_logs).
  - Incluye el panel visual logs.php con codificación de colores para estados HTTP.
  - Reemplaza los procedimientos almacenados (CALL) por subconsultas SQL directas compatibles con el hosting compartido.
  - Incluye el script de base de datos adaptado (banca_db.sql).

* /Local: Contiene la versión original de desarrollo diseñada para entornos locales XAMPP.
  - Incluye el archivo INSTRUCCIONES.txt con la guía de configuración local.
  - Incluye el script SQL original (creacion_y_migracion.sql) con procedimientos almacenados (sp_monitor, sp_deposito, etc.).
  - Contiene los archivos del proyecto original (/ProyectoBanca) y las pruebas unitarias (/tests).

---

## Requisitos del Sistema (Entorno Local)

- XAMPP (Apache 2.4+ / PHP 8.x / MySQL 8.x o MariaDB)
- Navegador Web (Chrome, Edge, Firefox)

---

## Configuración de la Base de Datos

### Para Despliegue en Servidor / Online (InfinityFree)
1. Accede a phpMyAdmin desde el panel de control de InfinityFree.
2. Importa y ejecuta el script ubicado en Online/banca_db.sql.
3. Este script creará la estructura de tablas base (usuarios, cuentas, movimientos, transferencias_terceros) y la tabla de monitoreo server_logs.

### Para Despliegue Local (XAMPP)
1. Abre phpMyAdmin local (http://localhost/phpmyadmin/).
2. Copia y ejecuta el script ubicado en Local/creacion_y_migracion.sql.
3. Este script generará la estructura base junto con los procedimientos almacenados requeridos para el entorno local.

---

## Instalación y Despliegue Local

1. Consulta el archivo Local/INSTRUCCIONES.txt para la guía detallada de instalación.
2. Clona este repositorio dentro del directorio público de Apache:
   ```bash
   cd /c/xampp/htdocs
   git clone https://github.com/ealsalvatierra/Banca-PHP-DW.git
