<?php

namespace Classes;

class SearchEngine
{
    private array $searchableFields = [];
    private array $filters = [];
    private array $sorts = [];
    private int $limit = 50;
    private int $offset = 0;

    public function searchable(array $fields): self
    {
        $this->searchableFields = $fields;
        return $this;
    }

    public function filter(string $field, $value, string $operator = '='): self
    {
        $this->filters[] = compact('field', 'value', 'operator');
        return $this;
    }

    public function sort(string $field, string $direction = 'asc'): self
    {
        $this->sorts[] = compact('field', 'direction');
        return $this;
    }

    public function paginate(int $page = 1, int $perPage = 50): self
    {
        $this->limit = $perPage;
        $this->offset = ($page - 1) * $perPage;
        return $this;
    }

    public function search(string $model, string $query = ''): array
    {
        $queryBuilder = $model::query();

        // Búsqueda de texto
        if (!empty($query) && !empty($this->searchableFields)) {
            $searchConditions = [];
            foreach ($this->searchableFields as $field) {
                $searchConditions[] = "$field LIKE ?";
            }

            $queryBuilder->whereRaw(
                '(' . implode(' OR ', $searchConditions) . ')',
                array_fill(0, count($this->searchableFields), "%$query%")
            );
        }

        // Filtros
        foreach ($this->filters as $filter) {
            $queryBuilder->where($filter['field'], $filter['operator'], $filter['value']);
        }

        // Ordenamiento
        foreach ($this->sorts as $sort) {
            $queryBuilder->orderBy($sort['field'], $sort['direction']);
        }

        // Paginación
        if ($this->limit > 0) {
            $queryBuilder->limit($this->limit);
            if ($this->offset > 0) {
                $queryBuilder->offset($this->offset);
            }
        }

        return $queryBuilder->get();
    }

    public static function quick(string $model, string $query, array $fields): array
    {
        return (new self())
            ->searchable($fields)
            ->search($model, $query);
    }
}
