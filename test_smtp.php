<?php
include 'vendor/autoload.php';
$app = include 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Mail;

$testEmail = 'rukhumuddin@gmail.com'; // You can change this to your email for testing

try {
    echo "Attempting to send a raw test email to $testEmail...\n";

    Mail::raw('This is a test email from the Elements CRM system to verify SMTP settings.', function ($message) use ($testEmail) {
        $message->to($testEmail)
            ->subject('SMTP Verification Test');
    });

    echo "SUCCESS: Test email sent successfully!\n";
}
catch (Exception $e) {
    echo "FAILED: Connection Error\n";
    echo "Message: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}
