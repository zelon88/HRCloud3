<?php
// HRProprietary Engine v4.3.4. The suite of the dependency manifest, of Type system & Programs, & of what each pipeline declares it runs.
//
//   sudo bash Documentation/Build/Tests/contained.sh php Documentation/Build/Tests/test_depends-cli.php
// Run it from the root of the application under test. A tree named after it is tested instead, & must be that root.
//
// Both halves of the manifest are read as the Dependency Core reads them, by its own verifyDependsManifest(), & every entry is held
// to what ABOUT_DEPENDENCIES.txt says one may be. Every program the Engine's code runs by a name written into it must be declared in the
// Engine's half, & every program a pipeline is seen to run must be declared by that pipeline, by the name of the entry that holds it.
// findMissingPrograms(), describeDependencyPrograms(), dependencyProblemWeight(), resolveDependencyState(), installOneDependency(),
// checkDepends(), showDependencyFindings(), installDepends() & uninstallDepends() are lifted out of the tree's own Dependency Core,
// exactly as the tree has it, into a namespace where a stand-in answers for every function that would look at the host or change it.
// A stand-in says what is installed. Each function a stand-in replaces stops the suite if it is ever reached for real.
//
// It installs, upgrades & removes nothing, runs no package manager, & writes & removes nothing.

if (PHP_SAPI !== 'cli') { if (!headers_sent()) http_response_code(404); die(); }

if (!is_file('Documentation/Build/Tests/testlib-cli.php')) { fwrite(STDERR, 'Run this from the root of the application it tests. See Documentation/Build/Tests/README.txt.'.PHP_EOL); exit(64); }
require 'Documentation/Build/Tests/testlib-cli.php';

$tree = testTree(isset($argv[1]) ? $argv[1] : (string)getcwd());
requireContainment();

// What the kernel & the Engine would have set before any function here ran.
$EnableMemoryProtection = TRUE;
$Verbose = FALSE;
$DirSep = DIRECTORY_SEPARATOR;
$Lol = PHP_EOL;
$InstLoc = $tree;
$CoreLoaded = TRUE;
$HostArchitecture = 'x86_64';
$EngineEnforceGPLCompliantDeps = FALSE;
$EngineAllowIncompleteDeps = TRUE;
$EngineAllowIncompleteDepsUpdate = FALSE;
$EngineUnwantedApacheConfs = array();
$dependencyFile = $tree.'/Resources/Engine/Cores/dependencyCore.php';
$entryName = testEntryPoint($tree);
$entryText = (string)@file_get_contents($tree.'/'.$entryName);
$RequiredDependsVersion = (preg_match('/\$RequiredDependsVersion\s*=\s*\'v?([0-9.]+)\'/', $entryText, $pinMatch) === 1) ? $pinMatch[1] : '';

// What the Engine would answer for an application named Depends.
function engineApplicationName() { return 'Depends'; }

// The functions a stand-in answers for. None may run for real here, because each would look at the host or change it.
// A stand-in in the namespace below answers in each one's place, & the suite stops if a call ever reaches one of these.
function refuseRealCall($functionName) { testStop('The real '.$functionName.'() was reached. A stand-in should have answered, & nothing was installed or removed.'); }
foreach (array('locateDependency', 'runInstallCommand', 'dependencyEnvironmentPrefix', 'dependencyVersionCommand', 'resolveDependencyChoice', 'ownBuildIsDeclared',
  'ownBuildIsDeclaredHere', 'validateDependencyAuthorization', 'detectPackageManager', 'promptOperator', 'enabledApacheConfs', 'turnOffApacheConfsInstallEnabled',
  'sweepOwnBuilds', 'refreshSandboxBinary', 'detachOwnCopy', 'dependencyPackageCommand', 'dependencyInstallCommand', 'believePackageInstall', 'resolveTargetVersion',
  'settleOwnBuild', 'verifyDependsManifest') as $placeholderName) eval('function '.$placeholderName.'(...$arguments) { refuseRealCall(\''.$placeholderName.'\'); }');

