<?php
include 'vendor/autoload.php';
$app = include 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "COLUMNS IN bulk_emails:\n";
foreach (Schema::getColumnListing('bulk_emails') as $col) {
    echo $col . "\n";
}

$job = DB::table('failed_jobs')->latest()->first();
if ($job) {
    echo "\nLATEST FAILED JOB EXCEPTION:\n";
    echo $job->exception;
}
