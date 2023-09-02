<?php

namespace App\Models\Grievance;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MGrievanceApplyThrough extends Model
{
    use HasFactory;

    /**
     * | Get all the active data for master
     */
    public function getAllActiveData()
    {
        return MGrievanceApplyThrough::where('status', 1)
            ->orderByDesc("id");
    }
}
