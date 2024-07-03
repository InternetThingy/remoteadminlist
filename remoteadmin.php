<?php
function getEnvVar($var) {
    return getenv($var);
}

function fetchGoogleDocsData($identifier) {
    $url = "https://docs.google.com/spreadsheets/d/e/$identifier/pub?output=csv";
    $data = file_get_contents($url);
    if ($data === false) {
        return false;
    }
    return $data;
}

function fetchGoogleSheetsData($identifier, $sheet = null, $apiKey) {
    $url = "https://sheets.googleapis.com/v4/spreadsheets/$identifier?key=$apiKey";
    $jsonResponse = file_get_contents($url);
    if ($jsonResponse === false) {
        return false;
    }
    $json = json_decode($jsonResponse, true);
    if (isset($json['sheets'][0]['properties']['title']) && !$sheet) {
        return ['missing_sheet' => true];
    }
    return $json;
}

function fetchSheetValues($identifier, $sheet, $apiKey) {
    $url = "https://sheets.googleapis.com/v4/spreadsheets/$identifier/values/$sheet?key=$apiKey";
    $data = file_get_contents($url);
    if ($data === false) {
        return false;
    }
    return $data;
}

function processConfigData($data) {
    $lines = explode("\n", $data);
    array_shift($lines); // Remove the first line (headings)
    $spreadsheetIDs = [];
    $groups = [];
    $groupData = '';

    foreach ($lines as $line) {
        $line = str_getcsv($line);
        if (count($line) < 3) continue;
        $spreadsheetIDs[] = trim($line[0]);
        $groups[] = trim($line[1]);
        $groupData .= "Group=" . trim($line[1]) . ":" . trim($line[2]) . "\n";
    }

    return [$spreadsheetIDs, $groups, $groupData];
}

function processIDData($data, $groups) {
    $lines = explode("\n", $data);
    $firstLine = str_getcsv(array_shift($lines));
    $indexes = [
        'clan' => 0,
        'username' => 1,
        'group' => 2,
        'steamid' => 3
    ];

    foreach ($indexes as $key => $index) {
        $index = array_search($key, array_map('strtolower', $firstLine));
        if ($index !== false) {
            $indexes[$key] = $index;
        }
    }

    $userData = '';
    $adminCount = 0;
    $failedCount = 0;
    $failedLineNos = [];

    foreach ($lines as $lineNumber => $line) {
        $line = str_getcsv($line);
        if (!isset($line[$indexes['steamid']]) || !is_numeric($line[$indexes['steamid']]) || strpos($line[$indexes['steamid']], '7656') !== 0) {
            $failedLineNos[] = $lineNumber + 2;
            $failedCount++;
            continue;
        }
        if (!in_array($line[$indexes['group']], $groups)) {
            $failedLineNos[] = $lineNumber + 2;
            $failedCount++;
            continue;
        }
        $userData .= "Admin=" . trim($line[$indexes['steamid']]) . ":" . trim($line[$indexes['group']]) . " // " . trim($line[$indexes['clan']]) . " - " . trim($line[$indexes['username']]) . "\n";
        $adminCount++;
    }

    return [$userData, $adminCount, $failedCount, $failedLineNos];
}

function outputResults($adminCount, $failedCount, $failedLineNos, $groupData, $userData, $betterurl = null) {
    if ($betterurl) {
        echo "// Use this URL next time. It's faster:\n";
        echo "// " . $betterurl . "\n";
    }
    echo "// $adminCount admins loaded (Total: " . ($adminCount + $failedCount) . "). ($failedCount failed - failed line numbers (" . implode(",", $failedLineNos) . "))\n";
    echo $groupData;
    echo $userData;
}

$config = $_GET['config'] ?? null;
$id = $_GET['id'] ?? null;
$csheet = $_GET['csheet'] ?? null;
$isheet = $_GET['isheet'] ?? null;
$db = $_GET['DB'] ?? null;
$rc = $_GET['RC'] ?? null;

if (!$config || !$id) {
    echo "Placeholder for instructions\n";
    exit;
}

$apiKey = getEnvVar('API_KEY');
$missingSheet = false;

$configData = false;
$idData = false;

if (strlen($config) === 86) {
    $configData = fetchGoogleDocsData($config);
} elseif (strlen($config) === 44) {
    $configData = fetchGoogleSheetsData($config, $csheet, $apiKey);
} else {
    if ($db) {
        echo "// Invalid doc ID supplied ('config')\n";
    }
    exit;
}

if (strlen($id) === 86) {
    $idData = fetchGoogleDocsData($id);
} elseif (strlen($id) === 44) {
    $idData = fetchGoogleSheetsData($id, $isheet, $apiKey);
} else {
    if ($db) {
        echo "// Invalid doc ID supplied ('id')\n";
    }
    exit;
}

if ($db) {
    if ($configData) echo "// DB:lookup : " . str_replace($apiKey, "YOUR_API_KEY", "https://docs.google.com/spreadsheets/d/e/$config/pub?output=csv") . "\n";
    if ($idData) echo "// DB:lookup : " . str_replace($apiKey, "YOUR_API_KEY", "https://docs.google.com/spreadsheets/d/e/$id/pub?output=csv") . "\n";
}

if ($rc) {
    if ($configData) echo "// RC:lookup : " . json_encode($configData) . "\n";
    if ($idData) echo "// RC:lookup : " . json_encode($idData) . "\n";
}

if (is_array($configData) && isset($configData['missing_sheet'])) {
    $missingSheet = true;
    $configData = fetchSheetValues($config, $csheet, $apiKey);
}
if (is_array($idData) && isset($idData['missing_sheet'])) {
    $missingSheet = true;
    $idData = fetchSheetValues($id, $isheet, $apiKey);
}

list($spreadsheetIDs, $groups, $groupData) = processConfigData($configData);
list($userData, $adminCount, $failedCount, $failedLineNos) = processIDData($idData, $groups);

if ($missingSheet) {
    $betterurl = "https://remoteadminlist.com/remoteadmin.php?config=$config&id=$id&csheet=$csheet&isheet=$isheet";
    outputResults($adminCount, $failedCount, $failedLineNos, $groupData, $userData, $betterurl);
} else {
    outputResults($adminCount, $failedCount, $failedLineNos, $groupData, $userData);
}
?>
