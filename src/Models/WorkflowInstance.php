<?php

namespace MatthewWegner\BpmnEngine\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkflowInstance extends Model
{
    protected $guarded = [];

    protected $casts = [
        'status' => \MatthewWegner\BpmnEngine\Enums\WorkflowInstanceStatus::class,
    ];

    /**
     * The workflow version blueprint this instance is following.
     */
    public function version(): BelongsTo
    {
        return $this->belongsTo(WorkflowVersion::class, 'workflow_version_id');
    }

    public function tokens()
    {
        return $this->hasMany(WorkflowToken::class, 'workflow_instance_id');
    }
}