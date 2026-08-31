<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Models\CampaignUpdate;
use Illuminate\Http\Request;

class CampaignUpdateController extends Controller
{
    /**
     * GET /api/campaigns/{campaign}/updates
     * List update kampanye (publik).
     */
    public function index(Campaign $campaign)
    {
        return response()->json($campaign->updates()->latest()->get());
    }

    /**
     * POST /api/campaigns/{campaign}/updates
     * Creator memposting update kampanye yang sedang aktif.
     */
    public function store(Request $request, Campaign $campaign)
    {
        if ($campaign->user_id !== $request->user()->id) {
            return response()->json([
                'message' => 'Anda tidak memiliki akses ke kampanye ini.',
            ], 403);
        }

        if ($campaign->status !== 'active') {
            return response()->json([
                'message' => 'Update hanya bisa ditambahkan saat status kampanye aktif.',
            ], 422);
        }

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string'],
        ]);

        $update = $campaign->updates()->create($validated);

        // Notifikasi ke semua backer yang sudah completed (Modul 4.4)
        $backers = $campaign->backings()
            ->where('status', 'completed')
            ->with('user')
            ->get()
            ->pluck('user')
            ->filter()
            ->unique('id');

        foreach ($backers as $backer) {
            $backer->notifications()->create([
                'type' => 'campaign_update',
                'title' => 'Update Baru: ' . $update->title,
                'body' => "Creator kampanye \"{$campaign->title}\" telah memposting update baru.",
                'data' => [
                    'campaign_id' => $campaign->id,
                    'update_id' => $update->id,
                ],
            ]);
        }

        return response()->json([
            'message' => 'Update kampanye berhasil diposting dan notifikasi telah dikirim ke para backer.',
            'update' => $update,
        ], 201);
    }
}
