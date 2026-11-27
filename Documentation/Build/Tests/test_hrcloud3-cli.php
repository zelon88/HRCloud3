<?php
// HRCloud3 v0.9.9. HRCloud3's acceptance test. It installs HRCloud3 & runs it as an administrator & a user would.
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
// markers & a base interface of the wrong version. It removes the account, & runs the installed copy in strict contract mode with its
// contract whole & with two values left out.
// Mail is handed to a stand-in in the scratch folder that keeps every message, so nothing leaves the machine.
// It removes nothing of its own. config.php, the contract & the base interface's version file are changed only in the copy in memory,
// & each is put back as it was. --account --remove removes the account the suite made, which is what that command is for.

if (PHP_SAPI !== 'cli') { if (!headers_sent()) http_response_code(404); die(); }

if (!is_file('Documentation/Build/Tests/testlib-cli.php')) { fwrite(STDERR, 'Run this from the root of the application it tests. See Documentation/Build/Tests/README.txt.'.PHP_EOL); exit(64); }
require 'Documentation/Build/Tests/testlib-cli.php';

$tree = testTree(isset($argv[1]) ? $argv[1] : (string)getcwd());
if (getenv('HRP_INSTALL') !== '1') testStop('This suite installs HRCloud3, so it runs only inside contained-install.sh. See Documentation/Build/Tests/README.txt.');
requireContainment();
if (!is_file($tree.'/cloudCore.php') or strpos((string)@file_get_contents($tree.'/Resources/Contract/app-engine-config.php'), "\$EngineApplicationName = 'HRCloud3';") === FALSE) testStop($tree.' is not HRCloud3. Run this from the root of the HRCloud3 tree.');

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
  global $hosted, $mailStandIn;
  $body = ($method === 'POST') ? http_build_query($fields) : '';
  $environment = array('REDIRECT_STATUS=200', 'GATEWAY_INTERFACE=CGI/1.1', 'REQUEST_METHOD='.$method, 'QUERY_STRING='.$query, 'SCRIPT_FILENAME='.$hosted.'/cloudCore.php',
    'SCRIPT_NAME=/HRProprietary/HRCloud3/cloudCore.php', 'REQUEST_URI=/HRProprietary/HRCloud3/cloudCore.php'.($query === '' ? '' : '?'.$query), 'SERVER_NAME=localhost',
    'SERVER_PORT=80', 'REMOTE_ADDR=127.0.0.1', 'HTTP_HOST=localhost', 'HTTP_USER_AGENT=test_hrcloud3', 'SERVER_PROTOCOL=HTTP/1.1',
    'CONTENT_TYPE=application/x-www-form-urlencoded', 'CONTENT_LENGTH='.strlen($body));
  $pipes = array();
  $process = proc_open(array_merge(array('runuser', '-u', 'www-data', '--', 'env', '-i'), $environment, array('php-cgi', '-d', 'sendmail_path='.$mailStandIn)),
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
$tierRows = array("HRCloud3 0.9.9\n", "  Kernel     4.3.3, requires 4.3.3\n", "  Engine     4.3.4ur, requires 4.3.4ur\n",
  "             & its Environment Core 4.3.3, requires 4.3.3\n", "  Config     0.9.9, requires 0.9.9 or later\n");
$coreRows = array("  Setup Core                  OK, 4.3.3\n", "  Dependency Core             OK, 4.3.4\n", "  Pipeline Core               OK, 4.3.2\n",
  "  Workflow Core               OK, 4.3.1\n", "  Authentication Core         OK, 4.2.3ur\n", "  Email Core                  OK, 4.2.0ur\n", "  Page Core                   OK, 4.3.4ur\n",
  "  Event Core                  OK, 4.3.1\n");
$noPipelines = "  Pipelines                   NONE, this application accepts none\n";
$interfaceRows = array("Installed interfaces\n  Default                     OK, 0.9.9, the base\n", "Language packs\n  Default                     1 of 1 OK\n");

list ($madeStatus, $madeSaid) = runCommand(array('mkdir', '-p', $hosted));
list ($copyStatus, $copySaid) = runCommand(array('cp', '-a', $tree.'/.', $hosted.'/'));
if ($madeStatus !== 0 or $copyStatus !== 0) testStop('HRCloud3 could not be copied to '.$hosted.'. '.$madeSaid.$copySaid);
if (file_exists($hosted.'/Resources/config.php')) testStop('The tree holds a config.php, which no release ships. Test a tree without one.');


// Part 1. --config --repair writes config.php from the two halves of the template, with HRCloud3's defaults.
list ($status, $said) = hrcloud3($hosted, array('--config', '--repair'));
check('--config --repair writes config.php from both halves of the template', $status === 0 && strpos($said, 'No config.php was found. Writing the defaults from the template.') !== FALSE
  && preg_match('/Wrote [0-9]+ setting\(s\), all defaults\./', $said) === 1 && !phpComplained($said), $said);
$configText = (string)@file_get_contents($hosted.'/Resources/config.php');
check('config.php holds HRCloud3\'s version, name, folders & update source', strpos($configText, "\$ConfigVersion = 'v0.9.9';") !== FALSE
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
  && strpos($said, "HRCloud3 0.9.9\nA home-hosted cloud storage platform built on the HRProprietary Engine.") !== FALSE
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
check('the installed copy publishes the loader & the interface\'s 13 files a browser fetches', preg_match('#  Published   Wrote 1 & removed [0-9]+ name\(s\)\. Serves the loader & 13 other file\(s\)\.\n#', $said) === 1
  && namesIn($hosted) === array('DATA', 'UI', 'cloudCore.php'), implode(' ', namesIn($hosted)));
$hostedInterface = namesBeneath($hosted.'/UI');
check('the hosted location holds the stylesheet, the scripts & the icon, & no component, pack or other PHP file', count(preg_grep('/\.php$/', $hostedInterface)) === 0
  && count(array_intersect(array('HRCloud3.css', 'HRC3-Functions.js', 'HRC3-SignIn.js', 'HRC3-SignInTimer.js', 'favicon.svg'), $hostedInterface)) === 5, implode(' ', $hostedInterface));
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
  "  Hosted location             OK, /var/www/html/HRProprietary/HRCloud3. Serves the loader & 13 other file(s) from /DATA/HRCloud3/Application.\n")), $said)
  && !namesAnExample($said) && !phpComplained($said), $said);
