<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Agent extends Model
{
    const CREATED_AT = 'CreateAt';
    const UPDATED_AT = 'UpdateAt';
    protected $primaryKey='AgentId';
    protected $table='agents';
}
