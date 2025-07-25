<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;    

class Map extends Page
{    protected static ?string $navigationIcon = 'heroicon-o-map';
  
    
    protected static ?string $navigationLabel = 'Peta Lokasi';
    protected static ?int $navigationSort = 3;
    
    protected static string $view = 'filament.pages.map';
    
    public static function canAccess(): bool
    {
        return true; // Sementara kita izinkan akses untuk semua user
    }
}
