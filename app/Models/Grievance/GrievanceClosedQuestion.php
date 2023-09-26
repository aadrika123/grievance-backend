<?php

namespace App\Models\Grievance;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Symfony\Component\CssSelector\Node\FunctionNode;

class GrievanceClosedQuestion extends Model
{
    use HasFactory;

    /**
     * | Save the Closed Questions data 
     */
    public function saveClosedQuestionData($request, $status)
    {
        $mGrievanceClosedQuestion = new GrievanceClosedQuestion();
        $mGrievanceClosedQuestion->question_id          = $request->questionId ?? null;
        $mGrievanceClosedQuestion->questions            = $request->question ?? null;
        $mGrievanceClosedQuestion->answers              = $request->ans ?? null;
        $mGrievanceClosedQuestion->module_id            = $request->moduleId;
        $mGrievanceClosedQuestion->apply_date           = $request->applyDate;
        $mGrievanceClosedQuestion->status               = $status;
        $mGrievanceClosedQuestion->closed_date          = $request->closeDate;
        $mGrievanceClosedQuestion->initiator            = $request->initiator;
        $mGrievanceClosedQuestion->remarks              = $request->remarks ?? null;
        $mGrievanceClosedQuestion->finisher             = $request->finisher;
        $mGrievanceClosedQuestion->is_in_workflow       = $request->inWorkflow ?? 0;
        $mGrievanceClosedQuestion->priority             = $request->priority;
        $mGrievanceClosedQuestion->save();
    }
}
