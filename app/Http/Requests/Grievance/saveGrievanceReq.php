<?php

namespace App\Http\Requests\Grievance;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class saveGrievanceReq extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize()
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
        $rules["mobileNo"]      = 'required|numeric|digits:10';
        $rules["applicantName"] = 'required';
        $rules["document"]      = 'nullable|mimes:jpeg,png,jpg,gif|max:20048';
        $rules["description"]   = 'required';
        $rules["grievanceHead"] = 'required|integer';
        $rules["department"]    = 'required|integer';
        $rules["email"]         = 'required|email';
        $rules["aadhar"]        = 'nullable|integer|digits:12';
        $rules["gender"]        = 'nullable|in:male,female';
        $rules["disability"]    = 'nullable|in:true,false';
        $rules["address"]       = 'required';
        $rules["districtId"]    = 'nullable|integer';
        $rules["ulbId"]         = 'nullable|integer';
        $rules["wardId"]        = 'nullable|integer';
        $rules["otherInfo"]     = 'nullable';
        if (isset($this->document) && $this->document) {
            $rules["docCode"]       = "required";
            $rules["docCategory"]   = "required";
        }
        return $rules;
    }

    // Validation Error Message
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
