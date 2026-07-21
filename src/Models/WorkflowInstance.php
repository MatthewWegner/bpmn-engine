<?php

namespace MatthewWegner\BpmnEngine\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkflowInstance extends Model
{
    protected $guarded = [];

    /**
     * The workflow version blueprint this instance is following.
     */
    public function version(): BelongsTo
    {
        return $this->belongsTo(WorkflowVersion::class, 'workflow_version_id');
    }
}