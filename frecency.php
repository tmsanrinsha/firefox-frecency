<?php
// require_once 'GaussianElimination.php';

define('FIRST_BUCKET_CUTOFF',   4 * 24 * 60 * 60 * 1000000);
define('SECOND_BUCKET_CUTOFF', 14 * 24 * 60 * 60 * 1000000);
define('THIRD_BUCKET_CUTOFF',  31 * 24 * 60 * 60 * 1000000);
define('FOURTH_BUCKET_CUTOFF', 90 * 24 * 60 * 60 * 1000000);
define('NUMVISITS', 10);

define('VISIT_TYPE_NUM', 8);
define('CUTOFF_NUM', 5);

// $cutoff = array(0, FIRST_BUCKET_CUTOFF, SECOND_BUCKET_CUTOFF, THIRD_BUCKET_CUTOFF, FOURTH_BUCKET_CUTOFF, null);
// $cutoffNum = count($cutoff) - 1;
$visitTypeArr = array(1, 2, 3, 5, 6);

// goto sqlEnd;
// 履歴データの取得
$db = new SQLite3(array_shift(glob('/Users/*/Library/Application Support/Firefox/Profiles/*/places.sqlite')));

// ロケーションバーもしくは、Vimperatorの:openなどから選択されて表示された履歴のデータの取得
$selectedVisitData = $db->query(
    "SELECT place_id, visit_date " .
    "FROM moz_historyvisits " .
    "WHERE (from_visit = 0 AND visit_type = 1) OR visit_type = 2"
);

for ($i = 0; $i < CUTOFF_NUM; $i++) {
    for ($j = 0; $j < VISIT_TYPE_NUM; $j++) {
        $coefficient[$i][$j] = 0;
    }
}

// 取得したデータから当時のfrecencyを計算するために必要なデータを取得する
while ($selectedVisitRow = $selectedVisitData->fetchArray()) {
        // echo "SELECT * " .
        // "FROM moz_historyvisits " .
        // "WHERE place_id   = " .  $selectedVisitRow['place_id'] . " " .
        //   "AND visit_date < " . $selectedVisitRow['visit_date'] . " " .
        // "ORDER BY visit_date DESC " .
        // "LIMIT " . NUMVISITS . PHP_EOL;
    $result = $db->query(
        "SELECT * " .
        "FROM moz_historyvisits " .
        "WHERE place_id   = " . $selectedVisitRow['place_id'] . " " .
          "AND visit_date < " . $selectedVisitRow['visit_date'] . " " .
        "ORDER BY visit_date DESC " .
        "LIMIT " . NUMVISITS
    );

    $countResult = $db->query(
        "SELECT count(*) as count " .
        "FROM moz_historyvisits " .
        "WHERE place_id   = " . $selectedVisitRow['place_id'] . ' ' .
          "AND visit_date < " . $selectedVisitRow['visit_date']
    );

    $countRow = $countResult->fetchArray();
    $count = $countRow['count'];

    while ($row = $result->fetchArray()) {
        if ($row['visit_date'] > $selectedVisitRow['visit_date'] - FIRST_BUCKET_CUTOFF) {
            $i = 0;
        } elseif ($row['visit_date'] > $selectedVisitRow['visit_date']- SECOND_BUCKET_CUTOFF) {
            $i = 1;
        } elseif ($row['visit_date'] > $selectedVisitRow['visit_date'] - THIRD_BUCKET_CUTOFF) {
            $i = 2;
        } elseif ($row['visit_date'] > $selectedVisitRow['visit_date'] - FOURTH_BUCKET_CUTOFF) {
            $i = 3;
        } elseif ($row['visit_date'] > 1) {
            // echo $row['visit_date'] . PHP_EOL;
            $i = 4;
        }
        // echo 'visit_date' . $row['visit_date'] . PHP_EOL;
        // echo 'visit_type' . $row['visit_type'] . PHP_EOL;
        $coefficient[$i][$row['visit_type'] - 1] += $count;
    }
}
$db->close();
var_export($coefficient);

$c = array_fill(0, CUTOFF_NUM, 0);
$l = 1;
$c[$l] = 1;

$max = 0;

while (true) {
    $sum = 0;
    $v = array_merge(array_fill(0, VISIT_TYPE_NUM, 0));
    for ($j=0; $j < VISIT_TYPE_NUM; $j++) {
        for ($i=0; $i < CUTOFF_NUM; $i++) {
            $v[$j] += $c[$i] * $coefficient[$i][$j];
        }
    }

    $v = normalize($v);
    echo 'v = ' . json_encode($v) . PHP_EOL;

    $c = array_merge(array_fill(0, CUTOFF_NUM, 0));
    for ($i=0; $i < CUTOFF_NUM; $i++) {
        for ($j=0; $j < VISIT_TYPE_NUM; $j++) {
            $c[$i] += $coefficient[$i][$j] * $v[$j];
        }
    }

    $c = normalize($c);
    echo 'c = ' . json_encode($c) . PHP_EOL;

    $sum = 0;
    for ($i=0; $i < CUTOFF_NUM; $i++) { 
        for ($j=0; $j < VISIT_TYPE_NUM; $j++) { 
            $sum += $coefficient[$i][$j] * $c[$i] * $v[$j];
        }
    }

    if ($sum > $max) {
        $max = $sum;
        echo 'l = ' . $l . PHP_EOL;
        echo 'max = ' . $max . PHP_EOL;
    } elseif (++$l < CUTOFF_NUM - 1) {
        $c = array_fill(0, CUTOFF_NUM, 0);
        $c[$l] = 1;
        continue;
    } else {
        break;
    }
}


function normalize($vec) {
    $len = sqrt(array_sum(array_map(function ($arr) {
        return $arr * $arr;
    }, $vec)));

    return array_map(function ($arr) use ($len) {
        return $arr / $len;
    }, $vec);
}


function printV($vec) {
    global $$vec;
    echo $vec . PHP_EOL;
    echo implode(', ', $$vec) . PHP_EOL;
}
