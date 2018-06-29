<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Facades\Log;

class TokenRule implements Rule
{
    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    // const PHONEREG = '/^(13[0-9]|14[579]|15[0-3,5-9]|17[0135678]|18[0-9])\\d{8}$/';  
    private $key = 'e948afae5761018e7af958e0a8bd675a';//token前面key
    private $timeliness = '136010';//token时效性,单位秒

    /**
     * Determine if the validation rule passes.
     * 规则: md5(key.time())
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        // echo '12a3';exit;
        $data = request()->all();
        $header = request()->header();
          
        echo "<pre>";  
            print_r($data);  
        echo "</pre>";  
        exit;  
        // echo $token = md5($this->key.request('time'));exit;
        Log::debug(request()->all());
        //时效性验证
        if(time()-request('time')>$this->timeliness){
            return false;
        }
        $token = md5($this->key.request('time'));
        // echo $token;exit;
        return request('token')==$token;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'token validation fail.';
    }
}
