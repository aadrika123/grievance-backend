<?php

namespace App\Models\Property;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PropActiveSaf extends Model
{
    use HasFactory;
    protected $connection = 'pgsql_property';

    /**
     * | Get property details according to user Id
     */
    public function getPropByUserId($userId)
    {
        // on('pgsql::read')
        return PropActiveSaf::where('prop_active_safs.user_id', $userId)
            ->where('status', 1);
    }
}
