<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Union extends Model
{
    const CREATED_AT = 'CreateAt';
    const UPDATED_AT = 'UpdateAt';
    protected $primaryKey='UnionId';
    protected $table = 'unions';
}
