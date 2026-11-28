<?php
// HRProprietary Engine v5.0.0. The suite of the limits a command runs under, of whether a sandbox was built for it, & of how long
// a session is kept.
//
//   sudo bash Documentation/Build/Tests/contained.sh php Documentation/Build/Tests/test_limits-cli.php
// Run it from the root of the application under test. A tree named after it is tested instead, & must be that root.
//
// limitCommand() & verifySystemdRun() are lifted out of the tree's own Resources/Engine/engine.php, exactly as the tree has it, & a
// command they limit is run for real, through a systemd-run this suite writes, which runs what it is handed only where it has been
// handed the variables a user manager is found by. So are sandboxCommand(), sandboxWasBuilt(), resolveDeleteThreshold() & the three
// sweeps that read --File Deletion Age Threshold--, & the shared LibreOffice module's conversion, the OCR pipeline & Identify's command
// runner, each from its own file. A stand-in answers for whatever they ask that would reach the host: which scope can be made, where
// a program is, whether bubblewrap works, a file's checks & every sweep. Identify's runner runs a sleep of this suite's own, & must
// leave none of it running once its watchdog has fired.
//
// It removes nothing. Every file it writes is beneath the scratch folder.

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
$Lolol = PHP_EOL.PHP_EOL;
$Append = FILE_APPEND;
$ApacheUser = 'www-data';
$RunningAsRoot = FALSE;
$RunningInContainer = FALSE;
$ThrowSandboxWarning = TRUE;
$EnablePerConversionLimits = TRUE;
$VirusScan = FALSE;
$kernelFile = $tree.'/Resources/Kernel/kernel.php';
$engineFile = $tree.'/Resources/Engine/engine.php';
$resourceManagerFile = $tree.'/Resources/Engine/Managers/resourceManager.php';
$libreOfficeFile = $tree.'/Resources/Pipelines/Shared/libreOffice.php';
$ocrFile = $tree.'/Resources/Pipelines/OCR/pipeline.php';
$identifyFile = $tree.'/Resources/Pipelines/Identify/pipeline.php';

// What this suite answers for wherever it is asked. Each is here only so a stand-in can be made for it, & stops the suite where it
// is reached, because nothing here may ask the host what the real one would.
function notHere($functionName) { testStop($functionName.'() was reached, & this suite answers for it everywhere.'); }
function locateDependency(...$arguments) { notHere('locateDependency'); }
function webServerAccountNumbers(...$arguments) { notHere('webServerAccountNumbers'); }
function resolveConversionLimit(...$arguments) { notHere('resolveConversionLimit'); }
function resolveConversionRuntime(...$arguments) { notHere('resolveConversionRuntime'); }
function sandboxProfileAsks(...$arguments) { notHere('sandboxProfileAsks'); }
function cgroupDelegationIsAvailable(...$arguments) { notHere('cgroupDelegationIsAvailable'); }
function limitCommand(...$arguments) { notHere('limitCommand'); }
function sandboxCommand(...$arguments) { notHere('sandboxCommand'); }
function sandboxWasBuilt(...$arguments) { notHere('sandboxWasBuilt'); }
function verifySandboxLauncher(...$arguments) { notHere('verifySandboxLauncher'); }
function sandboxIdentityFlags(...$arguments) { notHere('sandboxIdentityFlags'); }
function sandboxAccountFlags(...$arguments) { notHere('sandboxAccountFlags'); }
function sandboxProfileFlags(...$arguments) { notHere('sandboxProfileFlags'); }
function sandboxOverlayFlags(...$arguments) { notHere('sandboxOverlayFlags'); }
function sandboxIsRequired(...$arguments) { notHere('sandboxIsRequired'); }
function sandboxRequiredProfiles(...$arguments) { notHere('sandboxRequiredProfiles'); }
function makeLibreOfficeInput(...$arguments) { notHere('makeLibreOfficeInput'); }
function sanitizeDocumentLinks(...$arguments) { notHere('sanitizeDocumentLinks'); }
function makeLibreOfficeProfile(...$arguments) { notHere('makeLibreOfficeProfile'); }
function removeLibreOfficeProfile(...$arguments) { notHere('removeLibreOfficeProfile'); }
function verifiedToolPath(...$arguments) { notHere('verifiedToolPath'); }
function verifyFile(...$arguments) { notHere('verifyFile'); }
function imageMagickDecoder(...$arguments) { notHere('imageMagickDecoder'); }
function convertWithLibreOffice(...$arguments) { notHere('convertWithLibreOffice'); }
function verifyDocumentConversionEngine(...$arguments) { notHere('verifyDocumentConversionEngine'); }
function runVirusScan(...$arguments) { notHere('runVirusScan'); }
function cleanDataLocation(...$arguments) { notHere('cleanDataLocation'); }
function verifyRequiredDirs(...$arguments) { notHere('verifyRequiredDirs'); }
function enumerateDataLocations(...$arguments) { notHere('enumerateDataLocations'); }
function runAsAccount(...$arguments) { notHere('runAsAccount'); }

