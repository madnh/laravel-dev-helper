<?php
namespace MaDnh\LaravelDevHelper\Util;


class ProcessResult
{
    protected $fields = array();
    protected $data_as_array = false;
    protected $data = array();
    protected $meta = array();
    protected $errors = array();
    protected $error_code = 0;
    protected $error_message = '';

    public function __construct()
    {
        $default_meta = config('process_result.meta');
        $default_field = config('process_result.fields');

        if ($default_meta) {
            $this->meta = array_merge($default_meta, $this->meta);
        }
        if ($default_field) {
            $this->fields = array_merge($default_meta, $this->fields);
        }
    }

    /**
     * Get error from arguments
     * @param string|\Exception $message
     * @param int|string $code
     * @param array $detail
     * @return array
     */
    protected function _getError($message, $code = 0, $detail = [])
    {
        if ($message instanceof \Exception) {
            if (!$code) {
                $code = $message->getCode();
            }
            $message = $message->getMessage();
        }
        if (!$message) {
            $message = 'Unknown error';
        }
        if (!(is_numeric($code) || is_string($code))) {
            $code = 0;
        }

        return array(
            'code' => $code,
            'message' => $message,
            'detail' => $detail
        );
    }

    /**
     * Convert object to array
     * @param $object
     * @return array
     */
    public function _objectToArray($object)
    {
        $result = array();
        foreach ($object as $k => $v) {
            $result[$k] = $v;
        }
        return $result;
    }

    /**
     * @param $message
     * @param int|string $code
     * @param array $detail
     */
    public function addError($message, $code = 0, $detail = [])
    {
        $error = $this->_getError($message, $code, $detail);

        if (!$this->error_message) {
            $this->error_message = $error['message'];
            $this->error_code = $error['code'];
        }
        $this->errors[] = $error;
    }

    public function error($message, $code = 0)
    {
        $error = $this->_getError($message, $code);
        $this->error_message = $error['message'];
        $this->error_code = $error['code'];
    }

    /**
     * Set data as array. Default data is object
     * @param null|boolean $as_array
     * @return bool
     */
    public function dataAsArray($as_array = null)
    {
        if (null === $as_array) {
            return (bool)$this->data_as_array;
        } else {
            $this->data_as_array = (bool)$as_array;
        }
        return $this->data_as_array;
    }

    /**
     * Add data
     * @param string|array $name
     * @param null|mixed $value
     * @throws \Exception
     */
    public function addData($name, $value = null)
    {
        $arg_count = func_num_args();
        if ($this->data_as_array) {
            if (1 === $arg_count) {
                if (is_object($name)) {
                    $name = $this->_objectToArray($name);
                }
                $this->data[] = $name;
            } else {
                $this->data[] = array(
                    $name . '' => $value
                );
            }
        } else {
            if (1 < $arg_count) {
                if (is_object($value)) {
                    $value = $this->_objectToArray($value);
                }
                if (is_object($name)) {
                    $name = $this->_objectToArray($name);
                }
                if (is_array($name)) {
                    foreach ($name as $tmp_name) {
                        $this->data[$tmp_name . ''] = $value;
                    }
                } else {
                    $this->data[$name . ''] = $value;
                }
            } else {
                throw new \Exception('Invalid function arguments');
            }
        }
    }

    /**
     * Add meta
     * @param string|array $name Name or array of name of meta
     * @param $value
     */
    public function addMeta($name, $value)
    {
        if (is_array($name)) {
            foreach ($name as $tmp_name) {
                $this->meta[$tmp_name] = $value;
            }
        } else {
            $this->meta[$name] = $value;
        }
    }

    public function addField($name, $value)
    {
        if (is_array($name)) {
            foreach ($name as $tmp_name) {
                $this->fields[$tmp_name] = $value;
            }
        } else {
            $this->fields[$name] = $value;
        }
    }

    /**
     * Export to array
     * @return array
     */
    public function export()
    {
        $result = array_merge($this->fields);
        unset($result['meta'], $result['error'], $result['data']);

        if ($this->meta) {
            $result['meta'] = $this->meta;
        }
        if ($this->error_message || !empty($this->errors)) {
            $result['error'] = array(
                'code' => $this->error_code,
                'message' => $this->error_message
            );
            if (!empty($this->errors)) {
                $result['error']['errors'] = $this->errors;
            }
        } else {
            $result['data'] = $this->data;
        }

        return $result;
    }

}