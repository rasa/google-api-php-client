<?php

// source: https://developers.google.com/google-apps/calendar/quickstart/php

require_once __DIR__ . '/vendor/autoload.php';

define('APPLICATION_NAME', 'Google Calendar API PHP Quickstart');
define('CREDENTIALS_PATH', __DIR__ . '/calendar-php-quickstart.json');
define('CLIENT_SECRET_PATH', __DIR__ . '/client_secret.json');
// If modifying these scopes, delete your previously saved credentials
// at ~/.credentials/calendar-php-quickstart.json
define('SCOPES', implode(' ', array(
  Google_Service_Calendar::CALENDAR_READONLY)
));

if (php_sapi_name() != 'cli') {
  throw new Exception('This application must be run on the command line.');
}

/**
 * Returns an authorized API client.
 * @return Google_Client the authorized client object
 */
function getClient() {
  $client = new Google_Client();
  $client->setApplicationName(APPLICATION_NAME);
  $client->setScopes(SCOPES);
  $client->setAuthConfig(CLIENT_SECRET_PATH);
  $client->setAccessType('offline');

  // Load previously authorized credentials from a file.
  $credentialsPath = expandHomeDirectory(CREDENTIALS_PATH);
  if (file_exists($credentialsPath)) {
    $accessToken = json_decode(file_get_contents($credentialsPath), true);
  } else {
    // Request authorization from the user.
    $authUrl = $client->createAuthUrl();
    printf("Open the following link in your browser:\n%s\n", $authUrl);
    print 'Enter verification code: ';
    $authCode = trim(fgets(STDIN));

    // Exchange authorization code for an access token.
    $accessToken = $client->fetchAccessTokenWithAuthCode($authCode);

    // Store the credentials to disk.
    if(!file_exists(dirname($credentialsPath))) {
      mkdir(dirname($credentialsPath), 0700, true);
    }
    file_put_contents($credentialsPath, json_encode($accessToken));
    printf("Credentials saved to %s\n", $credentialsPath);
  }
  $client->setAccessToken($accessToken);

  // Refresh the token if it's expired.
  if ($client->isAccessTokenExpired()) {
    $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
    file_put_contents($credentialsPath, json_encode($client->getAccessToken()));
  }
  return $client;
}

/**
 * Expands the home directory alias '~' to the full path.
 * @param string $path the path to expand.
 * @return string the expanded path.
 */
function expandHomeDirectory($path) {
  $homeDirectory = getenv('HOME');
  if (empty($homeDirectory)) {
    $homeDirectory = getenv('HOMEDRIVE') . getenv('HOMEPATH');
  }
  return str_replace('~', realpath($homeDirectory), $path);
}

// Get the API client and construct the service object.
$client = getClient();
$service = new Google_Service_Calendar($client);

/*
// see https://developers.google.com/google-apps/calendar/v3/reference/calendarList/list#examples
$calendarList = $service->calendarList->listCalendarList();

while(true) {
  foreach ($calendarList->getItems() as $calendarListEntry) {
    echo $calendarListEntry->getSummary();
	print_r($calendarListEntry->id);
	print "\n";
	#exit(8);
  }
  $pageToken = $calendarList->getNextPageToken();
  if ($pageToken) {
    $optParams = array('pageToken' => $pageToken);
    $calendarList = $service->calendarList->listCalendarList($optParams);
  } else {
    break;
  }
}

exit(0);
*/

// Print the next 10 events on the user's calendar.
# $calendarId = 'primary';
$calendarId = 'gk6r2q10s93lbkp45pince5nhc@group.calendar.google.com';

$optParams = array(
  'maxResults' => 2500,
  'orderBy' => 'startTime',
  'singleEvents' => TRUE,
  'timeMax' => date('c'),
);
$results = $service->events->listEvents($calendarId, $optParams);

$a = array();
$n = 0;

