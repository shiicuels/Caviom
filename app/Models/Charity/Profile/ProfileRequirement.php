<?php

namespace App\Models\Charity\Profile;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Models\CharitableOrganization;

class ProfileRequirement extends Model
{
    use HasFactory;
    protected $guarded = [];
    protected $touches = ['charitableOrganization'];
    public function charitableOrganization()
    {
        return $this->belongsTo(CharitableOrganization::class, 'charitable_organization_id', 'id');
    }
    public function submittedBy()
    {
        return $this->belongsTo(User::class, 'submitted_by', 'id');
    }
}