// Whether a line holding the text was logged since the log was last emptied.
function logHas($expectedText) {
  foreach ((array)$GLOBALS['TestLog'] as $line) if (strpos((string)$line, $expectedText) !== FALSE) return TRUE;
  return FALSE; }

// How many processes are running a sleep for the seconds named, which nothing but this suite runs.
function sleepsLeft($seconds) {
  $found = 0;
  foreach ((array)glob('/proc/[0-9]*/cmdline') as $commandLinePath) if ((string)@file_get_contents($commandLinePath) === "sleep\0".$seconds."\0") $found++;
  return $found; }

liftFunctions($kernelFile, array('getChildFunction', 'purgeSensitiveMemory', 'redeclare', 'sanitizeString', 'sanitize', 'getExtension'), array());
liftFunctions($engineFile, array('resolveDeleteThreshold'), array());
liftFunctions($identifyFile, array('identifyRunCommand'), array('proc_open'));
standInFor('lim', array('locateDependency', 'webServerAccountNumbers', 'resolveConversionLimit', 'resolveConversionRuntime', 'sandboxProfileAsks', 'cgroupDelegationIsAvailable'));
liftFunctionsInto('lim', $engineFile, array('verifySystemdRun', 'limitCommand'), array('exec'));
standInFor('sc', array('verifySandboxLauncher', 'sandboxIdentityFlags', 'sandboxProfileAsks', 'sandboxAccountFlags', 'sandboxProfileFlags', 'sandboxOverlayFlags', 'sandboxIsRequired', 'sandboxRequiredProfiles', 'limitCommand', 'sandboxWasBuilt'));
liftFunctionsInto('sc', $engineFile, array('sandboxCommand'), array());
liftFunctionsInto('sb', $engineFile, array('sandboxWasBuilt'), array());
standInFor('lo', array('locateDependency', 'makeLibreOfficeInput', 'sanitizeDocumentLinks', 'sandboxCommand', 'sandboxWasBuilt', 'makeLibreOfficeProfile', 'removeLibreOfficeProfile', 'limitCommand'));
liftFunctionsInto('lo', $libreOfficeFile, array('convertWithLibreOffice'), array('exec', 'rename'));
standInFor('ocr', array('verifiedToolPath', 'sanitize', 'verifyFile', 'imageMagickDecoder', 'sandboxCommand', 'shell_exec', 'convertWithLibreOffice', 'verifyDocumentConversionEngine', 'runVirusScan'));
liftFunctionsInto('ocr', $ocrFile, array('ocrFiles'), array('shell_exec', 'rename'));
standInFor('sw', array('cleanDataLocation', 'verifyRequiredDirs', 'enumerateDataLocations', 'runAsAccount'));
liftFunctionsInto('sw', $engineFile, array('prepareWebRequest', 'runEngineCleanCommand'), array());
liftFunctionsInto('sw', $resourceManagerFile, array('cleanEveryConvertLoc'), array());


