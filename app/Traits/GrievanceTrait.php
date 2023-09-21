<?php

namespace App\Traits;

use App\Models\Grievance\GrievanceActiveApplicantion;
use Carbon\Carbon;
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
        | Caution check the ulb and the ward related details 
     */
    public function getActiveApplicatioList($workflowIds, $ulbId, $dataBase)
    {
        return  DB::table($dataBase)
            ->select($dataBase . '.*', 'uwm.ward_name', 'um.ulb_name')
            ->leftJoin('ulb_ward_masters AS uwm', 'uwm.id', '=', $dataBase . '.ward_id')
            ->join('ulb_masters AS um', 'um.id', '=', $dataBase . '.ulb_id')
            ->where($dataBase . '.status', 1)
            // ->where($dataBase . '.ulb_id', $ulbId)
            ->whereIn($dataBase . '.workflow_id', $workflowIds)
            ->where($dataBase . '.in_inner_workflow', false);
    }

    /**
     * | Save the data to the inner workflow solved table  
     * | As the table can be dynamic so the table name and the data are provided in request
     */
    public function saveAssoApplicationSolveData($refApplication, $database, $refMetaReq)
    {
        $now = Carbon::now();
        DB::table($database)->insert([
            "mobile_no"             => $refApplication->mobile_no,
            "email"                 => $refApplication->email,
            "applicant_name"        => $refApplication->applicant_name,
            "uid"                   => $refApplication->uid,
            "created_at"            => $now,
            "updated_at"            => $now,
            "description"           => $refApplication->description,
            "grievance_head"        => $refApplication->grievance_head,
            "department"            => $refApplication->department,
            "gender"                => $refApplication->gender,
            "disability"            => $refApplication->disability,
            "address"               => $refApplication->address,
            "district_id"           => $refApplication->district_id,
            "ulb_id"                => $refApplication->ulb_id,
            "ward_id"               => $refApplication->ward_id,
            "application_no"        => $refApplication->application_no,
            "current_role"          => $refApplication->current_role,
            "initiator_id"          => $refApplication->initiator_id,
            "finisher_id"           => $refApplication->finisher_id,
            "workflow_id"           => $refApplication->workflow_id,
            "doc_upload_status"     => $refApplication->doc_upload_status,
            "is_doc"                => $refApplication->is_doc,
            "apply_date"            => $refApplication->apply_date,
            "other_info"            => $refApplication->other_info,
            "user_id"               => $refApplication->user_id,
            "user_type"             => $refApplication->user_type,
            "user_apply_through"    => $refApplication->user_apply_through,
            "agency_approved_by"    => $refApplication->agency_approved_by,
            "agency_approve_date"   => $refApplication->agency_approve_date,
            "wf_send_by"            => $refApplication->wf_send_by,
            "wf_send_by_role"       => $refApplication->wf_send_by_role,
            "wf_send_by_date"       => $refApplication->wf_send_by_date,
            "reopen_count"          => $refApplication->reopen_count,
            "parent_wf_id"          => $refApplication->workflow_id,
            "approve_reject_date"   => $now,
            "status"                => $refMetaReq['status'],
        ]);
        return DB::getPdo()->lastInsertId();
    }
}
