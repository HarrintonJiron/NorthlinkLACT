<?php

namespace App\Modules\Personnel\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeLeave extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    public const TYPE_SICK = 'sick';

    public const TYPE_MATERNITY = 'maternity';

    public const TYPE_PATERNITY = 'paternity';

    public const TYPE_BEREAVEMENT = 'bereavement';

    public const TYPE_MARRIAGE = 'marriage';

    public const TYPE_LEGAL = 'legal';

    public const TYPE_UNPAID_PERSONAL = 'unpaid_personal';

    public const TYPE_OTHER = 'other';

    /**
     * Whether each leave type is paid by default, per the Código del Trabajo de
     * Nicaragua. The final call always belongs to the business (Recursos Humanos),
     * so this is only a suggested default the form pre-selects.
     *
     * @return array<string, bool>
     */
    public static function defaultPaidByType(): array
    {
        return [
            self::TYPE_SICK => true,
            self::TYPE_MATERNITY => true,
            self::TYPE_PATERNITY => true,
            self::TYPE_BEREAVEMENT => true,
            self::TYPE_MARRIAGE => true,
            self::TYPE_LEGAL => false,
            self::TYPE_UNPAID_PERSONAL => false,
            self::TYPE_OTHER => false,
        ];
    }

    protected $fillable = [
        'employee_id',
        'type',
        'start_date',
        'end_date',
        'days',
        'paid',
        'status',
        'notes',
        'approved_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'paid' => 'boolean',
            'approved_at' => 'datetime',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
