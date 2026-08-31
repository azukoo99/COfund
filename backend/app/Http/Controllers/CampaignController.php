<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Models\CampaignImage;
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
        $query = Campaign::with(['category', 'creator', 'images'])
            ->withCount('backings');

        // Default: hanya tampilkan yang sudah live ke publik
        $status = $request->query('status', 'active');
        if ($status !== 'all') {
            $query->where('status', $status);
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->query('category_id'));
        }

        if ($request->filled('search')) {
            $search = $request->query('search');
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
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
     * GET /api/campaigns/{campaign}
     * Detail kampanye publik.
     */
    public function show(Campaign $campaign)
    {
        $campaign->load([
            'category',
            'creator:id,name,email',
            'images',
            'tiers',
            'updates' => fn($q) => $q->latest(),
            'backings' => fn($q) => $q->where('status', 'completed')->with('backer:id,name')->latest(),
        ])->loadCount('backings');

        // Tambahan info kalkulasi progress & sisa hari
        $percentage = $campaign->target_amount > 0
            ? round(($campaign->collected_amount / $campaign->target_amount) * 100, 2)
            : 0;

        $daysLeft = max(0, (int) now()->startOfDay()->diffInDays(\Carbon\Carbon::parse($campaign->deadline)->startOfDay(), false));

        $campaignData = $campaign->toArray();
        $campaignData['collected_percentage'] = $percentage;
        $campaignData['days_left'] = $daysLeft;

        return response()->json($campaignData);
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
            'images' => ['nullable', 'array', 'min:1', 'max:5'],
            'images.*' => ['nullable'], // bisa berupa uploaded file atau url string
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

        // Simpan foto kampanye jika ada
        if ($request->hasFile('images')) {
            $isPrimary = true;
            foreach ($request->file('images') as $imageFile) {
                $path = $imageFile->store('campaigns', 'public');
                $campaign->images()->create([
                    'url' => '/storage/' . $path,
                    'is_primary' => $isPrimary,
                ]);
                $isPrimary = false;
            }
        } elseif (!empty($validated['images']) && is_array($validated['images'])) {
            $isPrimary = true;
            foreach ($validated['images'] as $imgUrl) {
                if (is_string($imgUrl)) {
                    $campaign->images()->create([
                        'url' => $imgUrl,
                        'is_primary' => $isPrimary,
                    ]);
                    $isPrimary = false;
                }
            }
        }

        return response()->json([
            'message' => 'Kampanye berhasil dibuat sebagai draft.',
            'campaign' => $campaign->load('images'),
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
            'campaign' => $campaign->load('images'),
        ]);
    }

    /**
     * POST /api/campaigns/{campaign}/images
     * Upload foto tambahan ke kampanye (maksimal 5 foto).
     */
    public function uploadImages(Request $request, Campaign $campaign)
    {
        if ($campaign->user_id !== $request->user()->id) {
            return response()->json([
                'message' => 'Anda tidak memiliki akses ke kampanye ini.',
            ], 403);
        }

        if ($campaign->status !== 'draft') {
            return response()->json([
                'message' => 'Foto kampanye hanya bisa diubah saat status masih draft.',
            ], 422);
        }

        $currentCount = $campaign->images()->count();
        if ($currentCount >= 5) {
            return response()->json([
                'message' => 'Maksimal 5 foto per kampanye sudah tercapai.',
            ], 422);
        }

        $request->validate([
            'images' => ['required', 'array', 'min:1', 'max:' . (5 - $currentCount)],
            'images.*' => ['required'],
        ]);

        if ($request->hasFile('images')) {
            $isPrimary = $currentCount === 0;
            foreach ($request->file('images') as $imageFile) {
                $path = $imageFile->store('campaigns', 'public');
                $campaign->images()->create([
                    'url' => '/storage/' . $path,
                    'is_primary' => $isPrimary,
                ]);
                $isPrimary = false;
            }
        } elseif (is_array($request->input('images'))) {
            $isPrimary = $currentCount === 0;
            foreach ($request->input('images') as $imgUrl) {
                if (is_string($imgUrl)) {
                    $campaign->images()->create([
                        'url' => $imgUrl,
                        'is_primary' => $isPrimary,
                    ]);
                    $isPrimary = false;
                }
            }
        }

        return response()->json([
            'message' => 'Foto kampanye berhasil ditambahkan.',
            'images' => $campaign->images()->get(),
        ], 201);
    }

    /**
     * DELETE /api/campaigns/{campaign}/images/{image}
     * Hapus satu foto dari kampanye (hanya saat status masih draft).
     */
    public function deleteImage(Request $request, Campaign $campaign, CampaignImage $image)
    {
        if ($campaign->user_id !== $request->user()->id) {
            return response()->json([
                'message' => 'Anda tidak memiliki akses ke kampanye ini.',
            ], 403);
        }

        if ($campaign->status !== 'draft') {
            return response()->json([
                'message' => 'Foto kampanye hanya bisa dihapus saat status masih draft.',
            ], 422);
        }

        if ($image->campaign_id !== $campaign->id) {
            return response()->json([
                'message' => 'Foto ini bukan bagian dari kampanye yang dipilih.',
            ], 404);
        }

        $wasPrimary = $image->is_primary;

        // Hapus file fisik jika disimpan di storage lokal
        if (\Illuminate\Support\Str::startsWith($image->url, '/storage/')) {
            $filePath = str_replace('/storage/', '', $image->url);
            \Illuminate\Support\Facades\Storage::disk('public')->delete($filePath);
        }

        $image->delete();

        // Jika foto yang dihapus adalah foto utama, jadikan foto pertama yang tersisa sebagai foto utama baru
        if ($wasPrimary) {
            $nextImage = $campaign->images()->first();
            if ($nextImage) {
                $nextImage->update(['is_primary' => true]);
            }
        }

        return response()->json([
            'message' => 'Foto berhasil dihapus.',
            'remaining_images' => $campaign->images()->get(),
        ]);
    }

    /**
     * PATCH /api/campaigns/{campaign}/images/{image}/primary
     * Atur foto tertentu sebagai foto utama (thumbnail) kampanye.
     */
    public function setPrimaryImage(Request $request, Campaign $campaign, CampaignImage $image)
    {
        if ($campaign->user_id !== $request->user()->id) {
            return response()->json([
                'message' => 'Anda tidak memiliki akses ke kampanye ini.',
            ], 403);
        }

        if ($campaign->status !== 'draft') {
            return response()->json([
                'message' => 'Foto utama hanya bisa diubah saat status masih draft.',
            ], 422);
        }

        if ($image->campaign_id !== $campaign->id) {
            return response()->json([
                'message' => 'Foto ini bukan bagian dari kampanye yang dipilih.',
            ], 404);
        }

        // Reset semua foto menjadi non-primary
        $campaign->images()->update(['is_primary' => false]);

        // Set foto ini sebagai primary
        $image->update(['is_primary' => true]);

        return response()->json([
            'message' => 'Foto utama berhasil diperbarui.',
            'images' => $campaign->images()->get(),
        ]);
    }

    /**
     * POST /api/campaigns/{campaign}/submit-review
     * Creator mengajukan kampanye draft ke admin untuk ditinjau.
     */
    public function submitReview(Request $request, Campaign $campaign)
    {
        if ($campaign->user_id !== $request->user()->id) {
            return response()->json([
                'message' => 'Anda tidak memiliki akses ke kampanye ini.',
            ], 403);
        }

        if ($campaign->status !== 'draft') {
            return response()->json([
                'message' => 'Hanya kampanye berstatus draft yang bisa diajukan untuk review.',
            ], 422);
        }

        // Syarat Bisnis: Wajib minimal 1 tier reward
        if ($campaign->tiers()->count() < 1) {
            return response()->json([
                'message' => 'Kampanye wajib memiliki minimal 1 tier reward sebelum diajukan untuk review.',
            ], 422);
        }

        // Syarat Bisnis: Wajib minimal 1 foto kampanye
        if ($campaign->images()->count() < 1) {
            return response()->json([
                'message' => 'Kampanye wajib memiliki minimal 1 foto kampanye sebelum diajukan untuk review.',
            ], 422);
        }

        $campaign->update([
            'status' => 'review',
            'rejection_note' => null,
        ]);

        return response()->json([
            'message' => 'Kampanye berhasil diajukan untuk review. Menunggu persetujuan admin.',
            'campaign' => $campaign,
        ]);
    }

    /**
     * DELETE /api/campaigns/{campaign}
     * Creator menghapus kampanye (hanya saat status masih draft - Rule 9).
     */
    public function destroy(Request $request, Campaign $campaign)
    {
        if ($campaign->user_id !== $request->user()->id) {
            return response()->json([
                'message' => 'Anda tidak memiliki akses untuk menghapus kampanye ini.',
            ], 403);
        }

        if ($campaign->status !== 'draft') {
            return response()->json([
                'message' => 'Kampanye tidak bisa dihapus setelah status bukan draft.',
            ], 422);
        }

        $campaign->delete();

        return response()->json([
            'message' => 'Kampanye draft berhasil dihapus.',
        ]);
    }
}

