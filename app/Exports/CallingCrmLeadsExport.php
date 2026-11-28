<?php

namespace App\Exports;

use App\Support\DistributionValue;
use Generator;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromGenerator;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;
use PhpOffice\PhpSpreadsheet\Cell\StringValueBinder;

class CallingCrmLeadsExport extends StringValueBinder implements
    FromGenerator,
    WithCustomValueBinder,
    WithHeadings,
    WithStrictNullComparison
{
    public function __construct(private Builder $query)
    {
    }

    public function headings(): array
    {
        return [
            'name',
            'mobile',
            'alternate_mobile',
            'whatsapp_number',
            'email',
            'company_name',
            'category',
            'preferred_language',
            'address',
            'city',
            'district',
            'state',
            'pincode',
            'required_product',
            'estimated_budget',
            'expected_deal_value',
            'expected_closing_date',
            'next_follow_up_at',
            'priority',
            'temperature',
            'lead_source',
            'lead_status',
            'assigned_employee_email',
            'team',
        ];
    }

    public function generator(): Generator
    {
        $leads = (clone $this->query)
            ->with([
                'leadCategory:id,name',
                'source:id,name',
                'status:id,name',
                'assignedUser:id,email',
                'team:id,name',
            ])
            ->lazyById(500);

        foreach ($leads as $lead) {
            $mobile = DistributionValue::phone($lead->mobile);

            if ($mobile === null) {
                continue;
            }

            yield [
                trim((string) $lead->name) ?: "Lead {$lead->id}",
                $mobile,
                DistributionValue::phone($lead->alternate_mobile),
                DistributionValue::phone($lead->whatsapp_number),
                $lead->email,
                $lead->company_name,
                $lead->category ?: $lead->leadCategory?->name,
                $lead->preferred_language,
                $lead->address,
                $lead->city,
                $lead->district,
                $lead->state,
                $lead->pincode,
                $lead->required_product,
                $lead->estimated_budget,
                $lead->expected_deal_value,
                $lead->expected_closing_date?->format('Y-m-d'),
                $lead->next_follow_up_at?->format('Y-m-d H:i:s'),
                $lead->priority,
                $lead->temperature,
                $lead->source?->name,
                $lead->status?->name,
                $lead->assignedUser?->email,
                $lead->team?->name,
            ];
        }
    }
}