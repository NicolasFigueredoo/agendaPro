<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Guardar y leer siempre a través de App\Support\Settings
 * (maneja JSON, encriptado y caché).
 */
class Setting extends Model
{
    protected $fillable = ['key', 'value'];
}
