<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PlanResource\Pages;
use App\Filament\Resources\PlanResource\RelationManagers;
use App\Models\Plan;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PlanResource extends Resource
{
    protected static ?string $model = Plan::class;

    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';
    protected static ?string $navigationGroup = '🏢 Core';
    protected static ?int $navigationSort = 3;

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->isSuperAdmin() ?? false;
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->isSuperAdmin() ?? false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Plan Details')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(191)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (string $operation, $state, Forms\Set $set) => $operation === 'create' ? $set('slug', \Illuminate\Support\Str::slug($state)) : null),
                             Forms\Components\TextInput::make('slug')
                            ->disabled()
                            ->dehydrated()
                            ->maxLength(191)
                            ->unique(Plan::class, 'slug', ignoreRecord: true)
                            ->helperText('Auto-generated from the plan name. Cannot be changed manually.')
                            ->placeholder('Will be generated after you type a name...'),
                        Forms\Components\Textarea::make('description')
                            ->columnSpanFull()
                            ->rows(3),
                        Forms\Components\TextInput::make('price')
                            ->required()
                            ->numeric()
                            ->prefix('$')
                            ->step(0.01),
                        Forms\Components\Select::make('billing_cycle')
                            ->required()
                            ->options([
                                'monthly' => 'Monthly',
                                'yearly' => 'Yearly',
                            ])
                            ->default('monthly'),
                        Forms\Components\Toggle::make('is_active')
                        
                            ->label('Active State')
                           
                            ->default(true)
                            ->columnSpanFull(),
                    ])->columns(2),

                Forms\Components\Section::make('Features & Limits')
                    ->description('Set the precise limits and capabilities for this plan. Leave limits empty for "unlimited".')
                    ->schema([
                        Forms\Components\Group::make()
                            ->statePath('features')
                            ->schema([
                                Forms\Components\Fieldset::make('Resource Limits (Count)')
                                    ->schema([
                                        Forms\Components\TextInput::make('max_properties')
                                            ->label('Max Properties')
                                            ->numeric()
                                            ->placeholder('Unlimited'),
                                        Forms\Components\TextInput::make('max_units')
                                            ->label('Max Units')
                                            ->numeric()
                                            ->placeholder('Unlimited'),
                                        Forms\Components\TextInput::make('max_employees')
                                            ->label('Max Employees')
                                            ->numeric()
                                            ->placeholder('Unlimited'),
                                        Forms\Components\TextInput::make('max_users')
                                            ->label('Max Admin Users')
                                            ->numeric()
                                            ->placeholder('Unlimited'),
                                    ])->columns(2),

                                Forms\Components\Fieldset::make('Feature Flags (Access)')
                                    ->schema([
                                        Forms\Components\Toggle::make('maintenance_tracking')
                                            ->label('Maintenance Tracking')
                                            ->default(false),
                                        Forms\Components\Toggle::make('rental_requests')
                                            ->label('Rental Requests Portal')
                                            ->default(false),
                                        Forms\Components\Toggle::make('expense_tracking')
                                            ->label('Expense Tracking')
                                            ->default(false),
                                        Forms\Components\Toggle::make('document_management')
                                            ->label('Document Management')
                                            ->default(false),
                                        Forms\Components\Toggle::make('advanced_reports')
                                            ->label('Analytics & Reports')
                                            ->default(false),
                                        Forms\Components\Toggle::make('export_data')
                                            ->label('Data Exporting (PDF/Excel)')
                                            ->default(false),
                                    ])->columns(3),
                            ]),
                    ]),
            ]);

    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('slug')
                    ->searchable()
                    ->color('gray'),
                Tables\Columns\TextColumn::make('price')
                    ->money()
                    ->sortable()
                    ->badge()
                    ->color('success'),
                Tables\Columns\TextColumn::make('billing_cycle')
                    ->searchable()
                    ->badge(),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->label('Active status'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Status')
                    ->placeholder('All Plans')
                    ->trueLabel('Active')
                    ->falseLabel('Inactive'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListPlans::route('/'),
            'create' => Pages\CreatePlan::route('/create'),
            'view'   => Pages\ViewPlan::route('/{record}'),
            'edit'   => Pages\EditPlan::route('/{record}/edit'),
        ];
    }
}
