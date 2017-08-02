<?php
namespace MaDnh\LaravelDevHelper\Util;

use MaDnh\LaravelDevHelper\RequestProfile\Profile;

class DataTable
{
    /**
     * @param array $request
     * @return array
     */
    public static function handle(array $request)
    {
        $params = array_merge(array(
            'draw' => 1,
            'columns' => array(),
            'order' => array(),
            'search' => array(
                'value' => ''
            ),
            'paging' => false
        ), $request);
        $params['paging'] = array_key_exists('start', $request);

        $params['search'] = array_merge(array(
            'value' => ''
        ), $params['search']);
        $params['search']['value'] = trim($params['search']['value']);
        $params['order'] = array_map(function ($order_detail) {
            return array_merge(array(
                'column' => '',
                'dir' => 'asc'
            ), (array)$order_detail);
        }, (array)$params['order']);

        $result = array(
            'columns' => $params['columns'],
            'draw' => $params['draw'],
            'query' => $params['search']['value'],
            'order' => array(),
            'order_string' => '',
            'paging' => $params['paging']
        );
        if ($params['paging']) {
            $result['start'] = max(0, intval($params['start']));
            $result['length'] = max(min(intval($params['length']), 100), 15);
        }

        foreach ($params['order'] as $order_detail) {
            if (array_key_exists($order_detail['column'], $params['columns'])
                && strlen(trim($params['columns'][$order_detail['column']]['name']))
                && in_array($params['columns'][$order_detail['column']]['orderable'], array(true, 'true'))
            ) {
                $result['order'][] = array(
                    'column' => $params['columns'][$order_detail['column']]['name'],
                    'direction' => (strtolower($order_detail['dir']) === 'asc') ? 'asc' : 'desc'
                );
            }
        }

        if (!empty($result['order'])) {
            $tmp_order_string = array();
            foreach ($result['order'] as $order_detail) {
                $tmp_order_string[] = $order_detail['column'] . ' ' . $order_detail['direction'];
            }
            $result['order_string'] = implode(', ', $tmp_order_string);
        }

        return $result;
    }

    public static function result($data, $total = null, $total_filtered = null)
    {
        $total = $total !== null ? $total : count($data);
        $pr = new ProcessResult();
        $pr->addField('draw', request()->input('draw', 1));
        $pr->addField('recordsTotal', $total);
        $pr->addField('recordsFiltered', $total_filtered !== null ? $total_filtered : $total);
        $pr->dataAsArray(true);

        foreach ($data as $row) {
            $pr->addData($row);
        }

        return $pr->export();
    }

    public static function error($message)
    {
        $pr = new ProcessResult();

        $pr->addField('draw', request()->input('draw', 1));
        $pr->addField('recordsTotal', 0);
        $pr->addField('recordsFiltered', 0);
        $pr->dataAsArray(true);

        $pr->addMeta('error', $message . '');


        return $pr->export();
    }

    /**
     * Handle request from DataTable and return request profile
     * @return Profile
     */
    public static function dataTableRequestProfile()
    {
        $data = self::handle(request()->all());
        $profile = Profile::withOverride($data);

        return $profile;
    }
}