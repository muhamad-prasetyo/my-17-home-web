<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Office;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class OfficeController extends Controller
{
    /**
     * Mendapatkan daftar semua kantor
     */
    public function index(Request $request)
    {
        try {
            // Caching data kantor selama 30 menit karena jarang berubah
            $offices = Cache::remember('offices_list', 1800, function () {
                return Office::select([
                    'id', 'name', 'address', 'latitude', 'longitude', 'radius_meter', 'office_type'
                ])->get();
            });
            
            return response()->json([
                'success' => true,
                'data' => $offices
            ]);
        } catch (\Exception $e) {
            Log::error('Error mengambil data kantor: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Gagal mendapatkan data kantor',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }
    
    /**
     * Mendapatkan detail kantor berdasarkan ID
     */
    public function show($id)
    {
        try {
            $office = Cache::remember('office_' . $id, 1800, function () use ($id) {
                return Office::findOrFail($id);
            });
            
            return response()->json([
                'success' => true,
                'data' => $office
            ]);
        } catch (\Exception $e) {
            Log::error('Error mengambil detail kantor: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Kantor tidak ditemukan',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 404);
        }
    }
}
