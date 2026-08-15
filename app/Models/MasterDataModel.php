<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

abstract class MasterDataModel extends Model
{
    protected $guarded = ['id'];
}