liftFunctions($tree.'/Resources/Kernel/kernel.php', array('getChildFunction', 'purgeSensitiveMemory', 'redeclare', 'contractPath'), array());
liftFunctions($tree.'/Resources/Engine/engine.php', array('getAcceptedEngineManifest', 'compareVersionMinimum'), array());
// The manifest is read as the Dependency Core reads it, by its own functions, in a namespace of their own so the stand-ins never touch them.
liftFunctionsInto('real', $dependencyFile, array('verifyDependsManifest', 'readEngineDependsManifest', 'joinDependsManifests'), array());
// What is tested, in a namespace where each stand-in answers.
standInFor('deps', array('locateDependency', 'runInstallCommand', 'dependencyEnvironmentPrefix', 'dependencyVersionCommand', 'resolveDependencyChoice', 'ownBuildIsDeclared',
  'ownBuildIsDeclaredHere', 'validateDependencyAuthorization', 'detectPackageManager', 'promptOperator', 'enabledApacheConfs', 'turnOffApacheConfsInstallEnabled',
  'sweepOwnBuilds', 'refreshSandboxBinary', 'detachOwnCopy', 'dependencyPackageCommand', 'dependencyInstallCommand', 'believePackageInstall', 'resolveTargetVersion',
  'settleOwnBuild', 'verifyDependsManifest'));
liftFunctionsInto('deps', $dependencyFile, array('findMissingPrograms', 'describeDependencyPrograms', 'dependencyProblemWeight', 'applyArchitectureRules', 'judgeDependencyEntry', 'probeOutputLine',
  'checkDepends', 'showDependencyFindings'), array());
liftFunctionsInto('deps', $dependencyFile, array('resolveDependencyState', 'installOneDependency', 'installDepends', 'uninstallDepends'), array('exec'));

// Says a stand-in for locateDependency() finds exactly the programs named.
function programsInstalled($programNames) {
  return array('locateDependency' => function ($arguments) use ($programNames) { return array('answer', in_array((string)$arguments[0], $programNames, TRUE) ? '/usr/bin/'.(string)$arguments[0] : ''); }); }

// Runs a function & gives back what it printed beside what it returned.
function printedBy($callable) {
  ob_start();
  $returned = call_user_func($callable);
  return array($returned, (string)ob_get_clean()); }

// Gives the names a block of PHP text writes inside the brackets of an assignment to one variable, read without executing it.
function namesAssigned($text, $variableName) {
  if (preg_match('/\$'.$variableName.'\s*=\s*array\((.*?)\);/s', $text, $found) !== 1) return NULL;
  preg_match_all("/'([^']+)'/", $found[1], $names);
  return $names[1]; }

// Gives a manifest entry by name, or an empty array.
function entryNamed($manifest, $entryName) {
  foreach ((array)$manifest as $entry) if (is_array($entry) && (string)($entry['Name'] ?? '') === $entryName) return $entry;
  return array(); }

// Gives every program an entry names, its Binary & each name in its Programs.
function programsOfEntry($entry) {
  $names = array();
  if (trim((string)($entry['Binary'] ?? '')) !== '') $names[] = basename(trim((string)$entry['Binary']));
  foreach ((array)($entry['Programs'] ?? array()) as $item) foreach ((array)$item as $name) $names[] = trim((string)$name);
  return $names; }

// Gives the name of the entry that declares a program, or an empty string.
function entryRunning($manifest, $programName) {
  foreach ((array)$manifest as $entry) if (in_array($programName, programsOfEntry($entry), TRUE)) return (string)$entry['Name'];
  return ''; }

// Gives the program each command written into a block of PHP code starts, by the first word of a command string it hands to exec,
// shell_exec, proc_open, popen, system, passthru, sandboxCommand or limitCommand, or assigns to a variable whose name ends in Command or Prefix.
// ScanCore's runShellCommand() is one of them from v5.0.1, because ScanCore v2.0.0 starts every command it runs through it.
// An assignment of an environment variable before the program is passed over, as the shell passes over it.
function programsWrittenInto($phpText) {
  $programs = array();
  preg_match_all('/(?:\b(?:exec|shell_exec|proc_open|popen|system|passthru|sandboxCommand|limitCommand|runShellCommand)\(\s*|\$[A-Za-z]*(?:Command|Prefix)\s*\.?=\s*)([\'"])((?:[A-Z_]+=\S*\s+)*)([a-z0-9\/][a-z0-9._+\/-]*)(?=[\s\'"])/', $phpText, $found, PREG_SET_ORDER);
  foreach ($found as $match) $programs[] = basename($match[3]);
  return array_values(array_unique($programs)); }

// The shell's own builtins, which the Shell entry declares in its Purpose rather than as programs.
// exec is one from v5.0.0, which Identify's runner starts its command with so the shell gives way to it.
$shellBuiltins = array('cd', 'command', 'echo', 'exec', 'exit', 'export', 'kill', 'test', 'umask');


