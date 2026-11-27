<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index(Request $request)
{
    // $companyId = $request->user()->company_id;

    // abort_if(!$companyId, 403, 'Company not found for current user.');

    // $categories = \App\Models\Category::query()
    //     ->where('categories.company_id', $companyId)
    //     ->whereHas('leads', function ($query) use ($companyId) {
    //         $query->where('leads.company_id', $companyId)
    //             ->whereNull('leads.assigned_to');
    //     })
    //     ->get();

    // return response()->json($categories);
}
}
