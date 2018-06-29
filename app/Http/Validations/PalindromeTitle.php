<?php

namespace App\Http\Validations;
 
use CustomValidation;
 
class PalindromeTitle implements CustomValidation
{
    public function name()
    {
        return 'is_palindrome';
    }
 
    public function test()
    {
        return function ($_, $value, $_) {
            return $value === strrev($value);
        }
    }
 
    public function errorMessage()
    {
        return 'Book标题格式不正确...';
    }
}