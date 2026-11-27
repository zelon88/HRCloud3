<?php
// HRProprietary Engine v4.3.4. The suite of strict contract mode, & of the check both code checkers make for it.
//
//   sudo bash Documentation/Build/Tests/contained.sh php Documentation/Build/Tests/test_contract-cli.php
// Run it from the root of the application under test. A tree named after it is tested instead, & must be that root.
//
// strictContractNames() & verifyStrictContract() are lifted out of the tree's own engine.php, exactly as the tree has it, & read contracts
// this suite writes beneath its scratch folder, where the kernel's own contractPath() is pointed. startEngine() is lifted into a namespace
// where a stand-in answers for loadEnvironmentCore() & startEngineSteps(), so nothing past strict contract mode is ever started for real.
// Then each code checker runs its check named strict, as a program of its own, from folders beneath the scratch folder that hold the tree's
// engine.php & a contract this suite wrote. The Engine & both checkers must name the same values for each contract.
// test_starter-cli.php runs the Starter in strict contract mode, installed, as an administrator would.
//
// It writes only beneath its scratch folder, & removes nothing.

if (PHP_SAPI !== 'cli') { if (!headers_sent()) http_response_code(404); die(); }

if (!is_file('Documentation/Build/Tests/testlib-cli.php')) { fwrite(STDERR, 'Run this from the root of the application it tests. See Documentation/Build/Tests/README.txt.'.PHP_EOL); exit(64); }
require 'Documentation/Build/Tests/testlib-cli.php';

$tree = testTree(isset($argv[1]) ? $argv[1] : (string)getcwd());
requireContainment();

// What the kernel & the Engine would have set before any function here ran.
$EnableMemoryProtection = TRUE;
$InstLoc = $tree;
$EngineStrictContract = FALSE;
$engineFile = $tree.'/Resources/Engine/engine.php';
$engineText = (string)@file_get_contents($engineFile);
$treeContract = (string)@file_get_contents($tree.'/Resources/Contract/app-engine-config.php');
$entryName = testEntryPoint($tree);
$strictNames = array('EngineApplicationName', 'EngineEntryPoint', 'EngineManagedLocations', 'EngineApplicationLayout', 'EnginePinSources', 'EngineSettingDefaults');
$contractCount = 0;
if ($engineText === '' or $treeContract === '') testStop($tree.' holds no Resources/Engine/engine.php or no Resources/Contract/app-engine-config.php that can be read.');

// The functions startEngine() calls once strict contract mode lets it go on. Neither may run for real here, because each starts the Engine.
// A stand-in in the namespace below answers in each one's place, & the suite stops if a call ever reaches one of these.
function refuseRealCall($functionName) { testStop('The real '.$functionName.'() was reached. A stand-in should have answered, & nothing was started.'); }
foreach (array('loadEnvironmentCore', 'startEngineSteps') as $placeholderName) eval('function '.$placeholderName.'(...$arguments) { refuseRealCall(\''.$placeholderName.'\'); }');

liftFunctions($tree.'/Resources/Kernel/kernel.php', array('getChildFunction', 'purgeSensitiveMemory', 'redeclare', 'contractPath'), array());
liftFunctions($engineFile, array('strictContractNames', 'verifyStrictContract'), array());
liftFunctionsInto('start', $engineFile, array('startEngine'), array());
standInFor('start', array('loadEnvironmentCore', 'startEngineSteps'));

// Writes a contract into a folder of its own beneath the scratch folder, as an application holds one, & gives back that folder.
// Each folder is named by a count, so none is written twice. Where the text is NULL no contract is written, as for one that cannot be read.
function contractFolder($contractText) {
  global $contractCount;
  $contractCount++;
  $contractFolderPath = makeFolder(scratchPath('contract-'.$contractCount.'/Resources/Contract'), 0755);
  if ($contractText !== NULL) writeScratchFile($contractFolderPath.'/app-engine-config.php', $contractText, 0644);
  return dirname(dirname($contractFolderPath)); }

// Says what verifyStrictContract() makes of a contract, with strict contract mode set as named. The kernel's contractPath() is pointed at it.
function strictVerdict($contractText, $strictSetting) {
  global $InstLoc, $EngineStrictContract;
  $InstLoc = contractFolder($contractText);
  $EngineStrictContract = $strictSetting;
  return verifyStrictContract(); }

