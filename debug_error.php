<?php
include 'vendor/autoload.php';
$app = include 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$job = DB::table('failed_jobs')->latest()->first();
if ($job) {
    echo $job->exception;
}
else {
    echo "No failed jobs found.";
}
