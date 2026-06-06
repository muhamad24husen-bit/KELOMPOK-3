<?php

namespace App\Models\BEMS;

use Illuminate\Database\Eloquent\Model;

class UiTemplate extends Model
{
    protected $fillable = [
        'name',
        'icon',
        'description',
        'schema',
        'default_mapping',
    ];

    protected $casts = [
        'schema'          => 'array',
        'default_mapping' => 'array',
    ];

    /**
     * Nodes using this template.
     */
    public function nodes()
    {
        return $this->hasMany(Node::class);
    }
}
