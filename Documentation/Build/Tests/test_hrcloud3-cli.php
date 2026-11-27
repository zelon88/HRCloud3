<?php
// HRCloud3 v1.0.0. HRCloud3's acceptance test. It installs HRCloud3 & runs it as an administrator & a user would.
//
//   cd /path/to/HRCloud3
//   sudo bash Documentation/Build/Tests/contained-install.sh php Documentation/Build/Tests/test_hrcloud3-cli.php
// Run it from the root of the HRCloud3 tree, inside contained-install.sh, where /DATA & /var/www/html are memory made for the run.
//
// It is the Engine's test_starter-cli.php, held to what HRCloud3's contract says rather than to the Starter's.
// It copies the tree to /var/www/html/HRProprietary/HRCloud3, where HRCloud3's contract installs it, & runs cloudCore.php there as
// root, each command a process of its own without a shell: --config --repair, -v, -h, an --app argument, & -fp, which installs it
// into /DATA/HRCloud3/Application & publishes the loader & the interface's assets. Then it runs the installed copy & the loader, manages
// an account from the command line, & from v0.9.9 sends the loader web requests through php-cgi as the web server account: the sign in
// page, signing in, home, settings, changing the address & the password, the log of what each page was built from, the component
// markers & a base interface of the wrong version. From v1.0.0 it asks the libraries every action a page's script asks, as an
// administrator & as a user who is not, through multipart forms & ranges as Apache would hand them over. It removes the accounts, with
// their spaces, & runs the installed copy in strict contract mode with its contract whole & with two values left out.
// It reads every version it checks from the tree's own files.
// Mail is handed to a stand-in in the scratch folder that keeps every message, so nothing leaves the machine.
// It removes nothing of its own. config.php, the contract, the base interface's version file & a trash record are changed only in the
// copy in memory, & each is put back as it was. --account --remove removes the accounts the suite made, which is what that command is for,
// & the Storage Core removes what the suite asks it to, in the libraries in memory.

if (PHP_SAPI !== 'cli') { if (!headers_sent()) http_response_code(404); die(); }

if (!is_file('Documentation/Build/Tests/testlib-cli.php')) { fwrite(STDERR, 'Run this from the root of the application it tests. See Documentation/Build/Tests/README.txt.'.PHP_EOL); exit(64); }
require 'Documentation/Build/Tests/testlib-cli.php';

$tree = testTree(isset($argv[1]) ? $argv[1] : (string)getcwd());
if (getenv('HRP_INSTALL') !== '1') testStop('This suite installs HRCloud3, so it runs only inside contained-install.sh. See Documentation/Build/Tests/README.txt.');
requireContainment();
if (!is_file($tree.'/cloudCore.php') or strpos((string)@file_get_contents($tree.'/Resources/Contract/app-engine-config.php'), "\$EngineApplicationName = 'HRCloud3';") === FALSE) testStop($tree.' is not HRCloud3. Run this from the root of the HRCloud3 tree.');

// The versions under test, as the tree's own files say them, so a release that changes nothing a check reads needs no change here.
// A component's stamp is the version its header says it was last changed in, which the Page Core reads as readFileStamp() does.
$versionIn = function ($relativePath, $variableName) use ($tree) {
  return (preg_match('/\\$'.$variableName.' = \'v([0-9]+\.[0-9]+\.[0-9]+)\';/', (string)@file_get_contents($tree.'/'.$relativePath), $versionMatch) === 1) ? $versionMatch[1] : ''; };
$release = $versionIn('Resources/versionInfo.php', 'Version');
$configVersion = $versionIn('Resources/Contract/config-template.php', 'ConfigVersion');
$interfaceVersion = $versionIn('UI/Default/uiVersionInfo.php', 'InterfaceVersion');
$languageVersion = $versionIn('UI/Default/Languages/en/languageStrings.php', 'LanguageVersion');
if ($release === '' or $configVersion === '' or $interfaceVersion === '' or $languageVersion === '') testStop('The tree does not say which versions it is.');
$stampOf = function ($relativePath) use ($tree) {
  return (preg_match('/^[ \t]*(?:\/\/ \/|\/\/|\*|#|<!--)?[ \t]*v([0-9]+\.[0-9]+\.[0-9]+(?:u?r)?)\.[ \t]*(?:-->)?[ \t]*\r?$/m', (string)@file_get_contents($tree.'/UI/Default/'.$relativePath, FALSE, NULL, 0, 8192), $stampMatch) === 1) ? $stampMatch[1] : 'unstamped'; };

$hosted = mustBeInstallFolder('/var/www/html/HRProprietary/HRCloud3');
$installed = mustBeInstallFolder('/DATA/HRCloud3/Application');
makeFolder(scratchPath('hrcloud3'), 0700);

// Runs cloudCore.php in the folder named, as root, with the arguments named. Gives back its exit status & everything it said, in that order.
function hrcloud3($folderPath, $arguments) {
  if (!chdir($folderPath)) testStop($folderPath.' could not be entered.');
  return runCommand(array_merge(array(PHP_BINARY, 'cloudCore.php'), $arguments)); }

// Says whether PHP itself raised anything while a command ran, which no command of the Engine's should ever print.
function phpComplained($said) {
  return preg_match('/^(PHP )?(Fatal error|Warning|Notice|Deprecated|Parse error): /m', $said) === 1; }

// Says whether what a command said names an example of the kit's, which nothing said for HRCloud3 should.
function namesAnExample($said) {
  return stripos($said, 'HRConvert2') !== FALSE or strpos($said, 'convertCore') !== FALSE or strpos($said, 'Starter') !== FALSE or strpos($said, 'app.php') !== FALSE; }

// Says whether every line named is in what a command said.
function allIn($expectedLines, $said) {
  foreach ($expectedLines as $expectedLine) if (strpos($said, $expectedLine) === FALSE) return FALSE;
  return TRUE; }

// Writes a file of the installation in memory, which a check changes & puts back.
function rewriteInstalled($filePath, $fileText) {
  mustBeInstallFolder($filePath);
  if (file_put_contents($filePath, $fileText) !== strlen($fileText)) testStop($filePath.' could not be written.');
  clearstatcache();
  return TRUE; }

// Says whether a logfile root wrote in HRCloud3's Engine folder holds every line named. Root logs there & nowhere else.
function engineLogSays($expectedLines) {
  foreach (namesIn('/DATA/HRCloud3/Engine') as $logName) {
    $logPath = '/DATA/HRCloud3/Engine/'.$logName;
    if (!is_link($logPath) && is_file($logPath) && allIn($expectedLines, (string)file_get_contents($logPath))) return TRUE; }
  return FALSE; }

// Lists the names in a folder, in order.
function namesIn($folderPath) {
  $folderNames = @scandir($folderPath);
  if (!is_array($folderNames)) return array('<not a folder>');
  $folderNames = array_values(array_diff($folderNames, array('.', '..')));
  sort($folderNames);
  return $folderNames; }

// Lists every name beneath a folder, at any depth.
function namesBeneath($folderPath) {
  $foundNames = array();
  foreach (namesIn($folderPath) as $entryName) {
    if ($entryName === '<not a folder>') continue;
    $foundNames[] = $entryName;
    if (is_dir($folderPath.'/'.$entryName) && !is_link($folderPath.'/'.$entryName)) $foundNames = array_merge($foundNames, namesBeneath($folderPath.'/'.$entryName)); }
  return $foundNames; }

// Says whether a logfile the web server account wrote holds every line named. A web request logs there & nowhere else.
function webLogSays($expectedLines) {
  foreach (namesIn('/DATA/HRCloud3/Logs') as $logName) {
    $logPath = '/DATA/HRCloud3/Logs/'.$logName;
    if (!is_link($logPath) && is_file($logPath) && allIn($expectedLines, (string)file_get_contents($logPath))) return TRUE; }
  return FALSE; }

// Gives back every line a logfile the web server account wrote that holds the text named, so a failed check can show what was logged.
function webLogLinesWith($expectedText) {
  $foundLines = array();
  foreach (namesIn('/DATA/HRCloud3/Logs') as $logName) {
    $logPath = '/DATA/HRCloud3/Logs/'.$logName;
    if (!is_link($logPath) && is_file($logPath)) foreach (explode("\n", (string)file_get_contents($logPath)) as $logLine) if (strpos($logLine, $expectedText) !== FALSE) $foundLines[] = $logLine; }
  return implode("\n", $foundLines); }

// Sends the loader one request through php-cgi as the web server account, as Apache would hand it over, & with mail handed to the
// stand-in. Gives back the status, the headers, the page & whatever PHP wrote to its own error stream, in that order.
function webRequest($method, $query, $fields) {
  return webExchange($method, $query, 'application/x-www-form-urlencoded', ($method === 'POST') ? http_build_query($fields) : '', array(), array()); }

// Sends the loader a form with files through php-cgi as webRequest() does, from v1.0.0, as a page's script uploads them. Each file is a
// name & its contents, & every one is sent as StorageUpload[]. The further variables Apache would set, such as a Range header, & the
// further PHP settings, such as output compression, are given as they are written. Gives back what webRequest() gives back.
function webForm($fields, $files, $extraEnvironment, $phpSettings) {
  $boundary = '----hrc3'.bin2hex(random_bytes(8));
  $body = '';
  foreach ($fields as $fieldName => $fieldValue) $body .= '--'.$boundary."\r\nContent-Disposition: form-data; name=\"".$fieldName."\"\r\n\r\n".$fieldValue."\r\n";
  foreach ($files as $fileRecord) $body .= '--'.$boundary."\r\nContent-Disposition: form-data; name=\"StorageUpload[]\"; filename=\"".str_replace('"', '%22', $fileRecord[0])."\"\r\n"
    ."Content-Type: application/octet-stream\r\n\r\n".$fileRecord[1]."\r\n";
  $body .= '--'.$boundary."--\r\n";
  return webExchange('POST', '', 'multipart/form-data; boundary='.$boundary, $body, $extraEnvironment, $phpSettings); }

