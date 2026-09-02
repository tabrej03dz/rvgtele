<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Models\User;
use App\Models\WhatsappMessageTemplate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WhatsappMessageTemplateController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | LIST TEMPLATES
    |--------------------------------------------------------------------------
    |
    | Normal User:
    | - apne personal templates
    | - company global templates
    |
    | view-all permission:
    | - sab allowed templates
    |
    */

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        abort_unless(
            $user->can('whatsapp-template.view-own') ||
            $user->can('whatsapp-template.view-all'),
            403,
            'You do not have permission to view WhatsApp templates.'
        );

        $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'type' => ['nullable', 'in:personal,global'],
            'status' => ['nullable', 'in:active,inactive'],
            'user_id' => ['nullable', 'integer'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $query = WhatsappMessageTemplate::query()
            ->with([
                'user:id,name,email,company_id',
                'company:id,name',
            ]);

        /*
        |--------------------------------------------------------------------------
        | Access
        |--------------------------------------------------------------------------
        */

        if (!$user->can('whatsapp-template.view-all')) {

            $query->where(function ($query) use ($user) {

                /*
                | Personal Templates
                */

                $query->where(function ($query) use ($user) {
                    $query
                        ->where('user_id', $user->id)
                        ->where('is_global', false);
                });

                /*
                | Global Templates
                */

                $query->orWhere(function ($query) use ($user) {

                    $query->where('is_global', true);

                    if ($user->company_id) {

                        $query->where(function ($query) use ($user) {
                            $query
                                ->where('company_id', $user->company_id)
                                ->orWhereNull('company_id');
                        });
                    }
                });
            });

        } else {

            /*
            |--------------------------------------------------------------------------
            | Admin / Manager with view-all
            |--------------------------------------------------------------------------
            */

            if (
                $user->company_id &&
                !$this->isSuperAdmin($user)
            ) {
                $query->where(function ($query) use ($user) {
                    $query
                        ->where('company_id', $user->company_id)
                        ->orWhereNull('company_id');
                });
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Search
        |--------------------------------------------------------------------------
        */

        if ($request->filled('search')) {

            $search = trim($request->search);

            $query->where(function ($query) use ($search) {
                $query
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('message', 'like', "%{$search}%");
            });
        }

        /*
        |--------------------------------------------------------------------------
        | User Filter
        |--------------------------------------------------------------------------
        */

        if (
            $request->filled('user_id') &&
            $user->can('whatsapp-template.view-all')
        ) {
            $query->where(
                'user_id',
                $request->integer('user_id')
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Type
        |--------------------------------------------------------------------------
        */

        if ($request->type === 'personal') {
            $query->where('is_global', false);
        }

        if ($request->type === 'global') {
            $query->where('is_global', true);
        }

        /*
        |--------------------------------------------------------------------------
        | Status
        |--------------------------------------------------------------------------
        */

        if ($request->status === 'active') {
            $query->where('is_active', true);
        }

        if ($request->status === 'inactive') {
            $query->where('is_active', false);
        }

        $perPage = $request->integer('per_page', 20);

        $templates = $query
            ->latest()
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'WhatsApp templates fetched successfully.',

            'data' => collect($templates->items())
                ->map(fn ($template) => $this->templateData($template))
                ->values(),

            'meta' => [
                'current_page' => $templates->currentPage(),
                'last_page' => $templates->lastPage(),
                'per_page' => $templates->perPage(),
                'total' => $templates->total(),
                'from' => $templates->firstItem(),
                'to' => $templates->lastItem(),
            ],
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | STORE
    |--------------------------------------------------------------------------
    */

    public function store(Request $request): JsonResponse
    {
        $user = $request->user();

        abort_unless(
            $user->can('whatsapp-template.create'),
            403,
            'You do not have permission to create WhatsApp templates.'
        );

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:150',
            ],

            'message' => [
                'required',
                'string',
                'max:10000',
            ],

            'is_global' => [
                'nullable',
                'boolean',
            ],

            'is_active' => [
                'nullable',
                'boolean',
            ],
        ]);

        $isGlobal = $request->boolean('is_global');

        /*
        |--------------------------------------------------------------------------
        | Global Permission
        |--------------------------------------------------------------------------
        */

        if (
            $isGlobal &&
            !$user->can('whatsapp-template.create-global')
        ) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to create global templates.',
            ], 403);
        }

        $template = WhatsappMessageTemplate::create([
            'company_id' => $user->company_id,
            'user_id' => $user->id,

            'name' => $validated['name'],
            'message' => $validated['message'],

            'is_global' => $isGlobal,

            'is_active' => $request->has('is_active')
                ? $request->boolean('is_active')
                : true,
        ]);

        $template->load([
            'user:id,name,email,company_id',
            'company:id,name',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'WhatsApp template created successfully.',
            'data' => $this->templateData($template),
        ], 201);
    }

    /*
    |--------------------------------------------------------------------------
    | SHOW
    |--------------------------------------------------------------------------
    */

    public function show(
        Request $request,
        WhatsappMessageTemplate $whatsappTemplate
    ): JsonResponse {

        $user = $request->user();

        $this->authorizeView(
            $user,
            $whatsappTemplate
        );

        $whatsappTemplate->load([
            'user:id,name,email,company_id',
            'company:id,name',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'WhatsApp template fetched successfully.',
            'data' => $this->templateData($whatsappTemplate),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | UPDATE
    |--------------------------------------------------------------------------
    */

    public function update(
        Request $request,
        WhatsappMessageTemplate $whatsappTemplate
    ): JsonResponse {

        $user = $request->user();

        $this->authorizeEdit(
            $user,
            $whatsappTemplate
        );

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:150',
            ],

            'message' => [
                'required',
                'string',
                'max:10000',
            ],

            'is_global' => [
                'nullable',
                'boolean',
            ],

            'is_active' => [
                'nullable',
                'boolean',
            ],
        ]);

        /*
        |--------------------------------------------------------------------------
        | Important:
        | Agar is_global API me nahi bheja gaya to existing value preserve hoga.
        |--------------------------------------------------------------------------
        */

        $isGlobal = $request->has('is_global')
            ? $request->boolean('is_global')
            : $whatsappTemplate->is_global;

        /*
        |--------------------------------------------------------------------------
        | Global banane ki permission
        |--------------------------------------------------------------------------
        */

        if (
            $isGlobal &&
            !$whatsappTemplate->is_global &&
            !$user->can('whatsapp-template.create-global')
        ) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to convert this template to global.',
            ], 403);
        }

        /*
        |--------------------------------------------------------------------------
        | Existing global ko edit karne ke liye edit-all chahiye,
        | except Super Admin.
        |--------------------------------------------------------------------------
        */

        if (
            $whatsappTemplate->is_global &&
            !$user->can('whatsapp-template.edit-all') &&
            !$this->isSuperAdmin($user)
        ) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to edit global templates.',
            ], 403);
        }

        $whatsappTemplate->update([
            'name' => $validated['name'],
            'message' => $validated['message'],
            'is_global' => $isGlobal,

            'is_active' => $request->has('is_active')
                ? $request->boolean('is_active')
                : $whatsappTemplate->is_active,
        ]);

        $whatsappTemplate->refresh();

        $whatsappTemplate->load([
            'user:id,name,email,company_id',
            'company:id,name',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'WhatsApp template updated successfully.',
            'data' => $this->templateData($whatsappTemplate),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | DELETE
    |--------------------------------------------------------------------------
    */

    public function destroy(
        Request $request,
        WhatsappMessageTemplate $whatsappTemplate
    ): JsonResponse {

        $user = $request->user();

        $this->authorizeDelete(
            $user,
            $whatsappTemplate
        );

        $whatsappTemplate->delete();

        return response()->json([
            'success' => true,
            'message' => 'WhatsApp template deleted successfully.',
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | SELECTABLE TEMPLATES FOR A LEAD
    |--------------------------------------------------------------------------
    |
    | Flutter me WhatsApp icon click karne par ye API call karo.
    |
    | User ko:
    | - apne personal templates
    | - company/global templates
    |
    | milenge.
    |
    */

    public function selectable(
        Request $request,
        Lead $lead
    ): JsonResponse {

        $user = $request->user();

        /*
        |--------------------------------------------------------------------------
        | Lead Access
        |--------------------------------------------------------------------------
        */

        $this->ensureLeadAccess(
            $user,
            $lead
        );

        $templates = WhatsappMessageTemplate::query()
            ->where('is_active', true)
            ->where(function ($query) use ($user) {

                /*
                |--------------------------------------------------------------------------
                | Personal
                |--------------------------------------------------------------------------
                */

                $query->where(function ($query) use ($user) {
                    $query
                        ->where('user_id', $user->id)
                        ->where('is_global', false);
                });

                /*
                |--------------------------------------------------------------------------
                | Global
                |--------------------------------------------------------------------------
                */

                $query->orWhere(function ($query) use ($user) {

                    $query->where('is_global', true);

                    if ($user->company_id) {

                        $query->where(function ($query) use ($user) {
                            $query
                                ->where('company_id', $user->company_id)
                                ->orWhereNull('company_id');
                        });
                    }
                });
            })
            ->orderByDesc('is_global')
            ->orderBy('name')
            ->get();

        $templateData = $templates->map(function ($template) use (
            $lead,
            $user
        ) {
            return [
                'id' => $template->id,
                'name' => $template->name,

                'type' => $template->is_global
                    ? 'global'
                    : 'personal',

                /*
                |--------------------------------------------------------------------------
                | Original Message
                |--------------------------------------------------------------------------
                */

                'original_message' => $template->message,

                /*
                |--------------------------------------------------------------------------
                | Lead variables already replaced
                |--------------------------------------------------------------------------
                */

                'rendered_message' => $this->replaceVariables(
                    $template->message,
                    $lead,
                    $user
                ),
            ];
        })->values();

        return response()->json([
            'success' => true,
            'message' => 'WhatsApp templates fetched successfully.',

            'data' => [
                'lead' => [
                    'id' => $lead->id,

                    'name' =>
                        $lead->name
                        ?? $lead->business_name
                        ?? 'Lead',

                    'business_name' =>
                        $lead->business_name
                        ?? $lead->name
                        ?? '',

                    'mobile' =>
                        $lead->mobile
                        ?? '',

                    'whatsapp_number' =>
                        $this->normalizeWhatsappNumber(
                            $lead->mobile ?? ''
                        ),

                    'city' =>
                        $lead->city
                        ?? '',

                    'category' =>
                        $lead->category
                        ?? '',
                ],

                'templates' => $templateData,

                'variables' => [
                    '{{name}}',
                    '{{business_name}}',
                    '{{mobile}}',
                    '{{city}}',
                    '{{category}}',
                    '{{user_name}}',
                    '{{employee_name}}',
                    '{{company_name}}',
                ],
            ],
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | RENDER CUSTOM MESSAGE
    |--------------------------------------------------------------------------
    |
    | Flutter me custom message likha ho aur usme {{name}} etc ho,
    | to ye API variables replace karke final message return karega.
    |
    */

    public function render(
        Request $request,
        Lead $lead
    ): JsonResponse {

        $user = $request->user();

        $this->ensureLeadAccess(
            $user,
            $lead
        );

        $validated = $request->validate([
            'message' => [
                'required',
                'string',
                'max:10000',
            ],
        ]);

        $rendered = $this->replaceVariables(
            $validated['message'],
            $lead,
            $user
        );

        return response()->json([
            'success' => true,
            'message' => 'Message rendered successfully.',

            'data' => [
                'lead_id' => $lead->id,

                'whatsapp_number' =>
                    $this->normalizeWhatsappNumber(
                        $lead->mobile ?? ''
                    ),

                'original_message' =>
                    $validated['message'],

                'rendered_message' =>
                    $rendered,

                /*
                |--------------------------------------------------------------------------
                | Direct WhatsApp URL optional
                |--------------------------------------------------------------------------
                */

                'whatsapp_url' =>
                    $this->buildWhatsappUrl(
                        $lead->mobile ?? '',
                        $rendered
                    ),
            ],
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | ADMIN USERS FOR FILTER
    |--------------------------------------------------------------------------
    |
    | Admin template page/app me user dropdown ke liye.
    |
    */

    public function users(Request $request): JsonResponse
    {
        $user = $request->user();

        abort_unless(
            $user->can('whatsapp-template.view-all'),
            403,
            'You do not have permission to view all template users.'
        );

        $query = User::query()
            ->orderBy('name');

        if (
            $user->company_id &&
            !$this->isSuperAdmin($user)
        ) {
            $query->where(
                'company_id',
                $user->company_id
            );
        }

        $users = $query
            ->get([
                'id',
                'name',
                'email',
                'company_id',
            ]);

        return response()->json([
            'success' => true,
            'message' => 'Users fetched successfully.',
            'data' => $users,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | TEMPLATE RESPONSE
    |--------------------------------------------------------------------------
    */

    private function templateData(
        WhatsappMessageTemplate $template
    ): array {

        return [
            'id' => $template->id,

            'company_id' =>
                $template->company_id,

            'user_id' =>
                $template->user_id,

            'name' =>
                $template->name,

            'message' =>
                $template->message,

            'type' =>
                $template->is_global
                    ? 'global'
                    : 'personal',

            'is_global' =>
                (bool) $template->is_global,

            'is_active' =>
                (bool) $template->is_active,

            'owner' => $template->user
                ? [
                    'id' => $template->user->id,
                    'name' => $template->user->name,
                    'email' => $template->user->email,
                ]
                : null,

            'company' => $template->company
                ? [
                    'id' => $template->company->id,
                    'name' => $template->company->name,
                ]
                : null,

            'created_at' =>
                optional($template->created_at)
                    ?->toISOString(),

            'updated_at' =>
                optional($template->updated_at)
                    ?->toISOString(),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | VARIABLE REPLACEMENT
    |--------------------------------------------------------------------------
    */

    private function replaceVariables(
        string $message,
        Lead $lead,
        User $user
    ): string {

        $variables = [

            '{{name}}' =>
                $lead->name
                ?? $lead->business_name
                ?? '',

            '{{business_name}}' =>
                $lead->business_name
                ?? $lead->name
                ?? '',

            '{{mobile}}' =>
                $lead->mobile
                ?? '',

            '{{city}}' =>
                $lead->city
                ?? '',

            '{{category}}' =>
                $lead->category
                ?? '',

            '{{user_name}}' =>
                $user->name
                ?? '',

            '{{employee_name}}' =>
                $user->name
                ?? '',

            '{{company_name}}' =>
                optional($user->company)->name
                ?? '',
        ];

        return str_replace(
            array_keys($variables),
            array_values($variables),
            $message
        );
    }

    /*
    |--------------------------------------------------------------------------
    | NORMALIZE NUMBER
    |--------------------------------------------------------------------------
    */

    private function normalizeWhatsappNumber(
        ?string $mobile
    ): string {

        $number = preg_replace(
            '/\D+/',
            '',
            (string) $mobile
        );

        /*
        |--------------------------------------------------------------------------
        | Indian 10 digit number
        |--------------------------------------------------------------------------
        */

        if (strlen($number) === 10) {
            return '91' . $number;
        }

        /*
        |--------------------------------------------------------------------------
        | 0XXXXXXXXXX
        |--------------------------------------------------------------------------
        */

        if (
            strlen($number) === 11 &&
            str_starts_with($number, '0')
        ) {
            return '91' . substr($number, 1);
        }

        return $number;
    }

    /*
    |--------------------------------------------------------------------------
    | WhatsApp URL
    |--------------------------------------------------------------------------
    */

    private function buildWhatsappUrl(
        ?string $mobile,
        string $message
    ): string {

        $number = $this->normalizeWhatsappNumber(
            $mobile
        );

        if (!$number) {
            return '';
        }

        return 'https://wa.me/' .
            $number .
            '?text=' .
            rawurlencode($message);
    }

    /*
    |--------------------------------------------------------------------------
    | AUTHORIZE VIEW
    |--------------------------------------------------------------------------
    */

    private function authorizeView(
        User $user,
        WhatsappMessageTemplate $template
    ): void {

        if ($this->isSuperAdmin($user)) {
            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Company restriction
        |--------------------------------------------------------------------------
        */

        $this->ensureCompanyAccess(
            $user,
            $template
        );

        /*
        |--------------------------------------------------------------------------
        | View All
        |--------------------------------------------------------------------------
        */

        if ($user->can('whatsapp-template.view-all')) {
            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Own
        |--------------------------------------------------------------------------
        */

        if (
            $user->can('whatsapp-template.view-own') &&
            (int) $template->user_id === (int) $user->id
        ) {
            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Global
        |--------------------------------------------------------------------------
        */

        if (
            $user->can('whatsapp-template.view-own') &&
            $template->is_global
        ) {
            return;
        }

        abort(
            403,
            'You do not have permission to view this template.'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | AUTHORIZE EDIT
    |--------------------------------------------------------------------------
    */

    private function authorizeEdit(
        User $user,
        WhatsappMessageTemplate $template
    ): void {

        if ($this->isSuperAdmin($user)) {
            return;
        }

        $this->ensureCompanyAccess(
            $user,
            $template
        );

        /*
        |--------------------------------------------------------------------------
        | Anyone with edit-all
        |--------------------------------------------------------------------------
        */

        if ($user->can('whatsapp-template.edit-all')) {
            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Own personal template only
        |--------------------------------------------------------------------------
        */

        if (
            (int) $template->user_id === (int) $user->id &&
            !$template->is_global &&
            $user->can('whatsapp-template.edit-own')
        ) {
            return;
        }

        abort(
            403,
            'You do not have permission to edit this template.'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | AUTHORIZE DELETE
    |--------------------------------------------------------------------------
    */

    private function authorizeDelete(
        User $user,
        WhatsappMessageTemplate $template
    ): void {

        if ($this->isSuperAdmin($user)) {
            return;
        }

        $this->ensureCompanyAccess(
            $user,
            $template
        );

        if ($user->can('whatsapp-template.delete-all')) {
            return;
        }

        if (
            (int) $template->user_id === (int) $user->id &&
            !$template->is_global &&
            $user->can('whatsapp-template.delete-own')
        ) {
            return;
        }

        abort(
            403,
            'You do not have permission to delete this template.'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | COMPANY ACCESS
    |--------------------------------------------------------------------------
    */

    private function ensureCompanyAccess(
        User $user,
        WhatsappMessageTemplate $template
    ): void {

        if ($this->isSuperAdmin($user)) {
            return;
        }

        if (!$user->company_id) {
            return;
        }

        /*
        |--------------------------------------------------------------------------
        | NULL company = system/global template
        |--------------------------------------------------------------------------
        */

        if (!$template->company_id) {
            return;
        }

        if (
            (int) $template->company_id !==
            (int) $user->company_id
        ) {
            abort(
                403,
                'You cannot access templates from another company.'
            );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | LEAD ACCESS
    |--------------------------------------------------------------------------
    */

    private function ensureLeadAccess(
        User $user,
        Lead $lead
    ): void {

        if ($this->isSuperAdmin($user)) {
            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Basic company protection
        |--------------------------------------------------------------------------
        */

        if (
            $user->company_id &&
            (int) $lead->company_id !==
            (int) $user->company_id
        ) {
            abort(
                403,
                'You cannot access this lead.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | IMPORTANT
        |--------------------------------------------------------------------------
        |
        | Agar aapke existing LeadController me assigned_to/team leader
        | visibility ka special logic hai to yahan bhi wahi access helper
        | reuse kar sakte ho.
        |
        */
    }

    /*
    |--------------------------------------------------------------------------
    | SUPER ADMIN
    |--------------------------------------------------------------------------
    */

    private function isSuperAdmin(
        User $user
    ): bool {

        return
            $user->hasRole('super-admin') ||
            $user->hasRole('super_admin') ||
            $user->hasRole('Super Admin');
    }
}
