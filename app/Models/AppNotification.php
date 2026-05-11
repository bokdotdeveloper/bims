<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AppNotification extends Model
{
    protected $table = 'app_notifications';

    protected $fillable = ['type', 'title', 'message', 'icon', 'meta', 'is_read'];

    protected function casts(): array
    {
        return [
            'meta'    => 'array',
            'is_read' => 'boolean',
        ];
    }
}

