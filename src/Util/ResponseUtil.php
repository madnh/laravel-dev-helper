<?php

namespace MaDnh\LaravelDevHelper\Util;

use Illuminate\Http\JsonResponse;
use MaDnh\LaravelDevHelper\Exceptions\Views\AjaxVersionOfViewNotFound;
use MaDnh\LaravelDevHelper\Helper;

class ResponseUtil
{
    const INFO = 'info';
    const SUCCESS = 'success';
    const ERROR = 'error';
    const WARNING = 'warning';


    /**
     * Send data as json
     * @param $data
     * @param array $option Array of options:
     * - meta: array, result meta
     * - fields: array, result fields
     * - data_as_array: boolean, send data as array
     * - status: integer, response http status code
     * - headers: array, response headers
     * - option: response options
     *
     * @param null $response_obj
     * @return \Illuminate\Http\JsonResponse
     * @throws \Exception
     */
    public static function jsonData($data, $option = array(), $response_obj = null)
    {
        if (null === $response_obj) {
            $response_obj = response();
        }
        $option = array_merge(array(
            'meta' => [],
            'fields' => [],
            'data_as_array' => false,
            'status' => 200,
            'headers' => [],
            'option' => JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE
        ), $option);
        $result = new ProcessResult();

        if ($option['data_as_array']) {
            $result->dataAsArray(true);
        }
        foreach ($data as $name => $value) {
            $result->addData($name, $value);
        }

        foreach ($option['meta'] as $name => $value) {
            $result->addMeta($name, $value);
        }

        foreach ($option['fields'] as $name => $value) {
            $result->addField($name, $value);
        }

        return $response_obj->json($result->export(), $option['status'], $option['headers'], $option['option']);
    }

    /**
     * @param string $html
     * @param array $option
     * @param null $response_obj
     * @return JsonResponse
     */
    public static function jsonView($html = '', $option = array(), $response_obj = null)
    {
//        if (\View::exists($html)) {
//            $html = view($html);
//        }

        $data = [
            'html' => $html
        ];

        return static::jsonData($data, $option, $response_obj);
    }

    /**
     * Send error as json
     * @param string|\Exception $message
     * @param array $option Array of options:
     * - meta: array, result meta
     * - fields: array, result fields
     * - code: numeric, option, error code, default is 0
     * - errors: array, option, array of errors. Each item is string or array of keys are message and code, or array with 2 item as message and code
     * - status: integer, response http status code
     * - headers: array, response headers
     * - option: response options
     *
     * @param null $response_obj
     * @return \Illuminate\Http\JsonResponse
     * @throws \Exception
     */
    public static function jsonError($message, $option = array(), $response_obj = null)
    {
        if (null === $response_obj) {
            $response_obj = response();
        }
        $option = array_merge(array(
            'code' => 0,
            'meta' => [],
            'fields' => [],
            'errors' => [],
            'status' => 200,
            'headers' => [],
            'option' => 0
        ), $option);
        $result = new ProcessResult();


        if (is_a($message, '\Exception')) {
            $message = $message->getMessage();
        }

        $result->error($message, $option['code']);

        foreach ($option['errors'] as $error) {
            if (is_string($error)) {
                $result->addError($error, $option['code']);
            }
            if (is_array($error) && !empty($error)) {
                $err_mess = '';
                $err_code = 0;
                $err_found_by_key = false;
                if (array_key_exists('message', $error)) {
                    $err_mess = $error['message'];
                    $err_found_by_key = true;
                }

                if (array_key_exists('code', $error)) {
                    $err_code = $error['code'];
                    $err_found_by_key = true;
                }
                if (!$err_found_by_key) {
                    unset($error['message']);
                    unset($error['code']);
                    if (!empty($error)) {
                        $err_mess = array_shift($error);
                        $err_code = array_shift($error);
                        if (!is_numeric($err_code)) {
                            throw new \Exception('Invalid error detail: ' . json_encode($error));
                        }
                    }
                }

                if ($err_mess && is_numeric($err_code)) {
                    $result->addError($err_mess, $err_code);
                }
            }
        }

        foreach ($option['meta'] as $name => $value) {
            $result->addMeta($name, $value);
        }
        foreach ($option['fields'] as $name => $value) {
            $result->addField($name, $value);
        }

        return $response_obj->json($result->export(), $option['status'], $option['headers'], $option['option']);
    }

    /**
     * @param string [$message]
     * @param array [$option]
     * @param null [$response_obj]
     * @return \Illuminate\Http\JsonResponse
     */
    public static function jsonSuccess($message = '', $option = array(), $response_obj = null)
    {
        $data = [
            'result' => 'success'
        ];

        if (!empty($message)) {
            $data['message'] = $message;
        }

        return static::jsonData($data, $option, $response_obj);
    }