// Part 1. A command a user scope holds runs. The scope's variables reach systemd-run through env, because timeout goes outside
// everything & runs the word after its figure as a program. Until v5.0.0 every such command exited 127 before anything ran.
// This systemd-run runs what follows -- only where it was handed the variables a user manager is found by, & the right ones.
putenv('XDG_RUNTIME_DIR');
putenv('DBUS_SESSION_BUS_ADDRESS');
$fakeBin = makeFolder(scratchPath('bin'), 0755);
$fakeScope = writeScratchFile($fakeBin.'/systemd-run', "#!/bin/sh\n"
  ."# Stands in for systemd-run. It needs the variables a user manager is found by, & runs what follows --.\n"
  ."[ \"\$XDG_RUNTIME_DIR\" = /run/user/4242 ] && [ \"\$DBUS_SESSION_BUS_ADDRESS\" = unix:path=/run/user/4242/bus ] || exit 3\n"
  ."while [ \"\$#\" -gt 0 ]; do\n"
  ."  if [ \"\$1\" = \"--\" ]; then shift; exec \"\$@\"; fi\n"
  ."  shift\n"
  ."done\n"
  ."exit 4\n", 0755);
$runtimeFigure = 30;
setHooks(array(
  'locateDependency' => function ($arguments, $count) use ($fakeScope) { return array('answer', $arguments[0] === 'systemd-run' ? $fakeScope : ($arguments[0] === 'timeout' ? '/usr/bin/timeout' : '')); },
  'webServerAccountNumbers' => function ($arguments, $count) { return array('answer', array(4242, 4242)); },
  'resolveConversionLimit' => function ($arguments, $count) { return array('answer', array(TRUE, 50, 512)); },
  'resolveConversionRuntime' => function ($arguments, $count) { return array('answer', array(TRUE, $GLOBALS['runtimeFigure'])); },
  'sandboxProfileAsks' => function ($arguments, $count) { return array('answer', FALSE); },
  'cgroupDelegationIsAvailable' => function ($arguments, $count) { return array('answer', FALSE); }));
$TestLog = array();
list ($scopeMode, $scopeBinary, $scopeEnvironment) = lim\verifySystemdRun();
check('where a user manager answers, the scope is a user scope, found by its variables', $scopeMode === 'user' && $scopeBinary === $fakeScope && strpos($scopeEnvironment, 'XDG_RUNTIME_DIR=') === 0, $scopeMode.' '.implode(' | ', $TestLog));
$limitedCommand = lim\limitCommand("printf '%s' limited", 'generic');
check('a limited command begins with the time limit & never with an assignment, because timeout runs its first word', strpos($limitedCommand, "'/usr/bin/timeout' 30 ") === 0
  && preg_match('/^[A-Za-z_][A-Za-z0-9_]*=/', $limitedCommand) !== 1 && strpos($limitedCommand, ' --user --scope ') !== FALSE, $limitedCommand);
$commandOutput = array();
$commandStatus = -1;
exec($limitedCommand.' 2>&1', $commandOutput, $commandStatus);
check('a command a user scope holds runs, & systemd-run is handed the variables its manager is found by', $commandStatus === 0 && implode('', $commandOutput) === 'limited', $commandStatus.': '.implode(' ', $commandOutput));
$runtimeFigure = 1;
$limitedCommand = lim\limitCommand('sleep 5', 'generic');
$commandOutput = array();
$commandStatus = -1;
$startedAt = microtime(TRUE);
exec($limitedCommand.' 2>&1', $commandOutput, $commandStatus);
check('a command that runs past its type\'s time is stopped, & exits 124, which is not asked again', $commandStatus === 124 && (microtime(TRUE) - $startedAt) < 4.0, $commandStatus.' after '.round(microtime(TRUE) - $startedAt, 1).' seconds');
setHooks(array());


// Part 2. sandboxWasBuilt() keeps the last answer, & only TRUE is TRUE.
check('nothing is sandboxed until sandboxCommand() has said so', sb\sandboxWasBuilt() === FALSE);
check('an answer of TRUE is kept', sb\sandboxWasBuilt(TRUE) === TRUE && sb\sandboxWasBuilt() === TRUE);
check('an answer of FALSE replaces it', sb\sandboxWasBuilt(FALSE) === FALSE && sb\sandboxWasBuilt() === FALSE);
check('an answer that is not TRUE is FALSE', sb\sandboxWasBuilt(1) === FALSE && sb\sandboxWasBuilt('yes') === FALSE && sb\sandboxWasBuilt() === FALSE);


