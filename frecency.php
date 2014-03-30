<?php
// require_once 'GaussianElimination.php';

define('FIRST_BUCKET_CUTOFF',   4 * 24 * 60 * 60 * 1000);
define('SECOND_BUCKET_CUTOFF', 14 * 24 * 60 * 60 * 1000);
define('THIRD_BUCKET_CUTOFF',  31 * 24 * 60 * 60 * 1000);
define('FOURTH_BUCKET_CUTOFF', 90 * 24 * 60 * 60 * 1000);
define('NUMVISITS', 10);

$cutoff = array(0, FIRST_BUCKET_CUTOFF, SECOND_BUCKET_CUTOFF, THIRD_BUCKET_CUTOFF, FOURTH_BUCKET_CUTOFF, null);
$cutoffNum = count($cutoff) - 1;
$visitTypeArr = array(1, 2, 3, 5, 6);
$visitTypeNum = count($visitTypeArr);

goto sqlEnd;
// 履歴データの取得
$db = new SQLite3(array_shift(glob('/Users/*/Library/Application Support/Firefox/Profiles/*/places.sqlite')));

// ロケーションバーもしくは、Vimperatorの:openなどから選択されて表示された履歴のデータの取得
$selectedVisitData = $db->query(
    "SELECT place_id, visit_date " .
    "FROM moz_historyvisits " .
    "WHERE (from_visit = 0 AND visit_type = 1) OR visit_type = 2"
);

// for ($i = 0; $i < $cutoffNum -1; $i++) {
for ($i = 0; $i < $cutoffNum; $i++) {
    for ($j = 0; $j < $visitTypeNum; $j++) {
        $coefficient[$i][$j] = 0;
    }
}

// 取得したデータから当時のfrecencyを計算するために必要なデータを取得する
while ($selectedVisitRow = $selectedVisitData->fetchArray()) {
    $cutoff[$cutoffNum] = $selectedVisitRow['visit_date'];
    $limit = NUMVISITS;
    for ($i = 0; $i < $cutoffNum; $i++) {
        // for ($visit_type = 1; $visit_type <= 8; $visit_type++) {
        foreach ($visitTypeArr as $j => $visitType) {
            $result = $db->query(
                "SELECT count(*) AS count " .
                "FROM moz_historyvisits " .
                "WHERE place_id   = " . $selectedVisitRow['place_id'] . " " .
                  "AND visit_date < " . ($selectedVisitRow['visit_date'] - $cutoff[$i]) . " " .
                  "AND visit_date > " . ($selectedVisitRow['visit_date'] - $cutoff[$i + 1]) . " " .
                  "AND visit_type = " . $visitType .
                  "LIMIT $limit"
            );
            $row = $result->fetchArray();
            $coefficient[$i][$j] += $row['count'];
        }
    }
}
$db->close();
var_export($coefficient);

sqlEnd:

$coefficient = array (
  0 => 
  array (
    0 => 2517,
    1 => 111,
    2 => 23,
    3 => 56,
    4 => 47,
  ),
  1 => 
  array (
    0 => 807,
    1 => 39,
    2 => 2,
    3 => 19,
    4 => 28,
  ),
  2 => 
  array (
    0 => 509,
    1 => 45,
    2 => 1,
    3 => 26,
    4 => 15,
  ),
  3 => 
  array (
    0 => 593,
    1 => 75,
    2 => 2,
    3 => 36,
    4 => 14,
  ),
  4 => 
  array (
    0 => 156951,
    1 => 30863,
    2 => 3221,
    3 => 15940,
    4 => 3421,
  ),
);


