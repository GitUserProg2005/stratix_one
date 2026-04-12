<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Webhook extends Model
{
    protected $fillable = [
        'name',
        'workflow_id',
        'node_id',
        'user_id',
        'is_active',
        'token',
        'last_triggered_at',
    ];
}
