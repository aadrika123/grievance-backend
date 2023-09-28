<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ActiveCitizen extends Model
{
    use HasFactory;
    protected $connection = 'pgsql_master';

    /**
     * | Get the citizen details by mobile no 
     */
    public function getCitizenDetails($mobileNo)
    {
        return ActiveCitizen::where('mobile', $mobileNo);
    }
}