// Changes the line of a contract that begins by assigning one value, in the way named. The suite stops where no line, or more than one, does.
function changeAssignment($contractText, $valueName, $changeKind) {
  $changes = array('slashes' => '// $'.$valueName.' =', 'hash' => '# $'.$valueName.' =', 'indented' => '  $'.$valueName.' =',
    'condition' => 'if (TRUE) $'.$valueName.' =', 'compared' => '$'.$valueName.' ==', 'longer' => '$'.$valueName.'Legacy =',
    'tight' => '$'.$valueName.'=', 'tabbed' => '$'.$valueName."\t=");
  $assignmentPattern = '/^\$'.$valueName.'[ \t]*=(?!=)/m';
  if (!isset($changes[$changeKind]) or preg_match_all($assignmentPattern, $contractText) !== 1) testStop('A contract does not begin a line by assigning $'.$valueName.' exactly once, or the change '.$changeKind.' is not one this suite makes.');
  preg_match($assignmentPattern, $contractText, $assignmentMatch, PREG_OFFSET_CAPTURE);
  return substr_replace($contractText, $changes[$changeKind], $assignmentMatch[0][1], strlen($assignmentMatch[0][0])); }

// Runs startEngine() on a contract, with strict contract mode set as named & the Environment Core's stand-in giving the answer named.
// The log the library keeps begins again, & so does its count of the stand-ins' calls.
function startWith($contractText, $strictSetting, $environmentAnswer) {
  global $InstLoc, $EngineStrictContract, $TestLog;
  $InstLoc = contractFolder($contractText);
  $EngineStrictContract = $strictSetting;
  $TestLog = array();
  setHooks(array('loadEnvironmentCore' => function ($hookArguments, $callCount) use ($environmentAnswer) { return array('answer', $environmentAnswer); },
    'startEngineSteps' => function ($hookArguments, $callCount) { return array('answer', TRUE); }));
  return start\startEngine(); }

// Makes a folder a code checker can run from, as from the root of an installation. It holds a contract, engine.php as named, & an empty
// entry point by both names a checker may look for, the one the tree's contract names & the app.php it assumes where a contract names none.
function checkerFolder($contractText, $engineSource) {
  global $entryName;
  $checkerRoot = contractFolder($contractText);
  makeFolder($checkerRoot.'/Resources/Engine', 0755);
  writeScratchFile($checkerRoot.'/Resources/Engine/engine.php', $engineSource, 0644);
  foreach (array_unique(array($entryName, 'app.php')) as $entryFile) writeScratchFile($checkerRoot.'/'.$entryFile, "<?php\n", 0644);
  return $checkerRoot; }

// Runs each code checker's check named strict from a folder. Gives back, for each, its exit status & the findings it reported, in that order.
// A finding is the text after its kind, because the two print the kind in columns one space apart.
function strictFindings($checkerRoot) {
  global $tree;
  $checkerFindings = array();
  if (!chdir($checkerRoot)) testStop($checkerRoot.' could not be entered.');
  $checkerRuns = array('python' => runCommand(array('python3', '-I', '-B', $tree.'/Documentation/Build/hrconvert2-check.py', '--only', 'strict')),
    'bash' => runCommand(array('bash', $tree.'/Documentation/Build/hrconvert2-check.sh', '--only', 'strict')));
  if (!chdir($tree)) testStop($tree.' could not be entered again.');
  foreach ($checkerRuns as $checkerName => $checkerRun) {
    preg_match_all('/^  STRICT +(.+)$/m', $checkerRun[1], $reportedFindings);
    $checkerFindings[$checkerName] = array($checkerRun[0], $reportedFindings[1]); }
  return $checkerFindings; }

// The findings both checkers report for a contract that does not set the values named, in the order named.
function unsetFindings($valueNames) {
  $expectedFindings = array();
  foreach ($valueNames as $valueName) $expectedFindings[] = 'Resources/Contract/app-engine-config.php does not set $'.$valueName.', which strict contract mode requires & the Engine otherwise defaults';
  return $expectedFindings; }