list ($status, $said) = hrcloud3($hosted, array('-v'));
check('the loader runs the installed copy', $status === 0 && strpos($said, "HRCloud3 0.9.9\n") !== FALSE && strpos($said, "  Installation                OK, /DATA/HRCloud3/Application\n") !== FALSE
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
if (substr_count($shippedConfig, "\n\$MarkPageComponents = FALSE;\n") !== 1 or substr_count($shippedConfig, "\n\$Verbose = FALSE;\n") !== 1) testStop('The installed config.php is not as --config --repair wrote it.');
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
rewriteInstalled($configPath, str_replace("\n\$Verbose = FALSE;\n", "\n\$Verbose = TRUE;\n", $shippedConfig));
list ($status, $headers, $page, $complaint) = webRequest('POST', '', array('page' => 'settings', 'SignInKey' => $aliceKey));
check('signed in, settings shows her account, offers to change the password & the address, & offers no file lifetime', $status === 200
  && strpos($page, '<title>Settings - HRCloud3</title>') !== FALSE && strpos($page, '>alice@example.com</dd>') !== FALSE && strpos($page, "data-hrc3-action='password'") !== FALSE
  && strpos($page, "data-hrc3-action='address'") !== FALSE && strpos($page, 'SignInFileLifetime') === FALSE && $complaint === '' && !phpComplained($page), $page.$complaint);
$settingsLine = 'Built the settings page with the Page Core 4.3.4ur for HRCloud3 0.9.9. Interface Default 0.9.9, language en 0.9.9, color blue. Components: '
  .'header.php from Default 0.9.9 #'.$fingerprintOf('header.php').', taskbar.php from Default 0.9.9 #'.$fingerprintOf('taskbar.php')
  .', settingsGui.php from Default 0.9.9 #'.$fingerprintOf('settingsGui.php').', footer.php from Default 0.9.9 #'.$fingerprintOf('footer.php')
  .', styleCore.php from Default 0.9.9 #'.$fingerprintOf('styleCore.php').', Languages/en from Default 0.9.9 #'.$fingerprintOf('Languages/en/languageStrings.php')
  .'. Assets: Stylesheet from Default 0.9.9 #'.$fingerprintOf('Resources/CSS/HRCloud3.css').', Library from Default 0.9.9 #'.$fingerprintOf('Resources/JS/HRC3-Functions.js')
  .', SignIn from Default 0.9.9 #'.$fingerprintOf('Resources/JS/HRC3-SignIn.js').', SignInTimer from Default 0.9.9 #'.$fingerprintOf('Resources/JS/HRC3-SignInTimer.js')
  .', Icon from Default 0.9.9 #'.$fingerprintOf('Resources/Image/favicon.svg').'.';
check('with --Enhanced Logging Verbosity-- on, each page is logged with every file it was built from, its layer, its stamp & its fingerprint', webLogSays(array($settingsLine)),
  'expected '.$settingsLine."\nlogged ".webLogLinesWith('Built the settings page'));
rewriteInstalled($configPath, $shippedConfig);
list ($status, $headers, $page, $complaint) = webRequest('POST', '', array('page' => 'home', 'interface' => '../../Default', 'language' => 'fr', 'color' => 'dark', 'SignInKey' => $aliceKey));
check('home is the App Launcher, in the color asked for, in English where the language asked for is not offered, & in Default', $status === 200
  && strpos($page, "<html lang='en' dir='ltr' data-color='dark'>") !== FALSE && strpos($page, 'No apps are installed yet.') !== FALSE && strpos($page, "href='UI/Default/") !== FALSE
  && $complaint === '', $page.$complaint);
check('with it off, as HRCloud3 ships, no page is logged', !webLogSays(array('Built the home page')), webLogLinesWith('Built the home page'));
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
  && strpos($page, '<!-- Page component begins: launcherGui.php from Default 0.9.9 #'.$fingerprintOf('launcherGui.php').' -->') !== FALSE
  && strpos($page, '<!-- Page component ends: footer.php -->') !== FALSE && substr_count($page, '<!-- Page component begins: ') === 4, $page);
