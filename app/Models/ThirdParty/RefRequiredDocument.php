<?php

namespace App\Models\ThirdParty;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RefRequiredDocument extends Model
{
    use HasFactory;

    /**
     * | Get  All Document Collictively For Array Of DocCode
     */
    public function getCollectiveDocByCode($moduldId, $docCodes)
    {
        return RefRequiredDocument::select(
            'requirements',
            'code'
        )
            ->where('module_id', $moduldId)
            ->whereIn('code', $docCodes)
            ->get();
    }
}