// Says whether both checkers exited with the status named & reported exactly the findings named, & describes what each did.
function bothCheckersSay($checkerFindings, $expectedStatus, $expectedFindings) {
  $bothAgree = TRUE;
  $checkerAccount = '';
  foreach ($checkerFindings as $checkerName => $checkerResult) {
    if ($checkerResult[0] !== $expectedStatus or $checkerResult[1] !== $expectedFindings) $bothAgree = FALSE;
    $checkerAccount .= $checkerName.' exited '.$checkerResult[0].' & reported '.count($checkerResult[1]).': '.implode(' | ', $checkerResult[1]).'. '; }
  return array($bothAgree, $checkerAccount); }


// Part 1. The six values, listed by one function on the one line both code checkers read.
check('strictContractNames() lists the six values, in the order they are reported', strictContractNames() === $strictNames, implode(', ', strictContractNames()));
$listLines = array_values(preg_grep('/\$StrictContractNames\s*=/', explode("\n", $engineText)));
check('& engine.php assigns that list once, on one line, as both checkers read it', count($listLines) === 1
  && preg_match('/^[ \t]*\$StrictContractNames\s*=\s*array\(([^)]*)\);/', $listLines[0], $listMatch) === 1 && preg_match_all("/'(\w+)'/", $listMatch[1], $listedNames) === 6
  && $listedNames[1] === $strictNames, implode(' | ', $listLines));


// Part 2. verifyStrictContract() reads the contract's text, & reads nothing unless strict contract mode is TRUE.
check('the tree\'s own contract sets every value strict contract mode requires', strictVerdict($treeContract, TRUE) === array(TRUE, array()));
check('with strict contract mode FALSE nothing is read, so a contract that is not there is complete', strictVerdict(NULL, FALSE) === array(TRUE, array()));
foreach (array('the string TRUE' => 'TRUE', 'the number one' => 1, 'NULL, as where it is never set' => NULL) as $settingLabel => $strictSetting)
  check('as it is with the setting '.$settingLabel.', because only TRUE turns strict contract mode on', strictVerdict(NULL, $strictSetting) === array(TRUE, array()));
check('in strict contract mode a contract that cannot be read sets none of the six', strictVerdict(NULL, TRUE) === array(FALSE, $strictNames));
check('& neither does an empty one', strictVerdict("<?php\n?>\n", TRUE) === array(FALSE, $strictNames));
foreach ($strictNames as $valueName) {
  $wronglySet = $wronglyUnset = array();
  foreach (array('slashes', 'hash', 'indented', 'condition', 'compared', 'longer') as $changeKind) if (strictVerdict(changeAssignment($treeContract, $valueName, $changeKind), TRUE) !== array(FALSE, array($valueName))) $wronglySet[] = $changeKind;
  foreach (array('tight', 'tabbed') as $changeKind) if (strictVerdict(changeAssignment($treeContract, $valueName, $changeKind), TRUE) !== array(TRUE, array())) $wronglyUnset[] = $changeKind;
  check('$'.$valueName.' commented out with // or #, indented, assigned after a condition, compared, or assigned only by a longer name is not set', $wronglySet === array(), 'Taken as set: '.implode(', ', $wronglySet));
  check('& $'.$valueName.' assigned with no space or with a tab before = is set', $wronglyUnset === array(), 'Taken as not set: '.implode(', ', $wronglyUnset)); }
$twoLeftOut = changeAssignment(changeAssignment($treeContract, 'EngineSettingDefaults', 'slashes'), 'EngineApplicationName', 'slashes');
check('every value left out is named, in the order strictContractNames() lists them', strictVerdict($twoLeftOut, TRUE) === array(FALSE, array('EngineApplicationName', 'EngineSettingDefaults')));
check('a contract with Windows line endings is read as one without', strictVerdict(str_replace("\n", "\r\n", $treeContract), TRUE) === array(TRUE, array()));


