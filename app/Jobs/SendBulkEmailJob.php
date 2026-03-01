<?php

namespace App\Jobs;

use App\Mail\GenericBulkEmail;
use App\Models\BulkEmail;
use App\Models\Lead;
use App\Models\User;
use App\Models\Company;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Exception;

class SendBulkEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $bulkEmail;

    /**
     * Create a new job instance.
     */
    public function __construct(BulkEmail $bulkEmail)
    {
        $this->bulkEmail = $bulkEmail;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $user = User::find($this->bulkEmail->sent_by);
            $company = Company::first();

            $emails = [];
            if ($this->bulkEmail->type === 'leads') {
                $query = Lead::whereNotNull('email')->where('email', '!=', '');
                if ($this->bulkEmail->target_status && $this->bulkEmail->target_status !== 'all') {
                    $query->where('status', $this->bulkEmail->target_status);
                }
                $emails = $query->pluck('email')->toArray();
            }
            elseif ($this->bulkEmail->type === 'random') {
                $emails = $this->bulkEmail->recipient ? [$this->bulkEmail->recipient] : [];
            }

            if (empty($emails)) {
                $this->bulkEmail->update(['status' => 'failed']);
                return;
            }

            foreach ($emails as $email) {
                Mail::to($email)->send(new GenericBulkEmail($this->bulkEmail->subject, $this->bulkEmail->body, $user, $company));
            }

            $this->bulkEmail->update([
                'status' => 'sent',
                'recipients_count' => count($emails)
            ]);
        }
        catch (Exception $e) {
            $this->bulkEmail->update(['status' => 'failed']);
            throw $e;
        }
    }
}
