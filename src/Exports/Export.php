<?php

namespace Konnco\FilamentHelper\Exports;

use AnourValar\EloquentSerialize\Facades\EloquentSerializeFacade as EloquentSerialize;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Builder;
use Laravel\SerializableClosure\Exceptions\PhpVersionNotSupportedException;
use Laravel\SerializableClosure\SerializableClosure;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class Export implements FromQuery, ShouldQueue, WithHeadings, WithMapping
{
    use Exportable;

    protected string $query;

    protected array $headings = [];

    protected string $map;

    /**
     * @throws PhpVersionNotSupportedException
     */
    public function __construct(Builder $query, $headings = [], $map = null)
    {
        $this->query = EloquentSerialize::serialize($query);
        $this->headings = $headings;

        $this->map = serialize(new SerializableClosure($map));
    }

    public function query(): Builder
    {
        return EloquentSerialize::unserialize($this->query);
    }

    public function headings(): array
    {
        return $this->headings;
    }

    public function map($row): array
    {
        $closure = unserialize($this->map)->getClosure();

        return $closure($row);
    }
}
