<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Participant extends Model
{
    protected $fillable = [
        'draw_id',
        'data',
        'display_value',
    ];

    protected $casts = [
        'data' => 'array',
    ];

    public function draw(): BelongsTo
    {
        return $this->belongsTo(Draw::class);
    }

    public function winner()
    {
        return $this->hasOne(Winner::class);
    }

    /**
     * Verificar si este participante ya ganó
     */
    public function hasWon(): bool
    {
        return Winner::where('draw_id', $this->draw_id)
            ->where('participant_id', $this->id)
            ->exists();
    }

    /**
     * Obtener un campo específico del JSON data
     */
    public function getField(string $field): mixed
    {
        return $this->data[$field] ?? null;
    }

    /**
     * Obtener múltiples campos formateados
     */
    public function getFields(array $fields): string
    {
        $values = [];
        foreach ($fields as $field) {
            if (isset($this->data[$field])) {
                $values[] = $this->data[$field];
            }
        }
        return implode(' ', $values);
    }
}
