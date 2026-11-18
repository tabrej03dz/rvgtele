<?php

namespace App\Observers;

use App\Models\Lead;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DistributionLeadObserver
{
    public function updating(Lead $lead): void
    {
        if (!$lead->getOriginal('distribution_indexed')) {
            return;
        }

        $protected = [
            'assigned_to',
            'call_status',
            'mobile',
            'company_id',
            'category_id',
            'state',
            'city',
            'distribution_phone',
        ];

        if (!$lead->isDirty($protected)) {
            return;
        }

        $managed = DB::table('distribution_numbers')
            ->where('company_id', $lead->getOriginal('company_id'))
            ->where('phone', $lead->getOriginal('distribution_phone'))
            ->exists();

        if ($managed) {
            throw ValidationException::withMessages([
                'lead' => 'This lead is managed by automatic distribution. Use its call-result API for outcomes.',
            ]);
        }
    }
}