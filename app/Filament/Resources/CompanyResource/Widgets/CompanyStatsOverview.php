<?php
// app/Filament/Resources/CompanyResource/Widgets/CompanyStatsOverview.php

namespace App\Filament\Resources\CompanyResource\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Model;

class CompanyStatsOverview extends BaseWidget
{
    public ?Model $record = null;

    protected function getStats(): array
    {
        return [
            Stat::make('Total Users', $this->record->users_count ?? 0)
                ->description('All company members')
                ->descriptionIcon('heroicon-m-users')
                ->color('info')
                ->chart([7, 3, 4, 5, 6, 3, 5, 3]),
            
            Stat::make('Active Staff', $this->record->active_users_count ?? 0)
                ->description('Admins & Managers')
                ->descriptionIcon('heroicon-m-user-group')
                ->color('success')
                ->chart([3, 2, 3, 4, 3, 2, 3, 2]),
            
            Stat::make('Tenants', $this->record->tenants_count ?? 0)
                ->description('Registered tenants')
                ->descriptionIcon('heroicon-m-user')
                ->color('gray')
                ->chart([4, 1, 1, 1, 3, 1, 2, 1]),
            
            Stat::make('Member Since', $this->record->created_at->diffForHumans())
                ->description('Company registration')
                ->descriptionIcon('heroicon-m-calendar')
                ->color('warning'),
        ];
    }
}