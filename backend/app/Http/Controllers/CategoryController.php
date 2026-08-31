<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    /**
     * GET /api/categories
     * List semua kategori kampanye (untuk dropdown form & filter).
     */
    public function index()
    {
        $categories = Category::withCount('campaigns')->get();

        return response()->json($categories);
    }
}
