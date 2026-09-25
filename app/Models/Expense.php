<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Expense extends Model
{
    public const CATEGORIES = [
        'loyer' => 'Loyer',
        'salaires' => 'Salaires',
        'fournitures' => 'Fournitures',
        'transport' => 'Transport / livraison',
        'marketing' => 'Marketing',
        'utilitaires' => 'Eau / électricité / internet',
        'maintenance' => 'Maintenance / réparations',
        'taxes' => 'Taxes / impôts',
        'autre' => 'Autre',
    ];

    protected $fillable = ['category', 'label', 'amount', 'expense_date', 'notes', 'user_id'];

    protected function casts(): array
    {
        return ['expense_date' => 'date'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
