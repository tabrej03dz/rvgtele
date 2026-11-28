<?php

namespace App\Console\Commands;

use App\Jobs\SendEmployeePush;
use App\Models\CallHistory;
use App\Models\DeviceToken;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class SendCallIdleReminders extends Command
{
    protected $signature = 'calling:send-idle-reminders
        {--dry-run : List eligible employees without generating messages or sending notifications}
        {--user= : Check a single users.id}';

    protected $description = 'Queue an employee push after more than 10 minutes of call inactivity';

    private const WORK_TIMEZONE = 'Asia/Kolkata';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $userId = $this->option('user');

        if (
            $userId !== null
            && filter_var($userId, FILTER_VALIDATE_INT, [
                'options' => ['min_range' => 1],
            ]) === false
        ) {
            $this->error('--user must be a positive users.id.');

            return self::FAILURE;
        }

        if (!$dryRun && $userId === null && !config('call_idle_reminders.enabled')) {
            return self::SUCCESS;
        }

        $start = (string) config('call_idle_reminders.start');
        $end = (string) config('call_idle_reminders.end');
        $clockFormat = '/^(?:[01][0-9]|2[0-3]):[0-5][0-9]$/D';

        if (!preg_match($clockFormat, $start) || !preg_match($clockFormat, $end) || $start >= $end) {
            $this->error('Set CALL_IDLE_WORK_START and CALL_IDLE_WORK_END as same-day HH:MM times.');

            return self::FAILURE;
        }

        $workNow = CarbonImmutable::now(self::WORK_TIMEZONE);
        $storageTimezone = config('app.timezone', 'UTC');
        $shiftStart = $workNow->setTimeFromTimeString($start)->setTimezone($storageTimezone);
        $shiftEnd = $workNow->setTimeFromTimeString($end)->setTimezone($storageTimezone);

        if ($workNow->lessThan($shiftStart) || $workNow->greaterThanOrEqualTo($shiftEnd)) {
            $this->info('Outside the configured calling hours.');

            return self::SUCCESS;
        }

        $databaseConnection = DB::getDefaultConnection();
        $queueDatabaseConnection = config('queue.connections.database.connection')
            ?: $databaseConnection;

        if (!$dryRun && $queueDatabaseConnection !== $databaseConnection) {
            $this->error('The database queue and employee records must use the same database connection.');

            return self::FAILURE;
        }

        $matched = 0;
        $queued = 0;
        $failed = 0;

        $this->employees()
            ->when($userId !== null, fn (Builder $query) => $query->whereKey((int) $userId))
            ->lazyById(100)
            ->each(function (User $employee) use (
                $dryRun,
                $shiftStart,
                $shiftEnd,
                &$matched,
                &$queued,
                &$failed,
            ): void {
                try {
                    // $minutes = $this->idleMinutes($employee, $shiftStart, $shiftEnd);
                    $activity = $this->eligibleActivity($employee, $shiftStart, $shiftEnd);

                    if ($activity === null) {
                        return;
                    }

                    $matched++;

                    if ($dryRun) {
                        $duration = $activity['last_call'] === null
                            ? 'कोई आउटगोइंग कॉल दर्ज नहीं है'
                            : $this->formatIdleDuration($activity['last_call'], $activity['now']);

                        $this->line("#{$employee->id} {$employee->name} | {$duration}");

                        return;
                    }

                    $message = $this->generateFollowUp($employee);

                    if ($this->queueReminder((int) $employee->id, $message, $shiftStart, $shiftEnd)) {
                        $queued++;
                    }
                } catch (Throwable $exception) {
                    $failed++;
                    report($exception);
                    $this->error("Could not process employee #{$employee->id}.");
                }
            });

        $this->info("Eligible: {$matched}. Queued: {$queued}. Failed: {$failed}.");

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }

    private function employees(): Builder
    {
        return User::query()
            ->where('users.is_active', 1)
            ->whereNotNull('users.company_id')
            ->whereHas('roles', fn (Builder $roles) => $roles->whereIn('name', [
                'employee',
                'telecaller',
                'sales_executive',
                'field_sales_executive',
                'quality_analyst',
                'customer_support',
                'accounts_user',
                'team_leader',
                'sales_manager',
                'branch_manager',
            ]))
            ->whereExists(function ($leads): void {
                $leads->selectRaw('1')
                    ->from('leads')
                    ->whereColumn('leads.assigned_to', 'users.id')
                    ->whereColumn('leads.company_id', 'users.company_id');
            })
            ->whereHas('deviceTokens', fn (Builder $devices) => $devices
                ->whereIn('platform', ['android', 'ios'])
                ->whereNotNull('token')
                ->where('token', '<>', ''));
    }

    // private function idleMinutes(
    //     User $employee,
    //     CarbonImmutable $shiftStart,
    //     CarbonImmutable $shiftEnd,
    // ): ?int {
    //     $now = CarbonImmutable::now($shiftStart->timezone)->startOfSecond();

    //     if ($now->lessThan($shiftStart) || $now->greaterThanOrEqualTo($shiftEnd)) {
    //         return null;
    //     }

    //     $lastCall = $this->latestOutgoingCall((int) $employee->id, $now);

    //     if ($lastCall !== null && $lastCall->lessThan($shiftStart)) {
    //         $lastCall = null;
    //     }

    //     $activityAt = $lastCall ?? $shiftStart;

    //     if ($employee->created_at && $employee->created_at->greaterThan($activityAt)) {
    //         $activityAt = CarbonImmutable::instance($employee->created_at);
    //     }

    //     $lastReminder = $employee->last_call_idle_reminder_at;

    //     if (
    //         $lastReminder
    //         && CarbonImmutable::parse($lastReminder, $shiftStart->timezone)
    //             ->greaterThanOrEqualTo($activityAt)
    //     ) {
    //         return null;
    //     }

    //     $seconds = $now->getTimestamp() - $activityAt->getTimestamp();

    //     return $seconds > 600 ? intdiv($seconds, 60) : null;
    // }


    /**
 * @return array{now: CarbonImmutable, last_call: ?CarbonImmutable}|null
 */
