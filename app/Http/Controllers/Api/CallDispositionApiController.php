<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CallDisposition;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CallDispositionApiController extends Controller
{
    /**
     * सभी accessible call dispositions.
     *
     * Default: केवल active dispositions
     *
     * active=all : active और inactive दोनों
     * active=1   : केवल active
     * active=0   : केवल inactive
     */
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'search' => [
                'nullable',
                'string',
                'max:255',
            ],

            'active' => [
                'nullable',
                Rule::in([
                    'all',
                    '1',
                    '0',
                    1,
                    0,
                ]),
            ],

            'type' => [
                'nullable',
                'string',
                'max:100',
            ],
        ]);

        $user = $request->user();
        $companyId = (int) $user->company_id;

        /*
        |--------------------------------------------------------------------------
        | Base Query
        |--------------------------------------------------------------------------
        |
        | Global dispositions:
        | company_id = NULL
        |
        | Company dispositions:
        | company_id = logged-in user company
        |
        */

        $query = CallDisposition::query()
            ->where(function (Builder $builder) use ($companyId) {
                $builder
                    ->whereNull('company_id')
                    ->orWhere('company_id', $companyId);
            });

        /*
        |--------------------------------------------------------------------------
        | Active Filter
        |--------------------------------------------------------------------------
        */

        $active = (string) ($validated['active'] ?? '1');

        if ($active !== 'all') {
            $query->where(
                'is_active',
                $active === '1'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Type Filter
        |--------------------------------------------------------------------------
        */

        if (!empty($validated['type'])) {
            $query->where(
                'type',
                $validated['type']
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Search
        |--------------------------------------------------------------------------
        */

        if (!empty($validated['search'])) {
            $search = trim(
                (string) $validated['search']
            );

            $query->where(
                function (Builder $builder) use ($search) {
                    $builder
                        ->where(
                            'name',
                            'like',
                            "%{$search}%"
                        )
                        ->orWhere(
                            'type',
                            'like',
                            "%{$search}%"
                        )
                        ->orWhere(
                            'auto_remarks',
                            'like',
                            "%{$search}%"
                        );
                }
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Fetch Dispositions
        |--------------------------------------------------------------------------
        */

        $dispositions = $query
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->get()
            ->map(function (CallDisposition $disposition) {
                return $this->formatDisposition(
                    $disposition
                );
            })
            ->values();

        return response()->json([
            'status' => true,
            'message' =>
                'Call dispositions fetched successfully.',

            'count' =>
                $dispositions->count(),

            'data' =>
                $dispositions,
        ]);
    }

    /**
     * Single call disposition details.
     */
    public function show(
        Request $request,
        CallDisposition $callDisposition
    ): JsonResponse {
        $user = $request->user();
        $companyId = (int) $user->company_id;

        /*
        |--------------------------------------------------------------------------
        | Company Security
        |--------------------------------------------------------------------------
        |
        | User केवल:
        | 1. Global disposition
        | 2. अपनी company का disposition
        |
        | देख सकता है।
        |
        */

        abort_unless(
            $callDisposition->company_id === null
            || (int) $callDisposition->company_id === $companyId,
            403,
            'You are not allowed to access this call disposition.'
        );

        return response()->json([
            'status' => true,
            'message' =>
                'Call disposition fetched successfully.',

            'data' =>
                $this->formatDisposition(
                    $callDisposition
                ),
        ]);
    }

    /**
     * Flutter के लिए clean disposition response.
     */
    private function formatDisposition(
        CallDisposition $disposition
    ): array {
        $nextFollowUpMinutes = $disposition->next_followup !== null
            ? (int) $disposition->next_followup
            : null;

        return [
            'id' =>
                (int) $disposition->id,

            'company_id' =>
                $disposition->company_id !== null
                    ? (int) $disposition->company_id
                    : null,

            'is_global' =>
                $disposition->company_id === null,

            'name' =>
                $disposition->name,

            'type' =>
                $disposition->type,

            'requires_remarks' =>
                (bool) $disposition->requires_remarks,

            'requires_follow_up' =>
                (bool) $disposition->requires_follow_up,

            'auto_remarks' =>
                $disposition->auto_remarks,

            /*
             * next_followup database में minutes में है।
             */
            'next_followup' =>
                $nextFollowUpMinutes,

            'next_followup_minutes' =>
                $nextFollowUpMinutes,

            'next_followup_unit' =>
                $nextFollowUpMinutes !== null
                    ? 'minutes'
                    : null,

            /*
             * Flutter चाहे तो इस suggested date को
             * follow-up field में सीधे दिखा सकता है।
             */
            'suggested_follow_up_at' =>
                $nextFollowUpMinutes !== null
                    ? now()
                        ->copy()
                        ->addMinutes($nextFollowUpMinutes)
                        ->toIso8601String()
                    : null,

            'is_active' =>
                (bool) $disposition->is_active,

            'created_at' =>
                $disposition->created_at?->toIso8601String(),

            'updated_at' =>
                $disposition->updated_at?->toIso8601String(),
        ];
    }
}