// Part 3. sandboxCommand() says whether it built a sandbox, for every command it returns, refused ones included.
// Its command is limited either way, so the command it returns is never the one it was handed.
function sandboxHooks($bubblewrapWorks, $sandboxIsRequired) {
  return array(
    'verifySandboxLauncher' => function ($arguments, $count) use ($bubblewrapWorks) { return array('answer', $bubblewrapWorks ? array('/usr/bin/bwrap', "'/usr/bin/bwrap'", FALSE, FALSE) : array(FALSE, '', FALSE, FALSE)); },
    'sandboxIdentityFlags' => function ($arguments, $count) { return array('answer', array(FALSE, '')); },
    'sandboxProfileAsks' => function ($arguments, $count) { return array('answer', FALSE); },
    'sandboxAccountFlags' => function ($arguments, $count) { return array('answer', array(1000, '')); },
    'sandboxProfileFlags' => function ($arguments, $count) { return array('answer', ''); },
    'sandboxOverlayFlags' => function ($arguments, $count) { return array('answer', ''); },
    'sandboxIsRequired' => function ($arguments, $count) use ($sandboxIsRequired) { return array('answer', $sandboxIsRequired); },
    'sandboxRequiredProfiles' => function ($arguments, $count) { return array('answer', array()); },
    'limitCommand' => function ($arguments, $count) { return array('answer', 'LIMITED '.$arguments[0]); },
    'sandboxWasBuilt' => function ($arguments, $count) { $GLOBALS['BuiltAnswers'][] = $arguments; return array('answer', isset($arguments[0]) && $arguments[0] === TRUE); }); }
$sandboxInput = makeFolder(scratchPath('sandbox/in'), 0700).'/page.txt';
$sandboxOutput = makeFolder(scratchPath('sandbox/out'), 0700).'/page.pdf';
$BuiltAnswers = array();
$TestLog = array();
setHooks(sandboxHooks(FALSE, FALSE));
list ($commandMayRun, $sandboxedCommand) = sc\sandboxCommand('tool '.escapeshellarg($sandboxInput).' '.escapeshellarg($sandboxOutput), $sandboxInput, $sandboxOutput, FALSE, 'generic');
check('where no sandbox can be built & none is required, the command runs unsandboxed & limited, & sandboxWasBuilt() is told FALSE', $commandMayRun === TRUE
  && strpos($sandboxedCommand, 'LIMITED tool ') === 0 && $BuiltAnswers === array(array(FALSE)), $sandboxedCommand.' '.json_encode($BuiltAnswers));
$BuiltAnswers = array();
setHooks(sandboxHooks(TRUE, FALSE));
list ($commandMayRun, $sandboxedCommand) = sc\sandboxCommand('tool '.escapeshellarg($sandboxInput).' '.escapeshellarg($sandboxOutput), $sandboxInput, $sandboxOutput, FALSE, 'generic');
check('where bubblewrap works, the command is sandboxed & limited, & sandboxWasBuilt() is told TRUE', $commandMayRun === TRUE
  && strpos($sandboxedCommand, "LIMITED '/usr/bin/bwrap'") === 0 && $BuiltAnswers === array(array(TRUE)), substr($sandboxedCommand, 0, 60).' '.json_encode($BuiltAnswers));
$BuiltAnswers = array();
setHooks(sandboxHooks(FALSE, TRUE));
list ($commandMayRun, $sandboxedCommand) = sc\sandboxCommand('tool '.escapeshellarg($sandboxInput).' '.escapeshellarg($sandboxOutput), $sandboxInput, $sandboxOutput, FALSE, 'generic');
check('where a sandbox is required & none can be built, the command is refused, & sandboxWasBuilt() is told FALSE', $commandMayRun === FALSE
  && $BuiltAnswers === array(array(FALSE)), json_encode($BuiltAnswers));
setHooks(array());


