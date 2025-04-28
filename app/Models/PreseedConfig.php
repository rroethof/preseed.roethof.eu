<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PreseedConfig extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'hash_id',
        'original_name',
        'content',
        // 'user_id', // Voeg toe als je user koppeling gebruikt
    ];

    /**
     * Get the route key for the model.
     * Bind aan 'hash_id' voor route model binding indien gewenst.
     *
     * @return string
     */
    // public function getRouteKeyName(): string
    // {
    //     return 'hash_id';
    // }
}
