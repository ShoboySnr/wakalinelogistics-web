<?php

namespace App\Modules\Metter\Models;

use Illuminate\Database\Eloquent\Model;

class MetterConfiguration extends Model
{
    protected $fillable = [
        'key',
        'value',
        'type',
        'category',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public static function get(string $key, $default = null)
    {
        $config = self::where('key', $key)->where('is_active', true)->first();
        
        if (!$config) {
            return $default;
        }

        return self::castValue($config->value, $config->type);
    }

    public static function set(string $key, $value, string $type = 'string', string $category = 'general', ?string $description = null): void
    {
        self::updateOrCreate(
            ['key' => $key],
            [
                'value' => is_array($value) ? json_encode($value) : $value,
                'type' => $type,
                'category' => $category,
                'description' => $description,
                'is_active' => true,
            ]
        );
    }

    public static function getByCategory(string $category): array
    {
        return self::where('category', $category)
            ->where('is_active', true)
            ->get()
            ->mapWithKeys(function ($config) {
                return [$config->key => self::castValue($config->value, $config->type)];
            })->toArray();
    }

    protected static function castValue($value, string $type)
    {
        return match($type) {
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'number' => is_numeric($value) ? (float) $value : $value,
            'json' => json_decode($value, true),
            default => $value,
        };
    }
}
