<?php
include 'vendor/autoload.php';
$app = include 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$job = DB::table('failed_jobs')->orderBy('id', 'desc')->first();
if ($job) {
    echo "LATEST FAILED JOB EXCEPTION:\n";
    echo $job->exception;
}
else {
    echo "No failed jobs found.";
}
