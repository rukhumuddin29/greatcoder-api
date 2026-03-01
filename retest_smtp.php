<?php
include 'vendor/autoload.php';
$app = include 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Mail;

$testEmail = 'rukhumuddin.md@gmail.com';

try {
    echo "Attempting to send a raw test email to $testEmail...\n";

    Mail::raw('This is a dedicated test email to verify SMTP and your personal inbox connection.', function ($message) use ($testEmail) {
        $message->to($testEmail)
            ->subject('SMTP Verification - Personal Inbox');
    });

    echo "SUCCESS: Test email sent successfully to $testEmail!\n";
}
catch (Exception $e) {
    echo "FAILED: Connection Error\n";
    echo "Message: " . $e->getMessage() . "\n";
}
