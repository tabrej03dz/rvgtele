<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Models\Category;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ActiveInactiveCustomerCategoryController extends Controller
{
    public function activeInactiveCustomerCategory(Request $request)
{
    $validated = $request->validate([
        'status' => 'required|string|in:active,inactive',
        'mobile' => 'required|string|max:20',
        'name'   => 'nullable|string|max:255',
        'state'  => 'nullable|string|max:255',
        'city'   => 'nullable|string|max:255',
    ]);

    $companyId = 1;
    $mobile = preg_replace('/\D+/', '', $validated['mobile']);

    if (strlen($mobile) === 12 && str_starts_with($mobile, '91')) {
        $mobile = substr($mobile, 2);
    } elseif (strlen($mobile) === 11 && str_starts_with($mobile, '0')) {
        $mobile = substr($mobile, 1);
    }

    if (!preg_match('/^[6-9][0-9]{9}$/', $mobile)) {
        return response()->json([
            'success' => false,
            'message' => 'Valid 10 digit mobile number required.',
        ], 422);
    }

    $categoryName = $validated['status'] === 'active'
        ? 'Active Data'
        : 'Expire Data';

    $category = Category::query()
        ->where('company_id', $companyId)
        ->whereRaw('LOWER(TRIM(name)) = ?', [strtolower($categoryName)])
        ->first();

    if (!$category) {
        return response()->json([
            'success'             => false,
            'message'             => 'Category not found.',
            'requested_status'    => $validated['status'],
            'expected_categories' => [$categoryName],
        ], 404);
    }

    [$lead, $isNew] = DB::transaction(function () use (
        $validated,
        $companyId,
        $category,
        $mobile
    ) {
        $lead = Lead::query()
            ->where('company_id', $companyId)
            ->whereIn('mobile', [
                $mobile,
                '91' . $mobile,
                '+91' . $mobile,
                '0' . $mobile,
            ])
            ->lockForUpdate()
            ->first();

        $isNew = $lead === null;
        $name = trim($validated['name'] ?? '');

        if ($isNew) {
            $lead = new Lead();
            $lead->company_id = $companyId;
            $lead->created_by = User::query()->firstOrFail()->id;
            $lead->name = $mobile;
        }

        if ($name !== '') {
            $lead->name = $name;
        }

        $lead->mobile = $mobile;
        $lead->category_id = $category->id;

        if (isset($validated['state'])) {
            $lead->state = trim($validated['state']);
        }

        if (isset($validated['city'])) {
            $lead->city = trim($validated['city']);
        }

        $lead->save();

        return [$lead, $isNew];
    });

    return response()->json([
        'success' => true,
        'message' => $isNew
            ? 'Lead created successfully.'
            : 'Lead updated successfully.',
        'action' => $isNew ? 'created' : 'updated',
        'data'   => [
            'id'            => $lead->id,
            'name'          => $lead->name,
            'mobile'        => $lead->mobile,
            'state'         => $lead->state,
            'city'          => $lead->city,
            'company_id'    => $lead->company_id,
            'category_id'   => $lead->category_id,
            'category_name' => $category->name,
            'status'        => $validated['status'],
            'assigned_to'   => $lead->assigned_to,
            'call_status'   => $lead->call_status,
            'created_at'    => $lead->created_at,
            'updated_at'    => $lead->updated_at,
        ],
    ], $isNew ? 201 : 200);
}
}
