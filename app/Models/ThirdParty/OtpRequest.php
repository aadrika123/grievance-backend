<?php

namespace App\Models\ThirdParty;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OtpRequest extends Model
{
    use HasFactory;


    /**
     * | Save the Otp for Checking Validatin
     * | @param 
     */
    public function saveOtp($mobileNo, $generateOtp)
    {
        $refData = OtpRequest::where('mobile_no', $mobileNo)
            ->first();
        if ($refData) {
            $refData->otp_time  = Carbon::now();
            $refData->otp       = $generateOtp;
            $refData->hit_count = $refData->hit_count + 1;
            $refData->update();
        } else {
            $mOtpMaster = new OtpRequest();
            $mOtpMaster->mobile_no  = $mobileNo;
            $mOtpMaster->otp        = $generateOtp;
            $mOtpMaster->otp_time   = Carbon::now();
            $mOtpMaster->hit_count  = 1;
            $mOtpMaster->save();
        }
    }

    /**
     * | Get otp by mobile no and otp
     */
    public function getOtpDetails($mobileNo, $otp)
    {
        return OtpRequest::where('mobile_no', $mobileNo)
            ->where('otp', $otp)
            ->first();
    }
}
