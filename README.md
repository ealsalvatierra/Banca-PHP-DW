# Proyecto Banca

Proyecto académico del curso de Desarrollo Web: simulación de un sistema bancario construido con PHP y MySQL/MariaDB.

## Funcionalidades

- Administrador: gestión de cajeros y monitoreo de operaciones.
- Cajero: creación de cuentas, depósitos y retiros.
- Usuario: registro, consulta de movimientos, cuentas de terceros y transferencias.

## Instalación local

1. Instalar WampServer o un entorno con Apache, PHP y MySQL/MariaDB. PHP debe tener la extensión mysqli habilitada.
2. Copiar el contenido de `ProyectoBanca/` a una carpeta dentro del directorio web (por ejemplo, `C:\wamp64\www\ProyectoBanca`).
3. Importar `ProyectoBanca/db.sql` desde phpMyAdmin o un cliente SQL. El script crea `banca_db`, tablas y procedimientos almacenados.
4. Ajustar `ProyectoBanca/conexion.php` a la configuración local de la base de datos. Por defecto usa `127.0.0.1`, usuario `root` y contraseña vacía.
5. Abrir `http://localhost/ProyectoBanca/`.

El script incluye el administrador de demostración `admin` / `admin123`.

## Alcance académico

Este código es una práctica de curso. Las contraseñas se almacenan y comparan en texto plano, y la configuración y cuenta predeterminadas son únicamente para un entorno local de demostración. Antes de usar datos reales o desplegarlo, implementar almacenamiento seguro de contraseñas y revisar los controles de autenticación, autorización y transacciones.

No se ejecutaron pruebas funcionales durante esta publicación; se revisaron los archivos estáticamente para preparar la subida. Esta revisión no constituye una auditoría de seguridad completa.

