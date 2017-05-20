<?php

include __DIR__ . '/vendor/autoload.php';

use Phpml\ModelManager;

echo "Starting\n";
$job = 'postcodeToState';
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
    echo "received workload " . $workload . PHP_EOL;

    // load model
    $modelManager = new ModelManager();
    $filePath = __DIR__ . '/models/' . 'postcodeToState';
    $classifier = $modelManager->restoreFromFile($filePath);
    echo 'model loaded from ' . $filePath . PHP_EOL;

    // process workload
    $workload = json_decode($workload);
    $predict = $classifier->predict($workload[0]);
    $result = $predict === $workload[1];
    $result = json_encode($result);

    echo 'Done with result ' . $result . PHP_EOL . PHP_EOL;
    return $result;
}