<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\CallDisposition;
use App\Models\Campaign;
use App\Models\Company;
use App\Models\LeadSource;
use App\Models\LeadStatus;
use App\Models\Pipeline;
use App\Models\PipelineStage;
use App\Models\Product;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Roles and Permissions
        |--------------------------------------------------------------------------
        */

        $this->call(RolePermissionSeeder::class);

        /*
        |--------------------------------------------------------------------------
        | Create Company
        |--------------------------------------------------------------------------
        */

        $company = Company::firstOrCreate(
            [
                'code' => 'RVG',
            ],
            [
                'name'  => 'Real Victory Groups',
                'email' => 'admin@example.com',
                'phone' => '9876543210',
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | Create Branch
        |--------------------------------------------------------------------------
        */

        $branch = Branch::firstOrCreate(
            [
                'company_id' => $company->id,
                'code'       => 'KANPUR',
            ],
            [
                'name' => 'Kanpur Head Office',
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | Create Super Admin User
        |--------------------------------------------------------------------------
        |
        | Email: superadmin@example.com
        | Password: password
        |
        */

        $superAdmin = User::firstOrCreate(
            [
                'email' => 'superadmin@example.com',
            ],
            [
                'company_id'       => $company->id,
                'branch_id'        => $branch->id,
                'name'             => 'Super Admin',
                'phone'            => '9999999999',
                'employee_code'    => 'SUPER001',
                'password'         => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );

        $superAdmin->syncRoles(['super_admin']);

        /*
        |--------------------------------------------------------------------------
        | Create Company Owner User
        |--------------------------------------------------------------------------
        |
        | Email: admin@example.com
        | Password: password
        |
        */

        $companyOwner = User::firstOrCreate(
            [
                'email' => 'admin@example.com',
            ],
            [
                'company_id'       => $company->id,
                'branch_id'        => $branch->id,
                'name'             => 'CRM Admin',
                'phone'            => '9876543210',
                'employee_code'    => 'EMP001',
                'password'         => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );

        $companyOwner->syncRoles(['company_owner']);

        /*
        |--------------------------------------------------------------------------
        | Create Default Team
        |--------------------------------------------------------------------------
        */

        $team = Team::firstOrCreate(
            [
                'company_id' => $company->id,
                'name'       => 'Sales Team A',
            ],
            [
                'branch_id' => $branch->id,
                'leader_id' => $companyOwner->id,
            ]
        );

        $companyOwner->update([
            'team_id' => $team->id,
        ]);

        /*
        |--------------------------------------------------------------------------
        | Create Lead Sources
        |--------------------------------------------------------------------------
        */

        $leadSources = [
            'Manual Entry',
            'Excel Import',
            'Website',
            'Facebook',
            'Google Ads',
            'WhatsApp',
            'Referral',
            'IndiaMART',
            'Justdial',
        ];

        foreach ($leadSources as $source) {
            LeadSource::firstOrCreate([
                'company_id' => $company->id,
                'name'       => $source,
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Create Lead Statuses
        |--------------------------------------------------------------------------
        */

        $leadStatuses = [
            ['New', '#2563eb'],
            ['Assigned', '#7c3aed'],
            ['Attempted', '#64748b'],
            ['Connected', '#0891b2'],
            ['Follow-up Required', '#f59e0b'],
            ['Interested', '#16a34a'],
            ['Qualified', '#15803d'],
            ['Converted', '#059669'],
            ['Not Interested', '#dc2626'],
            ['Invalid Number', '#991b1b'],
            ['Lost', '#475569'],
        ];

        foreach ($leadStatuses as $index => $status) {
            $statusName = $status[0];
            $statusColor = $status[1];

            LeadStatus::firstOrCreate(
                [
                    'company_id' => $company->id,
                    'slug'       => str($statusName)->slug()->toString(),
                ],
                [
                    'name'         => $statusName,
                    'color'        => $statusColor,
                    'sort_order'   => $index,
                    'is_converted' => $statusName === 'Converted',
                    'is_lost'      => $statusName === 'Lost',
                ]
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Create Call Dispositions
        |--------------------------------------------------------------------------
        */

        $callDispositions = [
            ['Interested', 'connected', true],
            ['Follow-up', 'connected', true],
            ['Send Details', 'connected', false],
            ['Converted', 'connected', false],
            ['Not Interested', 'connected', false],
            ['No Answer', 'not_connected', true],
            ['Busy', 'not_connected', true],
            ['Switched Off', 'not_connected', true],
            ['Wrong Number', 'not_connected', false],
            ['Do Not Call', 'other', false],
        ];

        foreach ($callDispositions as $disposition) {
            CallDisposition::firstOrCreate(
                [
                    'company_id' => $company->id,
                    'name'       => $disposition[0],
                ],
                [
                    'type'               => $disposition[1],
                    'requires_follow_up' => $disposition[2],
                ]
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Create Default Pipeline
        |--------------------------------------------------------------------------
        */

        $pipeline = Pipeline::firstOrCreate(
            [
                'company_id' => $company->id,
                'name'       => 'Default Sales Pipeline',
            ],
            [
                'is_default' => true,
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | Create Pipeline Stages
        |--------------------------------------------------------------------------
        */

        $pipelineStages = [
            ['New Lead', '#2563eb', 10],
            ['Contacted', '#0891b2', 20],
            ['Interested', '#16a34a', 40],
            ['Qualified', '#15803d', 60],
            ['Demo / Meeting', '#7c3aed', 70],
            ['Quotation', '#d97706', 80],
            ['Negotiation', '#ea580c', 90],
            ['Won', '#059669', 100],
            ['Lost', '#dc2626', 0],
        ];

        foreach ($pipelineStages as $index => $stage) {
            $stageName = $stage[0];
            $stageColor = $stage[1];
            $probability = $stage[2];

            PipelineStage::firstOrCreate(
                [
                    'pipeline_id' => $pipeline->id,
                    'name'        => $stageName,
                ],
                [
                    'color'       => $stageColor,
                    'probability' => $probability,
                    'sort_order'  => $index,
                    'is_won'      => $stageName === 'Won',
                    'is_lost'     => $stageName === 'Lost',
                ]
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Create Default Product
        |--------------------------------------------------------------------------
        */

        Product::firstOrCreate(
            [
                'company_id' => $company->id,
                'code'       => 'CRM-001',
            ],
            [
                'name'        => 'Telecalling CRM',
                'base_price'  => 25000,
                'tax_percent' => 18,
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | Create Default Campaign
        |--------------------------------------------------------------------------
        */

        Campaign::firstOrCreate(
            [
                'company_id' => $company->id,
                'code'       => 'CMP001',
            ],
            [
                'name'   => 'Default Calling Campaign',
                'status' => 'active',
            ]
        );
    }
}
