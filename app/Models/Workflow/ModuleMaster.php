<?php

namespace App\Models\Workflow;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ModuleMaster extends Model
{
    use HasFactory;
    protected $connection = 'pgsql_master';

    /**
     * | Get active module list
     */
    public function getModuleList()
    {
        return ModuleMaster::where('is_suspended', false)
            ->orderByDesc('id');
    }
}
