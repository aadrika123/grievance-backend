<?php

namespace App\Models\Grievance;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class GrievanceActiveApplicantion extends Model
{
    use HasFactory;
    protected $guarded = [];

    /**
     * | Save the grievance request  
     */
    public function saveGrievanceDetails($req, $refRequest)
    {
        $mGrievanceActiveApplicantion = new GrievanceActiveApplicantion();
        $mGrievanceActiveApplicantion->mobile_no            = $req->mobileNo;
        $mGrievanceActiveApplicantion->email                = $req->email;
        $mGrievanceActiveApplicantion->applicant_name       = $req->applicantName;
        $mGrievanceActiveApplicantion->uid                  = $req->aadhar;
        $mGrievanceActiveApplicantion->description          = $req->description;
        $mGrievanceActiveApplicantion->grievance_head       = $req->grievanceHead;
        $mGrievanceActiveApplicantion->department           = $req->department;
        $mGrievanceActiveApplicantion->gender               = $req->gender;
        $mGrievanceActiveApplicantion->disability           = $req->disability;
        $mGrievanceActiveApplicantion->address              = $req->address;
        $mGrievanceActiveApplicantion->district_id          = $req->districtId;
        $mGrievanceActiveApplicantion->ulb_id               = $req->ulbId;
        $mGrievanceActiveApplicantion->ward_id              = $req->wardId;
        $mGrievanceActiveApplicantion->other_info           = $req->otherInfo;
        $mGrievanceActiveApplicantion->user_apply_through   = $req->applyThrough ?? $refRequest['applyThrough'];
        $mGrievanceActiveApplicantion->application_no       = $refRequest['applicationNo'];
        $mGrievanceActiveApplicantion->initiator_id         = $refRequest['initiatorRoleId'];
        $mGrievanceActiveApplicantion->finisher_id          = $refRequest['finisherRoleId'];
        $mGrievanceActiveApplicantion->workflow_id          = $refRequest['workflowId'];
        $mGrievanceActiveApplicantion->is_doc               = false;                                // Static
        $mGrievanceActiveApplicantion->apply_date           = Carbon::now();                        // Static
        $mGrievanceActiveApplicantion->user_id              = $refRequest['userId'];
        $mGrievanceActiveApplicantion->user_type            = $refRequest['userType'];
        // $mGrievanceActiveApplicantion->current_role     = $refRequest['initiatorRoleId'];
        $mGrievanceActiveApplicantion->save();
        return [
            "id" => $mGrievanceActiveApplicantion->id
        ];
    }

    /**
     * | Get the active aplication list 
     */
    public function getActiveGrievance($applicationNo, $mobileNo)
    {
        return GrievanceActiveApplicantion::where('application_no', $applicationNo)
            ->where('mobile_no', $mobileNo)
            ->orderByDesc('id');
    }

    /**
     * | Get application details by id
     */
    public function getActiveGrievanceById($id)
    {
        return GrievanceActiveApplicantion::where('id', $id)
            ->where('status', 1);
    }

    /**
     * | Save the doc status 
     */
    public function updateDocStatus($applicationId, $status)
    {
        GrievanceActiveApplicantion::where('id', $applicationId)
            ->update([
                'is_doc' => $status
            ]);
    }

    /**
     * | Save the doc verify status
     */
    public function updateAppliVerifyStatus($applicationId, $status)
    {
        GrievanceActiveApplicantion::where('id', $applicationId)
            ->update([
                'doc_verify_status' => $status
            ]);
    }

    /**
     * | Grievance Detial 
     */
    public function getGrievanceFullDetails($applicationId, $database)
    {
        return DB::table($database)
            ->select($database . '.*', 'ulb_masters.ulb_name', 'ulb_ward_masters.ward_name')
            // ->leftJoin('wf_roles', 'wf_roles.id', '=', $database . '.current_role')
            ->join('ulb_ward_masters', 'ulb_ward_masters.id', '=', $database . '.ward_id')
            ->join('ulb_masters', 'ulb_masters.id', '=', $database . '.ulb_id')
            ->where($database . '.id', $applicationId)
            ->where($database . '.status', 1);
    }

    /**
     * | Delete the applications record
        | Caution
     */
    public function deleteRecord($applicationId)
    {
        GrievanceActiveApplicantion::where('id', $applicationId)
            ->delete();
    }

    /**
     * | Update the current role details in active table
     */
    public function updateCurrentRole($applicationId, $roleId)
    {
        GrievanceActiveApplicantion::where('id', $applicationId)
            ->update([
                'current_role' => $roleId
            ]);
    }


    /**
     * | Get list of grievance that are not in workflow
     */
    public function getGriavanceDetails($moduleId)
    {
        return GrievanceActiveApplicantion::select(
            'grievance_active_applicantions.id',
            'grievance_active_applicantions.mobile_no',
            'grievance_active_applicantions.applicant_name',
            'grievance_active_applicantions.application_no',
            'grievance_active_applicantions.apply_date',
            'grievance_active_applicantions.user_apply_through',
            'grievance_active_applicantions.workflow_id',
            'ulb_masters.ulb_name',
            'ulb_ward_masters.ward_name',
            'm_grievance_apply_through.apply_through_name',
            DB::raw("CONCAT('" . config('app.url') . "', '/', wf_active_documents.relative_path, '/', wf_active_documents.document) as full_url")
        )
            ->join('wf_active_documents', 'wf_active_documents.active_id', 'grievance_active_applicantions.id')
            ->join('m_grievance_apply_through', 'm_grievance_apply_through.id', 'grievance_active_applicantions.user_apply_through')
            ->join('ulb_masters', 'ulb_masters.id', '=', 'grievance_active_applicantions.ulb_id')
            ->join('ulb_ward_masters', 'ulb_ward_masters.id', '=', 'grievance_active_applicantions.ward_id')

            ->where('grievance_active_applicantions.status', 1)
            ->where('in_inner_workflow', false)
            ->whereColumn('wf_active_documents.ulb_id', 'grievance_active_applicantions.ulb_id')
            ->where('wf_active_documents.module_id', $moduleId)
            ->whereColumn('wf_active_documents.workflow_id', 'grievance_active_applicantions.workflow_id')
            ->where('wf_active_documents.status', 1)
            ->orderByDesc('grievance_active_applicantions.id');
    }


    /**
     * | Search grievance list for Agency
     */
    public function searchActiveGrievance()
    {
        return GrievanceActiveApplicantion::select(
            'grievance_active_applicantions.id',
            'grievance_active_applicantions.mobile_no',
            'grievance_active_applicantions.applicant_name',
            'grievance_active_applicantions.application_no',
            'grievance_active_applicantions.apply_date',
            'grievance_active_applicantions.user_apply_through',
            'grievance_active_applicantions.inner_workflow_id',
            'grievance_active_applicantions.workflow_id',
            'm_grievance_apply_through.apply_through_name',
            DB::raw("(SELECT wf_masters.id FROM wf_masters 
                JOIN wf_workflows ON wf_masters.id = wf_workflows.wf_master_id
                WHERE wf_workflows.id = grievance_active_applicantions.workflow_id) as workflow_mstr_id"),
            DB::raw("(SELECT wf_masters.id FROM wf_masters 
                JOIN wf_workflows ON wf_masters.id = wf_workflows.wf_master_id
                WHERE wf_workflows.id = grievance_active_applicantions.inner_workflow_id) as inner_workflow_mstr_id")
        )
            ->join('m_grievance_apply_through', 'm_grievance_apply_through.id', 'grievance_active_applicantions.user_apply_through')
            ->where('grievance_active_applicantions.status', 1);
    }
}