// Part 3. startEngine() asks strict contract mode first, & an Engine whose contract leaves a value out goes no further.
$engineStarted = startWith($twoLeftOut, TRUE, array(TRUE, ''));
check('with a value left out in strict contract mode the Engine does not start, & nothing past the check runs', $engineStarted === FALSE && $TestHookCalls === array(), json_encode($TestHookCalls));
check('& ERROR 31027 names every value left out, once', $TestLog === array('ERROR!!! 31027, The Engine did not start, because $EngineStrictContract is TRUE & the application contract at '
  .'Resources/Contract/app-engine-config.php does not set $EngineApplicationName, $EngineSettingDefaults.'), implode(' | ', $TestLog));
$engineStarted = startWith($treeContract, TRUE, array(TRUE, ''));
check('with every value set the Engine loads its Environment Core & takes its steps', $engineStarted === TRUE && $TestHookCalls === array('loadEnvironmentCore' => 1, 'startEngineSteps' => 1)
  && $TestLog === array(), json_encode($TestHookCalls).' '.implode(' | ', $TestLog));
$engineStarted = startWith($twoLeftOut, FALSE, array(TRUE, ''));
check('with strict contract mode FALSE a value left out is no reason not to start', $engineStarted === TRUE && $TestHookCalls === array('loadEnvironmentCore' => 1, 'startEngineSteps' => 1)
  && $TestLog === array(), json_encode($TestHookCalls).' '.implode(' | ', $TestLog));
$engineStarted = startWith($treeContract, TRUE, array(FALSE, 'It could not be loaded.'));
check('& an Environment Core that was refused still stops it, with its warning & without 31027', $engineStarted === FALSE && $TestHookCalls === array('loadEnvironmentCore' => 1)
  && count($TestLog) === 1 && strpos($TestLog[0], 'WARNING!!! The Engine did not start, because its Environment Core') === 0 && strpos($TestLog[0], 'It could not be loaded.') !== FALSE, implode(' | ', $TestLog));


// Part 4. Each code checker runs its check named strict as a program of its own, & names exactly what the Engine names.
list ($bothAgree, $checkerAccount) = bothCheckersSay(strictFindings(checkerFolder($treeContract, $engineText)), 0, array());
check('both checkers pass the tree\'s own contract', $bothAgree, $checkerAccount);
$mixedContract = changeAssignment(changeAssignment(changeAssignment(changeAssignment(changeAssignment(changeAssignment($treeContract,
  'EngineEntryPoint', 'slashes'), 'EngineApplicationLayout', 'indented'), 'EnginePinSources', 'condition'), 'EngineSettingDefaults', 'compared'),
  'EngineApplicationName', 'tight'), 'EngineManagedLocations', 'tabbed');
$mixedNames = array('EngineEntryPoint', 'EngineApplicationLayout', 'EnginePinSources', 'EngineSettingDefaults');
list ($bothAgree, $checkerAccount) = bothCheckersSay(strictFindings(checkerFolder($mixedContract, $engineText)), 1, unsetFindings($mixedNames));
check('both report a value commented out, indented, assigned after a condition & compared, & neither reports one assigned tightly or with a tab', $bothAgree, $checkerAccount);
check('& the Engine names the same four, in the same order', strictVerdict($mixedContract, TRUE) === array(FALSE, $mixedNames));
list ($bothAgree, $checkerAccount) = bothCheckersSay(strictFindings(checkerFolder("<?php\n?>\n", $engineText)), 1, unsetFindings($strictNames));
check('both report all six where the contract sets none', $bothAgree, $checkerAccount);
list ($bothAgree, $checkerAccount) = bothCheckersSay(strictFindings(checkerFolder(NULL, $engineText)), 1,
  array('Resources/Contract/app-engine-config.php is missing, so it sets none of the values strict contract mode requires'));
check('both report a contract that is missing, once', $bothAgree, $checkerAccount);
if (substr_count($engineText, '$StrictContractNames = array(') !== 1) testStop('engine.php does not assign $StrictContractNames = array( once, so the list cannot be taken out of a copy of it.');
list ($bothAgree, $checkerAccount) = bothCheckersSay(strictFindings(checkerFolder($treeContract, str_replace('$StrictContractNames = array(', '$StrictContractNamesGone = array(', $engineText))), 1,
  array('Resources/Engine/engine.php lists no name in strictContractNames(), so no contract was held to the values strict contract mode requires'));
check('both report an engine.php whose list cannot be read, rather than passing every contract', $bothAgree, $checkerAccount);

finish('test_contract');