// Part 4. The shared LibreOffice module asks sandboxWasBuilt() whether it was sandboxed, rather than comparing commands.
// Without a sandbox it builds its command again, with a profile of its own & the output's folder named, & limits it.
// Until v5.0.0 a limited command was taken for a sandboxed one, so LibreOffice wrote into PHP's working folder & used the shared
// profile. Each command here is a printf that writes what it was handed into a record, so nothing but printf runs.
$officeFolder = makeFolder(scratchPath('office'), 0700);
$officeCopies = makeFolder(scratchPath('office/copy'), 0700);
$officeProfile = makeFolder(scratchPath('office-profile'), 0700);
$officeInput = writeScratchFile($officeFolder.'/report.docx', 'This is not a document.', 0600);
$officeOutput = $officeFolder.'/report.pdf';
function officeHooks($sandboxWasBuilt, $sandboxAnswer, $recordPath) {
  global $officeCopies, $officeProfile;
  return array(
    'locateDependency' => function ($arguments, $count) { return array('answer', $arguments[0] === 'soffice' ? '/opt/stand-in/soffice' : ''); },
    'makeLibreOfficeInput' => function ($arguments, $count) use ($officeCopies) { return array('answer', array(TRUE, $officeCopies, $officeCopies.'/report.docx')); },
    'sanitizeDocumentLinks' => function ($arguments, $count) { return array('answer', array(TRUE, 0)); },
    'sandboxCommand' => function ($arguments, $count) use ($sandboxAnswer) { return array('answer', array(TRUE, $sandboxAnswer)); },
    'sandboxWasBuilt' => function ($arguments, $count) use ($sandboxWasBuilt) { return array('answer', $sandboxWasBuilt); },
    'makeLibreOfficeProfile' => function ($arguments, $count) use ($officeProfile) { return array('answer', array(TRUE, $officeProfile)); },
    'removeLibreOfficeProfile' => function ($arguments, $count) { return array('answer', TRUE); },
    'limitCommand' => function ($arguments, $count) use ($recordPath) { $GLOBALS['LimitedProfiles'][] = $arguments[1]; return array('answer', "printf '%s' ".escapeshellarg($arguments[0]).' > '.escapeshellarg($recordPath)); }); }
$Verbose = TRUE;
$LimitedProfiles = array();
$TestLog = array();
$recordPath = scratchPath('office-unsandboxed');
setHooks(officeHooks(FALSE, "'/usr/bin/timeout' 600 '/opt/stand-in/soffice' --headless --convert-to 'pdf' --outdir . '/in/report.docx'", $recordPath));
list ($officeConverted, $officePrinted, $officeRan) = lo\convertWithLibreOffice($officeInput, $officeOutput, 'pdf');
$officeRecord = (string)@file_get_contents($recordPath);
check('without a sandbox LibreOffice is built a command of its own, naming the output\'s folder & a profile of its own, & it is limited',
  $officeRan === TRUE && $LimitedProfiles === array('libreoffice') && strpos($officeRecord, "--outdir '".$officeFolder."'") !== FALSE
  && strpos($officeRecord, "'-env:UserInstallation=file://".$officeProfile."'") !== FALSE && strpos($officeRecord, '--outdir .') === FALSE, $officeRecord.' '.json_encode($LimitedProfiles));
check('& it says it ran unsandboxed', logHas('Sandboxed: NO'), implode(' | ', $TestLog));
$LimitedProfiles = array();
$TestLog = array();
$recordPath = scratchPath('office-sandboxed');
setHooks(officeHooks(TRUE, "printf '%s' sandboxed > ".escapeshellarg($recordPath), $recordPath));
list ($officeConverted, $officePrinted, $officeRan) = lo\convertWithLibreOffice($officeInput, $officeOutput, 'pdf');
check('in a sandbox LibreOffice runs the command sandboxCommand() returned, which is limited already, & says so', $officeRan === TRUE && $LimitedProfiles === array()
  && (string)@file_get_contents($recordPath) === 'sandboxed' && logHas('Sandboxed: YES'), implode(' | ', $TestLog));
$Verbose = FALSE;
setHooks(array());


