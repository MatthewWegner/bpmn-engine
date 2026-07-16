<?php

namespace MatthewWegner\BpmnEngine\Models;

use Illuminate\Database\Eloquent\Model;

class WorkflowVersion extends Model
{
    protected $guarded = [];

    public function nodes()
    {
        return $this->hasMany(WorkflowNode::class);
    }

    public function edges()
    {
        return $this->hasMany(WorkflowEdge::class);
    }
}