<?php

$gmc = new GearmanClient();
$gmc->addServer();

// callbacks
$gmc->setCompleteCallback(function (GearmanTask $task) {
    echo "COMPLETE: " . $task->jobHandle() . ", " . $task->data() . "\n";
});
$gmc->setFailCallback(function (GearmanTask $task) {
    echo "FAILED: " . $task->jobHandle() . "\n";
});

// add tasks
for ($i = 0; $i < 10; $i++) {
    $workload = [
        'payload' => 'foo',
        'model' => function(String $string) {
            return strrev($string);
        }
    ];
    $gmc->addTask("reverse", $workload, $data);
}

// run
if (!$gmc->runTasks()) {
    echo "ERROR " . $gmc->error() . "\n";
    exit;
}

// all done
echo "DONE\n";