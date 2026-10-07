<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudyRetentionSetting extends Model
{
    protected $fillable = ['term_id', 'source_term_id', 'assessment'];

    public function sourceTerm()
    {
        return $this->belongsTo(Term::class, 'source_term_id');
    }
}