// Part 1. The manifest, read by the Dependency Core's own functions.
$TestLog = array();
list ($manifestIsAvailable, $manifest, $detectedDependsVersion) = \real\verifyDependsManifest($RequiredDependsVersion);
check('the Dependency Core reads both halves at the version '.$entryName.' pins', $manifestIsAvailable === TRUE && $RequiredDependsVersion !== '' && ltrim($detectedDependsVersion, 'vV') === $RequiredDependsVersion, implode(' ', $TestLog));
check('& names no dependency in both halves', !preg_grep('/both halves/', $TestLog), implode(' ', $TestLog));
$engineHalfText = (string)@file_get_contents($tree.'/Resources/Engine/depends.php');
$applicationHalfText = (string)@file_get_contents($tree.'/Resources/Contract/depends.php');
list ($engineHalfIsRead, $engineHalf, $engineHalfVersion) = \real\readEngineDependsManifest();
$acceptedManifest = getAcceptedEngineManifest();
check('the Engine pins its half at exactly the version the half declares', $engineHalfIsRead === TRUE && $engineHalfVersion !== '' && field($acceptedManifest, 'depends.php') === $engineHalfVersion, $engineHalfVersion);
$documentedTypes = array('apt', 'php-extension', 'source', 'pip', 'manual', 'bundled', 'system');
$badTypes = $badPrograms = $systemBuilds = $incomplete = $names = array();
foreach ($manifest as $entry) {
  $names[] = (string)$entry['Name'];
  if (!in_array((string)$entry['Type'], $documentedTypes, TRUE)) $badTypes[] = $entry['Name'].' is '.$entry['Type'];
  if (isset($entry['Programs'])) {
    if (!is_array($entry['Programs']) or count($entry['Programs']) === 0) $badPrograms[] = $entry['Name'].' declares Programs that are not a list';
    else foreach ($entry['Programs'] as $item) {
      $members = is_array($item) ? $item : array($item);
      if (count($members) === 0) $badPrograms[] = $entry['Name'].' declares an empty group';
      foreach ($members as $member) if (!is_string($member) or trim($member) === '' or strpbrk($member, "/\\ \t") !== FALSE) $badPrograms[] = $entry['Name'].' declares '.var_export($member, TRUE); } }
  if ((string)$entry['Type'] === 'system' && (isset($entry['BuildCommand']) or isset($entry['Fallback']) or isset($entry['VersionSource']))) $systemBuilds[] = $entry['Name'];
  foreach (array('License', 'Source', 'Purpose') as $provenanceField) if (trim((string)($entry[$provenanceField] ?? '')) === '') $incomplete[] = $entry['Name'].' has no '.$provenanceField; }
check('every entry is of a Type ABOUT_DEPENDENCIES.txt documents', $badTypes === array(), implode('. ', $badTypes));
check('every entry\'s Programs is a list of names, or of groups of names, & no name is a path', $badPrograms === array(), implode('. ', $badPrograms));
check('no entry of Type system says how it is built, because nothing ever builds one', $systemBuilds === array(), implode(', ', $systemBuilds));
check('every entry says its License, Source & Purpose', $incomplete === array(), implode('. ', $incomplete));
check('no name is declared twice', count($names) === count(array_unique($names)), implode(', ', array_diff_assoc($names, array_unique($names))));
check('Tar is the Engine\'s, because the Engine\'s update unpacks with it, & the application declares none of its own', field(entryNamed($engineHalf, 'Tar'), 'Type') === 'system'
  && strpos($applicationHalfText, "'Name' => 'Tar'") === FALSE);
check('Git is not the Engine\'s, because the Engine runs no git', entryNamed($engineHalf, 'Git') === array() && strpos($engineHalfText, "'git'") === FALSE);
check('Apache names apachectl or apache2ctl, & a2disconf', field(entryNamed($engineHalf, 'Apache'), 'Programs') === array(array('apachectl', 'apache2ctl'), 'a2disconf'));
check('procps is apt, in Core, so a host lacking it is given it & never loses it', field(entryNamed($engineHalf, 'Procps'), 'Type') === 'apt' && field(entryNamed($engineHalf, 'Procps'), 'Subsystem') === 'Core');

