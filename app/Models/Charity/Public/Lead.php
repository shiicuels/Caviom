<?php

namespace App\Models\Charity\Public;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Lead extends Model
{
    use HasFactory;

    protected $guarded = [];
    public $timestamps = false;

    protected $casts = [
        'first_name' => 'encrypted',
        'middle_name' => 'encrypted',
        'last_name' => 'encrypted',

    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'charitable_organization_id', 'id');
    }
}