<?php

namespace Ariby\LaravelImageUpload\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Validator;

abstract class BaseRequest extends FormRequest
{
    public function __construct(array $query = array(), array $request = array(), array $attributes = array(), array $cookies = array(), array $files = array(), array $server = array(), $content = null)
    {
        parent::__construct($query, $request, $attributes, $cookies, $files, $server, $content);

        // 僅允許字母數字和 _
        Validator::extend('strict_alpha_num_underline', function ($attribute, $value) {
            return (is_string($value) && preg_match('/^([a-zA-Z0-9_])+$/i', $value));
        });

    }
}