// Every program the Engine's code runs by name is declared in its half, or is a builtin of the Shell it declares.
$engineSources = array_merge(glob($tree.'/Resources/Kernel/*.php'), glob($tree.'/Resources/Engine/*.php'), glob($tree.'/Resources/Engine/Cores/*.php'), glob($tree.'/Resources/Engine/Managers/*.php'));
$engineSources = array_values(array_filter($engineSources, function ($sourcePath) { return basename($sourcePath) !== 'depends.php'; }));
$undeclared = $programsSeen = array();
// pip3 runs only for an entry of Type pip. Where one exists, an entry declares pip3 & the pip entry names it in Requires, as ABOUT_DEPENDENCIES.txt says.
$pipEntries = array_filter($manifest, function ($entry) { return (string)$entry['Type'] === 'pip'; });
foreach ($pipEntries as $pipEntry) if (entryRunning($manifest, 'pip3') === '' or !in_array(entryRunning($manifest, 'pip3'), (array)$pipEntry['Requires'], TRUE)) $undeclared[] = 'pip3, which '.$pipEntry['Name'].' is installed with';
foreach ($engineSources as $sourcePath) foreach (programsWrittenInto((string)file_get_contents($sourcePath)) as $programName) {
  $programsSeen[$programName] = TRUE;
  if (!in_array($programName, $shellBuiltins, TRUE) && !($programName === 'pip3' && count($pipEntries) === 0) && entryRunning($engineHalf, $programName) === '') $undeclared[] = $programName.' in '.basename($sourcePath); }
// Each program the Engine finds by name & then runs from a variable, as it runs whichever package manager it found.
foreach ($engineSources as $sourcePath) if (preg_match_all("/locateDependency\('([a-z0-9._+-]+)'\)/", (string)file_get_contents($sourcePath), $located)) foreach ($located[1] as $programName) {
  $programsSeen[$programName] = TRUE;
  // httpd is looked for only to name Apache's unit on a host that calls it httpd, & is never run.
  if ($programName !== 'httpd' && entryRunning($engineHalf, $programName) === '' && entryRunning($manifest, $programName) === '') $undeclared[] = $programName.' located in '.basename($sourcePath); }
foreach (array('apt-get', 'dnf', 'yum', 'apk', 'pacman') as $programName) if (entryRunning($engineHalf, $programName) === '') $undeclared[] = $programName.', a package manager the Dependency Core uses';
check('every program the Engine runs by name is declared in its half, or is a builtin of its Shell', $undeclared === array() && count($programsSeen) >= 20, implode(', ', $undeclared).'. '.count($programsSeen).' seen.');
check('sh is declared, because PHP hands every command the Engine runs to /bin/sh', entryRunning($engineHalf, 'sh') === 'Shell');
check('& the Shell entry names each builtin the Engine relies on', array_diff($shellBuiltins, preg_match_all('/\b(cd|command|echo|exec|exit|export|kill|test|umask)\b/', (string)field(entryNamed($engineHalf, 'Shell'), 'Purpose'), $builtinsNamed) ? $builtinsNamed[1] : array()) === array());


// Part 2. What each pipeline declares, against the manifest & against what its code runs.
$contractText = (string)@file_get_contents($tree.'/Resources/Contract/app-engine-config.php');
$acceptedPipelines = array();
if (preg_match('/\$EngineAcceptedPipelines\s*=\s*array\((.*?)\);/s', $contractText, $acceptedBlock) === 1) {
  preg_match_all("/'([A-Za-z0-9]+)'\s*=>\s*'v?([0-9.]+)'/", $acceptedBlock[1], $acceptedPairs, PREG_SET_ORDER);
  foreach ($acceptedPairs as $pair) $acceptedPipelines[$pair[1]] = $pair[2]; }
