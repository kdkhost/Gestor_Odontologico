<?php

namespace App\Filament\Auth\Pages;

use Filament\Auth\Pages\Login;

class CoreLogin extends Login
{
    public function mount(): void
    {
        redirect()->route('admin.login');
    }
}
