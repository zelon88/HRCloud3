<?php
// HRCloud3 v0.9.8. HRCloud3's acceptance test. It installs HRCloud3 & runs it as an administrator would.
//
//   cd /path/to/HRCloud3
//   sudo bash Documentation/Build/Tests/contained-install.sh php Documentation/Build/Tests/test_hrcloud3-cli.php
// Run it from the root of the HRCloud3 tree, inside contained-install.sh, where /DATA & /var/www/html are memory made for the run.
//
// It is the Engine's test_starter-cli.php, held to what HRCloud3's contract says rather than to the Starter's.
// It copies the tree to /var/www/html/HRProprietary/HRCloud3, where HRCloud3's contract installs it, & runs cloudCore.php there as
// root, each command a process of its own without a shell: --config --repair, -v, -h, an --app argument, & -fp, which installs it
// into /DATA/HRCloud3/Application & publishes the loader. Then it runs the installed copy & the loader, manages an account from the
// command line from making it to removing it, & runs the installed copy in strict contract mode with its contract whole & with two
// values left out.
// It removes nothing of its own. The contract is changed only in the copy in memory, & put back as it was. --account --remove removes
// the account the suite made, which is what that command is for.

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

// The rows -v prints for each tier & each of the Engine's cores, as HRCloud3 pins them.
$tierRows = array("HRCloud3 0.9.8\n", "  Kernel     4.3.3, requires 4.3.3\n", "  Engine     4.3.4, requires 4.3.4\n",
  "             & its Environment Core 4.3.3, requires 4.3.3\n", "  Config     0.9.8, requires 0.9.8 or later\n");
$coreRows = array("  Setup Core                  OK, 4.3.3\n", "  Dependency Core             OK, 4.3.4\n", "  Pipeline Core               OK, 4.3.2\n",
  "  Workflow Core               OK, 4.3.1\n", "  Authentication Core         OK, 4.2.3\n", "  Email Core                  OK, 4.2.0\n", "  Event Core                  OK, 4.3.1\n");
$noPipelines = "  Pipelines                   NONE, this application accepts none\n";

list ($madeStatus, $madeSaid) = runCommand(array('mkdir', '-p', $hosted));
list ($copyStatus, $copySaid) = runCommand(array('cp', '-a', $tree.'/.', $hosted.'/'));
if ($madeStatus !== 0 or $copyStatus !== 0) testStop('HRCloud3 could not be copied to '.$hosted.'. '.$madeSaid.$copySaid);
if (file_exists($hosted.'/Resources/config.php')) testStop('The tree holds a config.php, which no release ships. Test a tree without one.');


// Part 1. --config --repair writes config.php from the two halves of the template, with HRCloud3's defaults.
list ($status, $said) = hrcloud3($hosted, array('--config', '--repair'));
check('--config --repair writes config.php from both halves of the template', $status === 0 && strpos($said, 'No config.php was found. Writing the defaults from the template.') !== FALSE
  && preg_match('/Wrote [0-9]+ setting\(s\), all defaults\./', $said) === 1 && !phpComplained($said), $said);
$configText = (string)@file_get_contents($hosted.'/Resources/config.php');
check('config.php holds HRCloud3\'s version, name, folders & update source', strpos($configText, "\$ConfigVersion = 'v0.9.8';") !== FALSE
  && strpos($configText, "\$ConvertLoc = '/DATA/HRCloud3';") !== FALSE && strpos($configText, "\$InstLoc = '/var/www/html/HRProprietary/HRCloud3';") !== FALSE
  && strpos($configText, "\$ApplicationName = 'HRCloud3';") !== FALSE && strpos($configText, "\$UpdateSourceRepository = 'zelon88/HRCloud3';") !== FALSE, $configText);