// What an Engine function a pipeline calls runs on its behalf.
$engineFunctionsRun = array('downloadRemoteFileForInspection' => 'Curl', 'waitForStream' => 'Procps');
// What a program a pipeline starts runs for itself, from that program's own source, which is read & never changed.
$scanCoreText = (string)@file_get_contents($tree.'/Resources/Pipelines/ScanCore/ScanCore/ScanCore.php');
$scanCoreRuns = array();
foreach (programsWrittenInto($scanCoreText) as $programName) if (entryRunning($manifest, $programName) !== '') $scanCoreRuns[] = entryRunning($manifest, $programName);
if (strpos($scanCoreText, 'PHP_BINARY') !== FALSE) $scanCoreRuns[] = 'PHP';
$scanCoreRuns = array_values(array_unique($scanCoreRuns));
$pinFaults = $nameFaults = $runFaults = array();
foreach ($acceptedPipelines as $pipelineName => $pinnedVersion) {
  $configText = (string)@file_get_contents($tree.'/Resources/Pipelines/'.$pipelineName.'/pipelineConfig.php');
  if (preg_match("/\\\$PipelineVersion\s*=\s*'v?([0-9.]+)'/", $configText, $versionMatch) !== 1 or $versionMatch[1] !== $pinnedVersion) $pinFaults[] = $pipelineName.' is pinned at '.$pinnedVersion;
  $declared = namesAssigned($configText, 'PipelineDependencies');
  foreach ((array)$declared as $declaredName) if (entryNamed($manifest, $declaredName) === array()) $nameFaults[] = $pipelineName.' declares '.$declaredName;
  // The code is the pipeline's own & every shared module it loads.
  $codeText = '';
  foreach (glob($tree.'/Resources/Pipelines/'.$pipelineName.'/*.php') as $codePath) $codeText .= (string)file_get_contents($codePath);
  foreach ((array)namesAssigned($configText, 'PipelineSharedModules') as $moduleName) $codeText .= (string)@file_get_contents($tree.'/Resources/Pipelines/Shared/'.basename($moduleName));
  $needed = array();
  foreach (programsWrittenInto($codeText) as $programName) {
    if (in_array($programName, $shellBuiltins, TRUE)) continue;
    $owner = entryRunning($manifest, $programName);
    if ($owner === '') $runFaults[] = $pipelineName.' runs '.$programName.', which no entry declares';
    else $needed[] = $owner; }
  foreach ($engineFunctionsRun as $functionName => $owner) if (strpos($codeText, $functionName.'(') !== FALSE) $needed[] = $owner;
  // A candidate Python named in the code is run by name, & an entry whose Interpreter is python3 is started by the distribution's Python.
  if (preg_match("/'python3'/", $codeText) === 1) $needed[] = 'Python 3';
  foreach ((array)$declared as $declaredName) if (field(entryNamed($manifest, $declaredName), 'Interpreter') === 'python3') $needed[] = 'Python 3';
  if (in_array('ScanCore', (array)$declared, TRUE)) $needed = array_merge($needed, $scanCoreRuns);
  foreach (array_unique($needed) as $neededName) if (!in_array($neededName, (array)$declared, TRUE)) $runFaults[] = $pipelineName.' runs '.$neededName.' & does not declare it'; }
check('every pipeline the contract accepts is pinned at the version its folder declares', $pinFaults === array(), implode('. ', $pinFaults));
check('every name a pipeline declares is in the manifest', $nameFaults === array(), implode('. ', $nameFaults));
check('every program a pipeline is seen to run, itself or through what it calls, it declares by its entry\'s name', $runFaults === array(), implode('. ', $runFaults));
if (isset($acceptedPipelines['ScanCore'])) check('ScanCore\'s pipeline declares what ScanCore runs, which is PHP, Curl & Git', array_diff(array('PHP', 'Curl', 'Git'), $scanCoreRuns) === array()
  && array_diff(array('ScanCore', 'PHP', 'Curl', 'Git'), (array)namesAssigned((string)@file_get_contents($tree.'/Resources/Pipelines/ScanCore/pipelineConfig.php'), 'PipelineDependencies')) === array(), implode(', ', $scanCoreRuns));
if (isset($acceptedPipelines['ScanCore'])) check('& the application that ships ScanCore declares Git', field(entryNamed($manifest, 'Git'), 'Subsystem') === 'Virus Scanning');
// ScanCore is in its pipeline's folder from v5.0.0, & nothing may look for it where it was.
if (isset($acceptedPipelines['ScanCore'])) check('ScanCore is in its pipeline\'s folder, where the pipeline runs it & the manifest asks it its version',
  is_file($tree.'/Resources/Pipelines/ScanCore/ScanCore/ScanCore.php') && !file_exists($tree.'/Resources/ScanCore')
  && strpos((string)@file_get_contents($tree.'/Resources/Pipelines/ScanCore/pipeline.php'), "\$scanCoreDir = dirname(__FILE__).\$DirSep.'ScanCore';") !== FALSE
  && strpos((string)field(entryNamed($manifest, 'ScanCore'), 'VersionCommand'), escapeshellarg($tree.'/Resources/Pipelines/ScanCore/ScanCore/ScanCore.php')) !== FALSE,
  (string)field(entryNamed($manifest, 'ScanCore'), 'VersionCommand'));
check('the version sources the application declares run nothing undeclared, & wget nowhere', array_filter($manifest, function ($entry) {
  return isset($entry['VersionSource']) && strpos((string)$entry['VersionSource'], 'wget') !== FALSE; }) === array());


