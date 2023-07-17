<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class administrator extends Model
{
    protected $fillable = ['userID'];

    public $timestamps = false;
    public function warnings() {
      return $this->hasMany('App\Models\warning');
    }
    public function reports() {
        return $this->hasMany('App\Models\report');
    }
}