// Hands php-cgi one request as the web server account, with the body & the content type given, & gives back what webRequest() does.
function webExchange($method, $query, $contentType, $body, $extraEnvironment, $phpSettings) {
  global $hosted, $mailStandIn;
  $environment = array_merge(array('REDIRECT_STATUS=200', 'GATEWAY_INTERFACE=CGI/1.1', 'REQUEST_METHOD='.$method, 'QUERY_STRING='.$query, 'SCRIPT_FILENAME='.$hosted.'/cloudCore.php',
    'SCRIPT_NAME=/HRProprietary/HRCloud3/cloudCore.php', 'REQUEST_URI=/HRProprietary/HRCloud3/cloudCore.php'.($query === '' ? '' : '?'.$query), 'SERVER_NAME=localhost',
    'SERVER_PORT=80', 'REMOTE_ADDR=127.0.0.1', 'HTTP_HOST=localhost', 'HTTP_USER_AGENT=test_hrcloud3', 'SERVER_PROTOCOL=HTTP/1.1',
    'CONTENT_TYPE='.$contentType, 'CONTENT_LENGTH='.strlen($body)), $extraEnvironment);
  $settings = array('-d', 'sendmail_path='.$mailStandIn);
  foreach ($phpSettings as $phpSetting) array_push($settings, '-d', $phpSetting);
  $pipes = array();
  $process = proc_open(array_merge(array('runuser', '-u', 'www-data', '--', 'env', '-i'), $environment, array('php-cgi'), $settings),
    array(0 => array('pipe', 'r'), 1 => array('pipe', 'w'), 2 => array('pipe', 'w')), $pipes);
  if (!is_resource($process)) testStop('php-cgi could not be started as the web server account.');
  fwrite($pipes[0], $body);
  fclose($pipes[0]);
  $answer = (string)stream_get_contents($pipes[1]);
  $complaint = (string)stream_get_contents($pipes[2]);
  fclose($pipes[1]);
  fclose($pipes[2]);
  proc_close($process);
  $split = strpos($answer, "\r\n\r\n");
  $gap = 4;
  if ($split === FALSE) list ($split, $gap) = array(strpos($answer, "\n\n"), 2);
  $headers = ($split === FALSE) ? '' : substr($answer, 0, $split);
  $page = ($split === FALSE) ? $answer : substr($answer, $split + $gap);
  $status = (preg_match('/^Status: ([0-9]{3})/mi', $headers, $statusMatch) === 1) ? (int)$statusMatch[1] : 200;
  return array($status, $headers, $page, $complaint); }

// Names the outcome an answer to one of the Engine's actions gives, or an empty string.
function outcomeIn($page) {
  return (preg_match('/"Outcome":"([a-z-]+)"/', $page, $outcomeMatch) === 1) ? $outcomeMatch[1] : ''; }

// Hands back the key an answer gives, which signing in & a trade both do, or an empty string.
function keyIn($page) {
  return (preg_match('/"Outcome":"(?:signed-in|exchanged)","Key":"([a-f0-9]{96})"/', $page, $keyMatch) === 1) ? $keyMatch[1] : ''; }

// Trades a key for a new one, as a page does on its timer, so a key the suite holds never outlives its life between checks.
function tradedKey($signInKey) {
  list ($status, $headers, $page, $complaint) = webRequest('POST', '', array('SignInAction' => 'exchange', 'SignInKey' => $signInKey));
  return keyIn($page); }

// The last code the mail stand-in was handed, or an empty string.
function lastMailedCode() {
  preg_match_all('/ is ([A-HJ-NP-Z2-9]{10})\b/', (string)file_get_contents($GLOBALS['mailbox']), $codeMatches);
  return (count($codeMatches[1]) > 0) ? (string)end($codeMatches[1]) : ''; }

// The rows -v prints for each tier & each of the Engine's cores, as HRCloud3 pins them. The Engine & the cores HRCloud3 changed carry ur.
$tierRows = array("HRCloud3 ".$release."\n", "  Kernel     4.3.3, requires 4.3.3\n", "  Engine     4.3.4ur, requires 4.3.4ur\n",
  "             & its Environment Core 4.3.3, requires 4.3.3\n", "  Config     ".$configVersion.", requires ".$configVersion." or later\n");
$coreRows = array("  Setup Core                  OK, 4.3.3ur\n", "  Dependency Core             OK, 4.3.4\n", "  Pipeline Core               OK, 4.3.2\n",
  "  Workflow Core               OK, 4.3.1\n", "  Authentication Core         OK, 4.2.3ur\n", "  Email Core                  OK, 4.2.0ur\n", "  Page Core                   OK, 4.3.4ur\n",
  "  Storage Core                OK, 4.3.4ur\n", "  Event Core                  OK, 4.3.1\n");
$noPipelines = "  Pipelines                   NONE, this application accepts none\n";
$interfaceRows = array("Installed interfaces\n  Default                     OK, ".$interfaceVersion.", the base\n", "Language packs\n  Default                     1 of 1 OK\n");

list ($madeStatus, $madeSaid) = runCommand(array('mkdir', '-p', $hosted));
list ($copyStatus, $copySaid) = runCommand(array('cp', '-a', $tree.'/.', $hosted.'/'));
if ($madeStatus !== 0 or $copyStatus !== 0) testStop('HRCloud3 could not be copied to '.$hosted.'. '.$madeSaid.$copySaid);
if (file_exists($hosted.'/Resources/config.php')) testStop('The tree holds a config.php, which no release ships. Test a tree without one.');


// Part 1. --config --repair writes config.php from the two halves of the template, with HRCloud3's defaults.
list ($status, $said) = hrcloud3($hosted, array('--config', '--repair'));
check('--config --repair writes config.php from both halves of the template', $status === 0 && strpos($said, 'No config.php was found. Writing the defaults from the template.') !== FALSE
  && preg_match('/Wrote [0-9]+ setting\(s\), all defaults\./', $said) === 1 && !phpComplained($said), $said);
$configText = (string)@file_get_contents($hosted.'/Resources/config.php');
check('config.php holds HRCloud3\'s version, name, folders & update source', strpos($configText, "\$ConfigVersion = 'v".$configVersion."';") !== FALSE
  && strpos($configText, "\$ConvertLoc = '/DATA/HRCloud3';") !== FALSE && strpos($configText, "\$InstLoc = '/var/www/html/HRProprietary/HRCloud3';") !== FALSE
  && strpos($configText, "\$ApplicationName = 'HRCloud3';") !== FALSE && strpos($configText, "\$UpdateSourceRepository = 'zelon88/HRCloud3';") !== FALSE, $configText);
check('accounts are on, every visitor signs in & only an administrator makes an account, by default', strpos($configText, "\$EnableAuthentication = TRUE;") !== FALSE
  && strpos($configText, "\$AllowAnonymousVisitors = FALSE;") !== FALSE && strpos($configText, "\$AllowRegistration = FALSE;") !== FALSE, $configText);
check('config.php holds nothing of the Starter\'s', strpos($configText, 'Greeting') === FALSE && !namesAnExample($configText));
check('the interface is Default in English, users may choose, & no component is marked, by default', strpos($configText, "\$SupportedInterfaces = array('Default');") !== FALSE
  && strpos($configText, "\$DefaultInterface = 'Default';") !== FALSE && strpos($configText, "\$SupportedLanguages = array('en' => 'English');") !== FALSE
  && strpos($configText, "\$DefaultColor = 'blue';") !== FALSE && strpos($configText, "\$AllowUserSelectableColor = TRUE;") !== FALSE
  && strpos($configText, "\$MarkPageComponents = FALSE;") !== FALSE, $configText);


// Part 2. -v, before HRCloud3 is installed.
list ($status, $said) = hrcloud3($hosted, array('-v'));
check('-v names HRCloud3 & each tier, at the version each pin requires', $status === 0 && allIn($tierRows, $said), $said);
check('each of the Engine\'s cores is the version HRCloud3 pins', allIn($coreRows, $said), $said);
check('-v judges the Default interface & its English pack', allIn($interfaceRows, $said), $said);
check('HRCloud3 accepts no pipeline, & nothing about that is a warning, an error or a failure', strpos($said, $noPipelines) !== FALSE
  && strpos($said, '34001') === FALSE && preg_match('/^(WARNING|ERROR)!!! .*accepts no pipeline/m', $said) === 0, $said);
check('-v says nothing of the kit\'s examples, & PHP raised nothing', !namesAnExample($said) && !phpComplained($said), $said);


// Part 3. -h, which the Engine prints & HRCloud3 adds to.
list ($status, $said) = hrcloud3($hosted, array('-h'));
check('-h describes HRCloud3, names cloudCore.php & its repository', $status === 0
  && strpos($said, "HRCloud3 ".$release."\nA home-hosted cloud storage platform built on the HRProprietary Engine.") !== FALSE
  && strpos($said, "  php cloudCore.php [argument]\n") !== FALSE && strpos($said, 'https://github.com/zelon88/HRCloud3') !== FALSE, $said);
check('-h lists the Engine\'s commands, the account commands among them, & no command of HRCloud3\'s own', strpos($said, '  -fp, --fix-permissions') !== FALSE
  && strpos($said, '--account') !== FALSE && strpos($said, '  --app-') === FALSE, $said);
check('-h lists HRCloud3\'s documents', strpos($said, 'Documentation/CHANGELOG.txt') !== FALSE && strpos($said, 'Documentation/ABOUT_AUTHENTICATION.txt') !== FALSE
  && strpos($said, 'Documentation/ABOUT_PAGES.txt') !== FALSE && strpos($said, 'Documentation/CREATING_INTERFACES.txt') !== FALSE, $said);
check('-h says nothing of the kit\'s examples, & PHP raised nothing', !namesAnExample($said) && !phpComplained($said), $said);


// Part 4. HRCloud3 has no command of its own, so the kernel refuses every argument beginning --app.
list ($status, $said) = hrcloud3($hosted, array('--app-nothing'));
check('an argument beginning --app is refused with status 2, & PHP raised nothing', $status === 2 && strpos($said, '--app-nothing') !== FALSE && !phpComplained($said), $said);


// Part 5. -fp installs HRCloud3 into the data location & publishes the loader & the interface's assets.
list ($status, $said) = hrcloud3($hosted, array('-fp'));
check('-fp installs HRCloud3 into Application in the data location, & runs the installed copy\'s -fp', $status === 0
  && preg_match('#  Installed   Installed [0-9]+ file\(s\) from /var/www/html/HRProprietary/HRCloud3 into /DATA/HRCloud3/Application\.\n#', $said) === 1
  && strpos($said, "  Running     /DATA/HRCloud3/Application/cloudCore.php -fp\n") !== FALSE && !phpComplained($said), $said);
check('the installed copy publishes the loader & the interface\'s 14 files a browser fetches', preg_match('#  Published   Wrote 1 & removed [0-9]+ name\(s\)\. Serves the loader & 14 other file\(s\)\.\n#', $said) === 1
  && namesIn($hosted) === array('DATA', 'UI', 'cloudCore.php'), implode(' ', namesIn($hosted)));
$hostedInterface = namesBeneath($hosted.'/UI');
check('the hosted location holds the stylesheet, the scripts & the icon, & no component, pack or other PHP file', count(preg_grep('/\.php$/', $hostedInterface)) === 0
  && count(array_intersect(array('HRCloud3.css', 'HRC3-Functions.js', 'HRC3-SignIn.js', 'HRC3-SignInTimer.js', 'HRC3-Files.js', 'favicon.svg'), $hostedInterface)) === 6, implode(' ', $hostedInterface));
