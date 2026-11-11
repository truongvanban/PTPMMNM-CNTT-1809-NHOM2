<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\AccountProfileController;

class ProfileController extends AccountProfileController
{
    public function __construct()
    {
        $this->initializeProfile('admin', 'Quản trị viên');
    }
}
