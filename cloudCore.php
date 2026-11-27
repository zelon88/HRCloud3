<?php
// / -----------------------------------------------------------------------------------
// / Copyright information ...
// / HRCloud3, Copyright on 11/26/2026 by Justin Grimes, www.github.com/zelon88
// /
// / License information ...
// / This project is protected by the GNU GPLv3 Open-Source license.
// / https://www.gnu.org/licenses/gpl-3.0.html
// /
// / Application information ...
// / HRCloud3 is a home-hosted cloud storage platform built on the HRProprietary Engine.
// / v0.9.8 is the rebuild's first step. It answers the Engine's commands & offers no web interface yet.
// /
// / File information ...
// / v0.9.8.
// / HRCloud3's entry point, begun from the HRProprietary Engine's Starter.
// / It boots through the kernel as every application on the Engine does, & supplies only what the
// / contract asks of an application: its pins, its name before config.php is read, its half of
// / config.php & its contract. Everything else is the kernel's & the Engine's.
// / Documentation/Build/Tests/test_hrcloud3-cli.php installs & runs it.
// /
// / Hardware requirements ...
// / This application requires at least a Raspberry Pi Model B+ or greater.
// / This application will run on just about any x86 or x64 computer.
// /
// / Dependency requirements ...
// / The authoritative list of dependencies is Resources/Engine/depends.php & Resources/Contract/depends.php, the Engine's half & the application's, & nothing else.
// / Run  php cloudCore.php -v  to see what this installation has.
// /
// / <3 Open-Source
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to verify that this installation is complete, current & usable.
// / Accepts nothing. Returns a verification boolean, the path of the config file, the version
// / versionInfo.php reports & the core loaded flag, in that order.
// / The pins below are this application's, & they are all it supplies. Each Engine component is
// / pinned exactly, at the version it declares, & config.php at the oldest one this file accepts.
// / Everything else is the kernel's verifyInstallationState(), which every application on the
// / Engine boots through. It finds who runs this process, reads versionInfo.php & config.php, checks
// / both against these pins, derives every location & reads the install secret. Every failure other
// / than the secret halts there, because nothing downstream can run without a config file or against
// / a mismatched component.
// / An application that serves pages also pins its interfaces & its language packs, as
// / $RequiredGuiVersion & $RequiredLanguageVersion. HRCloud3 has neither yet, so it pins neither.
function verifyInstallation() {
  // / Set variables.
  global $HRCloud3Version, $EngineApplicationVersion, $RequiredConfigVersion, $RequiredSetupCoreVersion, $RequiredDependencyCoreVersion, $RequiredDependsVersion, $RequiredPipelineCoreVersion, $RequiredWorkflowCoreVersion, $RequiredAuthenticationCoreVersion, $RequiredEmailCoreVersion, $RequiredEventCoreVersion, $RequiredEngineVersion, $RequiredConfigScript, $RequiredSecretVersion, $CoreLoaded, $Version, $EnableMemoryProtection;
  $InstallationIsVerified = FALSE;
  $ConfigFile = '';
  // / Every file of this application's own refuses to run unless this is set, so it is set before the kernel reads one.
  $CoreLoaded = TRUE;
  // / This application's own version, which Resources/versionInfo.php must report as well.
  // / It is handed to the Engine here, where it is defined, so there is only ever one copy.
  $HRCloud3Version = 'v0.9.8';
  $HRCloud3Version = ltrim($HRCloud3Version, 'vV');
  $EngineApplicationVersion = $HRCloud3Version;
  // / The oldest config.php this file accepts. A newer one carrying every setting it needs is fine.
  // / Raise it only when this application adds or removes a setting of its own, with $ConfigVersion in its half of the template.
  $RequiredConfigVersion = 'v0.9.8';
  $RequiredConfigVersion = ltrim($RequiredConfigVersion, 'vV');
  // / Each Engine core, & this application's half of the dependency manifest, is an exact match.
  // / A component built for another release may not be called safely, so it is judged before it is loaded.
  // / php cloudCore.php --build --pins --write writes each of these from the file it names, so none is moved by hand.
  $RequiredSetupCoreVersion = 'v4.3.3';
  $RequiredSetupCoreVersion = ltrim($RequiredSetupCoreVersion, 'vV');
  $RequiredDependencyCoreVersion = 'v4.3.4';
  $RequiredDependencyCoreVersion = ltrim($RequiredDependencyCoreVersion, 'vV');
  $RequiredDependsVersion = 'v0.9.8';
  $RequiredDependsVersion = ltrim($RequiredDependsVersion, 'vV');
  $RequiredPipelineCoreVersion = 'v4.3.2';
  $RequiredPipelineCoreVersion = ltrim($RequiredPipelineCoreVersion, 'vV');
  $RequiredWorkflowCoreVersion = 'v4.3.1';
  $RequiredWorkflowCoreVersion = ltrim($RequiredWorkflowCoreVersion, 'vV');
  $RequiredAuthenticationCoreVersion = 'v4.2.3';
  $RequiredAuthenticationCoreVersion = ltrim($RequiredAuthenticationCoreVersion, 'vV');
  $RequiredEmailCoreVersion = 'v4.2.0';
  $RequiredEmailCoreVersion = ltrim($RequiredEmailCoreVersion, 'vV');
  $RequiredEventCoreVersion = 'v4.3.1';
  $RequiredEventCoreVersion = ltrim($RequiredEventCoreVersion, 'vV');
  // / The Engine is an exact match as well. It is bundled with this application rather than installed
  // / beside it, so the pin & the file it points at are replaced together. The build runs inside the
  // / Engine & cannot write this pin, so it is moved by hand, with the version engine.php declares.
  $RequiredEngineVersion = 'v4.3.4';
  $RequiredEngineVersion = ltrim($RequiredEngineVersion, 'vV');
  // / The bootstrap script & the install secret, each an exact match. The secret's is the format
  // / of a file the kernel writes, so no shipped file declares it & it is moved by hand.
  $RequiredConfigScript = 'v4.0.1';
  $RequiredConfigScript = ltrim($RequiredConfigScript, 'vV');
  $RequiredSecretVersion = 'v3.7.7';
  $RequiredSecretVersion = ltrim($RequiredSecretVersion, 'vV');
  // / The kernel verifies the installation against the pins above.
  list ($InstallationIsVerified, $ConfigFile) = verifyInstallationState(__FILE__, $HRCloud3Version, $RequiredConfigVersion);
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  // / $InstallationIsVerified, $ConfigFile & $CoreLoaded are not purged, because they are return values.
  purgeSensitiveMemory($EnableMemoryProtection);
  return array($InstallationIsVerified, $ConfigFile, $Version, $CoreLoaded); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to verify that every input a request carries is properly sanitized.
// / Accepts nothing. Returns a validity boolean & the two session tokens, in that order.
// / A session's tokens are the only input HRCloud3 reads from a request yet, & the kernel derives a session's identity from them.
// / A command line carries no token, so both are empty there & the kernel makes a pair.
// / An application reads each input it accepts here, through sanitize(), & nowhere else.
function verifyInputs() {
  // / Set variables.
  global $EnableMemoryProtection;
  $InputsAreVerified = TRUE;
  $Token1 = $Token2 = '';
  $variableIsSanitized = array();
  $sanitizedCheck = FALSE;
  $checkIndex = 0;
  if (isset($_POST['Token1'])) list ($Token1, $variableIsSanitized[$checkIndex++]) = sanitize($_POST['Token1'], TRUE);
  if (isset($_POST['Token2'])) list ($Token2, $variableIsSanitized[$checkIndex++]) = sanitize($_POST['Token2'], TRUE);
  // / Check the list of error check results & see if any errors occurred.
  foreach ($variableIsSanitized as $sanitizedCheck) if (!$sanitizedCheck) $InputsAreVerified = FALSE;
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  // / $InputsAreVerified, $Token1 & $Token2 are not purged, because they are return values.
  purgeSensitiveMemory($EnableMemoryProtection, $variableIsSanitized, $sanitizedCheck, $checkIndex);
  return array($InputsAreVerified, $Token1, $Token2); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to set this application's own global variables for the session.
// / Accepts nothing. Returns a validity boolean, which is TRUE once every one has been set.
// / The Engine's are set before this runs, by the Engine's startEngine(), & the data locations by the kernel.
// / HRCloud3 keeps none of its own yet, so this is where it sets the first of them.
function verifyGlobals() {
  // / Set variables.
  global $EnableMemoryProtection;
  $GlobalsAreVerified = TRUE;
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  // / $GlobalsAreVerified is not purged, because it is the return value.
  purgeSensitiveMemory($EnableMemoryProtection);
  return array($GlobalsAreVerified); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to describe this application's own half of config.php to the configuration utility.
// / Accepts nothing. Returns the model, keyed by section, which the contract names in $EngineConfigModelProvider.
// / The Engine models its own half in engineConfigModel(), & --config reads the two as one.
// / This array changes when this application's half of the template, Resources/Contract/config-template.php, changes.
// / A section the Engine's half shares is one section, & its name must match config.php exactly.
// / Each variable carries its Type, the variable it Depends on, & the Description config.php gives it.
// / HRCloud3 keeps no setting of its own yet, so its half holds only $ConfigVersion.
function applicationConfigModel() {
  // / Set variables.
  global $EnableMemoryProtection;
  $ConfigModel = array();
  $ConfigModel = array(
    // / $ConfigVersion is declared above the first section header, so it belongs to no section.
    // / It is modelled here as read only, so no reset & no edit touches it.
    'Unsectioned' => array('Writable' => FALSE, 'Variables' => array(
      'ConfigVersion' => array('Type' => 'version', 'Depends' => '', 'Description' => 'Config Version. Read only. Raised by an update & by a repair.'))));
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  // / $ConfigModel is not purged, because it is the return value.
  purgeSensitiveMemory($EnableMemoryProtection);
  return $ConfigModel; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to give the help -h prints what only this application knows.
// / Accepts nothing. Returns the description, the arguments, the sections & the address the Engine's showHelpInfo() prints, keyed by those names.
// / The help is the Engine's, so it lists the Engine's commands itself. HRCloud3 has no command of its own yet.
function applicationHelpInfo() {
  // / Set variables.
  $HelpInfo = array(
    'Description' => 'A home-hosted cloud storage platform built on the HRProprietary Engine. This release is the rebuild\'s first step, & offers no web interface yet.',
    'Arguments' => array(),
    'Sections' => array(
      'Documentation' => array(
        '  README.md                                   What HRCloud3 is, & where its rebuild stands.',
        '  Documentation/CHANGELOG.txt                 Every change made to HRCloud3, newest first.',
        '  Documentation/ABOUT_AUTHENTICATION.txt      Accounts, & the  --account  commands that manage them.',
        '  Documentation/ERROR_DESCRIPTIONS.txt        Every warning & numbered error, its cause & its fix.')),
    'Address' => 'https://github.com/zelon88/HRCloud3');
  return $HelpInfo; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / The main logic of the program that makes use of the functions above.

// / The kernel is checked, then loaded, before anything else runs.
// / Everything below calls into it, quickDie included, so this is the one place in the
// / application allowed to halt without quickDie, because a missing kernel takes quickDie with
// / it. Each halt prints its message & exits with status one.
// / It is found beside this file rather than through $InstLoc, which comes from a
// / configuration that has not been read yet & may name a different installation entirely.
// / The version is read with the same pattern readComponentVersion uses, because that
// / function lives in the kernel & so cannot be asked before it is loaded. A mismatched
// / kernel is never executed, the same guarantee a mismatched Engine gets.
// / The build cannot write this pin or the Engine's, because it runs inside both. Move them
// / by hand, with the version each file declares.
// / The name this application goes by until config.php names it. The kernel labels whatever it reports with it before config.php is read.
$DefaultApplicationName = 'HRCloud3';
$RequiredKernelVersion = 'v4.3.3';
$KernelFile = dirname(__FILE__).DIRECTORY_SEPARATOR.'Resources'.DIRECTORY_SEPARATOR.'Kernel'.DIRECTORY_SEPARATOR.'kernel.php';
$KernelVersionMatch = array();
if (!is_readable($KernelFile)) {
  print('ERROR!!! '.$DefaultApplicationName.'-28006, The kernel is missing or unreadable at '.$KernelFile.'! Nothing can be loaded or logged without it.'.PHP_EOL);
  exit(1); }
if (!preg_match('/\$KernelVersion\s*=\s*\'([^\']+)\'/', (string)@file_get_contents($KernelFile), $KernelVersionMatch) or ltrim($KernelVersionMatch[1], 'vV') !== ltrim($RequiredKernelVersion, 'vV')) {
  print('ERROR!!! '.$DefaultApplicationName.'-28007, The kernel at '.$KernelFile.' reports '.(isset($KernelVersionMatch[1]) ? $KernelVersionMatch[1] : 'no version').' & this application requires '.$RequiredKernelVersion.'!'.PHP_EOL);
  exit(1); }
$KernelVersionMatch = array();
require_once ($KernelFile);

// / Root runs nothing an account other than root could have changed. The tree this file is in
// / is read as soon as the kernel that reads it is loaded, & before anything else in it runs.
// / The check is consumed & not acted on further here. verifyCodeIsTrustedByRoot halts every
// / command but a repair itself, says so, & a repair reads the tree again as it finishes, so
// / FALSE reaching this line means a repair that has already been warned about is running.
// / This file names itself, so a refusal names the command that gives the tree back to root.
$CodeIsTrusted = verifyCodeIsTrustedByRoot(dirname(__FILE__), basename(__FILE__));

// / The following code resets PHP's time limit for execution.
$TimeReset = setTimeLimit();
if (!$TimeReset) quickDie('Could not set the execution timer!', 3);

// / The following code sets date & time related variables.
list ($TimeIsSet, $Date, $Time, $EpochTime) = verifyTime();
if (!$TimeIsSet or !$Date or !$Time) quickDie('Could not verify timezone!', 4);

// / The following code verifies that the installation is valid.
list ($InstallationIsVerified, $ConfigFile, $Version, $CoreLoaded) = verifyInstallation();
if (!$InstallationIsVerified) quickDie('Could not verify installation!', 5);

// / The following code verifies that string inputs to the core are properly sanitized.
list ($InputsAreVerified, $Token1, $Token2) = verifyInputs();
if (!$InputsAreVerified) quickDie('Could not verify inputs!', 6);

// / The following code verifies enough user information to generate a unique session identifier.
list ($SessionIsVerified, $IP, $HashedUserAgent) = verifySession();
if (!$SessionIsVerified) quickDie('Could not verify session!', 7);

// / The following code verifies the tokens supplied by the user, if any.
// / If no tokens were supplied by the user, generate new ones.
list ($TokensAreValid, $Token1, $Token2) = verifyTokens($Token1, $Token2);

// / The following code generates a series of unique session identifiers.
list ($SesHashIsVerified, $SesHash, $SesHash2, $SesHash3, $SesHash4) = verifySesHash($Token1);
if (!$SesHashIsVerified) quickDie('Could not verify unique session identifier!', 8);

// / The following code reads the Engine configuration, which is data rather than code.
// / It is read here, before the logging environment, & the Engine itself is verified &
// / loaded later. verifyLogs() decides the name of the logfile & some of that decision belongs to
// / the Engine, so the settings have to exist before it runs.
// / An absent configuration is not an error. Every setting it carries has a local fallback
// / & the Engine reports its own absence when it is verified.
$EngineConfigPath = $InstLoc.DIRECTORY_SEPARATOR.'Resources'.DIRECTORY_SEPARATOR.'Engine'.DIRECTORY_SEPARATOR.'engineConfig.php';
if (file_exists($EngineConfigPath)) require_once ($EngineConfigPath);

// / The following code verifies the logging environment.
list ($LogFileExists, $LogFile) = verifyLogs();
if (!$LogFileExists) quickDie('Could not verify logging environment!', 9);
if ($Verbose) logEntry('Verified logging environment.');

// / The following code verifies & loads the Engine, which provides the environment this application runs in rather than anything it does.
// / The kernel's loadEngine() verifies it, loads it & has it start, & the Engine's startEngine() builds everything a request hangs off it.
// / It is loaded before this application's own globals, & that position is not negotiable, because the session's folders are the Engine's.
// / An Engine that is missing or mismatched is a warning, & what is lost is named while everything that does not depend on it carries on.
list ($EngineActive, $EngineVersion) = loadEngine();

// / The following code sets this application's own global variables for the session.
list ($GlobalsAreVerified) = verifyGlobals();
if (!$GlobalsAreVerified) errorEntry('Could not verify globals!', 11, TRUE);
else if ($Verbose) logEntry('Verified globals.');

// / The following code decides if the security context being attempted matches a valid CLI or web request.
// / The kernel's runCommandLine() reads the command line, answers -v & -h, hands an argument beginning --app to this application's
// / handler & every other command to the Engine. It names this file in what it prints.
list ($CommandLineHandled, $UserType, $CommandLineStatus) = runCommandLine(basename(__FILE__));

// / HRCloud3 has no web interface yet, so a web request is refused in one sentence & served nothing.
// / The refusal is a warning rather than an error, because a request this application handled correctly is not a fault.
// / Nothing is admitted, so no session folder is made & nothing is swept for a request that is served nothing.
// / 503 says the service is not available yet, which is true until the interface arrives.
if (!$CommandLineHandled && $UserType === 'web') {
  warningEntry('A web request was refused, because HRCloud3 has no web interface yet.');
  if (!headers_sent()) {
    http_response_code(503);
    header('Content-Type: text/plain; charset=utf-8'); }
  print('HRCloud3 is being rebuilt & offers no web interface yet. Run  php '.basename(__FILE__).' -h  on the server.'.PHP_EOL); }

// / Stop execution of the application, with the status a command line earned.
// / A web request never sees a status, so it exits 0.
// / A handled command line exits with the status its command set.
exit($CommandLineHandled ? (int)$CommandLineStatus : 0);
// / -----------------------------------------------------------------------------------