clearstatcache();
check('the installation is whole, & root\'s', is_file($installed.'/cloudCore.php') && is_file($installed.'/Resources/Engine/engine.php') && is_file($installed.'/Resources/config.php')
  && is_file($installed.'/Documentation/CHANGELOG.txt') && fileowner($installed) === 0 && fileowner($installed.'/cloudCore.php') === 0 && (fileperms($installed) & 0022) === 0,
  implode(' ', namesIn($installed)));
$webAccount = posix_getpwnam('www-data');
check('Accounts & Users are made, the web server account\'s & 0700', is_array($webAccount) && is_dir('/DATA/HRCloud3/Accounts') && is_dir('/DATA/HRCloud3/Users')
  && fileowner('/DATA/HRCloud3/Accounts') === $webAccount['uid'] && fileowner('/DATA/HRCloud3/Users') === $webAccount['uid']
  && (fileperms('/DATA/HRCloud3/Accounts') & 0777) === 0700 && (fileperms('/DATA/HRCloud3/Users') & 0777) === 0700, implode(' ', namesIn('/DATA/HRCloud3')));
check('-fp says nothing of the kit\'s examples', !namesAnExample($said), $said);


// Part 6. The installed copy, & the loader that runs it.
list ($status, $said) = hrcloud3($installed, array('-v'));
check('the installed copy\'s -v reports the installation & the hosted location as they are', $status === 0 && allIn(array_merge($tierRows, $coreRows, array($noPipelines,
  "  Installation                OK, /DATA/HRCloud3/Application\n",
  "  Hosted location             OK, /var/www/html/HRProprietary/HRCloud3. Serves the loader & 14 other file(s) from /DATA/HRCloud3/Application.\n")), $said)
  && !namesAnExample($said) && !phpComplained($said), $said);
list ($status, $said) = hrcloud3($hosted, array('-v'));
check('the loader runs the installed copy', $status === 0 && strpos($said, "HRCloud3 ".$release."\n") !== FALSE && strpos($said, "  Installation                OK, /DATA/HRCloud3/Application\n") !== FALSE
  && !phpComplained($said), $said);


// Part 7. An account, managed from the command line. Root hands each command to the web server account.
list ($status, $said) = hrcloud3($installed, array('--account', '--add', 'alice', 'alice@example.com'));
check('--account --add makes an account & shows its password once', $status === 0 && preg_match('/[A-Za-z0-9]{20}/', $said, $passwordMatch) === 1 && !phpComplained($said), $said);
$alicePassword = isset($passwordMatch[0]) ? $passwordMatch[0] : '';
list ($status, $listSaid) = hrcloud3($installed, array('--account', '--list'));
check('--account --list names the account, confirmed, with its address', $status === 0 && strpos($listSaid, 'alice') !== FALSE && strpos($listSaid, 'alice@example.com') !== FALSE
  && !phpComplained($listSaid), $listSaid);
check('--account --list shows no hash', preg_match('/\$argon2|\$2y\$|[0-9a-f]{64}/', $listSaid) === 0, $listSaid);
$accountNames = namesBeneath('/DATA/HRCloud3/Accounts');
check('no name in Accounts reveals the username or the address', count($accountNames) > 0 && count(preg_grep('/alice|example/i', $accountNames)) === 0, implode(' ', $accountNames));
check('Users holds nothing until the user first signs in, because accountFolderPath() makes a folder the first time it is needed', namesIn('/DATA/HRCloud3/Users') === array(),
  implode(' ', namesIn('/DATA/HRCloud3/Users')));
list ($status, $said) = hrcloud3($installed, array('--account', '--admin', 'alice'));
list ($listStatus, $listSaid) = hrcloud3($installed, array('--account', '--list'));
check('--account --admin makes the account an administrator', $status === 0 && $listStatus === 0 && preg_match('/alice.*admin/i', $listSaid) === 1 && !phpComplained($said), $said.$listSaid);
list ($status, $said) = hrcloud3($installed, array('--account', '--disable', 'alice'));
list ($listStatus, $listSaid) = hrcloud3($installed, array('--account', '--list'));
check('--account --disable disables it', $status === 0 && preg_match('/alice.*disabled/i', $listSaid) === 1 && !phpComplained($said), $said.$listSaid);
list ($status, $said) = hrcloud3($installed, array('--account', '--enable', 'alice'));
list ($listStatus, $listSaid) = hrcloud3($installed, array('--account', '--list'));
check('--account --enable enables it again', $status === 0 && preg_match('/alice.*disabled/i', $listSaid) === 0 && strpos($listSaid, 'alice') !== FALSE && !phpComplained($said), $said.$listSaid);


// Part 8. The pages, through the loader, as Apache would hand a request to it. Each request is a php-cgi process of its own, run as
// the web server account. Mail is handed to a stand-in that keeps every message in the scratch folder.
$mailFolder = makeFolder(scratchPath('mail'), 0755);
$mailbox = writeScratchFile($mailFolder.'/mailbox.txt', '', 0666);
$mailStandIn = writeScratchFile($mailFolder.'/sendmail', "#!/bin/sh\ncat >> ".$mailbox."\nprintf '%s\\n' '----END----' >> ".$mailbox."\n", 0755);
$fingerprintOf = function ($relativePath) use ($installed) { return substr((string)hash_file('sha256', $installed.'/UI/Default/'.$relativePath), 0, 12); };
$configPath = mustBeInstallFolder($installed.'/Resources/config.php');
$shippedConfig = (string)file_get_contents($configPath);
check('HRCloud3 ships with verbose logging on while it is in development, & marks no component', substr_count($shippedConfig, "\n\$Verbose = TRUE;\n") === 1
  && substr_count($shippedConfig, "\n\$MarkPageComponents = FALSE;\n") === 1);
if (substr_count($shippedConfig, "\n\$MarkPageComponents = FALSE;\n") !== 1 or substr_count($shippedConfig, "\n\$Verbose = TRUE;\n") !== 1) testStop('The installed config.php is not as --config --repair wrote it.');
list ($status, $headers, $page, $complaint) = webRequest('GET', '', array());
check('a visitor is drawn the sign in page, with the policy that keeps its key from another site', $status === 200 && stripos($headers, 'Cross-Origin-Opener-Policy: same-origin') !== FALSE
  && strpos($page, '<title>Sign in - HRCloud3</title>') !== FALSE && strpos($page, "data-hrc3-page='signin'") !== FALSE && $complaint === '' && !phpComplained($page)
  && !namesAnExample($page), $headers.$page.$complaint);
preg_match_all("#(?:href|src)='(UI/[^'?]+)\\?v=[0-9]+'#", $page, $assetMatches);
$assetsServed = array_filter($assetMatches[1], function ($assetPath) use ($hosted) { return is_file($hosted.'/'.$assetPath); });
check('every asset the page links is in the hosted location, at the address the page names, with its time', count($assetMatches[1]) === 4 && count($assetsServed) === 4,
  implode(' ', $assetMatches[1]));
list ($status, $headers, $page, $complaint) = webRequest('GET', 'page=settings', array());
check('an address naming settings asks a visitor to sign in first, & leads to settings once they have', $status === 200 && strpos($page, '<title>Sign in - HRCloud3</title>') !== FALSE
  && strpos($page, 'cloudCore.php?page=settings') !== FALSE, $page);
list ($status, $headers, $page, $complaint) = webRequest('POST', '', array('SignInAction' => 'signin', 'SignInUsername' => 'alice', 'SignInPassword' => $alicePassword));
$aliceKey = keyIn($page);
check('alice signs in with the password --account --add showed, & is handed a key', $status === 200 && $aliceKey !== '' && $complaint === '', $page.$complaint);
list ($status, $headers, $page, $complaint) = webRequest('POST', '', array('page' => 'settings', 'SignInKey' => $aliceKey));
check('signed in, settings shows her account, offers to change the password & the address, & offers no file lifetime', $status === 200
  && strpos($page, '<title>Settings - HRCloud3</title>') !== FALSE && strpos($page, '>alice@example.com</dd>') !== FALSE && strpos($page, "data-hrc3-action='password'") !== FALSE
  && strpos($page, "data-hrc3-action='address'") !== FALSE && strpos($page, 'SignInFileLifetime') === FALSE && $complaint === '' && !phpComplained($page), $page.$complaint);
$builtFrom = function ($label, $relativePath) use ($fingerprintOf, $stampOf) { return $label.' from Default '.$stampOf($relativePath).' #'.$fingerprintOf($relativePath); };
$settingsLine = 'Built the settings page with the Page Core 4.3.4ur for HRCloud3 '.$release.'. Interface Default '.$interfaceVersion.', language en '.$languageVersion.', color blue. Components: '
  .$builtFrom('header.php', 'header.php').', '.$builtFrom('taskbar.php', 'taskbar.php').', '.$builtFrom('settingsGui.php', 'settingsGui.php').', '.$builtFrom('footer.php', 'footer.php')
  .', '.$builtFrom('styleCore.php', 'styleCore.php').', '.$builtFrom('Languages/en', 'Languages/en/languageStrings.php').'. Assets: '.$builtFrom('Stylesheet', 'Resources/CSS/HRCloud3.css')
  .', '.$builtFrom('Library', 'Resources/JS/HRC3-Functions.js').', '.$builtFrom('SignIn', 'Resources/JS/HRC3-SignIn.js').', '.$builtFrom('SignInTimer', 'Resources/JS/HRC3-SignInTimer.js')
  .', '.$builtFrom('Files', 'Resources/JS/HRC3-Files.js').', '.$builtFrom('Icon', 'Resources/Image/favicon.svg').'.';
check('with --Enhanced Logging Verbosity-- on, as HRCloud3 ships it, each page is logged with every file it was built from, its layer, its stamp & its fingerprint', webLogSays(array($settingsLine)),
  'expected '.$settingsLine."\nlogged ".webLogLinesWith('Built the settings page'));
rewriteInstalled($configPath, str_replace("\n\$Verbose = TRUE;\n", "\n\$Verbose = FALSE;\n", $shippedConfig));
list ($status, $headers, $page, $complaint) = webRequest('POST', '', array('page' => 'home', 'interface' => '../../Default', 'language' => 'fr', 'color' => 'dark', 'SignInKey' => $aliceKey));
check('home is the App Launcher, in the color asked for, in English where the language asked for is not offered, & in Default', $status === 200
  && strpos($page, "<html lang='en' dir='ltr' data-color='dark'>") !== FALSE && strpos($page, 'No apps are installed yet.') !== FALSE && strpos($page, "href='UI/Default/") !== FALSE
  && $complaint === '', $page.$complaint);
