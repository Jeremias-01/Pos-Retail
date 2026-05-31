# POS Retail

Sistema de punto de venta desarrollado para el curso Analisis de Sistemas I.

## Descripcion

POS Retail permite gestionar los procesos principales de una entidad comercial: seguridad de acceso, productos, proveedores, clientes, compras, devoluciones al proveedor, ventas, comprobantes, Kardex y reporte de ventas.

El proyecto fue construido con un alcance academico, enfocado en cumplir los modulos solicitados en la rubrica del curso.

## Herramientas utilizadas

- PHP 8
- MySQL / MariaDB
- XAMPP
- HTML
- CSS
- PDO para conexion a base de datos

## Modulos incluidos

- Login y seguridad
- Bloqueo de usuario por intentos fallidos
- Recuperacion de contrasena
- Productos
- Proveedores
- Clientes
- Compras al contado y credito
- Devoluciones al proveedor
- Ventas al contado y credito
- Comprobante de venta
- Comprobante de pago
- Kardex de productos
- Reporte de ventas

## Instalacion

1. Copiar la carpeta del proyecto en:

```text
C:\xampp\htdocs\pos-retail
```

2. Iniciar Apache y MySQL desde XAMPP.

3. Crear la base de datos ejecutando el archivo:

```text
sql/schema.sql
```

4. Crear el usuario administrador ejecutando el archivo:

```text
sql/seed_admin.sql
```

5. Abrir el sistema en el navegador:

```text
http://localhost:8080/pos-retail/login.php
```

Si Apache esta configurado en el puerto 80, usar:

```text
http://localhost/pos-retail/login.php
```

## Usuario de prueba

```text
Usuario: admin
Contrasena: Admin123*
```

## Base de datos

Nombre de la base de datos:

```text
pos_retail
```

Tablas principales:

- roles
- usuarios
- productos
- proveedores
- clientes
- compras
- detalle_compras
- ventas
- detalle_ventas
- pagos
- kardex
- cuentas_por_pagar
- cuentas_por_cobrar
- devoluciones_proveedor
- detalle_devoluciones_proveedor

## Flujo principal para demostracion

1. Iniciar sesion con el usuario administrador.
2. Registrar o consultar productos.
3. Registrar proveedor y cliente.
4. Registrar una compra y verificar aumento de stock.
5. Consultar Kardex.
6. Registrar una venta y verificar disminucion de stock.
7. Visualizar comprobante de venta y pago.
8. Consultar reporte de ventas.
9. Registrar una devolucion al proveedor.

## Seguridad

- Las contrasenas se almacenan usando hash con `password_hash`.
- El login valida credenciales con `password_verify`.
- El usuario se bloquea al superar los intentos fallidos permitidos.
- La recuperacion de contrasena permite actualizar la clave usando el correo registrado.

## Estado del proyecto

Version funcional academica para entrega de curso.
