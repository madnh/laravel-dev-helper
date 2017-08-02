<?php

namespace MaDnh\LaravelDevHelper\FormRequest;

use Illuminate\Foundation\Http\FormRequest;
use MaDnh\LaravelDevHelper\FormRequest\Exceptions\InvalidEmbedFormRequestInfo;
use MaDnh\LaravelDevHelper\Util\Errors;
use MaDnh\LaravelDevHelper\Util\ProcessResult;

class BaseFormRequest extends FormRequest
{
    /**
     * Embed name in others
     * @var string
     */
    public $embed_name = '';

    /**
     * Embed others form request
     * @var array
     */
    public $embed = [];

    /**
     * @var array
     */
    private $embed_parts_cache = [];

    /**
     * Get input value, support when this request is in embed of other request
     * @param string $name Input name
     * @param mixed $default Default value
     * @return mixed
     */
    public function getInput($name, $default = null)
    {
        $prefix = $this->embed_name ? $this->embed_name . '.' : '';
        return $this->input($prefix . $name, $default);
    }

    /**
     * Get input name with embed name
     * @param string $name
     * @return string
     */
    public function getInputName($name)
    {
        return ($this->embed_name ? $this->embed_name . '.' : '') . $name;
    }

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $baseRules = $this->baseRules();
        $embedRules = $this->getPartsOfEmbedRequests()['rules'];
        $additionRules = $this->additionRules();
        $exceptRules = (array)$this->exceptRules();

        return array_except(array_merge($baseRules, $embedRules, $additionRules), $exceptRules);
    }

    /**
     * @return array
     */
    protected function baseRules()
    {
        return [
            //
        ];
    }

    /**
     * @return array
     */
    protected function additionRules()
    {
        return [
            //
        ];
    }

    /**
     * @return array
     */
    protected function exceptRules()
    {
        return [
            //
        ];
    }

    public function attributes()
    {
        $baseAttributes = $this->baseAttributes();
        $embedAttributes = $this->getPartsOfEmbedRequests()['attributes'];
        $additionAttributes = $this->additionAttributes();
        $exceptAttributes = (array)$this->exceptAttributes();

        return array_except(array_merge($baseAttributes, $embedAttributes, $additionAttributes), $exceptAttributes);
    }

    /**
     * @return array
     */
    protected function baseAttributes()
    {
        return [
            //
        ];
    }

    protected function additionAttributes()
    {
        return [
            //
        ];
    }

    protected function exceptAttributes()
    {
        return [
            //
        ];
    }

    public function messages()
    {
        $baseMessages = $this->baseMessages();
        $embedMessages = $this->getPartsOfEmbedRequests()['messages'];
        $additionMessages = $this->additionMessages();
        $exceptMessages = $this->exceptMessages();

        return array_except(array_merge($baseMessages, $embedMessages, $additionMessages), $exceptMessages);
    }

    protected function baseMessages()
    {
        return [
            //
        ];
    }

    protected function additionMessages()
    {
        return [
            //
        ];
    }

    protected function exceptMessages()
    {
        return [
            //
        ];
    }

    /**
     * Get the proper failed validation response for the request.
     *
     * @param  array $errors
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function response(array $errors)
    {
        if ($this->ajax() || $this->wantsJson()) {
            return $this->responseInvalidRequest($errors);
        }

        return $this->redirector->to($this->getRedirectUrl())
            ->withInput($this->except($this->dontFlash))
            ->withErrors($errors, $this->errorBag);
    }

    /**
     * @return array
     * @throws InvalidEmbedFormRequestInfo
     */
    protected function getPartsOfEmbedRequests()
    {
        if (empty($this->embed_parts_cache)) {
            $additionValidateRequest = AdditionValidateRequests::with($this->embed);
            $this->embed_parts_cache = $additionValidateRequest->getParts();
        }

        return $this->embed_parts_cache;
    }

    /*|--------------------------------------------------------------------------
      | responseInvalidRequest
      |--------------------------------------------------------------------------
      | Return error in JSON with structure like this
      |{
      | "meta": {
      |   "env": "local",
      |   "build": "0.0.4"
      | },
      | "error": {
      |   "code": "REQUEST_VALIDATION_FAILED",
      |   "message": "The request is invalid, validation rules has failed",
      |   "errors": [
      |     {
      |       "code": "REQUEST_VALIDATE_FIELD_FAILED",
      |       "message": "Field [address] is invalid",
      |       "detail": {
      |         "field": "address",
      |         "errors": [
      |           "The address may not be greater than 10 characters."
      |         ]
      |       }
      |     }
      |   ]
      | }
      |}
    |
    |
    */
    /**
     * @param array $errors
     * @param null|ProcessResult $pr
     * @return \Illuminate\Http\JsonResponse
     */
    protected function responseInvalidRequest($errors, $pr = null)
    {
        if (!$pr) {
            $pr = new ProcessResult();
        }
        $pr->error('Validate request failed', 'REQUEST_VALIDATION_FAILED');

        foreach ($errors as $field => $field_error) {
            $pr->addError('Field ' . $field . ' is invalid', 'REQUEST_VALIDATE_FIELD_FAILED', [
                'field' => $field,
                'errors' => $field_error
            ]);
        }

        return response()->json($pr->export(), 422);
    }
}