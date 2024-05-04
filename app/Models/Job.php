<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Job extends Model
{
    use HasFactory;

    // trả về dữ liệu sở hữu id đã liên kết vs bảng job
    public function jobType(){
        return $this->belongsTo(JobType::class);
    }

    public function category(){
        return $this->belongsTo(Category::class);
    }

    public function applications(){
        return $this->hasMany(JobApplication::class);
    }

    public function user(){
        return $this->belongsTo(User::class);
    }
}
