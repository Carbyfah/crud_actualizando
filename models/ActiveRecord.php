<? php

namespace Model;

use PDO;
use PDOException;
use Exception;

abstract class ActiveRecord {
    protected static PDO $db;
    protected static string $tabla = '';
    protected static array $columnasDB = [];
    protected static string $idTabla = 'id';
    protected static array $fillable = [];
    protected static array $hidden = [];
    protected static array $casts = [];

    protected array $attributes = [];
    protected array $original = [];
    protected bool $exists = false;

    // Validation
    protected static array $rules = [];
    protected array $errors = [];

    // Scopes
    protected static array $globalScopes = [];

    public function __construct(array $attributes = []) {
        $this -> fill($attributes);
        $this -> syncOriginal();
        $this -> bootIfNotBooted();
    }

    public static function setDB(PDO $database): void {
        self:: $db = $database;
    }

    /**
     * Boot the model if it hasn't been booted
     */
    protected function bootIfNotBooted(): void {
        if (!isset(static:: $booted[static:: class])) {
            static:: $booted[static:: class] = true;
            static:: boot();
        }
    }

    /**
     * Boot method for model initialization
     */
    protected static function boot(): void {
        // Override in child classes for custom boot logic
    }

    // Mass assignment protection
    public function fill(array $attributes): self {
        foreach($attributes as $key => $value) {
            if (in_array($key, static:: $fillable) || empty(static:: $fillable)) {
                $this -> setAttribute($key, $value);
            }
        }
        return $this;
    }

    public function setAttribute(string $key, $value): void {
        // Apply mutators
        $mutatorMethod = 'set'.ucfirst($key). 'Attribute';
        if (method_exists($this, $mutatorMethod)) {
            $value = $this -> $mutatorMethod($value);
        }

        // Apply casting
        if (isset(static:: $casts[$key])) {
            $value = $this -> castValue($value, static:: $casts[$key]);
        }

        $this -> attributes[$key] = $value;
    }

    public function getAttribute(string $key) {
        $value = $this -> attributes[$key] ?? null;

        // Apply accessors
        $accessorMethod = 'get'.ucfirst($key). 'Attribute';
        if (method_exists($this, $accessorMethod)) {
            return $this -> $accessorMethod($value);
        }

        return $value;
    }

    private function castValue($value, string $type) {
        switch ($type) {
            case 'int':
            case 'integer':
                return (int) $value;
            case 'bool':
            case 'boolean':
                return (bool) $value;
            case 'float':
                return (float) $value;
            case 'array':
                return is_array($value) ? $value : json_decode($value, true);
            case 'json':
                return json_decode($value, true);
            case 'date':
                return $value ? date('Y-m-d', strtotime($value)) : null;
            case 'datetime':
                return $value ? date('Y-m-d H:i:s', strtotime($value)) : null;
            default:
                return $value;
        }
    }

    // Query Builder Pattern con Scopes
    public static function query(): QueryBuilder {
        $builder = new QueryBuilder(static:: class);

        // Apply global scopes
        foreach(static:: $globalScopes as $scope) {
            $builder = $scope($builder);
        }

        return $builder;
    }

    // Scopes
    public function scopeActive($query) {
        return $query -> where('situacion', 1);
    }

    public function scopeInactive($query) {
        return $query -> where('situacion', 0);
    }

    public static function find($id): ?static {
        return static:: query() -> where(static:: $idTabla, $id) -> first();
    }

    public static function findOrFail($id): static {
        $model = static:: find($id);
        if (!$model) {
            throw new Exception("Model not found with ID: $id");
        }
        return $model;
    }

    public static function all(): array {
        return static:: query() -> active() -> get();
    }

    public static function where(string $column, $operator, $value = null): QueryBuilder {
        if ($value === null) {
            $value = $operator;
            $operator = '=';
        }
        return static:: query() -> where($column, $operator, $value);
    }

    // Métodos CRUD mejorados
    public function save(): bool {
        if (!$this -> validate()) {
            return false;
        }

        try {
            if ($this -> exists) {
                return $this -> performUpdate();
            } else {
                return $this -> performInsert();
            }
        } catch (PDOException $e) {
            $this -> addError('database', $e -> getMessage());
            error_log("Database error in ".static:: class . ": ".$e -> getMessage());
            return false;
        }
    }

