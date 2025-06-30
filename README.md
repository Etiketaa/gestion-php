# Sistema de Gestión de Puntos de Venta (POS System)

Este es un sistema de gestión de puntos de venta y control de inventario desarrollado en PHP y MySQL, con una interfaz de usuario moderna y un tema oscuro.

! [Imagen de login](img/gestion1.png)

## Características

El sistema incluye las siguientes funcionalidades:

*   **Gestión de Usuarios:**
    *   Registro de nuevos usuarios con datos personales (nombre, apellido, email, teléfono).
    *   Aprobación/Rechazo de usuarios por parte de administradores.
    *   Asignación de roles (administrador o usuario común) por parte de administradores.
    *   Control de acceso basado en roles: los usuarios comunes solo pueden ver y añadir productos, mientras que los administradores tienen control total (CRUD) sobre productos, categorías y usuarios.
    *   Notificaciones en el dashboard para administradores sobre usuarios pendientes de aprobación.

*   **Gestión de Productos:**
    *   Creación, lectura, actualización y eliminación (CRUD) de productos.
    *   Generación automática de SKU (Stock Keeping Unit) para cada producto.
    *   Carga de imágenes de productos, almacenadas en formato Base64 en la base de datos para optimizar el espacio.
    *   Asignación de categorías a los productos.
    *   Búsqueda de productos en tiempo real en la lista de productos.

*   **Gestión de Categorías:**
    *   Creación, lectura, actualización y eliminación (CRUD) de categorías de productos.

*   **Gestión de Carrito de Compras:**
    *   Añadir productos al carrito.
    *   Visualizar y gestionar los productos en el carrito.
    *   Actualizar cantidades y eliminar productos del carrito.

*   **Gestión de Inventario:**
    *   Registro de movimientos de inventario (entradas y salidas de stock) con un historial detallado.
    *   Alertas de stock bajo en el dashboard principal.

*   **Dashboard Principal:**
    *   Panel de control con estadísticas clave: número total de productos, cantidad total de stock.
    *   Listado de productos con stock bajo.
    *   Listado de productos recientemente añadidos.

*   **Exportación de Datos:**
    *   Funcionalidad para exportar la lista de productos a un archivo CSV.

*   **Interfaz de Usuario:**
    *   Diseño moderno y responsivo utilizando Bootstrap.
    *   Tema oscuro (dark mode) con colores azul oscuro y gris para una mejor experiencia visual.

## Instalación

Para configurar y ejecutar este proyecto en tu entorno local, sigue estos pasos:

1.  **Servidor Web (XAMPP/WAMP/MAMP):** Asegúrate de tener un servidor web con PHP y MySQL instalado (por ejemplo, XAMPP).

2.  **Clonar el Repositorio:** Descarga o clona este repositorio en el directorio `htdocs` de tu instalación de XAMPP (o el directorio equivalente para tu servidor web).

    ```bash
    git clone <URL_DEL_REPOSITORIO> C:\xampp\htdocs\gestion-php
    ```

3.  **Configuración de la Base de Datos:**
    *   Abre phpMyAdmin (generalmente accesible a través de `http://localhost/phpmyadmin`).
    *   Crea una nueva base de datos llamada `pos_system`.
    *   Importa el archivo `sql/database.sql` en la base de datos `pos_system`. Este archivo creará todas las tablas necesarias (`users`, `products`, `categories`, `inventory_movements`).

4.  **Configuración de la Conexión a la Base de Datos:**
    *   Abre el archivo `db.php` en la raíz del proyecto (`C:\xampp\htdocs\gestion-php\db.php`).
    *   Verifica que las constantes de conexión a la base de datos sean correctas para tu entorno:

        ```php
        define('DB_SERVER', 'localhost');
        define('DB_USERNAME', 'root'); // Tu usuario de MySQL
        define('DB_PASSWORD', '');     // Tu contraseña de MySQL (vacío por defecto en XAMPP)
        define('DB_NAME', 'pos_system');
        ```

5.  **Acceder a la Aplicación:**
    *   Abre tu navegador web y navega a `http://localhost/gestion-php`.

## Uso

### Usuarios Administradores

Para acceder con privilegios de administrador, puedes usar las siguientes credenciales (creadas automáticamente al configurar la base de datos):

*   **Usuario:** `admin1`, `admin2` o `admin3`
*   **Contraseña:** `admin`

Los administradores tienen acceso completo a todas las funcionalidades del sistema, incluyendo la gestión de usuarios, productos y categorías.

### Registro de Nuevos Usuarios

Los usuarios pueden registrarse a través del enlace "Sign up now" en la página de login. Deberán proporcionar:

*   Nombre de usuario
*   Contraseña
*   Nombre
*   Apellido
*   Email
*   Número de teléfono (opcional)

Después del registro, la cuenta quedará en estado "pendiente" y requerirá la aprobación de un administrador para poder iniciar sesión.

### Gestión de Usuarios (Solo Administradores)

Los administradores pueden gestionar los usuarios desde la sección "Manage Users" en el menú de navegación. Aquí pueden:

*   Aprobar o rechazar cuentas pendientes.
*   Cambiar el rol de un usuario entre "admin" y "user".
*   Eliminar usuarios.

### Gestión de Productos

*   **Añadir Producto:** Los administradores pueden añadir nuevos productos, incluyendo nombre, descripción, precio, stock, SKU automático, imagen y categoría.
*   **Ver Productos:** Todos los usuarios pueden ver la lista de productos. Los administradores verán opciones adicionales para editar y eliminar.
*   **Buscar Productos:** Utiliza el campo de búsqueda en tiempo real para filtrar productos por nombre o SKU.

### Gestión de Carrito de Compras

*   **Añadir al Carrito:** Los usuarios pueden añadir productos al carrito desde la lista de productos.
*   **Ver Carrito:** Accede al carrito para ver los productos añadidos, sus cantidades y el total.
*   **Actualizar Cantidad:** Modifica la cantidad de un producto directamente en el carrito.
*   **Eliminar del Carrito:** Quita productos del carrito si ya no los deseas.

## Tecnologías Utilizadas

*   **Backend:** PHP 7.x+
*   **Base de Datos:** MySQL
*   **Frontend:** HTML5, CSS3, JavaScript
*   **Framework CSS:** Bootstrap 4.5.2
*   **Librería JS:** jQuery 3.5.1
