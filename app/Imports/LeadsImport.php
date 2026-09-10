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

        private readonly int $defaultCategoryId,

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
            $this->field($row, 'name')
        );

        /*
        |--------------------------------------------------------------------------
        | Mobile
        |--------------------------------------------------------------------------
        */

        $mobile = $this->cleanPhone(
            $this->field($row, 'mobile')
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
            $this->field($row, 'category')
        );

        /*
        |--------------------------------------------------------------------------
        | Resolve Source
        |--------------------------------------------------------------------------
        */

        $sourceId =
            $this->resolveSourceId(
                $this->field($row, 'lead_source')
            );

        /*
        |--------------------------------------------------------------------------
        | Resolve Status
        |--------------------------------------------------------------------------
        */

        $statusId =
            $this->resolveStatusId(
                $this->field($row, 'lead_status')
            );

        /*
        |--------------------------------------------------------------------------
        | Resolve Employee
        |--------------------------------------------------------------------------
        */

        $assignedTo =
            $this->resolveAssignedTo(
                $this->field($row, 'assigned_to')
            );

        /*
        |--------------------------------------------------------------------------
        | Resolve Team
        |--------------------------------------------------------------------------
        */

        $teamId =
            $this->resolveTeamId(
                $this->field($row, 'team')
            );

        /*
        |--------------------------------------------------------------------------
        | Priority
        |--------------------------------------------------------------------------
        */

        $priority =
            strtolower(
                $this->clean(
                    $this->field($row, 'priority')
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
                    $this->field($row, 'temperature')
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
                    $this->field($row, 'alternate_mobile')
                ),

            'whatsapp_number' =>
                $this->cleanPhone(
                    $this->field($row, 'whatsapp_number')
                ),

            'email' =>
                $this->clean(
                    $this->field($row, 'email')
                ),

            'company_name' =>
                $this->clean(
                    $this->field($row, 'company_name')
                ),

            /*
            |--------------------------------------------------------------------------
            | CATEGORY
            |--------------------------------------------------------------------------
            */

            'category' =>
                $category,

            'category_id' =>
                $this->defaultCategoryId,

            'preferred_language' =>
                $this->clean(
                    $this->field($row, 'preferred_language')
                ),

            'address' =>
                $this->clean(
                    $this->field($row, 'address')
                ),

            'city' =>
                $this->clean(
                    $this->field($row, 'city')
                ),

            'district' =>
                $this->clean(
                    $this->field($row, 'district')
                ),

            'state' =>
                $this->clean(
                    $this->field($row, 'state')
                ),

            'pincode' =>
                $this->clean(
                    $this->field($row, 'pincode')
                ),

            'required_product' =>
                $this->clean(
                    $this->field($row, 'required_product')
                ),

            'estimated_budget' =>
                $this->numericOrNull(
                    $this->field($row, 'estimated_budget')
                ),

            'expected_deal_value' =>
                $this->numericOrNull(
                    $this->field($row, 'expected_deal_value')
                ),

            'expected_closing_date' =>
                $this->dateOrNull(
                    $this->field($row, 'expected_closing_date')
                ),

            'next_follow_up_at' =>
                $this->dateTimeOrNull(
                    $this->field($row, 'next_follow_up_at')
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

                    $existingLead->category_id =
                        $data['category_id'];

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

                $lead->category_id =
                    $data['category_id'];

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
    | Flexible Excel Heading Mapping
    |--------------------------------------------------------------------------
    |
    | Excel headings do not need to match strictly.
    | Example:
    |   Mobile, Mobile No, Mobile Number, Phone, Contact Number => mobile
    |   Name, Customer Name, Lead Name, Client Name             => name
    |
    */

    private const HEADING_ALIASES = [
        'name' => [
            'name',
            'lead name',
            'lead_name',
            'customer name',
            'customer_name',
            'client name',
            'client_name',
            'customer',
            'client',
            'person name',
            'contact person',
            'contact person name',
            'full name',
        ],

        'mobile' => [
            'mobile',
            'mobile no',
            'mobile number',
            'mobile_no',
            'mobile_number',
            'phone',
            'phone no',
            'phone number',
            'phone_no',
            'phone_number',
            'contact',
            'contact no',
            'contact number',
            'contact_no',
            'contact_number',
            'primary mobile',
            'primary phone',
            'primary contact',
            'telephone',
            'tel',
        ],

        'alternate_mobile' => [
            'alternate mobile',
            'alternate mobile no',
            'alternate mobile number',
            'alternate_mobile',
            'alternate_mobile_no',
            'alternate_mobile_number',
            'alternate phone',
            'alternate phone no',
            'alternate phone number',
            'alt mobile',
            'alt mobile no',
            'alt phone',
            'secondary mobile',
            'secondary phone',
            'other mobile',
            'other phone',
            'mobile 2',
            'phone 2',
        ],

        'whatsapp_number' => [
            'whatsapp',
            'whatsapp no',
            'whatsapp number',
            'whatsapp_no',
            'whatsapp_number',
            'whats app',
            'whats app no',
            'whats app number',
            'wa number',
            'wa no',
        ],

        'email' => [
            'email',
            'email id',
            'email_id',
            'email address',
            'email_address',
            'mail',
            'mail id',
            'e mail',
        ],

        'company_name' => [
            'company',
            'company name',
            'company_name',
            'business',
            'business name',
            'business_name',
            'firm',
            'firm name',
            'firm_name',
            'shop',
            'shop name',
            'shop_name',
            'organisation',
            'organization',
            'organisation name',
            'organization name',
            'store name',
            'establishment name',
        ],

        'category' => [
            'category',
            'category name',
            'category_name',
            'lead category',
            'lead_category',
            'business category',
            'business type',
            'type',
        ],

        'lead_source' => [
            'lead source',
            'lead_source',
            'source',
            'source name',
            'source_name',
            'lead source name',
            'lead_source_name',
        ],

        'lead_status' => [
            'lead status',
            'lead_status',
            'status',
            'status name',
            'status_name',
            'lead status name',
            'lead_status_name',
        ],

        'assigned_to' => [
            'assigned to',
            'assigned_to',
            'assigned employee',
            'assigned employee email',
            'assigned_employee',
            'assigned_employee_email',
            'employee',
            'employee email',
            'employee_email',
            'assignee',
            'assignee email',
            'user',
            'user email',
        ],

        'team' => [
            'team',
            'team name',
            'team_name',
            'assigned team',
            'assigned_team',
        ],

        'priority' => [
            'priority',
            'lead priority',
            'lead_priority',
        ],

        'temperature' => [
            'temperature',
            'lead temperature',
            'lead_temperature',
            'lead type',
        ],

        'preferred_language' => [
            'preferred language',
            'preferred_language',
            'language',
            'customer language',
            'lead language',
        ],

        'address' => [
            'address',
            'full address',
            'full_address',
            'business address',
            'office address',
            'shop address',
            'location address',
        ],

        'city' => [
            'city',
            'city name',
            'city_name',
            'town',
            'location',
            'place',
        ],

        'district' => [
            'district',
            'district name',
            'district_name',
            'dist',
        ],

        'state' => [
            'state',
            'state name',
            'state_name',
            'province',
        ],

        'pincode' => [
            'pincode',
            'pin code',
            'pin_code',
            'postal code',
            'postal_code',
            'zip',
            'zip code',
            'zipcode',
        ],

        'required_product' => [
            'required product',
            'required_product',
            'product',
            'product required',
            'interested product',
            'interest',
            'requirement',
        ],

        'estimated_budget' => [
            'estimated budget',
            'estimated_budget',
            'budget',
            'lead budget',
            'customer budget',
        ],

        'expected_deal_value' => [
            'expected deal value',
            'expected_deal_value',
            'deal value',
            'deal_value',
            'expected value',
            'value',
        ],

        'expected_closing_date' => [
            'expected closing date',
            'expected_closing_date',
            'closing date',
            'closing_date',
            'expected close date',
            'deal closing date',
        ],

        'next_follow_up_at' => [
            'next follow up at',
            'next_follow_up_at',
            'next follow up',
            'next_follow_up',
            'next followup',
            'next_followup',
            'follow up date',
            'followup date',
            'follow up',
            'followup',
        ],
    ];

    /**
     * Read an Excel value using flexible heading names.
     */
    private function field(
        Collection $row,
        string $field
    ): mixed {
        $aliases = self::HEADING_ALIASES[$field] ?? [$field];

        /*
        |--------------------------------------------------------------------------
        | Build normalized row only once for this call
        |--------------------------------------------------------------------------
        */

        $normalizedRow = [];

        foreach ($row as $heading => $value) {
            $normalizedHeading =
                $this->normalizeHeading(
                    (string) $heading
                );

            if ($normalizedHeading !== '') {
                $normalizedRow[$normalizedHeading] = $value;
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Exact normalized alias match
        |--------------------------------------------------------------------------
        |
        | "Mobile Number", "mobile_number", "MOBILE-NUMBER",
        | "mobile.number" => all normalize to "mobilenumber"
        |
        */

        foreach ($aliases as $alias) {
            $key =
                $this->normalizeHeading(
                    $alias
                );

            if (
                array_key_exists(
                    $key,
                    $normalizedRow
                )
            ) {
                $value = $normalizedRow[$key];

                if (
                    $value !== null
                    &&
                    trim((string) $value) !== ''
                ) {
                    return $value;
                }
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Safe relaxed match
        |--------------------------------------------------------------------------
        |
        | Allows headings such as:
        |   "Customer Mobile Number"
        |   "Lead Contact Number"
        |   "Business Company Name"
        |
        | Very short aliases are ignored here to avoid accidental matches.
        |
        */

        foreach ($aliases as $alias) {
            $aliasKey =
                $this->normalizeHeading(
                    $alias
                );

            if (strlen($aliasKey) < 5) {
                continue;
            }

            foreach ($normalizedRow as $headingKey => $value) {
                if (
                    (
                        str_contains(
                            $headingKey,
                            $aliasKey
                        )
                        ||
                        str_contains(
                            $aliasKey,
                            $headingKey
                        )
                    )
                    &&
                    $value !== null
                    &&
                    trim((string) $value) !== ''
                ) {
                    return $value;
                }
            }
        }

        return null;
    }

    /**
     * Normalize Excel heading for comparison.
     *
     * Examples:
     * Mobile Number  => mobilenumber
     * mobile_number  => mobilenumber
     * MOBILE-NO.     => mobileno
     */
    private function normalizeHeading(
        string $heading
    ): string {
        $heading =
            mb_strtolower(
                trim($heading)
            );

        return preg_replace(
            '/[^a-z0-9]+/u',
            '',
            $heading
        ) ?? '';
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