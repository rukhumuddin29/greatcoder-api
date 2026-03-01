<?php
include 'vendor/autoload.php';
$app = include 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

try {
    echo "COLUMNS IN bulk_emails:\n";
    $cols = Schema::getColumnListing('bulk_emails');
    foreach ($cols as $col) {
        echo "- $col\n";
    }

    echo "\nLATEST FAILED JOB:\n";
    $job = DB::table('failed_jobs')->orderBy('id', 'desc')->first();
    if ($job) {
        echo "ID: " . $job->id . "\n";
        echo "Payload: " . substr($job->payload, 0, 100) . "...\n";
        echo "Exception: " . $job->exception . "\n";
    }
    else {
        echo "No failed jobs found.\n";
    }
}
catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
