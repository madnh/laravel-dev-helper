<?php


namespace MaDnh\LaravelDevHelper\FormRequest;

use Illuminate\Foundation\Http\FormRequest;
use MaDnh\LaravelDevHelper\FormRequest\Exceptions\CustomValidateRequestNotFound;
use MaDnh\LaravelDevHelper\FormRequest\Exceptions\EmbedFormRequestNotFound;
use MaDnh\LaravelDevHelper\FormRequest\Exceptions\GetNonExistsAdditionValidateRequestPart;
use MaDnh\LaravelDevHelper\FormRequest\Exceptions\InvalidCustomValidateRequestOptions;
use MaDnh\LaravelDevHelper\FormRequest\Exceptions\InvalidEmbedFormRequestInfo;


class AdditionValidateRequests
{
    /**
     * Arrays of validate request classes
     * @var array
     */
    public $requests = [];
    /**
     * Arrays of custom validate request parts, each item containing:
     * - rules: request rules
     * - attributes: request attributes
     * - messages: request messages
     *
     * @var array
     */
    public $customs = [];

    public $only = true;
    public $except = [];

    /**
     * @var array
     */
    protected $request_parts_cache = [];

    /**
     * Add an form validate request
     *
     * @param string $class
     * @param string|array $detail String is value of prefix
     * - prefix: string which will prepend to rules, attributes and messages of class. Default is empty string
     * - only: Array of name of rules, attributes and messages which will take from class. True is all of them. Default is true
     * - except: Array of name of rules, attributes and messages which will except from class. Default is empty array
     *
     * @return $this
     * @throws EmbedFormRequestNotFound
     * @throws InvalidEmbedFormRequestInfo
     * @throws \Exception
     */
    public function addRequest($class, $detail = [])
    {
        if (!is_string($class)) {
            throw new InvalidEmbedFormRequestInfo('Try to add addition validate request with an invalid class name. It must be string');
        }
        if (!class_exists($class)) {
            throw new EmbedFormRequestNotFound('Embed form request class not found: ' . $class);
        }
        if (is_string($detail)) {
            $detail = ['prefix' => $detail];
        }
        if (is_array($detail)) {
            if (0 === count(array_intersect(array_keys($detail), ['prefix', 'only', 'except']))) {
                $detail = ['only' => $detail];
            }
        } else {
            throw new \Exception('Addition validate requests detail must be string or array');
        }

        $detail = array_merge([
            'prefix' => '',
            'only' => true,
            'except' => [],
            'instance' => null
        ], (array)$detail);

        /**
         * @var FormRequest|BaseFormRequest
         */
        $instance = new $class();
        $detail['instance'] = $instance;

        if ($instance instanceof BaseFormRequest) {
            $detail['instance']->embed_name = $detail['prefix'];
        }

        $detail['only'] = true !== $detail['only'] ? (array)$detail['only'] : true;
        $detail['except'] = !empty($detail['except']) ? (array)$detail['except'] : [];

        $this->requests[$class] = $detail;
        $this->resetCache();

        return $this;
    }

    /**
     * @param string|null $name
     * @param array $rules
     * @param array $attributes
     * @param array $messages
     * @return $this
     */
    public function addCustomWithName($name, $rules, $attributes = [], $messages = [])
    {
        $this->resetCache();

        $detail = [
            'prefix' => '',
            'rules' => $rules,
            'attributes' => $attributes,
            'messages' => $messages,
        ];
        if (empty($name)) {
            $this->customs[] = $detail;
        } else {
            $this->customs[$name] = $detail;
        }

        return $this;
    }

    /**
     * @param array $rules
     * @param array $attributes
     * @param array $messages
     * @return AdditionValidateRequests
     */
    public function addCustom($rules, $attributes = [], $messages = [])
    {
        return $this->addCustomWithName(null, $rules, $attributes, $messages);
    }

