<?php

namespace App\Filament\Resources\PlanResource\Pages;

use App\Filament\Resources\PlanResource;
use Filament\Actions;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;

class ViewPlan extends ViewRecord
{
    protected static string $resource = PlanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Plan Information')
                    ->schema([
                        Infolists\Components\TextEntry::make('name')
                            ->weight('bold')
                            ->size(Infolists\Components\TextEntry\TextEntrySize::Large),
                        Infolists\Components\TextEntry::make('slug')
                            ->color('gray'),
                        Infolists\Components\TextEntry::make('price')
                            ->money()
                            ->badge()
                            ->color('success'),
                        Infolists\Components\TextEntry::make('billing_cycle')
                            ->badge(),
                        Infolists\Components\IconEntry::make('is_active')
                            ->boolean()
                            ->label('Active Status'),
                        Infolists\Components\TextEntry::make('description')
                        ->default('No Description')
                        ->color('gray')
                            ->columnSpanFull(),
                    ])->columns(3),

                Infolists\Components\Section::make('Resource Limits')
                    ->description('Maximum resource limits defined for this plan. Empty means Unlimited.')
                    ->schema([
                        Infolists\Components\TextEntry::make('features.max_properties')
                            ->label('Max Properties')
                            ->default('Unlimited')
                            ->badge()
                            ->color('info'),
                        Infolists\Components\TextEntry::make('features.max_units')
                            ->label('Max Units')
                            ->default('Unlimited')
                            ->badge()
                            ->color('info'),
                        Infolists\Components\TextEntry::make('features.max_employees')
                            ->label('Max Employees')
                            ->default('Unlimited')
                            ->badge()
                            ->color('info'),
                        Infolists\Components\TextEntry::make('features.max_users')
                            ->label('Max Admin Users')
                            ->default('Unlimited')
                            ->badge()
                            ->color('info'),
                    ])->columns(4),

                Infolists\Components\Section::make('Feature Access')
                    ->description('Modules and capabilities enabled for this plan')
                    ->schema([
                        Infolists\Components\IconEntry::make('features.maintenance_tracking')
                            ->label('Maintenance Tracking')
                            ->boolean(),
                        Infolists\Components\IconEntry::make('features.rental_requests')
                            ->label('Rental Requests')
                            ->boolean(),
                        Infolists\Components\IconEntry::make('features.expense_tracking')
                            ->label('Expense Tracking')
                            ->boolean(),
                        Infolists\Components\IconEntry::make('features.document_management')
                            ->label('Document Management')
                            ->boolean(),
                        Infolists\Components\IconEntry::make('features.advanced_reports')
                            ->label('Advanced Reports')
                            ->boolean(),
                        Infolists\Components\IconEntry::make('features.export_data')
                            ->label('Data Export (PDF/Excel)')
                            ->boolean(),
                    ])->columns(3),
            ]);
    }
}
