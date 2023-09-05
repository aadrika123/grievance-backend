<?php

namespace App\Models\Workflow;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class WfRoleusermap extends Model
{
    use HasFactory;
    protected $connection = 'pgsql_master';

    /**
     * | Get role by User and Workflow Id
     */
    public function getRoleByUserWfId($req)
    {
        return DB::connection('pgsql_master')
            ->table('wf_roleusermaps as r')
            ->select(
                'r.wf_role_id',
                'w.forward_role_id',
                'w.backward_role_id'
            )
            ->join('wf_workflowrolemaps as w', 'w.wf_role_id', '=', 'r.wf_role_id')
            ->where('r.user_id', $req->userId)
            ->where('w.workflow_id', $req->workflowId)
            ->where('w.is_suspended', false)
            ->first();
    }
}
