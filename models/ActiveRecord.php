<?php
namespace Model;

use PDO;
use PDOException;
use Exception;

abstract class ActiveRecord
{
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

    public function __construct(array $attributes = [])
    {
        $this->fill($attributes);
        $this->syncOriginal();
    }

    public static function setDB(PDO $database): void
    {
        self::$db = $database;
    }

    // Mass assignment protection
    public function fill(array $attributes): self
    {
        foreach ($attributes as $key => $value) {
            if (in_array($key, static::$fillable) || empty(static::$fillable)) {
                $this->setAttribute($key, $value);
            }
        }
        return $this;
    }

    public function setAttribute(string $key, $value): void
    {
        if (isset(static::$casts[$key])) {
            $value = $this->castValue($value, static::$casts[$key]);
        }
        $this->attributes[$key] = $value;
    }

    public function getAttribute(string $key)
    {
        return $this->attributes[$key] ?? null;
    }

    private function castValue($value, string $type)
    {
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
            default:
                return $value;
        }
    }

    // Query Builder Pattern
    public static function query(): QueryBuilder
    {
        return new QueryBuilder(static::class);
    }

    public static function find($id): ?static
    {
        return static::query()->where(static::$idTabla, $id)->first();
    }

    public static function findOrFail($id): static
    {
        $model = static::find($id);
        if (!$model) {
            throw new Exception("Model not found with ID: $id");
        }
        return $model;
    }

    public static function all(): array
    {
        return static::query()->where('situacion', 1)->get();
    }

    public static function where(string $column, $operator, $value = null): QueryBuilder
    {
        if ($value === null) {
            $value = $operator;
            $operator = '=';
        }
        return static::query()->where($column, $operator, $value);
    }

    // Save methods
    public function save(): bool
    {
        if (!$this->validate()) {
            return false;
        }

        try {
            if ($this->exists) {
                return $this->performUpdate();
            } else {
                return $this->performInsert();
            }
        } catch (PDOException $e) {
            $this->addError('database', $e->getMessage());
            return false;
        }
    }

    private function performInsert(): bool
    {
        $attributes = $this->getAttributesForInsert();
        
        if (empty($attributes)) {
            return false;
        }

        $columns = array_keys($attributes);
        $placeholders = array_fill(0, count($columns), '?');
        
        $sql = "INSERT INTO " . static::$tabla . " (" . implode(', ', $columns) . ") VALUES (" . implode(', ', $placeholders) . ")";
        
        $stmt = self::$db->prepare($sql);
        $result = $stmt->execute(array_values($attributes));
        
        if ($result) {
            $this->setAttribute(static::$idTabla, self::$db->lastInsertId());
            $this->exists = true;
            $this->syncOriginal();
        }
        
        return $result;
    }

    private function performUpdate(): bool
    {
        $attributes = $this->getDirtyAttributes();
        
        if (empty($attributes)) {
            return true; // No changes
        }

        $sets = [];
        foreach (array_keys($attributes) as $column) {
            $sets[] = "$column = ?";
        }
        
        $sql = "UPDATE " . static::$tabla . " SET " . implode(', ', $sets) . " WHERE " . static::$idTabla . " = ?";
        
        $values = array_values($attributes);
        $values[] = $this->getAttribute(static::$idTabla);
        
        $stmt = self::$db->prepare($sql);
        $result = $stmt->execute($values);
        
        if ($result) {
            $this->syncOriginal();
        }
        
        return $result;
    }

    private function getAttributesForInsert(): array
    {
        $attributes = [];
        foreach (static::$columnasDB as $column) {
            if ($column !== static::$idTabla && isset($this->attributes[$column])) {
                $attributes[$column] = $this->attributes[$column];
            }
        }
        return $attributes;
    }

    private function getDirtyAttributes(): array
    {
        $dirty = [];
        foreach ($this->attributes as $key => $value) {
            if (!array_key_exists($key, $this->original) || $this->original[$key] !== $value) {
                $dirty[$key] = $value;
            }
        }
        return $dirty;
    }

    public function delete(): bool
    {
        if (!$this->exists) {
            return false;
        }

        // Soft delete
        if (in_array('situacion', static::$columnasDB)) {
            $this->setAttribute('situacion', 0);
            return $this->save();
        }

        // Hard delete
        $sql = "DELETE FROM " . static::$tabla . " WHERE " . static::$idTabla . " = ?";
        $stmt = self::$db->prepare($sql);
        return $stmt->execute([$this->getAttribute(static::$idTabla)]);
    }

    // Validation
    protected function validate(): bool
    {
        $this->errors = [];
        
        foreach (static::$rules as $field => $rules) {
            $value = $this->getAttribute($field);
            $this->validateField($field, $value, $rules);
        }
        
        return empty($this->errors);
    }

    private function validateField(string $field, $value, array $rules): void
    {
        foreach ($rules as $rule) {
            if ($rule === 'required' && ($value === null || $value === '')) {
                $this->addError($field, "The $field field is required.");
                continue;
            }
            
            if ($rule === 'email' && $value && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                $this->addError($field, "The $field must be a valid email address.");
                continue;
            }
            
            if (strpos($rule, 'min:') === 0) {
                $min = (int) substr($rule, 4);
                if (strlen($value) < $min) {
                    $this->addError($field, "The $field must be at least $min characters.");
                }
                continue;
            }
            
            if (strpos($rule, 'max:') === 0) {
                $max = (int) substr($rule, 4);
                if (strlen($value) > $max) {
                    $this->addError($field, "The $field must not exceed $max characters.");
                }
                continue;
            }
        }
    }

    protected function addError(string $field, string $message): void
    {
        $this->errors[$field][] = $message;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function hasErrors(): bool
    {
        return !empty($this->errors);
    }

    private function syncOriginal(): void
    {
        $this->original = $this->attributes;
    }

    // Array/JSON conversion
    public function toArray(): array
    {
        $array = $this->attributes;
        
        // Remove hidden attributes
        foreach (static::$hidden as $hidden) {
            unset($array[$hidden]);
        }
        
        return $array;
    }

    public function toJson(): string
    {
        return json_encode($this->toArray(), JSON_UNESCAPED_UNICODE);
    }

    // Magic methods
    public function __get(string $key)
    {
        return $this->getAttribute($key);
    }

    public function __set(string $key, $value): void
    {
        $this->setAttribute($key, $value);
    }

    public function __isset(string $key): bool
    {
        return isset($this->attributes[$key]);
    }
}