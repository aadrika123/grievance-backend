<?php

namespace App\Models\Workflow;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorkflowCandidate extends Model
{
    use HasFactory;
    protected $connection = 'pgsql_master';
}
