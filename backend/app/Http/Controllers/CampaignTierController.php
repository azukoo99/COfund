<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CampaignTier;
use App\Models\Campaign;

class CampaignTierController extends Controller
{
    /**
     * GET /api/campaigns/{campaign}/tiers
     */
    public function index(Campaign $campaign)
    {
        return response()->json($campaign->tiers);
    }

    /**
     * POST /api/campaigns/{campaign}/tiers
     * Hanya pemilik campaign, dan hanya saat status masih draft.
     */
    public function store(Request $request, Campaign $campaign)
    {
        if ($campaign->user_id !== $request->user()->id) {
            return response()->json([
                'message' => 'Anda tidak memiliki akses ke kampanye ini.',
            ], 403);
        }

        if ($campaign->status !== 'draft') {
            return response()->json([
                'message' => 'Tier hanya bisa ditambahkan saat kampanye masih draft.',
            ], 422);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'min_amount' => ['required', 'numeric', 'min:1'],
            'quota' => ['required', 'integer', 'min:0'], // 0 = unlimited
            'reward_description' => ['required', 'string'],
        ]);

        $tier = $campaign->tiers()->create([
            ...$validated,
            'remaining_quota' => $validated['quota'],
        ]);

        return response()->json([
            'message' => 'Tier berhasil ditambahkan.',
            'tier' => $tier,
        ], 201);
    }

    /**
     * PUT /api/campaigns/{campaign}/tiers/{tier}
     */
    public function update(Request $request, Campaign $campaign, CampaignTier $tier)
    {
        if ($campaign->user_id !== $request->user()->id) {
            return response()->json([
                'message' => 'Anda tidak memiliki akses ke kampanye ini.',
            ], 403);
        }

        if ($tier->campaign_id !== $campaign->id) {
            return response()->json(['message' => 'Tier tidak ditemukan di kampanye ini.'], 404);
        }

        if ($campaign->status !== 'draft') {
            return response()->json([
                'message' => 'Tier hanya bisa diedit saat kampanye masih draft.',
            ], 422);
        }

        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:100'],
            'min_amount' => ['sometimes', 'required', 'numeric', 'min:1'],
            'quota' => ['sometimes', 'required', 'integer', 'min:0'],
            'reward_description' => ['sometimes', 'required', 'string'],
        ]);

        // Kalau quota diubah, sesuaikan remaining_quota proporsional
        if (isset($validated['quota'])) {
            $used = $tier->quota - $tier->remaining_quota;
            $validated['remaining_quota'] = max(0, $validated['quota'] - $used);
        }

        $tier->update($validated);

        return response()->json([
            'message' => 'Tier berhasil diperbarui.',
            'tier' => $tier,
        ]);
    }

    /**
     * DELETE /api/campaigns/{campaign}/tiers/{tier}
     */
    public function destroy(Request $request, Campaign $campaign, CampaignTier $tier)
    {
        if ($campaign->user_id !== $request->user()->id) {
            return response()->json([
                'message' => 'Anda tidak memiliki akses ke kampanye ini.',
            ], 403);
        }

        if ($campaign->status !== 'draft') {
            return response()->json([
                'message' => 'Tier hanya bisa dihapus saat kampanye masih draft.',
            ], 422);
        }

        $tier->delete();

        return response()->json(['message' => 'Tier berhasil dihapus.']);
    }
}
