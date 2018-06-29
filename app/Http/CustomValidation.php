<?php

namespace App\Http\Validations;
 
interface CustomValidation
{
    public function name();
    public function test();
    public function errorMessage();
}
