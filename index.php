<?php

/*
 * This script read from json file and parses the data to Google spreadsheet
 * Google spreadsheet url: 
 * https://docs.google.com/spreadsheets/d/1gYa4ajBcEZuRQ0TMWoyVNAySOInVDLD5UaKxeJi3jyQ/edit#gid=0
 */

require_once __DIR__ . '/vendor/autoload.php';
 
if (php_sapi_name() != 'cli') {
    throw new Exception('try running "php index.php" from command line.');
}
 
// This Sheet MUST BE shared with service account email
define('SHEET_ID', '1gYa4ajBcEZuRQ0TMWoyVNAySOInVDLD5UaKxeJi3jyQ');
define('APPLICATION_NAME', 'Manipulating Google Sheets from PHP');
define('CLIENT_SECRET_PATH', __DIR__ . '/google_spreadsheet.json');
define('ACCESS_TOKEN', '95ca66f2dd10c89a55246d98813e0a5f5aab2553');
define('SCOPES', implode(' ', [Google_Service_Sheets::SPREADSHEETS]));
 
// Create Google API Client
$client = new Google_Client();
$client->setApplicationName(APPLICATION_NAME);
$client->setScopes(SCOPES);
$client->setAuthConfig(CLIENT_SECRET_PATH);
$client->setAccessToken(ACCESS_TOKEN);
 
//clearing the spreadsheet if any value exists.
$service = new Google_Service_Sheets($client);
$sheetInfo = $service->spreadsheets->get(SHEET_ID)->getProperties();
$requestBody = new Google_Service_Sheets_ClearValuesRequest();
$response = $service->spreadsheets_values->clear(SHEET_ID, 'A1:i13', $requestBody);

/* Reading json file content*/
$json_content = file_get_contents('input.json');
$parsed_data = json_decode($json_content, true);
if (json_last_error()) {
    echo "Error Occured while parsing the json string. Please make sure to use valid json data";
}

$options = array('valueInputOption' => 'RAW');
$permissions = array('view_grades', 'change_grades', 'add_grades', 'delete_grades', 'view_classes',
    'change_classes', 'add_classes', 'delete_classes');
$header = array_merge( array(' '), $permissions);

/* Parsing the output*/
$data[] = $header;
foreach ($parsed_data as $role => $role_permissions) {  
    $values[] = $role;
    foreach ($permissions as $key=>$permission) { 
        if (in_array($permission, $role_permissions)) {
            $values[] = 1;
        } else {
            $values[] = 0;
        }      
    } 
    $data[] = $values;
    $values = array();
}

/*writing values to google spreadsheet*/
$body   = new Google_Service_Sheets_ValueRange(array('values' => $data));
$result = $service->spreadsheets_values->update(SHEET_ID, 'A1:i5', $body, $options);
print($result->updatedRange. PHP_EOL);