<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Table extends Model
{
    use HasFactory;

    protected $fillable = [
        'branch_id',
        'name',
        'capacity',
        'status',
        'reservation_customer_name',
        'reservation_phone',
        'reservation_time',
        'reservation_note',
    ];

    protected $casts = [
        'reservation_time' => 'datetime',
    ];

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }
}
