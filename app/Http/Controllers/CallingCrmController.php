<?php

namespace App\Http\Controllers;

use App\Models\Lead;
use Illuminate\Http\Request;

class CallingCrmController extends Controller
{
    
    public function index(Request $request)
    {
        $user = $request->user();

        $search = trim((string) $request->query('search', ''));
        $category = $request->query('category');
        $status = $request->query('status');

        /*
        |--------------------------------------------------------------------------
        | Base Query
        |--------------------------------------------------------------------------
        | Sirf logged-in user ki assigned leads.
        | Pending status wali leads nahi aayengi.
        | NULL status bhi exclude rahega.
        */

        $baseQuery = Lead::query()
            ->where('assigned_to', $user->id)
            ->whereNotNull('call_status')
            ->where('call_status', '!=', 'pending');

        /*
        |--------------------------------------------------------------------------
        | Category Filter Options
        |--------------------------------------------------------------------------
        */

        $categories = (clone $baseQuery)
            ->whereNotNull('category')
            ->where('category', '!=', '')
            ->distinct()
            ->orderBy('category')
            ->pluck('category');

        /*
        |--------------------------------------------------------------------------
        | Call Status Filter Options
        |--------------------------------------------------------------------------
        */

        $statuses = (clone $baseQuery)
            ->distinct()
            ->orderBy('call_status')
            ->pluck('call_status');

        /*
        |--------------------------------------------------------------------------
        | Leads With Complete Call History
        |--------------------------------------------------------------------------
        */

        $contacts = (clone $baseQuery)

            ->when($search !== '', function ($query) use ($search) {

                $query->where(function ($q) use ($search) {

                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('phone', 'like', "%{$search}%");

                    if (ctype_digit($search)) {
                        $q->orWhere('id', (int) $search);
                    }

                });

            })

            ->when(
                $category !== null && $category !== '',
                function ($query) use ($category) {
                    $query->where('category', $category);
                }
            )

            ->when(
                $status !== null && $status !== '',
                function ($query) use ($status) {
                    $query->where('call_status', $status);
                }
            )

            // Complete call history
            ->with([
                'callHistories' => function ($query) {
                    $query->orderByDesc('call_at')
                          ->orderByDesc('id');
                }
            ])

            // Total call history count
            ->withCount('callHistories')

            // Existing updated_at column
            ->orderByDesc('updated_at')
            ->orderByDesc('id')

            ->paginate(20)
            ->withQueryString();

        return view('calling-crm.index', [
            'title' => 'Calling CRM',
            'contacts' => $contacts,
            'categories' => $categories,
            'statuses' => $statuses,
        ]);
    }

}