check('accounts are on, every visitor signs in & only an administrator makes an account, by default', strpos($configText, "\$EnableAuthentication = TRUE;") !== FALSE
  && strpos($configText, "\$AllowAnonymousVisitors = FALSE;") !== FALSE && strpos($configText, "\$AllowRegistration = FALSE;") !== FALSE, $configText);
check('config.php holds nothing of the Starter\'s', strpos($configText, 'Greeting') === FALSE && !namesAnExample($configText));


// Part 2. -v, before HRCloud3 is installed.
list ($status, $said) = hrcloud3($hosted, array('-v'));
check('-v names HRCloud3 & each tier, at the version each pin requires', $status === 0 && allIn($tierRows, $said), $said);
check('each of the Engine\'s cores is the version HRCloud3 pins', allIn($coreRows, $said), $said);
check('HRCloud3 accepts no pipeline, & nothing about that is a warning, an error or a failure', strpos($said, $noPipelines) !== FALSE
  && strpos($said, '34001') === FALSE && preg_match('/^(WARNING|ERROR)!!! .*accepts no pipeline/m', $said) === 0, $said);
check('-v says nothing of the kit\'s examples, & PHP raised nothing', !namesAnExample($said) && !phpComplained($said), $said);


// Part 3. -h, which the Engine prints & HRCloud3 adds to.
list ($status, $said) = hrcloud3($hosted, array('-h'));
check('-h describes HRCloud3, names cloudCore.php & its repository', $status === 0
  && strpos($said, "HRCloud3 0.9.8\nA home-hosted cloud storage platform built on the HRProprietary Engine.") !== FALSE
  && strpos($said, "  php cloudCore.php [argument]\n") !== FALSE && strpos($said, 'https://github.com/zelon88/HRCloud3') !== FALSE, $said);
check('-h lists the Engine\'s commands, the account commands among them, & no command of HRCloud3\'s own', strpos($said, '  -fp, --fix-permissions') !== FALSE
  && strpos($said, '--account') !== FALSE && strpos($said, '  --app-') === FALSE, $said);
check('-h lists HRCloud3\'s documents', strpos($said, 'Documentation/CHANGELOG.txt') !== FALSE && strpos($said, 'Documentation/ABOUT_AUTHENTICATION.txt') !== FALSE, $said);
check('-h says nothing of the kit\'s examples, & PHP raised nothing', !namesAnExample($said) && !phpComplained($said), $said);


// Part 4. HRCloud3 has no command of its own, so the kernel refuses every argument beginning --app.
list ($status, $said) = hrcloud3($hosted, array('--app-nothing'));
check('an argument beginning --app is refused with status 2, & PHP raised nothing', $status === 2 && strpos($said, '--app-nothing') !== FALSE && !phpComplained($said), $said);


// Part 5. -fp installs HRCloud3 into the data location & publishes the loader.
list ($status, $said) = hrcloud3($hosted, array('-fp'));
check('-fp installs HRCloud3 into Application in the data location, & runs the installed copy\'s -fp', $status === 0
  && preg_match('#  Installed   Installed [0-9]+ file\(s\) from /var/www/html/HRProprietary/HRCloud3 into /DATA/HRCloud3/Application\.\n#', $said) === 1
  && strpos($said, "  Running     /DATA/HRCloud3/Application/cloudCore.php -fp\n") !== FALSE && !phpComplained($said), $said);
check('the installed copy publishes the loader, & the hosted location serves nothing else', preg_match('#  Published   Wrote 1 & removed [0-9]+ name\(s\)\. Serves the loader & 0 other file\(s\)\.\n#', $said) === 1
  && namesIn($hosted) === array('DATA', 'cloudCore.php'), implode(' ', namesIn($hosted)));
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
  "  Hosted location             OK, /var/www/html/HRProprietary/HRCloud3. Serves the loader & 0 other file(s) from /DATA/HRCloud3/Application.\n")), $said)
  && !namesAnExample($said) && !phpComplained($said), $said);
