<?php

namespace MaDnh\LaravelDevHelper\RequestProfile\Handlers;


use Illuminate\Support\Collection;
use MaDnh\LaravelDevHelper\RequestProfile\Handler;

class SelectBoxFormat extends Handler
{
    public function processData(&$data, $is_collection = false, $options = [])
    {
        if (!$this->profile->has('is_select_box', true) || !$this->profile->is('is_select_box', null)) {
            return;
        }
        if ($data instanceof Collection) {
            $data = $data->toArray();
        }

        $options = array_merge([
            'value_field' => 'id',
            'text_field' => 'id',
        ], $options);

        $result = [];

        if ($is_collection) {
            $value_field = $this->profile->get('value_field', $options['value_field']);
            $text_field = $this->profile->get('text_field', $options['text_field']);

            foreach ($data as $item) {
                if (array_key_exists($value_field, $item) && array_key_exists($text_field, $item)) {
                    $result[] = [
                        'value' => $item[$value_field],
                        'text' => $item[$text_field]
                    ];
                }
            }
        } else {
            foreach ($data as $key => $value) {
                $result[] = [
                    'value' => $key,
                    'text' => $value
                ];
            }
        }

        $data = $result;
    }
}