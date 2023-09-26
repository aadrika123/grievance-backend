<?php

namespace App\Http\Requests\Grievance;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class closeGrievanceReq extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules()
    {
        $rules["question"]      = 'required|';
        $rules["moduleId"]      = 'required|int';
        $rules["userId"]        = 'nullable|integer';
        $rules["status"]        = 'required|in:yes,no';                                // 0:pass/false,1:close/true   
        $rules["applyDate"]     = 'nullable';
        $rules["remarks"]       = 'nullable';
        $rukes["setPriority"]   = 'nullable|in:1,2,3,4,5';
        $rules["mobileNo"]      = 'nullable|numeric|digits:10';
        $rules["initiator"]     = 'nullable|int';
        return $rules;
    }
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            response()->json(
                [
                    'status'   => false,
                    'message'  => 'The given data was invalid',
                    'errors'   => $validator->errors()
                ],
                422
            )
        );
    }
}
