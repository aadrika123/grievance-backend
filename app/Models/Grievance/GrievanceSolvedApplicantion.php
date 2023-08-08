<?php

namespace App\Models\Grievance;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GrievanceSolvedApplicantion extends Model
{
    use HasFactory;

    /**
     * | Get the Solved Application using applicationId
     */
    public function getSolvedApplication($applicationId)
    {
        return GrievanceSolvedApplicantion::where('application_id', $applicationId);
    }

    /**
     * | Save the Approved Grievance Detials
     */
    public function saveGrievanceDetials($activeGrievance, $refDetails)
    {
        $now = Carbon::now();
        $mGrievanceSolvedApplicantion = new GrievanceSolvedApplicantion();
        $mGrievanceSolvedApplicantion->mobile_no            = $activeGrievance->mobile_no;
        $mGrievanceSolvedApplicantion->email                = $activeGrievance->email;
        $mGrievanceSolvedApplicantion->applicant_name       = $activeGrievance->applicant_name;
        $mGrievanceSolvedApplicantion->uid                  = $activeGrievance->uid;
        $mGrievanceSolvedApplicantion->description          = $activeGrievance->description;
        $mGrievanceSolvedApplicantion->grievance_head       = $activeGrievance->grievance_head;
        $mGrievanceSolvedApplicantion->department           = $activeGrievance->department;
        $mGrievanceSolvedApplicantion->gender               = $activeGrievance->gender;
        $mGrievanceSolvedApplicantion->disability           = $activeGrievance->disability;
        $mGrievanceSolvedApplicantion->address              = $activeGrievance->address;
        $mGrievanceSolvedApplicantion->district_id          = $activeGrievance->district_id;
        $mGrievanceSolvedApplicantion->ulb_id               = $activeGrievance->ulb_id;
        $mGrievanceSolvedApplicantion->ward_id              = $activeGrievance->ward_id;
        $mGrievanceSolvedApplicantion->application_no       = $activeGrievance->application_no;
        $mGrievanceSolvedApplicantion->current_role         = $activeGrievance->current_role;
        $mGrievanceSolvedApplicantion->initiator_id         = $activeGrievance->initiator_id;
        $mGrievanceSolvedApplicantion->finisher_id          = $activeGrievance->finisher_id;
        $mGrievanceSolvedApplicantion->last_role_id         = $activeGrievance->last_role_id;
        $mGrievanceSolvedApplicantion->workflow_id          = $activeGrievance->workflow_id;
        $mGrievanceSolvedApplicantion->parked               = $activeGrievance->parked;
        $mGrievanceSolvedApplicantion->is_escalate          = $activeGrievance->is_escalate;
        $mGrievanceSolvedApplicantion->escalate_by          = $activeGrievance->escalate_by;
        $mGrievanceSolvedApplicantion->in_inner_workflow    = $activeGrievance->in_inner_workflow;
        $mGrievanceSolvedApplicantion->doc_upload_status    = $activeGrievance->doc_upload_status;
        $mGrievanceSolvedApplicantion->doc_verify_status    = $activeGrievance->doc_verify_status;
        $mGrievanceSolvedApplicantion->inner_workflow_id    = $activeGrievance->inner_workflow_id;
        $mGrievanceSolvedApplicantion->is_doc               = $activeGrievance->is_doc;
        $mGrievanceSolvedApplicantion->apply_date           = $activeGrievance->apply_date;
        $mGrievanceSolvedApplicantion->other_info           = $activeGrievance->other_info;
        $mGrievanceSolvedApplicantion->reopen_count         = $refDetails['reopenCount'];
        $mGrievanceSolvedApplicantion->application_id       = $activeGrievance->id;
        $mGrievanceSolvedApplicantion->approved_date        = $now;
        $mGrievanceSolvedApplicantion->ranking              = 1;                                        // Static
        $mGrievanceSolvedApplicantion->approve_no           = $refDetails['approvalNo'];
        $mGrievanceSolvedApplicantion->save();
        return $mGrievanceSolvedApplicantion->id;
    }
}
