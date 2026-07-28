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
        $companyId = (int) $request->user()->company_id;

        /*
        |--------------------------------------------------------------------------
        | Get company pipeline
        |--------------------------------------------------------------------------
        |
        | Default pipeline ko preference milegi.
        | Agar default pipeline nahi hai to first available pipeline load hogi.
        |
        */

        $pipeline = Pipeline::query()
            ->where('company_id', $companyId)
            ->orderByDesc('is_default')
            ->orderBy('id')
            ->first();

        /*
        |--------------------------------------------------------------------------
        | Pipeline nahi mili
        |--------------------------------------------------------------------------
        */

        if (!$pipeline) {
            return view('pipeline.index', [
                'pipeline' => null,
                'leads' => collect(),
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Load pipeline stages
        |--------------------------------------------------------------------------
        |
        | pipeline_stages table me bhi is_active column zaroori nahi hai,
        | isliye yahan is_active condition nahi lagayi gayi.
        |
        */

        $stages = PipelineStage::query()
            ->where('pipeline_id', $pipeline->id)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $pipeline->setRelation('stages', $stages);

        /*
        |--------------------------------------------------------------------------
        | Pipeline stages nahi mili
        |--------------------------------------------------------------------------
        */

        if ($stages->isEmpty()) {
            return view('pipeline.index', [
                'pipeline' => $pipeline,
                'leads' => collect(),
            ]);
        }

        $firstStage = $stages->first();

        /*
        |--------------------------------------------------------------------------
        | Existing null-stage leads ko first stage assign karein
        |--------------------------------------------------------------------------
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
        | Current pipeline stage IDs
        |--------------------------------------------------------------------------
        */

        $stageIds = $stages
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        /*
        |--------------------------------------------------------------------------
        | Load pipeline leads
        |--------------------------------------------------------------------------
        */

        $leads = Lead::query()
            ->with([
                'assignedUser:id,name,employee_code',
                'status:id,name,color',
                'source:id,name',
            ])
            ->where('company_id', $companyId)
            ->whereIn('pipeline_stage_id', $stageIds)
            ->latest('id')
            ->get()
            ->groupBy(function (Lead $lead) {
                return (int) $lead->pipeline_stage_id;
            });

        return view('pipeline.index', [
            'pipeline' => $pipeline,
            'leads' => $leads,
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