check('with it off, no page is logged', !webLogSays(array('Built the home page')), webLogLinesWith('Built the home page'));
list ($madeStatus, $madeSaid) = runCommand(array('mkdir', '-p', $installed.'/UI/Second', $installed.'/UI/Broken'));
rewriteInstalled($installed.'/UI/Second/uiVersionInfo.php', "<?php\n\$InterfaceVersion = 'v".$interfaceVersion."';\n");
rewriteInstalled($installed.'/UI/Broken/uiVersionInfo.php', "<?php\n\$InterfaceVersion = 'v0.0.1';\n");
if (substr_count($shippedConfig, "\$SupportedInterfaces = array('Default');") !== 1) testStop('The installed config.php does not offer Default alone, as HRCloud3 ships it.');
rewriteInstalled($configPath, str_replace("\$SupportedInterfaces = array('Default');", "\$SupportedInterfaces = array('Default', 'Second', 'Broken');", $shippedConfig));
list ($status, $headers, $page, $complaint) = webRequest('POST', '', array('page' => 'settings', 'SignInKey' => $aliceKey));
check('the appearance form offers each interface that can be used, & never one that cannot', $madeStatus === 0 && $status === 200
  && strpos($page, "<option value='Default' selected>Default</option>") !== FALSE && strpos($page, "<option value='Second'>Second</option>") !== FALSE
  && strpos($page, 'Broken') === FALSE && $complaint === '' && !phpComplained($page), $madeSaid.$page.$complaint);
rewriteInstalled($configPath, $shippedConfig);
check('no web request is warned about a served location, because HRCloud3 serves none', !webLogSays(array('No served location is set inside the hosted location')));
$aliceKey = tradedKey($aliceKey);
list ($status, $headers, $page, $complaint) = webRequest('POST', '', array('SignInAction' => 'address', 'SignInKey' => $aliceKey, 'SignInPassword' => $alicePassword, 'SignInEmail' => 'alice2@example.org'));
$mailed = (string)file_get_contents($mailbox);
check('asking for a new address mails a code to the new address & to nobody else', outcomeIn($page) === 'address-code-sent' && strpos($mailed, 'To: alice2@example.org') !== FALSE
  && strpos($mailed, 'alice@example.com') === FALSE && lastMailedCode() !== '' && $complaint === '', $page.$mailed.$complaint);
list ($status, $headers, $page, $complaint) = webRequest('POST', '', array('SignInAction' => 'address-confirm', 'SignInKey' => $aliceKey, 'SignInCode' => 'AAAAAAAAAA'));
check('a wrong code changes nothing', outcomeIn($page) === 'code-wrong', $page);
list ($status, $headers, $page, $complaint) = webRequest('POST', '', array('SignInAction' => 'address-confirm', 'SignInKey' => $aliceKey, 'SignInCode' => lastMailedCode()));
$mailed = (string)file_get_contents($mailbox);
check('the right code changes the address, & a notice goes to the old one', outcomeIn($page) === 'address-changed' && strpos($mailed, 'To: alice@example.com') !== FALSE
  && strpos($mailed, 'Subject: HRCloud3: your account was changed') !== FALSE && $complaint === '', $page.$mailed.$complaint);
list ($listStatus, $listSaid) = hrcloud3($installed, array('--account', '--list'));
check('--account --list shows the new address', strpos($listSaid, 'alice2@example.org') !== FALSE && strpos($listSaid, 'alice@example.com') === FALSE, $listSaid);
$aliceKey = tradedKey($aliceKey);
list ($status, $headers, $page, $complaint) = webRequest('POST', '', array('SignInAction' => 'password', 'SignInKey' => $aliceKey, 'SignInPassword' => 'not-her-password', 'SignInNewPassword' => 'correct-horse-battery-staple'));
check('a wrong current password changes nothing', outcomeIn($page) === 'credentials-wrong', $page);
list ($status, $headers, $page, $complaint) = webRequest('POST', '', array('SignInAction' => 'password', 'SignInKey' => $aliceKey, 'SignInPassword' => $alicePassword, 'SignInNewPassword' => 'short'));
check('a new password that is too short is refused', outcomeIn($page) === 'password-short', $page);
list ($status, $headers, $page, $complaint) = webRequest('POST', '', array('SignInAction' => 'password', 'SignInKey' => $aliceKey, 'SignInPassword' => $alicePassword, 'SignInNewPassword' => 'correct-horse-battery-staple'));
check('the right one changes it', outcomeIn($page) === 'password-changed' && $complaint === '', $page.$complaint);
list ($status, $headers, $page, $complaint) = webRequest('POST', '', array('page' => 'settings', 'SignInKey' => $aliceKey));
check('every sign in the account had is over, so the key she held draws the sign in page', strpos($page, '<title>Sign in - HRCloud3</title>') !== FALSE, $page);
list ($status, $headers, $page, $complaint) = webRequest('POST', '', array('SignInAction' => 'signin', 'SignInUsername' => 'alice', 'SignInPassword' => $alicePassword));
check('the old password no longer signs in', outcomeIn($page) === 'credentials-wrong', $page);
list ($status, $headers, $page, $complaint) = webRequest('POST', '', array('SignInAction' => 'signin', 'SignInUsername' => 'alice', 'SignInPassword' => 'correct-horse-battery-staple'));
$aliceKey = keyIn($page);
check('the new one does', $aliceKey !== '', $page);
rewriteInstalled($configPath, str_replace("\n\$MarkPageComponents = FALSE;\n", "\n\$MarkPageComponents = TRUE;\n", $shippedConfig));
list ($status, $headers, $page, $complaint) = webRequest('POST', '', array('page' => 'home', 'SignInKey' => $aliceKey));
check('with --Mark Page Components-- on, each component is marked in the page, & named as the log names it', $status === 200
  && strpos($page, '<!-- Page component begins: '.$builtFrom('launcherGui.php', 'launcherGui.php').' -->') !== FALSE
  && strpos($page, '<!-- Page component ends: footer.php -->') !== FALSE && substr_count($page, '<!-- Page component begins: ') === 4, $page);
rewriteInstalled($configPath, $shippedConfig);
list ($status, $headers, $page, $complaint) = webRequest('POST', '', array('page' => 'home', 'SignInKey' => $aliceKey));
check('with it off, as it ships, nothing is marked', strpos($page, '<!-- Page component') === FALSE && strpos($page, 'No apps are installed yet.') !== FALSE, $page);
$versionPath = mustBeInstallFolder($installed.'/UI/Default/uiVersionInfo.php');
$shippedVersion = (string)file_get_contents($versionPath);
if (substr_count($shippedVersion, "\$InterfaceVersion = 'v".$interfaceVersion."';") !== 1) testStop('The installed UI/Default/uiVersionInfo.php is not as it ships.');
rewriteInstalled($versionPath, str_replace("\$InterfaceVersion = 'v".$interfaceVersion."';", "\$InterfaceVersion = 'v0.9.8';", $shippedVersion));
list ($status, $headers, $page, $complaint) = webRequest('GET', '', array());
check('with the base interface at another version, a visitor is answered 503 in one sentence of plain text', $status === 503 && stripos($headers, 'Content-Type: text/plain') !== FALSE
  && trim($page) === 'HRCloud3 could not draw this page. Its administrator\'s log says why.' && $complaint === '', $headers.$page.$complaint);
check('& the log says why, with error 51000', webLogSays(array('The interface Default reports version 0.9.8 & this application requires '.$interfaceVersion.'. It was not used.', 'HRCloud3-51000, ',
  'A page could not be drawn, so the visitor was told so in plain text.')));
list ($status, $said) = hrcloud3($installed, array('-v'));
check('-v says the interface failed & why, & counts it', strpos($said, "  Default                     FAILED, reports 0.9.8\n") !== FALSE && preg_match('/[1-9][0-9]* check\(s\) failed\./', $said) === 1, $said);
rewriteInstalled($versionPath, $shippedVersion);
list ($status, $headers, $page, $complaint) = webRequest('GET', '', array());
check('put back, the sign in page is drawn again', $status === 200 && strpos($page, '<title>Sign in - HRCloud3</title>') !== FALSE && (string)file_get_contents($versionPath) === $shippedVersion, $page);


// Part 9. The Files page & the Engine's Storage Core, from v1.0.0. A library is asked through the loader as the page's script asks it,
// as alice, who is an administrator, & bob, who is not. config.php is changed only in the copy in memory, & each change is put back.
$librariesFolder = '/DATA/HRCloud3/Libraries';
$webAccount = posix_getpwnam('www-data');
$librariesStatus = @lstat($librariesFolder);
check('-fp made Libraries in the data location, the web server account\'s & 0700, because the contract gives LibraryLoc the web role', is_array($librariesStatus)
  && ($librariesStatus['mode'] & 0170000) === 0040000 && $librariesStatus['uid'] === $webAccount['uid'] && ($librariesStatus['mode'] & 0777) === 0700, implode(' ', namesIn($librariesFolder)));
check('the web requests above made no library, & left only the time of their last sweep in Libraries', namesIn($librariesFolder) === array('swept'), implode(' ', namesIn($librariesFolder)));
list ($status, $said) = hrcloud3($installed, array('-v'));
check('-v names each library, where it is made, & how long the trash keeps what it holds', allIn(array("  Storage Core                OK, 4.3.4ur\n",
  "Libraries\n  Drive                       OK, personal, made on first use at /DATA/HRCloud3/Libraries/Drive\n",
  "  Shared                      OK, shared, made on first use at /DATA/HRCloud3/Libraries/Shared\n", "  Trash                       Emptied after 30 day(s)\n"), $said)
  && !phpComplained($said), $said);
$failedBefore = (preg_match('/([0-9]+) check\(s\) failed\./', $said, $failedMatch) === 1) ? (int)$failedMatch[1] : 0;

// Holds each user's key, & trades it for a new one once it is ten seconds old, as a page's timer does.
$storageKeys = array();
function signInFor($username, $password) {
  global $storageKeys;
  list ($status, $headers, $page, $complaint) = webRequest('POST', '', array('SignInAction' => 'signin', 'SignInUsername' => $username, 'SignInPassword' => $password));
  $storageKeys[$username] = array('Key' => keyIn($page), 'Issued' => time());
  return $storageKeys[$username]['Key']; }
function keyFor($username) {
  global $storageKeys;
  if (time() - $storageKeys[$username]['Issued'] >= 10) $storageKeys[$username] = array('Key' => tradedKey($storageKeys[$username]['Key']), 'Issued' => time());
  return $storageKeys[$username]['Key']; }