private function eligibleActivity(
    User $employee,
    CarbonImmutable $shiftStart,
    CarbonImmutable $shiftEnd,
): ?array {
    $now = CarbonImmutable::now($shiftStart->timezone)->startOfSecond();

    if ($now->lessThan($shiftStart) || $now->greaterThanOrEqualTo($shiftEnd)) {
        return null;
    }

    $lastCall = $this->latestOutgoingCall((int) $employee->id, $now);

    $activityAt = $lastCall !== null && $lastCall->greaterThan($shiftStart)
        ? $lastCall
        : $shiftStart;

    if ($employee->created_at && $employee->created_at->greaterThan($activityAt)) {
        $activityAt = CarbonImmutable::instance($employee->created_at);
    }

    $idleSeconds = $now->getTimestamp() - $activityAt->getTimestamp();

    if ($idleSeconds <= 600) {
        return null;
    }

    $lastReminder = $employee->last_call_idle_reminder_at;

    if ($lastReminder) {
        $lastReminderAt = CarbonImmutable::parse($lastReminder, $now->timezone);
        $repeatSeconds = max(1, (int) config('call_idle_reminders.repeat_minutes', 10)) * 60;
        $secondsSinceReminder = $now->getTimestamp() - $lastReminderAt->getTimestamp();

        if (
            $lastReminderAt->greaterThanOrEqualTo($activityAt)
            && $secondsSinceReminder < $repeatSeconds
        ) {
            return null;
        }
    }

    return [
        'now' => $now,
        'last_call' => $lastCall,
    ];
}

    private function latestOutgoingCall(int $userId, CarbonImmutable $now): ?CarbonImmutable
    {
        $lastCall = CallHistory::query()
            ->where('user_id', $userId)
            ->where(function (Builder $calls): void {
                $calls->where('direction', 'outgoing')
                    ->orWhere(function (Builder $legacy): void {
                        $legacy->whereNull('direction')
                            ->where(function (Builder $types): void {
                                $types->whereNull('call_type')
                                    ->orWhere('call_type', 'outgoing');
                            });
                    });
            })
            ->whereRaw('COALESCE(call_at, created_at) <= ?', [$now])
            ->max(DB::raw('COALESCE(call_at, created_at)'));

        return $lastCall === null
            ? null
            : CarbonImmutable::parse($lastCall, $now->timezone);
    }

    // private function message(string $name, int $minutes): string
    // {
    //     $name = Str::limit(trim($name), 100, '');
    //     $fallback = 'पिछले 10 मिनट से अधिक समय से कोई कॉल दर्ज नहीं हुई है। कृपया अपनी अगली कॉल शुरू करें।';

    //     $key = config('call_idle_reminders.openai_key');
    //     $model = config('call_idle_reminders.openai_model');

    //     if (blank($key) || blank($model)) {
    //         return "{$name} जी, {$fallback}";
    //     }

    //     try {
    //         $response = Http::withToken($key)
    //             ->acceptJson()
    //             ->connectTimeout(3)
    //             ->timeout(8)
    //             ->post('https://api.openai.com/v1/chat/completions', [
    //                 'model' => $model,
    //                 'store' => false,
    //                 'max_completion_tokens' => 512,
    //                 'messages' => [
    //                     [
    //                         'role' => 'system',
    //                         'content' => 'Write a respectful Hindi push notification from TeleCRM. '
    //                             . 'Say no outgoing call was recorded for over 10 minutes '
    //                             . 'and politely ask the employee to start their next call. '
    //                             . 'Use at most two short sentences. Return only the text '
    //                             . 'without greeting, name, heading, emoji or quotes.',
    //                     ],
    //                     [
    //                         'role' => 'user',
    //                         'content' => "Whole minutes since recorded activity: {$minutes}.",
    //                     ],
    //                 ],
    //             ]);

    //         $body = $response->json('choices.0.message.content');

    //         if ($response->successful() && is_string($body) && trim($body) !== '') {
    //             return "{$name} जी, " . Str::limit(Str::squish($body), 300, '');
    //         }

    //         Log::warning('Call idle reminder used fallback text.', [
    //             'openai_status' => $response->status(),
    //         ]);
    //     } catch (ConnectionException) {
    //         Log::warning('Call idle reminder used fallback text after an OpenAI connection failure.');
    //     }

    //     return "{$name} जी, {$fallback}";
    // }

    private function generateFollowUp(User $employee): string
    {
        $previousFollowUp = $this->previousFollowUp($employee);

        $fallbacks = [
            'कृपया अपनी अगली लीड को कॉल करें।',
            'कृपया अगली कॉल करके अपना फॉलो-अप आगे बढ़ाएँ।',
            'कृपया कॉलिंग फिर से शुरू करें और अगली लीड से बात करें।',
        ];

        $previousText = Str::lower(Str::squish($previousFollowUp));

        $fallback = collect($fallbacks)
            ->first(fn (string $line) => Str::lower(Str::squish($line)) !== $previousText);

        $key = config('call_idle_reminders.openai_key');
        $model = config('call_idle_reminders.openai_model');

        if (blank($key) || blank($model)) {
            Log::warning('Call idle reminder used fallback text because OpenAI config is missing.');

            return $fallback;
        }

        try {
            $response = Http::withToken($key)
                ->acceptJson()
                ->connectTimeout(3)
                ->timeout(8)
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model' => $model,
                    'store' => false,
                    'max_completion_tokens' => 512,
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => 'Write one short, respectful Hindi sentence for a TeleCRM push. '
                                . 'Politely ask the employee to resume calling or call the next lead. '
                                . 'Vary the wording from the previous sentence. '
                                . 'Write at most 20 words. Omit names, greetings, elapsed time, '
                                . 'numbers, emojis, headings, threats and quotes. Return only the sentence.',
                        ],
                        [
                            'role' => 'user',
                            'content' => $previousFollowUp === ''
                                ? 'Write the first reminder sentence.'
                                : "Previous reminder sentence: {$previousFollowUp}",
                        ],
                    ],
                ]);

            $body = $response->json('choices.0.message.content');

            if ($response->successful() && is_string($body)) {
                $line = Str::limit(Str::squish($body), 180, '');

                if ($line !== '' && Str::lower($line) !== $previousText) {
                    return $line;
                }
            }

            Log::warning('Call idle reminder used fallback text.', [
                'openai_status' => $response->status(),
            ]);
        } catch (ConnectionException) {
            Log::warning('Call idle reminder used fallback text after an OpenAI connection failure.');
        }

        return $fallback;
    }

    private function previousFollowUp(User $employee): string
    {
        if (!$employee->last_call_idle_reminder_at) {
            return '';
        }

        $message = DB::table('employee_push_notifications as n')
            ->join('employee_push_recipients as r', 'r.notification_id', '=', 'n.id')
            ->where('r.user_id', $employee->id)
            ->whereNull('n.created_by')
            ->where('n.created_at', $employee->last_call_idle_reminder_at)
            ->orderByDesc('n.id')
            ->value('n.message');

        $parts = explode("\n", (string) $message, 2);

        return trim($parts[1] ?? '');
    }

    private function formatIdleDuration(CarbonImmutable $lastCall, CarbonImmutable $now): string
    {
        $totalMinutes = intdiv(max(0, $now->getTimestamp() - $lastCall->getTimestamp()), 60);
        $hours = intdiv($totalMinutes, 60);
        $minutes = $totalMinutes % 60;

        if ($hours === 0) {
            return "{$minutes} मिनट";
        }

        return $minutes === 0
            ? "{$hours} घंटे"
            : "{$hours} घंटे {$minutes} मिनट";
    }

    private function queueReminder(
        int $userId,
        string $message,
        CarbonImmutable $shiftStart,
        CarbonImmutable $shiftEnd,
    ): bool {
        return DB::transaction(function () use ($userId, $message, $shiftStart, $shiftEnd): bool {
            $employee = $this->employees()->whereKey($userId)->lockForUpdate()->first();

            // if (!$employee || $this->idleMinutes($employee, $shiftStart, $shiftEnd) === null) {
            //     return false;
            // }

            if (!$employee) {
                return false;
            }

            $activity = $this->eligibleActivity($employee, $shiftStart, $shiftEnd);

            if ($activity === null) {
                return false;
            }

            $deviceIds = DeviceToken::query()
                ->where('user_id', $userId)
                ->whereIn('platform', ['android', 'ios'])
                ->whereNotNull('token')
                ->where('token', '<>', '')
                ->pluck('id');

            if ($deviceIds->isEmpty()) {
                return false;
            }

            $now = $activity['now'];
            $lastCall = $activity['last_call'];
            $name = Str::limit(trim($employee->name), 100, '');

            if ($lastCall === null) {
                $activityText = 'आपकी कोई आउटगोइंग कॉल अभी तक दर्ज नहीं हुई है।';
            } else {
                $duration = $this->formatIdleDuration($lastCall, $now);
                $activityText = "आपकी आखिरी आउटगोइंग कॉल {$duration} पहले दर्ज हुई थी।";
            }

            $message = "{$name} जी, {$activityText}\n{$message}";

            $notificationId = DB::table('employee_push_notifications')->insertGetId([
                'created_by' => null,
                'message' => $message,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            DB::table('employee_push_recipients')->insert([
                'notification_id' => $notificationId,
                'user_id' => $userId,
            ]);

            DB::table('users')->where('id', $userId)->update([
                'last_call_idle_reminder_at' => $now,
            ]);

            foreach ($deviceIds as $deviceId) {
                Bus::dispatch(
                    (new SendEmployeePush((int) $notificationId, (int) $deviceId, $userId))
                        ->onConnection('database')
                        ->beforeCommit()
                );
            }

            return true;
        }, 3);
    }
}