// Part 3. findMissingPrograms() & describeDependencyPrograms().
$apacheLike = array('Name' => 'Server', 'Binary' => 'server', 'Programs' => array('cp', array('apachectl', 'apache2ctl'), ' a2disconf ', array(), ''));
setHooks(programsInstalled(array('cp', 'apachectl', 'apache2ctl', 'a2disconf')));
check('an entry whose every program is here misses nothing', \deps\findMissingPrograms($apacheLike) === array());
setHooks(programsInstalled(array('cp', 'apache2ctl', 'a2disconf')));
check('a group is met by any one of its names', \deps\findMissingPrograms($apacheLike) === array());
setHooks(programsInstalled(array('cp', 'a2disconf')));
check('a group none of whose names is here is missing, named as the group', \deps\findMissingPrograms($apacheLike) === array('apachectl or apache2ctl'));
setHooks(programsInstalled(array()));
check('each missing program is named in the order declared, trimmed, & an empty item is no program', \deps\findMissingPrograms($apacheLike) === array('cp', 'apachectl or apache2ctl', 'a2disconf'));
setHooks(programsInstalled(array()));
check('an entry that declares no Programs misses nothing, & looks nothing up', \deps\findMissingPrograms(array('Name' => 'Plain', 'Binary' => 'plain')) === array() && !isset($TestHookCalls['locateDependency']));
check('the supply chain report names the Binary, then each program, with a group joined by or', \deps\describeDependencyPrograms($apacheLike) === 'server, cp, apachectl or apache2ctl, a2disconf');
check('& names none for a library that runs nothing', \deps\describeDependencyPrograms(array('Name' => 'Library', 'Binary' => '')) === 'none');


// Part 4. resolveDependencyState() with Programs.
$coreLike = array('Name' => 'Utilities', 'Binary' => 'rm', 'Programs' => array('cp', 'timeout', 'nice'), 'Type' => 'system', 'Package' => 'coreutils', 'MinimumVersion' => '',
  'VersionCommand' => '', 'VersionPattern' => '', 'Required' => TRUE, 'Subsystem' => 'Core', 'Requires' => array(), 'License' => 'GPL-3.0', 'Source' => 'https://example.invalid', 'Purpose' => 'Tests.');
setHooks(programsInstalled(array('rm', 'cp', 'timeout', 'nice')));
check('an entry whose Binary & every program are here is ok', \deps\resolveDependencyState($coreLike) === array(TRUE, '', 'ok', ''));
setHooks(programsInstalled(array('rm', 'cp', 'nice')));
check('one missing program makes the entry absent, & says which', \deps\resolveDependencyState($coreLike) === array(FALSE, '', 'absent', 'timeout is not installed.'));
setHooks(programsInstalled(array('rm', 'nice')));
check('two are named together', field(\deps\resolveDependencyState($coreLike), 3) === 'cp & timeout are not installed.');
setHooks(programsInstalled(array('rm')));
check('& three are named as a list', field(\deps\resolveDependencyState($coreLike), 3) === 'cp, timeout & nice are not installed.');
setHooks(programsInstalled(array('cp', 'timeout', 'nice')));
check('a missing Binary is absent as it always was, & no program is looked for', \deps\resolveDependencyState($coreLike) === array(FALSE, '', 'absent', '') && field($TestHookCalls, 'locateDependency') === 1);
$versionedLike = array_merge($coreLike, array('VersionCommand' => 'rm --version', 'VersionPattern' => '/coreutils\) (\d+\.\d+)/', 'MinimumVersion' => '8.0'));
setHooks(array_merge(programsInstalled(array('rm', 'cp', 'nice')), array('dependencyVersionCommand' => function ($arguments) { return array('answer', 'printf "rm (GNU coreutils) 9.1"'); },
  'dependencyEnvironmentPrefix' => function ($arguments) { return array('answer', ''); })));
check('an entry missing a program is not asked its version', field(\deps\resolveDependencyState($versionedLike), 2) === 'absent' && !isset($TestHookCalls['dependencyVersionCommand']));
setHooks(array_merge(programsInstalled(array('rm', 'cp', 'timeout', 'nice')), array('dependencyVersionCommand' => function ($arguments) { return array('answer', 'printf "rm (GNU coreutils) 9.1"'); },
  'dependencyEnvironmentPrefix' => function ($arguments) { return array('answer', ''); })));
