<?php

namespace Controllers;

use MVC\Router;

class AppController
{
    // Propiedades base
    protected static $alertas = [];
    protected static $respuestaJSON = [];
    protected $modelo = null;


    public static function index(Router $router)
    {
        $router->render('pages/index', []);
    }
    /**
     * Envía una respuesta JSON al cliente con el código HTTP especificado
     * 
     * Propósito:
     * - Establece el código de estado HTTP apropiado
     * - Configura las cabeceras para contenido JSON
     * - Codifica los datos en formato JSON
     * - Asegura caracteres Unicode correctamente escapados
     * - Finaliza la ejecución del script tras enviar la respuesta
     * 
     * Analogía:
     * Es como un mensajero que entrega un paquete (datos) en un formato específico 
     * (JSON), asegurándose de que el destinatario sepa exactamente cómo interpretarlo 
     * (headers) y con qué nivel de importancia tratarlo (código HTTP).
     * 
     * Ejemplo de uso:
     * class ProductoController extends AppController {
     *     public function buscar() {
     *         try {
     *             $productos = Producto::where('categoria', 'electronica');
     *             self::responderJson([
     *                 'exito' => true,
     *                 'data' => $productos
     *             ], 200);
     *         } catch (\Exception $e) {
     *             self::responderJson([
     *                 'exito' => false,
     *                 'mensaje' => $e->getMessage()
     *             ], 500);
     *         }
     *     }
     * }
     * 
     * @param mixed $data   Datos a enviar en la respuesta JSON
     * @param int $codigo   Código de estado HTTP (default: 200)
     * @return void
     */
    protected static function responderJson($data, $codigo = 200)
    {
        // Limpiamos cualquier salida previa
        if (ob_get_level() > 0) {
            ob_end_clean();
        }

        http_response_code($codigo);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    // ...existing code...

    /**
     * Limpia el buffer de salida y establece las cabeceras para JSON
     * 
     * Propósito:
     * - Asegura que no haya contenido previo en el buffer
     * - Establece las cabeceras correctas para JSON
     * - Previene problemas de codificación en la respuesta
     */

    protected static function limpiarSalida()
    {
        // Iniciamos buffer de salida si no está activo
        if (ob_get_level() === 0) {
            ob_start();
        }
        // Limpiamos cualquier salida previa
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        // Iniciamos nuevo buffer
        ob_start();
        header('Content-Type: application/json; charset=utf-8');
    }

    /**
     * Valida el método HTTP de la petición actual
     * 
     * Propósito:
     * - Verifica que la petición use el método HTTP correcto
     * - Previene accesos no autorizados por métodos incorrectos
     * - Asegura que las rutas se accedan de forma apropiada
     * - Lanza excepciones para métodos no permitidos
     * 
     * Analogía:
     * Es como un guardia de seguridad que verifica que uses la entrada correcta. 
     * Si intentas entrar por la puerta de salida (método incorrecto), el guardia 
     * te detendrá y te indicará el error.
     * 
     * Ejemplo de uso:
     * class ProductoController extends AppController {
     *     public function crear() {
     *         try {
     *             self::validarMetodo('POST');
     *             
     *             $datos = self::obtenerJson();
     *             $producto = new Producto($datos);
     *             $resultado = $producto->guardar();
     *             
     *             self::responderJson($resultado);
     *             
     *         } catch (\Exception $e) {
     *             self::responderJson([
     *                 'error' => $e->getMessage()
     *             ], 405); // Method Not Allowed
     *         }
     *     }
     * }
     * 
     * @param string $metodo Método HTTP esperado (GET, POST, PUT, DELETE, etc.)
     * @throws \Exception Si el método de la petición no coincide con el esperado
     * @return void
     */
    protected static function validarMetodo($metodo)
    {
        if ($_SERVER['REQUEST_METHOD'] !== $metodo) {
            throw new \Exception('Método HTTP no permitido');
        }
    }

    /**
     * Obtiene y decodifica datos JSON del cuerpo de la petición
     * 
     * Propósito:
     * - Captura datos JSON del cuerpo de la petición HTTP
     * - Convierte el JSON en un array asociativo de PHP
     * - Facilita el acceso a datos enviados por el cliente
     * - Maneja automáticamente la decodificación del formato
     * 
     * Analogía:
     * Es como un traductor que toma una carta escrita en un idioma extranjero (JSON) 
     * y la traduce a nuestro idioma nativo (array PHP), permitiéndonos entender 
     * fácilmente su contenido.
     * 
     * Ejemplo de uso:
     * class ProductoController extends AppController {
     *     public function actualizar() {
     *         try {
     *             self::validarMetodo('PUT');
     *             
     *             // Obtener datos JSON enviados por el cliente
     *             $datos = self::obtenerJson();
     *             
     *             if(!isset($datos['id'])) {
     *                 throw new \Exception('ID no proporcionado');
     *             }
     *             
     *             $producto = Producto::find($datos['id']);
     *             $producto->sincronizar($datos);
     *             $resultado = $producto->guardar();
     *             
     *             self::responderJson([
     *                 'exito' => true,
     *                 'producto' => $producto
     *             ]);
     *             
     *         } catch (\Exception $e) {
     *             self::responderJson([
     *                 'error' => $e->getMessage()
     *             ], 400);
     *         }
     *     }
     * }
     * 
     * @return array|null Datos decodificados del JSON o null si no hay datos
     */
    protected static function obtenerJson()
    {
        return json_decode(file_get_contents('php://input'), true);
    }

    /**
     * Maneja errores de forma estandarizada en el controlador
     * 
     * Propósito:
     * - Centraliza el manejo de errores del controlador
     * - Registra errores en el log del sistema
     * - Genera respuestas de error consistentes
     * - Facilita el debugging y monitoreo de errores
     * 
     * Analogía:
     * Es como un supervisor de incidentes que, cuando ocurre un problema:
     * 1. Registra el incidente en la bitácora (error_log)
     * 2. Prepara un informe estandarizado del problema
     * 3. Asegura que todos los errores se manejen de manera uniforme
     * 
     * Ejemplo de uso:
     * class ProductoController extends AppController {
     *     public function actualizar($id) {
     *         try {
     *             $producto = Producto::find($id);
     *             if(!$producto) {
     *                 throw new \Exception("Producto no encontrado");
     *             }
     *             
     *             $datos = self::obtenerJson();
     *             $producto->sincronizar($datos);
     *             $resultado = $producto->guardar();
     *             
     *             return self::responderJson($resultado);
     *             
     *         } catch (\Exception $e) {
     *             $error = $this->manejarError($e, 'actualizar producto');
     *             return self::responderJson($error, $error['codigo']);
     *         }
     *     }
     * }
     * 
     * @param \Exception $e        Excepción capturada
     * @param string     $operacion Descripción de la operación que falló
     * @return array              Respuesta estandarizada con información del error
     */
    protected function manejarError($e, $operacion = '')
    {
        $mensaje = "Error en {$operacion}: " . $e->getMessage();
        error_log($mensaje);

        return [
            'exito' => false,
            'mensaje' => $mensaje,
            'codigo' => 500
        ];
    }

    /**
     * Genera una respuesta estandarizada para las operaciones del controlador
     * 
     * Propósito:
     * - Estandariza el formato de respuestas
     * - Mantiene consistencia en la estructura de datos
     * - Permite incluir datos adicionales de forma opcional
     * - Define códigos HTTP apropiados para cada respuesta
     * 
     * Analogía:
     * Es como un formato oficial de documentos donde siempre se incluyen los 
     * mismos elementos básicos (éxito/fracaso, mensaje) pero permite agregar 
     * anexos (data) cuando sea necesario, similar a un formulario estándar 
     * con campos opcionales.
     * 
     * Ejemplo de uso:
     * class ProductoController extends AppController {
     *     public function listar() {
     *         try {
     *             $productos = Producto::all();
     *             
     *             if(empty($productos)) {
     *                 return $this->generarRespuesta(
     *                     true,
     *                     'No hay productos disponibles',
     *                     [],
     *                     204  // No Content
     *                 );
     *             }
     *             
     *             return $this->generarRespuesta(
     *                 true,
     *                 'Productos recuperados exitosamente',
     *                 ['productos' => $productos],
     *                 200
     *             );
     *             
     *         } catch (\Exception $e) {
     *             return $this->generarRespuesta(
     *                 false,
     *                 $e->getMessage(),
     *                 null,
     *                 500
     *             );
     *         }
     *     }
     * }
     * 
     * @param bool   $exito   Indica si la operación fue exitosa
     * @param string $mensaje Mensaje descriptivo del resultado
     * @param mixed  $data    Datos adicionales opcionales
     * @param int    $codigo  Código HTTP de la respuesta
     * @return array         Respuesta estandarizada
     */
    protected function generarRespuesta($exito = true, $mensaje = '', $data = null, $codigo = 200)
    {
        $respuesta = [
            'exito' => $exito,
            'mensaje' => $mensaje,
            'codigo' => $codigo
        ];

        if ($data !== null) {
            $respuesta['data'] = $data;
        }

        return $respuesta;
    }

    /**
     * Valida datos de entrada según reglas definidas
     * 
     * Propósito:
     * - Verifica que los datos cumplan con las reglas establecidas
     * - Maneja validaciones requeridas y de formato
     * - Genera mensajes de error descriptivos
     * - Centraliza la lógica de validación de datos
     * 
     * Analogía:
     * Es como un inspector de calidad que revisa cada pieza (campo) según una 
     * lista de requisitos específicos (reglas). Si una pieza no cumple con los 
     * estándares, se marca y se describe exactamente qué está mal.
     * 
     * Ejemplo de uso:
     * class UsuarioController extends AppController {
     *     protected function obtenerReglas($operacion) {
     *         return [
     *             'nombre' => ['required'],
     *             'email' => ['required', 'email'],
     *             'password' => ['required', 'min:6']
     *         ];
     *     }
     *     
     *     public function crear() {
     *         $datos = self::obtenerJson();
     *         
     *         // Validar los datos recibidos
     *         $errores = $this->validarDatos($datos, $this->obtenerReglas('crear'));
     *         
     *         if(!empty($errores)) {
     *             return self::responderJson([
     *                 'exito' => false,
     *                 'errores' => $errores
     *             ], 400);
     *         }
     *         
     *         $usuario = new Usuario($datos);
     *         $resultado = $usuario->guardar();
     *         
     *         return self::responderJson($resultado);
     *     }
     * }
     * 
     * @param array $datos  Datos a validar
     * @param array $reglas Reglas de validación a aplicar
     * @return array       Lista de errores encontrados
     */
    protected function validarDatos($datos, $reglas)
    {
        $errores = [];

        foreach ($reglas as $campo => $validaciones) {
            if (!isset($datos[$campo]) && in_array('required', $validaciones)) {
                $errores[] = "El campo {$campo} es requerido";
                continue;
            }

            if (isset($datos[$campo])) {
                $valor = $datos[$campo];
                foreach ($validaciones as $validacion) {
                    if ($validacion === 'email' && !filter_var($valor, FILTER_VALIDATE_EMAIL)) {
                        $errores[] = "El campo {$campo} debe ser un email válido";
                    }
                    // Agregar más validaciones según necesites
                }
            }
        }

        return $errores;
    }

    /**
     * Procesa una operación CRUD completa de forma estandarizada
     * 
     * Propósito:
     * - Implementa el flujo completo de una operación CRUD
     * - Valida el método HTTP y los datos de entrada
     * - Ejecuta validaciones específicas por operación
     * - Maneja errores de forma consistente
     * - Genera respuestas estandarizadas
     * 
     * Analogía:
     * Es como una línea de producción automatizada donde:
     * 1. Se verifica que la orden llegue por el canal correcto (método HTTP)
     * 2. Se revisa que los materiales estén completos (datos)
     * 3. Se valida la calidad de los materiales (validaciones)
     * 4. Se procesa el producto (operación)
     * 5. Se empaca y etiqueta el resultado (respuesta)
     * 
     * Ejemplo de uso:
     * class ProductoController extends AppController {
     *     public function crear() {
     *         return $this->procesarOperacion('crear', [
     *             'nombre' => 'Nuevo Producto',
     *             'precio' => 99.99,
     *             'categoria_id' => 1
     *         ]);
     *     }
     *     
     *     protected function obtenerReglas($operacion) {
     *         return [
     *             'crear' => [
     *                 'nombre' => ['required'],
     *                 'precio' => ['required', 'numeric'],
     *                 'categoria_id' => ['required', 'exists:categorias']
     *             ]
     *         ][$operacion] ?? [];
     *     }
     * }
     * 
     * @param string $operacion Tipo de operación CRUD a ejecutar
     * @param array  $datos     Datos para la operación (opcional)
     * @return array           Respuesta estandarizada de la operación
     */
    protected function procesarOperacion($operacion, $datos = null)
    {
        try {
            $this->validarMetodo('POST');

            if ($datos === null) {
                $datos = $this->obtenerJson();
            }

            if (empty($datos)) {
                return $this->generarRespuesta(false, 'No se recibieron datos', null, 400);
            }

            // Validar datos según la operación
            $errores = $this->validarDatos($datos, $this->obtenerReglas($operacion));
            if (!empty($errores)) {
                return $this->generarRespuesta(false, implode(', ', $errores), null, 400);
            }

            // Ejecutar la operación en el modelo
            $resultado = $this->ejecutarOperacion($operacion, $datos);

            return $this->generarRespuesta(
                $resultado['exito'],
                $resultado['mensaje'],
                $resultado['data'] ?? null,
                $resultado['codigo'] ?? 200
            );
        } catch (\Exception $e) {
            return $this->manejarError($e, $operacion);
        }
    }

    /**
     * Define las reglas de validación según la operación
     * 
     * Propósito:
     * - Proporciona las reglas de validación específicas por operación
     * - Permite personalizar validaciones según el contexto
     * - Define una estructura base para las validaciones
     * - Facilita la extensión en controladores hijos
     * 
     * Analogía:
     * Es como un libro de reglas que cambia según el tipo de producto que 
     * se está procesando. Por ejemplo, las reglas para validar un libro 
     * son diferentes a las de un dispositivo electrónico.
     * 
     * Ejemplo de uso:
     * class UsuarioController extends AppController {
     *     protected function obtenerReglas($operacion) {
     *         $reglas = [
     *             'crear' => [
     *                 'nombre' => ['required'],
     *                 'email' => ['required', 'email'],
     *                 'password' => ['required', 'min:6']
     *             ],
     *             'actualizar' => [
     *                 'id' => ['required'],
     *                 'nombre' => ['required'],
     *                 'email' => ['email']
     *             ],
     *             'login' => [
     *                 'email' => ['required', 'email'],
     *                 'password' => ['required']
     *             ]
     *         ];
     *         
     *         return $reglas[$operacion] ?? [];
     *     }
     * }
     * 
     * @param string $operacion Nombre de la operación para obtener sus reglas
     * @return array           Conjunto de reglas de validación para la operación
     */
    protected function obtenerReglas($operacion)
    {
        // Cada controlador hijo debe implementar sus propias reglas
        return [];
    }

    /**
     * Ejecuta la operación específica en el modelo asociado
     * 
     * Propósito:
     * - Delega la ejecución de operaciones CRUD al modelo
     * - Verifica la existencia del modelo antes de operar
     * - Maneja diferentes tipos de operaciones de forma uniforme
     * - Asegura consistencia en las operaciones del modelo
     * 
     * Analogía:
     * Es como un supervisor que, según el tipo de trabajo solicitado 
     * (crear, actualizar, eliminar), asigna la tarea al especialista 
     * adecuado (modelo) y asegura que tenga todas las herramientas 
     * necesarias (datos) para realizar el trabajo.
     * 
     * Ejemplo de uso:
     * class ProductoController extends AppController {
     *     public function __construct() {
     *         $this->modelo = new Producto();
     *     }
     *     
     *     public function crear() {
     *         $datos = [
     *             'nombre' => 'Nuevo Producto',
     *             'precio' => 99.99
     *         ];
     *         
     *         try {
     *             $resultado = $this->ejecutarOperacion('crear', $datos);
     *             return self::responderJson($resultado);
     *         } catch (\Exception $e) {
     *             return $this->manejarError($e, 'crear producto');
     *         }
     *     }
     * }
     * 
     * @param string $operacion Tipo de operación a ejecutar (crear|actualizar|eliminar)
     * @param array  $datos     Datos necesarios para la operación
     * @throws \Exception       Si no hay modelo definido o la operación es inválida
     * @return array           Resultado de la operación ejecutada
     */
    protected function ejecutarOperacion($operacion, $datos)
    {
        if (!$this->modelo) {
            throw new \Exception('No se ha definido un modelo para el controlador');
        }

        switch ($operacion) {
            case 'crear':
                return $this->modelo->guardarSeguro($datos);
            case 'actualizar':
                return $this->modelo->actualizar($datos);
            case 'eliminar':
                return $this->modelo->eliminar();
            default:
                throw new \Exception('Operación no válida');
        }
    }

    /**
     * Renderiza una vista con datos y alertas del sistema
     * 
     * Propósito:
     * - Centraliza la renderización de vistas
     * - Inyecta datos y alertas automáticamente
     * - Mantiene consistencia en la presentación
     * - Facilita la reutilización de componentes visuales
     * 
     * Analogía:
     * Es como un diseñador que toma un plano (vista), lo combina con los 
     * materiales necesarios (datos) y las notas importantes (alertas) para 
     * crear el producto final que verá el cliente.
     * 
     * Ejemplo de uso:
     * class ProductoController extends AppController {
     *     public function index() {
     *         $productos = Producto::all();
     *         
     *         if(empty($productos)) {
     *             static::$alertas['info'][] = 'No hay productos registrados';
     *         }
     *         
     *         return $this->renderizar($router, 'productos/index', [
     *             'titulo' => 'Gestión de Productos',
     *             'productos' => $productos,
     *             'total' => count($productos)
     *         ]);
     *     }
     * }
     * 
     * @param Router $router Instancia del router para renderizar
     * @param string $vista  Ruta de la vista a renderizar
     * @param array  $datos  Datos adicionales para la vista
     * @return mixed        Resultado de la renderización
     */
    protected function renderizar(Router $router, $vista, $datos = [])
    {
        $datos['alertas'] = static::$alertas;
        return $router->render($vista, $datos);
    }
}
