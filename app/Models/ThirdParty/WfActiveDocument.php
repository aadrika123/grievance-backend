<?php

namespace App\Models\ThirdParty;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class WfActiveDocument extends Model
{
    use HasFactory;
    protected $guarded = [];

    /**
     * | Get Workflow Active Documents By Active Id
     */
    public function getDocByRefIds($activeId, $workflowId, $moduleId)
    {
        return WfActiveDocument::select(
            DB::raw("concat(relative_path,'/',document) as doc_path"),
            '*'
        )
            ->where('active_id', $activeId)
            ->where('workflow_id', $workflowId)
            ->where('module_id', $moduleId)
            ->where('status', 1)
            ->orderByDesc('id')
            ->get();
    }


    /**
     * | Post Workflow Document
     */
    public function postDocuments($req)
    {
        $metaReqs = $this->metaReqs($req);
        if (isset($req->verifyStatus)) {
            $metaReqs = array_merge($metaReqs, [
                "verify_status" => $req->verifyStatus
            ]);
        }
        WfActiveDocument::create($metaReqs);
    }

    /**
     * | Meta Request function for updation and post the request
     */
    public function metaReqs($req)
    {
        return [
            "active_id"     => $req->activeId,
            "workflow_id"   => $req->workflowId,
            "ulb_id"        => $req->ulbId,
            "module_id"     => $req->moduleId,
            "relative_path" => $req->relativePath,
            "document"      => $req->document,
            "remarks"       => $req->remarks ?? null,
            "doc_code"      => $req->docCode,
            "owner_dtl_id"  => $req->ownerDtlId,
            "doc_category"  => $req->docCategory ?? null
        ];
    }

    /**
     * | Get the document for the module 
     */
    public function getDocsByAppNo($applicationId, $workflowId, $moduleId)
    {
        return DB::table('wf_active_documents as d')
            ->select(
                'd.id',
                'd.document',
                'd.remarks',
                'd.verify_status',
                'd.doc_code',
                'd.doc_category',
                'd.status',
                DB::raw("concat(relative_path,'/',document) as ref_doc_path"),
            )
            ->where('d.active_id', $applicationId)
            ->where('d.workflow_id', $workflowId)
            ->where('d.module_id', $moduleId)
            ->where('d.status', '!=', 0)
            ->get();
    }

    /**
     * | Get Uploaded documents
     */
    public function getDocsByActiveId($req)
    {
        return WfActiveDocument::where('active_id', $req->activeId)
            ->select(
                'doc_code',
                'owner_dtl_id',
                'verify_status'
            )
            ->where('workflow_id', $req->workflowId)
            ->where('module_id', $req->moduleId)
            ->where('verify_status', '!=', 2)
            ->where('status', 1)
            ->get();
    }

    /**
     * | Get Verified Documents
     */
    public function getVerifiedDocsByActiveId(array $req)
    {
        return WfActiveDocument::where('active_id', $req['activeId'])
            ->select(
                'doc_code',
                'owner_dtl_id',
                'verify_status'
            )
            ->where('workflow_id', $req['workflowId'])
            ->where('module_id', $req['moduleId'])
            ->where('verify_status', 1)
            ->where('status', 1)
            ->get();
    }


    /**
     * | Document Verify Reject
     */
    public function docVerifyReject($id, $req)
    {
        $document = WfActiveDocument::find($id);
        $document->update($req);
    }
}
