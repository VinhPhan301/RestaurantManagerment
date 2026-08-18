<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReservationRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'branch_id',
        'table_id',
        'customer_name',
        'phone',
        'reservation_date',
        'reservation_time',
        'guests',
        'note',
        'status',
    ];

    protected $casts = [
        'reservation_date' => 'date',
    ];

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function table()
    {
        return $this->belongsTo(Table::class);
    }
}
