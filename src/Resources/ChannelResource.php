<?php

namespace Filament\TeamChat\Resources;

use BackedEnum;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Filament\TeamChat\Models\Channel;
use Filament\TeamChat\Resources\ChannelResource\Pages\CreateChannel;
use Filament\TeamChat\Resources\ChannelResource\Pages\EditChannel;
use Filament\TeamChat\Resources\ChannelResource\Pages\ListChannels;
use UnitEnum;

class ChannelResource extends Resource
{
    protected static ?string $model = Channel::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-hashtag';

    protected static ?string $navigationLabel = 'チャンネル管理';

    protected static string|UnitEnum|null $navigationGroup = 'Team Chat';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('チャンネル名')
                    ->required()
                    ->maxLength(255),
                TextInput::make('slug')
                    ->label('スラッグ')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(255),
                Select::make('type')
                    ->label('種別')
                    ->options([
                        'public' => 'パブリック',
                        'private' => 'プライベート',
                    ])
                    ->required()
                    ->default('public'),
                Textarea::make('description')
                    ->label('説明')
                    ->rows(3),
                TextInput::make('topic')
                    ->label('トピック')
                    ->maxLength(255),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('チャンネル名')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('slug')
                    ->label('スラッグ')
                    ->searchable(),
                TextColumn::make('type')
                    ->label('種別')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'public' => 'success',
                        'private' => 'warning',
                        default => 'gray',
                    }),
                TextColumn::make('members_count')
                    ->label('メンバー数')
                    ->counts('members')
                    ->sortable(),
                TextColumn::make('creator.name')
                    ->label('作成者'),
                TextColumn::make('created_at')
                    ->label('作成日')
                    ->dateTime('Y-m-d')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->label('種別')
                    ->options([
                        'public' => 'パブリック',
                        'private' => 'プライベート',
                    ]),
            ])
            ->actions([
                EditAction::make(),
            ])
            ->bulkActions([
                DeleteBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListChannels::route('/'),
            'create' => CreateChannel::route('/create'),
            'edit' => EditChannel::route('/{record}/edit'),
        ];
    }
}