if (count($results->getItems()) > 0) {
  foreach ($results->getItems() as $event) {
    $start = $event->start->dateTime;
    if (empty($start)) {
      $start = $event->start->date;
    }
	$summary = $event->getSummary();
	$location = $event->getLocation();
	$key = strftime('%y-%m-%d', strtotime($start));
	$key = sprintf("%s-%03d", $key, $n++);
	$date = strftime('%d&#8209;%h&#8209;%y', strtotime($start));

	if (preg_match('/^(.*)\s+[A-Z]{3}$/', $summary, $m)) {
		$summary = trim($m[1]);
	}

	if (preg_match('/^(.*)\s*\d+:\d+[apAP][mM]?$/', $summary, $m)) {
		$summary = trim($m[1]);
	}

	if (preg_match('/^(.*)\s*\d+[apAP][mM]?$/', $summary, $m)) {
		$summary = trim($m[1]);
	}

	if (preg_match('/^(.*)\s*\d+$/', $summary, $m)) {
		$summary = trim($m[1]);
	}
	
	if (preg_match('/^(.*)\s*-\s*$/', $summary, $m)) {
		$summary = trim($m[1]);
	}
	
	if (preg_match('/^(.*)\([^\)]*\)\s*$/', $summary, $m)) {
		$summary = trim($m[1]);
	}
	if (preg_match('/^(.*)@/', $summary, $m)) {
		$summary = trim($m[1]);
	}
	
	if (preg_match('/^(.*),\s*United\s*States\s*$/', $location, $m)) {
		$location = trim($m[1]);
	}
	
	if (preg_match('/^(.*),\s*US\s*$/', $location, $m)) {
		$location = trim($m[1]);
	}

	if (preg_match('/^(.*)-\d{4}\s*$/', $location, $m)) {
		$location = trim($m[1]);
	}
	
	
	if (preg_match('/^(.*)\s*\d{5}\s*$/', $location, $m)) {
		$location = trim($m[1]);
	}
	
	$start_date = $date;
	$end_date = $date;
	$etag = $event->etag;
	$date_title = $start;
	if ($event->start->timeZone > '') {
		$date_title .= sprintf(' (%s)', $event->start->timeZone);
	}
	
	if (array_key_exists($etag, $a)) {
		$start_date = $a[$etag]['start_date'];
		$date = $a[$etag]['start_date'];
		$date_title = $a[$etag]['date_title'];
		$date = sprintf("%s&nbsp;&#8209; %s", $start_date, $end_date);
	}

	$a[$etag] = array(
		'date' => $date,
		'date_title' => $date_title,
		'description' => $event->getDescription(),
		'start_date' => $start_date,
		'end_date' => $end_date,
		'key' => $key,
		'location'	=> $location,
		'location_title'	=> $event->getLocation(),
		'location_url' => 'https://maps.google.com/maps?q=' . urlencode($location),
		'summary' => $summary,
		'summary_title' => $event->getSummary(),
		'summary_url' => $event->htmlLink,
	);
  }
}

function cmp($a, $b) {
    return ($a['key'] > $b['key']) ? -1 : 1;
}

usort($a, "cmp");

$h = <<<EOT
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8"/>
<style>
table, th, td {
	border: 1px solid gray;
    border-spacing: 0px;
</style>
</head>
<body>

EOT;

$h .= <<<EOT
<table style="width:100%; border: 0pt; padding: 0pt; font-size: 12pt" class="easy-table easy-table-default " border="0">
<thead>
<tr>
<th>Title</th>
<th>Location</th>
<th align='right'>Date</th>
</tr>
</thead>
<tbody>

EOT;

foreach ($a as $e) {
	$h .= "<tr>\n";
	$h .= sprintf("<td title='%s'><a target='_blank' href='%s'>%s</a></td>\n", 
		htmlspecialchars($e['summary_title'], ENT_HTML5 | ENT_QUOTES),
		$e['summary_url'],
		htmlspecialchars($e['summary'], ENT_HTML5 | ENT_QUOTES)
	);
	$h .= sprintf("<td title='%s'><a target='_blank' href='%s'>%s</a></td>\n", 
		htmlspecialchars($e['location_title'], ENT_HTML5 | ENT_QUOTES),
		$e['location_url'],
		htmlspecialchars($e['location'], ENT_HTML5 | ENT_QUOTES)
	);
	$h .= sprintf("<td title='%s' align='right'>%s</td>\n", 
		htmlspecialchars($e['date_title'], ENT_HTML5 | ENT_QUOTES),
		$e['date']
	);
	$h .= "</tr>\n";
}

$h .= "\n</tbody>\n</table>";

echo $h;
