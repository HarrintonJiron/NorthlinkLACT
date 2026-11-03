<?php

namespace App\Exceptions;

use Exception;

class OfflineSyncConflictException extends Exception
{
    /** @param array<string, mixed> $result */
    public function __construct(public readonly array $result)
    {
        parent::__construct((string) ($result['message'] ?? 'Conflicto de sincronización offline.'));
    }
}
