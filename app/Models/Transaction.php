<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'organization_id',
        'maker_id',
        'checker_id',
        'type',
        'category',
        'amount',
        'description',
        'proof_path',
        'status',
        'tx_hash',
        'rejection_reason',
    ];

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function maker()
    {
        return $this->belongsTo(User::class, 'maker_id');
    }

    public function checker()
    {
        return $this->belongsTo(User::class, 'checker_id');
    }
}