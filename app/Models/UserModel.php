<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Shield\Models\UserModel as ShieldUserModel;

/**
 * Model akun Shield dengan tambahan tautan ke marga dan data diri di silsilah.
 */
class UserModel extends ShieldUserModel
{
    protected function initialize(): void
    {
        parent::initialize();

        $this->allowedFields = [
            ...$this->allowedFields,
            'marga_id',
            'person_id',
            'punguan_id',
        ];
    }
}
