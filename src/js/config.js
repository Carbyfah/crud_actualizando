/**
 * Configuración global de la aplicación
 * 
 * Propósito:
 * - Centraliza configuraciones comunes
 * - Define rutas base del sistema
 * - Mapea rutas específicas por entidad
 * - Facilita mantenimiento de URLs
 */
export const CONFIG = {
    BASE_URL: '/app_carbajal',
    API_URL: '/app_carbajal',
    ROUTES: {
        categorias: {
            view: '/patitos',
            create: '/categorias/guardarCategoria',
            read: '/categorias/obtenerCategorias',
            update: '/categorias/modificarCategoria',
            delete: '/categorias/eliminarCategoria',
            find: '/categorias/buscarCategoria'
        },
        clientes: {
            view: '/guardaBosques',
            create: '/clientes/guardarCliente',
            read: '/clientes/obtenerClientes',
            update: '/clientes/modificarCliente',
            delete: '/clientes/eliminarCliente',
            find: '/clientes/buscarCliente'
        },
        prioridades: {
            view: '/charquitos',
            create: '/prioridades/guardarPrioridad',
            read: '/prioridades/obtenerPrioridades',
            update: '/prioridades/modificarPrioridad',
            delete: '/prioridades/eliminarPrioridad',
            find: '/prioridades/buscarPrioridad'
        },
        productos: {
            view: '/paquito',
            create: '/productos/guardarProducto',
            read: '/productos/obtenerProductos',
            update: '/productos/modificarProducto',
            delete: '/productos/eliminarProducto',
            find: '/productos/buscarProducto'
        },
        ventas: {
            view: '/chaguito',
            create: '/ventas/guardarVenta',
            read: '/ventas/obtenerVentas',
            update: '/ventas/modificarVenta',
            delete: '/ventas/eliminarVenta',
            find: '/ventas/buscarVenta'
        },
        usuarios: {
            view: '/panas',
            create: '/usuarios/guardarUsuario',
            read: '/usuarios/obtenerUsuarios',
            update: '/usuarios/modificarUsuario',
            delete: '/usuarios/eliminarUsuario',
            find: '/usuarios/buscarUsuario'
        },
        detalleventas: {
            view: '/rambito',
            create: '/detalleventas/guardarDetalleVenta',
            read: '/detalleventas/obtenerDetalleVentas',
            update: '/detalleventas/modificarDetalleVenta',
            delete: '/detalleventas/eliminarDetalleVenta',
            find: '/detalleventas/buscarDetalleVenta'
        }
    }
};