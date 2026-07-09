<?php

namespace App\Core\Query;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class ListQuery
{
    public function __construct(
        private readonly Request $request,
    ) {
    }

    /**
     * Apply standard search, active-state filtering, sorting, and pagination.
     *
     * @param  Builder<\Illuminate\Database\Eloquent\Model>  $query
     * @param  array<int, string>  $searchable
     * @param  array<int, string>  $allowedSorts
     */
    public function paginate(
        Builder $query,
        array $searchable,
        array $allowedSorts = ['name', 'code', 'created_at'],
        string $defaultSort = 'name',
        int $defaultPerPage = 10,
    ): LengthAwarePaginator {
        $this->apply($query, $searchable, $allowedSorts, $defaultSort);

        return $query
            ->paginate($this->perPage($defaultPerPage))
            ->withQueryString();
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

    /** @return array{search?: string, is_active?: string, sort?: string, direction?: string, per_page?: string} */
    public function filters(): array
    {
        return $this->request->only(['search', 'is_active', 'sort', 'direction', 'per_page']);
    }

    private function perPage(int $default): int
    {
        $perPage = (int) $this->request->query('per_page', $default);

        return min(max($perPage, 5), 100);
    }
}
