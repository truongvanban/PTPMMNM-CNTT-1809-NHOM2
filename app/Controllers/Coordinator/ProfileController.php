<?php

declare(strict_types=1);

namespace App\Controllers\Coordinator;

use App\Controllers\AccountProfileController;

class ProfileController extends AccountProfileController
{
    public function __construct()
    {
        $this->initializeProfile('coordinator', 'Điều phối viên');
    }
}