/*
$max = 0;
$maxC = array();
$maxV = array();

$countC = array_fill(0, $cutoffNum, 0);
$countV = array_fill(0, $visitTypeNum, 0);

while (true) { // for cutoff
    // 極座標の設定
    for ($i=0; $i < $cutoffNum; $i++) {
        $c[$i] = 1;

        for ($j=0; $j < $i; $j++) { 
            $c[$i] *= sin(M_PI / (2 * 10) * $countC[$j]);
        }

        if ($i === $cutoffNum - 1) {
            $c[$i] *= sin(M_PI / (2 * 10) * $countC[$i]);
        } else {
            $c[$i] *= cos(M_PI / (2 * 10) * $countC[$i]);
        }
    }

    while (true) { // for visit_type

        for ($i=0; $i < $visitTypeNum; $i++) {
            $v[$i] = 1;

            for ($j=0; $j < $i; $j++) { 
                $v[$i] *= sin(M_PI / (2 * 10) * $countV[$j]);
            }

            if ($i === $visitTypeNum - 1) {
                $v[$i] *= sin(M_PI / (2 * 10) * $countV[$i]);
            } else {
                $v[$i] *= cos(M_PI / (2 * 10) * $countV[$i]);
            }
        }

        $sum = 0;
        for ($i=0; $i < $cutoffNum; $i++) { 
            for ($j=0; $j < $visitTypeNum; $j++) { 
                $sum += $coefficient[$i][$j] * $c[$i] * $v[$j];
            }
        }

        printV('countV');
        printV('v');
        printV('c');
        printV('maxC');
        printV('maxV');
        echo 'sum' . PHP_EOL;
        var_dump($sum);
        echo 'max' . PHP_EOL;
        sleep(1);

        if ($sum > $max) {
            $max = $sum;
            $maxC = $c;
            $maxV = $v;
            echo 'max';
            var_dump($max);
            echo 'maxC';
            var_dump($maxC);
            echo 'maxV';
            var_dump($maxV);
        }

        $j = 0;
        while (true) {
            if ($countV[$j] < 10) {
                $countV[$j]++;
                break;
            } elseif($j < $visitTypeNum - 1) {
                $countV[$j] = 0;
                $j++;
                continue;
            } else {
                $countV[$j] = 0;
                break 2;
            }
        }
    }

    $j = 0;
    while (true) {
        if ($countC[$j] < 10) {
            $countC[$j]++;
            break;
        } elseif($j < $cutoffNum - 1) {
            $countC[$j] = 0;
            $j++;
            var_dump($countC);
            var_dump($countV);
            continue;
        } else {
            $countC[$j] = 0;
            break 2;
        }
    }
}
echo 'end';
*/

$c = array_merge(array(1), array_fill(0, $cutoffNum - 1, 0));
$max = 0;

while (true) {
    $sum = 0;
    $v = array_merge(array_fill(0, $visitTypeNum, 0));
    for ($j=0; $j < $visitTypeNum; $j++) {
        for ($i=0; $i < $cutoffNum; $i++) {
            $v[$j] += $c[$i] * $coefficient[$i][$j];
        }
    }

    $v = normalize($v);
    echo 'v = ' . json_encode($v) . PHP_EOL;

    $c = array_merge(array_fill(0, $cutoffNum, 0));
    for ($i=0; $i < $cutoffNum; $i++) {
        for ($j=0; $j < $visitTypeNum; $j++) {
            $c[$i] += $coefficient[$i][$j] * $v[$j];
        }
    }

    $c = normalize($c);
    echo 'c = ' . json_encode($c) . PHP_EOL;

    $sum = 0;
    for ($i=0; $i < $cutoffNum; $i++) { 
        for ($j=0; $j < $visitTypeNum; $j++) { 
            $sum += $coefficient[$i][$j] * $c[$i] * $v[$j];
        }
    }

    if ($sum > $max) {
        $max = $sum;
        echo 'max = ' . $max . PHP_EOL;
    } else {
        break;
    }

    sleep(1);
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

/* Gaussianeliminationでやったやつ
$coefficientVisit = array();
foreach ($coefficient as $i => $row) {
    if ($i === 1) {
        $coefficientVisit[] = array_merge($row, array(-1, 0));
    } else {
        $coefficientVisit[] = array_merge($row, array(0, 0));
    }
    // $coefficientVisit[] = array_merge($row, array(-1, 0));
    // $coefficientVisit[] = array_merge($row, array(0));
}
// $coefficientVisit[] = array_merge(array_fill(0, $visitTypeNum, 1), array(0, 100));
// $coefficientVisit[] = array_merge(array(0, 0, 0, 1, 0, 0, 1));
$coefficientVisit[] = array_merge(array(0, 1, 0, 0, 0, 0, 10));

var_dump($coefficientVisit);
$ge = new GaussianElimination($coefficientVisit);
var_dump($ge->solve());
 */
