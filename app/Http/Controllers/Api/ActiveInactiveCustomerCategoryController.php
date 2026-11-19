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
        // Step 1: Validate request
        $validated = $request->validate([
            'status' => 'required|string|in:active,inactive',
            'mobile' => 'required|string|max:20',
            'name'   => 'nullable|string|max:255',
            'state'  => 'nullable|string|max:255',
            'city'   => 'nullable|string|max:255',
        ]);

        $user = User::first();


        // Company ID हमेशा 1 रहेगी
        $companyId = 1;

        // Step 2: Normalize mobile number
        $mobile = preg_replace(
            '/\D+/',
            '',
            $validated['mobile']
        );

        if (
            strlen($mobile) === 12 &&
            str_starts_with($mobile, '91')
        ) {
            $mobile = substr($mobile, 2);

        } elseif (
            strlen($mobile) === 11 &&
            str_starts_with($mobile, '0')
        ) {
            $mobile = substr($mobile, 1);
        }

        if (!preg_match('/^[6-9][0-9]{9}$/', $mobile)) {
            return response()->json([
                'success' => false,
                'message' => 'Valid 10 digit mobile number required.',
            ], 422);
        }

        // Step 3: Active / Inactive category find करें
        $categoryNames = $validated['status'] === 'active'
            ? ['active', 'Active Data']
            : ['inactive', 'Expire Data'];

        $category = Category::query()
            ->where('company_id', $companyId)
            ->where(function ($query) use ($categoryNames) {

                foreach ($categoryNames as $name) {
                    $query->orWhereRaw(
                        'LOWER(TRIM(name)) = ?',
                        [$name]
                    );
                }

            })
            ->orderByRaw(
                'CASE WHEN LOWER(TRIM(name)) = ? THEN 0 ELSE 1 END',
                [$categoryNames[0]]
            )
            ->first();


        if (!$category) {
            return response()->json([
                'success' => false,
                'message' => 'Category not found.',
                'requested_status' => $validated['status'],
                'expected_categories' => $categoryNames,
            ], 404);
        }

        // Step 4: Create or Update Lead
        $result = DB::transaction(function () use (
            $validated,
            $user,
            $companyId,
            $category,
            $mobile
        ) {

            // Mobile के सभी supported formats
            $possibleMobiles = [
                $mobile,
                '91' . $mobile,
                '+91' . $mobile,
                '0' . $mobile,
            ];

            // सिर्फ Company ID 1 में Lead Search
            $lead = Lead::query()
                ->where('company_id', $companyId)
                ->whereIn('mobile', $possibleMobiles)
                ->lockForUpdate()
                ->first();


            $isNew = !$lead;

            // Lead नहीं मिली तो नई बनाएँ
            if (!$lead) {
                $lead = new Lead();

                $lead->company_id = $companyId;
                $lead->created_by = $user->id;

                $lead->name = !empty($validated['name'])
                    ? trim($validated['name'])
                    : $mobile;
            }

            // Mobile हमेशा normalized save होगा
            $lead->mobile = $mobile;

            // Active / Inactive category update
            $lead->category_id = $category->id;

            // Name तभी update जब request में आया हो
            if (
                isset($validated['name']) &&
                trim($validated['name']) !== ''
            ) {
                $lead->name = trim($validated['name']);
            }

            // State तभी update जब request में दिया गया हो
            if (
                array_key_exists('state', $validated) &&
                $validated['state'] !== null
            ) {
                $lead->state = trim($validated['state']);
            }

            // City तभी update जब request में दिया गया हो
            if (
                array_key_exists('city', $validated) &&
                $validated['city'] !== null
            ) {
                $lead->city = trim($validated['city']);
            }

            // Existing assigned_to, call_status,
            // और call history में कोई बदलाव नहीं
            $lead->save();

            return [
                'lead' => $lead,
                'is_new' => $isNew,
            ];
        });

        $lead = $result['lead'];
        $isNew = $result['is_new'];

        // Step 5: Response
        return response()->json([
            'success' => true,

            'message' => $isNew
                ? 'Lead created successfully.'
                : 'Lead updated successfully.',

            'action' => $isNew ? 'created' : 'updated',

            'data' => [
                'id'            => $lead->id,
                'name'          => $lead->name,
                'mobile'        => $lead->mobile,
                'state'         => $lead->state,
                'city'          => $lead->city,
                'company_id'    => $lead->company_id,
                'category_id'   => $category->id,
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
