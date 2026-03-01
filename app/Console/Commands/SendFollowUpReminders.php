<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use App\Models\Lead;
use App\Services\NotificationService;
use Carbon\Carbon;

class SendFollowUpReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notifications:follow-up-reminders';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $description = 'Send notifications for leads with follow-up due today';

    /**
     * Execute the console command.
     */
    public function handle(NotificationService $service)
    {
        $leads = Lead::whereDate('follow_up_date', today())
            ->whereNotNull('assigned_to')
            ->whereNotIn('status', ['converted', 'lost', 'not_interested'])
            ->get();

        foreach ($leads as $lead) {
            $service->notify(
                $lead->assigned_to,
                'follow_up_due',
                'Follow-Up Due Today ⏰',
                "Follow-up due for lead \"{$lead->name}\". Check notes for context.",
                [
                    'link' => "/leads/{$lead->id}",
                    'data' => ['lead_id' => $lead->id],
                    'color' => 'warning',
                    'icon' => 'mdi-clock-alert-outline'
                ]
            );
        }

        $this->info("Sent {$leads->count()} follow-up reminders.");
    }
}
