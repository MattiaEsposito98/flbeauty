<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource;
use App\Models\Category;

class CategoryController extends Controller
{
    public function index()
    {
        return CategoryResource::collection(
            Category::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get()
        );
    }
}
