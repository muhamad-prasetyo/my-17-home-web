<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AnnouncementResource\Pages;
use App\Filament\Resources\AnnouncementResource\RelationManagers;
use App\Models\Announcement;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Card;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Forms\Components\Actions\Action as FormAction;

class AnnouncementResource extends Resource
{
    protected static ?string $model = Announcement::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationLabel = 'Pengumuman';



    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Card::make()
                    ->columns(2)
                    ->schema([
                        TextInput::make('title')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('slug')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),
                        Textarea::make('excerpt')
                            ->rows(3)
                            ->maxLength(500)
                            ->label('Ringkasan (Excerpt)'),
                        FileUpload::make('banner_path')
                            ->columnSpanFull()
                            ->image()
                            ->label('Banner (opsional)')
                            ->directory('announcements')
                            ->maxSize(2048)
                            ->hint('Maksimal 2MB, format: jpg, jpeg, png, webp')
                            ->imagePreviewHeight('150')
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp', 'image/jpg'])
                            ->imageEditor()
                            ->imageResizeMode('cover')
                            ->imageResizeTargetWidth(800)
                            ->imageResizeTargetHeight(600),
                        RichEditor::make('content')
                            ->required()
                            ->label('Konten'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->sortable(),
                ImageColumn::make('banner_path')
                    ->label('Banner')
                    ->square()
                    ->width(50),
                TextColumn::make('title')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('excerpt')
                    ->limit(50)
                    ->label('Ringkasan'),
                TextColumn::make('created_at')
                    ->dateTime('d M Y')
                    ->label('Dibuat'),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->modalHeading('Detail Pengumuman')
                    ->modalButton('Tutup')
                    ->modalWidth('2xl')
                    ->recordTitle('title')
                    ->form([
                        FileUpload::make('banner_path')
                            ->image()
                            ->disabled()
                            ->label('Banner'),
                        TextInput::make('title')->disabled(),
                        TextInput::make('slug')->disabled(),
                        Textarea::make('excerpt')->disabled(),
                        RichEditor::make('content')->disabled(),
                    ]),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
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
            'index' => Pages\ListAnnouncements::route('/'),
            'create' => Pages\CreateAnnouncement::route('/create'),
            'edit' => Pages\EditAnnouncement::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->select('id', 'title', 'slug', 'excerpt', 'content', 'banner_path', 'created_at');
    }
}