    /**
     * @param $view
     * @param $data
     * @param string|array $options Render options:
     * - ajax_view: view use when request is ajax. Response is JSON
     * - layout: Layout name. Variable in view is _layout
     *
     * If $options is string then it is value of layout
     *
     * @return JsonResponse|\Response|\View
     */
    public static function view($view, $data, $options = [])
    {
        $request = request();
        $is_ajax = $request->ajax();

        if (is_string($options)) {
            $options = ['layout' => $options];
        }

        $options = array_merge([
            'layout' => null,
            'ajax_view' => null,
            'ajax_layout' => config('view.ajax_layout', null)
        ], $options);

        if($is_ajax){
            if (empty($options['ajax_view'])) {
                $view_ajax = $view . '_ajax';
                $options['ajax_view'] = view()->exists($view_ajax) ? $view_ajax : $view;
            }

            $view = Helper::firstNotEmpty($options['ajax_view'], $view);
        }
        if (!empty($options['layout'])) {
            $data['_layout'] = $options['layout'];
        }

        if ($is_ajax) {
            return static::jsonView(view($view, $data)->render());
        }

        return view($view, $data);
    }

    /**
     * Return view, support ajax version if request need ajax
     * ajax version of a view is view path with suffix is "_ajax"
     * @param string $view
     * @param array $data
     * @param array $options
     * @return JsonResponse|\Response|\View
     * @throws AjaxVersionOfViewNotFound
     */
    public static function viewSupportAJAX($view, $data, $options = [])
    {
        $viewAjax = $view . '_ajax';

        if (!view()->exists($viewAjax)) {
            $viewAjax = $view;
        }

        $options['ajax_view'] = $viewAjax;

        return static::view($view, $data, $options);
    }


    /**
     * @param string $to If not special then return previous page (with inputs)
     * @param array $options
     * @return mixed
     */
    public static function redirect($to = null, $options = [])
    {
        $request = request();
        $options = array_merge([
            'message' => null,
            'message_type' => self::SUCCESS,
            'data' => null
        ], $options);

        if (!empty($options['message'])) {
            if ($options['message'] instanceof \Exception) {
                $options['message'] = $options['message']->getMessage();
            }
            if (empty($options['message_type'])) {
                $options['message_type'] = $options['message_type'] instanceof \Exception ? self::ERROR : self::SUCCESS;
            }
        }

        if ($request->ajax() || $request->wantsJson()) {
            if (empty($options['message']) && !empty($options['message_type'])) {
                switch ($options['message_type']) {
                    case self::ERROR:
                        $options['message'] = 'Error';
                        break;
                    default:
                        $options['message'] = 'Success';
                }
            }
            if ($options['message_type'] == self::ERROR) {
                return static::jsonError($options['message']);
            }

            $data = [
                'result' => Helper::firstNotEmpty($options['message'], 'Success')
            ];
            if (!empty($options['data'])) {
                $data['data'] = $options['data'];
            }

            return static::jsonData($data);
        }

        if (!empty($options['message'])) {
            \Flash::message($options['message'], Helper::firstNotEmpty($options['message_type'], self::SUCCESS));
        }

        return $to ? redirect($to) : back()->withInput();
    }

    /**
     * @param string|array|\Exception $message
     * @param string $messageType
     * @param string $to
     * @return JsonResponse|\Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public static function redirectWithMessage($message, $messageType = self::SUCCESS, $to = null)
    {
        $data = [
            'message' => $message,
            'message_type' => $messageType
        ];

        return static::redirect($to, $data);
    }

    /**
     * @param string|array|\Exception $message
     * @param string $to
     * @return JsonResponse|\Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public static function redirectSuccess($message, $to = null)
    {
        return static::redirectWithMessage($message, self::SUCCESS, $to);
    }

    /**
     * @param string|array|\Exception $message
     * @param string $to
     * @return JsonResponse|\Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public static function redirectError($message, $to = null)
    {
        return static::redirectWithMessage($message, self::ERROR, $to);
    }

    public static function redirectWithMessageAndData($message, $data, $messageType = self::SUCCESS, $to = null)
    {
        return static::redirect($to, [
            'data' => $data,
            'message' => $message,
            'message_type' => $messageType
        ]);
    }

    /**
     * @param $message
     * @param bool $isError
     * @param null $input
     * @return \Illuminate\Http\RedirectResponse
     */
    public static function backWithMessage($message, $isError = true, $input = null)
    {
        \Flash::message($message, $isError ? self::ERROR : self::INFO);

        if (false === $input) {
            return back();
        }

        return back()->withInput($input);
    }
}