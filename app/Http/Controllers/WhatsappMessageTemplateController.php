<?php

namespace App\Http\Controllers;

use App\Models\Lead;
use App\Models\User;
use App\Models\WhatsappMessageTemplate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class WhatsappMessageTemplateController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | INDEX
    |--------------------------------------------------------------------------
    */

    public function index(Request $request): View
    {
        $user = $request->user();

        abort_unless(
            $user->can('whatsapp-template.view-own') ||
            $user->can('whatsapp-template.view-all'),
            403
        );

        $query = WhatsappMessageTemplate::query()
            ->with([
                'user:id,name,email',
                'company:id,name',
            ]);

        /*
        |--------------------------------------------------------------------------
        | User Access
        |--------------------------------------------------------------------------
        */

        if (!$user->can('whatsapp-template.view-all')) {

            $query->where(function ($query) use ($user) {

                /*
                | User ka khud ka personal template
                */

                $query->where(function ($query) use ($user) {
                    $query
                        ->where('user_id', $user->id)
                        ->where('is_global', false);
                });

                /*
                | OR company/global templates
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
            | View All Permission
            |--------------------------------------------------------------------------
            |
            | Company users ko normally apni company ke templates hi.
            | Super Admin jiska company_id NULL hai usko sab templates.
            |
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
            $query->where('user_id', $request->integer('user_id'));
        }

        /*
        |--------------------------------------------------------------------------
        | Type Filter
        |--------------------------------------------------------------------------
        */

        if ($request->type === 'global') {
            $query->where('is_global', true);
        }

        if ($request->type === 'personal') {
            $query->where('is_global', false);
        }

        /*
        |--------------------------------------------------------------------------
        | Status Filter
        |--------------------------------------------------------------------------
        */

        if ($request->status === 'active') {
            $query->where('is_active', true);
        }

        if ($request->status === 'inactive') {
            $query->where('is_active', false);
        }

        $templates = $query
            ->latest()
            ->paginate(20)
            ->withQueryString();

        /*
        |--------------------------------------------------------------------------
        | Users For Admin Filter
        |--------------------------------------------------------------------------
        */

        $users = collect();

        if ($user->can('whatsapp-template.view-all')) {

            $usersQuery = User::query()
                ->orderBy('name');

            if (
                $user->company_id &&
                !$this->isSuperAdmin($user)
            ) {
                $usersQuery->where('company_id', $user->company_id);
            }

            $users = $usersQuery
                ->get([
                    'id',
                    'name',
                    'email',
                    'company_id',
                ]);
        }

        return view('whatsapp_templates.index', [
            'templates' => $templates,
            'users' => $users,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | CREATE
    |--------------------------------------------------------------------------
    */

    public function create(Request $request): View
    {
        abort_unless(
            $request->user()->can('whatsapp-template.create'),
            403
        );

        return view('whatsapp_templates.create');
    }

    /*
    |--------------------------------------------------------------------------
    | STORE
    |--------------------------------------------------------------------------
    */

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();

        abort_unless(
            $user->can('whatsapp-template.create'),
            403
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
            abort(403, 'You cannot create global WhatsApp templates.');
        }

        WhatsappMessageTemplate::create([
            'company_id' => $user->company_id,
            'user_id' => $user->id,

            'name' => $validated['name'],
            'message' => $validated['message'],

            'is_global' => $isGlobal,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()
            ->route('whatsapp-templates.index')
            ->with('success', 'WhatsApp message template created successfully.');
    }

    /*
    |--------------------------------------------------------------------------
    | EDIT
    |--------------------------------------------------------------------------
    */

    public function edit(
        Request $request,
        WhatsappMessageTemplate $whatsappTemplate
    ): View {

        $this->authorizeTemplateEdit(
            $request,
            $whatsappTemplate
        );

        return view('whatsapp_templates.edit', [
            'template' => $whatsappTemplate,
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
    ): RedirectResponse {

        $this->authorizeTemplateEdit(
            $request,
            $whatsappTemplate
        );

        $user = $request->user();

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
        | Global Permission Check
        |--------------------------------------------------------------------------
        */

        if (
            $isGlobal &&
            !$user->can('whatsapp-template.create-global')
        ) {

            /*
            | Agar template pahle se global hai aur user global edit permission
            | nahi rakhta tab bhi global nahi bana sakta.
            */

            abort(
                403,
                'You cannot create or convert templates to global templates.'
            );
        }

        $whatsappTemplate->update([
            'name' => $validated['name'],
            'message' => $validated['message'],

            'is_global' => $isGlobal,
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()
            ->route('whatsapp-templates.index')
            ->with('success', 'WhatsApp message template updated successfully.');
    }

    /*
    |--------------------------------------------------------------------------
    | DELETE
    |--------------------------------------------------------------------------
    */

    public function destroy(
        Request $request,
        WhatsappMessageTemplate $whatsappTemplate
    ): RedirectResponse {

        $user = $request->user();

        $isOwner =
            (int) $whatsappTemplate->user_id ===
            (int) $user->id;

        if ($isOwner) {

            abort_unless(
                $user->can('whatsapp-template.delete-own') ||
                $user->can('whatsapp-template.delete-all'),
                403
            );

        } else {

            abort_unless(
                $user->can('whatsapp-template.delete-all'),
                403
            );
        }

        $this->ensureCompanyAccess(
            $user,
            $whatsappTemplate
        );

        $whatsappTemplate->delete();

        return redirect()
            ->route('whatsapp-templates.index')
            ->with('success', 'WhatsApp message template deleted successfully.');
    }

    /*
    |--------------------------------------------------------------------------
    | SELECTABLE TEMPLATES FOR WHATSAPP POPUP
    |--------------------------------------------------------------------------
    */

    public function selectable(
        Request $request,
        Lead $lead
    ): JsonResponse {

        $user = $request->user();

        /*
        |--------------------------------------------------------------------------
        | Lead Security
        |--------------------------------------------------------------------------
        */

        if (
            $user->company_id &&
            !$this->isSuperAdmin($user) &&
            (int) $lead->company_id !== (int) $user->company_id
        ) {
            abort(403);
        }

        /*
        |--------------------------------------------------------------------------
        | Templates
        |--------------------------------------------------------------------------
        */

        $templates = WhatsappMessageTemplate::query()
            ->where('is_active', true)
            ->where(function ($query) use ($user) {

                /*
                | User Personal Templates
                */

                $query->where(function ($query) use ($user) {
                    $query
                        ->where('user_id', $user->id)
                        ->where('is_global', false);
                });

                /*
                | Company Global Templates
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

        /*
        |--------------------------------------------------------------------------
        | Replace Variables
        |--------------------------------------------------------------------------
        */

        $data = $templates->map(function ($template) use ($lead, $user) {

            return [
                'id' => $template->id,
                'name' => $template->name,

                'type' => $template->is_global
                    ? 'Global'
                    : 'Personal',

                'message' => $this->replaceVariables(
                    $template->message,
                    $lead,
                    $user
                ),
            ];
        });

        return response()->json([
            'ok' => true,

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

                'mobile' => $lead->mobile ?? '',
                'city' => $lead->city ?? '',
                'category' => $lead->category ?? '',
            ],

            'whatsapp_number' =>
                $this->normalizeWhatsappNumber(
                    $lead->mobile ?? ''
                ),

            'templates' => $data,
        ]);
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
    | WhatsApp Number
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
        | Indian 10 Digit Number
        |--------------------------------------------------------------------------
        */

        if (strlen($number) === 10) {
            $number = '91' . $number;
        }

        /*
        |--------------------------------------------------------------------------
        | +91 -> already cleaned to 91XXXXXXXXXX
        |--------------------------------------------------------------------------
        */

        return $number;
    }

    /*
    |--------------------------------------------------------------------------
    | EDIT ACCESS
    |--------------------------------------------------------------------------
    */

    private function authorizeTemplateEdit(
        Request $request,
        WhatsappMessageTemplate $template
    ): void {

        $user = $request->user();

        $this->ensureCompanyAccess(
            $user,
            $template
        );

        $isOwner =
            (int) $template->user_id ===
            (int) $user->id;

        if ($isOwner) {

            abort_unless(
                $user->can('whatsapp-template.edit-own') ||
                $user->can('whatsapp-template.edit-all'),
                403
            );

            return;
        }

        abort_unless(
            $user->can('whatsapp-template.edit-all'),
            403
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Company Security
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

        if (
            $template->company_id &&
            (int) $template->company_id !==
            (int) $user->company_id
        ) {
            abort(403);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Super Admin
    |--------------------------------------------------------------------------
    */

    private function isSuperAdmin(User $user): bool
    {
        return
            $user->hasRole('super-admin') ||
            $user->hasRole('super_admin') ||
            $user->hasRole('Super Admin');
    }
}