// Asks a library for something as a user, with the files & the further variables & settings given. Gives back what webForm() gives back,
// & the reply read out of the answer, or NULL where there is none.
function storageAsk($username, $fields, $files = array(), $extraEnvironment = array(), $phpSettings = array()) {
  $fields['SignInKey'] = keyFor($username);
  list ($status, $headers, $page, $complaint) = webForm($fields, $files, $extraEnvironment, $phpSettings);
  $lines = explode("\n", trim($page));
  $reply = json_decode((string)end($lines), TRUE);
  if ($complaint !== '' or phpComplained($page)) $GLOBALS['storageComplaints'][] = $complaint.$page;
  return array($status, $headers, $page, $complaint, (is_array($reply) && isset($reply['Storage']) && is_array($reply['Storage'])) ? $reply['Storage'] : NULL); }
$storageComplaints = array();

// Names the entries a listing gives, in its order.
function entryNames($reply) {
  $names = array();
  foreach ((array)field($reply, 'Entries') as $entry) $names[] = (string)field($entry, 'Name');
  return $names; }

// Says whether a path is a real folder or file the web server account owns, with the mode given.
function webOwns($path, $mode) {
  global $webAccount;
  clearstatcache();
  $status = @lstat($path);
  return is_array($status) && ($status['mode'] & 0120000) !== 0120000 && $status['uid'] === $webAccount['uid'] && ($status['mode'] & 0777) === $mode; }

list ($status, $headers, $page, $complaint) = webRequest('POST', '', array('StorageAction' => 'list', 'StorageLibrary' => 'Drive'));
check('a library asked without a key answers signed-out in one line of JSON, & never draws the sign in page', $status === 200 && trim($page) === '{"Storage":{"Outcome":"signed-out"}}', $page);
list ($status, $said) = hrcloud3($installed, array('--account', '--add', 'bob', 'bob@example.com'));
$bobPassword = (preg_match('/[A-Za-z0-9]{20}/', $said, $passwordMatch) === 1) ? $passwordMatch[0] : '';
check('bob is added, who is not an administrator', $status === 0 && $bobPassword !== '', $said);
check('alice & bob each sign in', signInFor('alice', 'correct-horse-battery-staple') !== '' && signInFor('bob', $bobPassword) !== '');
list ($status, $headers, $page, $complaint, $reply) = storageAsk('bob', array('StorageAction' => 'explode'));
check('an action no library has is answered action-invalid', field($reply, 'Outcome') === 'action-invalid', $page);
list ($status, $headers, $page, $complaint) = webRequest('POST', '', array('page' => 'files', 'SignInKey' => keyFor('bob')));
check('the Files page is drawn for a signed in user, with its shell & its script, & the taskbar offers it', $status === 200 && strpos($page, '<title>Files - HRCloud3</title>') !== FALSE
  && strpos($page, "id='hrc3FilesCard'") !== FALSE && strpos($page, "HRC3-Files.js") !== FALSE && strpos($page, "data-hrc3-go='files' aria-current='page'") !== FALSE
  && $complaint === '' && !phpComplained($page), $page.$complaint);
list ($status, $headers, $page, $complaint) = webRequest('POST', '', array('page' => 'home', 'SignInKey' => keyFor('bob')));
check('every other page\'s taskbar offers Files too', strpos($page, "data-hrc3-go='files'>Files</button>") !== FALSE, $page);

list ($status, $headers, $page, $complaint, $reply) = storageAsk('bob', array('StorageAction' => 'libraries'));
$bobLibraries = array();
foreach ((array)field($reply, 'Libraries') as $libraryRecord) $bobLibraries[(string)field($libraryRecord, 'Name')] = $libraryRecord;
check('bob is offered Drive, his own to write & to empty, & Shared, which he only reads', field($reply, 'Outcome') === 'libraries' && array_keys($bobLibraries) === array('Drive', 'Shared')
  && field($bobLibraries['Drive'], 'Kind') === 'personal' && field($bobLibraries['Drive'], 'Writable') === TRUE && field($bobLibraries['Drive'], 'Purgeable') === TRUE
  && field($bobLibraries['Shared'], 'Kind') === 'shared' && field($bobLibraries['Shared'], 'Writable') === FALSE && field($bobLibraries['Shared'], 'Purgeable') === FALSE
  && (int)field($reply, 'UploadLimit') > 0 && field($reply, 'TrashDays') === 30, $page);
check('asking for the libraries makes nothing', namesIn($librariesFolder) === array('swept'), implode(' ', namesIn($librariesFolder)));
list ($status, $headers, $page, $complaint, $reply) = storageAsk('alice', array('StorageAction' => 'libraries'));
check('alice, an administrator, may write Shared & empty its trash', strpos($page, '{"Name":"Shared","Kind":"shared","Writable":true,"Purgeable":true}') !== FALSE, $page);

list ($status, $headers, $page, $complaint, $reply) = storageAsk('bob', array('StorageAction' => 'list', 'StorageLibrary' => 'Drive', 'StorageFolder' => ''));
$bobSpaces = namesIn($librariesFolder.'/Drive');
$bobFolder = (string)reset($bobSpaces);
$bobSpace = $librariesFolder.'/Drive/'.$bobFolder;
check('the first listing makes Drive & bob\'s space in it, named for his account\'s random folder, each the web server account\'s & 0700', field($reply, 'Outcome') === 'listed'
  && field($reply, 'Entries') === array() && field($reply, 'Writable') === TRUE && count($bobSpaces) === 1 && preg_match('/^[a-f0-9]{18}$/D', $bobFolder) === 1
  && namesIn($bobSpace) === array('Files', 'Incoming', 'Trash') && webOwns($librariesFolder.'/Drive', 0700) && webOwns($bobSpace, 0700) && webOwns($bobSpace.'/Files', 0700), $page);
check('bob\'s space is named as his folder in Users is, so no path names him', in_array($bobFolder, namesIn('/DATA/HRCloud3/Users'), TRUE), implode(' ', namesIn('/DATA/HRCloud3/Users')));

list ($status, $headers, $page, $complaint, $reply) = storageAsk('bob', array('StorageAction' => 'folder', 'StorageLibrary' => 'Drive', 'StorageNewName' => 'Photos'));
check('a folder is made', field($reply, 'Outcome') === 'made' && webOwns($bobSpace.'/Files/Photos', 0700), $page);
list ($status, $headers, $page, $complaint, $reply) = storageAsk('bob', array('StorageAction' => 'folder', 'StorageLibrary' => 'Drive', 'StorageNewName' => 'Photos'));
check('a name that is taken is refused', field($reply, 'Outcome') === 'exists', $page);
$refusedNames = array();
foreach (array('..', '.', 'a/b', ' Leading', 'Trailing ', "Line\nBreak", '') as $badName) {
  list ($status, $headers, $page, $complaint, $reply) = storageAsk('bob', array('StorageAction' => 'folder', 'StorageLibrary' => 'Drive', 'StorageNewName' => $badName));
  if (field($reply, 'Outcome') !== 'name-invalid') $refusedNames[] = json_encode($badName).' '.$page; }
check('a name that is a dot or two, holds a slash or a control character, begins or ends with a space, or is empty is refused', $refusedNames === array(), implode(' ', $refusedNames));
list ($status, $headers, $page, $complaint, $reply) = storageAsk('bob', array('StorageAction' => 'list', 'StorageLibrary' => 'Drive', 'StorageFolder' => '../../../Accounts'));
check('a folder that climbs out of the space is refused before anything is looked at', field($reply, 'Outcome') === 'folder-invalid', $page);

$hello = "Hello, world!\n";
list ($status, $headers, $page, $complaint, $reply) = storageAsk('bob', array('StorageAction' => 'upload', 'StorageLibrary' => 'Drive', 'StorageWhenTaken' => 'keep'), array(array('hello world.txt', $hello)));
check('a file is uploaded, kept under its own name, the web server account\'s & 0600', field($reply, 'Outcome') === 'uploaded'
  && field(field(field($reply, 'Stored'), 0), 'StoredAs') === 'hello world.txt' && (string)@file_get_contents($bobSpace.'/Files/hello world.txt') === $hello
  && webOwns($bobSpace.'/Files/hello world.txt', 0600) && namesIn($bobSpace.'/Incoming') === array(), $page);
list ($status, $headers, $page, $complaint, $reply) = storageAsk('bob', array('StorageAction' => 'upload', 'StorageLibrary' => 'Drive', 'StorageWhenTaken' => 'keep'), array(array('hello world.txt', 'second')));
check('a second upload of the name is kept beside the first, numbered before its extension, & the first is untouched', field($reply, 'Outcome') === 'uploaded'
  && field(field(field($reply, 'Stored'), 0), 'StoredAs') === 'hello world (2).txt' && (string)@file_get_contents($bobSpace.'/Files/hello world.txt') === $hello, $page);
list ($status, $headers, $page, $complaint, $reply) = storageAsk('bob', array('StorageAction' => 'upload', 'StorageLibrary' => 'Drive', 'StorageWhenTaken' => 'refuse'), array(array('hello world.txt', 'third')));
check('& one that is told to refuse a taken name is refused, & replaces nothing', field($reply, 'Outcome') === 'exists' && (string)@file_get_contents($bobSpace.'/Files/hello world.txt') === $hello, $page);
list ($status, $headers, $page, $complaint, $reply) = storageAsk('bob', array('StorageAction' => 'upload', 'StorageLibrary' => 'Drive', 'StorageWhenTaken' => 'keep'),
  array(array('<i>bold.txt', 'markup'), array('Ünïcödé 10.txt', 'ten'), array('Ünïcödé 2.txt', 'two')));
check('several files go in one request, & a name like markup or in any script is kept exactly', field($reply, 'Outcome') === 'uploaded' && count((array)field($reply, 'Stored')) === 3
  && is_file($bobSpace.'/Files/<i>bold.txt') && is_file($bobSpace.'/Files/Ünïcödé 10.txt'), $page);
list ($status, $headers, $page, $complaint, $reply) = storageAsk('bob', array('StorageAction' => 'list', 'StorageLibrary' => 'Drive'));
check('a listing gives folders first, then files in the order a person reads them, with sizes, & escapes markup in a name', field($reply, 'Outcome') === 'listed'
  && entryNames($reply) === array('Photos', '<i>bold.txt', 'hello world (2).txt', 'hello world.txt', 'Ünïcödé 2.txt', 'Ünïcödé 10.txt')
  && field(field(field($reply, 'Entries'), 3), 'Size') === strlen($hello) && strpos($page, '<i>') === FALSE, $page);

