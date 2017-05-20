<?php

echo "Starting\n";
$job = 'reverse';
$worker = new GearmanWorker();
$worker->addServer();
$worker->addFunction($job, 'main');

print "Waiting for job '$job' ...\n";

while ($worker->work()) {
    if ($worker->returnCode() != GEARMAN_SUCCESS) {
        echo "return_code: " . $worker->returnCode() . "\n";
        break;
    }
}

function main(GearmanJob $job)
{
    $workload = $job->workload();
    echo "processing " . json_encode($workload) . PHP_EOL;

    var_dump($workload);

    $fn = $workload['model'];
    $result = $fn($workload['payload']);

    return $result;
}