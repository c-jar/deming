<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Action extends Model
{
    public static $searchable = [
        'reference',
        'type',
        'scope',
        'cause',
        'remediation',
        'justification'
    ];

    protected $dates = [
        'creation_date',
        'due_date',
        'close_date',
    ];

    protected $fillable = [
        'reference',
        'type',
        'criticity',
        'scope',
        'cause',
        'remediation',
        'creation_date',
        'due_date',
        'close_date',
        'justification'
    ];

    // Control status :
    // O - Open
    // 1 - Closed
    // 2 - Rejected

    public function owners()
    {
        return $this->belongsToMany(User::class, 'action_user', 'action_id')->orderBy('name');
    }

    public function measures()
    {
        return $this->belongsToMany(Measure::class, 'action_measure', 'action_id');
    }
}