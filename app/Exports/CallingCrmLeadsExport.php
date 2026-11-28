<?php

namespace App\Exports;

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
    private array $columns;

    public function __construct(private Builder $query)
    {
        $lead = $query->getModel();

        $this->columns = $lead->getConnection()
            ->getSchemaBuilder()
            ->getColumnListing($lead->getTable());
    }

    public function headings(): array
    {
        return [
            ...$this->columns,
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
                'source:id,name',
                'status:id,name',
                'assignedUser:id,email',
                'team:id,name',
            ])
            ->lazyById(500);

        foreach ($leads as $lead) {
            $attributes = $lead->getRawOriginal();
            $row = [];

            foreach ($this->columns as $column) {
                $value = $attributes[$column] ?? null;
                $row[] = is_bool($value) ? (int) $value : $value;
            }

            $row[] = $lead->source?->name;
            $row[] = $lead->status?->name;
            $row[] = $lead->assignedUser?->email;
            $row[] = $lead->team?->name;

            yield $row;
        }
    }
}