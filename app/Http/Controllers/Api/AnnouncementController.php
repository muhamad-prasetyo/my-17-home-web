<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AnnouncementController extends Controller
{
    /**
     * Convert relative <img src> URLs in content to absolute URLs.
     */
    private function formatContent(string $content): string
    {
        // Convert relative src attributes to absolute URLs
        $content = preg_replace_callback(
            '/src="\/(.*?)"/i',
            function ($matches) {
                $src = $matches[1];
                if (Str::startsWith($src, ['http', 'data'])) {
                    return 'src="'.$src.'"';
                }
                return 'src="'.url($src).'"';
            },
            $content
        );
        // Remove <figure> and <figcaption> tags
        $content = preg_replace('/<\/?(?:figure|figcaption)[^>]*>/i', '', $content);
        // Convert attachment links ending with image extensions to inline <img> tags
        $content = preg_replace_callback(
            '/<a\s+(?:[^>]*?)href="([^\"]+\.(?:png|jpe?g|gif|svg)(?:\?[^\"]*)?)"[^>]*>.*?<\/a>/is',
            function ($matches) {
                $url = $matches[1];
                $urlEscaped = htmlspecialchars($url, ENT_QUOTES, 'UTF-8');
                return '<img src="'.$urlEscaped.'" />';
            },
            $content
        );
        return $content;
    }

    /**
     * List all announcements.
     */
    public function index()
    {
        $announcements = Announcement::orderBy('created_at', 'desc')->get();

        // Transform data to include banner_url and formatted timestamps
        $data = $announcements->map(function ($announcement) {
            return [
                'id'         => $announcement->id,
                'title'      => $announcement->title,
                'slug'       => $announcement->slug,
                'excerpt'    => $announcement->excerpt,
                'content'    => $this->formatContent($announcement->content),
                'banner_url' => $announcement->banner_path ? url('storage/' . $announcement->banner_path) : null,
                'created_at' => $announcement->created_at?->toDateTimeString(),
                'updated_at' => $announcement->updated_at?->toDateTimeString(),
            ];
        });

        return response()->json([
            'data' => $data,
        ]);
    }

    /**
     * Show details of a single announcement.
     */
    public function show($id)
    {
        $announcement = Announcement::findOrFail($id);

        return response()->json([
            'data' => [
                'id'         => $announcement->id,
                'title'      => $announcement->title,
                'slug'       => $announcement->slug,
                'excerpt'    => $announcement->excerpt,
                'content'    => $this->formatContent($announcement->content),
                'banner_url' => $announcement->banner_path ? url('storage/' . $announcement->banner_path) : null,
                'created_at' => $announcement->created_at?->toDateTimeString(),
                'updated_at' => $announcement->updated_at?->toDateTimeString(),
            ],
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
