<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Auth\AuthenticationException;

class BingdingValidatorRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    // protected function failedAuthorization()
    // {
    //     throw new AuthenticationException('该帐号已被拉黑');
    // }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(){

        // echo "<pre>";  
        //     print_r(request()->header());  
        // echo "</pre>";  
        // exit;  
        //验证支付表单
        if(request('type')=='alipay'){
            return [
                'account'=>[
                    'required',
                    'unique:credit',
                ],
                'time'=>'required',
                'name'=>'required',
                'uid'=>'required',
                'token'=>['required',new \App\Rules\TokenRule],
            ];
        }elseif(request('type')=='bank'){
        //验证银行表单
            return [
                'account'=>[
                    'required',
                    'unique:credit',
                ],
                'name'=>'required',
                'idCard'=>'required',
                'originBank'=>'required',
                'originProvince'=>'required',
                'originCity'=>'required',
                'branchBank'=>'required',
                'uid'=>'required',
                'token'=>['required',new \App\Rules\TokenRule],
            ];
        }
        return [
            'account'=>[
                'required',
                'unique:credit',
            ],
            'name'=>'required',
            'type'=>'required|in:alipay,bank',
            'uid'=>'required',
            'token'=>['required',new \App\Rules\TokenRule],
        ];
    }

    public function messages(){
        //验证支付表单
        if(request('type')=='alipay'){
            $message['account.required'] = '支付宝帐号不能为空.';
            $message['name.required'] = '姓名不能为空.';
        }elseif(request('type')=='bank'){
        //验证银行表单
            $message['account.required'] = '银行卡卡号不能为空.';
            $message['name.required'] = '持卡人姓名不能为空.';
        }
        $message['account.unique'] = request('account').' 账号已存在.';
        $message['idCard.required'] = '身份证不能为空.';
        $message['originBank.required'] = '开户行不能为空.';
        $message['originProvince.required'] = '开户省份不能为空.';
        $message['originCity.required'] = '开户城市不能为空.';
        $message['branchBank.required'] = '开户支行不能为空.';
        $message['type.required'] = 'type不能为空.';
        $message['type.in'] = 'type不在范围内.';
        $message['uid.required'] = 'uid必填.';
        return $message;
    }


}
