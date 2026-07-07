<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WhatsappSenderApiKeyLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'sender_id',
        'key_hint',
        'action',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(WhatsappSender::class, 'sender_id');
    }
}