list ($status, $headers, $page, $complaint, $reply) = storageAsk('bob', array('StorageAction' => 'download', 'StorageLibrary' => 'Drive', 'StorageName' => 'hello world.txt'));
check('a download is the file itself, as an attachment a browser never shows as a page, sniffs or runs', $status === 200 && $page === $hello
  && stripos($headers, 'Content-Type: application/octet-stream') !== FALSE && strpos($headers, "Content-Disposition: attachment; filename=\"hello world.txt\"; filename*=UTF-8''hello%20world.txt") !== FALSE
  && strpos($headers, 'X-Content-Type-Options: nosniff') !== FALSE && strpos($headers, "Content-Security-Policy: default-src 'none'; sandbox") !== FALSE
  && strpos($headers, 'Cache-Control: private, no-store') !== FALSE && strpos($headers, 'Accept-Ranges: bytes') !== FALSE && strpos($headers, 'Content-Length: '.strlen($hello)) !== FALSE, $headers.$page);
list ($status, $headers, $page, $complaint, $reply) = storageAsk('bob', array('StorageAction' => 'download', 'StorageLibrary' => 'Drive', 'StorageName' => 'hello world.txt'), array(), array('HTTP_RANGE=bytes=0-4'));
check('a range is sent as part of the file, with status 206', $status === 206 && $page === 'Hello' && strpos($headers, 'Content-Range: bytes 0-4/'.strlen($hello)) !== FALSE, $headers.$page);
list ($status, $headers, $page, $complaint, $reply) = storageAsk('bob', array('StorageAction' => 'download', 'StorageLibrary' => 'Drive', 'StorageName' => 'hello world.txt'), array(), array('HTTP_RANGE=bytes=-7'));
check('& so are its last bytes', $status === 206 && $page === substr($hello, -7), $headers.$page);
list ($status, $headers, $page, $complaint, $reply) = storageAsk('bob', array('StorageAction' => 'download', 'StorageLibrary' => 'Drive', 'StorageName' => 'hello world.txt'), array(), array('HTTP_RANGE=bytes=100-'));
check('a range past the end is answered 416, & sends nothing', $status === 416 && $page === '' && strpos($headers, 'Content-Range: bytes */'.strlen($hello)) !== FALSE, $headers.$page);
list ($status, $headers, $page, $complaint, $reply) = storageAsk('bob', array('StorageAction' => 'download', 'StorageLibrary' => 'Drive', 'StorageName' => 'hello world.txt'),
  array(), array('HTTP_ACCEPT_ENCODING=gzip', 'HTTP_RANGE=bytes=7-'), array('zlib.output_compression=On'));
check('where PHP compresses its output, a download is sent as it is, so a range is the part it names', $status === 206 && $page === substr($hello, 7)
  && stripos($headers, 'Content-Encoding') === FALSE, $headers.$page);
list ($status, $headers, $page, $complaint, $reply) = storageAsk('bob', array('StorageAction' => 'download', 'StorageLibrary' => 'Drive', 'StorageName' => 'nothing.txt'));
check('a file that is not there answers missing, in JSON a page can read', field($reply, 'Outcome') === 'missing', $page);

list ($status, $headers, $page, $complaint, $reply) = storageAsk('bob', array('StorageAction' => 'rename', 'StorageLibrary' => 'Drive', 'StorageName' => 'hello world.txt', 'StorageNewName' => 'greeting.txt'));
check('a file is renamed', field($reply, 'Outcome') === 'renamed' && (string)@file_get_contents($bobSpace.'/Files/greeting.txt') === $hello, $page);
list ($status, $headers, $page, $complaint, $reply) = storageAsk('bob', array('StorageAction' => 'rename', 'StorageLibrary' => 'Drive', 'StorageName' => 'greeting.txt', 'StorageNewName' => 'hello world (2).txt'));
check('a rename to a taken name is refused, & replaces nothing', field($reply, 'Outcome') === 'exists' && (string)@file_get_contents($bobSpace.'/Files/hello world (2).txt') === 'second', $page);
list ($status, $headers, $page, $complaint, $reply) = storageAsk('bob', array('StorageAction' => 'move', 'StorageLibrary' => 'Drive', 'StorageName' => 'greeting.txt', 'StorageDestinationFolder' => 'Photos'));
check('a file is moved into a folder', field($reply, 'Outcome') === 'moved' && is_file($bobSpace.'/Files/Photos/greeting.txt') && !file_exists($bobSpace.'/Files/greeting.txt'), $page);
list ($status, $headers, $page, $complaint, $reply) = storageAsk('bob', array('StorageAction' => 'folder', 'StorageLibrary' => 'Drive', 'StorageFolder' => 'Photos', 'StorageNewName' => 'Sub'));
list ($status, $headers, $page, $complaint, $reply) = storageAsk('bob', array('StorageAction' => 'move', 'StorageLibrary' => 'Drive', 'StorageName' => 'Photos', 'StorageDestinationFolder' => 'Photos/Sub'));
check('a folder is never moved into a folder inside it', field($reply, 'Outcome') === 'into-itself' && is_dir($bobSpace.'/Files/Photos/Sub'), $page);
list ($status, $headers, $page, $complaint, $reply) = storageAsk('bob', array('StorageAction' => 'copy', 'StorageLibrary' => 'Drive', 'StorageName' => 'Photos', 'StorageDestinationFolder' => '', 'StorageWhenTaken' => 'keep'));
check('a folder is copied beside itself, numbered, with everything in it', field($reply, 'Outcome') === 'copied' && field($reply, 'Name') === 'Photos (2)'
  && (string)@file_get_contents($bobSpace.'/Files/Photos (2)/greeting.txt') === $hello && is_dir($bobSpace.'/Files/Photos (2)/Sub') && namesIn($bobSpace.'/Incoming') === array(), $page);
list ($status, $headers, $page, $complaint, $reply) = storageAsk('bob', array('StorageAction' => 'copy', 'StorageLibrary' => 'Drive', 'StorageName' => 'Photos', 'StorageDestinationLibrary' => 'Shared', 'StorageDestinationFolder' => ''));
check('nothing is copied into a library its user only reads', field($reply, 'Outcome') === 'read-only' && !is_dir($librariesFolder.'/Shared/Photos'), $page);

$configPath = mustBeInstallFolder($installed.'/Resources/config.php');
$shippedConfig = (string)file_get_contents($configPath);
if (substr_count($shippedConfig, "\n\$UserStorageQuota = 1024;\n") !== 1) testStop('The installed config.php is not as the suite wrote it.');
rewriteInstalled($configPath, str_replace("\n\$UserStorageQuota = 1024;\n", "\n\$UserStorageQuota = 1;\n", $shippedConfig));
list ($status, $headers, $page, $complaint, $reply) = storageAsk('bob', array('StorageAction' => 'upload', 'StorageLibrary' => 'Drive'), array(array('big.bin', str_repeat('x', 1100000))));
check('with a quota of 1 MB & no listener, an upload that would pass it is refused & kept nowhere', field($reply, 'Outcome') === 'quota' && !file_exists($bobSpace.'/Files/big.bin')
  && namesIn($bobSpace.'/Incoming') === array(), $page);
rewriteInstalled($configPath, $shippedConfig);
list ($status, $said) = hrcloud3($installed, array('--config'));
check('--config does not call the quota inert where the listener is off, because the Storage Core keeps it', strpos($said, '$UserStorageQuota has no effect') === FALSE
  && strpos($said, '$StorageReserve has no effect') === FALSE, $said);

list ($status, $headers, $page, $complaint, $reply) = storageAsk('bob', array('StorageAction' => 'delete', 'StorageLibrary' => 'Drive', 'StorageName' => 'Photos (2)'));
$bobTrash = namesIn($bobSpace.'/Trash');
check('a folder is deleted into the trash, whole, beside a record of where it was', field($reply, 'Outcome') === 'trashed' && !file_exists($bobSpace.'/Files/Photos (2)')
  && count($bobTrash) === 1 && preg_match('/^[0-9]{14}-[a-f0-9]{16}$/D', (string)reset($bobTrash)) === 1 && namesIn($bobSpace.'/Trash/'.reset($bobTrash)) === array('Item', 'Record.json')
  && is_file($bobSpace.'/Trash/'.reset($bobTrash).'/Item/greeting.txt'), $page);
list ($status, $headers, $page, $complaint, $reply) = storageAsk('bob', array('StorageAction' => 'trash', 'StorageLibrary' => 'Drive'));
$trashEntry = field(field($reply, 'Entries'), 0);
check('the trash lists it, with the folder it was in & when', field($reply, 'Outcome') === 'trash-listed' && field($trashEntry, 'Name') === 'Photos (2)' && field($trashEntry, 'Folder') === ''
  && field($trashEntry, 'Kind') === 'folder' && abs((int)field($trashEntry, 'Deleted') - time()) < 120 && field($trashEntry, 'Id') === reset($bobTrash), $page);
storageAsk('bob', array('StorageAction' => 'folder', 'StorageLibrary' => 'Drive', 'StorageNewName' => 'Photos (2)'));
list ($status, $headers, $page, $complaint, $reply) = storageAsk('bob', array('StorageAction' => 'restore', 'StorageLibrary' => 'Drive', 'StorageTrashId' => (string)field($trashEntry, 'Id')));
check('it is put back where it was, & beside what took its name since, replacing nothing', field($reply, 'Outcome') === 'restored' && field($reply, 'Name') === 'Photos (2) (2)'
  && is_file($bobSpace.'/Files/Photos (2) (2)/greeting.txt') && is_dir($bobSpace.'/Files/Photos (2)') && namesIn($bobSpace.'/Trash') === array(), $page);
storageAsk('bob', array('StorageAction' => 'delete', 'StorageLibrary' => 'Drive', 'StorageName' => 'hello world (2).txt'));
list ($status, $headers, $page, $complaint, $reply) = storageAsk('bob', array('StorageAction' => 'trash', 'StorageLibrary' => 'Drive'));
list ($status, $headers, $page, $complaint, $reply) = storageAsk('bob', array('StorageAction' => 'purge', 'StorageLibrary' => 'Drive', 'StorageTrashId' => (string)field(field(field($reply, 'Entries'), 0), 'Id')));
check('something in the trash is deleted forever', field($reply, 'Outcome') === 'purged' && namesIn($bobSpace.'/Trash') === array(), $page);
storageAsk('bob', array('StorageAction' => 'delete', 'StorageLibrary' => 'Drive', 'StorageName' => 'Photos (2)'));
storageAsk('bob', array('StorageAction' => 'delete', 'StorageLibrary' => 'Drive', 'StorageName' => 'Photos (2) (2)'));
list ($status, $headers, $page, $complaint, $reply) = storageAsk('bob', array('StorageAction' => 'empty', 'StorageLibrary' => 'Drive'));
check('the trash is emptied', field($reply, 'Outcome') === 'emptied' && field($reply, 'Count') === 2 && namesIn($bobSpace.'/Trash') === array(), $page);

