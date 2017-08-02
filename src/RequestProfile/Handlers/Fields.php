<?php

namespace MaDnh\LaravelDevHelper\RequestProfile\Handlers;

use Illuminate\Support\Collection;
use MaDnh\LaravelDevHelper\RequestProfile\Handler;

class Fields extends Handler
{
    /**
     * White list fields
     * @var null|array
     */
    public $fields = null;

    /**
     * @var null|array
     */
    public $default_fields = null;

    /**
     * Black list fields
     * @var array
     */
    public $excepts = [];

    /**
     * Use null for missing fields, if not, missing fields is excluded from result
     * @var bool
     */
    public $null_for_missing = true;

    public function useNullForMissing($use_null = true)
    {
        $this->null_for_missing = $use_null && true;

        return $this;
    }

    public function withs($fields)
    {
        if (is_null($fields)) {
            $this->fields = null;
        } else {
            $this->fields = array_flatten(func_get_args());
        }

        return $this;
    }

    public function defaultFields($fields = null)
    {
        if (is_null($fields)) {
            $this->default_fields = null;
        } else {
            $this->default_fields = array_flatten(func_get_args());
        }

        return $this;
    }

    public function excepts($excepts)
    {
        $this->excepts = array_flatten(func_get_args());

        return $this;
    }

    /**
     * @param $fields
     * @return array|null
     */
    protected function clean_fields($fields)
    {
        if (is_array($this->fields)) {
            if (!is_array($fields)) {
                return $this->fields;
            }

            return array_intersect($fields, $this->fields);
        }

        return $fields;
    }

    /**
     * @return array|null
     */
    public function getFields()
    {
        $default_fields = $this->clean_fields($this->default_fields);

        if (!$this->profile->has('fields', true)) {
            return $default_fields;
        }

        $input_fields = $this->profile->get('fields', []);

        if (true === $input_fields) {
            return null;
        }

        if (is_string($input_fields)) {
            $input_fields = array_map('trim', explode(',', $input_fields));
        }

        if (!empty($input_fields)) {
            return (array)$input_fields;
        }

        return $default_fields;
    }

    public function getExcepts()
    {
        return array_unique(array_merge($this->excepts, (array)$this->profile->get('excepts', [])));
    }

    /**
     * @param array $item
     * @return array
     */
    public function processDataItem($item)
    {
        if ($item instanceof Collection) {
            $item = $item->toArray();
        }

        $excepts = $this->getExcepts();
        $fields = $this->getFields();
        if (!empty($excepts)) {
            array_forget($item, $this->excepts);
        }
        if (!is_array($fields)) {
            return $item;
        }

        $result = [];

        if ($this->profile->get('null_for_missing', $this->null_for_missing)) {
            foreach ($fields as $field) {
                array_set($result, $field, array_get($item, $field, null));
            }
        } else {
            foreach ($fields as $field) {
                if (array_has($item, $field)) {
                    array_set($result, $field, array_get($item, $field));
                }
            }
        }

        return $result;
    }

    /**
     * Process data before return to client
     * @param array|Collection $data
     * @param bool $is_collection Data is an collection of items
     */
    public function processData(&$data, $is_collection)
    {
        if ($data instanceof Collection) {
            $data = $data->toArray();
        }

        if (!$is_collection) {
            $data = $this->processDataItem($data);
        }

        foreach ($data as $key => $value) {
            $data[$key] = $this->processDataItem($value instanceof Collection ? $value->toArray() : $value);
        }
    }
}