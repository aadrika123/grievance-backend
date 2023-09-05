<?php

namespace App\Models\ThirdParty;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class WfActiveDocument extends Model
{
    use HasFactory;
    protected $guarded = [];
    protected $connection = 'pgsql_master';

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
        return WfActiveDocument::select(
            'wf_active_documents.id',
            'wf_active_documents.document',
            'wf_active_documents.remarks',
            'wf_active_documents.verify_status',
            'wf_active_documents.doc_code',
            'wf_active_documents.doc_category',
            'wf_active_documents.status',
            DB::raw("concat(relative_path,'/',document) as ref_doc_path"),
        )
            ->where('wf_active_documents.active_id', $applicationId)
            ->where('wf_active_documents.workflow_id', $workflowId)
            ->where('wf_active_documents.module_id', $moduleId)
            ->where('wf_active_documents.status', '!=', 0)
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

    /**
     * | Update the active id of the document 
     */
    public function updateActiveIdOfDoc($refRequest, $activeId)
    {
        WfActiveDocument::where('active_id', $refRequest->oldActiveId)
            ->where('workflow_id', $refRequest->workflowId)
            ->where('ulb_id', $refRequest->ulbId)
            ->where('module_id', $refRequest->moduleId)
            ->update([
                "active_id" => $activeId
            ]);
    }
}
