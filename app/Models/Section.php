<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Section extends Model
{
    public $timestamps = false;

    protected $guarded = ['id'];
    protected $fillable = ['title', 'quiz_id'];

    public function quiz()
    {
        return $this->belongsTo('App\Models\Quiz');
    }

    public function groups()
    {
        return $this->hasMany('App\Models\Group');
    }


    public function children() {
        return $this->hasMany($this, 'section_group_id', 'id');
    }
}
