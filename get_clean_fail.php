<?php
include 'vendor/autoload.php';
$app = include 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$job = DB::table('failed_jobs')->orderBy('id', 'desc')->first();
if ($job) {
    $lines = explode("\n", $job->exception);
    echo "ID: " . $job->id . "\n";
    echo "MESSAGE: " . $lines[0] . "\n";
    if (isset($lines[1]))
        echo "DETAIL: " . $lines[1] . "\n";
}
else {
    echo "No failed jobs found.";
}
