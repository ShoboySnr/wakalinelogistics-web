<?php

namespace App\Modules\Admin\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SupportTicket extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'client_id',
        'ticket_number',
        'subject',
        'description',
        'status',
        'priority',
        'category',
        'assigned_to',
        'resolved_at',
        'closed_at',
    ];

    protected $casts = [
        'resolved_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function assignedAdmin()
    {
        return $this->belongsTo(\App\Models\User::class, 'assigned_to');
    }

    public function messages()
    {
        return $this->hasMany(TicketMessage::class, 'ticket_id')->orderBy('created_at');
    }

    public function latestMessage()
    {
        return $this->hasOne(TicketMessage::class, 'ticket_id')->latestOfMany();
    }

    public function unreadByAdmin()
    {
        return $this->messages()->where('sender_type', 'client')->where('is_read', false);
    }

    public static function generateTicketNumber(): string
    {
        do {
            $number = 'TKT-' . strtoupper(substr(md5(uniqid()), 0, 8));
        } while (static::where('ticket_number', $number)->exists());

        return $number;
    }

    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'open' => 'bg-blue-100 text-blue-800',
            'in_progress' => 'bg-yellow-100 text-yellow-800',
            'resolved' => 'bg-green-100 text-green-800',
            'closed' => 'bg-gray-100 text-gray-800',
            default => 'bg-gray-100 text-gray-800',
        };
    }

    public function getPriorityColorAttribute(): string
    {
        return match($this->priority) {
            'low' => 'bg-gray-100 text-gray-600',
            'medium' => 'bg-blue-100 text-blue-700',
            'high' => 'bg-orange-100 text-orange-700',
            'urgent' => 'bg-red-100 text-red-700',
            default => 'bg-gray-100 text-gray-600',
        };
    }
}
