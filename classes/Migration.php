<?php

namespace Classes;

abstract class Migration
{
    protected static $db;

    public static function setDB($database): void
    {
        self::$db = $database;
    }

    abstract public function up(): void;
    abstract public function down(): void;

    protected function createTable(string $table, callable $callback): void
    {
        $schema = new SchemaBuilder($table);
        $callback($schema);

        $sql = $schema->toSQL();
        self::$db->exec($sql);
    }

    protected function dropTable(string $table): void
    {
        self::$db->exec("DROP TABLE IF EXISTS $table");
    }
}

class SchemaBuilder
{
    private string $table;
    private array $columns = [];
    private array $indexes = [];
    private array $constraints = [];

    public function __construct(string $table)
    {
        $this->table = $table;
    }

    public function id(string $name = 'id'): self
    {
        $this->columns[] = "$name SERIAL PRIMARY KEY";
        return $this;
    }

    public function string(string $name, int $length = 255): self
    {
        $this->columns[] = "$name VARCHAR($length)";
        return $this;
    }

    public function text(string $name): self
    {
        $this->columns[] = "$name TEXT";
        return $this;
    }

    public function integer(string $name): self
    {
        $this->columns[] = "$name INTEGER";
        return $this;
    }

    public function decimal(string $name, int $precision = 8, int $scale = 2): self
    {
        $this->columns[] = "$name DECIMAL($precision,$scale)";
        return $this;
    }

    public function boolean(string $name): self
    {
        $this->columns[] = "$name SMALLINT DEFAULT 0";
        return $this;
    }

    public function timestamp(string $name): self
    {
        $this->columns[] = "$name DATETIME YEAR TO SECOND";
        return $this;
    }

    public function timestamps(): self
    {
        $this->timestamp('created_at');
        $this->timestamp('updated_at');
        return $this;
    }

    public function foreign(string $column, string $references, string $on = null): self
    {
        $constraint = "FOREIGN KEY ($column) REFERENCES $references";
        if ($on) {
            $constraint .= "($on)";
        }
        $this->constraints[] = $constraint;
        return $this;
    }

    public function index(string $column): self
    {
        $this->indexes[] = "CREATE INDEX idx_{$this->table}_{$column} ON {$this->table} ($column)";
        return $this;
    }

    public function toSQL(): string
    {
        $sql = "CREATE TABLE {$this->table} (\n";
        $sql .= "  " . implode(",\n  ", $this->columns);

        if (!empty($this->constraints)) {
            $sql .= ",\n  " . implode(",\n  ", $this->constraints);
        }

        $sql .= "\n)";

        // Agregar índices después
        foreach ($this->indexes as $index) {
            $sql .= ";\n" . $index;
        }

        return $sql;
    }
}

// Ejemplo de migración
class CreateClientesTable extends Migration
{
    public function up(): void
    {
        $this->createTable('clientes', function ($table) {
            $table->id('id_cliente');
            $table->string('nombres', 80);
            $table->string('apellidos', 80);
            $table->string('telefono', 20);
            $table->string('correo', 100);
            $table->string('direccion', 200);
            $table->boolean('situacion')->default(1);
            $table->timestamps();

            $table->index('correo');
            $table->index('situacion');
        });
    }

    public function down(): void
    {
        $this->dropTable('clientes');
    }
}
