@php

    $latestCall = $lead->latestCall;

    $latestRemark =
        $latestCall?->remarks
        ?? $latestCall?->remark
        ?? $latestCall?->auto_remarks
        ?? null;

    $latestFeedback =
        $latestRemark
        ?: $lead->latest_note_body;

    /*
    |--------------------------------------------------------------------------
    | Duration
    |--------------------------------------------------------------------------
    |
    | Different project versions ke liye multiple possible names support.
    |
    */

    $duration =
        $latestCall?->duration_seconds
        ?? $latestCall?->duration
        ?? $latestCall?->call_duration
        ?? null;

    $durationText = null;

    if (is_numeric($duration)) {

        $duration = (int)$duration;

        $minutes = intdiv(
            $duration,
            60
        );

        $seconds =
            $duration % 60;

        $durationText =
            str_pad(
                (string)$minutes,
                2,
                '0',
                STR_PAD_LEFT
            )
            . 'm '
            .
            str_pad(
                (string)$seconds,
                2,
                '0',
                STR_PAD_LEFT
            )
            . 's';

    } elseif(
        is_string($duration)
        &&
        trim($duration) !== ''
    ) {

        $durationText = $duration;
    }


    $initials =
        collect(
            preg_split(
                '/\s+/',
                trim(
                    $lead->name
                    ?: $lead->company_name
                    ?: 'Lead'
                )
            )
        )
        ->filter()
        ->take(2)
        ->map(
            fn($word) =>
                mb_strtoupper(
                    mb_substr(
                        $word,
                        0,
                        1
                    )
                )
        )
        ->implode('');


    $leadUrl = route(
        'leads.show',
        array_merge(
            [
                'lead' => $lead->id
            ],
            request()->except([
                'new_page',
                'dialed_page',
                'connected_page'
            ])
        )
    );


    $avatarClass = match($type) {
        'new' =>
            'avatar-new',

        'dialed' =>
            'avatar-dialed',

        default =>
            'avatar-connected',
    };


    $cardClass = match($type) {
        'new' =>
            'new-card',

        'dialed' =>
            'dialed-card',

        default =>
            'connected-card',
    };


    /*
    |--------------------------------------------------------------------------
    | WhatsApp Number
    |--------------------------------------------------------------------------
    */

    $whatsappNumber =
        preg_replace(
            '/\D+/',
            '',
            $lead->whatsapp_number
                ?: $lead->mobile
        );


    /*
    |--------------------------------------------------------------------------
    | Call Time
    |--------------------------------------------------------------------------
    */

    $lastCallTime =
        $latestCall?->created_at;


    /*
    |--------------------------------------------------------------------------
    | Follow-up
    |--------------------------------------------------------------------------
    */

    $nextFollowUp =
        $lead->next_follow_up_at;

@endphp


<div class="lead-card {{ $cardClass }}">

    <div class="lead-top">

        <div class="lead-profile">

            <div class="lead-avatar {{ $avatarClass }}">
                {{ $initials ?: 'L' }}
            </div>


            <div class="min-w-0">

                <a
                    href="{{ $leadUrl }}"
                    class="lead-name"
                >
                    {{
                        $lead->company_name
                        ?: $lead->name
                        ?: 'Unnamed Lead'
                    }}
                </a>


                <div class="lead-meta">

                    <i data-lucide="phone"></i>

                    <span>
                        {{ $lead->mobile ?: 'No Mobile' }}
                    </span>

                </div>


                @if($lead->city || $lead->state)

                    <div class="lead-meta">

                        <i data-lucide="map-pin"></i>

                        <span>
                            {{
                                collect([
                                    $lead->city,
                                    $lead->state
                                ])
                                ->filter()
                                ->implode(', ')
                            }}
                        </span>

                    </div>

                @endif

            </div>

        </div>


        <div class="lead-right">

            @if($type === 'new')

                <div class="lead-badge text-emerald-600">
                    New
                </div>

                <div class="call-state text-slate-500">
                    ◷ No call yet
                </div>

            @else

                @if($lastCallTime)

                    <div class="call-time">

                        <i data-lucide="clock"></i>

                        <span>
                            {{
                                \Illuminate\Support\Carbon::parse(
                                    $lastCallTime
                                )->isToday()
                                    ? 'Today '
                                        .
                                        \Illuminate\Support\Carbon::parse(
                                            $lastCallTime
                                        )->format('h:i A')

                                    : \Illuminate\Support\Carbon::parse(
                                        $lastCallTime
                                    )->format('d M, h:i A')
                            }}
                        </span>

                    </div>

                @endif


                @if($type === 'dialed')

                    <div class="call-state text-orange-600">

                        {{
                            $latestCall?->disposition?->name
                            ?: 'Dialed'
                        }}

                    </div>

                @else

                    @if($durationText)

                        <div class="call-state text-emerald-600">

                            ☎ {{ $durationText }}

                        </div>

                    @else

                        <div class="call-state text-emerald-600">
                            Connected
                        </div>

                    @endif

                @endif

            @endif

        </div>

    </div>


    {{-- FEEDBACK --}}

    @if($type !== 'new')

        <div class="feedback-row">

            <span>
                Last Feedback:
            </span>

            <span class="feedback-value">
                {{
                    $latestFeedback
                    ?: 'No feedback entered'
                }}
            </span>

        </div>

    @endif


    {{-- NEXT FOLLOW UP --}}

    @if($nextFollowUp)

        <div class="followup-row">

            <i data-lucide="calendar-clock"></i>

            <span>

                Next Follow-up:

                <strong>

                    {{
                        \Illuminate\Support\Carbon::parse(
                            $nextFollowUp
                        )->format(
                            'd M, h:i A'
                        )
                    }}

                </strong>

            </span>

        </div>

    @endif


    {{-- DEMO SEND --}}

    @if($lead->demo_send)

        <div class="mt-2 flex justify-end">

            <span class="demo-sent">

                <i data-lucide="video"></i>

                Demo Sent

            </span>

        </div>

    @endif


    {{-- BOTTOM --}}

    <div class="card-bottom">

        <div>

            @if($lead->category)

                <span class="category-tag">
                    {{ $lead->category }}
                </span>

            @endif

        </div>


        <div class="action-group">

            {{-- CALL --}}

            @if($lead->mobile)

                <button
                    type="button"
                    class="round-action call-action"
                    title="Call on mobile"
                    @click="sendCall({{ $lead->id }})"
                    :disabled="
                        sendingCall === {{ $lead->id }}
                    "
                >
                    <i data-lucide="phone"></i>
                </button>

            @endif


            {{-- WHATSAPP --}}

            @if($whatsappNumber)

                <a
                    href="https://wa.me/{{ $whatsappNumber }}"
                    target="_blank"
                    rel="noopener"
                    class="round-action whatsapp-action"
                    title="WhatsApp"
                >
                    <i data-lucide="message-circle"></i>
                </a>

            @endif


            {{-- OPEN LEAD --}}

            <a
                href="{{ $leadUrl }}"
                class="round-action open-action"
                title="Open Lead"
            >
                <i data-lucide="plus"></i>
            </a>

        </div>

    </div>

</div>