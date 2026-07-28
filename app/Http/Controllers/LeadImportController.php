<?php

namespace App\Http\Controllers;

use App\Exports\LeadImportTemplateExport;
use App\Imports\LeadsImport;
use App\Models\LeadSource;
use App\Models\LeadStatus;
use App\Models\Pipeline;
use App\Models\PipelineStage;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class LeadImportController extends Controller
{
    /**
     * Show Excel import form.
     */
    public function create(Request $request): View {

        $companyId = (int) $request->user()->company_id;

        return view('leads.import', [
            'sources' => LeadSource::query()
                ->where(function (Builder $query) use ($companyId) {
                    $query
                        ->whereNull('company_id')
                        ->orWhere('company_id', $companyId);
                })
                ->orderBy('name')
                ->get(),

            'statuses' => LeadStatus::query()
                ->where(function (Builder $query) use ($companyId) {
                    $query
                        ->whereNull('company_id')
                        ->orWhere('company_id', $companyId);
                })
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(),

            'users' => User::query()
                ->where('company_id', $companyId)
                ->where('is_active', true)
                ->orderBy('name')
                ->get([
                    'id',
                    'name',
                    'employee_code',
                    'email',
                ]),

            'teams' => Team::query()
                ->where('company_id', $companyId)
                ->orderBy('name')
                ->get([
                    'id',
                    'name',
                ]),
        ]);
    }

    /**
     * Import leads.
     */
    public function store(
        Request $request
    ): RedirectResponse {
        $companyId = (int) $request->user()->company_id;

        $validated = $request->validate([
            'file' => [
                'required',
                'file',
                'mimes:xlsx,xls,csv',
                'max:10240',
            ],

            'duplicate_action' => [
                'required',
                Rule::in([
                    'skip',
                    'update',
                ]),
            ],

            'default_lead_source_id' => [
                'required',
                'integer',

                Rule::exists('lead_sources', 'id')
                    ->where(function ($query) use ($companyId) {
                        $query->where(
                            function ($subQuery) use ($companyId) {
                                $subQuery
                                    ->whereNull('company_id')
                                    ->orWhere(
                                        'company_id',
                                        $companyId
                                    );
                            }
                        );
                    }),
            ],

            'default_lead_status_id' => [
                'required',
                'integer',

                Rule::exists('lead_statuses', 'id')
                    ->where(function ($query) use ($companyId) {
                        $query->where(
                            function ($subQuery) use ($companyId) {
                                $subQuery
                                    ->whereNull('company_id')
                                    ->orWhere(
                                        'company_id',
                                        $companyId
                                    );
                            }
                        );
                    }),
            ],

            'default_assigned_to' => [
                'nullable',
                'integer',

                Rule::exists('users', 'id')
                    ->where(function ($query) use ($companyId) {
                        $query
                            ->where('company_id', $companyId)
                            ->where('is_active', true);
                    }),
            ],

            'default_team_id' => [
                'nullable',
                'integer',

                Rule::exists('teams', 'id')
                    ->where(
                        fn ($query) =>
                            $query->where(
                                'company_id',
                                $companyId
                            )
                    ),
            ],
        ]);

        $firstPipelineStageId =
            $this->defaultPipelineStageId($companyId);

        $import = new LeadsImport(
            companyId: $companyId,
            importedBy: (int) $request->user()->id,
            defaultSourceId:
                (int) $validated['default_lead_source_id'],
            defaultStatusId:
                (int) $validated['default_lead_status_id'],
            defaultAssignedTo:
                !empty($validated['default_assigned_to'])
                    ? (int) $validated['default_assigned_to']
                    : null,
            defaultTeamId:
                !empty($validated['default_team_id'])
                    ? (int) $validated['default_team_id']
                    : null,
            defaultPipelineStageId:
                $firstPipelineStageId,
            duplicateAction:
                $validated['duplicate_action']
        );

        Excel::import(
            $import,
            $request->file('file')
        );

        return redirect()
            ->route('leads.import.create')
            ->with('success', 'Lead import completed.')
            ->with('import_result', $import->result());
    }

    /**
     * Download Excel import template.
     */
    public function downloadTemplate(): BinaryFileResponse
    {
        return Excel::download(
            new LeadImportTemplateExport(),
            'lead-import-template.xlsx'
        );
    }

    /**
     * Find first stage of default pipeline.
     */
    private function defaultPipelineStageId(
        int $companyId
    ): ?int {
        $pipeline = Pipeline::query()
            ->where('company_id', $companyId)
            ->orderByDesc('is_default')
            ->orderBy('id')
            ->first();

        if (!$pipeline) {
            return null;
        }

        return PipelineStage::query()
            ->where('pipeline_id', $pipeline->id)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->value('id');
    }
}