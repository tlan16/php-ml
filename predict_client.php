<?php
/**
 * Created by PhpStorm.
 * User: frank
 * Date: 20/05/17
 * Time: 10:42 AM
 */

use \Phpml\ModelManager;

require __DIR__ . '/vendor/autoload.php';

use Phpml\Classification\KNearestNeighbors;

$data = json_decode(file_get_contents(__DIR__ . '/addresses.json'), true);

// prepare learning data
$samples = array_map(function (array $row) {
    return [
        $row['POSTCODE']
    ];
}, $data);
$labels = array_map(function (array $row) {
    return $row['STATE_TERRITORY_CODE'];
}, $data);

echo 'data prepared' . PHP_EOL;

// train
$classifier = new KNearestNeighbors();
$classifier->train($samples, $labels);
$filePath = __DIR__ . '/models/' . 'postcodeToState';
$modelManager = new ModelManager();
$modelManager->saveToFile($classifier, $filePath);
unset($classifier, $modelManager);

echo 'data trained' . PHP_EOL;

// test accuracy
$result = ['correct' => 0, 'count' => 0];
$testData = [];
for ($i = 2000; $i < 3000; $i++) {
    $testData[] = [
        [(String)$i],
        'NSW',
    ];
}
for ($i = 3000; $i < 4000; $i++) {
    $testData[] = [
        [(String)$i],
        'VIC',
    ];
}

// fire
$gmc = new GearmanClient();
$gmc->addServer();

// callbacks
$gmc->setCompleteCallback(function (GearmanTask $task) use (&$result) {
    $data = json_decode($task->data());

    $result['count']++;
    if ($data === true) {
        $result['correct']++;
    }
    echo "COMPLETE: " . $task->jobHandle() . ", " . $task->data() . PHP_EOL;

    echo "Summery: " . json_encode($result) . PHP_EOL;
});

$gmc->setFailCallback(function (GearmanTask $task) {
    echo "FAILED: " . $task->jobHandle() . "\n";
});

// add tasks
foreach ($testData as $row) {
    $gmc->addTask("postcodeToState", json_encode($row), $data);
}

// run
if (!$gmc->runTasks()) {
    echo "ERROR " . $gmc->error() . "\n";
    exit;
}

// all done
echo "DONE\n";

//echo sprintf('Accuracy: %4.2f', $result['correct'] / $result['count']);