rewriteInstalled($configPath, $shippedConfig);
list ($status, $headers, $page, $complaint) = webRequest('POST', '', array('page' => 'home', 'SignInKey' => $aliceKey));
check('with it off, as it ships, nothing is marked', strpos($page, '<!-- Page component') === FALSE && strpos($page, 'No apps are installed yet.') !== FALSE, $page);
$versionPath = mustBeInstallFolder($installed.'/UI/Default/uiVersionInfo.php');
$shippedVersion = (string)file_get_contents($versionPath);
if (substr_count($shippedVersion, "\$InterfaceVersion = 'v0.9.9';") !== 1) testStop('The installed UI/Default/uiVersionInfo.php is not as it ships.');
rewriteInstalled($versionPath, str_replace("\$InterfaceVersion = 'v0.9.9';", "\$InterfaceVersion = 'v0.9.8';", $shippedVersion));
list ($status, $headers, $page, $complaint) = webRequest('GET', '', array());
check('with the base interface at another version, a visitor is answered 503 in one sentence of plain text', $status === 503 && stripos($headers, 'Content-Type: text/plain') !== FALSE
  && trim($page) === 'HRCloud3 could not draw this page. Its administrator\'s log says why.' && $complaint === '', $headers.$page.$complaint);
check('& the log says why, with error 51000', webLogSays(array('The interface Default reports version 0.9.8 & this application requires 0.9.9. It was not used.', 'HRCloud3-51000, ',
  'A page could not be drawn, so the visitor was told so in plain text.')));
list ($status, $said) = hrcloud3($installed, array('-v'));
check('-v says the interface failed & why, & counts it', strpos($said, "  Default                     FAILED, reports 0.9.8\n") !== FALSE && preg_match('/[1-9][0-9]* check\(s\) failed\./', $said) === 1, $said);
rewriteInstalled($versionPath, $shippedVersion);
list ($status, $headers, $page, $complaint) = webRequest('GET', '', array());
check('put back, the sign in page is drawn again', $status === 200 && strpos($page, '<title>Sign in - HRCloud3</title>') !== FALSE && (string)file_get_contents($versionPath) === $shippedVersion, $page);


// Part 9. The account is removed, with the folder signing in made for it.
list ($status, $said) = hrcloud3($installed, array('--account', '--remove', 'alice', '-y'));
list ($listStatus, $listSaid) = hrcloud3($installed, array('--account', '--list'));
check('--account --remove -y removes the account, & Users is left empty', $status === 0 && strpos($listSaid, 'alice') === FALSE && namesIn('/DATA/HRCloud3/Users') === array()
  && !phpComplained($said), $said.$listSaid);


// Part 10. Strict contract mode. HRCloud3's contract turns it on while HRCloud3 is in development, & sets every value it requires, so the
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
