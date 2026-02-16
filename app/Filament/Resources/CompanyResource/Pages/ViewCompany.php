<?php

namespace App\Filament\Resources\CompanyResource\Pages;

use App\Filament\Resources\CompanyResource;
use Filament\Resources\Pages\Page;

class ViewCompany extends Page
{
    protected static string $resource = CompanyResource::class;
    protected static string $view = 'filament.pages.view-company'; // Blade جديد

    public $company;

    public function mount($record): void
    {
        $this->company = CompanyResource::getModel()::findOrFail($record);
    }
}
