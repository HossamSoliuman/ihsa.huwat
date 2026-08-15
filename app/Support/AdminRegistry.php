<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use InvalidArgumentException;

class AdminRegistry
{
    public function tabs(): array
    {
        return config('info.tabs', []);
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
        return config('info.default_tab', array_key_first($this->tabs()));
    }

    public function resource(string $key): array
    {
        $resource = config("info_resources.{$key}");

        if (! $resource) {
            throw new InvalidArgumentException("Unknown admin resource [{$key}].");
        }

        return $resource + ['key' => $key, 'readonly' => false, 'badges' => [], 'with' => []];
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
            ->with($this->resource($resourceKey)['with'])
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
                $isBoundSelect = $field['type'] === 'select' && ! empty($field['options']);

                /*
                 * القائمة المحصورة بـ in: تحكم قيمتها بالكامل، فلا يُفرض عليها نوع.
                 * فرض `string` هنا يرفض مفاتيح الجداول المرتبطة حين تصل أعدادًا صحيحة.
                 */
                $typeRule = match (true) {
                    $isBoundSelect => null,
                    $field['type'] === 'number' => 'numeric',
                    $field['type'] === 'boolean' => 'boolean',
                    $field['type'] === 'date' => 'date',
                    default => 'string',
                };

                if ($typeRule !== null) {
                    $rules[] = $typeRule;
                }

                // القوائم مخزّنة كـ [القيمة => التسمية]، والتحقق يجري على القيمة لا على ما يُعرض.
                if ($isBoundSelect) {
                    $rules[] = 'in:' . implode(',', array_keys($field['options']));
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

    /**
     * تُعيد القائمة دائمًا بالشكل [القيمة => التسمية]. القوائم الثابتة تُخزَّن قيمتها
     * كنصّها، أما القوائم المشتقّة من موديل فتدعم شكلين: عمود واحد (القيمة هي التسمية)،
     * أو `value` + `label` لحقول المفاتيح الأجنبية حيث تُخزَّن id وتُعرض التسمية.
     */
    private function resolveOptions(array $field): array
    {
        if (! empty($field['options'])) {
            $options = $field['options'];

            return array_is_list($options) ? array_combine($options, $options) : $options;
        }

        if (empty($field['options_from'])) {
            return [];
        }

        $source = $field['options_from'];
        $query = (new $source['model'])->newQuery();

        if (isset($source['value'], $source['label'])) {
            return $query->orderBy($source['label'])
                ->pluck($source['label'], $source['value'])
                ->all();
        }

        $column = $source['column'];

        $values = $query->whereNotNull($column)
            ->orderBy($column)
            ->pluck($column)
            ->unique()
            ->values()
            ->all();

        return array_combine($values, $values);
    }
}