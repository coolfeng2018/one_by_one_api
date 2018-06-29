<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    protected $primaryKey='UserId';
    protected $table='users';
    protected $connection = 'account';
}