    /**
     * Método seguro para guardar con validación completa
     */
    public function guardarSeguro(array $datos, $id = null, string $operacion = 'crear'): array {
        try {
            // Llenar atributos
            $this -> fill($datos);

            // Si es actualización, establecer el ID
            if ($operacion === 'actualizar' && $id) {
                $this -> setAttribute(static:: $idTabla, $id);
                $this -> exists = true;
            }

            // Validar antes de guardar
            if (!$this -> validate()) {
                return [
                    'exito' => false,
                    'mensaje' => 'Errores de validación: '.implode(', ', $this -> getErrorMessages()),
                    'errores' => $this -> errors
                ];
            }

            // Guardar
            if ($this -> save()) {
                $mensaje = match($operacion) {
                    'crear' => 'Registro creado exitosamente',
                        'actualizar' => 'Registro actualizado exitosamente',
                            'eliminar_logico' => 'Registro eliminado exitosamente',
                    default => 'Operación completada exitosamente'
                };

                return [
                    'exito' => true,
                    'mensaje' => $mensaje,
                    'data' => $this -> toArray(),
                    'id' => $this -> getAttribute(static:: $idTabla)
                ];
            }

            return [
                'exito' => false,
                'mensaje' => 'No se pudo completar la operación',
                'errores' => $this -> errors
            ];

        } catch (Exception $e) {
            error_log("Error en guardarSeguro: ".$e -> getMessage());
            return [
                'exito' => false,
                'mensaje' => 'Error interno: '.$e -> getMessage()
            ];
        }
    }

    private function performInsert(): bool {
        $attributes = $this -> getAttributesForInsert();

        if (empty($attributes)) {
            return false;
        }

        // Agregar timestamps si la tabla los tiene
        if ($this -> hasTimestamps()) {
            $now = date('Y-m-d H:i:s');
            $attributes['created_at'] = $now;
            $attributes['updated_at'] = $now;
        }

        $columns = array_keys($attributes);
        $placeholders = array_fill(0, count($columns), '?');

        $sql = "INSERT INTO ".static:: $tabla. " (".implode(', ', $columns). ") VALUES (".implode(', ', $placeholders). ")";

        $stmt = self:: $db -> prepare($sql);
        $result = $stmt -> execute(array_values($attributes));

        if ($result) {
            $this -> setAttribute(static:: $idTabla, self:: $db -> lastInsertId());
            $this -> exists = true;
            $this -> syncOriginal();
        }

        return $result;
    }

    private function performUpdate(): bool {
        $attributes = $this -> getDirtyAttributes();

        if (empty($attributes)) {
            return true; // No changes
        }

        // Agregar updated_at si la tabla lo tiene
        if ($this -> hasTimestamps()) {
            $attributes['updated_at'] = date('Y-m-d H:i:s');
        }

        $sets = [];
        foreach(array_keys($attributes) as $column) {
            $sets[] = "$column = ?";
        }

        $sql = "UPDATE ".static:: $tabla. " SET ".implode(', ', $sets). " WHERE ".static:: $idTabla. " = ?";

        $values = array_values($attributes);
        $values[] = $this -> getAttribute(static:: $idTabla);

        $stmt = self:: $db -> prepare($sql);
        $result = $stmt -> execute($values);

        if ($result) {
            $this -> syncOriginal();
        }

        return $result;
    }

    private function getAttributesForInsert(): array {
        $attributes = [];
        foreach(static:: $columnasDB as $column) {
            if ($column !== static:: $idTabla && isset($this -> attributes[$column])) {
                $attributes[$column] = $this -> attributes[$column];
            }
        }
        return $attributes;
    }

    private function getDirtyAttributes(): array {
        $dirty = [];
        foreach($this -> attributes as $key => $value) {
            if (!array_key_exists($key, $this -> original) || $this -> original[$key] !== $value) {
                $dirty[$key] = $value;
            }
        }
        return $dirty;
    }

    public function delete(): bool {
        if (!$this -> exists) {
            return false;
        }

        // Soft delete
        if (in_array('situacion', static:: $columnasDB)) {
            $this -> setAttribute('situacion', 0);
            return $this -> save();
        }

        // Hard delete
        $sql = "DELETE FROM ".static:: $tabla. " WHERE ".static:: $idTabla. " = ?";
        $stmt = self:: $db -> prepare($sql);
        return $stmt -> execute([$this -> getAttribute(static:: $idTabla)]);
    }

    // Validation mejorada
    protected function validate(): bool {
        $this -> errors =[];

        foreach(static:: $rules as $field => $rules) {
            $value = $this -> getAttribute($field);
            $this -> validateField($field, $value, $rules);
        }

        return empty($this -> errors);
    }

    private function validateField(string $field, $value, array $rules): void {
        foreach($rules as $rule) {
            if (is_string($rule)) {
                $this -> applyRule($field, $value, $rule);
            } elseif(is_callable($rule)) {
                $result = $rule($value, $this);
                if ($result !== true) {
                    $this -> addError($field, $result ?: "Validation failed for $field");
                }
            }
        }
    }

