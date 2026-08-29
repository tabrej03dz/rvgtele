@php
    $record = $data ?? null;
@endphp


{{-- ========================================================= --}}
{{-- BASIC INFORMATION --}}
{{-- ========================================================= --}}

<section class="software-panel">

    <div class="software-panel-title">

        <span class="panel-heading-label">

            <span class="panel-heading-icon">
                <i data-lucide="user-round"></i>
            </span>

            Basic Information

        </span>

    </div>


    <div
        class="
            grid
            gap-4
            p-4
            md:grid-cols-2
            lg:grid-cols-3
        "
    >

        <div>

            <label class="software-label">
                Company *
            </label>

            <select
                name="company_id"
                required
                class="w-full"
            >

                <option value="">
                    Select Company
                </option>

                @foreach($companies as $company)

                    <option
                        value="{{ $company->id }}"
                        @selected(
                            (string) old(
                                'company_id',
                                $record?->company_id
                            )
                            ===
                            (string) $company->id
                        )
                    >
                        {{ $company->name }}
                    </option>

                @endforeach

            </select>

            @error('company_id')
                <div class="software-error">
                    {{ $message }}
                </div>
            @enderror

        </div>


        <div>

            <label class="software-label">
                Customer Name
            </label>

            <input
                type="text"
                name="name"
                value="{{ old('name', $record?->name) }}"
                class="w-full"
                placeholder="Customer name"
            >

        </div>


        <div>

            <label class="software-label">
                Business / Company Name
            </label>

            <input
                type="text"
                name="company_name"
                value="{{ old(
                    'company_name',
                    $record?->company_name
                ) }}"
                class="w-full"
                placeholder="Business name"
            >

        </div>


        <div>

            <label class="software-label">
                Mobile
            </label>

            <input
                type="text"
                name="mobile"
                maxlength="20"
                value="{{ old('mobile', $record?->mobile) }}"
                class="w-full"
                placeholder="Mobile number"
            >

        </div>


        <div>

            <label class="software-label">
                Alternate Mobile
            </label>

            <input
                type="text"
                name="alternate_mobile"
                maxlength="20"
                value="{{ old(
                    'alternate_mobile',
                    $record?->alternate_mobile
                ) }}"
                class="w-full"
                placeholder="Alternate number"
            >

        </div>


        <div>

            <label class="software-label">
                WhatsApp Number
            </label>

            <input
                type="text"
                name="whatsapp_number"
                maxlength="20"
                value="{{ old(
                    'whatsapp_number',
                    $record?->whatsapp_number
                ) }}"
                class="w-full"
                placeholder="WhatsApp number"
            >

        </div>


        <div>

            <label class="software-label">
                Email
            </label>

            <input
                type="email"
                name="email"
                value="{{ old('email', $record?->email) }}"
                class="w-full"
                placeholder="Email address"
            >

        </div>


        <div>

            <label class="software-label">
                Preferred Language
            </label>

            <input
                type="text"
                name="preferred_language"
                value="{{ old(
                    'preferred_language',
                    $record?->preferred_language
                ) }}"
                class="w-full"
                placeholder="Hindi / English..."
            >

        </div>

    </div>

</section>


{{-- ========================================================= --}}
{{-- CATEGORY & SOURCE --}}
{{-- ========================================================= --}}

<section class="software-panel">

    <div class="software-panel-title">

        <span class="panel-heading-label">

            <span class="panel-heading-icon">
                <i data-lucide="tags"></i>
            </span>

            Category & Source

        </span>

    </div>


    <div
        class="
            grid
            gap-4
            p-4
            md:grid-cols-3
        "
    >

        <div>

            <label class="software-label">
                Category
            </label>

            <input
                type="text"
                name="category"
                value="{{ old(
                    'category',
                    $record?->category
                ) }}"
                class="w-full"
                placeholder="Jewellery, Solar, Furniture..."
            >

        </div>


        <div>

            <label class="software-label">
                Lead Source
            </label>

            <input
                type="text"
                name="lead_source"
                value="{{ old(
                    'lead_source',
                    $record?->lead_source
                ) }}"
                class="w-full"
                placeholder="Google, Facebook..."
            >

        </div>


        <div>

            <label class="software-label">
                Campaign
            </label>

            <input
                type="text"
                name="campaign"
                value="{{ old(
                    'campaign',
                    $record?->campaign
                ) }}"
                class="w-full"
                placeholder="Campaign name"
            >

        </div>

    </div>

</section>


{{-- ========================================================= --}}
{{-- LOCATION --}}
{{-- ========================================================= --}}

<section class="software-panel">

    <div class="software-panel-title">

        <span class="panel-heading-label">

            <span class="panel-heading-icon">
                <i data-lucide="map-pin"></i>
            </span>

            Location

        </span>

    </div>


    <div
        class="
            grid
            gap-4
            p-4
            md:grid-cols-2
            lg:grid-cols-4
        "
    >

        <div class="md:col-span-2 lg:col-span-4">

            <label class="software-label">
                Address
            </label>

            <textarea
                name="address"
                rows="3"
                class="w-full"
                placeholder="Full address"
            >{{ old('address', $record?->address) }}</textarea>

        </div>


        <div>

            <label class="software-label">
                City
            </label>

            <input
                type="text"
                name="city"
                value="{{ old('city', $record?->city) }}"
                class="w-full"
            >

        </div>


        <div>

            <label class="software-label">
                District
            </label>

            <input
                type="text"
                name="district"
                value="{{ old(
                    'district',
                    $record?->district
                ) }}"
                class="w-full"
            >

        </div>


        <div>

            <label class="software-label">
                State
            </label>

            <input
                type="text"
                name="state"
                value="{{ old('state', $record?->state) }}"
                class="w-full"
            >

        </div>


        <div>

            <label class="software-label">
                Pincode
            </label>

            <input
                type="text"
                name="pincode"
                maxlength="10"
                value="{{ old(
                    'pincode',
                    $record?->pincode
                ) }}"
                class="w-full"
            >

        </div>

    </div>

</section>


{{-- ========================================================= --}}
{{-- BUSINESS REQUIREMENT --}}
{{-- ========================================================= --}}

<section class="software-panel">

    <div class="software-panel-title">

        <span class="panel-heading-label">

            <span class="panel-heading-icon">
                <i data-lucide="briefcase-business"></i>
            </span>

            Business Requirement

        </span>

    </div>


    <div
        class="
            grid
            gap-4
            p-4
            md:grid-cols-3
        "
    >

        <div>

            <label class="software-label">
                Industry
            </label>

            <input
                type="text"
                name="industry"
                value="{{ old(
                    'industry',
                    $record?->industry
                ) }}"
                class="w-full"
            >

        </div>


        <div>

            <label class="software-label">
                Required Product
            </label>

            <input
                type="text"
                name="required_product"
                value="{{ old(
                    'required_product',
                    $record?->required_product
                ) }}"
                class="w-full"
            >

        </div>


        <div>

            <label class="software-label">
                Estimated Budget
            </label>

            <input
                type="number"
                name="estimated_budget"
                step="0.01"
                min="0"
                value="{{ old(
                    'estimated_budget',
                    $record?->estimated_budget
                ) }}"
                class="w-full"
            >

        </div>


        <div class="md:col-span-3">

            <label class="software-label">
                Remarks
            </label>

            <textarea
                name="remarks"
                rows="4"
                class="w-full"
                placeholder="Important notes..."
            >{{ old('remarks', $record?->remarks) }}</textarea>

        </div>

    </div>

</section>