storageAsk('bob', array('StorageAction' => 'delete', 'StorageLibrary' => 'Drive', 'StorageName' => 'Ünïcödé 2.txt'));
$bobTrash = namesIn($bobSpace.'/Trash');
$tamperedEntry = $bobSpace.'/Trash/'.(string)reset($bobTrash);
$tamperedRecord = (string)file_get_contents(mustBeInstallFolder($tamperedEntry.'/Record.json'));
rewriteInstalled($tamperedEntry.'/Record.json', str_replace('"Folder":[]', '"Folder":[".."]', $tamperedRecord));
list ($status, $headers, $page, $complaint, $reply) = storageAsk('bob', array('StorageAction' => 'trash', 'StorageLibrary' => 'Drive'));
list ($restoreStatus, $restoreHeaders, $restorePage, $restoreComplaint, $restoreReply) = storageAsk('bob', array('StorageAction' => 'restore', 'StorageLibrary' => 'Drive', 'StorageTrashId' => basename($tamperedEntry)));
check('a trash record that names a folder no request could is never read, so a restore is never led out of the space', field($reply, 'Entries') === array()
  && field($restoreReply, 'Outcome') === 'missing' && is_file($tamperedEntry.'/Item'), $page.$restorePage);
rewriteInstalled($tamperedEntry.'/Record.json', $tamperedRecord);

list ($linkStatus, $linkSaid) = runCommand(array('ln', '-s', '/etc', $bobSpace.'/Files/escape'));
list ($fileLinkStatus, $fileLinkSaid) = runCommand(array('ln', '-s', '/etc/passwd', $bobSpace.'/Files/passwd'));
list ($status, $headers, $page, $complaint, $reply) = storageAsk('bob', array('StorageAction' => 'list', 'StorageLibrary' => 'Drive'));
list ($inStatus, $inHeaders, $inPage, $inComplaint, $inReply) = storageAsk('bob', array('StorageAction' => 'list', 'StorageLibrary' => 'Drive', 'StorageFolder' => 'escape'));
list ($getStatus, $getHeaders, $getPage, $getComplaint, $getReply) = storageAsk('bob', array('StorageAction' => 'download', 'StorageLibrary' => 'Drive', 'StorageName' => 'passwd'));
check('a link put in a space is never listed, never entered & never sent', $linkStatus === 0 && $fileLinkStatus === 0 && !in_array('escape', entryNames($reply), TRUE)
  && !in_array('passwd', entryNames($reply), TRUE) && field($inReply, 'Outcome') === 'missing' && field($getReply, 'Outcome') === 'missing' && strpos($getPage, 'root:') === FALSE,
  $page.$inPage.$getPage);
list ($status, $headers, $page, $complaint, $reply) = storageAsk('bob', array('StorageAction' => 'delete', 'StorageLibrary' => 'Drive', 'StorageName' => 'escape'));
check('& never deleted, because nothing a request names is a link', field($reply, 'Outcome') === 'missing' && is_link($bobSpace.'/Files/escape') && is_file('/etc/passwd'), $page);

list ($status, $headers, $page, $complaint, $reply) = storageAsk('bob', array('StorageAction' => 'list', 'StorageLibrary' => 'Shared'));
check('Shared is made the first time it is listed, & bob only reads it', field($reply, 'Outcome') === 'listed' && field($reply, 'Writable') === FALSE
  && webOwns($librariesFolder.'/Shared', 0700) && namesIn($librariesFolder.'/Shared') === array(), $page.implode(' ', namesIn($librariesFolder.'/Shared')));
$readOnlyAnswers = array();
foreach (array(array('StorageAction' => 'folder', 'StorageNewName' => 'Mine'), array('StorageAction' => 'delete', 'StorageName' => 'Anything'), array('StorageAction' => 'trash'),
  array('StorageAction' => 'empty')) as $readOnlyFields) {
  list ($status, $headers, $page, $complaint, $reply) = storageAsk('bob', array_merge(array('StorageLibrary' => 'Shared'), $readOnlyFields));
  $readOnlyAnswers[] = field($reply, 'Outcome'); }
list ($status, $headers, $page, $complaint, $reply) = storageAsk('bob', array('StorageAction' => 'upload', 'StorageLibrary' => 'Shared'), array(array('mine.txt', 'mine')));
$readOnlyAnswers[] = field($reply, 'Outcome');
check('& every change he asks of it is answered read-only', $readOnlyAnswers === array('read-only', 'read-only', 'read-only', 'read-only', 'read-only')
  && namesIn($librariesFolder.'/Shared') === array(), implode(' ', $readOnlyAnswers));
list ($status, $headers, $page, $complaint, $reply) = storageAsk('alice', array('StorageAction' => 'folder', 'StorageLibrary' => 'Shared', 'StorageNewName' => 'Team'));
list ($upStatus, $upHeaders, $upPage, $upComplaint, $upReply) = storageAsk('alice', array('StorageAction' => 'upload', 'StorageLibrary' => 'Shared', 'StorageFolder' => 'Team'), array(array('plan.txt', 'the plan')));
check('alice, an administrator, writes Shared, & its trash, its upload folder & who added what are kept in .hrcloud3, named for HRCloud3, at its top',
  field($reply, 'Outcome') === 'made' && field($upReply, 'Outcome') === 'uploaded' && namesIn($librariesFolder.'/Shared') === array('.hrcloud3', 'Team')
  && namesIn($librariesFolder.'/Shared/.hrcloud3') === array('Authors', 'Incoming', 'Trash') && count(namesIn($librariesFolder.'/Shared/.hrcloud3/Authors')) === 2, $page.$upPage);
list ($status, $headers, $page, $complaint, $reply) = storageAsk('bob', array('StorageAction' => 'list', 'StorageLibrary' => 'Shared'));
list ($getStatus, $getHeaders, $getPage, $getComplaint, $getReply) = storageAsk('bob', array('StorageAction' => 'download', 'StorageLibrary' => 'Shared', 'StorageFolder' => 'Team', 'StorageName' => 'plan.txt'));
list ($keptStatus, $keptHeaders, $keptPage, $keptComplaint, $keptReply) = storageAsk('bob', array('StorageAction' => 'list', 'StorageLibrary' => 'Shared', 'StorageFolder' => '.hrcloud3'));
check('bob reads what alice wrote there, & never sees or enters .hrcloud3', entryNames($reply) === array('Team') && $getPage === 'the plan' && field($keptReply, 'Outcome') === 'missing',
  $page.$getPage.$keptPage);
list ($status, $headers, $page, $complaint, $reply) = storageAsk('alice', array('StorageAction' => 'folder', 'StorageLibrary' => 'Shared', 'StorageNewName' => '.hrcloud3'));
check('nobody may name anything .hrcloud3 at the top of a shared library', field($reply, 'Outcome') === 'name-invalid', $page);
if (substr_count($shippedConfig, "'Shared' => array('Kind' => 'shared', 'Location' => '', 'Writers' => 'admins')") !== 1) testStop('The installed config.php does not list Shared as HRCloud3 ships it.');
rewriteInstalled($configPath, str_replace("'Writers' => 'admins')", "'Writers' => 'everyone')", $shippedConfig));
list ($status, $headers, $page, $complaint, $reply) = storageAsk('bob', array('StorageAction' => 'delete', 'StorageLibrary' => 'Shared', 'StorageFolder' => 'Team', 'StorageName' => 'plan.txt'));
list ($ownStatus, $ownHeaders, $ownPage, $ownComplaint, $ownReply) = storageAsk('bob', array('StorageAction' => 'upload', 'StorageLibrary' => 'Shared', 'StorageFolder' => 'Team'), array(array('bobs.txt', 'by bob')));
storageAsk('bob', array('StorageAction' => 'delete', 'StorageLibrary' => 'Shared', 'StorageFolder' => 'Team', 'StorageName' => 'bobs.txt'));
list ($trashStatus, $trashHeaders, $trashPage, $trashComplaint, $trashReply) = storageAsk('bob', array('StorageAction' => 'trash', 'StorageLibrary' => 'Shared'));
$sharedEntries = array();
foreach ((array)field($trashReply, 'Entries') as $sharedEntry) $sharedEntries[(string)field($sharedEntry, 'Name')] = array((string)field($sharedEntry, 'Id'), field($sharedEntry, 'Purgeable'));
list ($purgeStatus, $purgeHeaders, $purgePage, $purgeComplaint, $purgeReply) = storageAsk('bob', array('StorageAction' => 'purge', 'StorageLibrary' => 'Shared', 'StorageTrashId' => (string)field(field($sharedEntries, 'plan.txt'), 0)));
list ($emptyStatus, $emptyHeaders, $emptyPage, $emptyComplaint, $emptyReply) = storageAsk('bob', array('StorageAction' => 'empty', 'StorageLibrary' => 'Shared'));
check('where everyone writes Shared, bob deletes there, & may delete forever only what he added, never what alice added, & never empties its trash', field($reply, 'Outcome') === 'trashed'
  && field($ownReply, 'Outcome') === 'uploaded' && field($trashReply, 'Purgeable') === FALSE && field(field($sharedEntries, 'plan.txt'), 1) === FALSE && field(field($sharedEntries, 'bobs.txt'), 1) === TRUE
  && field($purgeReply, 'Outcome') === 'not-allowed' && field($emptyReply, 'Outcome') === 'not-allowed' && count(namesIn($librariesFolder.'/Shared/.hrcloud3/Trash')) === 2,
  $page.$ownPage.$trashPage.$purgePage.$emptyPage);
list ($status, $headers, $page, $complaint, $reply) = storageAsk('bob', array('StorageAction' => 'purge', 'StorageLibrary' => 'Shared', 'StorageTrashId' => (string)field(field($sharedEntries, 'bobs.txt'), 0)));
check('& he deletes forever what he added', field($reply, 'Outcome') === 'purged' && count(namesIn($librariesFolder.'/Shared/.hrcloud3/Trash')) === 1, $page);
list ($status, $headers, $page, $complaint, $reply) = storageAsk('alice', array('StorageAction' => 'purge', 'StorageLibrary' => 'Shared', 'StorageTrashId' => (string)field(field($sharedEntries, 'plan.txt'), 0)));
check('& alice, an administrator, deletes anything forever, & each record of who added what goes with it', field($reply, 'Outcome') === 'purged'
  && namesIn($librariesFolder.'/Shared/.hrcloud3/Trash') === array() && count(namesIn($librariesFolder.'/Shared/.hrcloud3/Authors')) === 1, $page);
rewriteInstalled($configPath, $shippedConfig);

