<?php

namespace App\Models\Workflow;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class WorkflowTrack extends Model
{
    use HasFactory;
    protected $guarded = [];

    /**
     * | Save the details in the wf tracks
     */
    public function saveTrack($request)
    {
        $track      = new WorkflowTrack;
        $userId     = $request->user_id;
        $ulbId      = $request->ulb_id ?? authUser($request)->ulb_id;
        $mTrackDate = $request->trackDate ?? Carbon::now()->format('Y-m-d H:i:s');

        $track->workflow_id         = $request->workflowId;
        $track->citizen_id          = $request->citizenId;
        $track->module_id           = $request->moduleId;
        $track->ref_table_dot_id    = $request->refTableDotId;
        $track->ref_table_id_value  = $request->refTableIdValue;
        $track->track_date          = $mTrackDate;
        $track->message             = $request->comment;
        $track->forward_date        = $request->forwardDate ?? null;
        $track->forward_time        = $request->forwardTime ?? null;
        $track->sender_role_id      = $request->senderRoleId ?? null;
        $track->receiver_role_id    = $request->receiverRoleId ?? null;
        $track->verification_status = $request->verificationStatus ?? 0;
        $track->user_id             = $userId;
        $track->ulb_id              = $ulbId;
        return  $track->save();
    }

    /**
     * | Get Workflow Track by Ref Table, Workflow, and ref table Value and Receiver RoleId
     */
    public function getWfTrackByRefId(array $req)
    {
        return WorkflowTrack::where('workflow_id', $req['workflowId'])
            ->where('ref_table_dot_id', $req['refTableDotId'])
            ->where('ref_table_id_value', $req['refTableIdValue'])
            ->where('receiver_role_id', $req['receiverRoleId'])
            ->where('status', true)
            ->first();
    }


        /**
     * | Get Tracks by Ref Table Id
     */
    public function getTracksByRefId($mRefTable, $tableId)
    {
        return DB::table('workflow_tracks')
            ->select(
                'workflow_tracks.ref_table_dot_id AS referenceTable',
                'workflow_tracks.ref_table_id_value AS applicationId',
                'workflow_tracks.message',
                'workflow_tracks.track_date',
                'workflow_tracks.forward_date',
                'workflow_tracks.forward_time',
                'w.role_name as commentedBy',
                'wr.role_name as forwarded_to',
                'users.name',
                'users.user_code',
            )
            ->where('ref_table_dot_id', $mRefTable)
            ->where('ref_table_id_value', $tableId)
            ->join('wf_roles as w', 'w.id', '=', 'workflow_tracks.sender_role_id')
            ->join('users', 'users.id', '=', 'workflow_tracks.user_id')
            ->leftJoin('wf_roles as wr', 'wr.id', '=', 'workflow_tracks.receiver_role_id')
            ->where('citizen_id', null)
            ->orderByDesc('workflow_tracks.id')
            ->get();
    }
}
