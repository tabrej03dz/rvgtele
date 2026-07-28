<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;

class LeadImportTemplateExport implements
    FromArray,
    WithHeadings,
    ShouldAutoSize
{
    public function headings(): array
    {
        return [
            'name',
            'mobile',
            'alternate_mobile',
            'whatsapp_number',
            'email',
            'company_name',
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

    public function array(): array
    {
        return [
            [
                'Ravi Jewellers',
                '9876543210',
                '',
                '9876543210',
                'ravi@example.com',
                'Ravi Jewellers Pvt Ltd',
                'Kanpur',
                'Kanpur Nagar',
                'Uttar Pradesh',
                '208001',
                'Telecalling CRM',
                '25000',
                '50000',
                '2026-08-30',
                '2026-07-30 16:00',
                'high',
                'warm',
                'Website',
                'New',
                'telecaller@example.com',
                'Sales Team A',
            ],
        ];
    }
}