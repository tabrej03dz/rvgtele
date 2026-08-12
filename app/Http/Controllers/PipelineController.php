<?php

namespace App\Http\Controllers;

use App\Models\Lead;
use App\Models\Pipeline;
use App\Models\PipelineStage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PipelineController extends Controller
{
    /**
     * Show company sales pipeline.
     */
   public function index(Request $request): View
{
    $user = $request->user();
    $companyId = (int) $user->company_id;

    /*
    |--------------------------------------------------------------------------
    | Get Company Pipeline
    |--------------------------------------------------------------------------
    */

    $pipeline = Pipeline::query()
        ->where('company_id', $companyId)
        ->orderByDesc('is_default')
        ->orderBy('id')
        ->first();

    if (!$pipeline) {
        return view('pipeline.index', [
            'pipeline' => null,
            'leads' => collect(),
            'paginatedLeads' => null,
            'employees' => collect(),
            'statuses' => collect(),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Load Pipeline Stages
    |--------------------------------------------------------------------------
    */

    $stages = PipelineStage::query()
        ->where('pipeline_id', $pipeline->id)
        ->orderBy('sort_order')
        ->orderBy('id')
        ->get();

    $pipeline->setRelation('stages', $stages);

    if ($stages->isEmpty()) {
        return view('pipeline.index', [
            'pipeline' => $pipeline,
            'leads' => collect(),
            'paginatedLeads' => null,
            'employees' => collect(),
            'statuses' => collect(),
        ]);
    }

    $firstStage = $stages->first();

    /*
    |--------------------------------------------------------------------------
    | Assign Null Stage Leads To First Stage
    |--------------------------------------------------------------------------
    |
    | NOTE:
    | Ideally ye migration/seeder/import ke time hona chahiye.
    | Har page load par UPDATE query avoid karna better hota hai.
    |
    */

    Lead::query()
        ->where('company_id', $companyId)
        ->whereNull('pipeline_stage_id')
        ->update([
            'pipeline_stage_id' => $firstStage->id,
            'updated_at' => now(),
        ]);

    /*
    |--------------------------------------------------------------------------
    | Current Pipeline Stage IDs
    |--------------------------------------------------------------------------
    */

    $stageIds = $stages
        ->pluck('id')
        ->map(fn ($id) => (int) $id)
        ->all();

    /*
    |--------------------------------------------------------------------------
    | Base Lead Query
    |--------------------------------------------------------------------------
    */

    $leadQuery = Lead::query()
        ->with([
            'assignedUser:id,name,employee_code',
            'status:id,name,color',
            'source:id,name',
        ])
        ->where('company_id', $companyId)
        ->whereIn('pipeline_stage_id', $stageIds);

    /*
    |--------------------------------------------------------------------------
    | Search Filter
    |--------------------------------------------------------------------------
    */

    if ($request->filled('search')) {
        $search = trim($request->search);

        $leadQuery->where(function ($query) use ($search) {
            $query->where('name', 'like', "%{$search}%")
                ->orWhere('company_name', 'like', "%{$search}%")
                ->orWhere('phone', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%");
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Stage Filter
    |--------------------------------------------------------------------------
    */

    if ($request->filled('stage')) {
        $stageId = (int) $request->stage;

        if (in_array($stageId, $stageIds, true)) {
            $leadQuery->where('pipeline_stage_id', $stageId);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Assigned User Filter
    |--------------------------------------------------------------------------
    */

    if ($request->filled('assigned_to')) {
        if ($request->assigned_to === 'unassigned') {
            $leadQuery->whereNull('assigned_to');
        } else {
            $leadQuery->where(
                'assigned_to',
                (int) $request->assigned_to
            );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Priority Filter
    |--------------------------------------------------------------------------
    */

    if ($request->filled('priority')) {
        $leadQuery->where('priority', $request->priority);
    }

    /*
    |--------------------------------------------------------------------------
    | Status Filter
    |--------------------------------------------------------------------------
    */

    if ($request->filled('status')) {
        $leadQuery->where('status_id', (int) $request->status);
    }

    /*
    |--------------------------------------------------------------------------
    | Sorting
    |--------------------------------------------------------------------------
    */

    switch ($request->get('sort')) {
        case 'oldest':
            $leadQuery->orderBy('id');
            break;

        case 'value_high':
            $leadQuery
                ->orderByDesc('expected_deal_value')
                ->orderByDesc('id');
            break;

        case 'value_low':
            $leadQuery
                ->orderBy('expected_deal_value')
                ->orderByDesc('id');
            break;

        case 'follow_up':
            $leadQuery
                ->orderByRaw('CASE WHEN next_follow_up_at IS NULL THEN 1 ELSE 0 END')
                ->orderBy('next_follow_up_at')
                ->orderByDesc('id');
            break;

        default:
            $leadQuery->orderByDesc('id');
            break;
    }

    /*
    |--------------------------------------------------------------------------
    | Pagination - Maximum 100 Leads Per Page
    |--------------------------------------------------------------------------
    */

    $paginatedLeads = $leadQuery
        ->paginate(100)
        ->withQueryString();

    /*
    |--------------------------------------------------------------------------
    | Group Current Page Leads By Stage
    |--------------------------------------------------------------------------
    */

    $leads = $paginatedLeads
        ->getCollection()
        ->groupBy(function (Lead $lead) {
            return (int) $lead->pipeline_stage_id;
        });

    /*
    |--------------------------------------------------------------------------
    | Employee Filter Data
    |--------------------------------------------------------------------------
    */

    $employees = \App\Models\User::query()
        ->where('company_id', $companyId)
        ->whereHas('assignedLeads', function ($query) use ($companyId) {
            $query->where('company_id', $companyId);
        })
        ->select('id', 'name', 'employee_code')
        ->orderBy('name')
        ->get();

    /*
    |--------------------------------------------------------------------------
    | Lead Status Filter Data
    |--------------------------------------------------------------------------
    */

    $statuses = \App\Models\LeadStatus::query()
        ->where('company_id', $companyId)
        ->orderBy('name')
        ->get(['id', 'name', 'color']);

    /*
    |--------------------------------------------------------------------------
    | Pipeline Overall Stats
    |--------------------------------------------------------------------------
    |
    | Ye pagination se independent hain.
    |
    */

    $statsQuery = Lead::query()
        ->where('company_id', $companyId)
        ->whereIn('pipeline_stage_id', $stageIds);

    $pipelineTotalLeads = (clone $statsQuery)->count();

    $pipelineTotalValue = (clone $statsQuery)
        ->sum('expected_deal_value');

    /*
    |--------------------------------------------------------------------------
    | Return View
    |--------------------------------------------------------------------------
    */

    return view('pipeline.index', [
        'pipeline' => $pipeline,
        'leads' => $leads,
        'paginatedLeads' => $paginatedLeads,
        'employees' => $employees,
        'statuses' => $statuses,
        'pipelineTotalLeads' => $pipelineTotalLeads,
        'pipelineTotalValue' => $pipelineTotalValue,
    ]);
}

    /**
     * Move lead from one stage to another.
     */
    public function move(
        Request $request,
        Lead $lead
    ): JsonResponse {
        $companyId = (int) $request->user()->company_id;

        /*
        |--------------------------------------------------------------------------
        | Lead company access check
        |--------------------------------------------------------------------------
        */

        abort_unless(
            (int) $lead->company_id === $companyId,
            403,
            'Unauthorized lead access.'
        );

        /*
        |--------------------------------------------------------------------------
        | Validate pipeline stage
        |--------------------------------------------------------------------------
        */

        $validated = $request->validate([
            'pipeline_stage_id' => [
                'required',
                'integer',

                Rule::exists('pipeline_stages', 'id')
                    ->where(function ($query) use ($companyId) {
                        $query->whereExists(
                            function ($pipelineQuery) use ($companyId) {
                                $pipelineQuery
                                    ->selectRaw('1')
                                    ->from('pipelines')
                                    ->whereColumn(
                                        'pipelines.id',
                                        'pipeline_stages.pipeline_id'
                                    )
                                    ->where(
                                        'pipelines.company_id',
                                        $companyId
                                    );
                            }
                        );
                    }),
            ],
        ]);

        /*
        |--------------------------------------------------------------------------
        | Get selected stage
        |--------------------------------------------------------------------------
        */

        $stage = PipelineStage::query()
            ->where('id', $validated['pipeline_stage_id'])
            ->whereHas('pipeline', function ($query) use ($companyId) {
                $query->where('company_id', $companyId);
            })
            ->firstOrFail();

        /*
        |--------------------------------------------------------------------------
        | Update lead stage
        |--------------------------------------------------------------------------
        */

        DB::transaction(function () use (
            $request,
            $lead,
            $stage
        ) {
            $oldStageId = $lead->pipeline_stage_id;

            $lead->update([
                'pipeline_stage_id' => $stage->id,
            ]);

            /*
            |--------------------------------------------------------------------------
            | Optional activity log
            |--------------------------------------------------------------------------
            */

            if (class_exists(\App\Models\ActivityLog::class)) {
                try {
                    \App\Models\ActivityLog::create([
                        'company_id' => $lead->company_id,
                        'user_id' => $request->user()->id,
                        'lead_id' => $lead->id,
                        'type' => 'pipeline_stage_changed',
                        'description' => 'Lead moved to pipeline stage: '
                            . $stage->name,
                        'properties' => [
                            'old_stage_id' => $oldStageId,
                            'new_stage_id' => $stage->id,
                        ],
                    ]);
                } catch (\Throwable $exception) {
                    report($exception);
                }
            }
        });

        return response()->json([
            'ok' => true,
            'message' => 'Lead moved successfully.',
            'lead_id' => $lead->id,
            'pipeline_stage_id' => $stage->id,
            'stage_name' => $stage->name,
        ]);
    }
}
