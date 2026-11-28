<?php
// / -----------------------------------------------------------------------------------
// / Copyright Information ...
// / ScanCore, Copyright on 3/31/2024 by Justin Grimes, www.github.com/zelon88
// /
// / License Information ...
// / This project is protected by the GNU GPLv3 Open-Source license.
// / BSD or MIT licensing is available. Reach out to @zelon88 for more information.
// / https://www.gnu.org/licenses/gpl-3.0.html
// /
// / Application Information ...
// / This application is designed to scan files & folders for viruses.
// /
// / File Information ...
// / v2.0.0.
// / ScanCore Core.
// / This file contains the core logic of the application.
// / Run  php ScanCore.php -help  to see every switch this file accepts.
// / Documentation/ABOUT_COMMAND_LINE_ARGUMENTS.txt says what each switch does & what it falls back to.
// / Documentation/ABOUT_REPORTS.txt says what a caller may read from the output & from the exit status.
// /
// / Hardware Requirements ...
// / This application requires at least a Raspberry Pi Model B+ or greater.
// / This application will run on just about any x86 or x64 computer.
// /
// / Dependency Requirements ...
// / This application requires PHP 8.0 or later, run from a command line on Linux or Windows.
// / The zlib extension reads compressed container members & png chunks, which are reported as incomplete without it.
// / An update uses Git, or downloads each file with PHP, or with cURL where PHP may not open a URL. None of them is needed to scan.
// / The posix extension hands new files to the owner of the installation when ScanCore is run as root.
// /
// / <3 Open-Source
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to release sensitive values from memory as soon as they stop being needed.
// / Every target must be passed as its own argument so no copy of the value is created.
// / Collecting targets into an array before the call would copy each value first.
function purgeSensitiveMemory(&...$variables) {
  // / Set variables.
  $MemoryPurged = FALSE;
  $purgeCount = 0;
  // / Overwrite each supplied reference so the caller's value is released immediately.
  foreach ($variables as $purgeKey => $purgeValue) {
    $variables[$purgeKey] = NULL;
    $purgeCount++; }
  if ($purgeCount > 0) $MemoryPurged = TRUE;
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  $purgeKey = $purgeValue = $purgeCount = NULL;
  unset($purgeKey, $purgeValue, $purgeCount);
  return $MemoryPurged; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to reset a narrow scope variable to a known value inside a loop body.
// / Assigning NULL before the new value releases the value it held straight away.
// / Nothing is purged here, because the target is the caller's own variable & the new value is a copy the caller still holds.
function redeclare(&$targetVariable, $newValue = NULL) {
  // / Set variables.
  $VariableIsRedeclared = FALSE;
  $targetVariable = NULL;
  $targetVariable = $newValue;
  $VariableIsRedeclared = TRUE;
  return $VariableIsRedeclared; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to sanitize any supplied input before that input gets used.
// / Arrays are walked recursively so a nested value cannot slip past unsanitized.
// / Strings lose null bytes & control characters, which are never valid in our inputs.
// / Other scalar types are already safe & are returned unaltered.
function sanitize($input) {
  // / Set variables.
  $SanitizedInput = $input;
  // / Walk arrays so every member is sanitized individually.
  if (is_array($input)) {
    $SanitizedInput = array();
    foreach ($input as $inputKey => $inputValue) $SanitizedInput[sanitize($inputKey)] = sanitize($inputValue); }
  // / Strip characters from strings that no legitimate argument would ever carry.
  if (is_string($input)) {
    $SanitizedInput = str_replace(chr(0), '', $input);
    $SanitizedInput = preg_replace('/[\x01-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $SanitizedInput);
    $SanitizedInput = trim($SanitizedInput); }
  // / Objects & resources are never expected here, so they are reduced to an empty string.
  if (is_object($input) or is_resource($input)) $SanitizedInput = '';
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($inputKey, $inputValue, $input);
  return $SanitizedInput; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to normalize a command line switch before it gets compared.
// / A switch is a leading dash followed by letters & nothing else.
// / Any other argument, such as a path, normalizes to an empty string.
function normalizeSwitch($argument) {
  // / Set variables.
  $NormalizedSwitch = '';
  $workingArgument = sanitize($argument);
  if (is_string($workingArgument)) if (preg_match('/^-[A-Za-z]+$/', $workingArgument)) $NormalizedSwitch = strtolower($workingArgument);
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($workingArgument, $argument);
  return $NormalizedSwitch; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to compare two version numbers numerically rather than as strings.
// / A string comparison ranks 24.2 below 7.6 & ranks 3.10 below 3.9, so we never use one.
// / A leading v is stripped first because casting 'v3' to an integer yields 0.
// / The supplied version passes when it is equal to or newer than the required version.
// / Returns whether both versions could be read & whether the supplied version passes, in that order.
function compareVersions($requiredVersion, $suppliedVersion) {
  // / Set variables.
  $VersionsCompared = $SuppliedIsAcceptable = FALSE;
  $requiredParts = $suppliedParts = array();
  $partIndex = $partTop = 0;
  $requiredClean = strtolower(trim(sanitize($requiredVersion)));
  $suppliedClean = strtolower(trim(sanitize($suppliedVersion)));
  $requiredClean = ltrim($requiredClean, 'v');
  $suppliedClean = ltrim($suppliedClean, 'v');
  // / A build that reports no parseable version cannot be cleared, so it is refused.
  if (preg_match('/^[0-9]+(\.[0-9]+)*$/', $requiredClean) && preg_match('/^[0-9]+(\.[0-9]+)*$/', $suppliedClean)) {
    $VersionsCompared = TRUE;
    $requiredParts = explode('.', $requiredClean);
    $suppliedParts = explode('.', $suppliedClean);
    // / Pad the shorter version so a two part version compares fairly against a three part one.
    while (count($requiredParts) < count($suppliedParts)) $requiredParts[] = '0';
    while (count($suppliedParts) < count($requiredParts)) $suppliedParts[] = '0';
    $SuppliedIsAcceptable = TRUE;
    $partTop = count($requiredParts);
    while ($partIndex < $partTop) {
      if (intval($suppliedParts[$partIndex]) > intval($requiredParts[$partIndex])) $partIndex = $partTop;
      else {
        if (intval($suppliedParts[$partIndex]) < intval($requiredParts[$partIndex])) {
          $SuppliedIsAcceptable = FALSE;
          $partIndex = $partTop; }
        else $partIndex++; } } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($requiredParts, $suppliedParts, $requiredClean, $suppliedClean, $partIndex, $partTop, $requiredVersion, $suppliedVersion);
  return array($VersionsCompared, $SuppliedIsAcceptable); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to resolve a path against the installation directory when it is relative.
// / A microservice gets invoked from whatever directory the caller happens to occupy.
// / Resolving against the installation directory keeps logs & definitions where expected.
function resolvePath($path, $baseDirectory) {
  // / Set variables.
  global $SEP;
  $ResolvedPath = sanitize($path);
  $pathIsAbsolute = FALSE;
  // / Detect a POSIX absolute path, a Windows drive letter path & a Windows network path.
  if (strlen($ResolvedPath) > 0) {
    if (substr($ResolvedPath, 0, 1) === '/' or substr($ResolvedPath, 0, 1) === '\\') $pathIsAbsolute = TRUE;
    if (preg_match('/^[A-Za-z]:[\\\\\/]/', $ResolvedPath)) $pathIsAbsolute = TRUE; }
  if (!$pathIsAbsolute && strlen($ResolvedPath) > 0) $ResolvedPath = $baseDirectory.$SEP.$ResolvedPath;
  // / Collapse any doubled separator that the concatenation may have introduced.
  $ResolvedPath = str_replace($SEP.$SEP, $SEP, $ResolvedPath);
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($pathIsAbsolute, $path, $baseDirectory);
  return $ResolvedPath; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to run one command in a shell, where the interpreter still has one.
// / Returns whether the command was run & what it printed, in that order.
// / Every program that ScanCore runs is run from here, & they are git, curl & the PHP binary itself.
// / A caller escapes every value it places in the command.
function runShellCommand($commandLine) {
  // / Set variables.
  $CommandRan = FALSE;
  $CommandOutput = '';
  $shellResult = NULL;
  // / An interpreter with the shell switched off answers that no command was run, & nothing is attempted.
  if (function_exists('shell_exec')) {
    $shellResult = @shell_exec($commandLine);
    $CommandRan = TRUE;
    if (is_string($shellResult)) $CommandOutput = $shellResult; }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($shellResult, $commandLine);
  return array($CommandRan, $CommandOutput); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to put what a program printed onto one line.
// / A report entry is one line, so every run of whitespace, line breaks included, becomes one space.
function collapseToOneLine($printedText) {
  // / Set variables.
  $OneLine = trim(strval(preg_replace('/\s+/', ' ', strval($printedText))));
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($printedText);
  return $OneLine; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to verify that this installation can run before anything starts.
// / Returns the verification, the version of this core, the oldest configuration version it accepts & the fault, in that order.
function verifyInstallation() {
  // / Set variables.
  global $Date, $Version, $RequiredConfigVersion, $InstallationVerified, $FileCount, $DirCount, $Infected, $Suspicious, $HandlerFileCount, $CodeFileCount, $IncompleteCount, $MemberContext, $EOL, $SEP, $RP, $CoreFile, $DefaultConfigFile, $LogAvailable, $LogBytesWritten, $VerificationFault;
  // / Set the date that names a rotated report file.
  $Date = date("m_d_y");
  // / Define what version of ScanCore this core file represents.
  $Version = 'v2.0.0';
  // / Define the oldest configuration file version that this core can accept.
  // / This is only raised when a release adds or removes a configuration entry.
  // / A release that changes no entries leaves this alone, so existing configuration files keep working.
  // / Any configuration file version that is newer than the version listed below is considered acceptable.
  $RequiredConfigVersion = 'v1.9';
  // / Set the variables that describe the application & that count what a scan finds.
  $DefaultConfigFile = 'ScanCore_Config.php';
  $CoreFile = 'ScanCore.php';
  $FileCount = $DirCount = $Infected = $Suspicious = $LogBytesWritten = 0;
  $HandlerFileCount = $CodeFileCount = $IncompleteCount = 0;
  $MemberContext = '';
  $EOL = PHP_EOL;
  $SEP = DIRECTORY_SEPARATOR;
  $RP = realpath(dirname(__FILE__));
  $LogAvailable = TRUE;
  $InstallationVerified = TRUE;
  $VerificationFault = '';
  $requiredFunctions = array('hash', 'hash_file', 'hash_init', 'hash_update', 'hash_final', 'scandir', 'fopen', 'fread', 'file_put_contents', 'file_get_contents');
  // / Refuse to run anywhere except a command line, because there is no web interface here.
  if (PHP_SAPI !== 'cli') {
    $InstallationVerified = FALSE;
    $VerificationFault = 'ScanCore only runs from a command line interpreter'; }
  // / Refuse to run on an interpreter older than the documented dependency requirement.
  if (version_compare(PHP_VERSION, '8.0.0', '<')) {
    $InstallationVerified = FALSE;
    $VerificationFault = 'ScanCore requires PHP 8.0 or later, found '.PHP_VERSION; }
  // / Refuse to run when a required function has been disabled by the interpreter.
  foreach ($requiredFunctions as $requiredFunction) if (!function_exists($requiredFunction)) {
    $InstallationVerified = FALSE;
    $VerificationFault = 'A required PHP function is unavailable:  '.$requiredFunction; }
  // / Refuse to run when the installation directory cannot be resolved.
  if ($RP === FALSE or $RP === '') {
    $InstallationVerified = FALSE;
    $VerificationFault = 'Cannot resolve the ScanCore installation directory'; }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($requiredFunctions, $requiredFunction);
  return array($InstallationVerified, $Version, $RequiredConfigVersion, $VerificationFault); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to apply a hardcoded default whenever a configuration entry is unusable.
// / A subsystem that can fall back must fall back before it errors.
// / Faults are collected rather than logged because logging is not available this early.
// / The type may be boolean, string, integer, array or list.
function applyConfigFallback(&$setting, $defaultValue, $settingName, $settingType) {
  // / Set variables.
  global $ConfigFaults;
  $SettingAccepted = TRUE;
  // / Detect a setting that was never defined by the configuration file at all.
  if (!isset($setting)) $SettingAccepted = FALSE;
  else {
    if ($settingType === 'boolean' && !is_bool($setting)) $SettingAccepted = FALSE;
    if ($settingType === 'string' && (!is_string($setting) or $setting === '')) $SettingAccepted = FALSE;
    if ($settingType === 'array' && (!is_array($setting) or count($setting) === 0)) $SettingAccepted = FALSE;
    // / An empty list is a real answer & is accepted as it was written, where an empty array is not.
    if ($settingType === 'list' && !is_array($setting)) $SettingAccepted = FALSE;
    if ($settingType === 'integer' && (!is_numeric($setting) or intval($setting) < 1)) $SettingAccepted = FALSE; }
  // / Fall back to the hardcoded default & record why, so the administrator can see it.
  if (!$SettingAccepted) {
    $setting = $defaultValue;
    $ConfigFaults[] = 'Configuration entry $'.$settingName.' is missing or invalid, using the built in default instead.'; }
  if ($SettingAccepted && $settingType === 'integer') $setting = intval($setting);
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($defaultValue, $settingName, $settingType);
  return $SettingAccepted; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to find a switch among the arguments before the arguments are parsed in full.
// / The configuration file, the definitions file & the report file must each be known before anything is loaded or logged.
// / Returns whether the switch was supplied & the value that followed it, in that order.
// / The value is an empty string where nothing followed the switch.
function findSwitch($argumentList, $switchNames) {
  // / Set variables.
  $SwitchSupplied = FALSE;
  $SwitchValue = '';
  $argumentKey = 0;
  $argumentText = '';
  foreach ($argumentList as $argumentKey => $argumentText) if (in_array(normalizeSwitch($argumentText), $switchNames, TRUE)) {
    $SwitchSupplied = TRUE;
    $SwitchValue = isset($argumentList[$argumentKey + 1]) ? strval(sanitize($argumentList[$argumentKey + 1])) : ''; }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($argumentKey, $argumentText, $argumentList, $switchNames);
  return array($SwitchSupplied, $SwitchValue); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to load the configuration file & settle the files that depend on it.
// / ScanCore_Config.php is loaded unless the -configfile argument names another file.
// / The configured definitions file is used unless the -defsfile argument names another file.
// / The configured report file is used unless the -reportfile argument names another file.
// / A configuration file that the interpreter cannot parse is refused, & what the interpreter said about it is kept for the error.
// / Returns whether the file loaded, whether the definitions file exists, the path of the file & whether its version is acceptable, in that order.
function loadConfig($requiredConfigVersion, $argumentList) {
  // / Set variables.
  global $ConfigFilePath, $RP, $SEP, $EOL, $ConfigFile, $ScanLoc, $DefsFile, $ConfigVersion, $ConfigLoaded, $DefsExist, $ReportFile, $ReportDir, $ReportFileName, $RequiredDirs, $InstallDir, $MaxLogSize, $MemoryLimit, $ChunkSize, $DefaultMemoryLimit, $DefaultChunkSize, $DefaultMaxLogSize, $DefinitionRepositoryName, $DefinitionUpdates, $DefinitionUpdateDomain, $DefinitionUpdateURL, $DefInstallDir, $DefGitDir, $ApplicationRepositoryName, $ApplicationUpdates, $ApplicationUpdateDomain, $ApplicationUpdateURL, $AppInstallDir, $AppGitDir, $DefinitionsUpdateSubscriptions, $DefsFileName, $Verbose, $Debug, $UpdateMethod, $DefinitionBranchName, $ApplicationBranchName, $ApplicationUpdateSubscriptions, $VersionsMatch, $ConfigFaults, $MaxScanDepth, $FollowSymlinks, $ConnectionTimeout, $MinimumDataSignatureLength, $ClassifyContent, $ClassifierFileName, $ClassifierFile, $MinimumLanguageSignatures, $EnabledClassifiers, $InspectArchivedContent, $MaxArchiveEntries, $MaxArchiveEntrySize, $MaxArchiveTotalSize, $MaxArchiveCompressionRatio, $ReportSuspicious, $PreserveOwnership, $OwnerUser, $OwnerGroup, $FilePermissions, $DirectoryPermissions, $UseHashIndex, $HashIndexThreshold, $InspectImages, $MaxImagePixels, $MaxImageChunkSize, $MaxArchiveDepth, $DefaultConfigFile, $ConfigLoadFault;
  $ConfigLoaded = $DefsExist = $VersionsMatch = FALSE;
  $ConfigFaults = array();
  $ConfigLoadFault = '';
  $ConfigFile = $DefaultConfigFile;
  $ConfigFilePath = $RP.$SEP.$ConfigFile;
  $requireResult = $switchSupplied = FALSE;
  $switchValue = '';
  $configFault = NULL;
  if (!is_array($argumentList)) $argumentList = array();
  list($switchSupplied, $switchValue) = findSwitch($argumentList, array('-configfile', '-cf'));
  if ($switchSupplied && $switchValue !== '') $ConfigFilePath = resolvePath($switchValue, $RP);
  if ($switchSupplied && $switchValue === '') $ConfigFaults[] = 'The -configfile argument was supplied without a path, using the default instead.';
  // / Load the configuration file located at $ConfigFilePath.
  if (!file_exists($ConfigFilePath) or is_dir($ConfigFilePath)) $ConfigLoadFault = 'The file does not exist.';
  else {
    try { $requireResult = require_once ($ConfigFilePath); }
    catch (Throwable $configFault) {
      $requireResult = FALSE;
      $ConfigLoadFault = 'The interpreter said:  '.$configFault->getMessage().' on line '.$configFault->getLine().'.'; }
    if ($requireResult !== FALSE) $ConfigLoaded = TRUE;
    if ($requireResult === FALSE && $ConfigLoadFault === '') $ConfigLoadFault = 'The file returned FALSE.'; }
  // / Record the configuration file that actually loaded so later functions can find it.
  if ($ConfigLoaded) $ConfigFile = $ConfigFilePath;
  // / Validate every configuration entry & fall back to a working default when needed.
  if ($ConfigLoaded) list($VersionsMatch) = validateConfig($requiredConfigVersion);
  list($switchSupplied, $switchValue) = findSwitch($argumentList, array('-defsfile', '-df'));
  if ($switchSupplied && $switchValue !== '') $DefsFile = resolvePath($switchValue, $RP);
  if ($switchSupplied && $switchValue === '') $ConfigFaults[] = 'The -defsfile argument was supplied without a path, using the configured file instead.';
  // / The report file is settled before anything is logged, so nothing is written to a report the caller did not name.
  list($switchSupplied, $switchValue) = findSwitch($argumentList, array('-reportfile', '-rf', '-logfile', '-lf'));
  if ($ConfigLoaded && $switchSupplied && $switchValue !== '') $ReportFile = resolvePath($switchValue, $ReportDir);
  // / Create a blank definitions file when an update was requested against a missing one.
  list($switchSupplied, $switchValue) = findSwitch($argumentList, array('-updatedefinitions', '-ud'));
  if ($switchSupplied && is_string($DefsFile) && $DefsFile !== '' && !file_exists($DefsFile)) @file_put_contents($DefsFile, '');
  // / Check whether the definitions file is present.
  if (is_string($DefsFile) && $DefsFile !== '') if (file_exists($DefsFile) && !is_dir($DefsFile)) $DefsExist = TRUE;
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($requireResult, $switchSupplied, $switchValue, $configFault, $requiredConfigVersion, $argumentList);
  return array($ConfigLoaded, $DefsExist, $ConfigFilePath, $VersionsMatch); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to validate configuration entries & derive the paths that depend on them.
// / The configuration file is a minimum match, so a newer configuration is acceptable.
// / A configuration that is older than this core requires is refused, because entries may be absent.
function validateConfig($requiredConfigVersion) {
  // / Set variables.
  global $RP, $SEP, $ConfigVersion, $ScanLoc, $DefsFile, $DefsFileName, $ReportFile, $ReportDir, $ReportFileName, $RequiredDirs, $InstallDir, $MaxLogSize, $MemoryLimit, $ChunkSize, $DefaultMemoryLimit, $DefaultChunkSize, $DefaultMaxLogSize, $DefinitionRepositoryName, $DefinitionUpdates, $DefinitionUpdateDomain, $DefinitionUpdateURL, $DefInstallDir, $DefGitDir, $ApplicationRepositoryName, $ApplicationUpdates, $ApplicationUpdateDomain, $ApplicationUpdateURL, $AppInstallDir, $AppGitDir, $DefinitionsUpdateSubscriptions, $Verbose, $Debug, $UpdateMethod, $DefinitionBranchName, $ApplicationBranchName, $ApplicationUpdateSubscriptions, $ConfigFaults, $MaxScanDepth, $FollowSymlinks, $ConnectionTimeout, $MinimumDataSignatureLength, $ClassifyContent, $ClassifierFileName, $ClassifierFile, $MinimumLanguageSignatures, $EnabledClassifiers, $InspectArchivedContent, $MaxArchiveEntries, $MaxArchiveEntrySize, $MaxArchiveTotalSize, $MaxArchiveCompressionRatio, $ReportSuspicious, $PreserveOwnership, $OwnerUser, $OwnerGroup, $FilePermissions, $DirectoryPermissions, $UseHashIndex, $HashIndexThreshold, $InspectImages, $MaxImagePixels, $MaxImageChunkSize, $MaxArchiveDepth, $CoreFile;
  $VersionsMatch = $versionsCompared = FALSE;
  // / Compare the configuration version numerically against the oldest version this core accepts.
  list($versionsCompared, $VersionsMatch) = compareVersions($requiredConfigVersion, isset($ConfigVersion) ? $ConfigVersion : '');
  // / A version that could not be compared is not acceptable, & error 4 says what the file reported.
  if (!$versionsCompared) $VersionsMatch = FALSE;
  // / Validate the entries that control update behaviour.
  applyConfigFallback($ApplicationUpdates, TRUE, 'ApplicationUpdates', 'boolean');
  applyConfigFallback($ApplicationUpdateURL, 'https://github.com/zelon88/ScanCore', 'ApplicationUpdateURL', 'string');
  applyConfigFallback($ApplicationUpdateDomain, 'github.com', 'ApplicationUpdateDomain', 'string');
  applyConfigFallback($ApplicationRepositoryName, 'ScanCore', 'ApplicationRepositoryName', 'string');
  applyConfigFallback($ApplicationBranchName, 'master', 'ApplicationBranchName', 'string');
  applyConfigFallback($ApplicationUpdateSubscriptions, array('README.md', 'LICENSE', 'index.html', 'ScanCore.php', 'ScanCore_Config.php', 'ScanCore_Classifiers.def', 'Documentation/index.html', 'Documentation/CHANGELOG.txt', 'Documentation/ERROR_DESCRIPTIONS.txt', 'Documentation/DEVELOPMENT_NOTES.txt', 'Documentation/ABOUT_COMMAND_LINE_ARGUMENTS.txt', 'Documentation/ABOUT_REPORTS.txt', 'Documentation/ABOUT_SCANNING.txt', 'Documentation/ABOUT_QUALITY_CONTROL.txt', 'Documentation/SECURITY.md', 'Documentation/SECURITY.txt', 'Documentation/Build/index.html', 'Documentation/Build/scancore-check-cli.php', 'Documentation/Build/scancore-test-cli.php', 'Documentation/Logos/index.html', 'Documentation/Logos/ScanCore_banner.png', 'Documentation/Logos/ScanCore_square.png'), 'ApplicationUpdateSubscriptions', 'array');
  applyConfigFallback($DefinitionUpdates, TRUE, 'DefinitionUpdates', 'boolean');
  applyConfigFallback($DefinitionUpdateURL, 'https://github.com/zelon88/ScanCore_Definitions', 'DefinitionUpdateURL', 'string');
  applyConfigFallback($DefinitionUpdateDomain, 'github.com', 'DefinitionUpdateDomain', 'string');
  applyConfigFallback($DefinitionRepositoryName, 'ScanCore_Definitions', 'DefinitionRepositoryName', 'string');
  applyConfigFallback($DefinitionBranchName, 'main', 'DefinitionBranchName', 'string');
  applyConfigFallback($DefinitionsUpdateSubscriptions, array('Virus'), 'DefinitionsUpdateSubscriptions', 'array');
  applyConfigFallback($UpdateMethod, 'git', 'UpdateMethod', 'string');
  // / Validate the entries that control scanning behaviour.
  applyConfigFallback($DefaultMaxLogSize, 1024*1024*32, 'DefaultMaxLogSize', 'integer');
  applyConfigFallback($DefaultMemoryLimit, 1024*1024*512, 'DefaultMemoryLimit', 'integer');
  applyConfigFallback($DefaultChunkSize, 1024*1024*8, 'DefaultChunkSize', 'integer');
  applyConfigFallback($MaxScanDepth, 64, 'MaxScanDepth', 'integer');
  applyConfigFallback($ConnectionTimeout, 15, 'ConnectionTimeout', 'integer');
  applyConfigFallback($MinimumDataSignatureLength, 4, 'MinimumDataSignatureLength', 'integer');
  applyConfigFallback($FollowSymlinks, FALSE, 'FollowSymlinks', 'boolean');
  applyConfigFallback($ClassifyContent, TRUE, 'ClassifyContent', 'boolean');
  applyConfigFallback($EnabledClassifiers, array('Language', 'SCAD', 'PDF', 'Document', 'Spreadsheet', 'Presentation', 'XPS', 'SVG', 'Markup', 'Stream', 'Transport', 'Model', 'Drawing', 'Ebook', 'Subtitle'), 'EnabledClassifiers', 'list');
  applyConfigFallback($MinimumLanguageSignatures, 2, 'MinimumLanguageSignatures', 'integer');
  applyConfigFallback($InspectArchivedContent, TRUE, 'InspectArchivedContent', 'boolean');
  applyConfigFallback($MaxArchiveEntries, 4096, 'MaxArchiveEntries', 'integer');
  applyConfigFallback($MaxArchiveDepth, 5, 'MaxArchiveDepth', 'integer');
  applyConfigFallback($InspectImages, TRUE, 'InspectImages', 'boolean');
  applyConfigFallback($MaxImagePixels, 16384*16384, 'MaxImagePixels', 'integer');
  applyConfigFallback($MaxImageChunkSize, 1024*1024*8, 'MaxImageChunkSize', 'integer');
  applyConfigFallback($MaxArchiveEntrySize, 1024*1024*8, 'MaxArchiveEntrySize', 'integer');
  applyConfigFallback($MaxArchiveTotalSize, 1024*1024*64, 'MaxArchiveTotalSize', 'integer');
  applyConfigFallback($MaxArchiveCompressionRatio, 200, 'MaxArchiveCompressionRatio', 'integer');
  applyConfigFallback($ReportSuspicious, TRUE, 'ReportSuspicious', 'boolean');
  applyConfigFallback($UseHashIndex, TRUE, 'UseHashIndex', 'boolean');
  applyConfigFallback($HashIndexThreshold, 10000, 'HashIndexThreshold', 'integer');
  applyConfigFallback($PreserveOwnership, TRUE, 'PreserveOwnership', 'boolean');
  applyConfigFallback($FilePermissions, 0644, 'FilePermissions', 'integer');
  applyConfigFallback($DirectoryPermissions, 0755, 'DirectoryPermissions', 'integer');
  if (!isset($OwnerUser) or !is_string($OwnerUser)) $OwnerUser = '';
  if (!isset($OwnerGroup) or !is_string($OwnerGroup)) $OwnerGroup = '';
  applyConfigFallback($ClassifierFileName, 'ScanCore_Classifiers.def', 'ClassifierFileName', 'string');
  applyConfigFallback($Debug, FALSE, 'Debug', 'boolean');
  applyConfigFallback($Verbose, FALSE, 'Verbose', 'boolean');
  // / Validate the entries that describe where things live on disk.
  applyConfigFallback($ReportDir, 'Logs', 'ReportDir', 'string');
  applyConfigFallback($ReportFileName, 'ScanCore_Report.txt', 'ReportFileName', 'string');
  applyConfigFallback($DefsFileName, 'ScanCore_Combined_Definitions.def', 'DefsFileName', 'string');
  applyConfigFallback($InstallDir, $RP, 'InstallDir', 'string');
  if (!isset($ScanLoc) or !is_string($ScanLoc)) $ScanLoc = '';
  // / Derive every path from the installation directory so the working directory never matters.
  $InstallDir = resolvePath($InstallDir, $RP);
  // / A configuration file loaded from elsewhere reports its own folder as the installation.
  // / Fall back to the folder holding this core file whenever the core is not where it says.
  if (!file_exists($InstallDir.$SEP.$CoreFile)) {
    $ConfigFaults[] = 'Configuration entry $InstallDir does not contain '.$CoreFile.', using '.$RP.' instead.';
    $InstallDir = $RP; }
  $ReportDir = resolvePath($ReportDir, $InstallDir);
  // / A trailing separator is taken off, so the folder compares equal to the folder of the report file.
  if (strlen($ReportDir) > 1) $ReportDir = rtrim($ReportDir, '/\\');
  $ReportFile = $ReportDir.$SEP.basename($ReportFileName);
  $RequiredDirs = array($ReportDir);
  $UpdateMethod = strtolower($UpdateMethod);
  // / Fall back to the git update method when the configured method is not recognized.
  if ($UpdateMethod !== 'git' && $UpdateMethod !== 'raw') {
    $ConfigFaults[] = 'Configuration entry $UpdateMethod is not git or raw, using git instead.';
    $UpdateMethod = 'git'; }
  $MaxLogSize = $DefaultMaxLogSize;
  $MemoryLimit = $DefaultMemoryLimit;
  $ChunkSize = $DefaultChunkSize;
  // / Resolve the definitions file only when the configuration did not already do it.
  if (!isset($DefsFile) or !is_string($DefsFile) or $DefsFile === '') $DefsFile = $InstallDir.$SEP.$DefsFileName;
  $DefsFile = resolvePath($DefsFile, $InstallDir);
  // / Fall back to the definitions beside the core when the configured file is not there.
  if (!file_exists($DefsFile)) if (file_exists($InstallDir.$SEP.$DefsFileName)) {
    $ConfigFaults[] = 'Configuration entry $DefsFile does not exist, using the file beside the core instead.';
    $DefsFile = $InstallDir.$SEP.$DefsFileName; }
  // / The classifier definitions ship with the application, beside the core.
  $ClassifierFile = $InstallDir.$SEP.basename($ClassifierFileName);
  // / Build the temporary update directories from the repository names.
  // / An empty repository name would aim the cleaner at the installation directory itself.
  $DefInstallDir = $InstallDir.$SEP.basename($DefinitionRepositoryName);
  $AppInstallDir = $InstallDir.$SEP.basename($ApplicationRepositoryName);
  $DefGitDir = $DefInstallDir.$SEP.'.git';
  $AppGitDir = $AppInstallDir.$SEP.'.git';
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($versionsCompared, $requiredConfigVersion);
  return array($VersionsMatch); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to work out which user & group own anything that ScanCore creates while it runs as root.
// / The owner of the installation directory is used, unless the configuration names another.
// / Nothing is worked out for any other account, because only root may give a file away.
// / Returns TRUE when an owner was found.
function resolveOwnership($announce) {
  // / Set variables.
  global $InstallDir, $OwnerUser, $OwnerGroup, $PreserveOwnership, $OwnershipUid, $OwnershipGid, $RunningAsRoot;
  $OwnershipResolved = FALSE;
  $OwnershipUid = $OwnershipGid = -1;
  $RunningAsRoot = FALSE;
  $referenceStat = array();
  $passwordEntry = $groupEntry = array();
  if (function_exists('posix_geteuid')) if (posix_geteuid() === 0) $RunningAsRoot = TRUE;
  if ($RunningAsRoot && $PreserveOwnership) {
    // / Inherit from the installation directory unless the configuration names an owner.
    $referenceStat = @stat($InstallDir);
    if (is_array($referenceStat)) {
      $OwnershipUid = intval($referenceStat['uid']);
      $OwnershipGid = intval($referenceStat['gid']);
      $OwnershipResolved = TRUE; }
    if ($OwnerUser !== '' && function_exists('posix_getpwnam')) {
      $passwordEntry = @posix_getpwnam($OwnerUser);
      if (is_array($passwordEntry)) {
        $OwnershipUid = intval($passwordEntry['uid']);
        $OwnershipResolved = TRUE; }
      else { if ($announce) processOutput('Cannot resolve the configured OwnerUser, falling back to the installation directory owner:  '.$OwnerUser, 'warning', 0, TRUE, FALSE, FALSE); } }
    if ($OwnerGroup !== '' && function_exists('posix_getgrnam')) {
      $groupEntry = @posix_getgrnam($OwnerGroup);
      if (is_array($groupEntry)) {
        $OwnershipGid = intval($groupEntry['gid']);
        $OwnershipResolved = TRUE; }
      else { if ($announce) processOutput('Cannot resolve the configured OwnerGroup, falling back to the installation directory group:  '.$OwnerGroup, 'warning', 0, TRUE, FALSE, FALSE); } }
    if ($announce) {
      if ($OwnershipResolved) processOutput('Running as root. Anything created will be handed to uid '.$OwnershipUid.' & gid '.$OwnershipGid.'.', 'log', 0, TRUE, FALSE, FALSE);
      else processOutput('Running as root & cannot work out who should own new files, so they will stay owned by root.', 'warning', 0, TRUE, FALSE, FALSE); } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($referenceStat, $passwordEntry, $groupEntry, $announce);
  return $OwnershipResolved; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to tell whether root may change a path by its name.
// / A folder may be changed, & so may a file that has no other name.
// / A link may not, because a change made by name would reach whatever the link points at.
// / A file with a second hard link may not either, because its other name could be anywhere on the system.
function pathIsPlainForRoot($path) {
  // / Set variables.
  $PathIsPlain = FALSE;
  $pathStat = @lstat($path);
  $pathType = 0;
  if (is_array($pathStat)) {
    $pathType = intval($pathStat['mode']) & 0170000;
    if ($pathType === 0040000 or ($pathType === 0100000 && intval($pathStat['nlink']) === 1)) $PathIsPlain = TRUE; }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($pathStat, $pathType, $path);
  return $PathIsPlain; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to hand a path to an owner & group without following a link.
// / lchown & lchgrp change the name itself, so a link put where the path was is never followed.
// / Returns TRUE when both the owner & the group were changed.
function changeOwnerWithoutFollowing($path, $ownerUid, $ownerGid) {
  // / Set variables.
  $OwnerChanged = FALSE;
  if (@lchown($path, $ownerUid) && @lchgrp($path, $ownerGid)) $OwnerChanged = TRUE;
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($path, $ownerUid, $ownerGid);
  return $OwnerChanged; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to hand a newly created path to the owner this installation belongs to.
// / Does nothing at all unless we are root, because nobody else may give a file away.
// / A link or a file with a second hard link is left as it is, because root would change what it leads to.
function applyOwnership($path, $isDirectory) {
  // / Set variables.
  global $RunningAsRoot, $PreserveOwnership, $OwnershipUid, $OwnershipGid, $FilePermissions, $DirectoryPermissions;
  $OwnershipApplied = FALSE;
  if ($RunningAsRoot && $PreserveOwnership && $OwnershipUid >= 0 && file_exists($path) && pathIsPlainForRoot($path)) {
    changeOwnerWithoutFollowing($path, $OwnershipUid, $OwnershipGid);
    if ($isDirectory) @chmod($path, $DirectoryPermissions);
    else @chmod($path, $FilePermissions);
    $OwnershipApplied = TRUE; }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($path, $isDirectory);
  return $OwnershipApplied; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to record who owns a file & what its mode is, before the file is replaced.
// / An update puts both back, so a file keeps the owner & the mode it had.
function captureFileIdentity($path) {
  // / Set variables.
  $FileIdentity = array('exists' => FALSE, 'uid' => -1, 'gid' => -1, 'mode' => -1);
  $pathStat = array();
  if (file_exists($path)) {
    $pathStat = @stat($path);
    if (is_array($pathStat)) $FileIdentity = array('exists' => TRUE, 'uid' => intval($pathStat['uid']), 'gid' => intval($pathStat['gid']), 'mode' => intval($pathStat['mode']) & 0777); }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($pathStat, $path);
  return $FileIdentity; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to put a recorded owner & mode back onto a replaced file.
// / The mode is restored whoever we are, because the owner of a file may always set it.
// / The owner is only restored when we are root, because nobody else may give a file away.
// / A link or a file with a second hard link is left as it is, because the change would reach what it leads to.
function restoreFileIdentity($path, $fileIdentity) {
  // / Set variables.
  global $RunningAsRoot, $PreserveOwnership;
  $IdentityRestored = FALSE;
  if ($fileIdentity['exists'] && file_exists($path) && pathIsPlainForRoot($path)) {
    if ($fileIdentity['mode'] >= 0) @chmod($path, $fileIdentity['mode']);
    if ($RunningAsRoot && $PreserveOwnership && $fileIdentity['uid'] >= 0) changeOwnerWithoutFollowing($path, $fileIdentity['uid'], $fileIdentity['gid']);
    $IdentityRestored = TRUE; }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($path, $fileIdentity);
  return $IdentityRestored; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to create a folder, & every missing folder above it, as the installation's own.
// / Each level is created by itself, so each one is handed to the owner as it appears.
// / Returns TRUE when the folder exists afterwards.
function makeOwnedDirectory($directory) {
  // / Set variables.
  $DirectoryExists = is_dir($directory);
  $parentDirectory = dirname($directory);
  if (!$DirectoryExists && $directory !== '' && $parentDirectory !== $directory) {
    if (!is_dir($parentDirectory)) makeOwnedDirectory($parentDirectory);
    if (@mkdir($directory, 0755)) applyOwnership($directory, TRUE);
    $DirectoryExists = is_dir($directory); }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($parentDirectory, $directory);
  return $DirectoryExists; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to create required directories when they do not already exist.
function createDirs($RequiredDirs) {
  // / Set variables.
  global $RP, $SEP;
  $RequiredDirsExist = TRUE;
  $reqdDir = '';
  // / Iterate through each required directory.
  foreach ($RequiredDirs as $reqdDir) {
    // / Detect if the directory already exists & create it if required.
    if (!file_exists($reqdDir)) makeOwnedDirectory($reqdDir);
    // / Copy an index.html file into the new directory as document root protection.
    if (!file_exists($reqdDir.$SEP.'index.html')) if (file_exists($RP.$SEP.'index.html')) {
      @copy($RP.$SEP.'index.html', $reqdDir.$SEP.'index.html');
      applyOwnership($reqdDir.$SEP.'index.html', FALSE); }
    if (!is_dir($reqdDir)) $RequiredDirsExist = FALSE; }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($reqdDir, $RequiredDirs);
  return array($RequiredDirsExist); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to start a new report file once the current one reaches the maximum size.
// / The full file is renamed with the date & a number that no earlier file holds.
function rotateLog($pendingBytes) {
  // / Set variables.
  global $ReportFile, $MaxLogSize, $Date, $LogBytesWritten;
  $LogRotated = FALSE;
  $rotationIndex = 0;
  $rotatedFile = '';
  $currentSize = 0;
  // / Measure the existing report file only once per session, then track our own writes.
  if ($LogBytesWritten === 0) if (file_exists($ReportFile)) $currentSize = intval(@filesize($ReportFile));
  if ($currentSize > 0) $LogBytesWritten = $currentSize;
  // / Rename the current report file out of the way once it would exceed the maximum size.
  if (($LogBytesWritten + $pendingBytes) > $MaxLogSize) if (file_exists($ReportFile)) {
    $rotatedFile = $ReportFile.'_'.$Date.'_'.$rotationIndex.'.old';
    while (file_exists($rotatedFile)) {
      $rotationIndex++;
      redeclare($rotatedFile, $ReportFile.'_'.$Date.'_'.$rotationIndex.'.old'); }
    $LogRotated = @rename($ReportFile, $rotatedFile);
    if ($LogRotated) {
      applyOwnership($rotatedFile, FALSE);
      $LogBytesWritten = 0; } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($rotationIndex, $rotatedFile, $currentSize, $pendingBytes);
  return $LogRotated; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to add an entry to the logs.
// / The level must be one of log, warning or error.
// / A log entry records normal activity & carries no number.
// / A warning tells the administrator something happened that did not stop the work.
// / An error records a failure that altered behaviour & always carries a documented number.
// / The report folder of the installation is created when the first entry is written to it, & not before.
// / Returns whether the entry was written & the entry as it is written, in that order.
function addLogEntry($entry, $level, $errorNumber) {
  // / Set variables.
  global $ReportFile, $ReportDir, $RequiredDirs, $EOL, $LogAvailable, $LogBytesWritten;
  $LogCreated = FALSE;
  $LogLine = '';
  $preText = '';
  $entryTime = date("F j, Y, g:i a");
  $writeResult = FALSE;
  $reportExisted = FALSE;
  $reportDirExists = TRUE;
  if (!is_numeric($errorNumber)) $errorNumber = 0;
  if ($level === 'error') $preText = 'ERROR!!! ScanCore-'.intval($errorNumber).' on '.$entryTime.', ';
  if ($level === 'warning') $preText = 'WARNING!!! ScanCore on '.$entryTime.', ';
  if ($level !== 'error' && $level !== 'warning') $preText = $entryTime.', ';
  $LogLine = $preText.$entry;
  // / Write the entry only while the report file remains usable.
  if ($LogAvailable) {
    rotateLog(strlen($LogLine.$EOL));
    $reportExisted = file_exists($ReportFile);
    if (!$reportExisted && dirname($ReportFile) === $ReportDir) list($reportDirExists) = createDirs($RequiredDirs);
    if ($reportDirExists) $writeResult = @file_put_contents($ReportFile, $LogLine.$EOL, FILE_APPEND);
    // / A report that root creates is handed to the owner of the installation.
    if (!$reportExisted && $writeResult !== FALSE) applyOwnership($ReportFile, FALSE);
    if ($writeResult === FALSE) {
      // / A scanner that cannot write a report must still scan, so the report is switched off & the console is told why.
      $LogAvailable = FALSE;
      echo 'ERROR!!! ScanCore-910 on '.$entryTime.', Cannot write to the report file:  '.$ReportFile.$EOL; }
    else {
      $LogBytesWritten = $LogBytesWritten + intval($writeResult);
      $LogCreated = TRUE; } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($preText, $entryTime, $writeResult, $reportExisted, $reportDirExists, $entry, $level, $errorNumber);
  return array($LogCreated, $LogLine); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to handle important messages to the console & log file.
// / The level must be one of log, warning or error.
// / A warning or an error that the report file could not take is printed with its label instead, so it is never lost.
function processOutput($txt, $level, $errorNumber, $requiredLog, $requiredConsole, $fatal) {
  // / Set variables.
  global $EOL, $Debug, $Verbose;
  $OutputProcessed = FALSE;
  $entryLevel = 'log';
  $logLine = '';
  $logWasWanted = FALSE;
  // / Verify that all inputs are of the correct type.
  if (!is_string($txt)) $txt = '';
  if (!is_numeric($errorNumber)) $errorNumber = 0;
  if ($level === 'error' or $level === 'warning') $entryLevel = $level;
  // / An error without a documented number is really a warning, so it gets recorded as one.
  if ($entryLevel === 'error' && intval($errorNumber) === 0) $entryLevel = 'warning';
  // / Log the provided text if the $Debug variable (-d switch) is set.
  if ($Debug or $requiredLog) {
    $logWasWanted = TRUE;
    list ($OutputProcessed, $logLine) = addLogEntry($txt, $entryLevel, $errorNumber); }
  // / Print the labelled entry where the report file could not take a warning or an error.
  if ($logWasWanted && !$OutputProcessed && $entryLevel !== 'log') echo $logLine.$EOL;
  else {
    // / Output the summary text to the terminal if the $Verbose (-v switch) variable is set.
    if ($Verbose or $requiredConsole) echo $txt.$EOL; }
  // / Stop execution as needed, reporting a failing status to whatever invoked us.
  if ($fatal) exit(1);
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($txt, $entryLevel, $logLine, $logWasWanted, $level, $errorNumber, $requiredLog, $requiredConsole, $fatal);
  return array($OutputProcessed); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to render a subscription list as readable text.
// / Both the updaters & the version screen need this, so it lives in one place.
function buildSubscriptionText($subscriptions) {
  // / Set variables.
  $SubscriptionText = '';
  if (is_array($subscriptions)) foreach ($subscriptions as $subscriptionName) $SubscriptionText = $SubscriptionText.' '.$subscriptionName.',';
  $SubscriptionText = trim(trim($SubscriptionText, ','), ' ');
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($subscriptionName, $subscriptions);
  return $SubscriptionText; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to reliably build help & version information.
// / Every path is absolute so this works no matter which directory invoked ScanCore.
// / The switch table is the one place in this file where a switch is described.
// / Returns whether the information was built, the definition subscriptions, the version text & the help text, in that order.
function buildHelpInformation() {
  // / Set variables.
  global $DefinitionsUpdateSubscriptions, $SubText, $VersionText, $HelpText, $ApplicationUpdateURL, $DefinitionUpdateURL, $DefsFile, $ConfigFile, $Version, $EOL;
  $InformationBuilt = FALSE;
  $SubText = $VersionText = $HelpText = '';
  $coreFilePath = realpath(__FILE__);
  $configFilePath = realpath($ConfigFile);
  $defsFilePath = realpath($DefsFile);
  $titleLine = $switchForm = '';
  $versionLines = $helpLines = $switchTable = $switchRow = array();
  $SubText = buildSubscriptionText($DefinitionsUpdateSubscriptions);
  // / Report an unresolved path plainly rather than printing the word false to the console.
  if ($configFilePath === FALSE) $configFilePath = 'Not found:  '.$ConfigFile;
  if ($defsFilePath === FALSE) $defsFilePath = 'Not found:  '.$DefsFile;
  // / The core file is the running script, so it is always present by definition.
  if ($coreFilePath !== FALSE) {
    $InformationBuilt = TRUE;
    // / A caller reads the version of an installation from the opening of this line.
    $titleLine = 'ScanCore '.$Version.' by Justin Grimes (@zelon88), licensed under GPLv3.';
    $versionLines = array($titleLine,
      'The original source code for this application can be found at:  https://github.com/zelon88/ScanCore',
      'This installation is located at:  '.$coreFilePath,
      'This installation is using a definitions file located at:  '.$defsFilePath,
      'This installation is using a configuration file located at:  '.$configFilePath,
      'This installation downloads Application updates from:  '.$ApplicationUpdateURL,
      'This installation downloads Definition updates from:  '.$DefinitionUpdateURL,
      'Configuration file was last updated on:  '.date("F d Y H:i:s.", intval(@filectime($ConfigFile))),
      'Application update was last installed on:  '.date("F d Y H:i:s.", intval(@filectime($coreFilePath))),
      'Definition update was last installed on:  '.date("F d Y H:i:s.", intval(@filectime($DefsFile))),
      'This installation has the following Definition Subscriptions:  '.$SubText);
    // / Each row is what a switch does, followed by every form the switch may be written in.
    $switchTable = array(
      array('Show version information', '-version', '-ver'),
      array('Show help information', '-help', '-h'),
      array('Force recursion', '-recursion', '-r'),
      array('Force no recursion', '-norecursion', '-nr'),
      array('Follow symbolic links while scanning', '-followsymlinks', '-fs'),
      array('Do not follow symbolic links', '-nofollowsymlinks', '-nfs'),
      array('Specify memory limit (in bytes)', '-memorylimit ####', '-m ####'),
      array('Specify chunk size (in bytes)', '-chunksize ####', '-c ####'),
      array('Force maximum scan depth (in folders)', '-maxdepth ###', '-md ###'),
      array('Enable "debug" mode (more logging)', '-debug', '-d'),
      array('Enable "verbose" mode (more console)', '-verbose', '-v'),
      array('Force a specific report file', '-reportfile /path/to/file', '-rf /path/to/file', '-logfile /path/to/file', '-lf /path/to/file'),
      array('Force a specific configuration file', '-configfile /path/to/file', '-cf /path/to/file'),
      array('Force a specific definitions file', '-defsfile /path/to/file', '-df /path/to/file'),
      array('Force maximum log size (in bytes)', '-maxlogsize ###', '-ml ###'),
      array('Report low confidence matches', '-suspicious', '-sus'),
      array('Report confirmed matches only', '-nosuspicious', '-nsus'),
      array('Enable content classification', '-classify', '-cy'),
      array('Disable content classification', '-noclassify', '-ncy'),
      array('Choose which classifiers run', '-classifiers scad,pdf', '-cl scad,pdf'),
      array('Inspect inside containers', '-inspectarchives', '-ia'),
      array('Do not open containers', '-noinspectarchives', '-nia'),
      array('Container nesting depth limit', '-maxarchivedepth ###', '-mad ###'),
      array('Inspect the structure of images', '-inspectimages', '-ii'),
      array('Do not inspect images', '-noinspectimages', '-nii'),
      array('Largest image accepted (in pixels)', '-maximagepixels ###', '-mip ###'),
      array('Largest image text chunk (in bytes)', '-maximagechunksize ###', '-mics ###'),
      array('Container entry count budget', '-maxarchiveentries ###', '-mae ###'),
      array('Container entry size budget (bytes)', '-maxarchiveentrysize ###', '-maes ###'),
      array('Container total size budget (bytes)', '-maxarchivetotalsize ###', '-mats ###'),
      array('Container expansion ratio limit', '-maxarchiveratio ###', '-mar ###'),
      array('Language signatures needed to decide', '-minlanguagesignatures ###', '-mls ###'),
      array('Shortest usable data signature', '-mindatasignature ###', '-mds ###'),
      array('Own created files as this user', '-owner www-data', '-ow www-data'),
      array('Own created files as this group', '-group www-data', '-gr www-data'),
      array('Leave created files owned by root', '-nopreserveownership', '-npo'),
      array('Do not use a packed hash index', '-nohashindex', '-nhi'),
      array('Rebuild the packed hash index', '-rebuildindex', '-ri'),
      array('Hashes needed before indexing', '-hashindexthreshold ###', '-hit ###'),
      array('Seconds to wait for an update host', '-connectiontimeout ###', '-ct ###'),
      array('Choose the update method', '-updatemethod git', '-um raw'),
      array('Perform definition update', '-updatedefinitions', '-ud'),
      array('Perform application update', '-updateapplication', '-ua'));
    $helpLines = array($titleLine, '',
      'Quick Start Examples:', '',
      '  php /path/to/ScanCore.php /path/to/scan -r',
      '  C:\Path-To-PHP-Binary.exe C:\Path-To-ScanCore.php C:\Path-To-Scan\ -m [integer] -c [integer] -v -d', '',
      'Required Arguments Include:', '',
      sprintf('  %-38s %s', 'File or folder to scan:', '/path/to/scan'), '',
      'Optional Arguments Include:');
    foreach ($switchTable as $switchRow) {
      $helpLines[] = '';
      $helpLines[] = sprintf('  %-38s %s', $switchRow[0].':', $switchRow[1]);
      foreach (array_slice($switchRow, 2) as $switchForm) $helpLines[] = sprintf('  %-38s %s', '', $switchForm); }
    $HelpText = implode($EOL, $helpLines).$EOL;
    $VersionText = implode($EOL, $versionLines); }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($coreFilePath, $configFilePath, $defsFilePath, $titleLine, $switchForm, $versionLines, $helpLines, $switchTable, $switchRow);
  return array($InformationBuilt, $SubText, $VersionText, $HelpText); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to read the value that follows a command line switch.
// / Returns whether a value was found & the sanitized value, in that order.
// / A switch written last has no value after it, & the configured value is kept.
function readArgumentValue($argumentList, $switchKey, $switchName) {
  // / Set variables.
  $ValueFound = FALSE;
  $ArgumentValue = '';
  if (isset($argumentList[$switchKey + 1])) {
    $ArgumentValue = sanitize($argumentList[$switchKey + 1]);
    if ($ArgumentValue !== '') $ValueFound = TRUE; }
  // / Warn rather than error, because the configured default remains perfectly usable.
  if (!$ValueFound) processOutput('The '.$switchName.' argument was supplied without a value, using the configured value instead.', 'warning', 0, TRUE, FALSE, FALSE);
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($argumentList, $switchKey, $switchName);
  return array($ValueFound, $ArgumentValue); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to apply the value that follows a command line switch to the setting it overrides.
// / The setting is taken by reference & keeps the value it had when the switch carries none.
// / Returns TRUE when the setting was given a new value.
function applyArgumentValue(&$setting, $argumentList, $switchKey, $switchName) {
  // / Set variables.
  $ValueApplied = FALSE;
  $argumentValue = '';
  list($ValueApplied, $argumentValue) = readArgumentValue($argumentList, $switchKey, $switchName);
  if ($ValueApplied) $setting = $argumentValue;
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($argumentValue, $argumentList, $switchKey, $switchName);
  return $ValueApplied; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to validate a numeric setting & clamp it into a workable range.
// / A chunk size of zero would make the file reader loop without ever advancing.
function validateNumericSetting($candidate, $defaultValue, $minimumValue, $settingName) {
  // / Set variables.
  $ValidatedSetting = intval($defaultValue);
  $candidateIsUsable = FALSE;
  if (is_numeric($candidate)) if (intval($candidate) >= $minimumValue) $candidateIsUsable = TRUE;
  if ($candidateIsUsable) $ValidatedSetting = intval($candidate);
  else processOutput('The '.$settingName.' value is missing or below the minimum of '.$minimumValue.', using '.intval($defaultValue).' instead.', 'warning', 0, TRUE, FALSE, FALSE);
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($candidateIsUsable, $candidate, $defaultValue, $minimumValue, $settingName);
  return $ValidatedSetting; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to lower one budget to a ceiling & to say that it was lowered.
// / The budget is taken by reference.
// / Returns TRUE when the budget was lowered.
function lowerBudgetToCeiling(&$budget, $budgetCeiling, $budgetName) {
  // / Set variables.
  $BudgetLowered = FALSE;
  if ($budget > $budgetCeiling) {
    processOutput('The '.$budgetName.' of '.$budget.' does not fit the MemoryLimit, using '.$budgetCeiling.' instead.', 'warning', 0, TRUE, FALSE, FALSE);
    $budget = $budgetCeiling;
    $BudgetLowered = TRUE; }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($budgetCeiling, $budgetName);
  return $BudgetLowered; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to hold the read budgets to what the memory limit can carry.
// / A chunk is held twice while it is searched, beside what the interpreter itself needs, so a chunk is a quarter of the limit at most.
// / A container member is held twice at every level of nesting, so the entry budget is divided by the depth as well.
// / An inflated png chunk is held beside the chunk it came from, so the chunk budget is a sixteenth of the limit at most.
// / A file that is larger than a lowered budget is reported as incomplete, where a larger budget would have stopped the interpreter.
// / Returns the number of budgets that were lowered.
function fitBudgetsToMemoryLimit() {
  // / Set variables.
  global $MemoryLimit, $ChunkSize, $MaxArchiveEntrySize, $MaxArchiveDepth, $MaxImageChunkSize;
  $BudgetsLowered = 0;
  if (lowerBudgetToCeiling($ChunkSize, max(4096, intdiv($MemoryLimit, 4)), 'ChunkSize')) $BudgetsLowered++;
  if (lowerBudgetToCeiling($MaxArchiveEntrySize, max(1024, intdiv($MemoryLimit, 4 * ($MaxArchiveDepth + 1))), 'MaxArchiveEntrySize')) $BudgetsLowered++;
  if (lowerBudgetToCeiling($MaxImageChunkSize, max(1024, intdiv($MemoryLimit, 16)), 'MaxImageChunkSize')) $BudgetsLowered++;
  return $BudgetsLowered; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to parse supplied command-line arguments.
// / The first argument is the file or folder to scan, & every switch is written after it.
// / The configuration file, the definitions file & the report file are settled by loadConfig(), before anything is logged.
// / Returns the check, whether a scan was asked for, the path to scan & the settings the main logic acts on, in that order.
function parseArgs($argumentList) {
  // / Set variables.
  global $ArgsParsed, $ReportFile, $ReportDir, $MaxLogSize, $Debug, $Verbose, $ChunkSize, $MemoryLimit, $DefaultMemoryLimit, $DefaultChunkSize, $DefaultMaxLogSize, $PerformDefUpdate, $PerformAppUpdate, $VersionText, $HelpText, $ConfigFilePath, $PerformScan, $ScanLoc, $InstallDir, $MaxScanDepth, $FollowSymlinks;
  global $ClassifyContent, $EnabledClassifiers, $MinimumLanguageSignatures, $MinimumDataSignatureLength, $InspectArchivedContent;
  global $MaxArchiveEntries, $MaxArchiveEntrySize, $MaxArchiveTotalSize, $MaxArchiveCompressionRatio, $ConnectionTimeout, $UpdateMethod;
  global $ReportSuspicious, $PreserveOwnership, $OwnerUser, $OwnerGroup, $UseHashIndex, $HashIndexThreshold, $RebuildHashIndex;
  global $InspectImages, $MaxImagePixels, $MaxImageChunkSize, $MaxArchiveDepth;
  $ArgsParsed = $PerformScan = $Recursion = FALSE;
  $RebuildHashIndex = FALSE;
  $PerformDefUpdate = $PerformAppUpdate = $showVersion = $showHelp = FALSE;
  $PathToScan = '';
  $valueFound = $informationBuilt = FALSE;
  $argumentValue = $currentSwitch = $subscriptionText = '';
  $scanPathSupplied = FALSE;
  // / Iterate the arguments once, recording every switch that was supplied.
  foreach ($argumentList as $argKey => $argValue) {
    redeclare($currentSwitch, normalizeSwitch($argValue));
    // / Read the switches that ask for information or for an update.
    if ($currentSwitch === '-version' or $currentSwitch === '-ver') $showVersion = TRUE;
    if ($currentSwitch === '-h' or $currentSwitch === '-help') $showHelp = TRUE;
    if ($currentSwitch === '-updatedefinitions' or $currentSwitch === '-ud') $PerformDefUpdate = TRUE;
    if ($currentSwitch === '-updateapplication' or $currentSwitch === '-ua') $PerformAppUpdate = TRUE;
    // / Read the switches that are complete by themselves.
    if ($currentSwitch === '-debug' or $currentSwitch === '-d') $Debug = TRUE;
    if ($currentSwitch === '-verbose' or $currentSwitch === '-v') $Verbose = TRUE;
    if ($currentSwitch === '-recursion' or $currentSwitch === '-r') $Recursion = TRUE;
    if ($currentSwitch === '-norecursion' or $currentSwitch === '-nr') $Recursion = FALSE;
    if ($currentSwitch === '-followsymlinks' or $currentSwitch === '-fs') $FollowSymlinks = TRUE;
    if ($currentSwitch === '-nofollowsymlinks' or $currentSwitch === '-nfs') $FollowSymlinks = FALSE;
    if ($currentSwitch === '-suspicious' or $currentSwitch === '-sus') $ReportSuspicious = TRUE;
    if ($currentSwitch === '-nosuspicious' or $currentSwitch === '-nsus') $ReportSuspicious = FALSE;
    if ($currentSwitch === '-classify' or $currentSwitch === '-cy') $ClassifyContent = TRUE;
    if ($currentSwitch === '-noclassify' or $currentSwitch === '-ncy') $ClassifyContent = FALSE;
    if ($currentSwitch === '-inspectarchives' or $currentSwitch === '-ia') $InspectArchivedContent = TRUE;
    if ($currentSwitch === '-noinspectarchives' or $currentSwitch === '-nia') $InspectArchivedContent = FALSE;
    if ($currentSwitch === '-inspectimages' or $currentSwitch === '-ii') $InspectImages = TRUE;
    if ($currentSwitch === '-noinspectimages' or $currentSwitch === '-nii') $InspectImages = FALSE;
    if ($currentSwitch === '-nopreserveownership' or $currentSwitch === '-npo') $PreserveOwnership = FALSE;
    if ($currentSwitch === '-nohashindex' or $currentSwitch === '-nhi') $UseHashIndex = FALSE;
    if ($currentSwitch === '-rebuildindex' or $currentSwitch === '-ri') $RebuildHashIndex = TRUE;
    // / Read the switches whose value is the setting, exactly as it was written.
    if ($currentSwitch === '-memorylimit' or $currentSwitch === '-m') applyArgumentValue($MemoryLimit, $argumentList, $argKey, $currentSwitch);
    if ($currentSwitch === '-chunksize' or $currentSwitch === '-c') applyArgumentValue($ChunkSize, $argumentList, $argKey, $currentSwitch);
    if ($currentSwitch === '-maxdepth' or $currentSwitch === '-md') applyArgumentValue($MaxScanDepth, $argumentList, $argKey, $currentSwitch);
    if ($currentSwitch === '-maxlogsize' or $currentSwitch === '-ml') applyArgumentValue($MaxLogSize, $argumentList, $argKey, $currentSwitch);
    if ($currentSwitch === '-maxarchivedepth' or $currentSwitch === '-mad') applyArgumentValue($MaxArchiveDepth, $argumentList, $argKey, $currentSwitch);
    if ($currentSwitch === '-maximagepixels' or $currentSwitch === '-mip') applyArgumentValue($MaxImagePixels, $argumentList, $argKey, $currentSwitch);
    if ($currentSwitch === '-maximagechunksize' or $currentSwitch === '-mics') applyArgumentValue($MaxImageChunkSize, $argumentList, $argKey, $currentSwitch);
    if ($currentSwitch === '-maxarchiveentries' or $currentSwitch === '-mae') applyArgumentValue($MaxArchiveEntries, $argumentList, $argKey, $currentSwitch);
    if ($currentSwitch === '-maxarchiveentrysize' or $currentSwitch === '-maes') applyArgumentValue($MaxArchiveEntrySize, $argumentList, $argKey, $currentSwitch);
    if ($currentSwitch === '-maxarchivetotalsize' or $currentSwitch === '-mats') applyArgumentValue($MaxArchiveTotalSize, $argumentList, $argKey, $currentSwitch);
    if ($currentSwitch === '-maxarchiveratio' or $currentSwitch === '-mar') applyArgumentValue($MaxArchiveCompressionRatio, $argumentList, $argKey, $currentSwitch);
    if ($currentSwitch === '-minlanguagesignatures' or $currentSwitch === '-mls') applyArgumentValue($MinimumLanguageSignatures, $argumentList, $argKey, $currentSwitch);
    if ($currentSwitch === '-mindatasignature' or $currentSwitch === '-mds') applyArgumentValue($MinimumDataSignatureLength, $argumentList, $argKey, $currentSwitch);
    if ($currentSwitch === '-hashindexthreshold' or $currentSwitch === '-hit') applyArgumentValue($HashIndexThreshold, $argumentList, $argKey, $currentSwitch);
    if ($currentSwitch === '-connectiontimeout' or $currentSwitch === '-ct') applyArgumentValue($ConnectionTimeout, $argumentList, $argKey, $currentSwitch);
    if ($currentSwitch === '-owner' or $currentSwitch === '-ow') applyArgumentValue($OwnerUser, $argumentList, $argKey, $currentSwitch);
    if ($currentSwitch === '-group' or $currentSwitch === '-gr') applyArgumentValue($OwnerGroup, $argumentList, $argKey, $currentSwitch);
    // / Read the switches whose value is reshaped before it becomes the setting.
    if ($currentSwitch === '-classifiers' or $currentSwitch === '-cl') {
      list($valueFound, $argumentValue) = readArgumentValue($argumentList, $argKey, $currentSwitch);
      // / An empty list is a real answer here, so a lone comma switches everything off.
      if ($valueFound) $EnabledClassifiers = array_filter(array_map('trim', explode(',', $argumentValue)), 'strlen'); }
    if ($currentSwitch === '-updatemethod' or $currentSwitch === '-um') {
      list($valueFound, $argumentValue) = readArgumentValue($argumentList, $argKey, $currentSwitch);
      if ($valueFound) $UpdateMethod = strtolower($argumentValue); }
    // / The report file was settled by loadConfig(), & it is read again here so that a switch with no value is reported.
    if ($currentSwitch === '-reportfile' or $currentSwitch === '-rf' or $currentSwitch === '-logfile' or $currentSwitch === '-lf') {
      list($valueFound, $argumentValue) = readArgumentValue($argumentList, $argKey, $currentSwitch);
      if ($valueFound) $ReportFile = resolvePath($argumentValue, $ReportDir); } }
  // / Validate every numeric setting & clamp it into a range the scanner can actually use.
  $MemoryLimit = validateNumericSetting($MemoryLimit, $DefaultMemoryLimit, 65536, 'MemoryLimit');
  $ChunkSize = validateNumericSetting($ChunkSize, $DefaultChunkSize, 4096, 'ChunkSize');
  $MaxLogSize = validateNumericSetting($MaxLogSize, $DefaultMaxLogSize, 4096, 'MaxLogSize');
  $MaxScanDepth = validateNumericSetting($MaxScanDepth, 64, 1, 'MaxScanDepth');
  $HashIndexThreshold = validateNumericSetting($HashIndexThreshold, 10000, 1, 'HashIndexThreshold');
  // / Validate every override the same way, so a bad argument falls back rather than breaks.
  $MinimumLanguageSignatures = validateNumericSetting($MinimumLanguageSignatures, 2, 1, 'MinimumLanguageSignatures');
  $MinimumDataSignatureLength = validateNumericSetting($MinimumDataSignatureLength, 4, 1, 'MinimumDataSignatureLength');
  $MaxArchiveEntries = validateNumericSetting($MaxArchiveEntries, 4096, 1, 'MaxArchiveEntries');
  $MaxArchiveDepth = validateNumericSetting($MaxArchiveDepth, 5, 1, 'MaxArchiveDepth');
  $MaxImagePixels = validateNumericSetting($MaxImagePixels, 16384*16384, 1, 'MaxImagePixels');
  $MaxImageChunkSize = validateNumericSetting($MaxImageChunkSize, 1024*1024*8, 1024, 'MaxImageChunkSize');
  $MaxArchiveEntrySize = validateNumericSetting($MaxArchiveEntrySize, 1024*1024*8, 1024, 'MaxArchiveEntrySize');
  $MaxArchiveTotalSize = validateNumericSetting($MaxArchiveTotalSize, 1024*1024*64, 1024, 'MaxArchiveTotalSize');
  $MaxArchiveCompressionRatio = validateNumericSetting($MaxArchiveCompressionRatio, 200, 2, 'MaxArchiveCompressionRatio');
  $ConnectionTimeout = validateNumericSetting($ConnectionTimeout, 15, 1, 'ConnectionTimeout');
  // / Fall back to the git update method when an override names something else.
  if ($UpdateMethod !== 'git' && $UpdateMethod !== 'raw') {
    processOutput('The UpdateMethod override is not git or raw, using git instead.', 'warning', 0, TRUE, FALSE, FALSE);
    $UpdateMethod = 'git'; }
  // / Hold every read budget to what the memory limit can carry.
  fitBudgetsToMemoryLimit();
  // / Detect if version or help information is being requested.
  if ($showVersion or $showHelp) {
    list ($informationBuilt, $subscriptionText, $VersionText, $HelpText) = buildHelpInformation();
    if ($informationBuilt) processOutput('Built version information.', 'log', 0, FALSE, FALSE, FALSE);
    else processOutput('Cannot build version information!', 'warning', 0, TRUE, TRUE, FALSE);
    // / The version & the help are answers for the console, so neither is written to the report.
    if ($showVersion) processOutput($VersionText, 'log', 0, FALSE, TRUE, FALSE);
    if ($showHelp) processOutput($HelpText, 'log', 0, FALSE, TRUE, FALSE);
    $ArgsParsed = TRUE; }
  // / Detect if an update is being requested.
  if ($PerformDefUpdate or $PerformAppUpdate) {
    processOutput('Starting ScanCore updater!', 'log', 0, TRUE, TRUE, FALSE);
    $ArgsParsed = TRUE; }
  // / Work out what should be scanned when no other operation was requested.
  if (!$PerformDefUpdate && !$PerformAppUpdate && !$showVersion && !$showHelp) {
    if (isset($argumentList[1])) {
      $PathToScan = sanitize($argumentList[1]);
      $scanPathSupplied = TRUE; }
    // / Fall back to the configured scan location before reporting that nothing was supplied.
    if (!$scanPathSupplied && is_string($ScanLoc) && $ScanLoc !== '') {
      $PathToScan = resolvePath($ScanLoc, $InstallDir);
      $scanPathSupplied = TRUE;
      processOutput('No scan path was supplied, falling back to the configured ScanLoc.', 'warning', 0, TRUE, FALSE, FALSE); }
    if (!$scanPathSupplied) processOutput('There were no arguments set!', 'error', 100, TRUE, TRUE, FALSE);
    else {
      // / Detect if a valid path to scan was supplied.
      if (!file_exists($PathToScan)) processOutput('The specified file was not found! The first argument must be a valid file or directory path!', 'error', 300, TRUE, TRUE, FALSE);
      else {
        if (!is_readable($PathToScan)) processOutput('The specified path cannot be read:  '.$PathToScan, 'error', 310, TRUE, TRUE, FALSE);
        else {
          // / Output status information.
          processOutput('Starting ScanCore!', 'log', 0, TRUE, TRUE, FALSE);
          processOutput('Loaded configuration file:  '.$ConfigFilePath, 'log', 0, TRUE, FALSE, FALSE);
          processOutput('The ChunkSize is:  '.$ChunkSize, 'log', 0, FALSE, FALSE, FALSE);
          processOutput('The MemoryLimit is:  '.$MemoryLimit, 'log', 0, FALSE, FALSE, FALSE);
          $ArgsParsed = $PerformScan = TRUE; } } } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($argKey, $argValue, $currentSwitch, $showVersion, $showHelp, $valueFound, $argumentValue, $scanPathSupplied, $informationBuilt, $subscriptionText, $argumentList);
  return array($ArgsParsed, $PerformScan, $PathToScan, $MemoryLimit, $ChunkSize, $Debug, $Verbose, $Recursion, $ReportFile, $MaxLogSize, $PerformDefUpdate, $PerformAppUpdate); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to confirm that a path is safe to delete before anything gets deleted.
// / The cleaner is only ever aimed at the temporary update directories.
// / A blank or misconfigured repository name must never point it somewhere else.
function cleanTargetIsSafe($location) {
  // / Set variables.
  global $InstallDir, $SEP;
  $TargetIsSafe = FALSE;
  $resolvedLocation = '';
  $resolvedInstall = realpath($InstallDir);
  if (is_string($location) && $location !== '') $resolvedLocation = realpath($location);
  // / A path that does not exist is safe, because there is nothing there to remove.
  if ($resolvedLocation === FALSE) $TargetIsSafe = TRUE;
  if (is_string($resolvedLocation) && $resolvedLocation !== '' && $resolvedInstall !== FALSE) {
    // / The target must sit underneath the installation directory & must not be it.
    if (str_starts_with($resolvedLocation.$SEP, $resolvedInstall.$SEP)) if ($resolvedLocation !== $resolvedInstall) $TargetIsSafe = TRUE; }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($resolvedLocation, $resolvedInstall, $location);
  return $TargetIsSafe; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to remove files & folders.
// / A symbolic link is removed as a link so the cleaner never walks outside its target.
function clean($Location) {
  // / Set variables.
  global $SEP;
  $LocationCleaned = FALSE;
  $entryName = '';
  $entryList = array();
  $scanResult = FALSE;
  // / Refuse to touch anything outside the installation directory.
  if (!cleanTargetIsSafe($Location)) processOutput('Refused to clean a path outside the installation directory:  '.$Location, 'warning', 0, TRUE, FALSE, FALSE);
  else {
    // / Detect if the location is a folder that is not merely a link to one.
    if (is_dir($Location) && !is_link($Location)) {
      $scanResult = @scandir($Location);
      // / Scan the folder for contents, tolerating a folder we are not allowed to read.
      if (is_array($scanResult)) {
        $entryList = array_diff($scanResult, array('..', '.'));
        // / Iterate through the contents of the folder.
        foreach ($entryList as $entryName) {
          // / If this object is a folder, run this function on it.
          if (is_dir($Location.$SEP.$entryName) && !is_link($Location.$SEP.$entryName)) clean($Location.$SEP.$entryName);
          // / If this object is a file or a link, delete it.
          else @unlink($Location.$SEP.$entryName); } }
      // / Try to delete the folder now that we've deleted the contents.
      if (is_dir($Location)) @rmdir($Location); }
    // / If the location is a file or a link, delete it.
    if (file_exists($Location) && !is_dir($Location)) @unlink($Location);
    if (is_link($Location)) @unlink($Location);
    // / Check if the location was deleted.
    if (!is_dir($Location) && !file_exists($Location)) $LocationCleaned = TRUE; }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($entryName, $entryList, $scanResult);
  return array($LocationCleaned); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to verify internet connectivity before attempting to perform update operations.
// / Type must be either 'application' or 'definition'.
// / This function is limited to the two domains defined in the configuration file.
// / A scheme or a trailing path is stripped, because a socket only wants a host name.
function connectionSuccess($type) {
  // / Set variables.
  global $ApplicationUpdateDomain, $DefinitionUpdateDomain, $ConnectionTimeout;
  $ConnectionResult = FALSE;
  $connection = FALSE;
  $urlToCheck = '';
  $errorNumber = 0;
  $errorText = '';
  if ($type === 'application') $urlToCheck = $ApplicationUpdateDomain;
  if ($type === 'definition') $urlToCheck = $DefinitionUpdateDomain;
  // / Reduce whatever the administrator wrote down to a bare host name.
  $urlToCheck = preg_replace('#^[a-zA-Z][a-zA-Z0-9+.\-]*://#', '', trim(sanitize($urlToCheck)));
  $urlToCheck = explode('/', $urlToCheck)[0];
  $urlToCheck = explode(':', $urlToCheck)[0];
  if ($urlToCheck !== '') $connection = @fsockopen($urlToCheck, 443, $errorNumber, $errorText, $ConnectionTimeout);
  if ($connection !== FALSE) {
    fclose($connection);
    $ConnectionResult = TRUE; }
  // / The caller raises the numbered error, so an unreachable host is only a warning here.
  if (!$ConnectionResult) processOutput('Cannot reach the configured update host:  '.$urlToCheck, 'warning', 0, TRUE, FALSE, FALSE);
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($connection, $urlToCheck, $errorNumber, $errorText, $type);
  return $ConnectionResult; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to download a single file from a remote repository.
// / The built in downloader is tried first & cURL is used as the fallback.
// / A repository that answers with its own not found page is treated as a failed download.
function fetchRemoteFile($sourceUrl, $destinationFile) {
  // / Set variables.
  global $ConnectionTimeout;
  $FileFetched = FALSE;
  $fetchedData = FALSE;
  $streamContext = NULL;
  $writeResult = FALSE;
  $commandRan = FALSE;
  $commandOutput = '';
  // / Try the built in downloader first, which needs no external binary.
  if (ini_get('allow_url_fopen')) {
    $streamContext = stream_context_create(array('http' => array('timeout' => $ConnectionTimeout, 'follow_location' => 1, 'user_agent' => 'ScanCore')));
    $fetchedData = @file_get_contents($sourceUrl, FALSE, $streamContext); }
  if (is_string($fetchedData)) if (trim($fetchedData) === '' or trim($fetchedData) === '404: Not Found') $fetchedData = FALSE;
  if (is_string($fetchedData)) {
    $writeResult = @file_put_contents($destinationFile, $fetchedData);
    if ($writeResult !== FALSE && $writeResult > 0) $FileFetched = TRUE; }
  // / Fall back to cURL when the built in downloader is unavailable or came back empty.
  if (!$FileFetched) {
    processOutput('Attempting download with cURL against URL:  '.$sourceUrl, 'log', 0, FALSE, FALSE, FALSE);
    list($commandRan, $commandOutput) = runShellCommand('curl -Ls --max-time '.intval($ConnectionTimeout).' '.escapeshellarg($sourceUrl).' --output '.escapeshellarg($destinationFile).' 2>&1');
    if (!$commandRan) processOutput('Cannot run cURL, because this interpreter has no shell.', 'log', 0, FALSE, FALSE, FALSE);
    if (file_exists($destinationFile)) if (filesize($destinationFile) > 0) {
      // / Confirm that cURL did not simply save the repository not found page to disk.
      redeclare($fetchedData, @file_get_contents($destinationFile));
      if (is_string($fetchedData)) if (trim($fetchedData) !== '404: Not Found') $FileFetched = TRUE; } }
  if (!$FileFetched) if (file_exists($destinationFile)) @unlink($destinationFile);
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($fetchedData, $streamContext, $writeResult, $commandRan, $commandOutput, $sourceUrl, $destinationFile);
  return $FileFetched; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to clone an update repository into a named destination directory.
// / The configured branch is the one cloned, & only its newest commit is fetched.
// / Every value reaching the shell is escaped, because they arrive from a text file.
function cloneRepository($repositoryUrl, $branchName, $destinationDir) {
  // / Set variables.
  $RepositoryCloned = FALSE;
  $commandRan = FALSE;
  $commandOutput = '';
  // / Confirm that git is actually installed before relying upon it.
  list($commandRan, $commandOutput) = runShellCommand('git --version 2>&1');
  if (!$commandRan or stripos($commandOutput, 'git version') === FALSE) processOutput('Cannot find a usable git binary.', 'warning', 0, TRUE, FALSE, FALSE);
  else {
    list($commandRan, $commandOutput) = runShellCommand('git clone --depth 1 --branch '.escapeshellarg($branchName).' -- '.escapeshellarg($repositoryUrl).' '.escapeshellarg($destinationDir).' 2>&1');
    if (is_dir($destinationDir)) $RepositoryCloned = TRUE;
    if (!$RepositoryCloned) processOutput('The git clone operation did not produce a directory:  '.collapseToOneLine($commandOutput), 'warning', 0, TRUE, FALSE, FALSE); }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($commandRan, $commandOutput, $repositoryUrl, $branchName, $destinationDir);
  return $RepositoryCloned; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to run a syntax check against a PHP file before it is trusted.
// / A downloaded core that does not parse would take the installation down with it.
// / An interpreter with no shell cannot run the check, which is reported as a check that was not performed & never as one that failed.
// / Returns whether the check was performed, whether the file passed & what the interpreter said where it did not, in that order.
function lintPhpFile($phpFile) {
  // / Set variables.
  $LintPerformed = $LintPassed = FALSE;
  $LintNote = '';
  $commandRan = FALSE;
  $commandOutput = '';
  list($commandRan, $commandOutput) = runShellCommand(escapeshellarg(PHP_BINARY).' -l '.escapeshellarg($phpFile).' 2>&1');
  if (!$commandRan) $LintNote = 'no shell is available to lint with';
  else {
    if ($commandOutput === '') $LintNote = 'the lint command returned nothing';
    else {
      $LintPerformed = TRUE;
      if (stripos($commandOutput, 'No syntax errors detected') !== FALSE) $LintPassed = TRUE;
      else $LintNote = collapseToOneLine($commandOutput); } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($commandRan, $commandOutput, $phpFile);
  return array($LintPerformed, $LintPassed, $LintNote); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to count the definitions in a file that could actually match something.
// / A staged definitions file is counted before it replaces a working one, & again once it is in place.
function countUsableDefinitions($definitionsFile) {
  // / Set variables.
  $UsableDefinitions = 0;
  $rawLines = $lineFields = array();
  $rawLine = '';
  $lineIsUsable = FALSE;
  if (file_exists($definitionsFile)) {
    $rawLines = file($definitionsFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if (!is_array($rawLines)) $rawLines = array();
    foreach ($rawLines as $rawLine) {
      redeclare($lineIsUsable, FALSE);
      if (trim($rawLine) !== '' && substr(trim($rawLine), 0, 1) !== '#') list($lineIsUsable, $lineFields) = parseDefinitionRecord(trim($rawLine));
      if ($lineIsUsable) $UsableDefinitions++; } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($rawLines, $rawLine, $lineFields, $lineIsUsable, $definitionsFile);
  return $UsableDefinitions; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to place a downloaded file beside its target as a staged replacement.
// / Nothing that is already installed is touched at this point.
function stageUpdateFile($sourceFile, $targetFile) {
  // / Set variables.
  $FileStaged = FALSE;
  $stagedFile = $targetFile.'.new';
  if (file_exists($stagedFile)) @unlink($stagedFile);
  if (!is_dir(dirname($targetFile))) makeOwnedDirectory(dirname($targetFile));
  if (file_exists($sourceFile)) $FileStaged = @copy($sourceFile, $stagedFile);
  if ($FileStaged) applyOwnership($stagedFile, FALSE);
  if (!$FileStaged) processOutput('Cannot stage a downloaded file at:  '.$stagedFile, 'warning', 0, TRUE, FALSE, FALSE);
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($stagedFile, $sourceFile, $targetFile);
  return $FileStaged; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to verify a staged file before anything installed gets moved aside.
// / A file that fails here costs nothing, because the installation is still untouched.
// / Returns whether the file passed & why it did not, in that order.
function verifyStagedFile($targetFile) {
  // / Set variables.
  global $ClassifierFileName;
  $FileVerified = FALSE;
  $VerificationNote = '';
  $stagedFile = $targetFile.'.new';
  $stagedSize = 0;
  $stagedHead = '';
  $lintPerformed = $lintPassed = FALSE;
  $lintNote = '';
  $fileExtension = fileExtensionOf($targetFile);
  if (!file_exists($stagedFile)) $VerificationNote = 'the staged file is missing';
  else {
    $stagedSize = intval(@filesize($stagedFile));
    if ($stagedSize < 1) $VerificationNote = 'the staged file is empty';
    else {
      $FileVerified = TRUE;
      // / A repository that answered with its own not found page is not an update.
      $stagedHead = trim(strval(@file_get_contents($stagedFile, FALSE, NULL, 0, 512)));
      if ($stagedHead === '404: Not Found' or $stagedHead === '') {
        $FileVerified = FALSE;
        $VerificationNote = 'the staged file carries a repository not found page'; }
      // / A PHP file has to parse before it is allowed anywhere near the installation.
      if ($FileVerified && $fileExtension === 'php') {
        list($lintPerformed, $lintPassed, $lintNote) = lintPhpFile($stagedFile);
        if (!$lintPerformed) processOutput('Cannot syntax check a staged file, continuing without that check:  '.$lintNote, 'warning', 0, TRUE, FALSE, FALSE);
        else {
          if (!$lintPassed) {
            $FileVerified = FALSE;
            $VerificationNote = 'the staged file does not parse:  '.$lintNote; } } }
      // / A definitions file has to yield something matchable before it replaces one.
      // / The classifier file is not a definitions file, so it is not held to that.
      if ($FileVerified && $fileExtension === 'def') if (basename($targetFile) !== basename($ClassifierFileName)) if (countUsableDefinitions($stagedFile) < 1) {
        $FileVerified = FALSE;
        $VerificationNote = 'the staged definitions file contains nothing matchable'; } } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($stagedFile, $stagedSize, $stagedHead, $lintPerformed, $lintPassed, $lintNote, $fileExtension, $targetFile);
  return array($FileVerified, $VerificationNote); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to move a verified staged file into place, keeping the installed one aside.
// / The installed file is renamed rather than deleted, so a failed test can put it back.
function swapStagedFile($targetFile) {
  // / Set variables.
  $FileSwapped = FALSE;
  $stagedFile = $targetFile.'.new';
  $retiredFile = $targetFile.'.old';
  $oldWasMoved = TRUE;
  // / Record who owned the installed file & what its mode was, before it moves aside.
  $targetIdentity = captureFileIdentity($targetFile);
  if (file_exists($retiredFile)) @unlink($retiredFile);
  // / A target that does not exist yet is a new file & has nothing to retire.
  if (file_exists($targetFile)) $oldWasMoved = @rename($targetFile, $retiredFile);
  if (!$oldWasMoved) processOutput('Cannot move the installed file aside:  '.$targetFile, 'warning', 0, TRUE, FALSE, FALSE);
  else {
    $FileSwapped = @rename($stagedFile, $targetFile);
    // / The replacement is given the owner & the mode of the file that it replaced.
    if ($FileSwapped) {
      if ($targetIdentity['exists']) restoreFileIdentity($targetFile, $targetIdentity);
      else applyOwnership($targetFile, FALSE); }
    // / Put the old file straight back if the staged file would not move into place.
    if (!$FileSwapped) if (file_exists($retiredFile)) @rename($retiredFile, $targetFile); }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($stagedFile, $retiredFile, $oldWasMoved, $targetIdentity, $targetFile);
  return $FileSwapped; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to put the retired file back over a target that failed its test.
function rollbackStagedFile($targetFile) {
  // / Set variables.
  $FileRestored = FALSE;
  $retiredFile = $targetFile.'.old';
  if (!file_exists($retiredFile)) $FileRestored = TRUE;
  else {
    if (file_exists($targetFile)) @unlink($targetFile);
    $FileRestored = @rename($retiredFile, $targetFile);
    if (!$FileRestored) processOutput('Cannot restore the previous file, it remains at:  '.$retiredFile, 'error', 760, TRUE, TRUE, FALSE); }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($retiredFile, $targetFile);
  return $FileRestored; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to remove the retired file once the new one has proven itself.
function commitStagedFile($targetFile) {
  // / Set variables.
  $FileCommitted = TRUE;
  $retiredFile = $targetFile.'.old';
  if (file_exists($retiredFile)) $FileCommitted = @unlink($retiredFile);
  if (!$FileCommitted) processOutput('Cannot remove the retired file, it remains at:  '.$retiredFile, 'warning', 0, TRUE, FALSE, FALSE);
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($retiredFile, $targetFile);
  return $FileCommitted; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to remove a staged file that will never be installed.
function discardStagedFile($targetFile) {
  // / Set variables.
  $FileDiscarded = TRUE;
  $stagedFile = $targetFile.'.new';
  if (file_exists($stagedFile)) $FileDiscarded = @unlink($stagedFile);
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($stagedFile, $targetFile);
  return $FileDiscarded; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to prove that the installation still runs after an update was applied.
// / The installed core is asked for its version in a process of its own, because this process still runs the code it started with.
// / Returns whether the test was performed, whether the core passed & what it printed where it did not, in that order.
function testInstalledApplication() {
  // / Set variables.
  global $InstallDir, $SEP, $CoreFile;
  $TestPerformed = $TestPassed = FALSE;
  $TestNote = '';
  $installedCore = $InstallDir.$SEP.$CoreFile;
  $commandRan = FALSE;
  $commandOutput = '';
  if (!file_exists($installedCore)) $TestNote = 'the installed core is missing';
  else {
    list($commandRan, $commandOutput) = runShellCommand(escapeshellarg(PHP_BINARY).' '.escapeshellarg($installedCore).' -version 2>&1');
    if (!$commandRan) $TestNote = 'no shell is available to test with';
    else {
      if ($commandOutput === '') $TestNote = 'the installed core returned nothing';
      else {
        $TestPerformed = TRUE;
        // / A working core opens its version screen with its own name & version number.
        if (stripos($commandOutput, 'ScanCore v') !== FALSE) $TestPassed = TRUE;
        else $TestNote = collapseToOneLine($commandOutput); } } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($installedCore, $commandRan, $commandOutput);
  return array($TestPerformed, $TestPassed, $TestNote); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to install definition updates.
// / The new definitions are staged beside the working ones, verified, then swapped in.
// / The working file is kept aside rather than deleted, so a failed test can restore it.
// / Nothing installed is touched until every staged file has passed verification.
// / Returns whether the update was installed & whether any subscribed file was left out of it, in that order.
function updateDefinitions() {
  // / Set variables.
  global $DefinitionUpdates, $DefinitionUpdateURL, $DefinitionsUpdateSubscriptions, $DefsFile, $DefInstallDir, $DefGitDir, $UpdateMethod, $SEP, $EOL, $RP, $DefinitionBranchName;
  $UpdateDefinitionsComplete = $UpdateDefinitionsErrors = FALSE;
  $subData = $subText = $verificationNote = '';
  $subCount = $subTotal = $installedCount = 0;
  $connectionResult = $sourceReady = $sourceObtained = $stagingReady = $fileVerified = $fileSwapped = FALSE;
  $defSubs = $defSubFile = $rawDefURL = $subData1 = '';
  $downloadMethod = $UpdateMethod;
  $subTotal = count($DefinitionsUpdateSubscriptions);
  $subText = buildSubscriptionText($DefinitionsUpdateSubscriptions);
  // / Only perform definition updates if they are enabled in the configuration file.
  if (!$DefinitionUpdates) processOutput('Definition updates are disabled in the configuration file.', 'warning', 0, TRUE, TRUE, FALSE);
  else {
    processOutput('Starting definition update. Update method is:  '.$UpdateMethod, 'log', 0, TRUE, FALSE, FALSE);
    processOutput('Preparing to install the following Definition Subscriptions:  '.$subText, 'log', 0, FALSE, FALSE, FALSE);
    processOutput('Cleaning update environment.', 'log', 0, TRUE, FALSE, FALSE);
    // / Remove anything an unfinished update may have left behind before starting a new one.
    clean($DefGitDir);
    clean($DefInstallDir);
    discardStagedFile($DefsFile);
    processOutput('Verifying network connectivity.', 'log', 0, FALSE, FALSE, FALSE);
    $connectionResult = connectionSuccess('definition');
    if (!$connectionResult) processOutput('Cannot verify network connectivity for definition updates!', 'error', 400, TRUE, TRUE, FALSE);
    else {
      processOutput('Verified network connectivity.', 'log', 0, FALSE, FALSE, FALSE);
      // / Clone the configured repository, when git is the configured method.
      if ($downloadMethod === 'git') $sourceReady = cloneRepository($DefinitionUpdateURL, $DefinitionBranchName, $DefInstallDir);
      // / A clone that produced nothing falls back to the raw method before the update is given up.
      if ($downloadMethod === 'git' && !$sourceReady) {
        processOutput('The git update method produced nothing, falling back to the raw update method.', 'warning', 0, TRUE, TRUE, FALSE);
        redeclare($downloadMethod, 'raw'); }
      // / Download each subscribed category by itself, when raw is the configured method or the fallback.
      if ($downloadMethod === 'raw') {
        processOutput('Creating a folder at:  '.$DefInstallDir, 'log', 0, FALSE, FALSE, FALSE);
        $sourceReady = makeOwnedDirectory($DefInstallDir);
        if ($sourceReady) foreach ($DefinitionsUpdateSubscriptions as $defSubs) {
          redeclare($defSubFile, $DefInstallDir.$SEP.'ScanCore_'.basename(strval($defSubs)).'.def');
          redeclare($rawDefURL, $DefinitionUpdateURL.'/raw/'.$DefinitionBranchName.'/ScanCore_'.strval($defSubs).'.def');
          processOutput('Attempting download against URL:  '.$rawDefURL, 'log', 0, FALSE, FALSE, FALSE);
          if (!fetchRemoteFile($rawDefURL, $defSubFile)) processOutput('Cannot download the definition subscription:  '.$defSubs, 'error', 410, TRUE, FALSE, FALSE); } }
      // / Only continue with the update if the download produced a folder.
      if (is_dir($DefInstallDir)) {
        $sourceObtained = TRUE;
        // / Protect the new folder with an index.html, where the download did not bring one of its own.
        if (!file_exists($DefInstallDir.$SEP.'index.html') && file_exists($RP.$SEP.'index.html')) @copy($RP.$SEP.'index.html', $DefInstallDir.$SEP.'index.html');
        // / Remove the git metadata directory, which we never need to keep around.
        if (is_dir($DefGitDir)) clean($DefGitDir);
        // / Combine every subscribed definition file into one body of text.
        foreach ($DefinitionsUpdateSubscriptions as $defSubs) {
          redeclare($defSubFile, $DefInstallDir.$SEP.'ScanCore_'.basename(strval($defSubs)).'.def');
          if (file_exists($defSubFile)) {
            processOutput('Loading Definition Subscription file:  '.$defSubFile, 'log', 0, FALSE, FALSE, FALSE);
            $subCount++;
            redeclare($subData1, @file_get_contents($defSubFile));
            if (is_string($subData1)) $subData = $subData.$EOL.$subData1; } }
        // / Stage the combined definitions beside the working file, which stays untouched.
        processOutput('Staging Combined Definitions file:  '.$DefsFile.'.new', 'log', 0, FALSE, FALSE, FALSE);
        if (trim($subData) === '') processOutput('The combined definition data was empty, the existing definitions were kept!', 'error', 420, TRUE, TRUE, FALSE);
        else {
          $stagingReady = (@file_put_contents($DefsFile.'.new', $subData) !== FALSE);
          if ($stagingReady) applyOwnership($DefsFile.'.new', FALSE);
          if (!$stagingReady) processOutput('Cannot write the staged definitions file:  '.$DefsFile.'.new', 'error', 430, TRUE, TRUE, FALSE);
          else {
            // / Verify the staged file before the working definitions are moved aside.
            list($fileVerified, $verificationNote) = verifyStagedFile($DefsFile);
            if (!$fileVerified) {
              discardStagedFile($DefsFile);
              processOutput('The staged definitions failed verification, the existing definitions were kept because '.$verificationNote.'!', 'error', 440, TRUE, TRUE, FALSE); }
            else {
              processOutput('Verified the staged definitions file.', 'log', 0, TRUE, FALSE, FALSE);
              // / Move the working file aside & move the staged file into its place.
              $fileSwapped = swapStagedFile($DefsFile);
              if (!$fileSwapped) processOutput('Cannot move the staged definitions into place:  '.$DefsFile, 'error', 430, TRUE, TRUE, FALSE);
              else {
                // / Test what is now installed, rather than what we believed we installed.
                if (countUsableDefinitions($DefsFile) < 1) {
                  rollbackStagedFile($DefsFile);
                  processOutput('The installed definitions failed their test & the previous definitions were restored!', 'error', 450, TRUE, TRUE, FALSE); }
                else {
                  // / Remove the file that was kept aside only once the new one has proven itself.
                  commitStagedFile($DefsFile);
                  $installedCount = $subCount;
                  $UpdateDefinitionsComplete = TRUE; } } } } } } }
    // / Remove the temporary update directory whether or not the update succeeded.
    clean($DefGitDir);
    clean($DefInstallDir); }
  if ($installedCount !== $subTotal) $UpdateDefinitionsErrors = TRUE;
  // / Say how many subscribed files arrived, wherever a download got far enough to have any.
  if ($sourceObtained && $installedCount !== $subTotal) processOutput('Only '.countWithNoun($installedCount, 'file', 'files').' of '.$subTotal.' subscribed definition files were installed.', 'warning', 0, TRUE, TRUE, FALSE);
  discardStagedFile($DefsFile);
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($defSubs, $defSubFile, $subData, $subData1, $subCount, $subTotal, $installedCount, $rawDefURL, $subText, $connectionResult, $sourceReady, $sourceObtained, $stagingReady, $fileVerified, $fileSwapped, $verificationNote, $downloadMethod);
  return array($UpdateDefinitionsComplete, $UpdateDefinitionsErrors); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to backup the configuration file prior to commencing application updates.
function backupConfig() {
  // / Set variables.
  global $ConfigFile;
  $ConfigCopied = FALSE;
  $configInc = 0;
  $backupConfigFile = $ConfigFile.'_Backup_'.$configInc.'.php';
  // / Find an unused name so an earlier backup never gets written over.
  while (file_exists($backupConfigFile)) {
    $configInc++;
    redeclare($backupConfigFile, $ConfigFile.'_Backup_'.$configInc.'.php'); }
  // / Copy the configuration file to a backup.
  processOutput('Backing up the existing configuration file to:  '.$backupConfigFile, 'log', 0, FALSE, FALSE, FALSE);
  if (file_exists($ConfigFile)) $ConfigCopied = @copy($ConfigFile, $backupConfigFile);
  if ($ConfigCopied) applyOwnership($backupConfigFile, FALSE);
  // / Only proceed if the configuration file was backed up.
  if (!$ConfigCopied) processOutput('Cannot backup the existing configuration file to:  '.$backupConfigFile, 'error', 800, TRUE, TRUE, FALSE);
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($configInc, $backupConfigFile);
  return array($ConfigCopied); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to install application updates.
// / Every subscribed file is downloaded, staged beside its target & verified first.
// / Nothing that is installed is moved until the whole set has passed, so one bad file cannot leave half an update in place.
// / The installation is then tested, & every file that was moved aside is restored if that test fails.
// / Returns whether the update was installed & whether any subscribed file was left out of it, in that order.
function updateApplication() {
  // / Set variables.
  global $ApplicationUpdates, $ApplicationUpdateURL, $ApplicationUpdateSubscriptions, $InstallDir, $AppInstallDir, $AppGitDir, $UpdateMethod, $SEP, $RP, $ApplicationBranchName;
  $UpdateApplicationComplete = $UpdateApplicationErrors = FALSE;
  $connectionResult = $sourceReady = $sourceObtained = $configCopied = $stagingFailed = $verificationFailed = FALSE;
  $swapFailed = $testPerformed = $testPassed = $fileVerified = $indexWasDownloaded = FALSE;
  $appSubs = $appSubFile = $rawAppURL = $relativeName = $targetFile = $stagedTarget = $verificationNote = $testNote = '';
  $stagedTargets = $newTargets = array();
  $subCount = $installedCount = 0;
  $downloadMethod = $UpdateMethod;
  $subTotal = count($ApplicationUpdateSubscriptions);
  // / Only perform application updates if they are enabled in the configuration file.
  if (!$ApplicationUpdates) processOutput('Application updates are disabled in the configuration file.', 'warning', 0, TRUE, TRUE, FALSE);
  else {
    processOutput('Starting application update. Update method is:  '.$UpdateMethod, 'log', 0, TRUE, FALSE, FALSE);
    list($configCopied) = backupConfig();
    if (!$configCopied) processOutput('Application update was aborted because the existing configuration file could not be backed up!', 'error', 900, TRUE, TRUE, FALSE);
    else {
      processOutput('Cleaning update environment.', 'log', 0, TRUE, FALSE, FALSE);
      // / Remove anything an unfinished update may have left behind before starting a new one.
      clean($AppGitDir);
      clean($AppInstallDir);
      foreach ($ApplicationUpdateSubscriptions as $appSubs) {
        redeclare($relativeName, str_replace('/', $SEP, strval($appSubs)));
        if (!str_contains($relativeName, '..')) discardStagedFile($InstallDir.$SEP.$relativeName); }
      processOutput('Verifying network connectivity.', 'log', 0, FALSE, FALSE, FALSE);
      $connectionResult = connectionSuccess('application');
      if (!$connectionResult) processOutput('Cannot verify network connectivity for application updates!', 'error', 700, TRUE, TRUE, FALSE);
      else {
        processOutput('Verified network connectivity.', 'log', 0, FALSE, FALSE, FALSE);
        // / Clone the configured repository, when git is the configured method.
        if ($downloadMethod === 'git') $sourceReady = cloneRepository($ApplicationUpdateURL, $ApplicationBranchName, $AppInstallDir);
        // / A clone that produced nothing falls back to the raw method before the update is given up.
        if ($downloadMethod === 'git' && !$sourceReady) {
          processOutput('The git update method produced nothing, falling back to the raw update method.', 'warning', 0, TRUE, TRUE, FALSE);
          redeclare($downloadMethod, 'raw'); }
        // / Download each subscribed file by itself, when raw is the configured method or the fallback.
        if ($downloadMethod === 'raw') {
          processOutput('Creating a folder at:  '.$AppInstallDir, 'log', 0, FALSE, FALSE, FALSE);
          $sourceReady = makeOwnedDirectory($AppInstallDir);
          if ($sourceReady) foreach ($ApplicationUpdateSubscriptions as $appSubs) {
            redeclare($relativeName, str_replace('/', $SEP, strval($appSubs)));
            redeclare($appSubFile, $AppInstallDir.$SEP.$relativeName);
            redeclare($rawAppURL, $ApplicationUpdateURL.'/raw/'.$ApplicationBranchName.'/'.strval($appSubs));
            // / A subscription that would be written outside the temporary folder is never downloaded.
            if (!str_contains($relativeName, '..')) {
              makeOwnedDirectory(dirname($appSubFile));
              processOutput('Attempting download against URL:  '.$rawAppURL, 'log', 0, FALSE, FALSE, FALSE);
              if (!fetchRemoteFile($rawAppURL, $appSubFile)) processOutput('Cannot download the application file:  '.$appSubs, 'error', 710, TRUE, FALSE, FALSE); } } }
        // / Only continue with the update if the download produced a folder.
        if (is_dir($AppInstallDir)) {
          $sourceObtained = TRUE;
          // / Note whether the download brought an index.html, then protect the new folder with the installed one where it did not.
          $indexWasDownloaded = file_exists($AppInstallDir.$SEP.'index.html');
          if (!$indexWasDownloaded && file_exists($RP.$SEP.'index.html')) @copy($RP.$SEP.'index.html', $AppInstallDir.$SEP.'index.html');
          // / Remove the git metadata directory, which we never need to keep around.
          if (is_dir($AppGitDir)) clean($AppGitDir);
          // / Stage every subscribed file beside its target, before anything that is installed is moved.
          foreach ($ApplicationUpdateSubscriptions as $appSubs) {
            redeclare($relativeName, str_replace('/', $SEP, strval($appSubs)));
            redeclare($appSubFile, $AppInstallDir.$SEP.$relativeName);
            redeclare($targetFile, $InstallDir.$SEP.$relativeName);
            // / Refuse a subscription name that would write outside the installation directory.
            if (str_contains($relativeName, '..')) {
              $stagingFailed = TRUE;
              processOutput('Refused an application subscription that points outside the installation directory:  '.$appSubs, 'error', 730, TRUE, TRUE, FALSE); }
            else {
              // / The index.html that protects the new folder is not one that the update brought.
              if (!file_exists($appSubFile) or ($relativeName === 'index.html' && !$indexWasDownloaded)) {
                $stagingFailed = TRUE;
                processOutput('The update does not hold the subscribed application file:  '.$appSubs, 'warning', 0, TRUE, TRUE, FALSE); }
              else {
                $subCount++;
                processOutput('Staging Application file:  '.$targetFile.'.new', 'log', 0, FALSE, FALSE, FALSE);
                if (!stageUpdateFile($appSubFile, $targetFile)) $stagingFailed = TRUE;
                else $stagedTargets[] = $targetFile; } } }
          // / Verify every staged file before a single installed file is moved aside.
          foreach ($stagedTargets as $stagedTarget) {
            list($fileVerified, $verificationNote) = verifyStagedFile($stagedTarget);
            if (!$fileVerified) {
              $verificationFailed = TRUE;
              processOutput('A staged application file failed verification because '.$verificationNote.':  '.$stagedTarget, 'error', 740, TRUE, TRUE, FALSE); } }
          if ($subCount !== $subTotal) $stagingFailed = TRUE;
          // / Abandon the whole update if anything at all is wrong with the staged set.
          if ($stagingFailed or $verificationFailed) {
            foreach ($stagedTargets as $stagedTarget) discardStagedFile($stagedTarget);
            processOutput('The application update was abandoned before any installed file was touched.', 'warning', 0, TRUE, TRUE, FALSE); }
          else {
            processOutput('Verified '.countWithNoun(count($stagedTargets), 'staged file', 'staged files').'.', 'log', 0, TRUE, FALSE, FALSE);
            // / Move each installed file aside & move its staged replacement into place.
            foreach ($stagedTargets as $stagedTarget) {
              processOutput('Installing Application file:  '.$stagedTarget, 'log', 0, FALSE, FALSE, FALSE);
              // / A file that the update brings for the first time is remembered, because nothing can be put back in its place.
              if (!file_exists($stagedTarget)) $newTargets[] = $stagedTarget;
              if (!swapStagedFile($stagedTarget)) {
                $swapFailed = TRUE;
                processOutput('Cannot install the application file:  '.$stagedTarget, 'error', 720, TRUE, TRUE, FALSE); }
              else $installedCount++; }
            // / Test what is now installed by asking the new core for its version.
            list($testPerformed, $testPassed, $testNote) = testInstalledApplication();
            if (!$testPerformed) processOutput('Cannot test the installed application, continuing without that check:  '.$testNote, 'warning', 0, TRUE, FALSE, FALSE);
            // / Put every file back if the installation does not run.
            if ($swapFailed or ($testPerformed && !$testPassed)) {
              foreach ($stagedTargets as $stagedTarget) {
                rollbackStagedFile($stagedTarget);
                discardStagedFile($stagedTarget); }
              // / A file that the update brought for the first time is removed, so the installation is left as it was found.
              foreach ($newTargets as $stagedTarget) if (file_exists($stagedTarget)) @unlink($stagedTarget);
              $installedCount = 0;
              processOutput('The updated application failed its test & the previous application was restored:  '.$testNote, 'error', 750, TRUE, TRUE, FALSE); }
            else {
              // / Remove the files that were kept aside only once the new application has proven itself.
              foreach ($stagedTargets as $stagedTarget) commitStagedFile($stagedTarget);
              if ($testPassed) processOutput('Tested the installed application.', 'log', 0, TRUE, FALSE, FALSE);
              $UpdateApplicationComplete = TRUE; } } }
        // / Remove the temporary update directory whether or not the update succeeded.
        clean($AppGitDir);
        clean($AppInstallDir); } } }
  if ($installedCount !== $subTotal) $UpdateApplicationErrors = TRUE;
  // / Say how many subscribed files were installed, wherever a download got far enough to have any.
  if ($sourceObtained && $installedCount !== $subTotal) processOutput('Only '.countWithNoun($installedCount, 'file', 'files').' of '.$subTotal.' subscribed application files were installed.', 'warning', 0, TRUE, TRUE, FALSE);
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($appSubs, $appSubFile, $rawAppURL, $relativeName, $targetFile, $stagedTargets, $newTargets, $stagedTarget, $subCount, $subTotal, $installedCount, $connectionResult, $sourceReady, $sourceObtained, $configCopied, $stagingFailed, $verificationFailed, $swapFailed, $testPerformed, $testPassed, $testNote, $verificationNote, $fileVerified, $downloadMethod, $indexWasDownloaded);
  return array($UpdateApplicationComplete, $UpdateApplicationErrors); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to render a count alongside a noun that agrees with it.
// / A summary that reads 1 files undermines confidence in everything above it.
function countWithNoun($itemCount, $singularNoun, $pluralNoun) {
  // / Set variables.
  $CountedPhrase = intval($itemCount).' '.$pluralNoun;
  if (intval($itemCount) === 1) $CountedPhrase = intval($itemCount).' '.$singularNoun;
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($itemCount, $singularNoun, $pluralNoun);
  return $CountedPhrase; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to say whether a named classifier is switched on in the configuration.
// / A name is compared without regard to case, so SCAD in the configuration is scad in the classifier definitions.
function classifierIsEnabled($classifierName) {
  // / Set variables.
  global $EnabledClassifiers;
  $ClassifierEnabled = FALSE;
  $wantedName = strtolower(trim(strval($classifierName)));
  if (is_array($EnabledClassifiers)) foreach ($EnabledClassifiers as $enabledEntry) {
    if (strtolower(trim(strval($enabledEntry))) === $wantedName) $ClassifierEnabled = TRUE; }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($wantedName, $enabledEntry, $classifierName);
  return $ClassifierEnabled; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to read the lower case extension from a file name.
function fileExtensionOf($file) {
  // / Set variables.
  $FileExtension = '';
  $baseName = basename(strval($file));
  $dotPosition = strrpos($baseName, '.');
  if ($dotPosition !== FALSE) $FileExtension = strtolower(substr($baseName, $dotPosition + 1));
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($baseName, $dotPosition, $file);
  return $FileExtension; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to split a comma separated list while honouring quoted members.
// / A quoted member keeps its leading & trailing whitespace & may contain a comma.
// / Returns each member alongside a flag saying whether it arrived quoted.
function splitDefinitionList($listText) {
  // / Set variables.
  $ListMembers = array();
  $characterIndex = 0;
  $characterTop = strlen($listText);
  $currentMember = '';
  $insideQuotes = FALSE;
  $memberWasQuoted = FALSE;
  $currentCharacter = '';
  while ($characterIndex < $characterTop) {
    redeclare($currentCharacter, substr($listText, $characterIndex, 1));
    // / A double quote opens or closes a literal member.
    if ($currentCharacter === '"') {
      $insideQuotes = !$insideQuotes;
      if ($insideQuotes) {
        $memberWasQuoted = TRUE;
        // / Whitespace sitting between the comma & the quote is separator, not content.
        // / A quoted member is never trimmed later, so it has to be dropped here instead.
        if (trim($currentMember) === '') redeclare($currentMember, ''); } }
    else {
      // / A comma outside quotes ends the current member.
      if ($currentCharacter === ',' && !$insideQuotes) {
        if (trim($currentMember) !== '' or $memberWasQuoted) $ListMembers[] = array('text' => ($memberWasQuoted ? $currentMember : trim($currentMember)), 'quoted' => $memberWasQuoted);
        redeclare($currentMember, '');
        $memberWasQuoted = FALSE; }
      else $currentMember = $currentMember.$currentCharacter; }
    $characterIndex++; }
  if (trim($currentMember) !== '' or $memberWasQuoted) $ListMembers[] = array('text' => ($memberWasQuoted ? $currentMember : trim($currentMember)), 'quoted' => $memberWasQuoted);
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($characterIndex, $characterTop, $currentMember, $insideQuotes, $memberWasQuoted, $currentCharacter, $listText);
  return $ListMembers; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to say which kind of record a line of the classifier definitions file holds.
// / Returns embed, type, magic, lang or handler, or an empty string for a blank line or a comment.
function classifierRecordKind($recordBody) {
  // / Set variables.
  $RecordKind = '';
  $keywordKind = '';
  if ($recordBody !== '' && substr($recordBody, 0, 1) !== '#') {
    // / A record that opens with none of the four keywords is a handler record, which opens with a type.
    $RecordKind = 'handler';
    foreach (array('embed', 'type', 'magic', 'lang') as $keywordKind) if (substr($recordBody, 0, strlen($keywordKind) + 1) === $keywordKind.' ') $RecordKind = $keywordKind; }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($keywordKind, $recordBody);
  return $RecordKind; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to split a classifier record at its equals sign.
// / The name sits between the keyword, whose length is given, & the equals sign.
// / Returns the name & the text after the equals sign, in that order, or two empty strings where there is no equals sign.
function splitClassifierRecord($recordBody, $keywordLength) {
  // / Set variables.
  $RecordName = $RecordText = '';
  $equalsPosition = strpos($recordBody, '=');
  if ($equalsPosition !== FALSE) {
    $RecordName = trim(substr($recordBody, $keywordLength, $equalsPosition - $keywordLength));
    $RecordText = trim(substr($recordBody, $equalsPosition + 1)); }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($equalsPosition, $recordBody, $keywordLength);
  return array($RecordName, $RecordText); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to read a comma separated run of file extensions.
// / Returns each extension in lower case, with the empty ones left out.
function parseExtensionList($extensionText) {
  // / Set variables.
  $ExtensionList = array();
  $extensionEntry = '';
  foreach (explode(',', $extensionText) as $extensionEntry) if (trim($extensionEntry) !== '') $ExtensionList[] = strtolower(trim($extensionEntry));
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($extensionEntry, $extensionText);
  return $ExtensionList; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to read the byte signatures a magic record declares.
// / Each one is a decimal offset, a colon & hexadecimal bytes.
// / A signature has to be whole bytes, so one with an odd number of digits is left out.
function parseMagicSignatures($signatureText) {
  // / Set variables.
  $SignatureList = array();
  $signaturePair = $magicBytes = '';
  $colonPosition = FALSE;
  foreach (explode(',', $signatureText) as $signaturePair) {
    redeclare($colonPosition, strpos($signaturePair, ':'));
    if ($colonPosition !== FALSE) {
      redeclare($magicBytes, strtolower(trim(substr($signaturePair, $colonPosition + 1))));
      if ($magicBytes !== '' && strlen($magicBytes) % 2 === 0 && preg_match('/^[0-9a-f]+$/', $magicBytes) === 1) $SignatureList[] = array('offset' => intval(trim(substr($signaturePair, 0, $colonPosition))), 'bytes' => $magicBytes); } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($signaturePair, $magicBytes, $colonPosition, $signatureText);
  return $SignatureList; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to read the handlers a handler record declares.
// / A handler is a token followed by its capabilities in brackets.
// / A quoted token keeps whatever whitespace & punctuation it arrived with.
// / Returns the handlers & the number of members that could not be read, in that order.
function parseHandlerMembers($handlerText) {
  // / Set variables.
  $HandlerList = array();
  $MalformedMembers = 0;
  $handlerMember = array();
  $handlerToken = $handlerCapabilities = '';
  $bracketPosition = FALSE;
  foreach (splitDefinitionList($handlerText) as $handlerMember) {
    // / Split the trailing capability list away from the handler token itself.
    redeclare($bracketPosition, strrpos($handlerMember['text'], '('));
    redeclare($handlerToken, '');
    redeclare($handlerCapabilities, '');
    if ($bracketPosition !== FALSE) {
      $handlerToken = rtrim(substr($handlerMember['text'], 0, $bracketPosition));
      $handlerCapabilities = trim(rtrim(trim(substr($handlerMember['text'], $bracketPosition + 1)), ')'));
      if (!$handlerMember['quoted']) $handlerToken = trim($handlerToken);
      else $handlerToken = trim($handlerToken, '"'); }
    if ($handlerToken === '' or $handlerCapabilities === '') $MalformedMembers++;
    else $HandlerList[] = array('token' => $handlerToken, 'capabilities' => $handlerCapabilities, 'literal' => $handlerMember['quoted']); }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($handlerMember, $handlerToken, $handlerCapabilities, $bracketPosition, $handlerText);
  return array($HandlerList, $MalformedMembers); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to read the name, the confidence & the signatures an embed record declares.
// / The confidence is written in brackets after the name, & is medium where none is written.
// / Returns the name, the confidence & the signatures, in that order.
function parseEmbedRecord($recordBody) {
  // / Set variables.
  $EmbedName = $embedText = '';
  $EmbedConfidence = 'medium';
  $SignatureList = array();
  $embedParts = $signatureMember = array();
  list($EmbedName, $embedText) = splitClassifierRecord($recordBody, 6);
  if (preg_match('/^(.+)\((high|medium|low)\)$/i', $EmbedName, $embedParts)) {
    $EmbedName = trim($embedParts[1]);
    $EmbedConfidence = strtolower($embedParts[2]); }
  foreach (splitDefinitionList($embedText) as $signatureMember) if ($signatureMember['text'] !== '') $SignatureList[] = decodeDefinitionValue($signatureMember['text']);
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($embedText, $embedParts, $signatureMember, $recordBody);
  return array($EmbedName, $EmbedConfidence, $SignatureList); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to read the classifier definitions file into a usable structure.
// / A classifier describes what a file is & what it is able to reach out to.
// / It never describes a threat, so nothing here is ever reported as an infection.
// / A record that belongs to a classifier that is not enabled is never loaded, so it costs nothing to scan for.
// / Returns whether any record was loaded, the classifiers & the length of the longest token or signature, in that order.
function loadClassifiers($ClassifierFile) {
  // / Set variables.
  global $EnabledClassifiers;
  $ClassifiersLoaded = FALSE;
  $Classifiers = array('types' => array(), 'languages' => array(), 'handlers' => array(), 'magic' => array(), 'embeds' => array());
  $ClassifierLongestToken = 0;
  $rawLines = $recordList = $signatureMembers = $handlerEntry = $signatureMember = array();
  $rawLine = $recordBody = $recordKind = $recordName = $recordText = $embedConfidence = $embedSignature = $enabledName = '';
  $recordCount = $malformedCount = $skippedCount = $malformedMembers = 0;
  $colonPosition = $keywordEnd = FALSE;
  if (!file_exists($ClassifierFile)) processOutput('Cannot load the classifier definitions file, content classification is disabled:  '.$ClassifierFile, 'error', 520, TRUE, FALSE, FALSE);
  else {
    $rawLines = file($ClassifierFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if (!is_array($rawLines)) $rawLines = array();
    foreach ($rawLines as $rawLine) {
      redeclare($recordBody, trim($rawLine));
      redeclare($recordKind, classifierRecordKind($recordBody));
      // / A type record maps a type name onto the extensions that type covers.
      if ($recordKind === 'type') {
        list($recordName, $recordText) = splitClassifierRecord($recordBody, 5);
        redeclare($recordList, parseExtensionList($recordText));
        if ($recordName === '' or count($recordList) === 0) $malformedCount++;
        else {
          $Classifiers['types'][strtolower($recordName)] = $recordList;
          $recordCount++; } }
      // / A magic record declares the bytes that identify a type whatever its name.
      if ($recordKind === 'magic') {
        list($recordName, $recordText) = splitClassifierRecord($recordBody, 6);
        redeclare($recordList, parseMagicSignatures($recordText));
        if ($recordName === '' or count($recordList) === 0) $malformedCount++;
        else {
          if (!classifierIsEnabled($recordName)) $skippedCount++;
          else {
            $Classifiers['magic'][strtolower($recordName)] = $recordList;
            $recordCount++; } } }
      // / A language record declares an executable language & how to recognize it.
      // / Its extensions sit before the first colon & its signatures after it.
      if ($recordKind === 'lang') {
        list($recordName, $recordText) = splitClassifierRecord($recordBody, 5);
        redeclare($colonPosition, strpos($recordText, ':'));
        redeclare($recordList, parseExtensionList($colonPosition === FALSE ? $recordText : substr($recordText, 0, $colonPosition)));
        redeclare($signatureMembers, splitDefinitionList($colonPosition === FALSE ? '' : trim(substr($recordText, $colonPosition + 1))));
        if ($recordName === '') $malformedCount++;
        else {
          if (!classifierIsEnabled('Language')) $skippedCount++;
          else {
            $Classifiers['languages'][] = array('name' => $recordName, 'extensions' => $recordList, 'signatures' => $signatureMembers);
            foreach ($signatureMembers as $signatureMember) if (strlen($signatureMember['text']) > $ClassifierLongestToken) $ClassifierLongestToken = strlen($signatureMember['text']);
            $recordCount++; } } }
      // / A handler record opens with the type it applies to, & every handler in it counts as a record.
      if ($recordKind === 'handler') {
        redeclare($keywordEnd, strpos($recordBody, ' '));
        redeclare($recordName, $keywordEnd === FALSE ? '' : strtolower(trim(substr($recordBody, 0, $keywordEnd))));
        redeclare($recordText, $keywordEnd === FALSE ? '' : trim(substr($recordBody, $keywordEnd + 1)));
        list($recordList, $malformedMembers) = parseHandlerMembers($recordText);
        if ($recordName === '' or (count($recordList) + $malformedMembers) === 0) $malformedCount++;
        else {
          if (!classifierIsEnabled($recordName)) $skippedCount++;
          else {
            if (!isset($Classifiers['handlers'][$recordName])) $Classifiers['handlers'][$recordName] = array();
            $malformedCount = $malformedCount + $malformedMembers;
            foreach ($recordList as $handlerEntry) {
              $Classifiers['handlers'][$recordName][] = $handlerEntry;
              if (strlen($handlerEntry['token']) > $ClassifierLongestToken) $ClassifierLongestToken = strlen($handlerEntry['token']);
              $recordCount++; } } } }
      // / An embed record declares code that has no business inside an image.
      // / It is loaded whichever classifiers are enabled, because image inspection is switched on by itself.
      if ($recordKind === 'embed') {
        list($recordName, $embedConfidence, $recordList) = parseEmbedRecord($recordBody);
        if ($recordName === '' or count($recordList) === 0) $malformedCount++;
        else {
          $Classifiers['embeds'][] = array('name' => $recordName, 'confidence' => $embedConfidence, 'signatures' => $recordList);
          foreach ($recordList as $embedSignature) if (strlen($embedSignature) > $ClassifierLongestToken) $ClassifierLongestToken = strlen($embedSignature);
          $recordCount++; } } }
    processOutput('Loaded '.countWithNoun($recordCount, 'classifier record', 'classifier records').'.', 'log', 0, FALSE, FALSE, FALSE);
    // / Say plainly how much was left out, so a quiet classifier is never a mystery.
    if ($skippedCount > 0) processOutput('Skipped '.countWithNoun($skippedCount, 'classifier record', 'classifier records').' of classifiers that are not enabled.', 'log', 0, TRUE, FALSE, FALSE);
    if ($malformedCount > 0) processOutput('Ignored '.countWithNoun($malformedCount, 'malformed classifier record', 'malformed classifier records').'.', 'warning', 0, TRUE, FALSE, FALSE);
    // / Report a configured classifier that this definitions file knows nothing about.
    foreach ($EnabledClassifiers as $enabledName) if (strtolower(trim(strval($enabledName))) !== 'language') {
      if (!isset($Classifiers['handlers'][strtolower(trim(strval($enabledName)))])) processOutput('The enabled classifier named '.$enabledName.' has no handlers declared in the classifier definitions file.', 'warning', 0, TRUE, FALSE, FALSE); }
    if ($recordCount < 1) processOutput('The classifier definitions file contains no usable records:  '.$ClassifierFile, 'error', 530, TRUE, FALSE, FALSE);
    else $ClassifiersLoaded = TRUE; }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($rawLines, $rawLine, $recordBody, $recordKind, $recordName, $recordText, $recordList, $signatureMembers, $signatureMember, $handlerEntry, $embedConfidence, $embedSignature, $enabledName, $recordCount, $malformedCount, $skippedCount, $malformedMembers, $colonPosition, $keywordEnd);
  return array($ClassifiersLoaded, $Classifiers, $ClassifierLongestToken); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to step past whitespace & comments while looking ahead from a token.
// / OpenSCAD treats a comment as a token separator, so surface/**/("file") is a call.
// / Returns the position of the first character that is neither, or the lookahead limit where none is found.
function skipToMeaningfulCharacter($haystack, $startPosition, $lookaheadLimit) {
  // / Set variables.
  $MeaningfulPosition = $startPosition;
  $haystackTop = strlen($haystack);
  $stopPosition = $startPosition + $lookaheadLimit;
  $keepSkipping = TRUE;
  $currentCharacter = $nextCharacter = '';
  $commentEnd = 0;
  if ($stopPosition > $haystackTop) $stopPosition = $haystackTop;
  while ($keepSkipping && $MeaningfulPosition < $stopPosition) {
    redeclare($currentCharacter, substr($haystack, $MeaningfulPosition, 1));
    redeclare($nextCharacter, substr($haystack, $MeaningfulPosition + 1, 1));
    // / Whitespace & newlines never end a call, so a call may span any number of lines.
    if ($currentCharacter === ' ' or $currentCharacter === "\t" or $currentCharacter === "\r" or $currentCharacter === "\n") $MeaningfulPosition++;
    else {
      // / A block comment separates a keyword from its bracket without ending the call.
      if ($currentCharacter === '/' && $nextCharacter === '*') {
        redeclare($commentEnd, strpos($haystack, '*/', $MeaningfulPosition + 2));
        if ($commentEnd === FALSE) {
          $MeaningfulPosition = $stopPosition;
          $keepSkipping = FALSE; }
        else $MeaningfulPosition = $commentEnd + 2; }
      else {
        // / A line comment runs to the end of its line & then stops separating.
        if ($currentCharacter === '/' && $nextCharacter === '/') {
          redeclare($commentEnd, strpos($haystack, "\n", $MeaningfulPosition + 2));
          if ($commentEnd === FALSE) {
            $MeaningfulPosition = $stopPosition;
            $keepSkipping = FALSE; }
          else $MeaningfulPosition = $commentEnd + 1; }
        else $keepSkipping = FALSE; } } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($haystackTop, $stopPosition, $keepSkipping, $currentCharacter, $nextCharacter, $commentEnd, $haystack, $startPosition, $lookaheadLimit);
  return $MeaningfulPosition; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to decide whether a handler token really appears in a block of data.
// / A literal token is a plain case insensitive search & carries no context rule.
// / A bare token must sit on an identifier boundary & must be followed by a bracket.
// / Those two rules together are what stop include_lid being read as an include call.
function handlerTokenAppears($haystack, $handlerToken, $isLiteral) {
  // / Set variables.
  $TokenAppears = FALSE;
  $searchOffset = 0;
  $foundPosition = 0;
  $characterBefore = $followingCharacter = '';
  $lookaheadPosition = 0;
  $keepSearching = TRUE;
  if ($handlerToken === '') $keepSearching = FALSE;
  // / A literal token is reported the moment it is seen anywhere in the data.
  if ($keepSearching && $isLiteral) {
    if (stripos($haystack, $handlerToken) !== FALSE) $TokenAppears = TRUE;
    $keepSearching = FALSE; }
  // / A bare token is inspected at every position it occupies until one qualifies.
  while ($keepSearching) {
    redeclare($foundPosition, stripos($haystack, $handlerToken, $searchOffset));
    if ($foundPosition === FALSE) $keepSearching = FALSE;
    else {
      redeclare($characterBefore, $foundPosition > 0 ? substr($haystack, $foundPosition - 1, 1) : '');
      // / The character before a call is never part of an identifier.
      if ($characterBefore === '' or preg_match('/[^A-Za-z0-9_]/', $characterBefore)) {
        redeclare($lookaheadPosition, skipToMeaningfulCharacter($haystack, $foundPosition + strlen($handlerToken), 256));
        redeclare($followingCharacter, substr($haystack, $lookaheadPosition, 1));
        // / The next meaningful character decides whether this is a call or a name.
        if ($followingCharacter === '(' or $followingCharacter === '<' or $followingCharacter === '=' or $followingCharacter === ':') {
          $TokenAppears = TRUE;
          $keepSearching = FALSE; } }
      if ($keepSearching) $searchOffset = $foundPosition + 1; } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($searchOffset, $foundPosition, $characterBefore, $followingCharacter, $lookaheadPosition, $keepSearching, $haystack, $handlerToken, $isLiteral);
  return $TokenAppears; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to test a block of data against the handlers of the candidate types.
// / A hit is remembered by type & token so a repeated match reports only once.
function matchHandlerTokens($haystack, $Classifiers, $candidateTypes, &$handlerHits, $containerEntry = '') {
  // / Set variables.
  $MatchesFound = 0;
  $hitKey = '';
  foreach ($candidateTypes as $candidateType) if (isset($Classifiers['handlers'][$candidateType])) {
    foreach ($Classifiers['handlers'][$candidateType] as $handlerEntry) {
      redeclare($hitKey, $candidateType.'|'.$handlerEntry['token']);
      if (!isset($handlerHits[$hitKey])) if (handlerTokenAppears($haystack, $handlerEntry['token'], $handlerEntry['literal'])) {
        $handlerHits[$hitKey] = array('type' => $candidateType, 'token' => $handlerEntry['token'], 'capabilities' => $handlerEntry['capabilities'], 'entry' => $containerEntry);
        $MatchesFound++; } } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($candidateType, $handlerEntry, $hitKey, $haystack, $candidateTypes, $containerEntry);
  return $MatchesFound; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to test a block of data against every declared language signature.
// / A signature is a plain literal, so nothing here needs an identifier boundary.
function matchLanguageSignatures($haystack, $Classifiers, &$languageHits) {
  // / Set variables.
  $MatchesFound = 0;
  $hitKey = '';
  foreach ($Classifiers['languages'] as $languageIndex => $languageEntry) {
    foreach ($languageEntry['signatures'] as $signatureIndex => $signatureEntry) {
      redeclare($hitKey, $languageIndex.'|'.$signatureIndex);
      if (!isset($languageHits[$hitKey])) if ($signatureEntry['text'] !== '') if (stripos($haystack, $signatureEntry['text']) !== FALSE) {
        $languageHits[$hitKey] = $languageEntry['name'];
        $MatchesFound++; } } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($languageIndex, $languageEntry, $signatureIndex, $signatureEntry, $hitKey, $haystack);
  return $MatchesFound; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to name the language an extension belongs to, when one claims it.
function languageForExtension($Classifiers, $fileExtension) {
  // / Set variables.
  $LanguageName = '';
  if ($fileExtension !== '') foreach ($Classifiers['languages'] as $languageEntry) if ($LanguageName === '') {
    if (in_array($fileExtension, $languageEntry['extensions'], TRUE)) $LanguageName = $languageEntry['name']; }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($languageEntry, $fileExtension);
  return $LanguageName; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to list the classifier types that claim a given file extension.
// / A type with no declared extension list claims the extension named after it.
function typesForExtension($Classifiers, $fileExtension) {
  // / Set variables.
  $CandidateTypes = array();
  if ($fileExtension !== '') foreach ($Classifiers['handlers'] as $typeName => $handlerList) {
    if (isset($Classifiers['types'][$typeName])) {
      if (in_array($fileExtension, $Classifiers['types'][$typeName], TRUE)) $CandidateTypes[] = $typeName; }
    else {
      if ($typeName === $fileExtension) $CandidateTypes[] = $typeName; } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($typeName, $handlerList, $fileExtension);
  return $CandidateTypes; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to decide which language the collected content signatures point at.
// / A single generic signature is never enough, because prose contains those too.
function resolveContentLanguage($languageHits, $Classifiers) {
  // / Set variables.
  global $MinimumLanguageSignatures;
  $ResolvedLanguage = '';
  $hitTally = array();
  $bestScore = 0;
  foreach ($languageHits as $hitName) {
    if (!isset($hitTally[$hitName])) $hitTally[$hitName] = 0;
    $hitTally[$hitName] = $hitTally[$hitName] + 1; }
  foreach ($hitTally as $tallyName => $tallyCount) if ($tallyCount >= $MinimumLanguageSignatures) if ($tallyCount > $bestScore) {
    $bestScore = $tallyCount;
    $ResolvedLanguage = $tallyName; }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($hitTally, $hitName, $tallyName, $tallyCount, $bestScore, $languageHits);
  return $ResolvedLanguage; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to render the collected handler hits as one readable sentence.
function describeHandlerHits($handlerHits) {
  // / Set variables.
  $HandlerDescription = '';
  $typeList = array();
  $tokenList = array();
  $tokenText = '';
  foreach ($handlerHits as $hitEntry) {
    if (!in_array($hitEntry['type'], $typeList, TRUE)) $typeList[] = $hitEntry['type'];
    // / A handler that was found inside a container names the member it was found in.
    if (isset($hitEntry['entry']) && $hitEntry['entry'] !== '') redeclare($tokenText, $hitEntry['token'].'('.$hitEntry['capabilities'].') in '.$hitEntry['entry']);
    else redeclare($tokenText, $hitEntry['token'].'('.$hitEntry['capabilities'].')');
    // / Two types may declare the same handler, so report the handler once.
    if (!in_array($tokenText, $tokenList, TRUE)) $tokenList[] = $tokenText; }
  $HandlerDescription = 'Type: '.implode('/', $typeList).', Handlers: '.implode(', ', $tokenList);
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($typeList, $tokenList, $tokenText, $hitEntry, $handlerHits);
  return $HandlerDescription; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to name the type whose magic signature the opening bytes of a file carry.
// / Every offset that a signature declares must match, because one common byte identifies nothing.
// / A signature tells two formats that share an extension apart, such as a transport stream & a TypeScript source.
function magicTypeFor($Classifiers, $fileHeader) {
  // / Set variables.
  $MagicType = '';
  $signatureMatches = FALSE;
  $expectedBytes = '';
  $actualBytes = '';
  foreach ($Classifiers['magic'] as $typeName => $signatureList) if ($MagicType === '') {
    redeclare($signatureMatches, TRUE);
    foreach ($signatureList as $signatureEntry) if ($signatureMatches) {
      redeclare($expectedBytes, $signatureEntry['bytes']);
      redeclare($actualBytes, strtolower(bin2hex(substr($fileHeader, $signatureEntry['offset'], strlen($expectedBytes) / 2))));
      if ($actualBytes !== $expectedBytes) $signatureMatches = FALSE; }
    if ($signatureMatches) $MagicType = $typeName; }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($typeName, $signatureList, $signatureEntry, $signatureMatches, $expectedBytes, $actualBytes, $fileHeader);
  return $MagicType; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to decide whether a container member is worth classifying for handlers.
// / A handler lives in markup, never in a picture or a font.
function archiveEntryIsInteresting($entryName) {
  // / Set variables.
  $EntryIsInteresting = FALSE;
  $entryExtension = fileExtensionOf($entryName);
  $readableExtensions = array('xml', 'rels', 'txt', 'htm', 'html', 'xhtml', 'opf', 'ncx', 'css', 'js', 'json', 'vtt', 'svg', 'fodt', 'plist', 'model', 'config', 'scad', 'mtl', 'obj', 'dae');
  // / A directory entry holds no content of its own.
  if (substr($entryName, -1) !== '/') {
    if (in_array($entryExtension, $readableExtensions, TRUE)) $EntryIsInteresting = TRUE;
    // / An entry with no extension at all is usually a manifest, so it is worth reading.
    if ($entryExtension === '') $EntryIsInteresting = TRUE; }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($entryExtension, $readableExtensions, $entryName);
  return $EntryIsInteresting; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to render the path of a scanned file or folder safely into a report line.
// / A control character is written as the escape that names it, so a hostile name cannot forge a line of its own.
// / A path is never shortened, because a caller finds its file in a line by the whole path that it handed over.
function printablePath($path) {
  // / Set variables.
  $PrintablePath = strval($path);
  if (preg_match('/[\x00-\x1F\x7F]/', $PrintablePath)) $PrintablePath = preg_replace_callback('/[\x00-\x1F\x7F]/', function($byteMatch) { return sprintf('\\x%02x', ord($byteMatch[0])); }, $PrintablePath);
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($path);
  return $PrintablePath; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to render the name of a container member, or any other text a file supplied, safely into a report line.
// / A control character is escaped, as it is in a path.
// / A name longer than 255 bytes is shortened, so one member cannot flood the report.
function printableName($name) {
  // / Set variables.
  $PrintableName = strval($name);
  if (strlen($PrintableName) > 255) $PrintableName = substr($PrintableName, 0, 252).'...';
  $PrintableName = printablePath($PrintableName);
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($name);
  return $PrintableName; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to read a 32 bit number as the unsigned number a file format means by it.
// / An interpreter whose integers are 32 bits wide hands the upper half of that range back as negative numbers.
function unsignedLong($signedLong) {
  // / Set variables.
  $UnsignedLong = $signedLong;
  if ($signedLong < 0) $UnsignedLong = $signedLong + 4294967296;
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($signedLong);
  return $UnsignedLong; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to read an unsigned 64 bit number from eight bytes.
// / The bytes are least significant first, as a zip writes them, unless the caller says otherwise, as a tar does.
// / It is read as two halves, so it reads the same on an interpreter whose integers are 32 bits wide.
// / A number too large for an integer comes back as a float & never wraps around to a negative one.
function readUnsigned64($eightBytes, $mostSignificantFirst) {
  // / Set variables.
  $Unsigned64 = 0;
  $halfWords = array();
  if (strlen($eightBytes) === 8) {
    $halfWords = unpack($mostSignificantFirst ? 'Nhigh/Nlow' : 'Vlow/Vhigh', $eightBytes);
    $Unsigned64 = (unsignedLong($halfWords['high']) * 4294967296) + unsignedLong($halfWords['low']); }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($halfWords, $eightBytes, $mostSignificantFirst);
  return $Unsigned64; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to say whether data expanded by more than a ratio allows.
// / Returns TRUE where the expanded size, divided by the stored size & rounded down, is more than the ratio.
// / The sizes are multiplied & never divided, so a size too large for an integer cannot stop the comparison.
function expansionExceeds($expandedSize, $storedSize, $ratioLimit) {
  // / Set variables.
  $ExpansionExceeds = FALSE;
  if ($storedSize > 0 && $expandedSize >= ($storedSize * ($ratioLimit + 1))) $ExpansionExceeds = TRUE;
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($expandedSize, $storedSize, $ratioLimit);
  return $ExpansionExceeds; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to write a number of bytes as a whole number, however large it is.
// / A number too large for an integer is a float, which would otherwise be written with an exponent.
function wholeNumberText($byteCount) {
  // / Set variables.
  $WholeNumberText = is_float($byteCount) ? number_format($byteCount, 0, '', '') : strval($byteCount);
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($byteCount);
  return $WholeNumberText; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to name the container format a file header describes.
// / A tar names itself at offset 257, so the header must be at least 263 bytes long.
function containerFormatOf($fileHeader) {
  // / Set variables.
  $ContainerFormat = '';
  if (substr($fileHeader, 0, 4) === "PK\x03\x04" or substr($fileHeader, 0, 4) === "PK\x05\x06") $ContainerFormat = 'zip';
  if (substr($fileHeader, 0, 3) === "\x1f\x8b\x08") $ContainerFormat = 'gzip';
  if (substr($fileHeader, 0, 3) === 'BZh') $ContainerFormat = 'bzip2';
  if (substr($fileHeader, 257, 5) === 'ustar') $ContainerFormat = 'tar';
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($fileHeader);
  return $ContainerFormat; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to name the image format a file header describes.
function imageFormatOf($fileHeader) {
  // / Set variables.
  $ImageFormat = '';
  if (substr($fileHeader, 0, 8) === "\x89PNG\r\n\x1a\n") $ImageFormat = 'png';
  if (substr($fileHeader, 0, 3) === "\xFF\xD8\xFF") $ImageFormat = 'jpeg';
  if (substr($fileHeader, 0, 6) === 'GIF87a' or substr($fileHeader, 0, 6) === 'GIF89a') $ImageFormat = 'gif';
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($fileHeader);
  return $ImageFormat; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to start the record that one file inspection keeps as it descends.
// / The budgets are shared by every nested container beneath one scanned file.
function newInspectionState($candidateTypes) {
  // / Set variables.
  $InspectionState = array('bytes' => 0, 'entries' => 0, 'halted' => FALSE, 'incomplete' => array(), 'types' => $candidateTypes);
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($candidateTypes);
  return $InspectionState; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to record a reason one file could not be inspected completely.
// / Each reason is kept once & the list is capped so the report line stays readable.
function noteIncomplete(&$inspection, $reason) {
  // / Set variables.
  $ReasonNoted = FALSE;
  if (!in_array($reason, $inspection['incomplete'], TRUE) && count($inspection['incomplete']) < 6) {
    $inspection['incomplete'][] = $reason;
    $ReasonNoted = TRUE; }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($reason);
  return $ReasonNoted; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to copy a block of data into a memory stream that can be sought & read.
// / Nested containers & embedded images are inspected through the same stream readers.
function openMemoryStream($streamData) {
  // / Set variables.
  $MemoryHandle = @fopen('php://memory', 'w+b');
  if ($MemoryHandle !== FALSE) {
    fwrite($MemoryHandle, $streamData);
    rewind($MemoryHandle); }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($streamData);
  return $MemoryHandle; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to inflate a compressed block held in memory, up to an output limit.
// / The input is fed in small pieces so one call can never produce an unbounded result.
// / Returns whether the data could be inflated, what it inflated to & whether the stream ended, in that order.
// / The stream has not ended where the limit stopped the work before the data did.
function inflateBounded($compressedData, $zlibEncoding, $outputLimit) {
  // / Set variables.
  $InflateWorked = FALSE;
  $InflatedData = '';
  $StreamEnded = FALSE;
  $inflateContext = FALSE;
  $inputOffset = 0;
  $inputLength = strlen($compressedData);
  $outputPiece = '';
  if (function_exists('inflate_init')) $inflateContext = @inflate_init($zlibEncoding);
  if ($inflateContext !== FALSE) {
    $InflateWorked = TRUE;
    while ($InflateWorked && !$StreamEnded && $inputOffset < $inputLength && strlen($InflatedData) <= $outputLimit) {
      redeclare($outputPiece, @inflate_add($inflateContext, substr($compressedData, $inputOffset, 4096)));
      $inputOffset = $inputOffset + 4096;
      if ($outputPiece === FALSE) $InflateWorked = FALSE;
      else {
        $InflatedData = $InflatedData.$outputPiece;
        if (inflate_get_status($inflateContext) === ZLIB_STREAM_END) $StreamEnded = TRUE; } }
    if (strlen($InflatedData) > $outputLimit) {
      $InflatedData = substr($InflatedData, 0, $outputLimit);
      $StreamEnded = FALSE; } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($inflateContext, $inputOffset, $inputLength, $outputPiece, $compressedData, $zlibEncoding, $outputLimit);
  return array($InflateWorked, $InflatedData, $StreamEnded); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to test a block of data against the embedded code signatures.
// / A signature shorter than six bytes is only tested against decoded text, because so short a run turns up by chance in compressed image data.
// / Returns the number of signatures that matched.
function matchEmbedSignatures($file, $haystack, $isDecodedText, &$fileDetections) {
  // / Set variables.
  global $Classifiers;
  $MatchesFound = 0;
  $signatureMatched = FALSE;
  if (isset($Classifiers['embeds'])) foreach ($Classifiers['embeds'] as $embedEntry) {
    // / Decoded text does not repeat what the raw bytes of the file already reported.
    redeclare($signatureMatched, ($isDecodedText && isset($fileDetections['Embedded.'.$embedEntry['name'].'|Structure Match|'])));
    foreach ($embedEntry['signatures'] as $signatureText) if (!$signatureMatched) {
      if ($isDecodedText or strlen($signatureText) >= 6) if (stripos($haystack, $signatureText) !== FALSE) {
        $signatureMatched = TRUE;
        recordDetection($file, 'Embedded.'.$embedEntry['name'], 'Structure Match', $signatureText, $embedEntry['confidence'], $fileDetections);
        $MatchesFound++; } } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($embedEntry, $signatureText, $signatureMatched, $haystack, $isDecodedText, $file);
  return $MatchesFound; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to work out how many bytes the image data of a png must inflate to.
// / Every scanline carries one filter byte.
// / An interlaced image is seven smaller images, each with scanlines of its own.
// / The sum is done in floating point, so a header that declares an enormous image cannot stop the arithmetic.
function pngDecodedSize($imageWidth, $imageHeight, $channelCount, $bitDepth, $interlaceMethod) {
  // / Set variables.
  $DecodedSize = 0;
  $bitsPerPixel = $channelCount * $bitDepth;
  $passIndex = 0;
  $passWidth = $passHeight = 0;
  $startX = array(0, 4, 0, 2, 0, 1, 0);
  $startY = array(0, 0, 4, 0, 2, 0, 1);
  $stepX = array(8, 8, 4, 4, 2, 2, 1);
  $stepY = array(8, 8, 8, 4, 4, 2, 2);
  if ($interlaceMethod !== 1) $DecodedSize = (floor((($imageWidth * $bitsPerPixel) + 7) / 8) + 1) * $imageHeight;
  else {
    while ($passIndex < 7) {
      $passWidth = floor(($imageWidth - $startX[$passIndex] + $stepX[$passIndex] - 1) / $stepX[$passIndex]);
      $passHeight = floor(($imageHeight - $startY[$passIndex] + $stepY[$passIndex] - 1) / $stepY[$passIndex]);
      if ($passWidth > 0 && $passHeight > 0) $DecodedSize = $DecodedSize + ((floor((($passWidth * $bitsPerPixel) + 7) / 8) + 1) * $passHeight);
      $passIndex++; } }
  // / A size that an integer can hold is handed back as one, so it is reported as a whole number.
  if ($DecodedSize < PHP_INT_MAX) $DecodedSize = intval($DecodedSize);
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($bitsPerPixel, $passIndex, $passWidth, $passHeight, $startX, $startY, $stepX, $stepY, $imageWidth, $imageHeight, $channelCount, $bitDepth, $interlaceMethod);
  return $DecodedSize; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to inspect the text a png chunk carries, inflating it first when needed.
// / A compressed chunk is inflated up to the chunk limit & no further.
function inspectPngTextChunk($file, $chunkType, $chunkData, &$fileDetections) {
  // / Set variables.
  global $MaxImageChunkSize, $MemberContext;
  $ChunkInspected = FALSE;
  $outerContext = $MemberContext;
  $separatorPosition = strpos($chunkData, "\0");
  $chunkText = $compressedPart = '';
  $isCompressed = FALSE;
  $inflateWorked = $streamEnded = FALSE;
  $fieldEnd = 0;
  $MemberContext = ($outerContext !== '' ? $outerContext.' > ' : '').$chunkType.' chunk';
  if ($separatorPosition !== FALSE) {
    $ChunkInspected = TRUE;
    if ($chunkType === 'tEXt') $chunkText = substr($chunkData, $separatorPosition + 1);
    if ($chunkType === 'zTXt' or $chunkType === 'iCCP') {
      $isCompressed = TRUE;
      $compressedPart = substr($chunkData, $separatorPosition + 2); }
    if ($chunkType === 'iTXt') {
      // / The text follows a compression flag, a method, a language tag & a translated keyword.
      $isCompressed = (substr($chunkData, $separatorPosition + 1, 1) === "\x01");
      $fieldEnd = strpos($chunkData, "\0", $separatorPosition + 3);
      if ($fieldEnd !== FALSE) $fieldEnd = strpos($chunkData, "\0", $fieldEnd + 1);
      if ($fieldEnd === FALSE) $ChunkInspected = FALSE;
      else {
        if ($isCompressed) $compressedPart = substr($chunkData, $fieldEnd + 1);
        else $chunkText = substr($chunkData, $fieldEnd + 1); } }
    if ($ChunkInspected && $isCompressed) {
      list($inflateWorked, $chunkText, $streamEnded) = inflateBounded($compressedPart, ZLIB_ENCODING_DEFLATE, $MaxImageChunkSize);
      // / A chunk still producing output at the limit is a decompression bomb.
      if ($inflateWorked && !$streamEnded && strlen($chunkText) >= $MaxImageChunkSize) recordDetection($file, 'Bomb.PNG.ChunkExpansion', 'Structure Match', 'expands past '.$MaxImageChunkSize.' bytes from '.strlen($compressedPart), 'high', $fileDetections); }
    // / A colour profile is binary, so only text chunks are searched for code.
    if ($ChunkInspected && $chunkType !== 'iCCP' && $chunkText !== '') matchEmbedSignatures($file, $chunkText, TRUE, $fileDetections); }
  $MemberContext = $outerContext;
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($outerContext, $separatorPosition, $chunkText, $compressedPart, $isCompressed, $inflateWorked, $streamEnded, $fieldEnd, $chunkType, $chunkData, $file);
  return $ChunkInspected; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to walk the chunks of a png & report what is wrong with its structure.
// / The header decides how large the decoded image may be.
// / The image data is inflated to be counted & is never kept, so the walk holds no more than one small buffer.
// / Returns TRUE when the chunks could be walked.
function inspectPngStream($file, $handle, $sourceSize, &$fileDetections) {
  // / Set variables.
  global $MaxImagePixels, $MaxImageChunkSize;
  $PngInspected = FALSE;
  $chunkLimit = 1048576;
  $chunkCount = 0;
  $position = 8;
  $keepWalking = TRUE;
  $sawHeader = $sawData = $sawEnd = $dataEnded = $overrunReported = FALSE;
  $malformedReason = '';
  $chunkHeader = $chunkType = $chunkData = $dataPiece = $storedCrc = $inflatedPiece = '';
  $chunkLength = $bytesLeft = $pieceLength = 0;
  $chunkFields = $headerFields = array();
  $crcContext = NULL;
  $inflateContext = FALSE;
  $keepData = FALSE;
  $imageWidth = $imageHeight = $decodedSize = $inflatedTotal = 0;
  $channelCounts = array(0 => 1, 2 => 3, 3 => 1, 4 => 2, 6 => 4);
  $validDepths = array(0 => array(1, 2, 4, 8, 16), 2 => array(8, 16), 3 => array(1, 2, 4, 8), 4 => array(8, 16), 6 => array(8, 16));
  $textChunks = array('tEXt', 'zTXt', 'iTXt', 'iCCP');
  $criticalChunks = array('IHDR', 'PLTE', 'IDAT', 'IEND');
  if ($handle !== FALSE && @fseek($handle, 8) === 0) $PngInspected = TRUE;
  while ($PngInspected && $keepWalking) {
    redeclare($chunkHeader, strval(fread($handle, 8)));
    if (strlen($chunkHeader) < 8) $keepWalking = FALSE;
    else {
      $chunkCount++;
      redeclare($chunkFields, unpack('Nlength', $chunkHeader));
      redeclare($chunkLength, unsignedLong($chunkFields['length']));
      redeclare($chunkType, substr($chunkHeader, 4, 4));
      $position = $position + 8;
      // / A chunk is named with four letters, & what is named otherwise ends the walk.
      if (!preg_match('/^[A-Za-z]{4}$/', $chunkType)) {
        if ($malformedReason === '') $malformedReason = 'a chunk is named with something other than four letters';
        $keepWalking = FALSE; }
      // / A chunk that claims more bytes than the file holds ends the walk.
      if ($keepWalking && ($position + $chunkLength + 4) > $sourceSize) {
        if ($malformedReason === '') $malformedReason = 'a chunk runs past the end of the file';
        $keepWalking = FALSE; }
      if ($keepWalking && $chunkCount > $chunkLimit) {
        if ($malformedReason === '') $malformedReason = 'more than '.$chunkLimit.' chunks';
        $keepWalking = FALSE; }
      if ($keepWalking) {
        if ($chunkCount === 1 && $chunkType !== 'IHDR' && $malformedReason === '') $malformedReason = 'the first chunk is not the header';
        if ($chunkType === 'IHDR' && ($sawHeader or $chunkLength !== 13) && $malformedReason === '') $malformedReason = 'a damaged or repeated header chunk';
        // / A decoder must refuse a critical chunk it does not know.
        if (preg_match('/^[A-Z]/', $chunkType) === 1 && !in_array($chunkType, $criticalChunks, TRUE) && $malformedReason === '') $malformedReason = 'an unknown critical chunk named '.$chunkType;
        redeclare($keepData, ($chunkType === 'IHDR' or in_array($chunkType, $textChunks, TRUE)) && $chunkLength <= $MaxImageChunkSize);
        if (in_array($chunkType, $textChunks, TRUE) && !$keepData && $malformedReason === '') $malformedReason = 'an oversized '.$chunkType.' chunk of '.$chunkLength.' bytes';
        redeclare($chunkData, '');
        redeclare($crcContext, hash_init('crc32b'));
        hash_update($crcContext, $chunkType);
        redeclare($bytesLeft, $chunkLength);
        while ($bytesLeft > 0) {
          redeclare($pieceLength, min($bytesLeft, 8192));
          redeclare($dataPiece, strval(fread($handle, $pieceLength)));
          if (strlen($dataPiece) < 1) break;
          $bytesLeft = $bytesLeft - strlen($dataPiece);
          hash_update($crcContext, $dataPiece);
          if ($keepData) $chunkData = $chunkData.$dataPiece;
          // / Inflate the image data only to count it, & stop counting once it has overrun.
          if ($chunkType === 'IDAT' && $inflateContext !== FALSE && !$dataEnded && !$overrunReported) {
            redeclare($inflatedPiece, @inflate_add($inflateContext, $dataPiece));
            if ($inflatedPiece === FALSE) $dataEnded = TRUE;
            else {
              $inflatedTotal = $inflatedTotal + strlen($inflatedPiece);
              if (inflate_get_status($inflateContext) === ZLIB_STREAM_END) $dataEnded = TRUE;
              if ($inflatedTotal > ($decodedSize + $MaxImageChunkSize)) {
                $overrunReported = TRUE;
                recordDetection($file, 'Bomb.PNG.DataOverrun', 'Structure Match', 'the image data inflates past '.$inflatedTotal.' bytes & the header declares '.wholeNumberText($decodedSize), 'high', $fileDetections); } } } }
        redeclare($storedCrc, strval(fread($handle, 4)));
        $position = $position + $chunkLength + 4;
        if (($bytesLeft > 0 or bin2hex($storedCrc) !== hash_final($crcContext)) && $malformedReason === '') $malformedReason = 'the '.$chunkType.' chunk fails its checksum';
        if ($chunkType === 'IHDR' && !$sawHeader && strlen($chunkData) === 13) {
          $sawHeader = TRUE;
          $headerFields = unpack('Nwidth/Nheight/Cdepth/Ccolour/Ccompression/Cfilter/Cinterlace', $chunkData);
          $imageWidth = unsignedLong($headerFields['width']);
          $imageHeight = unsignedLong($headerFields['height']);
          if ($imageWidth < 1 or $imageHeight < 1 or !isset($validDepths[$headerFields['colour']]) or !in_array($headerFields['depth'], $validDepths[$headerFields['colour']], TRUE) or $headerFields['interlace'] > 1) {
            if ($malformedReason === '') $malformedReason = 'the header declares an impossible image'; }
          else {
            $decodedSize = pngDecodedSize($imageWidth, $imageHeight, $channelCounts[$headerFields['colour']], $headerFields['depth'], $headerFields['interlace']);
            // / An image past the pixel limit is refused, whatever it holds.
            if (($imageWidth * $imageHeight) > $MaxImagePixels) recordDetection($file, 'Bomb.PNG.PixelFlood', 'Structure Match', wholeNumberText($imageWidth).' by '.wholeNumberText($imageHeight).' pixels decodes to '.wholeNumberText($decodedSize).' bytes from a file of '.$sourceSize, 'high', $fileDetections);
            else { if (function_exists('inflate_init')) $inflateContext = @inflate_init(ZLIB_ENCODING_DEFLATE); } } }
        if ($chunkType === 'IDAT') $sawData = TRUE;
        if ($keepData && $chunkType !== 'IHDR') inspectPngTextChunk($file, $chunkType, $chunkData, $fileDetections);
        if ($chunkType === 'IEND') {
          $sawEnd = TRUE;
          $keepWalking = FALSE; } } } }
  if ($PngInspected) {
    if ($malformedReason === '' && (!$sawHeader or !$sawData or !$sawEnd)) $malformedReason = 'the header, the image data or the end chunk is missing';
    // / Image data that overruns its header by a little is damage rather than a bomb.
    if ($malformedReason === '' && !$overrunReported && $sawHeader && $inflatedTotal > $decodedSize) $malformedReason = 'the image data holds '.($inflatedTotal - $decodedSize).' bytes more than the header declares';
    if ($malformedReason !== '') recordDetection($file, 'Malformed.PNG', 'Structure Match', $malformedReason, 'medium', $fileDetections);
    // / Anything after the end chunk is invisible to an image viewer & readable by everything else.
    if ($sawEnd && $position < $sourceSize) recordDetection($file, 'Polyglot.PNG.TrailingData', 'Structure Match', ($sourceSize - $position).' bytes follow the end of the image', 'medium', $fileDetections); }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($chunkLimit, $chunkCount, $position, $keepWalking, $sawHeader, $sawData, $sawEnd, $dataEnded, $overrunReported, $malformedReason, $chunkHeader, $chunkType, $chunkData, $dataPiece, $storedCrc, $inflatedPiece, $chunkLength, $bytesLeft, $pieceLength, $chunkFields, $headerFields, $crcContext, $inflateContext, $keepData, $imageWidth, $imageHeight, $decodedSize, $inflatedTotal, $channelCounts, $validDepths, $textChunks, $criticalChunks, $file, $handle, $sourceSize);
  return $PngInspected; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to locate the central directory of a zip archive.
// / The directory record sits at the end of the file, behind an optional comment.
// / The base is the number of bytes that sit in front of the archive, which is above zero where an archive was appended to another file.
// / A number too large for the file it came from is refused, so a directory that lies about itself is not followed.
function findZipDirectory($handle, $sourceSize) {
  // / Set variables.
  $Directory = array('found' => FALSE, 'entries' => 0, 'offset' => 0, 'size' => 0, 'base' => 0);
  $tailLength = min($sourceSize, 65557);
  $tailStart = $sourceSize - $tailLength;
  $tailData = $locatorData = $wideData = '';
  $recordPosition = FALSE;
  $recordFields = array();
  $attemptsLeft = 16;
  $recordStart = $directoryEnd = $widePosition = 0;
  if ($tailLength >= 22 && @fseek($handle, $tailStart) === 0) {
    $tailData = strval(fread($handle, $tailLength));
    $recordPosition = strrpos($tailData, "PK\x05\x06");
    // / A comment may carry a false record, so each candidate must fit the file it is in.
    while ($recordPosition !== FALSE && !$Directory['found'] && $attemptsLeft > 0) {
      $attemptsLeft--;
      if (($recordPosition + 22) <= $tailLength) {
        redeclare($recordFields, unpack('vdisk/vstartDisk/vdiskEntries/ventries/Vsize/Voffset/vcomment', substr($tailData, $recordPosition + 4, 18)));
        redeclare($recordStart, $tailStart + $recordPosition);
        redeclare($directoryEnd, $recordStart);
        $Directory['entries'] = $recordFields['entries'];
        $Directory['size'] = unsignedLong($recordFields['size']);
        $Directory['offset'] = unsignedLong($recordFields['offset']);
        // / A saturated field means the real values sit in the zip64 record instead.
        if ($Directory['entries'] === 0xFFFF or $Directory['size'] === 0xFFFFFFFF or $Directory['offset'] === 0xFFFFFFFF) {
          if ($recordStart >= 20 && @fseek($handle, $recordStart - 20) === 0) {
            redeclare($locatorData, strval(fread($handle, 20)));
            if (substr($locatorData, 0, 4) === "PK\x06\x07") {
              redeclare($widePosition, readUnsigned64(substr($locatorData, 8, 8), FALSE));
              if ($widePosition < $sourceSize && @fseek($handle, intval($widePosition)) === 0) {
                redeclare($wideData, strval(fread($handle, 56)));
                if (strlen($wideData) === 56 && substr($wideData, 0, 4) === "PK\x06\x06") {
                  redeclare($directoryEnd, intval($widePosition));
                  $Directory['entries'] = readUnsigned64(substr($wideData, 32, 8), FALSE);
                  $Directory['size'] = readUnsigned64(substr($wideData, 40, 8), FALSE);
                  $Directory['offset'] = readUnsigned64(substr($wideData, 48, 8), FALSE); } } } } }
        // / Nothing a directory declares may be larger than the file it sits in.
        if ($Directory['entries'] <= $sourceSize && $Directory['size'] <= $sourceSize && $Directory['offset'] <= $sourceSize) {
          $Directory['entries'] = intval($Directory['entries']);
          $Directory['size'] = intval($Directory['size']);
          $Directory['offset'] = intval($Directory['offset']);
          $Directory['base'] = $directoryEnd - $Directory['size'] - $Directory['offset'];
          if ($Directory['base'] >= 0) {
            // / An empty archive has no directory to check, & any other must open with an entry.
            if ($Directory['entries'] === 0) $Directory['found'] = ($Directory['size'] === 0);
            else {
              if (@fseek($handle, $Directory['base'] + $Directory['offset']) === 0) if (strval(fread($handle, 4)) === "PK\x01\x02") $Directory['found'] = TRUE; } } } }
      if (!$Directory['found']) $recordPosition = ($recordPosition > 0) ? strrpos(substr($tailData, 0, $recordPosition), "PK\x05\x06") : FALSE; } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($tailLength, $tailStart, $tailData, $locatorData, $wideData, $recordPosition, $recordFields, $attemptsLeft, $recordStart, $directoryEnd, $widePosition, $handle, $sourceSize);
  return $Directory; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to read the entries a zip central directory lists.
// / Reading stops at the entry budget, so the list may be shorter than the directory.
// / Returns whether the directory could be read & the entries, in that order.
function readZipDirectory($handle, $directory) {
  // / Set variables.
  global $MaxArchiveEntries;
  $DirectoryRead = FALSE;
  $EntryList = array();
  $entryHeader = $entryName = $extraData = $wideName = '';
  $entryFields = $extraFields = $entryRecord = array();
  $entryIndex = $extraOffset = $wideOffset = $wideEnd = 0;
  if (@fseek($handle, $directory['base'] + $directory['offset']) === 0) $DirectoryRead = TRUE;
  while ($DirectoryRead && $entryIndex < $directory['entries'] && count($EntryList) < $MaxArchiveEntries) {
    redeclare($entryHeader, strval(fread($handle, 46)));
    if (strlen($entryHeader) < 46 or substr($entryHeader, 0, 4) !== "PK\x01\x02") $DirectoryRead = FALSE;
    else {
      redeclare($entryFields, unpack('vmade/vneeded/vflags/vmethod/vtime/vdate/Vcrc/Vcompressed/Vsize/vnameLength/vextraLength/vcommentLength/vdisk/vinternal/Vexternal/Voffset', substr($entryHeader, 4)));
      redeclare($entryName, $entryFields['nameLength'] > 0 ? strval(fread($handle, $entryFields['nameLength'])) : '');
      redeclare($extraData, $entryFields['extraLength'] > 0 ? strval(fread($handle, $entryFields['extraLength'])) : '');
      if ($entryFields['commentLength'] > 0) fread($handle, $entryFields['commentLength']);
      redeclare($entryRecord, array('name' => $entryName, 'flags' => $entryFields['flags'], 'method' => $entryFields['method'], 'compressed' => unsignedLong($entryFields['compressed']), 'size' => unsignedLong($entryFields['size']), 'offset' => unsignedLong($entryFields['offset'])));
      // / A saturated field is carried in the zip64 extra field, in a fixed order.
      redeclare($extraOffset, 0);
      while (($extraOffset + 4) <= strlen($extraData)) {
        redeclare($extraFields, unpack('vid/vlength', substr($extraData, $extraOffset, 4)));
        if ($extraFields['id'] === 1) {
          redeclare($wideOffset, $extraOffset + 4);
          redeclare($wideEnd, min(strlen($extraData), $extraOffset + 4 + $extraFields['length']));
          foreach (array('size', 'compressed', 'offset') as $wideName) if ($entryRecord[$wideName] === 0xFFFFFFFF && ($wideOffset + 8) <= $wideEnd) {
            $entryRecord[$wideName] = readUnsigned64(substr($extraData, $wideOffset, 8), FALSE);
            $wideOffset = $wideOffset + 8; } }
        $extraOffset = $extraOffset + 4 + $extraFields['length']; }
      $entryRecord['offset'] = $entryRecord['offset'] + $directory['base'];
      $EntryList[] = $entryRecord;
      $entryIndex++; } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($entryHeader, $entryName, $extraData, $wideName, $entryFields, $extraFields, $entryRecord, $entryIndex, $extraOffset, $wideOffset, $wideEnd, $directory, $handle);
  return array($DirectoryRead, $EntryList); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to read one member of a zip archive, up to a limit.
// / The limit bounds the output whatever the archive declares, so a dishonest header cannot cause a large read.
// / Returns the data, whether all of the member was read, a note & the number of stored bytes consumed, in that order.
// / The note is empty, or one of damaged, mismatch, encrypted & unsupported.
function readZipMember($handle, $sourceSize, $entryRecord, $readLimit) {
  // / Set variables.
  $MemberData = '';
  $MemberComplete = FALSE;
  $MemberNote = '';
  $ConsumedBytes = 0;
  $localHeader = $localName = $dataPiece = $outputPiece = '';
  $localFields = array();
  $bytesLeft = 0;
  $inflateContext = FALSE;
  $keepReading = TRUE;
  if (($entryRecord['offset'] + 30) > $sourceSize or @fseek($handle, intval($entryRecord['offset'])) !== 0) $MemberNote = 'damaged';
  else {
    $localHeader = strval(fread($handle, 30));
    if (strlen($localHeader) < 30 or substr($localHeader, 0, 4) !== "PK\x03\x04") $MemberNote = 'damaged';
    else {
      $localFields = unpack('vneeded/vflags/vmethod/vtime/vdate/Vcrc/Vcompressed/Vsize/vnameLength/vextraLength', substr($localHeader, 4));
      $localName = $localFields['nameLength'] > 0 ? strval(fread($handle, $localFields['nameLength'])) : '';
      if ($localFields['extraLength'] > 0) fread($handle, $localFields['extraLength']);
      // / The two headers describing one member must agree, or two readers see two files.
      if ($localName !== $entryRecord['name'] or $localFields['method'] !== $entryRecord['method']) $MemberNote = 'mismatch';
      if (($entryRecord['flags'] & 1) === 1) $MemberNote = 'encrypted';
      $bytesLeft = intval(min($entryRecord['compressed'], max(0, $sourceSize - ftell($handle))));
      // / A member that stores nothing is read in full by reading nothing.
      if ($MemberNote !== 'encrypted' && $bytesLeft < 1 && ($entryRecord['method'] === 0 or $entryRecord['method'] === 8)) $MemberComplete = TRUE;
      if ($MemberNote !== 'encrypted' && $bytesLeft > 0 && $entryRecord['method'] === 0) {
        $MemberData = strval(fread($handle, min($bytesLeft, $readLimit)));
        $ConsumedBytes = strlen($MemberData);
        $MemberComplete = ($bytesLeft <= $readLimit); }
      if ($MemberNote !== 'encrypted' && $bytesLeft > 0 && $entryRecord['method'] === 8) {
        if (function_exists('inflate_init')) $inflateContext = @inflate_init(ZLIB_ENCODING_RAW);
        if ($inflateContext === FALSE) $MemberNote = 'unsupported';
        while ($inflateContext !== FALSE && $keepReading && $bytesLeft > 0) {
          redeclare($dataPiece, strval(fread($handle, min($bytesLeft, 8192))));
          if ($dataPiece === '') $keepReading = FALSE;
          else {
            $bytesLeft = $bytesLeft - strlen($dataPiece);
            $ConsumedBytes = $ConsumedBytes + strlen($dataPiece);
            redeclare($outputPiece, @inflate_add($inflateContext, $dataPiece));
            if ($outputPiece === FALSE) {
              $MemberNote = 'damaged';
              $keepReading = FALSE; }
            else {
              $MemberData = $MemberData.$outputPiece;
              if (inflate_get_status($inflateContext) === ZLIB_STREAM_END) {
                $MemberComplete = TRUE;
                $keepReading = FALSE; }
              if (strlen($MemberData) > $readLimit) {
                $MemberData = substr($MemberData, 0, $readLimit);
                $MemberComplete = FALSE;
                $keepReading = FALSE; } } } }
        // / A stream that runs out of stored bytes before it ends, short of the limit, was cut short.
        if ($inflateContext !== FALSE && !$MemberComplete && $MemberNote === '' && strlen($MemberData) < $readLimit) $MemberNote = 'damaged'; }
      if ($MemberNote !== 'encrypted' && $entryRecord['method'] !== 0 && $entryRecord['method'] !== 8) $MemberNote = 'unsupported'; } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($localHeader, $localName, $dataPiece, $outputPiece, $localFields, $bytesLeft, $inflateContext, $keepReading, $entryRecord, $readLimit, $handle, $sourceSize);
  return array($MemberData, $MemberComplete, $MemberNote, $ConsumedBytes); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to claim the budget for reading one more container member.
// / Returns the number of bytes that may be read, or zero once a budget is spent.
function claimMemberBudget(&$inspection) {
  // / Set variables.
  global $MaxArchiveEntries, $MaxArchiveEntrySize, $MaxArchiveTotalSize;
  $ReadLimit = 0;
  $inspection['entries']++;
  if ($inspection['entries'] > $MaxArchiveEntries) {
    noteIncomplete($inspection, 'the entry budget of '.$MaxArchiveEntries.' was reached');
    $inspection['halted'] = TRUE; }
  else {
    $ReadLimit = min($MaxArchiveEntrySize, $MaxArchiveTotalSize - $inspection['bytes']);
    if ($ReadLimit < 1) {
      $ReadLimit = 0;
      noteIncomplete($inspection, 'the total decompression budget of '.$MaxArchiveTotalSize.' bytes was reached');
      $inspection['halted'] = TRUE; } }
  return $ReadLimit; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to inspect one member that was taken out of a container.
// / The member is matched against the definitions, classified, checked as an image & opened in turn where it is a container itself.
function inspectMemberData($file, $memberPath, $memberName, $memberData, $memberIsComplete, $depth, &$fileDetections, &$handlerHits, &$inspection) {
  // / Set variables.
  global $Defs, $Classifiers, $ClassifiersLoaded, $ClassifyContent, $InspectImages, $MaxArchiveDepth, $MemberContext;
  $MemberInspected = TRUE;
  $outerContext = $MemberContext;
  $memberHeader = substr($memberData, 0, 512);
  $containerFormat = containerFormatOf($memberHeader);
  $imageFormat = imageFormatOf($memberHeader);
  $scopeTypes = array();
  $memoryHandle = FALSE;
  $MemberContext = $memberPath;
  if ($ClassifiersLoaded) $scopeTypes = typesForExtension($Classifiers, fileExtensionOf($memberName));
  // / A hash only means something when the whole member was read.
  if ($memberIsComplete) matchHashSignatures($file, hash('md5', $memberData), hash('sha1', $memberData), hash('sha256', $memberData), $Defs, $fileDetections);
  matchContentIndicators($file, $memberData, $Defs, $scopeTypes, $fileDetections);
  foreach ($Defs['name'] as $definitionRecord) if (stripos($memberName, $definitionRecord['value']) !== FALSE) recordDetection($file, $definitionRecord['name'], 'Name Match', $definitionRecord['value'], $definitionRecord['confidence'], $fileDetections);
  // / A member that is a container reports its handlers through its own members instead.
  if ($ClassifyContent && $ClassifiersLoaded && $containerFormat === '' && archiveEntryIsInteresting($memberName)) matchHandlerTokens($memberData, $Classifiers, $inspection['types'], $handlerHits, printableName($memberPath));
  if ($InspectImages && $imageFormat !== '') {
    matchEmbedSignatures($file, $memberData, FALSE, $fileDetections);
    // / A member that was cut short would only report the cut as damage.
    if ($imageFormat === 'png' && $memberIsComplete) {
      $memoryHandle = openMemoryStream($memberData);
      if ($memoryHandle !== FALSE) {
        inspectPngStream($file, $memoryHandle, strlen($memberData), $fileDetections);
        fclose($memoryHandle); } } }
  if ($containerFormat !== '') {
    if (($depth + 1) > $MaxArchiveDepth) {
      recordDetection($file, 'Bomb.Archive.NestingDepth', 'Structure Match', 'containers are nested more than '.$MaxArchiveDepth.' deep', 'high', $fileDetections);
      $inspection['halted'] = TRUE; }
    else {
      if (!$memberIsComplete) noteIncomplete($inspection, 'a nested archive larger than the entry budget was not opened');
      else {
        redeclare($memoryHandle, openMemoryStream($memberData));
        if ($memoryHandle !== FALSE) {
          inspectContainerHandle($file, $memoryHandle, strlen($memberData), $containerFormat, $memberPath, $depth + 1, $fileDetections, $handlerHits, $inspection);
          fclose($memoryHandle); } } } }
  $MemberContext = $outerContext;
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($outerContext, $memberHeader, $containerFormat, $imageFormat, $scopeTypes, $memoryHandle, $definitionRecord, $memberPath, $memberName, $memberData, $memberIsComplete, $depth, $file);
  return $MemberInspected; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to inspect a zip archive through a stream handle.
// / The directory is judged before any member is read.
// / Members that share stored bytes & a directory that declares an absurd total are both bombs, & either stops the inspection.
// / Returns TRUE when the directory could be found.
function inspectZipHandle($file, $handle, $sourceSize, $containerPath, $depth, &$fileDetections, &$handlerHits, &$inspection) {
  // / Set variables.
  global $MaxArchiveEntries, $MaxArchiveEntrySize, $MaxArchiveTotalSize, $MaxArchiveCompressionRatio, $MemberContext;
  $ZipInspected = FALSE;
  $outerContext = $MemberContext;
  $directory = findZipDirectory($handle, $sourceSize);
  $directoryRead = FALSE;
  $entryList = $extentList = $entryRecord = $extentRecord = array();
  $declaredTotal = $previousEnd = $readLimit = $consumedBytes = 0;
  $entriesOverlap = $memberComplete = FALSE;
  $memberData = $memberNote = $memberPath = '';
  $MemberContext = $containerPath;
  if (!$directory['found']) noteIncomplete($inspection, 'a zip directory could not be read');
  else {
    list($directoryRead, $entryList) = readZipDirectory($handle, $directory);
    $ZipInspected = TRUE;
    if (!$directoryRead) noteIncomplete($inspection, 'a zip directory is damaged');
    // / A directory that lists more members than the budget allows is read as far as the budget goes.
    if ($directory['entries'] > count($entryList) && $directoryRead) noteIncomplete($inspection, 'an archive lists '.$directory['entries'].' entries, which is more than the entry budget of '.$MaxArchiveEntries);
    foreach ($entryList as $entryRecord) {
      $declaredTotal = $declaredTotal + $entryRecord['size'];
      if ($entryRecord['compressed'] > 0) $extentList[] = array($entryRecord['offset'], $entryRecord['offset'] + 30 + strlen($entryRecord['name']) + $entryRecord['compressed']); }
    usort($extentList, function($firstExtent, $secondExtent) { return $firstExtent[0] <=> $secondExtent[0]; });
    foreach ($extentList as $extentRecord) {
      if ($extentRecord[0] < $previousEnd) $entriesOverlap = TRUE;
      $previousEnd = max($previousEnd, $extentRecord[1]); }
    if ($entriesOverlap) {
      recordDetection($file, 'Bomb.Archive.OverlappingEntries', 'Structure Match', 'members of the archive share the same stored bytes', 'high', $fileDetections);
      $inspection['halted'] = TRUE; }
    if (!$inspection['halted'] && $declaredTotal > $MaxArchiveTotalSize && expansionExceeds($declaredTotal, $sourceSize, $MaxArchiveCompressionRatio)) {
      recordDetection($file, 'Bomb.Archive.TotalExpansion', 'Structure Match', countWithNoun(count($entryList), 'member declares', 'members declare').' '.wholeNumberText($declaredTotal).' bytes from an archive of '.$sourceSize, 'high', $fileDetections);
      $inspection['halted'] = TRUE; }
    foreach ($entryList as $entryRecord) if (!$inspection['halted'] && substr($entryRecord['name'], -1) !== '/') {
      redeclare($memberPath, ($containerPath !== '' ? $containerPath.' > ' : '').$entryRecord['name']);
      $MemberContext = $memberPath;
      // / A member that declares a huge expansion is refused before any of it is read.
      if ($entryRecord['size'] > $MaxArchiveEntrySize && expansionExceeds($entryRecord['size'], $entryRecord['compressed'], $MaxArchiveCompressionRatio)) {
        recordDetection($file, 'Bomb.Archive.CompressionRatio', 'Structure Match', 'declares '.wholeNumberText($entryRecord['size']).' bytes from '.wholeNumberText($entryRecord['compressed']), 'high', $fileDetections);
        $inspection['halted'] = TRUE; }
      else {
        redeclare($readLimit, claimMemberBudget($inspection));
        if ($readLimit > 0) {
          list($memberData, $memberComplete, $memberNote, $consumedBytes) = readZipMember($handle, $sourceSize, $entryRecord, $readLimit);
          if ($memberNote === 'encrypted') noteIncomplete($inspection, 'an encrypted member cannot be inspected');
          if ($memberNote === 'unsupported') noteIncomplete($inspection, 'a member uses a compression method that is not read');
          if ($memberNote === 'damaged') noteIncomplete($inspection, 'a damaged member could not be read');
          if ($memberNote === 'mismatch') recordDetection($file, 'Malformed.Archive.HeaderMismatch', 'Structure Match', 'the two headers describing the member disagree', 'medium', $fileDetections);
          // / A member that fills the limit either lied about its size or is simply larger than the budget.
          if (!$memberComplete && strlen($memberData) >= $readLimit) {
            if (expansionExceeds(strlen($memberData), $consumedBytes, $MaxArchiveCompressionRatio)) {
              recordDetection($file, 'Bomb.Archive.CompressionRatio', 'Structure Match', 'expands past '.strlen($memberData).' bytes from '.$consumedBytes, 'high', $fileDetections);
              $inspection['halted'] = TRUE; }
            else noteIncomplete($inspection, 'a member larger than the entry budget was inspected in part'); }
          $inspection['bytes'] = $inspection['bytes'] + strlen($memberData);
          if ($memberData !== '' && !$inspection['halted']) inspectMemberData($file, $memberPath, basename(str_replace('\\', '/', $entryRecord['name'])), $memberData, $memberComplete, $depth, $fileDetections, $handlerHits, $inspection); } } } }
  $MemberContext = $outerContext;
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($outerContext, $directory, $directoryRead, $entryList, $extentList, $entryRecord, $extentRecord, $declaredTotal, $previousEnd, $readLimit, $consumedBytes, $entriesOverlap, $memberComplete, $memberData, $memberNote, $memberPath, $file, $handle, $sourceSize, $containerPath, $depth);
  return $ZipInspected; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to inspect a gzip stream through a stream handle.
// / The stream is inflated up to the member budget, then inspected as one member.
function inspectGzipHandle($file, $handle, $sourceSize, $containerPath, $depth, &$fileDetections, &$handlerHits, &$inspection) {
  // / Set variables.
  global $MaxArchiveCompressionRatio, $MemberContext;
  $GzipInspected = FALSE;
  $outerContext = $MemberContext;
  $inflateContext = FALSE;
  $streamData = $dataPiece = $outputPiece = '';
  $streamComplete = FALSE;
  $keepReading = TRUE;
  $consumedBytes = 0;
  $readLimit = claimMemberBudget($inspection);
  $streamPath = ($containerPath !== '' ? $containerPath.' > ' : '').'gzip stream';
  $MemberContext = $streamPath;
  if (function_exists('inflate_init')) $inflateContext = @inflate_init(ZLIB_ENCODING_GZIP);
  if ($inflateContext === FALSE) noteIncomplete($inspection, 'a gzip stream cannot be read without the zlib extension');
  if ($inflateContext !== FALSE && $readLimit > 0 && @fseek($handle, 0) === 0) {
    $GzipInspected = TRUE;
    while ($keepReading && !feof($handle)) {
      redeclare($dataPiece, strval(fread($handle, 8192)));
      if ($dataPiece === '') $keepReading = FALSE;
      else {
        $consumedBytes = $consumedBytes + strlen($dataPiece);
        redeclare($outputPiece, @inflate_add($inflateContext, $dataPiece));
        if ($outputPiece === FALSE) $keepReading = FALSE;
        else {
          $streamData = $streamData.$outputPiece;
          if (inflate_get_status($inflateContext) === ZLIB_STREAM_END) {
            $streamComplete = TRUE;
            $keepReading = FALSE; }
          if (strlen($streamData) > $readLimit) {
            $streamData = substr($streamData, 0, $readLimit);
            $streamComplete = FALSE;
            $keepReading = FALSE; } } } }
    if ($streamData !== '' && !$streamComplete) {
      if (strlen($streamData) >= $readLimit && expansionExceeds(strlen($streamData), $consumedBytes, $MaxArchiveCompressionRatio)) {
        recordDetection($file, 'Bomb.Archive.CompressionRatio', 'Structure Match', 'expands past '.strlen($streamData).' bytes from '.$consumedBytes, 'high', $fileDetections);
        $inspection['halted'] = TRUE; }
      else noteIncomplete($inspection, 'a compressed stream larger than the entry budget was inspected in part'); }
    $inspection['bytes'] = $inspection['bytes'] + strlen($streamData);
    if ($streamData !== '' && !$inspection['halted']) inspectMemberData($file, $streamPath, 'gzip stream', $streamData, $streamComplete, $depth, $fileDetections, $handlerHits, $inspection); }
  $MemberContext = $outerContext;
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($outerContext, $inflateContext, $streamData, $dataPiece, $outputPiece, $streamComplete, $keepReading, $consumedBytes, $readLimit, $streamPath, $file, $handle, $sourceSize, $containerPath, $depth);
  return $GzipInspected; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to inspect a tar archive through a stream handle.
// / A tar is a run of 512 byte headers, each followed by its member padded to a block.
// / A long name is stored as a member of its own, ahead of the one member it names.
function inspectTarHandle($file, $handle, $sourceSize, $containerPath, $depth, &$fileDetections, &$handlerHits, &$inspection) {
  // / Set variables.
  $TarInspected = FALSE;
  $position = 0;
  $keepWalking = TRUE;
  $tarHeader = $memberName = $namePrefix = $longName = $sizeField = $memberType = $memberData = $memberPath = '';
  $memberSize = $readLimit = 0;
  $memberComplete = FALSE;
  $paxParts = array();
  while ($keepWalking && !$inspection['halted'] && ($position + 512) <= $sourceSize && @fseek($handle, $position) === 0) {
    redeclare($tarHeader, strval(fread($handle, 512)));
    redeclare($memberName, rtrim(substr($tarHeader, 0, 100), "\0"));
    // / A block of nothing is how a tar says it has finished.
    if (strlen($tarHeader) < 512 or $memberName === '') $keepWalking = FALSE;
    else {
      $TarInspected = TRUE;
      redeclare($namePrefix, rtrim(substr($tarHeader, 345, 155), "\0"));
      if (substr($tarHeader, 257, 5) === 'ustar' && $namePrefix !== '') $memberName = $namePrefix.'/'.$memberName;
      redeclare($sizeField, substr($tarHeader, 124, 12));
      // / A size is written in octal, or in binary when the leading bit is set.
      if ((ord($sizeField[0]) & 0x80) !== 0) redeclare($memberSize, readUnsigned64(substr($sizeField, 4, 8), TRUE));
      else redeclare($memberSize, octdec(preg_replace('/[^0-7]/', '', $sizeField)));
      redeclare($memberType, substr($tarHeader, 156, 1));
      $position = $position + 512;
      if (($position + $memberSize) > $sourceSize) {
        noteIncomplete($inspection, 'a tar archive ends before its last member does');
        $memberSize = max(0, $sourceSize - $position);
        $keepWalking = FALSE; }
      $memberSize = intval($memberSize);
      // / A long name is stored as a member of its own, ahead of the member it names.
      if ($memberType === 'L') redeclare($longName, rtrim(strval(fread($handle, min($memberSize, 4096))), "\0"));
      // / A pax header does the same job & writes the name as a path record.
      if ($memberType === 'x' && preg_match('/^\d+ path=([^\n]*)$/m', strval(fread($handle, min($memberSize, 8192))), $paxParts)) redeclare($longName, $paxParts[1]);
      if (($memberType === '0' or $memberType === "\0" or $memberType === '7') && $memberSize > 0) {
        if ($longName !== '') $memberName = $longName;
        redeclare($readLimit, claimMemberBudget($inspection));
        if ($readLimit > 0) {
          redeclare($memberData, strval(fread($handle, min($memberSize, $readLimit))));
          redeclare($memberComplete, $memberSize <= $readLimit && $keepWalking);
          if (!$memberComplete && $keepWalking) noteIncomplete($inspection, 'a member larger than the entry budget was inspected in part');
          $inspection['bytes'] = $inspection['bytes'] + strlen($memberData);
          redeclare($memberPath, ($containerPath !== '' ? $containerPath.' > ' : '').$memberName);
          if ($memberData !== '') inspectMemberData($file, $memberPath, basename($memberName), $memberData, $memberComplete, $depth, $fileDetections, $handlerHits, $inspection); } }
      // / A long name names the one member after it, whatever kind of member that is.
      if ($memberType !== 'L' && $memberType !== 'x' && $memberType !== 'K' && $memberType !== 'g') redeclare($longName, '');
      $position = $position + (intdiv($memberSize + 511, 512) * 512); } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($position, $keepWalking, $tarHeader, $memberName, $namePrefix, $longName, $sizeField, $memberType, $memberData, $memberPath, $memberSize, $readLimit, $memberComplete, $paxParts, $file, $handle, $sourceSize, $containerPath, $depth);
  return $TarInspected; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to inspect a container of any supported format through a stream handle.
// / The depth counts how many containers sit above this one.
function inspectContainerHandle($file, $handle, $sourceSize, $containerFormat, $containerPath, $depth, &$fileDetections, &$handlerHits, &$inspection) {
  // / Set variables.
  $ContainerInspected = FALSE;
  if ($containerFormat === 'zip') $ContainerInspected = inspectZipHandle($file, $handle, $sourceSize, $containerPath, $depth, $fileDetections, $handlerHits, $inspection);
  if ($containerFormat === 'gzip') $ContainerInspected = inspectGzipHandle($file, $handle, $sourceSize, $containerPath, $depth, $fileDetections, $handlerHits, $inspection);
  if ($containerFormat === 'tar') $ContainerInspected = inspectTarHandle($file, $handle, $sourceSize, $containerPath, $depth, $fileDetections, $handlerHits, $inspection);
  if ($containerFormat === 'bzip2') noteIncomplete($inspection, 'a bzip2 stream is not read');
  processOutput('Inspected a '.$containerFormat.' container at depth '.$depth.'.', 'log', 0, FALSE, FALSE, FALSE);
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($containerFormat, $containerPath, $depth, $sourceSize, $file, $handle);
  return $ContainerInspected; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to write a value into a report line safely.
// / A byte signature may hold bytes that have no printable form.
// / Each such byte is written as the escape that names it, so the report stays plain text.
function printableValue($rawValue) {
  // / Set variables.
  $PrintableValue = $rawValue;
  if (preg_match('/[^\x20-\x7E]/', $rawValue)) $PrintableValue = preg_replace_callback('/[^\x20-\x7E]/', function($byteMatch) { return sprintf('\\x%02x', ord($byteMatch[0])); }, $rawValue);
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($rawValue);
  return $PrintableValue; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to decode the byte escapes a definition value may carry.
// / A byte with no printable form is written as an escape such as \x60 & is decoded here into the byte it names.
function decodeDefinitionValue($rawValue) {
  // / Set variables.
  $DecodedValue = $rawValue;
  if (strpos($rawValue, '\\x') !== FALSE) $DecodedValue = preg_replace_callback('/\\\\x([0-9A-Fa-f]{2})/', function($escapeMatch) { return chr(hexdec($escapeMatch[1])); }, $rawValue);
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($rawValue);
  return $DecodedValue; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to name the index file that belongs to a definitions file.
function hashIndexPath($DefsFile) {
  // / Set variables.
  $IndexPath = $DefsFile.'.idx';
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($DefsFile);
  return $IndexPath; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to pack hash records into sorted sections that can be searched by halving.
function packHashSections($hashRecords) {
  // / Set variables.
  $PackedSections = array('md5' => '', 'sha1' => '', 'sha256' => '', 'counts' => array('md5' => 0, 'sha1' => 0, 'sha256' => 0), 'strings' => array());
  foreach (array('md5', 'sha1', 'sha256') as $hashKind) if (isset($hashRecords[$hashKind])) {
    sort($hashRecords[$hashKind], SORT_STRING);
    $PackedSections['counts'][$hashKind] = count($hashRecords[$hashKind]);
    $PackedSections[$hashKind] = implode('', $hashRecords[$hashKind]); }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($hashKind, $hashRecords);
  return $PackedSections; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to build a packed index beside a definitions file.
// / Each record is a hash followed by the offset of its line in the definitions file.
// / The name & the confidence of a definition are read from that line once its hash has matched.
// / The string indicators are kept in the index too, so a valid index is all that a scan has to load.
// / The header carries the hash of the definitions file, so an index built from another file is never trusted.
// / Returns TRUE when the index was written.
function buildHashIndex($DefsFile, $indexFile, $hashRecords, $stringIndicators, $sourceHash) {
  // / Set variables.
  $IndexBuilt = FALSE;
  $packedSections = packHashSections($hashRecords);
  $sectionCounts = $packedSections['counts'];
  $stringBlob = serialize($stringIndicators);
  $headerText = 'SCIDX2'."\n".$sourceHash."\n".$sectionCounts['md5'].' '.$sectionCounts['sha1'].' '.$sectionCounts['sha256'].' '.strlen($stringBlob)."\n";
  if (@file_put_contents($indexFile, $headerText.$packedSections['md5'].$packedSections['sha1'].$packedSections['sha256'].$stringBlob) !== FALSE) {
    applyOwnership($indexFile, FALSE);
    $IndexBuilt = TRUE; }
  else processOutput('Cannot write the definitions index, so the definitions will be parsed on every scan:  '.$indexFile, 'error', 550, TRUE, FALSE, FALSE);
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($packedSections, $sectionCounts, $stringBlob, $headerText, $hashRecords, $stringIndicators, $indexFile, $sourceHash, $DefsFile);
  return $IndexBuilt; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to load a packed index, when one exists & was built from the definitions file in use.
// / Each section is read straight from the file, so the index is never held in memory twice.
// / Returns whether the index was loaded & the index, in that order.
function loadHashIndex($indexFile, $sourceHash) {
  // / Set variables.
  $IndexLoaded = FALSE;
  $Index = array('md5' => '', 'sha1' => '', 'sha256' => '', 'counts' => array('md5' => 0, 'sha1' => 0, 'sha256' => 0), 'strings' => array());
  $indexHandle = FALSE;
  $headerLine = array('', '', '');
  $sectionCounts = array();
  $recordWidths = array('md5' => 20, 'sha1' => 24, 'sha256' => 36);
  $hashKind = '';
  $recordWidth = $stringLength = 0;
  $stringBlob = '';
  $sectionsRead = FALSE;
  if (file_exists($indexFile)) $indexHandle = @fopen($indexFile, 'rb');
  if ($indexHandle !== FALSE) {
    $headerLine[0] = trim(strval(fgets($indexHandle)));
    $headerLine[1] = trim(strval(fgets($indexHandle)));
    $headerLine[2] = trim(strval(fgets($indexHandle)));
    $sectionCounts = explode(' ', $headerLine[2]);
    if ($headerLine[0] === 'SCIDX2' && $headerLine[1] === $sourceHash && count($sectionCounts) === 4) {
      $Index['counts']['md5'] = max(0, intval($sectionCounts[0]));
      $Index['counts']['sha1'] = max(0, intval($sectionCounts[1]));
      $Index['counts']['sha256'] = max(0, intval($sectionCounts[2]));
      $stringLength = max(0, intval($sectionCounts[3]));
      $sectionsRead = TRUE;
      // / A section that is cut short would answer no for every hash past the cut, so a short read refuses the index.
      foreach ($recordWidths as $hashKind => $recordWidth) {
        if ($Index['counts'][$hashKind] > 0) $Index[$hashKind] = strval(fread($indexHandle, $Index['counts'][$hashKind] * $recordWidth));
        if (strlen($Index[$hashKind]) !== ($Index['counts'][$hashKind] * $recordWidth)) $sectionsRead = FALSE; }
      if ($sectionsRead) {
        $stringBlob = $stringLength > 0 ? strval(fread($indexHandle, $stringLength)) : serialize(array());
        $Index['strings'] = @unserialize($stringBlob, array('allowed_classes' => FALSE));
        if (is_array($Index['strings'])) $IndexLoaded = TRUE; } }
    fclose($indexHandle); }
  if (!$IndexLoaded) $Index = array('md5' => '', 'sha1' => '', 'sha256' => '', 'counts' => array('md5' => 0, 'sha1' => 0, 'sha256' => 0), 'strings' => array());
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($indexHandle, $headerLine, $sectionCounts, $recordWidths, $hashKind, $recordWidth, $stringLength, $stringBlob, $sectionsRead, $indexFile, $sourceHash);
  return array($IndexLoaded, $Index); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to find a hash inside a packed index section, by halving the range.
// / Returns the offset of the matching line in the definitions file, or minus one.
function lookupPackedHash($sectionBlob, $sectionCount, $hashWidth, $rawHash) {
  // / Set variables.
  $MatchOffset = -1;
  $lowIndex = 0;
  $highIndex = $sectionCount - 1;
  $middleIndex = 0;
  $recordWidth = $hashWidth + 4;
  $comparison = 0;
  $offsetBytes = array();
  while ($lowIndex <= $highIndex && $MatchOffset < 0) {
    $middleIndex = intdiv($lowIndex + $highIndex, 2);
    $comparison = strcmp(substr($sectionBlob, $middleIndex * $recordWidth, $hashWidth), $rawHash);
    if ($comparison === 0) {
      $offsetBytes = unpack('V', substr($sectionBlob, ($middleIndex * $recordWidth) + $hashWidth, 4));
      $MatchOffset = intval($offsetBytes[1]); }
    else {
      if ($comparison < 0) $lowIndex = $middleIndex + 1;
      else $highIndex = $middleIndex - 1; } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($lowIndex, $highIndex, $middleIndex, $recordWidth, $comparison, $offsetBytes, $sectionBlob, $rawHash, $sectionCount, $hashWidth);
  return $MatchOffset; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to read one definition record from the definitions file, at a known offset.
// / This is how a hash found in the packed index is given its name & its confidence.
// / Returns whether a record was read & the record, in that order.
function readDefinitionAtOffset($DefsFile, $lineOffset) {
  // / Set variables.
  $RecordFound = FALSE;
  $Record = array('kind' => '', 'name' => '', 'value' => '', 'confidence' => '', 'scope' => '', 'src' => '');
  $handle = @fopen($DefsFile, 'rb');
  $lineText = '';
  if ($handle !== FALSE) {
    if (@fseek($handle, $lineOffset) === 0) {
      $lineText = fgets($handle);
      if (is_string($lineText)) list($RecordFound, $Record) = parseDefinitionRecord(trim($lineText)); }
    fclose($handle); }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($handle, $lineText, $DefsFile, $lineOffset);
  return array($RecordFound, $Record); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to read one definition record in the ScanCore definition format.
// / A record is written as  <kind> <name> = <value> [attribute=value ...]  & nothing in it is positional.
// / A hash must be written in hexadecimal & be exactly as long as its kind of hash.
// / The attributes that are read are confidence, scope & src, & any other attribute is ignored.
// / A confidence that is absent or is not high, medium or low falls back to the default of the kind.
// / Returns whether the record could be read & the record, in that order.
function parseDefinitionRecord($recordBody) {
  // / Set variables.
  $RecordParsed = FALSE;
  $Record = array('kind' => '', 'name' => '', 'value' => '', 'confidence' => '', 'scope' => '', 'src' => '');
  $keywordEnd = strpos($recordBody, ' ');
  $equalsPosition = strpos($recordBody, '=');
  $closingQuote = 0;
  $remainder = $attributeText = $attributePair = $attributeKey = '';
  $valueParts = $attributeParts = array();
  $hashLengths = array('md5' => 32, 'sha1' => 40, 'sha256' => 64);
  $otherKinds = array('name', 'data', 'host', 'url');
  $attributesRead = array('confidence', 'scope', 'src');
  $validConfidences = array('high', 'medium', 'low');
  if ($keywordEnd !== FALSE && $equalsPosition !== FALSE && $keywordEnd < $equalsPosition) {
    $Record['kind'] = strtolower(trim(substr($recordBody, 0, $keywordEnd)));
    $Record['name'] = trim(substr($recordBody, $keywordEnd + 1, $equalsPosition - $keywordEnd - 1));
    $remainder = ltrim(substr($recordBody, $equalsPosition + 1));
    // / A quoted value may hold a space & keeps the whitespace it was written with.
    if (substr($remainder, 0, 1) === '"') {
      // / A quote written after a backslash belongs to the value & does not close it.
      do $closingQuote = strpos($remainder, '"', $closingQuote + 1);
      while ($closingQuote !== FALSE && substr($remainder, $closingQuote - 1, 1) === '\\');
      if ($closingQuote !== FALSE) {
        $Record['value'] = decodeDefinitionValue(str_replace('\\"', '"', substr($remainder, 1, $closingQuote - 1)));
        $attributeText = substr($remainder, $closingQuote + 1);
        $RecordParsed = TRUE; } }
    else {
      // / A value written without quotes is the first word, & the words after it are attributes.
      $valueParts = preg_split('/\s+/', $remainder, 2);
      $Record['value'] = $valueParts[0];
      if (isset($valueParts[1])) $attributeText = $valueParts[1];
      if ($Record['value'] !== '') $RecordParsed = TRUE; }
    // / An attribute is a key, an equals sign & a value, with no space between them.
    if ($RecordParsed) foreach (preg_split('/\s+/', $attributeText, -1, PREG_SPLIT_NO_EMPTY) as $attributePair) {
      redeclare($attributeParts, explode('=', $attributePair, 2));
      redeclare($attributeKey, strtolower($attributeParts[0]));
      if (isset($attributeParts[1]) && in_array($attributeKey, $attributesRead, TRUE)) $Record[$attributeKey] = trim($attributeParts[1]); }
    // / A hash is held in lower case, so it is found whichever case the record used.
    if (isset($hashLengths[$Record['kind']])) {
      $Record['value'] = strtolower($Record['value']);
      if (preg_match('/^[0-9a-f]{'.$hashLengths[$Record['kind']].'}$/', $Record['value']) !== 1) $RecordParsed = FALSE; }
    else { if (!in_array($Record['kind'], $otherKinds, TRUE)) $RecordParsed = FALSE; }
    if ($Record['name'] === '' or $Record['value'] === '') $RecordParsed = FALSE; }
  // / A whole file hash is an exact match & is trusted, & every other kind is a heuristic.
  if ($RecordParsed) {
    $Record['confidence'] = strtolower($Record['confidence']);
    if (!in_array($Record['confidence'], $validConfidences, TRUE)) $Record['confidence'] = isset($hashLengths[$Record['kind']]) ? 'high' : 'medium'; }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($keywordEnd, $equalsPosition, $closingQuote, $remainder, $attributeText, $attributePair, $attributeKey, $valueParts, $attributeParts, $hashLengths, $otherKinds, $attributesRead, $validConfidences, $recordBody);
  return array($RecordParsed, $Record); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to read every record of a definitions file.
// / The file is read one line at a time, so a large file is never held in memory whole.
// / A hash, a host & a url are each stored under their own value, so looking one up costs the same however many there are.
// / Every hash is also packed with the offset of its line, ready for an index to be built from.
// / Returns whether any record could be used, the definitions & the packed hash records, in that order.
function readDefinitionRecords($DefsFile) {
  // / Set variables.
  global $UseHashIndex, $HashIndexThreshold;
  $RecordsRead = FALSE;
  $Defs = array('md5' => array(), 'sha1' => array(), 'sha256' => array(), 'name' => array(), 'data' => array(), 'host' => array(), 'url' => array());
  $HashRecords = array('md5' => array(), 'sha1' => array(), 'sha256' => array());
  $defsHandle = @fopen($DefsFile, 'rb');
  $rawLine = $recordBody = $recordKind = '';
  $lineOffset = $recordStart = $hashCount = $usableCount = $malformedCount = $retiredFormatCount = 0;
  $recordParsed = FALSE;
  $definitionRecord = array();
  if ($defsHandle === FALSE) processOutput('Cannot open the definitions file for reading:  '.$DefsFile, 'error', 500, TRUE, TRUE, TRUE);
  while ($defsHandle !== FALSE && ($rawLine = fgets($defsHandle)) !== FALSE) {
    // / Remember where the line starts, so a hash that matches can find its own record.
    redeclare($recordStart, $lineOffset);
    $lineOffset = $lineOffset + strlen($rawLine);
    redeclare($recordBody, trim($rawLine));
    if ($recordBody !== '' && substr($recordBody, 0, 1) !== '#') {
      // / A line in the retired tab delimited format is counted, so the file can be reported as one.
      if (strpos($recordBody, "\t") !== FALSE && strpos($recordBody, '=') === FALSE) $retiredFormatCount++;
      else {
        list($recordParsed, $definitionRecord) = parseDefinitionRecord($recordBody);
        if (!$recordParsed) $malformedCount++;
        else {
          $usableCount++;
          redeclare($recordKind, $definitionRecord['kind']);
          if (isset($HashRecords[$recordKind])) {
            // / A whole record is held only while the set is small enough to go without an index.
            if (!$UseHashIndex or $hashCount < $HashIndexThreshold) $Defs[$recordKind][$definitionRecord['value']] = $definitionRecord;
            $hashCount++;
            $HashRecords[$recordKind][] = hex2bin($definitionRecord['value']).pack('V', $recordStart); }
          if ($recordKind === 'host' or $recordKind === 'url') $Defs[$recordKind][strtolower(trim($definitionRecord['value']))] = $definitionRecord;
          // / A name or a data indicator has no shape to look up, so it is searched for directly.
          if ($recordKind === 'name' or $recordKind === 'data') $Defs[$recordKind][] = $definitionRecord; } } } }
  if ($defsHandle !== FALSE) fclose($defsHandle);
  processOutput('Found '.$usableCount.' definitions.', 'log', 0, FALSE, FALSE, FALSE);
  processOutput('Definitions by kind:  '.count($HashRecords['md5']).' md5, '.count($HashRecords['sha1']).' sha1, '.count($HashRecords['sha256']).' sha256, '.count($Defs['name']).' name, '.count($Defs['data']).' data, '.count($Defs['host']).' host, '.count($Defs['url']).' url.', 'log', 0, FALSE, FALSE, FALSE);
  if ($malformedCount > 0) processOutput('Ignored '.countWithNoun($malformedCount, 'malformed definition record', 'malformed definition records').'.', 'warning', 0, TRUE, FALSE, FALSE);
  if ($retiredFormatCount > 0) processOutput('The definitions file holds '.countWithNoun($retiredFormatCount, 'record', 'records').' in the retired tab delimited format, which is no longer read. Rebuild it with the ScanCore_Definitions builder.', 'error', 540, TRUE, TRUE, FALSE);
  if ($usableCount < 1) processOutput('The definitions file contains no usable definitions:  '.$DefsFile, 'error', 510, TRUE, TRUE, FALSE);
  else $RecordsRead = TRUE;
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($defsHandle, $rawLine, $recordBody, $recordKind, $lineOffset, $recordStart, $hashCount, $usableCount, $malformedCount, $retiredFormatCount, $recordParsed, $definitionRecord, $DefsFile);
  return array($RecordsRead, $Defs, $HashRecords); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to load the definitions, from a packed index where a valid one exists.
// / A valid index means that the definitions file is not read again until a hash matches.
// / An index is built for a set of hashes large enough to be worth a second file on disk.
// / Returns whether the definitions were loaded, the definitions, the hash of the definitions file & the length of the longest indicator, in that order.
function loadDefs($DefsFile) {
  // / Set variables.
  global $UseHashIndex, $HashIndexThreshold, $RebuildHashIndex, $HashIndex, $HashIndexActive, $MinimumDataSignatureLength;
  $DefsLoaded = FALSE;
  $Defs = array('md5' => array(), 'sha1' => array(), 'sha256' => array(), 'name' => array(), 'data' => array(), 'host' => array(), 'url' => array());
  $DefData = '';
  $DefsLongestSignature = 0;
  $HashIndex = array('md5' => '', 'sha1' => '', 'sha256' => '', 'counts' => array('md5' => 0, 'sha1' => 0, 'sha256' => 0), 'strings' => array());
  $HashIndexActive = FALSE;
  $indexFile = hashIndexPath($DefsFile);
  $indexLoaded = FALSE;
  $hashRecords = $definitionRecord = array();
  $hashTotal = $usableCount = $shortCount = $definitionKey = 0;
  $indicatorKind = '';
  if (!file_exists($DefsFile)) processOutput('Cannot load the definitions file:  '.$DefsFile, 'error', 500, TRUE, TRUE, TRUE);
  else {
    processOutput('Loading the definitions file:  '.$DefsFile, 'log', 0, FALSE, FALSE, FALSE);
    $DefData = strval(hash_file('sha256', $DefsFile));
    if ($UseHashIndex && !$RebuildHashIndex) list($indexLoaded, $HashIndex) = loadHashIndex($indexFile, $DefData);
    if ($indexLoaded) {
      $HashIndexActive = TRUE;
      foreach (array('name', 'data', 'host', 'url') as $indicatorKind) if (isset($HashIndex['strings'][$indicatorKind]) && is_array($HashIndex['strings'][$indicatorKind])) $Defs[$indicatorKind] = $HashIndex['strings'][$indicatorKind];
      $usableCount = $HashIndex['counts']['md5'] + $HashIndex['counts']['sha1'] + $HashIndex['counts']['sha256'] + count($Defs['name']) + count($Defs['data']) + count($Defs['host']) + count($Defs['url']);
      processOutput('Loaded '.$usableCount.' definitions from the packed index.', 'log', 0, FALSE, FALSE, FALSE);
      $DefsLoaded = TRUE; }
    else {
      list($DefsLoaded, $Defs, $hashRecords) = readDefinitionRecords($DefsFile);
      $hashTotal = count($hashRecords['md5']) + count($hashRecords['sha1']) + count($hashRecords['sha256']);
      if ($DefsLoaded && $UseHashIndex && $hashTotal >= $HashIndexThreshold) {
        // / The packed records are searched from this run onward, whether or not the index file can be written.
        $HashIndex = packHashSections($hashRecords);
        $HashIndexActive = TRUE;
        $Defs['md5'] = $Defs['sha1'] = $Defs['sha256'] = array();
        if (buildHashIndex($DefsFile, $indexFile, $hashRecords, array('name' => $Defs['name'], 'data' => $Defs['data'], 'host' => $Defs['host'], 'url' => $Defs['url']), $DefData)) processOutput('Built a packed index for '.countWithNoun($hashTotal, 'hash', 'hashes').':  '.$indexFile, 'log', 0, TRUE, FALSE, FALSE); }
      // / A set that is too small to index leaves no index behind.
      if ($DefsLoaded && $hashTotal < $HashIndexThreshold && file_exists($indexFile)) @unlink($indexFile); }
    // / A data signature shorter than the configured minimum is never searched for.
    foreach ($Defs['data'] as $definitionKey => $definitionRecord) if (strlen($definitionRecord['value']) < $MinimumDataSignatureLength) {
      unset($Defs['data'][$definitionKey]);
      $shortCount++; }
    if ($shortCount > 0) processOutput('Ignored '.countWithNoun($shortCount, 'data signature', 'data signatures').' shorter than the MinimumDataSignatureLength of '.$MinimumDataSignatureLength.'.', 'warning', 0, TRUE, FALSE, FALSE);
    // / The longest indicator decides how much of one chunk is carried into the next.
    foreach (array('data', 'host', 'url') as $indicatorKind) foreach ($Defs[$indicatorKind] as $definitionRecord) $DefsLongestSignature = max($DefsLongestSignature, strlen($definitionRecord['value'])); }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($indexFile, $indexLoaded, $hashRecords, $definitionRecord, $hashTotal, $usableCount, $shortCount, $definitionKey, $indicatorKind);
  return array($DefsLoaded, $Defs, $DefData, $DefsLongestSignature); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to record a single detection against a file.
// / A detection of high confidence opens its line with Infected, & every other detection opens with Suspicious.
// / A detection made inside a container names the member it was made in.
// / The same detection is reported once for a file, however many times it is made.
function recordDetection($file, $definitionName, $matchType, $matchValue, $confidence, &$fileDetections) {
  // / Set variables.
  global $ReportSuspicious, $MemberContext;
  $DetectionRecorded = FALSE;
  $detectionKey = $definitionName.'|'.$matchType.'|'.$MemberContext;
  $memberText = ($MemberContext !== '') ? ' in '.printableName($MemberContext) : '';
  $openingWord = ($confidence === 'high') ? 'Infected: ' : 'Suspicious: ';
  if (!isset($fileDetections[$detectionKey]) && ($confidence === 'high' or $ReportSuspicious)) {
    $fileDetections[$detectionKey] = $confidence;
    $DetectionRecorded = TRUE;
    processOutput($openingWord.printablePath($file).' ('.$definitionName.', '.$matchType.': '.printableValue($matchValue).$memberText.')', 'log', 0, TRUE, TRUE, FALSE); }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($detectionKey, $memberText, $openingWord, $file, $definitionName, $matchType, $matchValue, $confidence);
  return $DetectionRecorded; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to decide whether a definition applies to the file being scanned.
// / A definition with no scope applies to every file.
// / A scoped definition applies only to a file that was identified as the classifier type it names.
function definitionIsInScope($definitionRecord, $candidateTypes) {
  // / Set variables.
  $DefinitionIsInScope = FALSE;
  $candidateType = '';
  if ($definitionRecord['scope'] === '' or strtolower($definitionRecord['scope']) === 'any') $DefinitionIsInScope = TRUE;
  else {
    if (is_array($candidateTypes)) foreach ($candidateTypes as $candidateType) if (strtolower($definitionRecord['scope']) === strtolower($candidateType)) $DefinitionIsInScope = TRUE; }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($candidateType, $definitionRecord, $candidateTypes);
  return $DefinitionIsInScope; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to look a host candidate up, removing its leftmost label after each miss.
// / An indicator that names evil.example therefore matches www.evil.example as well.
// / Returns the indicator that matched, or an empty string.
function lookupHostIndicator($hostCandidate, $hostIndex) {
  // / Set variables.
  $MatchedKey = '';
  $workingHost = strtolower($hostCandidate);
  $dotPosition = 0;
  // / A host name is never longer than 253 characters, so only the end of a longer run is looked up.
  // / That end begins after a dot, so a label is never read from its middle.
  if (strlen($workingHost) > 253) $workingHost = strval(strstr(substr($workingHost, -253), '.'));
  $workingHost = trim($workingHost, '.-');
  while ($workingHost !== '' && $MatchedKey === '') {
    if (isset($hostIndex[$workingHost])) $MatchedKey = $workingHost;
    else {
      $dotPosition = strpos($workingHost, '.');
      if ($dotPosition === FALSE) redeclare($workingHost, '');
      else redeclare($workingHost, substr($workingHost, $dotPosition + 1)); } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($workingHost, $dotPosition, $hostCandidate, $hostIndex);
  return $MatchedKey; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to look up every host or every url that a block of data holds.
// / The kind is host or url, & the index holds the indicators of that kind under their own values.
// / A url is a scheme followed by anything up to whitespace or a quoting character, & it is looked up whole.
// / A host is a run of name characters that holds at least one dot, which covers a dotted quad as well.
// / The block is searched one match at a time, so the memory a search needs does not grow with what it finds.
// / Returns the number of indicators that matched.
function matchNetworkIndicators($file, $haystack, $indicatorKind, $indicatorIndex, $candidateTypes, &$fileDetections) {
  // / Set variables.
  $MatchesFound = 0;
  // / Each part of the host pattern takes its run in one step & gives nothing back, so the search stays linear.
  $candidatePattern = ($indicatorKind === 'url') ? '#[a-z][a-z0-9+.\-]{1,15}://[^\s"\'<>()\[\]\\\\]{4,2048}#i' : '#(?<![a-z0-9\-])[a-z0-9\-]++\.[a-z0-9.\-]++#i';
  $matchType = ($indicatorKind === 'url') ? 'URL Match' : 'Host Match';
  $searchOffset = 0;
  $searchResult = 1;
  $candidateMatch = array();
  $matchedKey = '';
  while ($searchResult === 1) {
    redeclare($searchResult, preg_match($candidatePattern, $haystack, $candidateMatch, PREG_OFFSET_CAPTURE, $searchOffset));
    if ($searchResult === 1) {
      $searchOffset = $candidateMatch[0][1] + strlen($candidateMatch[0][0]);
      if ($indicatorKind === 'url') redeclare($matchedKey, strtolower(rtrim($candidateMatch[0][0], '.,;:)]}\'"')));
      else redeclare($matchedKey, lookupHostIndicator($candidateMatch[0][0], $indicatorIndex));
      if ($matchedKey !== '' && isset($indicatorIndex[$matchedKey])) if (definitionIsInScope($indicatorIndex[$matchedKey], $candidateTypes)) {
        recordDetection($file, $indicatorIndex[$matchedKey]['name'], $matchType, $indicatorIndex[$matchedKey]['value'], $indicatorIndex[$matchedKey]['confidence'], $fileDetections);
        $MatchesFound++; } } }
  // / A search that the pattern engine gave up on must not read as a block with nothing in it.
  if ($searchResult === FALSE) processOutput('Cannot search a block of data for '.$indicatorKind.' indicators, so any in it were not matched.', 'warning', 0, TRUE, TRUE, FALSE);
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($candidatePattern, $matchType, $searchOffset, $searchResult, $candidateMatch, $matchedKey, $haystack, $indicatorKind, $indicatorIndex, $candidateTypes, $file);
  return $MatchesFound; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to test a block of file content against the data, host & url indicators.
// / A data indicator is searched for directly, so every one of them costs a search of its own.
// / A host or a url is found in the block & looked up, whatever the number of indicators.
// / None of these are ever tested against the path of the file.
// / Returns the number of indicators that matched.
function matchContentIndicators($file, $haystack, $Defs, $candidateTypes, &$fileDetections) {
  // / Set variables.
  $MatchesFound = 0;
  $definitionRecord = array();
  foreach ($Defs['data'] as $definitionRecord) if (definitionIsInScope($definitionRecord, $candidateTypes)) {
    if (stripos($haystack, $definitionRecord['value']) !== FALSE) {
      recordDetection($file, $definitionRecord['name'], 'Data Match', $definitionRecord['value'], $definitionRecord['confidence'], $fileDetections);
      $MatchesFound++; } }
  // / The block is only searched for urls or for hosts when an indicator of that kind is loaded.
  if (count($Defs['url']) > 0) $MatchesFound = $MatchesFound + matchNetworkIndicators($file, $haystack, 'url', $Defs['url'], $candidateTypes, $fileDetections);
  if (count($Defs['host']) > 0) $MatchesFound = $MatchesFound + matchNetworkIndicators($file, $haystack, 'host', $Defs['host'], $candidateTypes, $fileDetections);
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($definitionRecord, $haystack, $candidateTypes, $file);
  return $MatchesFound; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to test the name of a file against the name indicators.
// / Only the name of the file is tested, & never the folders above it.
// / Returns the number of indicators that matched.
function matchNameIndicators($file, $Defs, &$fileDetections) {
  // / Set variables.
  $MatchesFound = 0;
  $fileName = basename($file);
  $definitionRecord = array();
  foreach ($Defs['name'] as $definitionRecord) {
    if (stripos($fileName, $definitionRecord['value']) !== FALSE) {
      recordDetection($file, $definitionRecord['name'], 'Name Match', $definitionRecord['value'], $definitionRecord['confidence'], $fileDetections);
      $MatchesFound++; } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($definitionRecord, $fileName, $file);
  return $MatchesFound; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to test the hashes of a file against the hash definitions.
// / A hash is found by one lookup, in the definitions or in the packed index, whichever is in use.
// / The packed index holds only where a record is, so the record is read from the definitions file once its hash has matched.
// / Returns the number of hashes that matched.
function matchHashSignatures($file, $md5Hash, $sha1Hash, $sha256Hash, $Defs, &$fileDetections) {
  // / Set variables.
  global $HashIndex, $HashIndexActive, $DefsFile;
  $MatchesFound = 0;
  $indexOffset = 0;
  $matchedRecord = array();
  $recordFound = FALSE;
  $hashKind = $computedHash = '';
  $hashWidths = array('md5' => 16, 'sha1' => 20, 'sha256' => 32);
  $computedHashes = array('md5' => $md5Hash, 'sha1' => $sha1Hash, 'sha256' => $sha256Hash);
  foreach ($computedHashes as $hashKind => $computedHash) {
    redeclare($recordFound, FALSE);
    if ($HashIndexActive) {
      if ($HashIndex['counts'][$hashKind] > 0) {
        redeclare($indexOffset, lookupPackedHash($HashIndex[$hashKind], $HashIndex['counts'][$hashKind], $hashWidths[$hashKind], hex2bin($computedHash)));
        if ($indexOffset >= 0) list($recordFound, $matchedRecord) = readDefinitionAtOffset($DefsFile, $indexOffset); } }
    else {
      if (isset($Defs[$hashKind][$computedHash])) {
        $recordFound = TRUE;
        $matchedRecord = $Defs[$hashKind][$computedHash]; } }
    if ($recordFound) {
      recordDetection($file, $matchedRecord['name'], strtoupper($hashKind).' Hash Match', $computedHash, $matchedRecord['confidence'], $fileDetections);
      $MatchesFound++; } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($indexOffset, $matchedRecord, $recordFound, $hashKind, $computedHash, $hashWidths, $computedHashes, $md5Hash, $sha1Hash, $sha256Hash, $file);
  return $MatchesFound; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to measure the unbroken run of text that a block of data ends with.
// / A run is broken by a space, a control character, a quote, a bracket, a backslash or a byte that is not text.
// / Those are what end a host name, a url & an identifier, so a block that is cut at one of them cuts none of the three.
// / Returns the length of the run, which is 0 where the block ends on a break or where no break is within 255 bytes of its end.
function trailingRunLength($blockText) {
  // / Set variables.
  $TrailingRunLength = 0;
  $runMatch = array();
  if (preg_match('/[^\x00-\x20\x7F-\xFF"\'<>()\[\]\\\\]+\z/', substr($blockText, -256), $runMatch) === 1) $TrailingRunLength = strlen($runMatch[0]);
  if ($TrailingRunLength > 255) $TrailingRunLength = 0;
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($runMatch, $blockText);
  return $TrailingRunLength; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to take the end of a chunk that is carried into the chunk after it.
// / The end is long enough to hold the longest signature, so a signature that spans two chunks is still found.
// / It begins at a break in the text, so a name is never read from its middle.
function carriedTail($chunkText, $tailLength) {
  // / Set variables.
  $CarriedTail = '';
  $tailStart = max(0, strlen($chunkText) - $tailLength);
  if ($tailLength > 0) {
    $tailStart = $tailStart - trailingRunLength(substr($chunkText, max(0, $tailStart - 256), min(256, $tailStart)));
    $CarriedTail = substr($chunkText, $tailStart); }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($tailStart, $chunkText, $tailLength);
  return $CarriedTail; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to decide how a file is to be classified, before any of it is searched.
// / A magic signature outranks an extension, because the opening bytes of a file cannot be renamed.
// / An extension that names a language settles what the file is, & its content is not searched for one.
// / An extension that nothing claims is searched against every declared type, unless the file is an image.
// / Returns the plan, which holds the mode, the types to search for, the types the file claims to be & the language its extension names.
// / The mode is empty, or one of language, handlers & unknown.
function planClassification($fileExtension, $fileHeader, $imageFormat) {
  // / Set variables.
  global $Classifiers;
  $ClassifyPlan = array('mode' => '', 'candidates' => typesForExtension($Classifiers, $fileExtension), 'scope' => array(), 'language' => languageForExtension($Classifiers, $fileExtension));
  $magicType = magicTypeFor($Classifiers, $fileHeader);
  // / A scoped definition applies to what a file claims to be, & never to the types an unknown file is searched against.
  $ClassifyPlan['scope'] = $ClassifyPlan['candidates'];
  if ($magicType !== '') {
    $ClassifyPlan['mode'] = 'handlers';
    $ClassifyPlan['candidates'] = $ClassifyPlan['scope'] = array($magicType); }
  else {
    if ($ClassifyPlan['language'] !== '') $ClassifyPlan['mode'] = 'language';
    else {
      if (count($ClassifyPlan['candidates']) > 0) $ClassifyPlan['mode'] = 'handlers';
      else {
        $ClassifyPlan['candidates'] = array_keys($Classifiers['handlers']);
        // / Compressed pixels hold every short token by chance, so the content of an image is not searched for one.
        if ($imageFormat === '') $ClassifyPlan['mode'] = 'unknown'; } } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($magicType, $fileExtension, $fileHeader, $imageFormat);
  return $ClassifyPlan; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to read a file in chunks, hashing & searching each chunk as it is read.
// / The end of each chunk is carried into the next, so a signature that spans two chunks is still found.
// / A full chunk hands back the run of text it ends inside, so the next chunk reads that run whole.
// / Nothing of a chunk but its carried end is held while the next chunk is read.
// / Returns whether the whole file was read, its md5 hash, its sha1 hash & its sha256 hash, in that order.
function scanFileContent($file, $handle, $fileSize, $Defs, $ChunkSize, $DefsLongestSignature, $classifyPlan, $imageFormat, $containerIsWalked, &$fileDetections, &$handlerHits, &$languageHits) {
  // / Set variables.
  global $Classifiers, $ClassifierLongestToken, $InspectImages;
  $ContentRead = TRUE;
  $Md5Hash = $Sha1Hash = $Sha256Hash = '';
  $md5Context = hash_init('md5');
  $sha1Context = hash_init('sha1');
  $sha256Context = hash_init('sha256');
  $buffer = $overlap = $window = '';
  $heldBack = 0;
  // / A file smaller than one chunk is asked for in one read of its own size.
  $readSize = ($fileSize > 0 && $fileSize < $ChunkSize) ? $fileSize : $ChunkSize;
  $overlapSize = max(0, $DefsLongestSignature - 1);
  $keepReading = ($fileSize > 0 && rewind($handle));
  // / A handler is recognised by what follows its token, so the end that is carried holds that as well.
  if ($classifyPlan['mode'] !== '') $overlapSize = max($overlapSize, $ClassifierLongestToken + 256);
  if ($imageFormat !== '') $overlapSize = max($overlapSize, $ClassifierLongestToken, 64);
  $overlapSize = min($overlapSize, intdiv($readSize, 2));
  while ($keepReading) {
    redeclare($buffer, fread($handle, $readSize));
    if ($buffer === FALSE) $ContentRead = FALSE;
    if ($buffer === FALSE or $buffer === '') $keepReading = FALSE;
    else {
      redeclare($heldBack, (strlen($buffer) === $ChunkSize) ? trailingRunLength($buffer) : 0);
      if ($heldBack > 0 && $heldBack < strlen($buffer)) if (fseek($handle, 0 - $heldBack, SEEK_CUR) === 0) redeclare($buffer, substr($buffer, 0, 0 - $heldBack));
      hash_update($md5Context, $buffer);
      hash_update($sha1Context, $buffer);
      hash_update($sha256Context, $buffer);
      processOutput('Scanning chunk.', 'log', 0, FALSE, FALSE, FALSE);
      redeclare($window, $overlap.$buffer);
      matchContentIndicators($file, $window, $Defs, $classifyPlan['scope'], $fileDetections);
      // / Code has no business inside an image, wherever in the file it sits.
      if ($InspectImages && $imageFormat !== '') matchEmbedSignatures($file, $window, FALSE, $fileDetections);
      if ($classifyPlan['mode'] === 'unknown') matchLanguageSignatures($window, $Classifiers, $languageHits);
      // / A container that is walked reports its handlers by member instead.
      if (($classifyPlan['mode'] === 'handlers' or $classifyPlan['mode'] === 'unknown') && !$containerIsWalked) matchHandlerTokens($window, $Classifiers, $classifyPlan['candidates'], $handlerHits);
      redeclare($overlap, carriedTail($buffer, $overlapSize));
      redeclare($window, '');
      redeclare($buffer, '');
      if (feof($handle)) $keepReading = FALSE; } }
  $Md5Hash = hash_final($md5Context);
  $Sha1Hash = hash_final($sha1Context);
  $Sha256Hash = hash_final($sha256Context);
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($md5Context, $sha1Context, $sha256Context, $buffer, $overlap, $window, $heldBack, $readSize, $overlapSize, $keepReading, $file, $handle, $fileSize, $classifyPlan, $imageFormat, $containerIsWalked);
  return array($ContentRead, $Md5Hash, $Sha1Hash, $Sha256Hash); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to inspect the structure of a file, as an image, as a container or as both.
// / A png is walked chunk by chunk, & a file that is only named as a png is reported.
// / A container is opened & every member of it is inspected in turn.
// / A zip is read from its end, so a file of any other format is checked for one that sits behind it.
// / Returns TRUE when there was a structure to inspect.
function inspectFileStructure($file, $handle, $fileSize, $fileExtension, $containerFormat, $imageFormat, &$fileDetections, &$handlerHits, &$inspection) {
  // / Set variables.
  global $InspectImages, $InspectArchivedContent, $MemberContext;
  $StructureInspected = FALSE;
  $hiddenDirectory = array();
  if ($fileSize > 0) {
    $StructureInspected = TRUE;
    if ($InspectImages) {
      if ($imageFormat === 'png') inspectPngStream($file, $handle, $fileSize, $fileDetections);
      if (($fileExtension === 'png' or $fileExtension === 'apng') && $imageFormat !== 'png') recordDetection($file, 'Masquerade.PNG', 'Structure Match', 'the file is named as a png & does not begin with a png signature', 'medium', $fileDetections); }
    if ($InspectArchivedContent) {
      if ($containerFormat !== '') inspectContainerHandle($file, $handle, $fileSize, $containerFormat, '', 0, $fileDetections, $handlerHits, $inspection);
      else {
        $hiddenDirectory = findZipDirectory($handle, $fileSize);
        if ($hiddenDirectory['found'] && $hiddenDirectory['entries'] > 0) {
          if ($imageFormat !== '') recordDetection($file, 'Polyglot.Archive.Hidden', 'Structure Match', 'a zip archive of '.countWithNoun($hiddenDirectory['entries'], 'entry', 'entries').' sits behind a '.$imageFormat.' header', 'medium', $fileDetections);
          inspectContainerHandle($file, $handle, $fileSize, 'zip', 'appended archive', 0, $fileDetections, $handlerHits, $inspection); } } } }
  $MemberContext = '';
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($hiddenDirectory, $file, $handle, $fileSize, $fileExtension, $containerFormat, $imageFormat);
  return $StructureInspected; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to scan one file against the definitions, classify it & inspect its structure.
// / The definitions file in use, a copy of it & the report file in use are never scanned, because each quotes signatures by design.
// / A file that cannot be opened or read in full is reported as incomplete, & the scan moves on to the next file.
// / A fault inside the inspection of one file is reported against that file as incomplete, & the scan moves on as well.
// / The memory limit is applied to the interpreter before the scan starts & is not used here.
// / Returns the check, the infected count, the file count & the suspicious count, in that order.
function virusCheck($file, $Defs, $DefData, $MemoryLimit, $ChunkSize, $DefsLongestSignature) {
  // / Set variables.
  global $Infected, $Suspicious, $FileCount, $IncompleteCount, $HandlerFileCount, $CodeFileCount, $MemberContext;
  global $DefsRealPath, $ReportRealPath, $DefsFileSize, $Classifiers, $ClassifiersLoaded, $ClassifyContent, $InspectArchivedContent;
  $CheckComplete = FALSE;
  $fileDetections = $handlerHits = $languageHits = $fileStat = array();
  $targetPath = realpath($file);
  $displayFile = printablePath($file);
  $skipReason = $fileHeader = $fileExtension = $containerFormat = $imageFormat = $contentLanguage = $md5Hash = $sha1Hash = $sha256Hash = '';
  $handle = $contentRead = $containerIsWalked = FALSE;
  $fileSize = 0;
  $classifyPlan = array('mode' => '', 'candidates' => array(), 'scope' => array(), 'language' => '');
  $inspection = newInspectionState(array());
  $inspectionFault = NULL;
  $MemberContext = '';
  if ($targetPath === FALSE or !is_file($file)) $skipReason = 'a path that is not a file';
  else {
    if ($targetPath === $DefsRealPath) $skipReason = 'the definitions file in use';
    if ($targetPath === $ReportRealPath) $skipReason = 'the report file in use';
    // / A copy of the definitions is as large as the definitions, so only a file of that size is hashed to find out.
    if ($skipReason === '' && $DefsFileSize > 0 && @filesize($file) === $DefsFileSize) if (@hash_file('sha256', $file) === $DefData) $skipReason = 'a copy of the definitions file in use'; }
  if ($skipReason !== '') processOutput('Skipped '.$skipReason.':  '.$displayFile, 'log', 0, FALSE, FALSE, FALSE);
  else {
    $FileCount++;
    processOutput('Scanning file:  '.$displayFile, 'log', 0, TRUE, FALSE, FALSE);
    $handle = @fopen($file, 'rb');
    if ($handle === FALSE) {
      processOutput('Cannot open a file for reading, it was skipped:  '.$displayFile, 'error', 610, TRUE, FALSE, FALSE);
      noteIncomplete($inspection, 'the file could not be opened'); }
    else {
      try {
        $fileStat = fstat($handle);
        $fileSize = (is_array($fileStat) && isset($fileStat['size'])) ? intval($fileStat['size']) : 0;
        // / The opening bytes identify an image or a container, whatever the file is named.
        $fileHeader = strval(fread($handle, 512));
        $fileExtension = fileExtensionOf($file);
        $containerFormat = containerFormatOf($fileHeader);
        $imageFormat = imageFormatOf($fileHeader);
        $containerIsWalked = ($containerFormat !== '' && $InspectArchivedContent);
        if ($ClassifyContent && $ClassifiersLoaded) $classifyPlan = planClassification($fileExtension, $fileHeader, $imageFormat);
        $inspection['types'] = $classifyPlan['candidates'];
        if ($fileSize > $ChunkSize) processOutput('Chunking file:  '.$displayFile, 'log', 0, TRUE, FALSE, FALSE);
        list($contentRead, $md5Hash, $sha1Hash, $sha256Hash) = scanFileContent($file, $handle, $fileSize, $Defs, $ChunkSize, $DefsLongestSignature, $classifyPlan, $imageFormat, $containerIsWalked, $fileDetections, $handlerHits, $languageHits);
        matchNameIndicators($file, $Defs, $fileDetections);
        // / A hash only means something when the whole file was read.
        if ($contentRead) matchHashSignatures($file, $md5Hash, $sha1Hash, $sha256Hash, $Defs, $fileDetections);
        else {
          processOutput('Cannot read the whole of a file, results may be incomplete:  '.$displayFile, 'error', 600, TRUE, TRUE, FALSE);
          noteIncomplete($inspection, 'the file could not be read in full'); }
        inspectFileStructure($file, $handle, $fileSize, $fileExtension, $containerFormat, $imageFormat, $fileDetections, $handlerHits, $inspection); }
      catch (Throwable $inspectionFault) {
        $MemberContext = '';
        processOutput('Cannot finish inspecting a file, the interpreter said '.printableName(collapseToOneLine($inspectionFault->getMessage())).' on line '.$inspectionFault->getLine().':  '.$displayFile, 'error', 640, TRUE, TRUE, FALSE);
        noteIncomplete($inspection, 'the inspection stopped on an internal fault'); }
      fclose($handle); }
    // / Say when part of a file could not be inspected, & why.
    if (count($inspection['incomplete']) > 0) {
      $IncompleteCount++;
      processOutput('Incomplete: '.$displayFile.' ('.implode('; ', $inspection['incomplete']).')', 'log', 0, TRUE, TRUE, FALSE); }
    // / A file is counted once, under the strongest verdict that any of its detections carried.
    if (in_array('high', $fileDetections, TRUE)) $Infected++;
    else { if (count($fileDetections) > 0) $Suspicious++; }
    // / An extension that names a language settles what a file is, & a file with an unclaimed extension is decided by the signatures it carries.
    if ($classifyPlan['mode'] === 'language') $contentLanguage = $classifyPlan['language'];
    if ($classifyPlan['mode'] === 'unknown') $contentLanguage = resolveContentLanguage($languageHits, $Classifiers);
    // / A file that holds code is reported as code & is not reported for its handlers.
    if ($contentLanguage !== '') {
      $CodeFileCount++;
      processOutput('Classified: '.$displayFile.' (File may contain executable code - Detected Language: '.$contentLanguage.')', 'log', 0, TRUE, TRUE, FALSE); }
    else {
      if (count($handlerHits) > 0) {
        $HandlerFileCount++;
        processOutput('Classified: '.$displayFile.' (File may contain file or URL handlers - '.describeHandlerHits($handlerHits).')', 'log', 0, TRUE, TRUE, FALSE); } } }
  $CheckComplete = TRUE;
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($fileDetections, $handlerHits, $languageHits, $fileStat, $targetPath, $displayFile, $skipReason, $fileHeader, $fileExtension, $containerFormat, $imageFormat, $contentLanguage, $md5Hash, $sha1Hash, $sha256Hash, $handle, $contentRead, $containerIsWalked, $fileSize, $classifyPlan, $inspection, $inspectionFault, $file);
  return array($CheckComplete, $Infected, $FileCount, $Suspicious); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to walk a folder & hand every file in it to the checker.
// / A path that is a file is handed to the checker as it is.
// / A symbolic link is skipped unless links are followed, & a folder is not opened past the maximum depth.
// / Returns the scan, the folder count, the file count, the infected count & the suspicious count, in that order.
function fileScan($folder, $Defs, $DefData, $MemoryLimit, $ChunkSize, $Recursion, $DefsLongestSignature, $currentDepth) {
  // / Set variables.
  global $SEP, $FileCount, $DirCount, $Infected, $Suspicious, $MaxScanDepth, $FollowSymlinks;
  $ScanComplete = FALSE;
  $folderEntries = FALSE;
  $entryName = $entryPath = '';
  $checkComplete = $childComplete = FALSE;
  $displayFolder = printablePath($folder);
  if (!file_exists($folder)) processOutput('The requested scan path no longer exists:  '.$displayFolder, 'error', 300, TRUE, TRUE, FALSE);
  else {
    if (!is_dir($folder)) list($ScanComplete, $Infected, $FileCount, $Suspicious) = virusCheck($folder, $Defs, $DefData, $MemoryLimit, $ChunkSize, $DefsLongestSignature);
    else {
      $DirCount++;
      $ScanComplete = TRUE;
      processOutput('Scanning folder:  '.$displayFolder, 'log', 0, TRUE, FALSE, FALSE);
      $folderEntries = @scandir($folder);
      // / A folder that cannot be read is skipped, & the rest of the scan goes on.
      if (!is_array($folderEntries)) processOutput('Cannot read the contents of a folder, it was skipped:  '.$displayFolder, 'warning', 0, TRUE, TRUE, FALSE);
      else foreach ($folderEntries as $entryName) if ($entryName !== '' && $entryName !== '.' && $entryName !== '..') {
        redeclare($entryPath, str_replace($SEP.$SEP, $SEP, $folder.$SEP.$entryName));
        if (is_link($entryPath) && !$FollowSymlinks) processOutput('Skipping a symbolic link:  '.printablePath($entryPath), 'log', 0, FALSE, FALSE, FALSE);
        else {
          if (!is_dir($entryPath)) {
            list($checkComplete, $Infected, $FileCount, $Suspicious) = virusCheck($entryPath, $Defs, $DefData, $MemoryLimit, $ChunkSize, $DefsLongestSignature);
            if (!$checkComplete) $ScanComplete = FALSE; }
          else {
            if ($Recursion) {
              if ($currentDepth >= $MaxScanDepth) processOutput('Reached the maximum scan depth, this folder was not opened:  '.printablePath($entryPath), 'warning', 0, TRUE, TRUE, FALSE);
              else {
                list($childComplete, $DirCount, $FileCount, $Infected, $Suspicious) = fileScan($entryPath, $Defs, $DefData, $MemoryLimit, $ChunkSize, $Recursion, $DefsLongestSignature, $currentDepth + 1);
                if (!$childComplete) $ScanComplete = FALSE; } } } } } } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($folderEntries, $entryName, $entryPath, $checkComplete, $childComplete, $displayFolder, $folder, $DefData, $currentDepth);
  return array($ScanComplete, $DirCount, $FileCount, $Infected, $Suspicious); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / The main logic of the program.

// / Verify the installation.
list($InstallationVerified, $Version, $RequiredConfigVersion, $VerificationFault) = verifyInstallation();
if (!$InstallationVerified) {
  echo 'ERROR!!! ScanCore-1, Cannot verify the ScanCore installation!  '.$VerificationFault.PHP_EOL;
  exit(1); }

// / Take the arguments the interpreter supplied, or none where it supplied none.
$ArgumentList = (isset($argv) && is_array($argv)) ? $argv : array();

// / Load the configuration file.
list ($ConfigLoaded, $DefsExist, $ConfigFilePath, $VersionsMatch) = loadConfig($RequiredConfigVersion, $ArgumentList);
if (!$ConfigLoaded) {
  echo 'ERROR!!! ScanCore-2, Cannot load the configuration file located at:  '.$ConfigFilePath.$EOL;
  echo printableName($ConfigLoadFault).$EOL;
  exit(1); }
if (!$VersionsMatch) {
  echo 'ERROR!!! ScanCore-4, Cannot verify the configuration file version located at:  '.$ConfigFilePath.$EOL;
  echo 'The file reports '.((isset($ConfigVersion) && is_string($ConfigVersion) && $ConfigVersion !== '') ? printableName($ConfigVersion) : 'no version').' & this core accepts '.$RequiredConfigVersion.' or newer.'.$EOL;
  exit(1); }
if (!$DefsExist) {
  echo 'ERROR!!! ScanCore-3, Cannot verify the definitions file located at:  '.$DefsFile.$EOL;
  exit(1); }

// / Work out who owns anything that is created, before the first entry is written to the report.
// / Nothing is said about it yet, because the command line may still name another owner.
resolveOwnership(FALSE);

// / Report anything the configuration file got wrong.
foreach ($ConfigFaults as $ConfigFault) processOutput($ConfigFault, 'warning', 0, TRUE, FALSE, FALSE);

// / Process supplied command-line arguments.
list($ArgsParsed, $PerformScan, $PathToScan, $MemoryLimit, $ChunkSize, $Debug, $Verbose, $Recursion, $ReportFile, $MaxLogSize, $PerformDefUpdate, $PerformAppUpdate) = parseArgs($ArgumentList);
if (!$ArgsParsed) processOutput('Cannot verify supplied arguments!', 'error', 6, TRUE, TRUE, TRUE);
// / A request for the version or for help is answered on the console, & nothing about it is written to the report.
$OperationRequested = ($PerformScan or $PerformDefUpdate or $PerformAppUpdate);
processOutput('Verified supplied arguments.', 'log', 0, $OperationRequested, FALSE, FALSE);

// / Work out the owner again, now that the command line has been read, & say who it is.
resolveOwnership($OperationRequested);

// / Hand the interpreter the memory limit that the scan was sized to work within.
if (@ini_set('memory_limit', strval($MemoryLimit)) === FALSE) processOutput('Cannot apply the configured MemoryLimit to the interpreter.', 'warning', 0, TRUE, FALSE, FALSE);

// / Perform a definition update, when required.
if ($PerformDefUpdate) {
  list($UpdateDefinitionsComplete, $UpdateDefinitionsErrors) = updateDefinitions();
  if (!$UpdateDefinitionsComplete) processOutput('Cannot install definition update!', 'error', 7, TRUE, TRUE, TRUE);
  else processOutput('Installed definition update.', 'log', 0, TRUE, TRUE, FALSE); }

// / Perform an application update, when required.
if ($PerformAppUpdate) {
  list($UpdateApplicationComplete, $UpdateApplicationErrors) = updateApplication();
  if (!$UpdateApplicationComplete) processOutput('Cannot install application update!', 'error', 8, TRUE, TRUE, TRUE);
  else processOutput('Installed application update. Please open '.$DefaultConfigFile.' & validate configuration entries.', 'log', 0, TRUE, TRUE, FALSE); }

// / Perform scanning operations, when required.
if ($PerformScan) {
  // / Load the virus definitions.
  list($DefsLoaded, $Defs, $DefData, $DefsLongestSignature) = loadDefs($DefsFile);
  if (!$DefsLoaded) processOutput('Cannot load definitions!', 'error', 9, TRUE, TRUE, TRUE);
  else processOutput('Loaded definitions.', 'log', 0, TRUE, FALSE, FALSE);

  // / Load the classifier definitions, which also hold the embedded code signatures that image inspection uses.
  // / A scan goes ahead without them, with classification switched off.
  $ClassifiersLoaded = FALSE;
  $Classifiers = array('types' => array(), 'languages' => array(), 'handlers' => array(), 'magic' => array(), 'embeds' => array());
  $ClassifierLongestToken = 0;
  if ($ClassifyContent or $InspectImages) list($ClassifiersLoaded, $Classifiers, $ClassifierLongestToken) = loadClassifiers($ClassifierFile);
  if ($ClassifyContent && !$ClassifiersLoaded) processOutput('Content classification is disabled because no classifier definitions could be loaded.', 'warning', 0, TRUE, FALSE, FALSE);

  // / Resolve the definitions file & the report file, which are never scanned.
  $DefsRealPath = realpath($DefsFile);
  $ReportRealPath = realpath($ReportFile);
  $DefsFileSize = intval(@filesize($DefsFile));

  // / Start the scanner!
  list($ScanComplete, $DirCount, $FileCount, $Infected, $Suspicious) = fileScan($PathToScan, $Defs, $DefData, $MemoryLimit, $ChunkSize, $Recursion, $DefsLongestSignature, 0);
  if (!$ScanComplete) processOutput('Cannot complete the requested scan!', 'error', 10, TRUE, TRUE, TRUE);
  else {
    // / The summary is one sentence to a line, & the infected count is the last line.
    processOutput('Scanned '.countWithNoun($FileCount, 'file', 'files').' in '.countWithNoun($DirCount, 'folder', 'folders').'.', 'log', 0, TRUE, TRUE, FALSE);
    if ($ClassifyContent && $ClassifiersLoaded) {
      processOutput('Found '.countWithNoun($HandlerFileCount, 'file', 'files').' that may contain file or URL handlers.', 'log', 0, TRUE, TRUE, FALSE);
      processOutput('Found '.countWithNoun($CodeFileCount, 'file', 'files').' that may contain executable code.', 'log', 0, TRUE, TRUE, FALSE); }
    processOutput('Found '.countWithNoun($IncompleteCount, 'file', 'files').' that could not be fully inspected.', 'log', 0, TRUE, TRUE, FALSE);
    if ($ReportSuspicious) processOutput('Found '.countWithNoun($Suspicious, 'suspicious file', 'suspicious files').'.', 'log', 0, TRUE, TRUE, FALSE);
    processOutput('Found '.countWithNoun($Infected, 'infected file', 'infected files').'.', 'log', 0, TRUE, TRUE, FALSE); } }
// / -----------------------------------------------------------------------------------
