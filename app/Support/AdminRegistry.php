<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use InvalidArgumentException;

class AdminRegistry
{
    public function tabs(): array
    {
        return config('hawat.tabs', []);
    }

    public function tab(string $key): array
    {
        $tab = Arr::get($this->tabs(), $key);

        if (! $tab) {
            throw new InvalidArgumentException("Unknown admin tab [{$key}].");
        }

        return $tab + ['key' => $key];
    }

    public function defaultTab(): string
    {
        return config('hawat.default_tab', array_key_first($this->tabs()));
    }

    public function resource(string $key): array
    {
        $resource = config("hawat_resources.{$key}");

        if (! $resource) {
            throw new InvalidArgumentException("Unknown admin resource [{$key}].");
        }

        return $resource + ['key' => $key, 'readonly' => false, 'badges' => []];
    }

    public function resourcesForTab(string $tabKey): array
    {
        $tab = $this->tab($tabKey);

        return collect(Arr::get($tab, 'resources', []))
            ->mapWithKeys(fn (string $key) => [$key => $this->resource($key)])
            ->all();
    }

    public function model(string $resourceKey): Model
    {
        $class = $this->resource($resourceKey)['model'];

        return new $class;
    }

    public function records(string $resourceKey)
    {
        return $this->model($resourceKey)
            ->newQuery()
            ->latest('id')
            ->paginate(25)
            ->withQueryString();
    }

    public function fields(string $resourceKey): array
    {
        return collect($this->resource($resourceKey)['fields'] ?? [])
            ->map(function (array $field) {
                $field['type'] ??= 'text';
                $field['required'] ??= false;
                $field['options'] = $this->resolveOptions($field);

                return $field;
            })
            ->all();
    }

    public function validationRules(string $resourceKey): array
    {
        return collect($this->fields($resourceKey))
            ->mapWithKeys(function (array $field) {
                $rules = [$field['required'] ? 'required' : 'nullable'];

                $rules[] = match ($field['type']) {
                    'number' => 'numeric',
                    'boolean' => 'boolean',
                    'date' => 'date',
                    default => 'string',
                };

                if ($field['type'] === 'select' && ! empty($field['options'])) {
                    $rules[] = 'in:' . implode(',', $field['options']);
                }

                if (in_array($field['type'], ['text', 'textarea'], true)) {
                    $rules[] = $field['type'] === 'textarea' ? 'max:5000' : 'max:255';
                }

                return [$field['key'] => $rules];
            })
            ->all();
    }

    public function normalize(string $resourceKey, array $data): array
    {
        foreach ($this->fields($resourceKey) as $field) {
            $key = $field['key'];

            if ($field['type'] === 'boolean') {
                $data[$key] = (bool) ($data[$key] ?? false);

                continue;
            }

            if (($data[$key] ?? null) === '') {
                $data[$key] = null;
            }
        }

        return $data;
    }

    private function resolveOptions(array $field): array
    {
        if (! empty($field['options'])) {
            return $field['options'];
        }

        if (empty($field['options_from'])) {
            return [];
        }

        $class = $field['options_from']['model'];
        $column = $field['options_from']['column'];

        return (new $class)->newQuery()
            ->whereNotNull($column)
            ->orderBy($column)
            ->pluck($column)
            ->unique()
            ->values()
            ->all();
    }
}