// Part 5. An OCR page split a required sandbox cannot isolate is refused & never run, as every other OCR command is.
// Until v5.0.0 it ran anyway, unsandboxed & unlimited. Every command is refused here, & shell_exec() records what it is handed.
$ocrFolder = makeFolder(scratchPath('ocr'), 0700);
$ConvertTempDir = $ocrFolder;
$ocrInput = writeScratchFile($ocrFolder.'/scan.pdf', '%PDF-1.4 a page this suite wrote', 0600);
$ShellCommands = array();
$TestLog = array();
setHooks(array(
  'verifiedToolPath' => function ($arguments, $count) { return array('answer', '/opt/stand-in/'.str_replace(' ', '-', strtolower((string)$arguments[0]))); },
  'sanitize' => function ($arguments, $count) { return array('answer', array($arguments[0], TRUE)); },
  'verifyFile' => function ($arguments, $count) use ($ocrInput, $ocrFolder) { return array('answer', array(TRUE, $ocrInput, $ocrInput, 'pdf', $ocrFolder.'/scan.txt', 'scan.txt')); },
  'imageMagickDecoder' => function ($arguments, $count) { return array('answer', array(TRUE, 'pdf')); },
  'sandboxCommand' => function ($arguments, $count) { return array('answer', array(FALSE, "printf '%s' refused")); },
  'shell_exec' => function ($arguments, $count) { $GLOBALS['ShellCommands'][] = $arguments[0]; return array('answer', ''); },
  'convertWithLibreOffice' => function ($arguments, $count) { return array('answer', array(FALSE, '', FALSE, FALSE)); },
  'verifyDocumentConversionEngine' => function ($arguments, $count) { return array('answer', array(FALSE, 0)); },
  'runVirusScan' => function ($arguments, $count) { notHere('runVirusScan'); }));
list ($ocrSucceeded, $ocrFailed, $ocrOutputs) = ocr\ocrFiles('scan.pdf', 'scan', 'txt', 2);
check('an OCR page split a required sandbox cannot isolate is refused with 15012, & nothing is run', $ShellCommands === array() && logHas('ERROR!!! 15012,')
  && !logHas('This OCR page split will run unsandboxed'), json_encode($ShellCommands).' '.implode(' | ', $TestLog));
check('& the operation says it failed', $ocrSucceeded === FALSE && $ocrFailed === TRUE && $ocrOutputs === array());
setHooks(array());


// Part 6. Identify's runner has the shell give way to the command it runs, so its watchdog's kill reaches the command itself.
// The shell this host has keeps sh -c between PHP & a command, & timeout is bubblewrap's parent, so killing the shell left the
// command & the sandbox under it running. A sleep nothing else runs is run for longer than the watchdog allows.
$startedAt = microtime(TRUE);
list ($identifyRan, $identifyPrinted, $identifyWasCut, $identifyStatus) = identifyRunCommand('sleep 37.25', 65536, 1);
usleep(300000);
check('Identify\'s watchdog stops a command that runs past its time', $identifyRan === TRUE && $identifyWasCut === TRUE && (microtime(TRUE) - $startedAt) < 10.0);
check('& the process it kills is the command, so nothing it started is left running', sleepsLeft('37.25') === 0, sleepsLeft('37.25').' left');


// Part 7. --File Deletion Age Threshold-- of 0 keeps files forever, as config.php says. Until v5.0.0 a 0 reached cleanDataLocation(),
// where every session is older than nothing, so every request, a bare -c & the Resource Manager's sweep removed every session.
function thresholdIs($configured) {
  global $DeleteThreshold, $TestLog;
  $DeleteThreshold = $configured;
  $TestLog = array();
  return resolveDeleteThreshold(); }
check('a threshold of 0 sweeps nothing, & is no warning', thresholdIs(0) === array(FALSE, 60) && $TestLog === array());
check('a threshold of 45 sweeps what is older than 45 minutes', thresholdIs(45) === array(TRUE, 45) && $TestLog === array());
check('a threshold written as digits is read', thresholdIs('90') === array(TRUE, 90) && $TestLog === array());
check('a negative threshold is warned of, & 60 is used', thresholdIs(-5) === array(TRUE, 60) && logHas('is not a whole number of minutes'));
check('a threshold that is not a whole number is warned of, & 60 is used', thresholdIs(2.5) === array(TRUE, 60) && logHas('is not a whole number of minutes')
  && thresholdIs('never') === array(TRUE, 60) && logHas('is not a whole number of minutes'));
