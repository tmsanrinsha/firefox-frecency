<?php

define('NUMVISITS', 10);
define('FIRST_BUCKET_CUTOFF',   4 * 24 * 60 * 60 * 1000000);
define('SECOND_BUCKET_CUTOFF', 14 * 24 * 60 * 60 * 1000000);
define('THIRD_BUCKET_CUTOFF',  31 * 24 * 60 * 60 * 1000000);
define('FOURTH_BUCKET_CUTOFF', 90 * 24 * 60 * 60 * 1000000);

$bucketWeight = array(
    100,70, 50, 30, 10
    );
// http://builder.japan.zdnet.com/html-css/sp_firefox-3-for-developer-2008/20382307/
$visitTypeBonus = array(
    1 => 100,  // linkVisitBonus
    2 => 2000, // typedVisitBonus
    3 => 75,   // bookmarkVistiBonus
    4 => 0,    // embedVisitBonus
    5 => 0,    // permRedirectVisitBonus
    6 => 0,    // tempRedirectVisitBonus
    7 => 0     // downloadVisitBonus
);
// framedLinkVisitBonus
// unvisitedTypedBonus
// unvisitedBoookmarkBonus
// defaultVisitBonus

$db = new SQLite3(array_shift(glob('/Users/*/Library/Application Support/Firefox/Profiles/*/places.sqlite')));

$resultPlaces = $db->query("SELECT id, frecency FROM moz_places ORDER BY frecency DESC LIMIT 100");
while ($rowPlaces = $resultPlaces->fetchArray()) {
    $id = $rowPlaces['id'];
    $result = $db->query(
        "SELECT * " .
        "FROM moz_historyvisits " .
        "WHERE place_id   = " . $id . " " .
        "LIMIT " . NUMVISITS
    );
    $point[$id] = 0;
    $now = time() * 1000 * 1000;
    while ($row = $result->fetchArray()) {
        if ($row['visit_date'] > $now - FIRST_BUCKET_CUTOFF) {
            $point[$id] += $visitTypeBonus[$row['visit_type']] / 100 * $bucketWeight[0];
        } elseif ($row['visit_date'] > $now - SECOND_BUCKET_CUTOFF) {
            $point[$id] += $visitTypeBonus[$row['visit_type']] / 100 * $bucketWeight[1];
        } elseif ($row['visit_date'] > $now - THIRD_BUCKET_CUTOFF) {
            $point[$id] += $visitTypeBonus[$row['visit_type']] / 100 * $bucketWeight[2];
        } elseif ($row['visit_date'] > $now - FOURTH_BUCKET_CUTOFF) {
            $point[$id] += $visitTypeBonus[$row['visit_type']] / 100 * $bucketWeight[3];
        } else {
            $point[$id] += $visitTypeBonus[$row['visit_type']] / 100 * $bucketWeight[4];
        }
    }
    $result = $db->query(
        "SELECT count(*) as count " .
        "FROM moz_historyvisits " .
        "WHERE place_id   = " . $id
    );
    $row = $result->fetchArray();
    echo "point {$point[$id]}" . PHP_EOL;
    echo "count " . $row['count'] . PHP_EOL;
    echo "calced frecency " . $point[$id] * $row['count'] / 10 . PHP_EOL;
    echo "frecency " . $rowPlaces['frecency'] . PHP_EOL;
    echo "-----------------" . PHP_EOL;
}
$db->close();