check('one with every program is asked, & is ok inside its window', \deps\resolveDependencyState($versionedLike) === array(TRUE, '9.1', 'ok', 'rm (GNU coreutils) 9.1'));


// Part 5. dependencyProblemWeight(), the one judge of a problem.
$weights = array(
  array(TRUE, 'absent', 'apt', 'required'), array(TRUE, 'unsupported', 'apt', 'required'), array(TRUE, 'refused', 'apt', 'required'), array(TRUE, 'too-new', 'apt', 'required'),
  array(TRUE, 'misconfigured', 'apt', 'required'), array(TRUE, 'outdated', 'apt', 'required'), array(TRUE, 'absent', 'system', 'required'),
  array(TRUE, 'unknown-version', 'apt', ''), array(TRUE, 'ok', 'apt', ''), array(TRUE, 'unverifiable', 'apt', ''),
  array(FALSE, 'absent', 'apt', 'optional'), array(FALSE, 'outdated', 'apt', 'optional'), array(FALSE, 'refused', 'manual', 'optional'), array(FALSE, 'outdated', 'system', 'optional'),
  array(FALSE, 'absent', 'system', ''), array(FALSE, 'unsupported', 'apt', ''), array(FALSE, 'unknown-version', 'apt', ''), array(FALSE, 'ok', 'system', ''));
$weightFaults = array();
foreach ($weights as $weight) if (\deps\dependencyProblemWeight(array('Required' => $weight[0], 'Status' => $weight[1], 'Type' => $weight[2])) !== $weight[3]) $weightFaults[] = ($weight[0] ? 'required ' : 'optional ').$weight[2].' '.$weight[1];
check('a problem is weighed as ABOUT_DEPENDENCIES.txt says, & an optional system entry that is absent is none', $weightFaults === array(), implode(', ', $weightFaults));


// Part 6. checkDepends() & showDependencyFindings() count what the judge counts.
// Each fake entry says what the stand-in for resolveDependencyChoice() finds.
function fakeEntry($entryName, $entryType, $isRequired, $foundStatus) {
  return array('Name' => $entryName, 'Binary' => strtolower($entryName), 'Type' => $entryType, 'Package' => strtolower($entryName), 'MinimumVersion' => '', 'VersionCommand' => '',
    'VersionPattern' => '', 'Required' => $isRequired, 'Subsystem' => 'Testing', 'Requires' => array(), 'License' => 'MIT', 'Source' => 'https://example.invalid',
    'Purpose' => 'Tests.', 'Found' => $foundStatus); }
function fakeHooks($fakeManifest) {
  return array(
    'verifyDependsManifest' => function ($arguments) use ($fakeManifest) { return array('answer', array(TRUE, $fakeManifest, 'v0.0.0')); },
    'resolveDependencyChoice' => function ($arguments) { $found = (string)$arguments[0]['Found']; return array('answer', array(in_array($found, array('ok', 'unknown-version'), TRUE), '', $found, '', '', '')); },
    'ownBuildIsDeclared' => function ($arguments) { return array('answer', FALSE); },
    'ownBuildIsDeclaredHere' => function ($arguments) { return array('answer', FALSE); },
    'validateDependencyAuthorization' => function ($arguments) { return array('answer', TRUE); },
    'detectPackageManager' => function ($arguments) { return array('answer', 'testpm'); },
    'enabledApacheConfs' => function ($arguments) { return array('answer', array()); },
    'turnOffApacheConfsInstallEnabled' => function ($arguments) { return array('answer', array()); },
    'sweepOwnBuilds' => function ($arguments) { return array('answer', array(TRUE, 0)); },
    'refreshSandboxBinary' => function ($arguments) { return array('answer', TRUE); },
    'detachOwnCopy' => function ($arguments) { return array('answer', array(TRUE, '')); },
    'dependencyPackageCommand' => function ($arguments) { return array('answer', ''); }); }
$readyManifest = array(fakeEntry('Unreadable', 'apt', TRUE, 'unknown-version'), fakeEntry('NoDnf', 'system', FALSE, 'absent'), fakeEntry('NoTool', 'apt', FALSE, 'absent'), fakeEntry('Present', 'system', TRUE, 'ok'));
setHooks(fakeHooks($readyManifest));
list ($checked, $reportText) = printedBy(function () { return \deps\checkDepends(''); });
check('a required program with an unreadable version leaves the installation ready, & only the optional apt entry is a problem', field($checked, 0) === TRUE && field($checked, 2) === 1, json_encode($checked));
list ($linesPrinted, $reportText) = printedBy(function () use ($checked) { return \deps\showDependencyFindings(field($checked, 1)); });
check('the report beneath agrees, & calls every required dependency usable', $linesPrinted === 4 && strpos($reportText, 'Every required dependency is present & usable.') !== FALSE
  && strpos($reportText, '1 optional dependenc(ies) are missing or cannot be used.') !== FALSE, $reportText);