list ($status, $said) = hrcloud3($hosted, array('-v'));
check('the loader runs the installed copy', $status === 0 && strpos($said, "HRCloud3 0.9.8\n") !== FALSE && strpos($said, "  Installation                OK, /DATA/HRCloud3/Application\n") !== FALSE
  && !phpComplained($said), $said);


// Part 7. An account, managed from the command line from making it to removing it. Root hands each command to the web server account.
list ($status, $said) = hrcloud3($installed, array('--account', '--add', 'alice', 'alice@example.com'));
check('--account --add makes an account & shows its password once', $status === 0 && preg_match('/[A-Za-z0-9]{20}/', $said) === 1 && !phpComplained($said), $said);
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
list ($status, $said) = hrcloud3($installed, array('--account', '--remove', 'alice', '-y'));
list ($listStatus, $listSaid) = hrcloud3($installed, array('--account', '--list'));
check('--account --remove -y removes the account, & Users is left empty', $status === 0 && strpos($listSaid, 'alice') === FALSE && namesIn('/DATA/HRCloud3/Users') === array()
  && !phpComplained($said), $said.$listSaid);


// Part 8. Strict contract mode. HRCloud3's contract turns it on while HRCloud3 is in development, & sets every value it requires, so the
// Engine starts. With one value commented out & one indented it does not start, & ERROR 31027 in root's log names both. The contract is put back.
$contractPath = mustBeInstallFolder($installed.'/Resources/Contract/app-engine-config.php');
$shippedContract = (string)file_get_contents($contractPath);
if (substr_count($shippedContract, "\n\$EngineEntryPoint = 'cloudCore.php';\n") !== 1 or substr_count($shippedContract, "\n\$EngineApplicationLayout = array(") !== 1
  or preg_match('/^\$EngineStrictContract = TRUE;$/m', $shippedContract) !== 1) testStop('The installed contract is not HRCloud3\'s as it ships.');
list ($status, $said) = hrcloud3($installed, array('-v'));
check('HRCloud3 ships in strict contract mode, sets every value it requires, & the Engine starts', $status === 0
  && strpos($said, "  Engine     4.3.4, requires 4.3.4\n") !== FALSE && strpos($said, 'did not start') === FALSE && !phpComplained($said), $said);
check('& nothing in root\'s log says 31027', !engineLogSays(array('HRCloud3-31027, ')), implode(' ', namesIn('/DATA/HRCloud3/Engine')));
$incompleteContract = str_replace(array("\n\$EngineEntryPoint = 'cloudCore.php';\n", "\n\$EngineApplicationLayout = array("),
  array("\n// \$EngineEntryPoint = 'cloudCore.php';\n", "\n  \$EngineApplicationLayout = array("), $shippedContract);
rewriteInstalled($contractPath, $incompleteContract);
list ($status, $said) = hrcloud3($installed, array('-v'));
check('with $EngineEntryPoint commented out & $EngineApplicationLayout indented the Engine does not start, & -v says so & counts it as failed', $status === 0
  && strpos($said, "  Engine     4.3.4, requires 4.3.4, & did not start\n") !== FALSE && strpos($said, '1 check(s) failed.') !== FALSE && !phpComplained($said), $said);
check('& root\'s log names both, with ERROR 31027', engineLogSays(array('HRCloud3-31027, ', 'does not set $EngineEntryPoint, $EngineApplicationLayout.')),
  implode(' ', namesIn('/DATA/HRCloud3/Engine')));
rewriteInstalled($contractPath, $shippedContract);
list ($status, $said) = hrcloud3($installed, array('-v'));
check('with its contract as it shipped, the installed copy starts the Engine', $status === 0 && strpos($said, "  Engine     4.3.4, requires 4.3.4\n") !== FALSE
  && strpos($said, 'did not start') === FALSE && (string)file_get_contents($contractPath) === $shippedContract, $said);

finish('test_hrcloud3');