    /**
     * @param string $customName
     * @param string|array $options Array is details, String is value of prefix
     * @return $this
     * @throws CustomValidateRequestNotFound
     * @throws InvalidCustomValidateRequestOptions
     */
    public function updateCustom($customName, $options)
    {
        if (!array_key_exists($customName, $this->customs)) {
            throw new CustomValidateRequestNotFound('Update non exists custom validate request: ' . $customName);
        }
        if (is_string($options)) {
            $options = ['prefix' => $options];
        }
        if (!is_array($options)) {
            throw new InvalidCustomValidateRequestOptions('Custom validate request options must be array, ' . gettype($options) . ' given');
        }

        $this->customs[$customName] = array_merge($this->customs[$customName], $options);
        $this->resetCache();

        return $this;
    }

    /**
     * @param string $customName
     * @param true|string|array $only
     * @return $this
     */
    public function customOnly($customName, $only)
    {
        if (true !== $only) {
            $only = (array)$only;
        }

        return $this->updateCustom($customName, ['only' => $only]);
    }

    /**
     * @param string $customName
     * @param string|array $except
     * @return $this
     */
    public function customExcept($customName, $except)
    {
        return $this->updateCustom($customName, ['except' => (array)$except]);
    }

    /**
     * @param string $classOrCustomName Request class or custom name
     */
    public function remove($classOrCustomName)
    {
        unset($this->requests[$classOrCustomName]);
        unset($this->customs[$classOrCustomName]);
    }

    /**
     * @return $this
     */
    public function resetCache()
    {
        $this->request_parts_cache = [];
        return $this;
    }

    /**
     * Parts:
     * - rules
     * - attributes
     * - messages
     *
     * @param string $part Part name. Empty is return all of parts
     * @return array
     * @throws GetNonExistsAdditionValidateRequestPart
     */
    public function getParts($part = null)
    {
        if (empty($this->request_parts_cache)) {
            $result = [
                'rules' => [],
                'attributes' => [],
                'messages' => []
            ];
            $details = array_merge($this->requests, $this->customs);

            foreach ($details as $detail) {
                $requestParts = $this->getPartsFromDetail($detail);

                $result['rules'] = array_merge($result['rules'], $requestParts['rules']);
                $result['attributes'] = array_merge($result['attributes'], $requestParts['attributes']);
                $result['messages'] = array_merge($result['messages'], $requestParts['messages']);
            }

            $this->request_parts_cache = $result;
        }

        if (!empty($part)) {
            if (array_key_exists($part, $this->request_parts_cache)) {
                return $this->request_parts_cache[$part];
            }

            throw new GetNonExistsAdditionValidateRequestPart('Get non exists addition validate part: ' . $part);
        }

        return $this->request_parts_cache;
    }

    /**
     * @return array
     */
    public function rules()
    {
        return $this->getParts('rules');
    }

    /**
     * @return array
     */
    public function attributes()
    {
        return $this->getParts('attributes');
    }

    /**
     * @return array
     */
    public function messages()
    {
        return $this->getParts('messages');
    }

    /**
     * Get parts of an addition validate or custom request
     * Result is an array of:
     * - rules: rules of addition validate request
     * - attributes: attributes of addition validate request
     * - messages: messages of addition validate request
     *
     * @param array $detail
     * @return array
     */
    protected function getPartsFromDetail($detail)
    {
        if (array_key_exists('instance', $detail)) {
            $instance = $detail['instance'];

            if (property_exists($instance, 'embed_name')) {
                $instance->embed_name = $detail['prefix'];
            }

            $rules = $instance->rules();
            $attributes = $instance->attributes();
            $messages = $instance->messages();
        } else {
            $rules = $detail['rules'];
            $attributes = $detail['attributes'];
            $messages = $detail['messages'];
        }

        if (array_key_exists('only', $detail) && is_array($detail['only']) && !empty($detail['only'])) {
            $rules = array_only($rules, $detail['only']);
            $attributes = array_only($attributes, $detail['only']);
            $messages = array_only($messages, $detail['only']);
        }
        if (array_key_exists('except', $detail) && !empty($detail['except'])) {
            $detail['except'] = (array)$detail['except'];

            $rules = array_except($rules, $detail['except']);
            $attributes = array_except($attributes, $detail['except']);
            $messages = array_except($messages, $detail['except']);
        }

        if (array_key_exists('prefix', $detail) && !empty($detail['prefix'])) {
            if (!empty($rules)) {
                $rules = array_dot([
                    $detail['prefix'] => $rules
                ]);
            }
            if (!empty($attributes)) {
                $attributes = array_dot([
                    $detail['prefix'] => $attributes
                ]);
            }
            if (!empty($messages)) {
                $messages = array_dot([
                    $detail['prefix'] => $messages
                ]);
            }
        }

        return [
            'rules' => $rules,
            'attributes' => $attributes,
            'messages' => $messages
        ];
    }

