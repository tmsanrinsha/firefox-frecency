<?php
define('FIRST_BUCKET_CUTOFF',   4 * 24 * 60 * 60 * 1000000);
define('SECOND_BUCKET_CUTOFF', 14 * 24 * 60 * 60 * 1000000);
define('THIRD_BUCKET_CUTOFF',  31 * 24 * 60 * 60 * 1000000);
define('FOURTH_BUCKET_CUTOFF', 90 * 24 * 60 * 60 * 1000000);
define('NUMVISITS', 10);

define('VISIT_TYPE_NUM', 8);
define('CUTOFF_NUM', 5);

$visitTypeArr = array(1, 2, 3, 5, 6);

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
            $i = 4;
        }
        $coefficient[$i][$row['visit_type'] - 1] += $count;
    }
}
$db->close();
var_export($coefficient);

$c = array_fill(0, CUTOFF_NUM, 0);
$l = 0;
$c[$l] = 1;

$max[$l] = 0;
$maxOfAll = 0;

while (true) {
    $sum = 0;
    // $v = array_merge(array_fill(0, VISIT_TYPE_NUM, 0));
    $v = array_fill(0, VISIT_TYPE_NUM, 0);
    for ($j=0; $j < VISIT_TYPE_NUM; $j++) {
        for ($i=0; $i < CUTOFF_NUM; $i++) {
            $v[$j] += $c[$i] * $coefficient[$i][$j];
        }
    }

    $v = normalize($v);
    echo 'v = ' . json_encode($v) . PHP_EOL;

    $c = array_fill(0, CUTOFF_NUM, 0);
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

    if ($sum > $max[$l]) {
        $max[$l] = $sum;
        $maxV[$l] = $v;
        $maxC[$l] = $c;
        echo 'l = ' . $l . PHP_EOL;
        echo 'max = ' . $max[$l] . PHP_EOL;
    } else {
        if ($max[$l] > $maxOfAll) {
            $maxOfAll = $max[$l];
            $maxVOfAll = $maxV[$l];
            $maxCOfAll = $maxC[$l];
        }
        if (++$l < CUTOFF_NUM - 1) {
            $c = array_fill(0, CUTOFF_NUM, 0);
            $c[$l] = 1;
            $max[$l] = 0;
            continue;
        } else {
            break;
        }
    }
}

echo 'maxVOfAll = ' . json_encode($maxVOfAll) . PHP_EOL;
echo 'maxCOfAll = ' . json_encode($maxCOfAll) . PHP_EOL;

for ($i = 0; $i < VISIT_TYPE_NUM; $i++) {
    $maxVOfAll[$i] = round($maxVOfAll[$i] * 100);
}
for ($i = 0; $i < CUTOFF_NUM; $i++) {
    $maxCOfAll[$i] = round($maxCOfAll[$i] * 100);
}

echo <<<EOT

set! places.frecency.linkVisitBonus=$maxVOfAll[0]
set! places.frecency.typedVisitBonus=$maxVOfAll[1]
set! places.frecency.bookmarkVisitBonus=$maxVOfAll[2]
set! places.frecency.embedVisitBonus=$maxVOfAll[3]
set! places.frecency.permRedirectVisitBonus=$maxVOfAll[4]
set! places.frecency.tempRedirectVisitBonus=$maxVOfAll[5]
set! places.frecency.downloadVisitBonus=$maxVOfAll[6]

set! places.frecency.firstBucketWeight=$maxCOfAll[0]
set! places.frecency.secondBucketWeight=$maxCOfAll[1]
set! places.frecency.thirdBucketWeight=$maxCOfAll[2]
set! places.frecency.fourthBucketWeight=$maxCOfAll[3]
set! places.frecency.defaultBucketWeight=$maxCOfAll[4]

EOT;

function normalize($vec) {
    $len = sqrt(array_sum(array_map(function ($arr) {
        return $arr * $arr;
    }, $vec)));

    return array_map(function ($arr) use ($len) {
        return $arr / $len;
    }, $vec);
}