storageAsk('bob', array('StorageAction' => 'delete', 'StorageLibrary' => 'Drive', 'StorageName' => 'Ünïcödé 10.txt'));
$bobTrash = namesIn($bobSpace.'/Trash');
$oldEntry = $bobSpace.'/Trash/'.$bobTrash[0];
$oldRecord = (string)file_get_contents(mustBeInstallFolder($oldEntry.'/Record.json'));
rewriteInstalled($oldEntry.'/Record.json', preg_replace('/"Deleted":[0-9]+/', '"Deleted":'.(time() - 31 * 86400), $oldRecord));
$leftover = mustBeInstallFolder($bobSpace.'/Incoming/20000101000000-0000000000000000');
rewriteInstalled($leftover, 'an upload that never finished');
touch($leftover, time() - 2 * 86400);
touch(mustBeInstallFolder($librariesFolder.'/swept'), time() - 3600);
list ($status, $headers, $page, $complaint) = webRequest('GET', '', array());
check('a request sweeps the libraries once ten minutes have passed, taking what the trash kept past its lifetime & an upload that never finished', !file_exists($oldEntry)
  && file_exists($bobSpace.'/Trash/'.$bobTrash[1]) && !file_exists($leftover) && webLogSays(array('Swept 2 entry or entries from the trash & the unfinished uploads of every library.')),
  implode(' ', namesIn($bobSpace.'/Trash')).' '.webLogLinesWith('Swept'));
if (substr_count($shippedConfig, "\n\$DeleteThreshold = 60;\n") !== 1) testStop('The installed config.php does not delete sessions after 60 minutes, as HRCloud3 ships it.');
rewriteInstalled($configPath, str_replace("\n\$DeleteThreshold = 60;\n", "\n\$DeleteThreshold = 0;\n", $shippedConfig));
list ($status, $headers, $page, $complaint) = webRequest('GET', '', array());
check('the session sweep never reaches a library, even where every session has expired', $status === 200 && is_file($bobSpace.'/Files/Photos/greeting.txt')
  && is_file($bobSpace.'/Files/hello world.txt') === FALSE && is_dir($librariesFolder.'/Shared/Team') && count(namesIn($bobSpace.'/Trash')) === 1, implode(' ', namesBeneath($bobSpace)));
rewriteInstalled($configPath, $shippedConfig);

if (substr_count($shippedConfig, "\$Libraries = array(\n  'Drive' => array('Kind' => 'personal', 'Location' => ''),\n") !== 1) testStop('The installed config.php does not list Drive as HRCloud3 ships it.');
rewriteInstalled($configPath, str_replace("\$Libraries = array(\n  'Drive' => array('Kind' => 'personal', 'Location' => ''),\n", "\$Libraries = array(\n  'Drive' => array('Kind' => 'personal', 'Location' => ''),\n"
  ."  'System' => array('Kind' => 'shared', 'Location' => '/etc/hrcloud3'),\n  'Inside' => array('Kind' => 'shared', 'Location' => '/DATA/HRCloud3/Libraries/Drive'),\n"
  ."  'Bad Name' => array('Kind' => 'personal', 'Location' => ''),\n", $shippedConfig));
list ($status, $said) = hrcloud3($installed, array('-v'));
check('-v names each library that cannot be used & why, & counts each as failed', strpos($said, "  System                      FAILED, its location /etc/hrcloud3 is in /etc, where the operating system keeps its own files\n") !== FALSE
  && strpos($said, "  Inside                      FAILED, its location /DATA/HRCloud3/Libraries/Drive is inside the data location or holds it. Leave its Location empty to keep it in the data location\n") !== FALSE
  && strpos($said, "  Bad Name                    FAILED, its name is not 1 to 32 letters, digits, dashes & underscores beginning with a letter or a digit\n") !== FALSE
  && strpos($said, ($failedBefore + 3).' check(s) failed.') !== FALSE && !phpComplained($said), $said);
list ($status, $headers, $page, $complaint, $reply) = storageAsk('bob', array('StorageAction' => 'libraries'));
check('& a page is offered only the libraries that can be used, & the log says why the others were left out', strpos($page, '"Name":"System"') === FALSE && strpos($page, '"Name":"Inside"') === FALSE
  && field(field(field($reply, 'Libraries'), 0), 'Name') === 'Drive' && webLogSays(array('The library System could not be used, because its location /etc/hrcloud3 is in /etc, where the operating system keeps its own files.')), $page);
rewriteInstalled($configPath, $shippedConfig);

$storageStart = strpos($shippedConfig, "\n// / ------------------------------\n// / ---Storage Information---\n");
$storageEnd = strpos($shippedConfig, "\n\$TrashLifetime = 30;\n// / ------------------------------\n");
if ($storageStart === FALSE or $storageEnd === FALSE or $storageEnd < $storageStart) testStop('The installed config.php has no Storage Information as HRCloud3 ships it.');
$olderConfig = str_replace("\$ConfigVersion = 'v".$configVersion."';", "\$ConfigVersion = 'v0.9.9';",
  substr($shippedConfig, 0, $storageStart).substr($shippedConfig, $storageEnd + strlen("\n\$TrashLifetime = 30;\n// / ------------------------------")));
rewriteInstalled($configPath, $olderConfig);
list ($status, $said) = hrcloud3($installed, array('-v'));
check('a config.php from v0.9.9 is refused, because it lacks the libraries & the trash lifetime', $status !== 0 && strpos($said, 'missing 2 required setting(s)') !== FALSE, $said);
list ($status, $said) = hrcloud3($installed, array('--config', '--repair', '-y'));
$repairedConfig = (string)file_get_contents($configPath);
check('& --config --repair adds both, at their defaults, under a Storage Information header it adds, & raises its version', $status === 0 && strpos($repairedConfig, "\$TrashLifetime = 30;") !== FALSE
  && strpos($repairedConfig, "'Shared' => array('Kind' => 'shared', 'Location' => '', 'Writers' => 'admins')") !== FALSE && substr_count($repairedConfig, "// / ---Storage Information---") === 1
  && strpos($repairedConfig, "\$ConfigVersion = 'v".$configVersion."';") !== FALSE && !phpComplained($said), $said.$repairedConfig);
list ($status, $said) = hrcloud3($installed, array('-v'));
check('& the repaired file runs, & offers the same libraries', $status === 0 && strpos($said, "  Drive                       OK, personal") !== FALSE && strpos($said, "  Shared                      OK, shared") !== FALSE, $said);
rewriteInstalled($configPath, $shippedConfig);
list ($status, $headers, $page, $complaint, $reply) = storageAsk('alice', array('StorageAction' => 'list', 'StorageLibrary' => 'Drive'));
check('alice has a space of her own in Drive, which bob never reaches', field($reply, 'Outcome') === 'listed' && field($reply, 'Entries') === array() && count(namesIn($librariesFolder.'/Drive')) === 2,
  $page.implode(' ', namesIn($librariesFolder.'/Drive')));
check('no request to a library made PHP complain', $storageComplaints === array(), implode("\n", $storageComplaints));


// Part 10. The accounts are removed, with the folder signing in made for each & the space each kept in every personal library.
list ($status, $said) = hrcloud3($installed, array('--account', '--remove', 'bob', '-y'));
check('--account --remove takes bob\'s space in Drive with his account, without following the link in it, & leaves alice\'s', $status === 0
  && !file_exists($bobSpace) && !is_link($bobSpace) && count(namesIn($librariesFolder.'/Drive')) === 1 && is_dir('/etc') && is_file('/etc/passwd') && !phpComplained($said), $said);
list ($status, $said) = hrcloud3($installed, array('--account', '--remove', 'alice', '-y'));
list ($listStatus, $listSaid) = hrcloud3($installed, array('--account', '--list'));
check('--account --remove -y removes alice, & Users & Drive are left empty, & Shared keeps what she wrote there, because a shared library is nobody\'s', $status === 0
  && strpos($listSaid, 'alice') === FALSE && namesIn('/DATA/HRCloud3/Users') === array() && namesIn($librariesFolder.'/Drive') === array() && is_dir($librariesFolder.'/Shared/Team')
  && !phpComplained($said), $said.$listSaid);


// Part 11. Strict contract mode. HRCloud3's contract turns it on while HRCloud3 is in development, & sets every value it requires, so the
// Engine starts. With one value commented out & one indented it does not start, & ERROR 31027 in root's log names both. The contract is put back.
$contractPath = mustBeInstallFolder($installed.'/Resources/Contract/app-engine-config.php');
$shippedContract = (string)file_get_contents($contractPath);
if (substr_count($shippedContract, "\n\$EngineEntryPoint = 'cloudCore.php';\n") !== 1 or substr_count($shippedContract, "\n\$EngineApplicationLayout = array(") !== 1
  or preg_match('/^\$EngineStrictContract = TRUE;$/m', $shippedContract) !== 1) testStop('The installed contract is not HRCloud3\'s as it ships.');
list ($status, $said) = hrcloud3($installed, array('-v'));
check('HRCloud3 ships in strict contract mode, sets every value it requires, & the Engine starts', $status === 0
  && strpos($said, "  Engine     4.3.4ur, requires 4.3.4ur\n") !== FALSE && strpos($said, 'did not start') === FALSE && !phpComplained($said), $said);
check('& nothing in root\'s log says 31027', !engineLogSays(array('HRCloud3-31027, ')), implode(' ', namesIn('/DATA/HRCloud3/Engine')));
$incompleteContract = str_replace(array("\n\$EngineEntryPoint = 'cloudCore.php';\n", "\n\$EngineApplicationLayout = array("),
  array("\n// \$EngineEntryPoint = 'cloudCore.php';\n", "\n  \$EngineApplicationLayout = array("), $shippedContract);
rewriteInstalled($contractPath, $incompleteContract);
list ($status, $said) = hrcloud3($installed, array('-v'));
check('with $EngineEntryPoint commented out & $EngineApplicationLayout indented the Engine does not start, & -v says so & counts it as failed', $status === 0
  && strpos($said, "  Engine     4.3.4ur, requires 4.3.4ur, & did not start\n") !== FALSE && strpos($said, '1 check(s) failed.') !== FALSE && !phpComplained($said), $said);
check('& root\'s log names both, with ERROR 31027', engineLogSays(array('HRCloud3-31027, ', 'does not set $EngineEntryPoint, $EngineApplicationLayout.')),
  implode(' ', namesIn('/DATA/HRCloud3/Engine')));
rewriteInstalled($contractPath, $shippedContract);
list ($status, $said) = hrcloud3($installed, array('-v'));
check('with its contract as it shipped, the installed copy starts the Engine', $status === 0 && strpos($said, "  Engine     4.3.4ur, requires 4.3.4ur\n") !== FALSE
  && strpos($said, 'did not start') === FALSE && (string)file_get_contents($contractPath) === $shippedContract, $said);

finish('test_hrcloud3');
