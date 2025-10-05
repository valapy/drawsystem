<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Draw extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'background_image',
        'display_field',
        'available_fields',
        'display_template',
        'status',
    ];

    protected $casts = [
        'available_fields' => 'array',
        'display_template' => 'array',
    ];

    public function participants(): HasMany
    {
        return $this->hasMany(Participant::class);
    }

    public function winners(): HasMany
    {
        return $this->hasMany(Winner::class);
    }

    /**
     * Obtener participantes que aún no han ganado
     */
    public function availableParticipants()
    {
        return $this->participants()
            ->whereNotIn('id', function ($query) {
                $query->select('participant_id')
                    ->from('winners')
                    ->where('draw_id', $this->id);
            });
    }

    /**
     * Seleccionar un ganador aleatorio
     */
    public function drawWinner(): ?Participant
    {
        $participant = $this->availableParticipants()->inRandomOrder()->first();

        if ($participant) {
            Winner::create([
                'draw_id' => $this->id,
                'participant_id' => $participant->id,
                'won_at' => now(),
            ]);
        }

        return $participant;
    }

    /**
     * Resetear el sorteo (eliminar todos los ganadores)
     */
    public function reset(): void
    {
        $this->winners()->delete();
    }

    /**
     * Finalizar sorteo
     */
    public function finish(): void
    {
        $this->update(['status' => 'finished']);
    }
}
