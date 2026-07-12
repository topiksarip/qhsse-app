<?php

namespace App\Core\Query;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class ListQuery
{
    private ?Builder $query = null;

    public function __construct(
        private readonly Request $request,
    ) {
    }

    public static function for(Builder $query, ?Request $request = null): self
    {
        $instance = new self($request ?? request());
        $instance->query = $query;

        return $instance;
    }

    /**
     * Apply standard search, active-state filtering, sorting, and pagination.
     *
     * @param  Builder<\Illuminate\Database\Eloquent\Model>|int|null  $query
     * @param  array<int, string>  $searchable
     * @param  array<int, string>  $allowedSorts
     */
    public function paginate(
        Builder|int|null $query = null,
        array $searchable = [],
        array $allowedSorts = ['name', 'code', 'created_at'],
        string $defaultSort = 'name',
        int $defaultPerPage = 10,
    ): LengthAwarePaginator {
        if (! $query instanceof Builder) {
            $perPage = is_int($query) ? $query : $defaultPerPage;

            return $this->builder()
                ->paginate($this->perPage($perPage))
                ->withQueryString();
        }

        $this->apply($query, $searchable, $allowedSorts, $defaultSort);

        return $query
            ->paginate($this->perPage($defaultPerPage))
            ->withQueryString();
    }

    /** @param array<int, string> $columns */
    public function search(array $columns, mixed $value): self
    {
        $search = trim((string) $value);

        if ($search !== '') {
            $this->builder()->where(function (Builder $query) use ($columns, $search): void {
                foreach ($columns as $column) {
                    $query->orWhere($column, 'like', "%{$search}%");
                }
            });
        }

        return $this;
    }

    public function filter(string $column, mixed $value): self
    {
        if ($value !== null && $value !== '') {
            $this->builder()->where($column, $value);
        }

        return $this;
    }

    public function sort(string $column, string $direction = 'asc'): self
    {
        $this->builder()->orderBy($column, strtolower($direction) === 'desc' ? 'desc' : 'asc');

        return $this;
    }

    public function defaultSort(string $sort): self
    {
        $descending = str_starts_with($sort, '-');

        return $this->sort(ltrim($sort, '-'), $descending ? 'desc' : 'asc');
    }

    /** @return Collection<int, \Illuminate\Database\Eloquent\Model> */
    public function get(): Collection
    {
        return $this->builder()->get();
    }

    /**
     * @param  Builder<\Illuminate\Database\Eloquent\Model>  $query
     * @param  array<int, string>  $searchable
     * @param  array<int, string>  $allowedSorts
     */
    public function apply(
        Builder $query,
        array $searchable,
        array $allowedSorts = ['name', 'code', 'created_at'],
        string $defaultSort = 'name',
    ): Builder {
        $search = trim((string) $this->request->query('search', ''));

        if ($search !== '') {
            $query->where(function (Builder $builder) use ($searchable, $search): void {
                foreach ($searchable as $column) {
                    $builder->orWhere($column, 'like', "%{$search}%");
                }
            });
        }

        if ($this->request->filled('is_active')) {
            $query->where('is_active', $this->request->boolean('is_active'));
        }

        $sort = (string) $this->request->query('sort', $defaultSort);
        if (! in_array($sort, $allowedSorts, true)) {
            $sort = $defaultSort;
        }

        $direction = $this->request->query('direction') === 'desc' ? 'desc' : 'asc';

        return $query->orderBy($sort, $direction);
    }

    /** @return array{search: string, is_active: string|null, sort: string|null, direction: string|null, per_page: string|null} */
    public function filters(): array
    {
        return array_merge([
            'search' => '',
            'is_active' => null,
            'sort' => null,
            'direction' => null,
            'per_page' => null,
        ], $this->request->only(['search', 'is_active', 'sort', 'direction', 'per_page']));
    }

    private function perPage(int $default): int
    {
        $perPage = (int) $this->request->query('per_page', $default);

        return min(max($perPage, 5), 100);
    }

    private function builder(): Builder
    {
        if (! $this->query) {
            throw new \LogicException('A query builder is required for fluent ListQuery operations.');
        }

        return $this->query;
    }
}
