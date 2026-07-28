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
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
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
    private int $imported = 0;

    private int $updated = 0;

    private int $duplicates = 0;

    private int $failed = 0;

    private array $errors = [];

    public function __construct(
        private readonly int $companyId,
        private readonly int $importedBy,
        private readonly int $defaultSourceId,
        private readonly int $defaultStatusId,
        private readonly ?int $defaultAssignedTo,
        private readonly ?int $defaultTeamId,
        private readonly ?int $defaultPipelineStageId,
        private readonly string $duplicateAction = 'skip'
    ) {
    }

    /**
     * Process imported rows.
     */
    public function collection(
        Collection $rows
    ): void {
        foreach ($rows as $index => $row) {
            /*
            | Header row is row 1, so actual Excel row begins from 2.
            */

            $excelRowNumber = $index + 2;

            try {
                $this->processRow(
                    collect($row)->map(
                        fn ($value) =>
                            is_string($value)
                                ? trim($value)
                                : $value
                    ),
                    $excelRowNumber
                );
            } catch (Throwable $exception) {
                $this->failed++;

                $this->errors[] = [
                    'row' => $excelRowNumber,
                    'message' => $exception->getMessage(),
                ];
            }
        }
    }

    /**
     * Process one Excel row.
     */
    private function processRow(
        Collection $row,
        int $rowNumber
    ): void {
        $mobile = $this->normalizeMobile(
            $row->get('mobile')
        );

        $data = [
            'name' => $this->nullableString(
                $row->get('name')
            ),

            'mobile' => $mobile,

            'alternate_mobile' => $this->normalizeMobile(
                $row->get('alternate_mobile')
            ),

            'whatsapp_number' => $this->normalizeMobile(
                $row->get('whatsapp_number')
            ),

            'email' => $this->nullableString(
                $row->get('email')
            ),

            'company_name' => $this->nullableString(
                $row->get('company_name')
            ),

            'city' => $this->nullableString(
                $row->get('city')
            ),

            'district' => $this->nullableString(
                $row->get('district')
            ),

            'state' => $this->nullableString(
                $row->get('state')
            ),

            'pincode' => $this->nullableString(
                $row->get('pincode')
            ),

            'required_product' => $this->nullableString(
                $row->get('required_product')
            ),

            'estimated_budget' => $this->nullableNumeric(
                $row->get('estimated_budget')
            ),

            'expected_deal_value' => $this->nullableNumeric(
                $row->get('expected_deal_value')
            ),

            'expected_closing_date' => $this->parseDate(
                $row->get('expected_closing_date')
            ),

            'next_follow_up_at' => $this->parseDateTime(
                $row->get('next_follow_up_at')
            ),

            'priority' => $this->normalizeOption(
                $row->get('priority'),
                [
                    'low',
                    'normal',
                    'high',
                    'urgent',
                    'hot',
                ],
                'normal'
            ),

            'temperature' => $this->normalizeOption(
                $row->get('temperature'),
                [
                    'cold',
                    'warm',
                    'hot',
                ],
                'cold'
            ),

            'lead_source_id' => $this->resolveSourceId(
                $row->get('lead_source')
            ),

            'lead_status_id' => $this->resolveStatusId(
                $row->get('lead_status')
            ),

            'assigned_to' => $this->resolveUserId(
                $row->get('assigned_employee_email')
            ),

            'team_id' => $this->resolveTeamId(
                $row->get('team')
            ),

            'pipeline_stage_id' =>
                $this->defaultPipelineStageId,
        ];

        $validator = Validator::make(
            $data,
            [
                'name' => [
                    'required',
                    'string',
                    'max:255',
                ],

                'mobile' => [
                    'required',
                    'string',
                    'min:7',
                    'max:20',
                ],

                'email' => [
                    'nullable',
                    'email',
                    'max:255',
                ],

                'priority' => [
                    'required',
                    'in:low,normal,high,urgent,hot',
                ],

                'temperature' => [
                    'required',
                    'in:cold,warm,hot',
                ],

                'estimated_budget' => [
                    'nullable',
                    'numeric',
                    'min:0',
                ],

                'expected_deal_value' => [
                    'nullable',
                    'numeric',
                    'min:0',
                ],
            ]
        );

        if ($validator->fails()) {
            $this->failed++;

            $this->errors[] = [
                'row' => $rowNumber,
                'message' => implode(
                    ' ',
                    $validator->errors()->all()
                ),
            ];

            return;
        }

        $existingLead = Lead::withTrashed()
            ->where('company_id', $this->companyId)
            ->where('mobile', $mobile)
            ->first();

        /*
        |--------------------------------------------------------------------------
        | Duplicate handling
        |--------------------------------------------------------------------------
        */

        if ($existingLead) {
            if ($this->duplicateAction === 'skip') {
                $this->duplicates++;

                return;
            }

            DB::transaction(function () use (
                $existingLead,
                $data
            ) {
                if ($existingLead->trashed()) {
                    $existingLead->restore();
                }

                $previousUserId =
                    $existingLead->assigned_to;

                $existingLead->update($data);

                if (
                    !empty($data['assigned_to'])
                    && (int) $previousUserId !==
                    (int) $data['assigned_to']
                ) {
                    $this->saveAssignmentHistory(
                        lead: $existingLead,
                        previousUserId: $previousUserId
                            ? (int) $previousUserId
                            : null,
                        newUserId:
                            (int) $data['assigned_to'],
                        reason:
                            'Lead reassigned during Excel update'
                    );
                }
            });

            $this->updated++;

            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Create new lead
        |--------------------------------------------------------------------------
        */

        DB::transaction(function () use ($data) {
            $lead = Lead::create([
                ...$data,

                'company_id' => $this->companyId,
                'created_by' => $this->importedBy,
            ]);

            if (!empty($data['assigned_to'])) {
                $this->saveAssignmentHistory(
                    lead: $lead,
                    previousUserId: null,
                    newUserId:
                        (int) $data['assigned_to'],
                    reason:
                        'Lead assigned during Excel import'
                );
            }
        });

        $this->imported++;
    }

    /**
     * Resolve lead source from Excel text.
     */
    private function resolveSourceId(
        mixed $sourceName
    ): int {
        $sourceName = $this->nullableString(
            $sourceName
        );

        if (!$sourceName) {
            return $this->defaultSourceId;
        }

        $source = LeadSource::query()
            ->where(function ($query) {
                $query
                    ->whereNull('company_id')
                    ->orWhere(
                        'company_id',
                        $this->companyId
                    );
            })
            ->whereRaw(
                'LOWER(name) = ?',
                [Str::lower($sourceName)]
            )
            ->first();

        return $source?->id ??
            $this->defaultSourceId;
    }

    /**
     * Resolve lead status from Excel text.
     */
    private function resolveStatusId(
        mixed $statusName
    ): int {
        $statusName = $this->nullableString(
            $statusName
        );

        if (!$statusName) {
            return $this->defaultStatusId;
        }

        $status = LeadStatus::query()
            ->where(function ($query) {
                $query
                    ->whereNull('company_id')
                    ->orWhere(
                        'company_id',
                        $this->companyId
                    );
            })
            ->whereRaw(
                'LOWER(name) = ?',
                [Str::lower($statusName)]
            )
            ->first();

        return $status?->id ??
            $this->defaultStatusId;
    }

    /**
     * Resolve employee using email.
     */
    private function resolveUserId(
        mixed $email
    ): ?int {
        $email = $this->nullableString($email);

        if (!$email) {
            return $this->defaultAssignedTo;
        }

        return User::query()
            ->where('company_id', $this->companyId)
            ->where('is_active', true)
            ->whereRaw(
                'LOWER(email) = ?',
                [Str::lower($email)]
            )
            ->value('id')
            ?? $this->defaultAssignedTo;
    }

    /**
     * Resolve team using team name.
     */
    private function resolveTeamId(
        mixed $teamName
    ): ?int {
        $teamName = $this->nullableString(
            $teamName
        );

        if (!$teamName) {
            return $this->defaultTeamId;
        }

        return Team::query()
            ->where('company_id', $this->companyId)
            ->whereRaw(
                'LOWER(name) = ?',
                [Str::lower($teamName)]
            )
            ->value('id')
            ?? $this->defaultTeamId;
    }

    /**
     * Save assignment history.
     */
    private function saveAssignmentHistory(
        Lead $lead,
        ?int $previousUserId,
        int $newUserId,
        string $reason
    ): void {
        LeadAssignment::create([
            'company_id' => $this->companyId,
            'lead_id' => $lead->id,
            'previous_user_id' => $previousUserId,
            'new_user_id' => $newUserId,
            'assigned_by' => $this->importedBy,
            'reason' => $reason,
            'assigned_at' => now(),
        ]);
    }

    /**
     * Normalize mobile number.
     */
    private function normalizeMobile(
        mixed $value
    ): ?string {
        if ($value === null || $value === '') {
            return null;
        }

        $mobile = preg_replace(
            '/[^0-9+]/',
            '',
            (string) $value
        );

        /*
        | Excel numeric cell 9.8765E+9 form me aaye to
        | normal integer string banane ki कोशिश.
        */

        if (
            is_numeric($value)
            && str_contains(
                strtolower((string) $value),
                'e'
            )
        ) {
            $mobile = number_format(
                (float) $value,
                0,
                '',
                ''
            );
        }

        return $mobile ?: null;
    }

    /**
     * Parse Excel date.
     */
    private function parseDate(
        mixed $value
    ): ?string {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            if (is_numeric($value)) {
                return Carbon::instance(
                    ExcelDate::excelToDateTimeObject(
                        $value
                    )
                )->format('Y-m-d');
            }

            return Carbon::parse($value)
                ->format('Y-m-d');
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * Parse Excel datetime.
     */
    private function parseDateTime(
        mixed $value
    ): ?string {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            if (is_numeric($value)) {
                return Carbon::instance(
                    ExcelDate::excelToDateTimeObject(
                        $value
                    )
                )->format('Y-m-d H:i:s');
            }

            return Carbon::parse($value)
                ->format('Y-m-d H:i:s');
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * Convert value to nullable string.
     */
    private function nullableString(
        mixed $value
    ): ?string {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value === ''
            ? null
            : $value;
    }

    /**
     * Convert numeric value.
     */
    private function nullableNumeric(
        mixed $value
    ): int|float|null {
        if ($value === null || $value === '') {
            return null;
        }

        $clean = str_replace(
            [',', '₹', 'Rs.', 'Rs'],
            '',
            (string) $value
        );

        return is_numeric($clean)
            ? (float) $clean
            : null;
    }

    /**
     * Normalize dropdown option.
     */
    private function normalizeOption(
        mixed $value,
        array $allowed,
        string $default
    ): string {
        $value = Str::lower(
            trim((string) $value)
        );

        return in_array(
            $value,
            $allowed,
            true
        )
            ? $value
            : $default;
    }

    /**
     * Import chunk size.
     */
    public function chunkSize(): int
    {
        return 500;
    }

    /**
     * Return import summary.
     */
    public function result(): array
    {
        return [
            'imported' => $this->imported,
            'updated' => $this->updated,
            'duplicates' => $this->duplicates,
            'failed' => $this->failed,
            'errors' => array_slice(
                $this->errors,
                0,
                100
            ),
        ];
    }
}