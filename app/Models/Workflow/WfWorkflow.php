<?php

namespace App\Models\Workflow;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WfWorkflow extends Model
{
    use HasFactory;


    /**
     * | Get Initiator Id While Sending to level Pending For the First Time
     * | @param mixed $wfWorkflowId > Workflow Id of Modules
     * | @var string $query
     */
    public function getInitiatorId(int $wfWorkflowId)
    {
        $query = "SELECT 
                    r.id AS role_id,
                    r.role_name AS role_name,
                    w.forward_role_id
                    FROM wf_roles r
                    INNER JOIN (SELECT * FROM wf_workflowrolemaps WHERE workflow_id=$wfWorkflowId) w ON w.wf_role_id=r.id
                    WHERE w.is_initiator=TRUE 
                    ";
        return $query;
    }

    /**
     * | Get workflow id by workflowId and ulbId 
     * | @param workflowID
     * | @param ulbId
     * | @return  
     */
    public function getulbWorkflowId($wfMstId, $ulbId)
    {
        return  WfWorkflow::where('wf_master_id', $wfMstId)
            ->where('ulb_id', $ulbId)
            ->where('is_suspended', false)
            ->first();
    }


    /**
     * | Get Finisher Id while approve or reject application
     * | @param wfWorkflowId ulb workflow id 
     */
    public function getFinisherId(int $wfWorkflowId)
    {
        $query = "SELECT 
                    r.id AS role_id,
                    r.role_name AS role_name 
                    FROM wf_roles r
                    INNER JOIN (SELECT * FROM wf_workflowrolemaps WHERE workflow_id=$wfWorkflowId) w ON w.wf_role_id=r.id
                    WHERE w.is_finisher=TRUE ";
        return $query;
    }
}
