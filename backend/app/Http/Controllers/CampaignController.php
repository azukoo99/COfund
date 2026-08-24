<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CampaignController extends Controller
{
    /**
     * GET /api/campaigns
     * List kampanye publik (default hanya yang aktif), dengan filter.
     */
    public function index(Request $request)
    {
        $query = Campaign::with(['category', 'creator'])
            ->withCount('backings');

        // Default: hanya tampilkan yang sudah live ke publik
        $status = $request->query('status', 'active');
        if ($status !== 'all') {
            $query->where('status', $status);
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->query('category_id'));
        }

        // Sorting: terbaru (default) atau terpopuler (paling banyak backer)
        $sort = $request->query('sort', 'latest');
        if ($sort === 'popular') {
            $query->orderByDesc('backings_count');
        } else {
            $query->latest();
        }

        $campaigns = $query->paginate(10);

        return response()->json($campaigns);
    }

    /**
     * GET /api/campaigns/{campaign:slug}
     * Detail kampanye publik.
     */
    public function show(Campaign $campaign)
    {
        $campaign->load(['category', 'creator', 'images', 'tiers', 'updates'])
            ->loadCount('backings');

        return response()->json($campaign);
    }

    /**
     * POST /api/campaigns
     * Creator membuat kampanye baru — status otomatis draft.
     */
    public function store(Request $request)
    {
        if ($request->user()->role !== 'creator') {
            return response()->json([
                'message' => 'Hanya akun dengan role creator yang bisa membuat kampanye.',
            ], 403);
        }
        $validated = $request->validate([
            'category_id' => ['required', 'exists:categories,id'],
            'title' => ['required', 'string', 'max:100'],
            'slug' => ['nullable', 'string', 'max:150', 'unique:campaigns,slug'],
            'description' => ['required', 'string'],
            'target_amount' => ['required', 'numeric', 'min:100000'],
            'deadline' => ['required', 'date', 'after_or_equal:' . now()->addDays(7)->toDateString()],
            'video_url' => ['nullable', 'url'],
        ]);

        $slug = $validated['slug'] ?? Str::slug($validated['title']);

        // Pastikan slug unik walau auto-generate (jaga-jaga ada judul sama)
        $originalSlug = $slug;
        $count = 1;
        while (Campaign::where('slug', $slug)->exists()) {
            $slug = $originalSlug . '-' . $count++;
        }

        $campaign = Campaign::create([
            'user_id' => $request->user()->id,
            'category_id' => $validated['category_id'],
            'title' => $validated['title'],
            'slug' => $slug,
            'description' => $validated['description'],
            'target_amount' => $validated['target_amount'],
            'deadline' => $validated['deadline'],
            'video_url' => $validated['video_url'] ?? null,
            'status' => 'draft',
        ]);

        return response()->json([
            'message' => 'Kampanye berhasil dibuat sebagai draft.',
            'campaign' => $campaign,
        ], 201);
    }

    /**
     * PUT/PATCH /api/campaigns/{campaign}
     * Hanya bisa diedit selama status masih draft, dan hanya oleh pemiliknya.
     */
    public function update(Request $request, Campaign $campaign)
    {
        if ($campaign->user_id !== $request->user()->id) {
            return response()->json([
                'message' => 'Anda tidak memiliki akses untuk mengedit kampanye ini.',
            ], 403);
        }

        if ($campaign->status !== 'draft') {
            return response()->json([
                'message' => 'Kampanye hanya bisa diedit saat status masih draft.',
            ], 422);
        }

        $validated = $request->validate([
            'category_id' => ['sometimes', 'required', 'exists:categories,id'],
            'title' => ['sometimes', 'required', 'string', 'max:100'],
            'slug' => ['sometimes', 'nullable', 'string', 'max:150', Rule::unique('campaigns', 'slug')->ignore($campaign->id)],
            'description' => ['sometimes', 'required', 'string'],
            'target_amount' => ['sometimes', 'required', 'numeric', 'min:100000'],
            'deadline' => ['sometimes', 'required', 'date', 'after_or_equal:' . now()->addDays(7)->toDateString()],
            'video_url' => ['nullable', 'url'],
        ]);

        $campaign->update($validated);

        return response()->json([
            'message' => 'Kampanye berhasil diperbarui.',
            'campaign' => $campaign,
        ]);
    }
}