$poolFolder = makeFolder(scratchPath('pool'), 0700);
$ConvertTemp = makeFolder(scratchPath('temp'), 0700);
$ConvertLoc = makeFolder(scratchPath('data'), 0700);
$PrimaryConvertLoc = $ConvertLoc;
$AdditionalConvertLocs = array();
$ProtectedRootDirs = array();
$RequiredDirs = array();
$InstLoc = $tree;
$SweptAt = array();
setHooks(array(
  'cleanDataLocation' => function ($arguments, $count) { $GLOBALS['SweptAt'][] = $arguments[2]; return array('answer', array(TRUE, FALSE)); },
  'verifyRequiredDirs' => function ($arguments, $count) { return array('answer', array(TRUE, array(), 3)); },
  'enumerateDataLocations' => function ($arguments, $count) use ($poolFolder) { return array('answer', array(array('Path' => $poolFolder, 'Type' => 'primary'))); },
  'runAsAccount' => function ($arguments, $count) { notHere('runAsAccount'); }));
// Runs a sweep with the threshold configured. Gives back what it returned, the thresholds each location was swept by & what it printed.
function sweepWith($configured, $sweepFunction, $sweepArguments) {
  global $DeleteThreshold, $SweptAt, $TestLog;
  $DeleteThreshold = $configured;
  $SweptAt = $TestLog = array();
  ob_start();
  $sweepAnswer = call_user_func_array($sweepFunction, $sweepArguments);
  $sweepPrinted = (string)ob_get_clean();
  return array($sweepAnswer, $SweptAt, $sweepPrinted); }
list ($sweepAnswer, $sweptAt) = sweepWith(0, 'sw\\prepareWebRequest', array());
check('a request sweeps nothing at a threshold of 0, & is still served', $sweepAnswer === TRUE && $sweptAt === array(), json_encode($sweptAt));
list ($sweepAnswer, $sweptAt) = sweepWith(45, 'sw\\prepareWebRequest', array());
check('a request sweeps the temporary location & the data location by the threshold', $sweepAnswer === TRUE && $sweptAt === array(45, 45), json_encode($sweptAt));
list ($sweepAnswer, $sweptAt, $sweepPrinted) = sweepWith(0, 'sw\\runEngineCleanCommand', array(''));
check('a bare -c sweeps nothing at a threshold of 0, & says so', $sweepAnswer === 0 && $sweptAt === array() && strpos($sweepPrinted, 'keeps files forever') !== FALSE, json_encode($sweptAt));
list ($sweepAnswer, $sweptAt) = sweepWith(0, 'sw\\runEngineCleanCommand', array('now'));
check('-c now sweeps every session, whatever the threshold', $sweepAnswer === 0 && $sweptAt === array(0, 0), json_encode($sweptAt));
list ($sweepAnswer, $sweptAt) = sweepWith(45, 'sw\\runEngineCleanCommand', array('0'));
check('-c 0 sweeps every session too, because the operator named 0 minutes', $sweepAnswer === 0 && $sweptAt === array(0, 0), json_encode($sweptAt));
list ($sweepAnswer, $sweptAt) = sweepWith(45, 'sw\\runEngineCleanCommand', array(''));
check('a bare -c sweeps by the threshold', $sweepAnswer === 0 && $sweptAt === array(45, 45), json_encode($sweptAt));
list ($sweepAnswer, $sweptAt) = sweepWith(0, 'sw\\cleanEveryConvertLoc', array());
check('the Resource Manager\'s sweep sweeps nothing at a threshold of 0', $sweepAnswer === array(TRUE, 0) && $sweptAt === array(), json_encode($sweptAt));
list ($sweepAnswer, $sweptAt) = sweepWith(45, 'sw\\cleanEveryConvertLoc', array());
check('the Resource Manager\'s sweep sweeps every location by the threshold', $sweepAnswer === array(TRUE, 1) && $sweptAt === array(45), json_encode($sweptAt));
setHooks(array());

finish('test_limits');
