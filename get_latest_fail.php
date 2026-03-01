<?php
include 'vendor/autoload.php';
$app = include 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$job = DB::table('failed_jobs')->orderBy('id', 'desc')->first();
if ($job) {
    echo "ID: " . $job->id . "\n";
    echo "Failed At: " . $job->failed_at . "\n";
    echo "Exception: " . $job->exception . "\n";
}
else {
    echo "No failed jobs found.";
}
