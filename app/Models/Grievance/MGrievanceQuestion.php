<?php

namespace App\Models\Grievance;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MGrievanceQuestion extends Model
{
    use HasFactory;

    /**
     * | Get Question list according to id 
     */
    public function getQuestionListById($id)
    {
        return  MGrievanceQuestion::where('id', $id)
            ->where('status', true);
    }

    /**
     * | Get All question list 
     */
    public function getAllQuestionList()
    {
        return MGrievanceQuestion::where('status', true)
            ->orderByDesc('id');
    }

    /**
     * | Get details for the parent id
     */
    public function getQuestionsByParentId($id)
    {
        return  MGrievanceQuestion::where('parent_question_id', $id)
            ->where('status', true)
            ->orderByDesc('id');
    }
}