check('& says once that an absent system entry is part of the operating system', substr_count($reportText, 'part of the operating system, & used only where this host has it') === 1, $reportText);
setHooks(fakeHooks(array_merge($readyManifest, array(fakeEntry('NoShell', 'system', TRUE, 'absent')))));
list ($checked, $reportText) = printedBy(function () { return \deps\checkDepends(''); });
list ($linesPrinted, $reportText) = printedBy(function () use ($checked) { return \deps\showDependencyFindings(field($checked, 1)); });
check('a required system entry that is absent is a required problem, in the verdict & in the report', field($checked, 0) === FALSE
  && strpos($reportText, '1 REQUIRED dependenc(ies) are missing or cannot be used.') !== FALSE, $reportText);


// Part 7. Nothing installs, upgrades or removes an entry of Type system.
$TestLog = array();
setHooks(array_merge(fakeHooks(array()), array('runInstallCommand' => function ($arguments) { return array('answer', array(TRUE, array(), 0)); })));
list ($installed, $returnText) = \deps\installOneDependency(fakeEntry('NoFind', 'system', TRUE, 'absent'), 'testpm');
check('installOneDependency() installs nothing of Type system, runs nothing, & says what to install by hand', $installed === FALSE
  && $returnText === 'This dependency is part of the operating system & is never installed by Depends. Install nofind with the host\'s package manager.'
  && !isset($TestHookCalls['runInstallCommand']) && !isset($TestHookCalls['dependencyPackageCommand']), $returnText);
list ($installed, $returnText) = \deps\installOneDependency(array_merge(fakeEntry('NoImage', 'system', TRUE, 'absent'), array('Package' => '')), 'testpm');
check('& names the entry where it names no package', strpos($returnText, 'Install NoImage with') !== FALSE, $returnText);
// Nothing in this install is the package manager's to install, so a call that reaches it is a fault.
$installManifest = array(fakeEntry('Unreadable', 'apt', TRUE, 'unknown-version'), fakeEntry('NoDnf', 'system', FALSE, 'absent'), fakeEntry('Present', 'system', TRUE, 'ok'), fakeEntry('NoShell', 'system', TRUE, 'absent'));
$TestLog = array();
setHooks(array_merge(fakeHooks($installManifest), array('runInstallCommand' => function ($arguments) { return array('answer', array(TRUE, array(), 0)); })));
list ($installResult, $installText) = printedBy(function () { return \deps\installDepends('token', '', TRUE); });
check('--install-depends names an optional system entry that is absent as optional, & fails nothing for it', strpos($installText, str_pad('NoDnf', 20).'absent, part of the operating system & optional') !== FALSE
  && strpos($installText, str_pad('Unreadable', 20).'present') !== FALSE && strpos($installText, str_pad('Present', 20).'present') !== FALSE, $installText);
check('& fails the run for a required one, naming it part of the operating system', field($installResult, 0) === FALSE && strpos($installText, str_pad('NoShell', 20).'FAILED, part of the operating system') !== FALSE
  && (bool)preg_grep('/^WARNING!!! Dependency NoShell could not be installed\. This dependency is part of the operating system/', $TestLog), $installText);
check('& asks the package manager nothing for either', !isset($TestHookCalls['dependencyInstallCommand']) && !isset($TestHookCalls['runInstallCommand']) && !isset($TestHookCalls['dependencyPackageCommand']), json_encode($TestHookCalls));
$systemOnly = array(fakeEntry('Perl', 'system', FALSE, 'ok'), fakeEntry('Tool', 'apt', FALSE, 'ok'));
setHooks(fakeHooks($systemOnly));
list ($removeResult, $removeText) = printedBy(function () { return \deps\uninstallDepends('token', 'Testing', TRUE); });
check('--uninstall-depends skips an entry of Type system, saying why, & asks no package manager to remove it', strpos($removeText, str_pad('Perl', 20).'part of the operating system, skipped') !== FALSE
  && field($TestHookCalls, 'dependencyPackageCommand') === 1 && field($TestHookCalls, 'detachOwnCopy') === 1, $removeText);
setHooks(array());

finish('test_depends');
