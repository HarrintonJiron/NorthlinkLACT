<?php

namespace App\Modules\Personnel\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeAttendance extends Model
{
    public const TYPE_PRESENTE = 'presente';

    public const TYPE_AUSENTE = 'ausente';

    public const TYPE_PERMISO = 'permiso';

    public const TYPE_VACACIONES = 'vacaciones';

    public const TYPE_SUBSIDIO = 'subsidio';

    public const TYPE_FERIADO = 'feriado';

    public const TYPE_DESCANSO = 'descanso';

    public const TYPE_ENTRADA_TARDIA = 'entrada_tardia';

    public const TYPE_SALIDA_ANTICIPADA = 'salida_anticipada';

    public const TYPE_INCAPACIDAD = 'incapacidad';

    public const TYPE_PARCIAL = 'dia_parcial';

    protected $fillable = [
        'employee_id',
        'attendance_date',
        'type',
        'check_in',
        'check_out',
        'notes',
        'justification_path',
        'justification_name',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'attendance_date' => 'date',
        ];
    }

    /**
     * @return array<int, string>
     */
    public static function types(): array
    {
        return [
            self::TYPE_PRESENTE,
            self::TYPE_AUSENTE,
            self::TYPE_PERMISO,
            self::TYPE_VACACIONES,
            self::TYPE_SUBSIDIO,
            self::TYPE_FERIADO,
            self::TYPE_DESCANSO,
            self::TYPE_ENTRADA_TARDIA,
            self::TYPE_SALIDA_ANTICIPADA,
            self::TYPE_INCAPACIDAD,
            self::TYPE_PARCIAL,
        ];
    }

    public static function typeLabel(string $type): string
    {
        return match ($type) {
            self::TYPE_PRESENTE => 'Presente',
            self::TYPE_AUSENTE => 'Ausente',
            self::TYPE_PERMISO => 'Permiso',
            self::TYPE_VACACIONES => 'Vacaciones',
            self::TYPE_SUBSIDIO => 'Subsidio',
            self::TYPE_FERIADO => 'Feriado',
            self::TYPE_DESCANSO => 'Descanso',
            self::TYPE_ENTRADA_TARDIA => 'Entrada tardía',
            self::TYPE_SALIDA_ANTICIPADA => 'Salida anticipada',
            self::TYPE_INCAPACIDAD => 'Incapacidad',
            self::TYPE_PARCIAL => 'Día trabajado parcialmente',
            default => $type,
        };
    }

    public static function requiresCheckIn(string $type): bool
    {
        return in_array($type, [
            self::TYPE_PRESENTE,
            self::TYPE_ENTRADA_TARDIA,
            self::TYPE_SALIDA_ANTICIPADA,
            self::TYPE_PARCIAL,
        ], true);
    }

    public static function requiresCheckOut(string $type): bool
    {
        return in_array($type, [
            self::TYPE_PRESENTE,
            self::TYPE_ENTRADA_TARDIA,
            self::TYPE_SALIDA_ANTICIPADA,
            self::TYPE_PARCIAL,
        ], true);
    }

    public static function allowsJustification(string $type): bool
    {
        return in_array($type, [
            self::TYPE_AUSENTE,
            self::TYPE_PERMISO,
            self::TYPE_INCAPACIDAD,
            self::TYPE_ENTRADA_TARDIA,
            self::TYPE_SALIDA_ANTICIPADA,
            self::TYPE_PARCIAL,
        ], true);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
