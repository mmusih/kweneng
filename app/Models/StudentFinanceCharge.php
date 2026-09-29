<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentFinanceCharge extends Model
{
    protected $guarded = ['id'];

    protected $casts = ['charged_on' => 'date'];
}