    /**
     * Get validator from addition of requests and customs
     *
     * @param null|array $data Data to validate, if empty then use request data
     * @return \Illuminate\Validation\Validator
     */
    public function validator($data = null)
    {
        if (null === $data) {
            $data = request()->all();
        }

        $rules = $this->rules();
        $messages = $this->messages();
        $customAttributes = $this->attributes();

        return \Validator::make($data, $rules, $messages, $customAttributes);
    }

    /**
     * Check if request is validate
     *
     * @param null|array $data Data to validate, if empty then use request data
     * @return true|\Illuminate\Validation\Validator Return true when request is valid, else, return the validator instance
     */
    public function isValid($data = null)
    {
        $validator = $this->validator($data);

        if (!$validator->fails()) {
            return true;
        }

        return $validator;
    }

    /**
     * @param string|array $classes
     * String is request class or array in formats of:
     * - request class, ex: Foo\Bar\BazFormRequest
     * - request class => detail, ex: Foo\Bar\BazFormRequest => ['only' => ['account', 'password']
     * - detail as array, ex: ['rules' => [...], 'attributes' => [...]]
     * - custom name => detail as array, ex: check_old_password => ['rules' => ['old_password' => 'required|string']]
     * @return static
     * @throws InvalidEmbedFormRequestInfo
     */
    public static function with($classes)
    {
        $instance = new static();

        foreach ((array)$classes as $class => $detail) {
            if (!is_string($class)) {
                //Class without detail
                if (is_string($detail)) {
                    $class = $detail;
                    $detail = [];
                } else if (is_array($detail)) {
                    //Custom by detail only
                    self::addCustomDetail($instance, null, $detail);
                    continue;
                } else {
                    throw new InvalidEmbedFormRequestInfo('Try to add addition validate request with an invalid info. Class must be string, detail must be array');
                }
            }
            if (is_string($detail)) {
                $detail = ['prefix' => $detail];
            }
            if (!class_exists($class)) {
                if (is_array($detail)) {
                    //Custom with name and detail
                    self::addCustomDetail($instance, $class, $detail);
                    continue;
                } else {
                    throw new InvalidEmbedFormRequestInfo('Try to add addition validate request with an invalid custom name and detail: ' . $class);
                }
            }

            //Class and detail
            $instance->addRequest($class, $detail);
        }

        return $instance;
    }

    /**
     * @param string|array $classes See 'with' method
     * @param array $data
     * @return \Illuminate\Validation\Validator|true
     */
    public function isValidWith($classes, $data = null)
    {
        $instance = self::with($classes);

        return $instance->isValid($data);
    }

    /**
     * @param $classes
     * @return \Illuminate\Validation\Validator
     */
    public static function validatorWith($classes)
    {
        $instance = self::with($classes);

        return $instance->validator();
    }

    /**
     * @param self $instance
     * @param string|null $name
     * @param array $detail
     */
    protected static function addCustomDetail($instance, $name, $detail)
    {
        $detail = array_merge([
            'prefix' => '',
            'rules' => [],
            'attributes' => [],
            'messages' => []
        ], (array)$detail);

        if (empty($name)) {
            $instance->addCustom($detail['rules'], $detail['attributes'], $detail['messages']);
        } else {
            $instance->addCustomWithName($name, $detail['rules'], $detail['attributes'], $detail['messages']);
        }
    }
}