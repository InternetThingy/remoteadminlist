<?php
header("Content-Type: text/plain");

function getEnvVar($var) {
    return getenv($var);
}

function fetchGoogleDocsData($identifier) {
    $url = "https://docs.google.com/spreadsheets/d/e/$identifier/pub?output=csv";
    $arrContextOptions = array(
        "ssl" => array(
            "verify_peer" => false,
            "verify_peer_name" => false,
        ),
    );
    $data = file_get_contents($url, false, stream_context_create($arrContextOptions));
    $responseCode = $http_response_header[0] ?? 'No response code';
    if ($data === false) {
        return [false, $url, $responseCode];
    }
    return [$data, $url, $responseCode];
}

function fetchGoogleSheetsData($identifier, $apiKey) {
    $url = "https://sheets.googleapis.com/v4/spreadsheets/$identifier?key=$apiKey";
    $arrContextOptions = array(
        "ssl" => array(
            "verify_peer" => false,
            "verify_peer_name" => false,
        ),
    );
    $jsonResponse = file_get_contents($url, false, stream_context_create($arrContextOptions));
    $responseCode = $http_response_header[0] ?? 'No response code';
    if ($jsonResponse === false) {
        return [false, $url, $responseCode];
    }
    $json = json_decode($jsonResponse, true);
    if (isset($json['sheets'][0]['properties']['title'])) {
        return [$json['sheets'][0]['properties']['title'], $url, $responseCode];
    }
    return [false, $url, $responseCode];
}

function fetchSheetValues($identifier, $sheet, $apiKey) {
    $url = "https://sheets.googleapis.com/v4/spreadsheets/$identifier/values/$sheet?key=$apiKey";
    $arrContextOptions = array(
        "ssl" => array(
            "verify_peer" => false,
            "verify_peer_name" => false,
        ),
    );
    $data = file_get_contents($url, false, stream_context_create($arrContextOptions));
    $responseCode = $http_response_header[0] ?? 'No response code';
    if ($data === false) {
        return [false, $url, $responseCode];
    }
    return [$data, $url, $responseCode];
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
$db = isset($_GET['DB']) && $_GET['DB'] === 'true';
$rc = isset($_GET['RC']) && $_GET['RC'] === 'true';

if (!$config || !$id) {
    echo "Placeholder for instructions\n";
    exit;
}

$apiKey = getEnvVar('API_KEY');
$missingSheet = false;

$configData = false;
$idData = false;
$configUrl = '';
$idUrl = '';
$configResponseCode = '';
$idResponseCode = '';

if (strlen($config) === 86) {
    list($configData, $configUrl, $configResponseCode) = fetchGoogleDocsData($config);
} elseif (strlen($config) === 44) {
    if (!$csheet) {
        list($csheet, $configUrl, $configResponseCode) = fetchGoogleSheetsData($config, $apiKey);
        if ($csheet === false) {
            echo "Failed to retrieve the sheet name for config.\n";
            exit;
        }
        $missingSheet = true;
    }
    list($configData, $configUrl, $configResponseCode) = fetchSheetValues($config, $csheet, $apiKey);
} else {
    if ($db) {
        echo "// Invalid doc ID supplied ('config')\n";
    }
    exit;
}

if (strlen($id) === 86) {
    list($idData, $idUrl, $idResponseCode) = fetchGoogleDocsData($id);
} elseif (strlen($id) === 44) {
    if (!$isheet) {
        list($isheet, $idUrl, $idResponseCode) = fetchGoogleSheetsData($id, $apiKey);
        if ($isheet === false) {
            echo "Failed to retrieve the sheet name for id.\n";
            exit;
        }
        $missingSheet = true;
    }
    list($idData, $idUrl, $idResponseCode) = fetchSheetValues($id, $isheet, $apiKey);
} else {
    if ($db) {
        echo "// Invalid doc ID supplied ('id')\n";
    }
    exit;
}

if ($db) {
    if ($configUrl) echo "// DB:lookup : " . str_replace($apiKey, "YOUR_API_KEY", $configUrl) . "\n";
    if ($idUrl) echo "// DB:lookup : " . str_replace($apiKey, "YOUR_API_KEY", $idUrl) . "\n";
}

if ($rc) {
    if ($configResponseCode) echo "// RC:lookup : $configResponseCode\n";
    if ($idResponseCode) echo "// RC:lookup : $idResponseCode\n";
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
