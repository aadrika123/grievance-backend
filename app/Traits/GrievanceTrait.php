<?php

namespace App\Traits;

use App\Models\Grievance\GrievanceActiveApplicantion;
use Illuminate\Support\Facades\DB;

/**
 *| Used for Grievance common function
 *| Created On- 24-07-2023
 *| Created By- Sam kerketta
 *------------------------------------------------------------------------------------------
 */
trait GrievanceTrait
{
    /**
     * | Get list of applications in workflow
     */
    public function getActiveApplicatioList($workflowIds, $ulbId, $dataBase)
    {
        return  DB::table($dataBase)
            ->select($dataBase . '.*', 'uwm.ward_name', 'um.ulb_name')
            ->join('ulb_ward_masters AS uwm', 'uwm.id', '=', $dataBase . '.ward_id')
            ->join('ulb_masters AS um', 'um.id', '=', $dataBase . '.ulb_id')
            ->where($dataBase . '.status', 1)
            ->where($dataBase . '.ulb_id', $ulbId)
            ->whereIn($dataBase . '.workflow_id', $workflowIds)
            ->where($dataBase . '.in_inner_workflow', false);
    }
}
