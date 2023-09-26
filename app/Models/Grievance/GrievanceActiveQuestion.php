<?php

namespace App\Models\Grievance;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Symfony\Component\CssSelector\Node\FunctionNode;

class GrievanceActiveQuestion extends Model
{
    use HasFactory;

    /**
     * | Save the forwarded question detials 
     */
    public function saveQuestion($request)
    {
        $mGrievanceActiveQuestion = new GrievanceActiveQuestion();
        $mGrievanceActiveQuestion->questions    = $request->question;
        $mGrievanceActiveQuestion->answers      = $request->refAnswer ?? null;
        $mGrievanceActiveQuestion->module_id    = $request->moduleId;
        $mGrievanceActiveQuestion->apply_date   = Carbon::now();
        $mGrievanceActiveQuestion->remarks      = $request->remarks;
        $mGrievanceActiveQuestion->priority     = $request->priorities;
        $mGrievanceActiveQuestion->initiator    = $request->initiator;
        $mGrievanceActiveQuestion->current_role = $request->current_role ?? null;
        $mGrievanceActiveQuestion->save();
    }

    /**
     * | Get active questions list 
     */
    public function listActiveQuestions()
    {
        return GrievanceActiveQuestion::where('status', 1)
            ->orderByDesc('id');
    }

    /**
     * | Update the application details 
     */
    public function updateDetails($reqBody, $id)
    {
        GrievanceActiveQuestion::where('id', $id)
            ->update($reqBody);
    }
}