    private function applyRule(string $field, $value, string $rule): void {
        if ($rule === 'required' && ($value === null || $value === '')) {
            $this -> addError($field, "El campo $field es requerido.");
            return;
        }

        if ($rule === 'email' && $value && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $this -> addError($field, "El campo $field debe ser un email válido.");
            return;
        }

        if (strpos($rule, 'min:') === 0) {
            $min = (int) substr($rule, 4);
            if (strlen($value) < $min) {
                $this -> addError($field, "El campo $field debe tener al menos $min caracteres.");
            }
            return;
        }

        if (strpos($rule, 'max:') === 0) {
            $max = (int) substr($rule, 4);
            if (strlen($value) > $max) {
                $this -> addError($field, "El campo $field no debe exceder $max caracteres.");
            }
            return;
        }

        if (strpos($rule, 'unique:') === 0) {
            $table = substr($rule, 7) ?: static:: $tabla;
            $this -> validateUnique($field, $value, $table);
            return;
        }
    }

    private function validateUnique(string $field, $value, string $table): void {
        if (!$value) return;

        $sql = "SELECT COUNT(*) FROM $table WHERE $field = ?";
        $params = [$value];

        // Excluir el registro actual si es una actualización
        if ($this -> exists) {
            $sql.= " AND ".static:: $idTabla. " != ?";
            $params[] = $this -> getAttribute(static:: $idTabla);
        }

        $stmt = self:: $db -> prepare($sql);
        $stmt -> execute($params);

        if ($stmt -> fetchColumn() > 0) {
            $this -> addError($field, "El valor del campo $field ya existe.");
        }
    }

    protected function addError(string $field, string $message): void {
        $this -> errors[$field][] = $message;
    }

    public function getErrors(): array {
        return $this -> errors;
    }

    public function getErrorMessages(): array {
        $messages = [];
        foreach($this -> errors as $field => $fieldErrors) {
            $messages = array_merge($messages, $fieldErrors);
        }
        return $messages;
    }

    public function hasErrors(): bool {
        return !empty($this -> errors);
    }

    private function syncOriginal(): void {
        $this -> original = $this -> attributes;
    }

    private function hasTimestamps(): bool {
        return in_array('created_at', static:: $columnasDB) && in_array('updated_at', static:: $columnasDB);
    }

    // Relaciones
    public function hasOne(string $related, string $foreignKey = null, string $localKey = null): ?object {
        $foreignKey = $foreignKey ?: static:: $idTabla;
        $localKey = $localKey ?: static:: $idTabla;

        return $related:: where($foreignKey, $this -> getAttribute($localKey)) -> first();
    }

    public function hasMany(string $related, string $foreignKey = null, string $localKey = null): array {
        $foreignKey = $foreignKey ?: static:: $idTabla;
        $localKey = $localKey ?: static:: $idTabla;

        return $related:: where($foreignKey, $this -> getAttribute($localKey)) -> get();
    }

    public function belongsTo(string $related, string $foreignKey = null, string $ownerKey = null): ?object {
        $foreignKey = $foreignKey ?: strtolower($related). '_id';
        $ownerKey = $ownerKey ?: 'id';

        return $related:: where($ownerKey, $this -> getAttribute($foreignKey)) -> first();
    }

    /**
     * Incluye relaciones con sufijo personalizable
     */
    public function atributosConRelaciones(string $sufijo = '_nombre'): array {
        $atributos = $this -> toArray();

        // Buscar relaciones automáticamente
        foreach($atributos as $key => $value) {
            if (strpos($key, 'id_') === 0) {
                $relacionNombre = substr($key, 3); // Quitar 'id_'
                $modeloRelacion = 'Model\\'.ucfirst($relacionNombre);

                if (class_exists($modeloRelacion)) {
                    $relacionado = $this -> belongsTo($modeloRelacion, $key);
                    if ($relacionado) {
                        $atributos[$relacionNombre.$sufijo] = $relacionado -> getAttribute('nombre');
                    }
                }
            }
        }

        return $atributos;
    }

    // Array/JSON conversion
    public function toArray(): array {
        $array = $this -> attributes;

        // Remove hidden attributes
        foreach(static:: $hidden as $hidden) {
            unset($array[$hidden]);
        }

        // Apply accessors
        foreach($array as $key => $value) {
            $array[$key] = $this -> getAttribute($key);
        }

        return $array;
    }

    public function toJson(): string {
        return json_encode($this -> toArray(), JSON_UNESCAPED_UNICODE);
    }

    // Magic methods
    public function __get(string $key) {
        return $this -> getAttribute($key);
    }

    public function __set(string $key, $value): void {
        $this -> setAttribute($key, $value);
    }

    public function __isset(string $key): bool {
        return isset($this -> attributes[$key]);
    }

    public function __toString(): string {
        return $this -> toJson();
    }
}