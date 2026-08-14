<?php

namespace App\Imports;

use App\Models\Lead;
use App\Models\LeadAssignment;
use App\Models\LeadSource;
use App\Models\LeadStatus;
use App\Models\Team;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use Throwable;

class LeadsImport implements
    ToCollection,
    WithHeadingRow,
    WithChunkReading,
    SkipsEmptyRows
{
    /*
    |--------------------------------------------------------------------------
    | Counters
    |--------------------------------------------------------------------------
    */

    private int $imported = 0;

    private int $updated = 0;

    private int $duplicates = 0;

    private int $failed = 0;

    /*
    |--------------------------------------------------------------------------
    | Errors
    |--------------------------------------------------------------------------
    */

    private array $errors = [];

    /*
    |--------------------------------------------------------------------------
    | Constructor
    |--------------------------------------------------------------------------
    */

    public function __construct(
        private readonly int $companyId,

        private readonly int $importedBy,

        private readonly int $defaultSourceId,

        private readonly int $defaultStatusId,

        private readonly ?int $defaultAssignedTo = null,

        private readonly ?int $defaultTeamId = null,

        private readonly ?int $defaultPipelineStageId = null,

        private readonly string $duplicateAction = 'skip',
    ) {
    }

    /*
    |--------------------------------------------------------------------------
    | Collection
    |--------------------------------------------------------------------------
    */

    public function collection(
        Collection $rows
    ): void {
        foreach ($rows as $index => $row) {
            /*
            |--------------------------------------------------------------------------
            | Excel Row Number
            |--------------------------------------------------------------------------
            |
            | Heading row = row 1
            |
            */

            $rowNumber = $index + 2;

            try {
                $this->importRow(
                    $row,
                    $rowNumber
                );
            } catch (Throwable $e) {
                $this->failed++;

                $this->errors[] = [
                    'row' => $rowNumber,
                    'message' => $e->getMessage(),
                ];
            }
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Import Single Row
    |--------------------------------------------------------------------------
    */

    private function importRow(
        Collection $row,
        int $rowNumber
    ): void {
        /*
        |--------------------------------------------------------------------------
        | Name
        |--------------------------------------------------------------------------
        */

        $name = $this->clean(
            $row->get('name')
        );

        /*
        |--------------------------------------------------------------------------
        | Mobile
        |--------------------------------------------------------------------------
        */

        $mobile = $this->cleanPhone(
            $row->get('mobile')
        );

        if (!$name) {
            throw new \RuntimeException(
                'Name is required.'
            );
        }

        if (!$mobile) {
            throw new \RuntimeException(
                'Mobile is required.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Existing Lead
        |--------------------------------------------------------------------------
        */

        $existingLead = Lead::query()
            ->where(
                'company_id',
                $this->companyId
            )
            ->where(
                'mobile',
                $mobile
            )
            ->first();

        /*
        |--------------------------------------------------------------------------
        | Duplicate Skip
        |--------------------------------------------------------------------------
        */

        if (
            $existingLead
            &&
            $this->duplicateAction === 'skip'
        ) {
            $this->duplicates++;

            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Category
        |--------------------------------------------------------------------------
        |
        | Normal String
        |
        */

        $category = $this->clean(
            $row->get('category')
        );

        /*
        |--------------------------------------------------------------------------
        | Resolve Source
        |--------------------------------------------------------------------------
        */

        $sourceId =
            $this->resolveSourceId(
                $row->get('lead_source')
                    ?? $row->get('source')
            );

        /*
        |--------------------------------------------------------------------------
        | Resolve Status
        |--------------------------------------------------------------------------
        */

        $statusId =
            $this->resolveStatusId(
                $row->get('lead_status')
                    ?? $row->get('status')
            );

        /*
        |--------------------------------------------------------------------------
        | Resolve Employee
        |--------------------------------------------------------------------------
        */

        $assignedTo =
            $this->resolveAssignedTo(
                $row->get('assigned_employee_email')
                    ?? $row->get('assigned_to')
                    ?? $row->get('employee_email')
            );

        /*
        |--------------------------------------------------------------------------
        | Resolve Team
        |--------------------------------------------------------------------------
        */

        $teamId =
            $this->resolveTeamId(
                $row->get('team')
                    ?? $row->get('team_name')
            );

        /*
        |--------------------------------------------------------------------------
        | Priority
        |--------------------------------------------------------------------------
        */

        $priority =
            strtolower(
                $this->clean(
                    $row->get('priority')
                ) ?? 'normal'
            );

        if (
            !in_array(
                $priority,
                [
                    'low',
                    'normal',
                    'high',
                    'urgent',
                    'hot',
                ],
                true
            )
        ) {
            $priority = 'normal';
        }

        /*
        |--------------------------------------------------------------------------
        | Temperature
        |--------------------------------------------------------------------------
        */

        $temperature =
            strtolower(
                $this->clean(
                    $row->get('temperature')
                ) ?? 'cold'
            );

        if (
            !in_array(
                $temperature,
                [
                    'cold',
                    'warm',
                    'hot',
                ],
                true
            )
        ) {
            $temperature = 'cold';
        }

        /*
        |--------------------------------------------------------------------------
        | Lead Data
        |--------------------------------------------------------------------------
        */

        $data = [
            'company_id' =>
                $this->companyId,

            'name' =>
                $name,

            'mobile' =>
                $mobile,

            'alternate_mobile' =>
                $this->cleanPhone(
                    $row->get(
                        'alternate_mobile'
                    )
                ),

            'whatsapp_number' =>
                $this->cleanPhone(
                    $row->get(
                        'whatsapp_number'
                    )
                ),

            'email' =>
                $this->clean(
                    $row->get('email')
                ),

            'company_name' =>
                $this->clean(
                    $row->get(
                        'company_name'
                    )
                ),

            /*
            |--------------------------------------------------------------------------
            | CATEGORY
            |--------------------------------------------------------------------------
            */

            'category' =>
                $category,

            'preferred_language' =>
                $this->clean(
                    $row->get(
                        'preferred_language'
                    )
                ),

            'address' =>
                $this->clean(
                    $row->get('address')
                ),

            'city' =>
                $this->clean(
                    $row->get('city')
                ),

            'district' =>
                $this->clean(
                    $row->get('district')
                ),

            'state' =>
                $this->clean(
                    $row->get('state')
                ),

            'pincode' =>
                $this->clean(
                    $row->get('pincode')
                ),

            'required_product' =>
                $this->clean(
                    $row->get(
                        'required_product'
                    )
                ),

            'estimated_budget' =>
                $this->numericOrNull(
                    $row->get(
                        'estimated_budget'
                    )
                ),

            'expected_deal_value' =>
                $this->numericOrNull(
                    $row->get(
                        'expected_deal_value'
                    )
                ),

            'expected_closing_date' =>
                $this->dateOrNull(
                    $row->get(
                        'expected_closing_date'
                    )
                ),

            'next_follow_up_at' =>
                $this->dateTimeOrNull(
                    $row->get(
                        'next_follow_up_at'
                    )
                ),

            'lead_source_id' =>
                $sourceId,

            'lead_status_id' =>
                $statusId,

            'assigned_to' =>
                $assignedTo,

            'team_id' =>
                $teamId,

            'pipeline_stage_id' =>
                $this->defaultPipelineStageId,

            'priority' =>
                $priority,

            'temperature' =>
                $temperature,
        ];

        /*
        |--------------------------------------------------------------------------
        | Update Existing Lead
        |--------------------------------------------------------------------------
        */

        if (
            $existingLead
            &&
            $this->duplicateAction === 'update'
        ) {
            $oldAssignedTo =
                $existingLead->assigned_to;

            DB::transaction(
                function () use (
                    $existingLead,
                    $data,
                    $oldAssignedTo
                ) {
                    /*
                    |--------------------------------------------------------------------------
                    | Fill + Save
                    |--------------------------------------------------------------------------
                    */

                    $existingLead->fill(
                        $data
                    );

                    /*
                    |--------------------------------------------------------------------------
                    | Category Explicitly Set
                    |--------------------------------------------------------------------------
                    |
                    | Fillable me category miss ho tab bhi save ho.
                    |
                    */

                    $existingLead->category =
                        $data['category'];

                    $existingLead->save();

                    /*
                    |--------------------------------------------------------------------------
                    | Assignment History
                    |--------------------------------------------------------------------------
                    */

                    if (
                        !empty($data['assigned_to'])
                        &&
                        (int) $oldAssignedTo !==
                            (int) $data['assigned_to']
                    ) {
                        $this->createAssignmentHistory(
                            lead:
                                $existingLead,

                            previousUserId:
                                $oldAssignedTo
                                    ? (int) $oldAssignedTo
                                    : null,

                            newUserId:
                                (int) $data['assigned_to'],

                            reason:
                                'Lead reassigned during import'
                        );
                    }
                }
            );

            $this->updated++;

            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Create New Lead
        |--------------------------------------------------------------------------
        */

        DB::transaction(
            function () use ($data) {
                /*
                |--------------------------------------------------------------------------
                | Avoid Fillable Problem For Category
                |--------------------------------------------------------------------------
                */

                $lead =
                    new Lead();

                /*
                |--------------------------------------------------------------------------
                | Core Fields
                |--------------------------------------------------------------------------
                */

                $lead->company_id =
                    $data['company_id'];

                $lead->name =
                    $data['name'];

                $lead->mobile =
                    $data['mobile'];

                $lead->alternate_mobile =
                    $data['alternate_mobile'];

                $lead->whatsapp_number =
                    $data['whatsapp_number'];

                $lead->email =
                    $data['email'];

                $lead->company_name =
                    $data['company_name'];

                /*
                |--------------------------------------------------------------------------
                | CATEGORY
                |--------------------------------------------------------------------------
                */

                $lead->category =
                    $data['category'];

                /*
                |--------------------------------------------------------------------------
                | Other Fields
                |--------------------------------------------------------------------------
                */

                $lead->preferred_language =
                    $data['preferred_language'];

                $lead->address =
                    $data['address'];

                $lead->city =
                    $data['city'];

                $lead->district =
                    $data['district'];

                $lead->state =
                    $data['state'];

                $lead->pincode =
                    $data['pincode'];

                $lead->required_product =
                    $data['required_product'];

                $lead->estimated_budget =
                    $data['estimated_budget'];

                $lead->expected_deal_value =
                    $data['expected_deal_value'];

                $lead->expected_closing_date =
                    $data['expected_closing_date'];

                $lead->next_follow_up_at =
                    $data['next_follow_up_at'];

                $lead->lead_source_id =
                    $data['lead_source_id'];

                $lead->lead_status_id =
                    $data['lead_status_id'];

                $lead->assigned_to =
                    $data['assigned_to'];

                $lead->team_id =
                    $data['team_id'];

                $lead->pipeline_stage_id =
                    $data['pipeline_stage_id'];

                $lead->priority =
                    $data['priority'];

                $lead->temperature =
                    $data['temperature'];

                $lead->created_by =
                    $this->importedBy;

                /*
                |--------------------------------------------------------------------------
                | Save
                |--------------------------------------------------------------------------
                */

                $lead->save();

                /*
                |--------------------------------------------------------------------------
                | Assignment History
                |--------------------------------------------------------------------------
                */

                if (
                    !empty(
                        $data['assigned_to']
                    )
                ) {
                    $this->createAssignmentHistory(
                        lead:
                            $lead,

                        previousUserId:
                            null,

                        newUserId:
                            (int) $data['assigned_to'],

                        reason:
                            'Lead assigned during import'
                    );
                }
            }
        );

        $this->imported++;
    }

    /*
    |--------------------------------------------------------------------------
    | Resolve Source
    |--------------------------------------------------------------------------
    */

    private function resolveSourceId(
        mixed $value
    ): int {
        $value =
            $this->clean($value);

        if (!$value) {
            return $this->defaultSourceId;
        }

        /*
        |--------------------------------------------------------------------------
        | Numeric ID
        |--------------------------------------------------------------------------
        */

        if (
            ctype_digit(
                (string) $value
            )
        ) {
            $exists =
                LeadSource::query()
                ->whereKey(
                    (int) $value
                )
                ->where(
                    function ($query) {
                        $query
                            ->whereNull(
                                'company_id'
                            )
                            ->orWhere(
                                'company_id',
                                $this->companyId
                            );
                    }
                )
                ->exists();

            if ($exists) {
                return (int) $value;
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Name
        |--------------------------------------------------------------------------
        */

        $source =
            LeadSource::query()
            ->where(
                function ($query) {
                    $query
                        ->whereNull(
                            'company_id'
                        )
                        ->orWhere(
                            'company_id',
                            $this->companyId
                        );
                }
            )
            ->whereRaw(
                'LOWER(name) = ?',
                [
                    strtolower($value),
                ]
            )
            ->first();

        return $source?->id
            ? (int) $source->id
            : $this->defaultSourceId;
    }

    /*
    |--------------------------------------------------------------------------
    | Resolve Status
    |--------------------------------------------------------------------------
    */

    private function resolveStatusId(
        mixed $value
    ): int {
        $value =
            $this->clean($value);

        if (!$value) {
            return $this->defaultStatusId;
        }

        /*
        |--------------------------------------------------------------------------
        | Numeric ID
        |--------------------------------------------------------------------------
        */

        if (
            ctype_digit(
                (string) $value
            )
        ) {
            $exists =
                LeadStatus::query()
                ->whereKey(
                    (int) $value
                )
                ->where(
                    function ($query) {
                        $query
                            ->whereNull(
                                'company_id'
                            )
                            ->orWhere(
                                'company_id',
                                $this->companyId
                            );
                    }
                )
                ->exists();

            if ($exists) {
                return (int) $value;
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Name
        |--------------------------------------------------------------------------
        */

        $status =
            LeadStatus::query()
            ->where(
                function ($query) {
                    $query
                        ->whereNull(
                            'company_id'
                        )
                        ->orWhere(
                            'company_id',
                            $this->companyId
                        );
                }
            )
            ->whereRaw(
                'LOWER(name) = ?',
                [
                    strtolower($value),
                ]
            )
            ->first();

        return $status?->id
            ? (int) $status->id
            : $this->defaultStatusId;
    }

    /*
    |--------------------------------------------------------------------------
    | Resolve Employee
    |--------------------------------------------------------------------------
    */

    private function resolveAssignedTo(
        mixed $value
    ): ?int {
        $value =
            $this->clean($value);

        if (!$value) {
            return $this->defaultAssignedTo;
        }

        /*
        |--------------------------------------------------------------------------
        | Numeric User ID
        |--------------------------------------------------------------------------
        */

        if (
            ctype_digit(
                (string) $value
            )
        ) {
            $user =
                User::query()
                ->whereKey(
                    (int) $value
                )
                ->where(
                    'company_id',
                    $this->companyId
                )
                ->where(
                    'is_active',
                    true
                )
                ->first();

            if ($user) {
                return (int) $user->id;
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Email
        |--------------------------------------------------------------------------
        */

        $user =
            User::query()
            ->where(
                'company_id',
                $this->companyId
            )
            ->where(
                'is_active',
                true
            )
            ->whereRaw(
                'LOWER(email) = ?',
                [
                    strtolower($value),
                ]
            )
            ->first();

        return $user
            ? (int) $user->id
            : $this->defaultAssignedTo;
    }

    /*
    |--------------------------------------------------------------------------
    | Resolve Team
    |--------------------------------------------------------------------------
    */

    private function resolveTeamId(
        mixed $value
    ): ?int {
        $value =
            $this->clean($value);

        if (!$value) {
            return $this->defaultTeamId;
        }

        /*
        |--------------------------------------------------------------------------
        | Numeric ID
        |--------------------------------------------------------------------------
        */

        if (
            ctype_digit(
                (string) $value
            )
        ) {
            $team =
                Team::query()
                ->whereKey(
                    (int) $value
                )
                ->where(
                    'company_id',
                    $this->companyId
                )
                ->first();

            if ($team) {
                return (int) $team->id;
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Team Name
        |--------------------------------------------------------------------------
        */

        $team =
            Team::query()
            ->where(
                'company_id',
                $this->companyId
            )
            ->whereRaw(
                'LOWER(name) = ?',
                [
                    strtolower($value),
                ]
            )
            ->first();

        return $team
            ? (int) $team->id
            : $this->defaultTeamId;
    }

    /*
    |--------------------------------------------------------------------------
    | Assignment History
    |--------------------------------------------------------------------------
    */

    private function createAssignmentHistory(
        Lead $lead,
        ?int $previousUserId,
        int $newUserId,
        string $reason
    ): void {
        LeadAssignment::create([
            'company_id' =>
                $this->companyId,

            'lead_id' =>
                $lead->id,

            'previous_user_id' =>
                $previousUserId,

            'new_user_id' =>
                $newUserId,

            'assigned_by' =>
                $this->importedBy,

            'reason' =>
                $reason,

            'assigned_at' =>
                now(),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Clean Value
    |--------------------------------------------------------------------------
    */

    private function clean(
        mixed $value
    ): ?string {
        if (
            $value === null
            ||
            $value === ''
        ) {
            return null;
        }

        $value = trim(
            (string) $value
        );

        return $value !== ''
            ? $value
            : null;
    }

    /*
    |--------------------------------------------------------------------------
    | Phone
    |--------------------------------------------------------------------------
    */

    private function cleanPhone(
        mixed $value
    ): ?string {
        $value =
            $this->clean($value);

        if (!$value) {
            return null;
        }

        /*
        |--------------------------------------------------------------------------
        | Excel Numeric Scientific Notation Safety
        |--------------------------------------------------------------------------
        */

        if (
            is_numeric($value)
            &&
            str_contains(
                strtolower($value),
                'e'
            )
        ) {
            $value =
                sprintf(
                    '%.0f',
                    (float) $value
                );
        }

        /*
        |--------------------------------------------------------------------------
        | Remove unnecessary characters
        |--------------------------------------------------------------------------
        */

        $value =
            preg_replace(
                '/[^0-9+]/',
                '',
                $value
            );

        return $value ?: null;
    }

    /*
    |--------------------------------------------------------------------------
    | Numeric
    |--------------------------------------------------------------------------
    */

    private function numericOrNull(
        mixed $value
    ): ?float {
        if (
            $value === null
            ||
            $value === ''
        ) {
            return null;
        }

        $cleaned =
            str_replace(
                [
                    ',',
                    '₹',
                    'Rs.',
                    'Rs',
                ],
                '',
                (string) $value
            );

        $cleaned =
            trim($cleaned);

        return is_numeric(
            $cleaned
        )
            ? (float) $cleaned
            : null;
    }

    /*
    |--------------------------------------------------------------------------
    | Date
    |--------------------------------------------------------------------------
    */

    private function dateOrNull(
        mixed $value
    ): ?string {
        if (
            $value === null
            ||
            $value === ''
        ) {
            return null;
        }

        try {
            if (
                is_numeric($value)
            ) {
                return ExcelDate::excelToDateTimeObject(
                    $value
                )->format(
                    'Y-m-d'
                );
            }

            return Carbon::parse(
                $value
            )->format(
                'Y-m-d'
            );
        } catch (Throwable) {
            return null;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Date Time
    |--------------------------------------------------------------------------
    */

    private function dateTimeOrNull(
        mixed $value
    ): ?string {
        if (
            $value === null
            ||
            $value === ''
        ) {
            return null;
        }

        try {
            if (
                is_numeric($value)
            ) {
                return ExcelDate::excelToDateTimeObject(
                    $value
                )->format(
                    'Y-m-d H:i:s'
                );
            }

            return Carbon::parse(
                $value
            )->format(
                'Y-m-d H:i:s'
            );
        } catch (Throwable) {
            return null;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Chunk Size
    |--------------------------------------------------------------------------
    */

    public function chunkSize(): int
    {
        return 500;
    }

    /*
    |--------------------------------------------------------------------------
    | Import Result
    |--------------------------------------------------------------------------
    */

    public function result(): array
    {
        return [
            'imported' =>
                $this->imported,

            'updated' =>
                $this->updated,

            'duplicates' =>
                $this->duplicates,

            'failed' =>
                $this->failed,

            'errors' =>
                array_slice(
                    $this->errors,
                    0,
                    100
                ),
        ];
    }
}