<?php

namespace Model;

use PDO;

class QueryBuilder
{
    private string $model;
    private array $wheres = [];
    private array $orders = [];
    private ?int $limit = null;
    private ?int $offset = null;
    private array $joins = [];
    private array $selects = ['*'];

    public function __construct(string $model)
    {
        $this->model = $model;
    }

    public function select(array $columns): self
    {
        $this->selects = $columns;
        return $this;
    }

    public function where(string $column, string $operator, $value = null): self
    {
        if ($value === null) {
            $value = $operator;
            $operator = '=';
        }

        $this->wheres[] = [
            'type' => 'where',
            'column' => $column,
            'operator' => $operator,
            'value' => $value,
            'boolean' => 'and'
        ];

        return $this;
    }

    public function orWhere(string $column, string $operator, $value = null): self
    {
        if ($value === null) {
            $value = $operator;
            $operator = '=';
        }

        $this->wheres[] = [
            'type' => 'where',
            'column' => $column,
            'operator' => $operator,
            'value' => $value,
            'boolean' => 'or'
        ];

        return $this;
    }

    public function whereIn(string $column, array $values): self
    {
        $this->wheres[] = [
            'type' => 'whereIn',
            'column' => $column,
            'values' => $values,
            'boolean' => 'and'
        ];

        return $this;
    }

    public function orderBy(string $column, string $direction = 'asc'): self
    {
        $this->orders[] = [
            'column' => $column,
            'direction' => strtolower($direction)
        ];

        return $this;
    }

    public function limit(int $limit): self
    {
        $this->limit = $limit;
        return $this;
    }

    public function offset(int $offset): self
    {
        $this->offset = $offset;
        return $this;
    }

    public function get(): array
    {
        $sql = $this->toSql();
        $bindings = $this->getBindings();

        $stmt = ActiveRecord::$db->prepare($sql);
        $stmt->execute($bindings);

        $results = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $instance = new $this->model();
            $instance->fill($row);
            $instance->exists = true;
            $results[] = $instance;
        }

        return $results;
    }

    public function first(): ?object
    {
        $this->limit(1);
        $results = $this->get();
        return $results[0] ?? null;
    }

    public function count(): int
    {
        $originalSelects = $this->selects;
        $this->selects = ['COUNT(*) as count'];

        $sql = $this->toSql();
        $bindings = $this->getBindings();

        $stmt = ActiveRecord::$db->prepare($sql);
        $stmt->execute($bindings);

        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $this->selects = $originalSelects;

        return (int) $result['count'];
    }

    private function toSql(): string
    {
        $model = new $this->model();
        $table = $model::$tabla;

        $sql = "SELECT " . implode(', ', $this->selects) . " FROM $table";

        // Add JOINs
        foreach ($this->joins as $join) {
            $sql .= " {$join['type']} JOIN {$join['table']} ON {$join['condition']}";
        }

        // Add WHERE clauses
        if (!empty($this->wheres)) {
            $sql .= " WHERE ";
            $whereClauses = [];

            foreach ($this->wheres as $index => $where) {
                $clause = '';

                if ($index > 0) {
                    $clause .= strtoupper($where['boolean']) . ' ';
                }

                if ($where['type'] === 'where') {
                    $clause .= "{$where['column']} {$where['operator']} ?";
                } elseif ($where['type'] === 'whereIn') {
                    $placeholders = str_repeat('?,', count($where['values']) - 1) . '?';
                    $clause .= "{$where['column']} IN ($placeholders)";
                }

                $whereClauses[] = $clause;
            }

            $sql .= implode(' ', $whereClauses);
        }

        // Add ORDER BY
        if (!empty($this->orders)) {
            $orderClauses = [];
            foreach ($this->orders as $order) {
                $orderClauses[] = "{$order['column']} " . strtoupper($order['direction']);
            }
            $sql .= " ORDER BY " . implode(', ', $orderClauses);
        }

        // Add LIMIT and OFFSET
        if ($this->limit !== null) {
            $sql .= " LIMIT {$this->limit}";

            if ($this->offset !== null) {
                $sql .= " OFFSET {$this->offset}";
            }
        }

        return $sql;
    }

    private function getBindings(): array
    {
        $bindings = [];

        foreach ($this->wheres as $where) {
            if ($where['type'] === 'where') {
                $bindings[] = $where['value'];
            } elseif ($where['type'] === 'whereIn') {
                $bindings = array_merge($bindings, $where['values']);
            }
        }

        return $bindings;
    }
}
