<?php
/*
HonestRepair Diablo Engine  -  Core
https://www.HonestRepair.net
https://github.com/zelon88

Licensed Under GNU GPLv3
https://www.gnu.org/licenses/gpl-3.0.html

Author: Justin Grimes
Date: 4/10/2022
<3 Open-Source

This is the primary Core file for the Diablo Web Application Engine.

The Diablo engine is a privacy-centric, nosql, lightweight, multipurpose web application platform
with database-less user authentication, user management, & automatic updates with configurable sources. 
If PHP is configured for cookieless sessions, it's also completely cookieless.

This version of the Diablo engine was customized for HRCloud3, but it's code is modular and reusable.

General Code Conventions Utilized:
 -Paths are either relative to the installation directory or specified in config.php under libraries.
 -Memory that isn't used at the end of a function is to be manually nulled and unset.
 -Conditionals that only control simple assignments or one-liners are condensed to one line.
 -Lower case variables denote severely limited scope (disposable).
 -Upper case variables have scope that transcends functions. Either as global input or a return value.
 -Don't let the interpreter see code it isn't going to use.
 -Brackets aren't special enough to go on their own line.
 -No database stuff anywhere.
 -No cookie stuff anywhere.
 -No frameworks.
*/

// / ----------------------------------------------------------------------------------
// / Prepare the execution environment.

// / Reset PHP's time limit for execution.
set_time_limit(0);

// / Make sure there is a session started.
if (session_status() == PHP_SESSION_NONE) session_start();

// / Determine the root path where the application is installed and where it is running from.
$RootPath = '';
if (!file_exists('config.php')) { 
  // / If we can't use relative paths, check the server document root directory instead.
  $RootPath = $_SERVER['DOCUMENT_ROOT'].DIRECTORY_SEPARATOR;
  if (!file_exists($RootPath.'config.php')) die('ERROR!!! 0, Could not process the Configuration file (config.php)!'.PHP_EOL); }

// / Load the config.php file.
require_once ($RootPath.'config.php');
$ConfigIsLoaded = TRUE; 

// / Stop the application if $MaintenanceMode is enabled.
if ($MaintenanceMode) die('The requested application is currently unavailable due to maintenance.'.PHP_EOL); 
// / ----------------------------------------------------------------------------------

// / ----------------------------------------------------------------------------------
// / Perform sanity checks to verify the environment is suitable for running.

// / Detemine the version of PHP in use to run the application.
// / Any PHP version earlier than 7.0 IS STRICTLY NOT SUPPORTED!!!
// / Specifically, PHP versions earlier than 7.0 require the list () functions used to be unserialized. 
// / If you run this application on a PHP version earlier than 7.0 you may experience extremely bizarre or even dangerous behavior.
// / PLEASE DO NOT RUN THIS APPLICATION ON ANYTHING EARLIER THAN PHP 7.0!!! 
// / HONESTREPAIR ASSUMES NO LIABILITY FOR USING THIS SOFTWARE!!!
if (version_compare(PHP_VERSION, '7.0.0') <= 0) die('<a class="errorMessage">ERROR!!! 2A, This application is NOT compatible with PHP versions earlier than 7.0. Running this application on unsupported PHP versions WILL cause unexpected behavior!</a>'.PHP_EOL); 

// / Determine the operating system in use to run the application.
// / Any version of Windows IS STRICTLY NOT SUPPORTED!!!
// / Specifically, only Debian-based Linux distros are supported.
// / PLEASE DO NOT RUN THIS APPLICATION ON A WINDOWS OPERATING SYSTEM!!! 
// / HONESTREPAIR ASSUMES NO LIABILITY FOR USING THIS SOFTWARE!!!
if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') die('<a class="errorMessage">ERROR!!! 2B, This application is NOT compatible with the Windows Operating System. Running this application on unsupported operating systems WILL cause unexpected behavior!</a>'.PHP_EOL); 
// / ----------------------------------------------------------------------------------

// / ----------------------------------------------------------------------------------
// / The following code sets the functions for the session.

// / A function for sanitizing input strings with varying degrees of tolerance.
// / Filters a given string of | \ ~ # [ ] ( ) { } ; : $ ! # ^ & % @ > * < " / '
// / This function will replace any of the above specified charcters with NOTHING. No character at all. An empty string.
// / Set $strict to TRUE to also filter out backslash characters as well. Example:  /
function sanitize($Variable, $Strict) { 
  // / Set variables.  
  $VariableIsSanitized = TRUE;
  if (!is_bool($Strict)) $Strict = TRUE; 
  if (!is_string($Variable)) $VariableIsSanitized = FALSE;
  else { 
    // / Note that when $strict is TRUE we also filter out backslashes. Not good if you're filtering a URL or path.
    if ($Strict) $Variable = trim(str_replace(str_split('|\\~#[](){};:$!#^&%@>*<"\'/'), '', $Variable));
    if (!$Strict) $Variable = trim(str_replace(str_split('|\\[](){};"\''), '', $Variable)); }
  return array($Variable, $VariableIsSanitized); }

// / A function to set the date and time for internal logic like file cleanup.
function verifyDate() { 
// / Set variables. 
  // / Set the raw time, in seconds since the Unix epoch.
  $RawTime = time();
  // / Set an abbreviated date that can be used in filenames.
  $Date = date("m-d-y");
  // / Set a full human readable date that can be appended to individual log lines.
  $Time = date("F j, Y, g:i a"); 
  // / An integer representing the current minute.
  $Minute = intval(date('i'));
  $LastMinute = $Minute - 1;
  $NextMinute = $Minute + 1;
  // / Detect & accomodate rollover minutes.
  if ($LastMinute <= 1) $LastMinute = 60;
  if ($NextMinute > 60) $NextMinute = 1;
  // / Set a short & unique request ID to help differentiate requests in logfiles.
  $RequestID = crc32($_SERVER['REMOTE_ADDR'] . $_SERVER['REQUEST_TIME_FLOAT'] . $_SERVER['REMOTE_PORT']);
  return array($RawTime, $Date, $Time, $Minute, $LastMinute, $RequestID); }

// / A function to generate and validate the operational environment for the Diablo Engine.
function verifyInstallation() { 
  // / Set variables. 
  global $Date, $Time, $Salts, $RootPath;
  if (!PHP_EOL) define("PHP_EOL", "\n");
  if (!FILE_APPEND) define("FILE_APPEND", 8);
  if (!DIRECTORY_SEPARATOR) define("DIRECTORY_SEPARATOR", '/');
  $dirCheck = $indexCheck = $dirExists = $indexExists = $logCheck = $cacheCheck = TRUE;
  $requiredDirs = array('Applications', 'Widgets', 'Logs',  'Cache', 'Cache'.DIRECTORY_SEPARATOR.'Data');
  $InstallationIsVerified = FALSE;
  // / For servers with unprotected directory roots, we must verify (at minimum) that a local index file exists to catch unwanted traversal.
  if (!file_exists('index.html')) $indexCheck = FALSE;
  // / Iterate through the $requiredDirs hard-coded (in this function, under "Set variables" section above).
  foreach ($requiredDirs as $requiredDir) { 
    $requiredDir = $RootPath.$requiredDir;
    // / If a $requiredDir doesn't exist, we create it.
    if (!is_dir($requiredDir)) $dirExists = mkdir($requiredDir, 0755);
    // / A sanity check to ensure the directory was actually created.
    if (!$dirExists) $dirCheck = FALSE;
    // / Copy an index file into the newly created directory to enable directory root protection the old fashioned way.
    if (!file_exists($requiredDir.DIRECTORY_SEPARATOR.'index.html')) $indexExists = copy('index.html', $requiredDir.DIRECTORY_SEPARATOR.'index.html');
    // / A sanity check to ensure that an index file was created in the newly created directory.
    if (!$indexExists) $indexCheck = FALSE; }
  // / Create a unique identifier for today's $LogFile.
  $logHash = substr(hash('sha256', $Salts[0].hash('sha256', $Date.$Salts[1].$Salts[2].$Salts[3])), 0, 7);
  // / Define today's $LogFile.
  $LogFile = $RootPath.'Logs'.DIRECTORY_SEPARATOR.$Date.'_'.$logHash.'.log';
  // / Create today's $LogFile if it doesn't exist yet.
  if (!file_exists($LogFile)) $logCheck = file_put_contents($LogFile, 'OP-Act: '.$Time.', Created a log file, "'.$LogFile.'".'.PHP_EOL);
  // / Create a unique identifier for the cache file.
  $CacheFile = $RootPath.'Cache'.DIRECTORY_SEPARATOR.'Cache-'.hash('sha256',$Salts[0].'CACHE').'.php';
  // / If no cache file exists yet (first run) we create one and write the $PostConfigUsers to it. 
  if (!file_exists($CacheFile)) $cacheCheck = file_put_contents($CacheFile, '<?php'.PHP_EOL);
  // / Make sure all sanity checks passed.
  if ($dirCheck && $indexCheck && $logCheck && $cacheCheck) $InstallationIsVerified = TRUE;
  // / Clean up unneeded memory.
  $dirCheck = $indexCheck = $logCheck = $cacheCheck = $requiredDirs = $requiredDir = $dirExists = $indexExists = $logHash = NULL;
  unset($dirCheck, $indexCheck, $logCheck, $cacheCheck, $requiredDirs, $requiredDir, $dirExists, $indexExists, $logHash);
  return array($LogFile, $CacheFile, $InstallationIsVerified); }

// / A function to verify that the connection is taking place over HTTPS.
// / $ConnectionIsVerified returns TRUE if the connection should be allowed according to the settings defined in config.php.
// / $ConnectionIsVerified returns FALSE if the connection should be not allowed according to the settings defined in config.php.
// / $ConnectionIsSecure returns TRUE if the connection is secured over HTTPS.
// / $ConnectionIsSecure returns FALSE if the connection is not secured over HTTPS.  
function verifyConnection() { 
  global $ForceHTTPS;
  $ConnectionIsVerified = $ConnectionIsSecure = FALSE;
  // / Determine if the connection is encrypted.
  if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') $ConnectionIsSecure = TRUE; 
  // / Set the $ConnectionIsVerified flag depending on whether or not the connection should be allowed according to the settings defined in config.php.
  if ($ForceHTTPS && $ConnectionIsSecure) $ConnectionIsVerified = TRUE;
  if (!$ForceHTTPS) $ConnectionIsVerified = TRUE;
  return array($ConnectionIsVerified, $ConnectionIsSecure);  }

// / A function to initialize global variables to default values.
function initializeVariables() { 
  // / Set variables. 
  global $RootPath, $CoreLoadedSuccessfully, $VersionsMatch, $CacheIsLoaded, $SessionIsVerified, $GlobalsAreVerified, $TokensAreValid, $TokenIsValid, $PasswordIsCorrect, $AuthIsComplete, $LibrariesAreLoaded, $LibraryDataIsLoaded, $UserLogsExists, $UserCacheExists, $NotificationsFileExists, $UserOptions, $UserCacheArrayData, $UserCacheRequiredOptions, $ServerToken, $OldServerToken, $UsernameAvailabilityRequest, $UserIsAdmin, $UsernameAvailabilityResponseNeeded, $NewAccountRequest, $DesiredUsername, $NewUserEmail, $AgreeToTerms, $NewUserPassword, $NewUserPasswordConfirm, $UserIsEnabled, $DependencyDir, $ApplicationsDir, $PHPMailerLoaded;
  // / Initialize default values for required variables.  
  $UserOptionCount = $ucCount = 0;
  $DesiredUsername = '';
  // / Initialize all required sanity checks to FALSE.
  $CoreLoadedSuccessfully = $VersionsMatch = $CacheIsLoaded = $SessionIsVerified = $GlobalsAreVerified = $TokensAreValid = $TokenIsValid = $PasswordIsCorrect = $AuthIsComplete = $LibrariesAreLoaded = $LibraryDataIsLoaded = $UserLogsExists = $UserCacheExists = $NotificationsFileExists = $ServerToken = $OldServerToken = $key = $UsernameAvailabilityRequest = $UserIsAdmin = $UsernameAvailabilityResponseNeeded = $NewAccountRequest = $NewUserEmail = $AgreeToTerms = $NewUserPassword = $NewUserPasswordConfirm = $UserIsEnabled = $PHPMailerLoaded = FALSE;
  // / Initialize all directory values.
  $DependencyDir = $RootPath.'Dependencies'.DIRECTORY_SEPARATOR;
  $ApplicationsDir = $RootPath.'Applications'.DIRECTORY_SEPARATOR;
  // / Initialize all required user options to NULL.
  list ($UserCacheArrayData, $UserCacheRequiredOptions) = generateDefaultUserCacheData();
  $ucCount = count($UserCacheRequiredOptions);
  foreach ($UserCacheRequiredOptions as $key => $ucItem ) { 
    $UserOptionCount++;
    $UserOptions[$key] = $ucItem; }
  // / Ensure sanity checks passed.
  if (intval($ucCount) === intval($UserOptionCount)) $InitializationComplete = TRUE; 
  // / Clean up unneeded memory.
  $ucItem = $ucCount = $key = NULL;
  unset($ucItem, $ucCount, $key);
  return array($InitializationComplete, $UserOptionCount); }

// / A function to generate useful, consistent, and easily repeatable error messages.
// / The $ErrorNumber should be an integer representing the unique error identifier.
// / If the $ErrorNumber is not an integer, it will be replaced with '0'.
// / The $ErrorMessage should be a string containing a brief description of the error.
// / Set $LogToUser to TRUE to submit the entry to the users logfile in addition to the default system logfile.
// / Set $LogToUser to FALSE to prevent the entry from being entered into the users logfile.
// / If $LogToUser is set to FALSE, the entry will still be logged in the default system logfile specified in config.php.
function dieGracefully($ErrorNumber, $ErrorMessage, $LogToUser) { 
  // / Set variables. 
  global $LogFile, $UserLogFile, $Time, $UserLogsExist, $RequestID;
  // / Perform a sanity check on the $ErrorNumber. 
  if (!is_numeric($ErrorNumber)) $ErrorNumber = 0;
  list ($ErrorOutput, $variableIsSanitized) = sanitize('ERROR!!! '.$ErrorNumber.', '.$Time.', '.$RequestID.', '.$ErrorMessage, FALSE);
  // / Write the primary log file. Note that we don't care about success or failure because we're about to kill the script regardless.
  file_put_contents($LogFile, $ErrorOutput.PHP_EOL, FILE_APPEND);
  if ($LogToUser && $UserLogsExist) file_put_contents($UserLogFile, $ErrorOutput, FILE_APPEND);
  die('<a class="errorMessage">'.$ErrorOutput.'</a>'); } 

// / A function to generate useful, consistent, and easily repeatable log messages.
// / The $EntryText should be a string containing a brief description of the log entry.
// / Set $LogToUser to TRUE to submit the entry to the users logfile in addition to the default system logfile.
// / Set $LogToUser to FALSE to prevent the entry from being entered into the users logfile.
// / If $LogToUser is set to FALSE, the entry will still be logged in the default system logfile specified in config.php.
// / Returns TRUE if all write operations succeeded.
// / Returns FALSE if any required write operations failed.
function logEntry($EntryText, $LogToUser) { 
  // / Set variables. 
  global $LogFile, $UserLogFile, $Time, $UserLogsExist, $RequestID;
  $logWrittenA = $logWrittenB = FALSE;
  // / Format the actual log message.
  list ($EntryOutput, $variableIsSanitized) = sanitize('OP-Act: '.$Time.', '.$RequestID.', '.$EntryText, FALSE);
  // / Write the actual log file.
  $logWrittenA = file_put_contents($LogFile, $EntryOutput.PHP_EOL, FILE_APPEND);
  if ($LogToUser && $UserLogsExist) $logWrittenB = file_put_contents($UserLogFile, $EntryOutput, FILE_APPEND);
  // / Check if either operation failed.
  if (!$logWrittenA or !$logWrittenB or $variableIsSanitized) $LogWritten = FALSE;
  // / Clean up unneeded memory.
  $logWrittenA = $logWrittenB = $variableIsSanitized = NULL;
  unset ($logWrittenA, $logWrittenB, $variableIsSanitized);
  return $LogWritten; } 

// / A function to load the system cache, which contains the master user list.
// / Cache files are stored as .php files and cache data is stored as an array. This ensures the files
// / cannot simply be viewed with a browser to reveal sensitive content. The data must be programatically
// / displayed or opened locally in a text editor.
// / Outputs a completely populated $Users array.
function loadCache() { 
  // / Set variables. 
  global $Users, $CacheFile, $Salts;
  // / Load the cache file containing the rest of the users.
  require ($CacheFile);
  // / Combine the hard coded users from the config file with the rest of the users from the cache file.
  if (isset($PostConfigUsers)) $Users = array_merge($PostConfigUsers, $Users);
  $CacheIsLoaded = TRUE;
  // / Return an array of all users as well as a boolean to tell us if the function succeeded.
  return array($Users, $CacheIsLoaded); }

// / A function to load core files.
// / Accepts either an array of cores or a single string.
// / If input is an array, CoresLoaded output is an array. If input is a string, CoresLoaded output is a string.
function loadCores($coresToLoad) { 
  // / Set variables. 
  global $AvailableCores, $ConfigIsLoaded, $RootPath; 
  $error = FALSE;
  $CoresLoaded = array();
  $CoresAreLoaded = TRUE;
  // / Check if $coresToLoad is an array.
  if (is_array($coresToLoad)) { 
    // / Loop through each core in the array element.
    foreach ($coresToLoad as $coreToLoad) { 
      // / Determine what the name of the specified core should be.
      $coreFile = $RootPath.strtolower($coreToLoad).'Core.php';
      // / Check that the specified core is available to load.
      if (file_exists($coreFile) && in_array(strtoupper($coreToLoad), $AvailableCores)) { 
        // / Load the actual core file.
        require($coreFile);
        // / Add the recently loaded core to the $CoresLoaded array.
        $CoresLoaded[$coresToLoad] = $coresToLoad; }
      else $error = TRUE; } }
  // / Check if $coresToLoad is a string.
  if (is_string($coresToLoad)) {
    // / Determine what the name of the specified core should be. 
    $coreFile = $RootPath.strtolower($coresToLoad).'Core.php';
    // / Check that the specified core is available to load.
    if (file_exists($coreFile) && in_array(strtoupper($coresToLoad), $AvailableCores)) { 
      // / Load the actual core file.
      require($coreFile);
      // / Add the recently loaded core to the $CoresLoaded array.
      $CoresLoaded[$coresToLoad] = $coresToLoad; }
    else $error = TRUE; }
  // / If the function encountered errors we throw the '$CoresAreLoaded' variable to FALSE.
  if ($error == TRUE) $CoresAreLoaded = FALSE;
  // / Clean up unneeded memory.
  $coresToLoad  = $coreFile = $coreToLoad = $error = NULL;
  unset($coresToLoad, $coreFile, $coreToLoad, $error);
  // / Return an array of the cores that are currently loaded as well as a boolean to tell us if the function succeeded.
  return array($CoresLoaded, $CoresAreLoaded); }

// / A function to check that the platform is running a consistent version.
// / Checks that the $EngineVersionInfo variable in 'versionInfo.php' matches the $EngineVersion variable in 'compatibilityCore.php'.
function checkVersionInfo() { 
  // / Set variables.
  global $CoresLoaded, $EngineVersion, $RootPath;
  $VersionsMatch = FALSE;
  // / Check that the Compatibility Core is loaded.
  if (in_array('COMPATIBILITY', $CoresLoaded));
  // / If for any reason the Compatibility Core is not loaded we will skip this entire version check.
  else {
    logEntry('Compatibility Core disabled. Skipping version check.', FALSE); 
    $VersionMatch = TRUE; }
  // / If the Compatibility Core is enabled we will also retrieve the '$EngineVersionInfo' variable from versionInfo.php to compare against.
  if (file_exists($RootPath.'versionInfo.php')) require($RootPath.'versionInfo.php');
  // / Now that we've gathered version information from two sources within the engine, we compare them.
  if (isset($EngineVersion) && isset($EngineVersionInfo)) if ($EngineVersion === $EngineVersionInfo) $VersionsMatch = TRUE; 
  // / Return TRUE if the both version strings match. Return FALSE if the two versions strings do not match.
  return $VersionsMatch; }

// / A function to detect information helpful for identifying a client.
function detectClientInfo() { 
  $HashedUserAgent = hash('sha256', $_SERVER['HTTP_USER_AGENT']);
  $ClientIP = $_SERVER['REMOTE_ADDR'];
 return array($HashedUserAgent, $ClientIP); }

// / A function to determine if the supplied session is valid.
function verifySession($SessionIDInput, $UserInput, $ClientTokenInput, $OldClientToken, $ServerToken, $OldServerToken) { 
  // / Set variables. 
  global $Users, $Salts, $Minute, $LastMinute;
  // / Set variables.
  $SessionIsVerified = $SessionID = $oldSessionID = $oldSessionIDA = $user = FALSE;
  // / Check that required inputs are valid.
  if ($SessionIDInput !== FALSE && $UserInput !== FALSE && $ClientTokenInput !== FALSE) { 
    // / Loop through the userlist and look for the specified username.
    foreach ($Users as $user) { 
      // / If the username is found we stop iterating through the user list.
      if ($UserInput === $user[1]) { 
        // / Create a series of valid session IDs.
        $UserID = $user[0];
        $SessionID = hash('sha256', $Minute.$Salts[0].$ClientTokenInput.$user[3].$Salts[1].$ServerToken.$Salts[2].$user[1]);
        $oldSessionID = hash('sha256', $Minute.$Salts[0].$OldClientToken.$user[3].$Salts[1].$ServerToken.$Salts[2].$user[1]);
        $oldSessionIDA = hash('sha256', $LastMinute.$Salts[0].$OldClientToken.$user[3].$Salts[1].$OldServerToken.$Salts[2].$user[1]);
        break; } } }
  // / Build an array of valid session IDs to compare against the one supplied by the user.
  $validSessionArray = array($SessionID, $oldSessionID, $oldSessionIDA);
  // / Perform the check to see if the session ID is valid.
  if (in_array($SessionIDInput, $validSessionArray)) $SessionIsVerified = TRUE;
  // / Clean up unneeded memory.
  $user = $validSessionArray = $oldSessionID = $oldSessionIDA = NULL; 
  unset($user, $validSessionArray, $oldSessionID, $oldSessionIDA);
  return array($UserID, $SessionID, $SessionIsVerified); } 

// / A function to validate and sanitize requried session and POST variables.
function verifyGlobals() { 
  // / Set variables. 
  $variableIsSanitized = TRUE; 
  $SessionID = $GlobalsAreVerified = $RequestTokens = $SessionType = $SessionIsVerified = $UserID = $UsernameAvailabilityRequest = $UsernameAvailabilityResponseNeeded = $NewAccountRequest = $NewUserEmail = $AgreeToTerms = $NewUserPassword = $NewUserPasswordConfirm = $ForgotUsernameRequest = FALSE;
  $UserDir = $DesiredUsername = $ForgotUserEmail = '';
  // / This code triggers the Username Availability Request process.
  // / Username Availability Requests are expensive for the server to perform.
  // / They also leak which usernames are taken, which is a tool used by malicious actors to enumerate a Diablo Engine user list.
  // / To prevent this from happening, requests for username availability are cached. 
  // / Clients who request too many usernames in a short amount of time will be blacklisted for a short time.
  if (isset($_POST['NewUserInput']) && isset($_POST['CheckUserAvailability'])) { 
    $UsernameAvailabilityRequest = $UsernameAvailabilityResponseNeeded = TRUE;
    list ($DesiredUsername, $variableIsSanitized) = sanitize($_POST['NewUserInput'], TRUE); }
  // / This code triggers the Forgot Username process / Username Recovery process.
  if (isset($_POST['RecoverAccount']) && isset($_POST['ForgotUserEmailInput'])) { 
    list ($ForgotUserEmail, $variableIsSanitized) = sanitize($_POST['ForgotUserEmailInput'], FALSE);
    $ForgotUsernameRequest = TRUE; }
  // / This code triggers the New Account Request process.
  // / New Account Request processes are potentially hazardous as they require that the core handle & store a lot of user input.
  // / They also leak which usernames are taken, which is a tool used by malicious actors to enumerate a Diablo Engine user list.
  // / To prevent this from happening, requests for new accounts are cached using the Username Availability Request process.
  // / The New Account Request process requires the user is not already blacklisted by the Username Availability Request process. 
  if (isset($_POST['NewUserInput']) && isset($_POST['CreateNewAccount']) && isset($_POST['NewUserEmail']) && isset($_POST['AgreeToTerms']) && isset($_POST['NewUserPassword']) && isset($_POST['NewUserPasswordConfirm'])) if ($_POST['AgreeToTerms'] === 'AGREE') if ($_POST['NewUserPassword'] !== '') if ($_POST['NewUserPassword'] === $_POST['NewUserPasswordConfirm']) { 
    $UsernameAvailabilityRequest = $NewAccountRequest = TRUE;
    $UsernameAvailabilityResponseNeeded = FALSE;
    list ($DesiredUsername, $variableIsSanitized) = sanitize($_POST['NewUserInput'], TRUE);
    list ($NewUserEmail, $variableIsSanitized) = sanitize($_POST['NewUserEmail'], FALSE);
    list ($AgreeToTerms, $variableIsSanitized) = sanitize($_POST['AgreeToTerms'], FALSE);
    list ($NewUserPassword, $variableIsSanitized) = sanitize($_POST['NewUserPassword'], FALSE);
    list ($NewUserPasswordConfirm, $variableIsSanitized) = sanitize($_POST['NewUserPasswordConfirm'], FALSE); }
  // / This code triggers the authentication process.
  // / This code is performed when a user submits enough credentials & tokens to start a new session.
  if (isset($_POST['UserInput']) && isset($_POST['PasswordInput']) && isset($_POST['ClientTokenInput']) && !isset($_POST['SessionID'])) { 
    list ($UserInput, $variableIsSanitized) = sanitize($_POST['UserInput'], TRUE);
    $_SESSION['UserInput'] = $UserInput;
    list ($PasswordInput, $VeriableIsSanitized) = sanitize($_POST['PasswordInput'], FALSE); 
    $_SESSION['PasswordInput'] = $PasswordInput;
    list ($ClientTokenInput, $VeriableIsSanitized) = sanitize($_POST['ClientTokenInput'], TRUE); 
    $_SESSION['ClientTokenInput'] = $ClientTokenInput;
    $SessionType = 'LOGIN';
    $UserID = 'DEFAULT';
    $GlobalsAreVerified = TRUE; }
  // / When no authentication credentials are supplied all related variables are initialized to NULL.
  else $UserInput = $ClientTokenInput = $PasswordInput = NULL;
  // / This code is performed when a user submits tokens and a session ID via POST request to continue an existing session.
  if (isset($_POST['UserInput']) && isset($_POST['SessionID']) && isset($_POST['ClientTokenInput'])) if ($_POST['UserInput'] !== '') { 
    list ($UserInput, $variableIsSanitized) = sanitize($_POST['UserInput'], TRUE);
    $_SESSION['UserInputName'] = $UserInput;
    list ($SessionIDInput, $variableIsSanitized) = sanitize($_POST['SessionID'], TRUE);
    $_SESSION['SessionIDInput'] = $SessionIDInput;
    list ($ClientTokenInput, $variableIsSanitized) = sanitize($_POST['ClientTokenInput'], TRUE);
    $_SESSION['ClientTokenInput'] = $ClientTokenInput;
    list ($ClientToken, $OldClientToken, $ServerToken, $OldServerToken, $TokensAreValid) = generateTokens($ClientTokenInput, $UserInput);
    list ($UserID, $SessionID, $SessionIsVerified) = verifySession($SessionIDInput, $UserInput, $ClientTokenInput, $OldClientToken, $ServerToken, $OldServerToken);
    if ($SessionIsVerified) $_SESSION['SessionID'] = $SessionID;
    else $_POST['SessionID'] = $_SESSION['SessionID'] = $SessionID = $SessionIDInput = NULL; 
    $SessionType = 'POST';
    if ($SessionIsVerified) $GlobalsAreVerified = TRUE; }
  // / Check if the user is attempting to login & prepare variables required to generate ClientTokens.
  // / This code is performed when a user requests tokens to begin the login process.
  if (isset($_POST['RequestTokens']) && isset($_POST['UserInput'])) if ($_POST['UserInput'] !== '') {
    list ($UserInput, $variableIsSanitized) = sanitize($_POST['UserInput'], TRUE);
    $_SESSION['UserInput'] = $UserInput;
    $RequestTokens = TRUE; }
  if ($SessionType === 'POST' or $SessionID === 'SESSION') { 
    // / Set the UserDir based on user input or most recently used.
    if (isset($_POST['UserDir'])) { 
      list ($UserInput, $variableIsSanitized) = sanitize($_POST['UserDir'], FALSE); 
      $_SESSION['UserDir'] = $UserDir; }
    if (!isset($_SESSION['UserDir']) or $_SESSION['UserDir'] == '') $_SESSION['UserDir'] = DIRECTORY_SEPARATOR; 
    $UserDir = $_SESSION['UserDir']; }
  // / Check that any sanitization operations that took place were completed successfully.
    if (!$variableIsSanitized) $SessionID = $GlobalsAreVerified = $RequestTokens = $SessionType = $SessionIsVerified = $UserID = $UsernameAvailabilityRequest = $UsernameAvailabilityResponseNeeded = $NewAccountRequest = $NewUserEmail = $AgreeToTerms = $NewUserPassword = $NewUserPasswordConfirm = FALSE;
  return array($UserID, $UserInput, $PasswordInput, $SessionID, $ClientTokenInput, $UserDir, $RequestTokens, $GlobalsAreVerified, $SessionType, $SessionIsVerified, $UsernameAvailabilityRequest, $DesiredUsername, $UsernameAvailabilityResponseNeeded, $NewAccountRequest, $NewUserEmail, $AgreeToTerms, $NewUserPassword, $NewUserPasswordConfirm, $ForgotUsernameRequest, $ForgotUserEmail); }

// / A function to throw a full screen login page when needed.
function requireLogin() { 
  // / Check that a login page exits.
  if (file_exists('login.php'))
    // / Load the login page.
    include('login.php');
    // / Kill the script to give the user a chance to use the login page.
    logEntry('User is not logged in.', FALSE);
    die();
  return array(); }

// / A function to generate initial client tokens before a user has fully logged in.
// / When a user returns to the site they will be prompted to enter their username.
// / The server will generate client tokens for the specified username and provide them to the current user.
// / The current user now has up to 2 minutes to enter the token with the correct password.
// / Requiring a valid token & invalidating issued tokens prevents replay attacks & complicates eavesdropping for credentials.
// / If the "$Old" variable is set to TRUE, tokens for the previous minute will be generated instead.
function getClientTokens($UserInput, $Old) {
  // / Set variables.
  global $Date, $Users, $Minute, $LastMinute, $Salts, $ClientToken;
  $minute = $Minute;
  $UserFound = FALSE;
  if ($Old === TRUE) $minute = $LastMinute;
  // / Loop through all users to check for the supplied username.
  foreach ($Users as $user) { 
    $UserID = $user[0];
    // / Continue ONLY if the $UserInput matches a valid $UserName.
    if ($user[1] === $UserInput) { 
      $UserFound = TRUE;
      $UserName = $user[1];
      $ClientToken = hash('sha256', $minute.$Salts[0].$user[3].$Salts[0].$Salts[1].$Salts[2].$Salts[3]); } } 
  // / If the specified user does not exist, randomize the already fake & invalid tokens provided by the getClientTokens() function.
  if (!$UserFound ) { 
    $ClientToken = hash('sha256', $Minute.$Date.$Salts[2].$Minute.$Salts[3].$Date.$LastMinute);
    $ServerToken = hash('sha256', $Minute.$Date.$Salts[0].$Minute.$Salts[1].$Date.$LastMinute); }
  // / Clean up unneeded memory.
  $user = $minute = NULL;
  unset($user, $minute);
  return array($UserFound, $ClientToken); } 

// / A function to generate new server tokens.
function generateServerToken() { 
  // / Set variables. 
  global $Minute, $LastMinute, $Salts, $ServerToken, $OldServerToken;
  $ServerToken = NULL;
  $TokenIsValid = FALSE;
  $ServerToken = hash('sha256', $Minute.$Salts[1].$Salts[3]);
  $OldServerToken = hash('sha256', $LastMinute.$Salts[1].$Salts[3]);
  $TokenIsValid = TRUE;
  return array($ServerToken, $OldServerToken, $TokenIsValid); }

// / A function to generate new client tokens and validate supplied ones.
// / This is the secret sauce behind full password encryption in-transit.
function generateTokens($ClientTokenInput, $UserInput) { 
  // / Set variables. 
  global $Date, $Users, $Minute, $LastMinute, $Salts, $ServerToken, $OldServerToken;
  $ClientToken = $OldClientToken = NULL;
  $TokensAreValid = $userFound = $oldUserFound = FALSE;
  // / Initialize ServerTokens.
  list ($ServerToken, $OldServerToken, $TokenIsValid) = generateServerToken();
  // / Generate client tokens to cross reference with the supplied ones.
  list ($userFound, $ClientToken) = getClientTokens($UserInput, FALSE);
  list ($oldUserFound, $OldClientToken) = getClientTokens($UserInput, TRUE);
  // / Compare the supplied tokens with the generated ones.
  if ($ClientTokenInput === $ClientToken or $ClientTokenInput === $OldClientToken) if ($TokenIsValid && $userFound && $ClientToken !== '') $TokensAreValid = TRUE;
  // / Free unneeded memory.
  $userFound = $oldUserFound = NULL;
  unset($userFound, $oldUserFound);
  return array($ClientToken, $OldClientToken, $ServerToken, $OldServerToken, $TokensAreValid); } 

// / A function to cleanup personally identifiable sensitive information left in memory during authentication operations. 
function cleanupSensitiveMemory() {
  // / Set variables in a global scope.
  global $PasswordInput, $Users, $ClientTokenInput;
  // / Set all personally identifiable variables to NULL.
  $PasswordInput = $Users = $ClientTokenInput = NULL;
  // / Remove the variables reference from memory.
  unset($PasswordInput, $Users, $ClientTokenInput); }

// / A function to authenticate a user and verify an encrypted input password with supplied tokens.
function authenticate($UserInput, $PasswordInput, $ClientToken, $ServerToken, $ClientTokenInput) { 
  // / Set variables. 
  global $Users, $Minute, $Salts;
  $sessionIsVerified = $userID = $UserID = $UserName = $PasswordIsCorrect = $UserIsAdmin = $AuthIsComplete = $UserEmail = $SessionID = $SessionIsVerified = $UserIsEnabled = FALSE;
  // / Iterate through each defined user.
  foreach ($Users as $user) { 
    // / Continue ONLY if the $UserInput matches a valid $UserName.
    if ($user[1] === $UserInput) { 
      // / If the UserInput is blank, we don't even bother checking it.
      if ($UserInput === '') break; 
      // / Continue ONLY if all tokens match and the password hash is correct.
      if ($ServerToken.$ClientToken.$user[3] === $ServerToken.$ClientTokenInput.$PasswordInput) {
        // / Define variables for a new session.
        $_SESSION['UserID'] = $UserID = $user[0];
        $_SESSION['UserInput'] = $UserName = $user[1];
        $_SESSION['UserEmail'] = $UserEmail = $user[2];
        $_SESSION['SessionID'] = $SessionID = hash('sha256', $Minute.$Salts[0].$ClientToken.$user[3].$Salts[1].$ServerToken.$Salts[2].$user[1]);
        $_SESSION['ClientTokenInput'] = $ClientTokenInput = $ClientToken;
        // / Set checks to TRUE only if the selected user account is set to ENABLED.
        if ($user[5]) $PasswordIsCorrect = $AuthIsComplete = $SessionIsVerified = $UserIsEnabled = TRUE;
        else { 
          $PasswordIsCorrect = $AuthIsComplete = TRUE;
          $SessionIsVerified = $UserIsEnabled = FALSE; }
        if ($PasswordIsCorrect) list ($userID, $UserInput, $PasswordInput, $sessionID, $ClientTokenInput, $UserDir, $RequestTokens, $GlobalsAreVerified, $SessionType, $sessionIsVerified, $usernameAvailabilityRequest, $desiredUsername, $usernameAvailabilityResponseNeeded, $newAccountRequest, $newUserEmail, $agreeToTerms, $newUserPassword, $newUserPasswordConfirm, $forgotUsernameRequest, $forgotUserEmail) = verifyGlobals();
        // / Here we grant the user their designated permissions.
        if (is_bool($user[4])) { 
          $UserIsAdmin = $user[4]; 
          // / Once we authenticate a user we no longer need to continue iterating through the user list, so we stop.
          break; } } } }
  // / Clean up unneeded memory.
  $sessionIsVerified = $usernameAvailabilityRequest = $desiredUsername = $usernameAvailabilityResponseNeeded = $newAccountRequest = $newUserEmail = $agreeToTerms = $newUserPassword = $user = $userID = $newUserPasswordConfirm = $sessionID = $forgotUsernameRequest = $forgotUserEmail = NULL;
  unset($sessionIsVerified, $usernameAvailabilityRequest, $desiredUsername, $usernameAvailabilityResponseNeeded, $newAccountRequest, $newUserEmail, $agreeToTerms, $newUserPassword, $user, $userID, $newUserPasswordConfirm, $sessionID, $forgotUsernameRequest, $forgotUserEmail);
  // / Cleanup sensitive memory only if is it not required to complete authentication.
  // / This code should only fire if the user login credentials were incorrect.
  if (!$PasswordIsCorrect && !$SessionIsVerified) cleanupSensitiveMemory();
  return array($UserID, $UserName, $UserEmail, $PasswordIsCorrect, $UserIsAdmin, $AuthIsComplete, $SessionID, $SessionIsVerified, $UserIsEnabled); }

// / A function to generate a user log file to the DATA library.
function generateUserLogs($UserID) { 
  // / Set variables. 
  $UserLogsExist = $UserLogDir = $UserLogFile = $UserDataDir = FALSE;
  global $Date, $Time, $Salts, $SessionIsVerified, $LibrariesActive;
  // / Check that the DATA library is enabled & activated.
  list ($DataLibCheck, $DataLibrary) = libCheck('DATA');
  // / Check that the session is verified, the UserID is set, and the data library passed all checks.
  if (isset($UserID)) if ($UserID !== 'DEFAULT') if ($DataLibCheck) {
    // / Define the directory structure for the logs.
    // / The $LibrariesActive[] array is defined in config.php where the user was instructed specifically to include trailing slashes on all directories.
    $UserDataDir = $LibrariesActive[$DataLibrary][2].$UserID.DIRECTORY_SEPARATOR;
    $UserLogDir = $UserDataDir.'Logs'.DIRECTORY_SEPARATOR;
    $UserLogFile = $UserLogDir.'Log-'.$Date.'-'.hash('sha256', $Salts[2].$UserID.$Salts[3].$Date.$Salts[1]).'.txt'; 
    // / Detect which folders exist already & create any that are missing.
    if (!is_dir($UserDataDir) && $UserDataDir !== '') mkdir($UserDataDir); 
    if (!is_dir($UserLogDir)) mkdir($UserLogDir); 
    if (is_dir($UserLogDir)) file_put_contents($UserLogFile, 'OP-Act: '.$Time.', Created a user log file, "'.$UserLogFile.'".'.PHP_EOL);
    if (file_exists($UserLogFile)) $UserLogsExist = TRUE; }
  return array($UserLogsExist, $UserLogDir, $UserLogFile, $UserDataDir); }

// / A function to define the default $UserCacheData for generateUserCache() & loadUserCache() functions. 
function generateDefaultUserCacheData() { 
  // / Set variables. 
  global $UserCacheRequiredOptions, $UserCacheArrayData, $DefaultColorScheme, $DefaultFont, $DefaultTimezone, $DefaultDisplayName, $DefaultTips, $DefaultTheme, $DefaultHRAI, $DefaultHRAIAudio, $DefaultLandingPage, $DefaultStayLoggedIn;
  // / Define the default data for a fresh installation of the $UserCacheFile.
  // / This is specially encoded to be written in a machine-readable .php file that will be included in generateUserCache().
  $UserCacheArrayData = '\'FRIENDS\'=>\'\', \'BLOCKED\'=>\'\', \'COLOR\'=>\''.$DefaultColorScheme.'\', \'FONT\'=>\''.$DefaultFont.'\', \'TIMEZONE\'=>\''.$DefaultTimezone.'\', \'DISPLAYNAME\'=>\''.$DefaultDisplayName.'\', \'TIPS\'=>\''.$DefaultTips.'\', \'THEME\'=>\''.$DefaultTheme.'\', \'HRAI\'=>\''.$DefaultHRAI.'\', \'HRAIAUDIO\'=>\''.$DefaultHRAIAudio.'\', \'LANDINGPAGE\'=>\''.$DefaultLandingPage.'\', \'STAYLOGGEDIN\'=>\''.$DefaultStayLoggedIn.'\'';
  // / Define an array of default cache elements that every user cache file must contain.
  // / Note that the values in this array MUST match the $UserCacheArrayData above which containing the valid defaults for each element of $UserOptions[].
  $UserCacheRequiredOptions = array('FRIENDS'=>array(), 'BLOCKED'=>array(), 'COLOR'=>$DefaultColorScheme, 'FONT'=>$DefaultFont, 'TIMEZONE'=>$DefaultTimezone, 'DISPLAYNAME'=>$DefaultDisplayName, 'TIPS'=>$DefaultTips, 'THEME'=>$DefaultTheme, 'HRAI'=>$DefaultHRAI, 'HRAIAUDIO'=>$DefaultHRAIAudio, 'LANDINGPAGE'=>$DefaultLandingPage, 'STAYLOGGEDIN'=>$DefaultStayLoggedIn); 
  return array($UserCacheArrayData, $UserCacheRequiredOptions); }

// / A function to generate a missing user cache file. 
// / Used when new users log in for the first time.
// / The $UserCacheData variable gets crudely validated and turned into $UserOptions when loaded by the loadUserCache() function.
function generateUserCache($UserID) { 
  // / Set variables. Note the $UserCacheExists, $UserCache and $UserCacheDir are all assumed to be FALSE unless they are changed to TRUE.
  // / If $UserCacheExists, $UserCache or $UserCacheDir return FALSE, the calling code should assume this function failed.
  global $Salts, $UserCacheRequiredOptions, $UserCacheArrayData;
  $UserCacheExists = $UserCache = $UserCacheDir = FALSE;
  $UserCacheDir = 'Cache'.DIRECTORY_SEPARATOR.'Data'.DIRECTORY_SEPARATOR.$UserID.DIRECTORY_SEPARATOR;
  if (!file_exists($UserCacheDir)) $dirExists = mkdir($UserCacheDir);
  // / Define the $UserCacheFile.
  $UserCacheFile = $UserCacheDir.'UserCache-'.hash('sha256',$Salts[0].'CACHE'.$UserID).'.php';
  // / Wrap the data above in proper PHP array syntax so it can be included later in the function.
  $userCacheData = '<?php'.PHP_EOL.'$userCacheData = array('.$UserCacheArrayData.');'.PHP_EOL;
  // / Write default cache data to the $UserCacheFile. 
  $UserCacheExists = file_put_contents($UserCacheFile, $userCacheData);
  // / Clean up unneeded memory.
  $userCacheData = $arrayData = $dirExists = NULL;
  unset($userCacheData, $arrayData, $dirExists); 
  return array($UserCacheExists, $UserCacheFile, $UserCacheDir); } 

// / A function to format a modified user cache array before re-writing it to the user cache file in PHP syntaxed form.
function updateUserCacheData() { 
  // / Set variables. 
  global $UserOptions;
  // / Define the default data for a fresh installation of the $UserCacheFile.
  // / This is specially encoded to be written in a machine-readable .php file that will be included in generateUserCache().
  $UserCacheArrayData = '$userCacheData = array(\'COLOR\'=>\''.$UserOptions['COLOR'].'\', \'FONT\'=>\''.$UserOptions['FONT'].'\', \'TIMEZONE\'=>\''.$UserOptions['TIMEZONE'].'\', \'DISPLAYNAME\'=>\''.$UserOptions['DISPLAYNAME'].'\', \'TIPS\'=>\''.$UserOptions['TIPS'].'\', \'THEME\'=>\''.$UserOptions['THEME'].'\', \'HRAI\'=>\''.$UserOptions['HRAI'].'\', \'HRAIAUDIO\'=>\''.$UserOptions['HRAIAUDIO'].'\', \'LANDINGPAGE\'=>\''.$UserOptions['LANDINGPAGE'].'\', \'STAYLOGGEDIN\'=>\''.$UserOptions['STAYLOGGEDIN'].'\');'.PHP_EOL;
  return $UserCacheArrayData; }

// / A function to save modified settings to the user cache.
// / Uses the updateUserCacheData() function to format the data array.
function saveUserCache() { 
  // / Set variables.
  global $UserCache, $UserOptions;
  $UserCacheArrayData = updateUserCacheData();
  $UserCacheWritten = file_put_contents($UserCache, $UserCacheArrayData, FILE_APPEND);
  // / Clean up unneeded memory.
  $newUserCacheData = $handle = $len = $final_len = $cache_old = $i = $newEntry = $key = $uOpt = $Prepend = NULL;
  unset($newUserCacheData, $handle, $len, $final_len, $cache_old, $i, $newEntry, $key, $uOpt, $Prepend);
  return $UserCacheWritten; }

// / A function to load the user cache, which contains an individual users option settings.
// / Cache files are stored as .php files and cache data is stored as an array. This ensures the files
// / cannot simply be viewed with a browser to reveal sensitive content. The data must be programatically
// / displayed or opened locally in a text editor.
function loadUserCache() {
  // / Set variables. Note the default options that are used as filters for validating the $UserOptions later.
  // / Also note the user cache is hashed with salts.
  global $Salts, $UserID, $UserCache, $UserCacheRequiredOptions;
    $UserOptions = array();
    $UserCacheIsLoaded = $ucaElementWritten = FALSE;
    // / If the user cache exists, load it.
    if (file_exists($UserCache)) {
      require ($UserCache);
      // / If the cache data variable is not set in the cache return an error and stop.
      if (!isset($userCacheData)) { 
        $userCache = NULL; 
        unset($userCache); 
        return array($UserOptions, $UserCacheIsLoaded); }
      // / If the cache data isn't an array we return an error and stop.
      if (!is_array($userCacheData)) { 
        $userCache = $userCacheData = NULL; 
        unset($userCache, $userCacheData); 
        return array($UserOptions, $UserCacheIsLoaded); }
      // / If the user cache is valid we copy it to the $UserOptions array & delete the temporary data.
      $UserOptions = $userCacheData;
      // / Remove the raw cache data from memory.
      $userCacheData = NULL;
      unset($userCacheData);
      // / Iterate through each option specified in the user cache and verify that is it valid.
      foreach ($UserOptions as $option => $value) {
        // / If an option is not valid it is removed from memory.
        if (!in_array($option, $UserCacheRequiredOptions)) { 
          $UserOptions[$option] = NULL;
          unset($UserOptions[$option]); } }
      $UserCacheIsLoaded = TRUE; 
      // / Iterate through the default UserCache items and look for new array elements that should exist.
      foreach ($UserCacheRequiredOptions as $key => $ucaElement) { 
        // / If a new array element is found which needs to be created we add it to the start of the $UserCache.
        // / This way new entries with default values run a lower risk of compromising existing settings.
        if (!in_array($ucaElement, $UserCacheRequiredOptions)) { 
          $UserOptions[$key] = $ucaElement;
          $ucaElementWritten = saveUserCache('TRUE'); }
        // / If the above code successfully wrote data to the beginning of the $UserCache we must re-run this function to load new data into memory.
        // / We could skip this by using a simple require, but we just wrote new data to the cache and we want to run it by all the sanity-check code above
        // / before we let our PHP interpreter see it.
        if ($ucaElementWritten) loadUserCache();
        break; } }
  // / Clean up unneeded memory.
  $option = $value = $key = $ucaElement = $ucaElementWritten = NULL;
  unset($UserCacheRequiredOptions, $option, $value, $key, $ucaElement, $ucaElementWritten);
  return array($UserOptions, $UserCacheIsLoaded); }

// / A function to initialize the libraries into categories based on their status.
// / Categories include: Active, Inactive, Custom & Default.
// / The final return value is a sanity check that should return TRUE after all other processing is complete.
function loadLibraries() { 
  // / Set variables. Note the default libraries that can be used as filters later in the application.
  global $Libraries, $LibrariesDefault;
  $LibrariesActive = $LibrariesInactive = $LibrariesCustom = array();
  // / Iterate through each library specified in config.php.
  foreach ($Libraries as $Library) { 
    // / If the array is not part of the default libraries it is assumed to be a custom library.
    if (!in_array($Library[0], $LibrariesDefault) && $Library[1]) array_push($LibrariesCustom, $Library); 
    // / If a libary is enabled it is marked as active and can be used as a filter later or to display as active in a GUI.
    if ($Library[1]) array_push($LibrariesActive, $Library);
    // / If a libary is disabled it is marked as inactive and can be used as a filter later or to display as inactive in a GUI.
    else array_push($LibrariesInactive, $Library); }
  $LibrariesAreLoaded = TRUE;
  return array($LibrariesActive, $LibrariesInactive, $LibrariesCustom, $LibrariesDefault, $LibrariesAreLoaded); } 

// / A function to read the data from the supplied array of libraries and load their contents.
function loadLibraryData() {
  // / Set variables. 
  global $LibrariesActive;
  $LibraryDataIsLoaded = FALSE;
  $LibraryError = FALSE;
  // / Validate the library location for each library.
  foreach ($LibrariesActive as $key => $LibraryActive) {
    // / Check that the selected library is actually supposed to be activated.
    if ($LibraryActive[1]) { 
      // / Check that the libraray data directory exists. 
      // / If the libraray data directory exists we scan its contents to an array at $LibraryActive[3]. 
      if (file_exists($LibraryActive[2])) { 
        $LibraryDataIsLoaded = TRUE;
        $LibrariesActive[$key][3] = scandir($LibraryActive[2]); }
      // / If the library data directory does not exist, we set the error to $LibraryActive[2] and $LibraryDataIsLoaded to FALSE.
      else { 
        $LibraryDataIsLoaded = FALSE;
        $LibraryError = $LibraryActive[2]; } }
    // / If the selected library is not supposed to be activated, we set the $LibraryError to $LibraryActive[1] & $LibraryDataIsLoaded to FALSE.
    else { 
      $LibraryDataIsLoaded = FALSE;
      $LibraryError = $LibraryActive[0]; } }
  // / Clean up unneeded memory.
  $key = NULL;
  unset($key); 
  return array($LibraryError, $LibraryDataIsLoaded); }  

// / A function to check the status of a given library.
// / Accepts a string as the only input.
// / Will compare the string against the names of all libraries.
// / This function is slower but more comprehensive than the libCheck() function.
function checkLibraryStatus($library) { 
  // / Set variables.
  global $LibrariesActive, $LibrariesInactive, $LibrariesCustom, $LibrariesDefault;
  $LibrraryExists = $LibraryIsActive = $LibraryIsInactive = $LibraryIsCustom = $LibraryIsDefault = $libA = $libB = $libC = $libD = FALSE;
  // / Iterate through each category & flag the categories where the supplied library appears.
  foreach ($LibrariesActive as $libA) if ($libA[0] === $library) { 
    $LibraryExists = $LibraryIsActive = TRUE; 
    break; }
  foreach ($LibrariesInactive as $libB) if ($libB[0] === $library) { 
    $LibraryExists = $LibraryIsInactive = TRUE; 
    break; }
  foreach ($LibrariesCustom as $libC) if ($libC[0] === $library) { 
    $LibraryExists = $LibraryIsCustom = TRUE; 
    break; }
  foreach ($LibrariesDefault as $libD) if ($libD[0] === $library) { 
    $LibraryExists = $LibraryIsDefault = TRUE; 
    break; }
  // / Clean up unneeded memory.
  $libA = $libB = $libC = $libD = $library = NULL;
  unset($libA, $libB, $libC, $libD, $library); 
  return array($LibraryExists, $LibraryIsActive, $LibraryIsInactive, $LibraryIsCustom, $LibraryIsDefault); }

// / A function to quickly check if a desired library is active.
// / $LibCheck returns the array key of the desired library if the input library is active.
// / $LibCheck returns FALSE if the input library is not active.
// / $DesiredLibrary returns the array index of the desired library if it is active.
// / $DesiredLibrary returns FALSE if the desired library is not active.
// / This function is faster but less comprehensive than the checkLibraryStatus() function.
function libCheck($library) { 
  // / Set variables. 
  global $LibrariesActive;
  $lib = $key = $LibCheck = $DesiredLibrary = FALSE;
  // / Check that the $LibrariesActive variable has been defined, that it is the proper type, & that the desired library is present in the array.
  if (isset($LibrariesActive)) if (is_array($LibrariesActive)) foreach ($LibrariesActive as $key => $lib) if ($lib[0] === $library) { 
      $LibCheck = TRUE; 
      $DesiredLibrary = $key; 
      break; } 
  // / Clean up unneeded memory.
  $lib = $key = NULL;
  unset($lib, $key);
  return array($LibCheck, $DesiredLibrary); }

// / A function to set detailed variables for a specified library.
// / Makes use of the checkLibraryStatus() function.
function selectLibrary($library) {
  // / Set variables. 
  $lib = array('', '', '', '');
  global $LibrariesActive;
  $libName = $LiberaryIsSelected = $LibraryName = $LibraryDir = $LibraryContents = $LibraryExists = $LibraryIsActive = $LibraryIsInactive = $LibraryIsCustom = $LibraryIsDefault = FALSE;
  // / Determine the status of the supplied library.
  list ($LibraryExists, $LibraryIsActive, $LibraryIsInactive, $LibraryIsCustom, $LibraryIsDefault) = checkLibraryStatus($library);
  // / Make sure the supplied library is valid to ensure that it can be selected.
  if ($LibraryExists && $LibraryIsActive) { 
    // / Iterate through the list of active libraries looking for the supplied one.
    foreach ($LibrariesActive as $lib) { 
      $libName = $lib[0];
      // / If we find a match we set library specific variables for this library.
      if ($libName === $library) { 
        $LibraryName = $libName;
        $LibraryDir = $lib[2];
        $LibraryContents = $lib[3]; 
        $LibraryIsSelected = TRUE; 
        break; } } }
  // / Clean up unneeded memory.
  $lib = $libName = NULL;
  unset($lib, $libName); 
  return array($LibraryIsSelected, $LibraryName, $LibraryDir, $LibraryContents, $LibraryExists, $LibraryIsActive, $LibraryIsInactive, $LibraryIsCustom, $LibraryIsDefault); }

// / A function to determine if a notifications file exists for the current user and generate one if missing.
// / A notification is considered a single line of the notifications file.
function generateNotificationsFile($UserID, $UserDataDir) { 
  // / Set variables. Note that we assume the $notificationsCheck is TRUE until we verify that a $NotificationsFile exists.
  global $Salts;
  $NotificationsFile = $UserDataDir.'UserNotifications-'.hash('sha256',$Salts[1].'NOTIFICATIONS'.$UserID).'.php';
  $NotificationsFileExists = FALSE;
  // / Detect if no file exists and try to create one.
  if (!file_exists($NotificationsFile)) file_put_contents($NotificationsFile, ''); 
  // / If the file operation operation returns FALSE, check again.
  if (file_exists($NotificationsFile)) $NotificationsFileExists = TRUE;
  return array($NotificationsFileExists, $NotificationsFile); } 

// / A function for loading notifications.
// / A notification is considered a single line of the notifications file.
function loadNotifications() {
  // / Set variables. 
  global $NotificationsFile;
  $Notifications = file($NotificationsFile);
  // / Check that $Notifications is actually an array.
  if (!is_array($Notifications) or !$Notifications) $Notifications = array(); 
  return $Notifications; }

// / A function to prepare a single notification for the UI.
// / Notifications are comprised of the date they were created and the content of the notification.
// / These two parts of a notification are separated by a "tab" character.
// / NOTE: If a notification entry begins with a single character of whitespace, the notification is "unread." 
// / Example:     [date]["TAB"][notification]
function decodeNotification($Notification) { 
  // / Set variables. 
  $NotificationDate = $NotificationContent = FALSE;
  // / Please note that the whitespace in the "explode" below is actually a TAB character!
  // / Also sanitize the $Notification before loading it.
  list ($NotificationDate, $NotificationContent) = explode("  ", sanitize($Notification, TRUE));
  return array($NotificationDate, $NotificationContent); }

// / A function for marking notifications as read.
// / A notification is considered a single line of the notifications file.
// / Lines that begin with a single charactere of whitespace are "unread."
// / To mark an item as "read" we simply remove the leading whitespace from unread notifications. 
function readNotifications() { 
  // / Set variables.
  global $Notifications;
  // / Iterate through the notifications and detect the first character. 
  foreach ($Notifications as $key => $notification) { 
    $firstChar = substr($notification, 0, 1);
    // / If the first character of the notification is not currently a space we assume it us currently "unread."
    if ($firstChar !== ' ') { 
      $notification = ltrim($notification); 
      // / We've just detected the removed the space in front of an unread notification and now we're saving it back to the array. 
      $Notifications[$key] = $notification; } }
  // / Write the new array back to the notifications file. 
  // / Note we don't need to reload the $NotificationsFile because we also modifed the copy already in memory.
  $NotificationsFileUpdated = file_put_contents($NotificationsFile, $Notifications);
  // / Clean up unneeded memory.
  $key = $notification = $firstChar = NULL;
  unset($key, $notification, $firstChar);
  return array($NotificationsFileUpdated); }

// / A function for purging notifications.
// / A notification is considered a single line of the notifications file.
// / Notifications are not stored with any corresponding serialization or indexing data.
// / The date-time of the notifications combined with its content determines unique identity.
function purgeNotification($NotificationToPurge) { 
  // / Set variables. Note that we assume the $NotificationsFileWritten and $NotificationsPurged are FALSE until the notification
  // / is deleted and a file is created. If either of those fail we assume that the operation failed.
  global $NotificationFile, $Notifications;
  $notificationFileWritten = $NotificationPurged = FALSE;
  // / Iterate through the notifications looking for ones that match the specified notification.
  foreach ($Notifications as $key => $notificationToCheck) { 
    // / If we find a matching notification we remove it from the array that is in memory.
    if ($NotificationToPurge === $notificationToCheck) unset($Notifications[$key]);
    // / With a new $Notifications array saved to memory, we can dump the modified data back into the $NotificationsFile.
    $notificationFileWritten = file_put_contents($NotificationFile, $Notifications);
    // / Note that below we only mark the check a success once we've verified that we both detected a match and re-wrote the file.
    // / Note that continuing to run after a failure of this operation could result in different states between $Notifications in memory
    // / and the data contained in the $NotificationsFile. 
    // / Note that we DON'T STOP the iteration EVEN IF we find our result. This enables automatic duplicate cleanup.
    if ($notificationFileWritten) $NotificationPurged = TRUE; }
  // / Clean up unneeded memory.
  $NotificationToPurge = $notificationFileWritten = $key = $notificationsCheck = NULL;
  unset($NotificationToPurge, $notificationFileWritten, $key, $notificationsToCheck);
  return array($NotificationPurged, $Notifications); }

// / A function to push a notification to a user.
function pushNotification($NotificationToPush, $TargetUserID) { 
  // / Sanitize the notification with strict character enforcement.
  $NotificationToPush = sanitize($NotificationToPush, TRUE);
  return $NotificationSent; }

// / A function to load an included dependency when called.
// / Accepts a string name of a valid dependency as the first argument,
// / If the string name is not set in the $AvailableDependencies array in config.php it will not be allowed to load.
// / Accepts a string or array of strings as the second argument. 
// / Each string in the second argument should be a valid path to a .php file to be included relative to the "Dependencies" directory.
// / If a specified .php file has been included already it will not be inluded again.
// / Returns TRUE when the specified dependency was successfully loaded..
// / Returns FALSE when the specified dependency could not be loaded.
function loadDependency($dependency, $files) { 
  // / Set variables. 
  global $DependencyDir, $AvailableDependencies;
  $DependencyLoadedSuccessfully = FALSE;
  $check = TRUE;
  if (!is_array($files)) $files = array($files);
  // / Loop through all of the dependencies one at a time,
  if (in_array($dependency, $AvailableDependencies)) foreach ($files as $dep) { 
    $depFile = $DependencyDir.$dependency.DIRECTORY_SEPARATOR.$dep;
    // / Only continue loading files if all required files exist.
    if (file_exists($depFile)) require_once($depFile); 
    else $check = FALSE; } 
  if ($check) $DependencyLoadedSuccessfully = TRUE;
  // / Clean up unneeded memory.
  $dependency = $files = $depFile = $dep = $check = NULL;
  unset($dependency, $files, $depFile, $dep, $check);
  return $DependencyLoadedSuccessfully; }

// / A function to send an email.
// / Accepts string inputs for $address, $subject, & $content.
// / Returns a boolean value 
function sendEmail($address, $subject, $content) { 
  // / Set variables. 
  global $PHPMailerLoaded, $EmailUseSMTP, $EmailFromAddress, $EmailFromName, $EmailSMTPRequireAuthentication, $Verbose, $EmailSendEncryption, $EmailSendEncryptionAutoTLS, $EmailSendEncryptionType, $EmailSMTPServer, $EmailSMTPPort, $EmailSMTPUsername, $EmailSMTPPassword;
  $phpMailerArray = array('src/PHPMailer.php', 'src/SMTP.php', 'src/Exception.php');
  $EmailSent = FALSE;
  $mailSMTPEncryptionType = 'none'; 
  if ($EmailSendEncryption) $mailSMTPEncryptionType = $EmailSendEncryptionType;
  // / Load PHPMailer into memory only if it hasn't been loaded already.
  if (!$PHPMailerLoaded) $PHPMailerLoaded = loadDependency('PHPMailer', $phpMailerArray);
  if (!$PHPMailerLoaded) dieGracefully(31, 'Could not load PHPMailer!', FALSE);
  else if ($Verbose) logEntry('Loaded PHPMailer', FALSE);
  // / Initialize a PHPMailer class.
  $mail = new PHPMailer\PHPMailer\PHPMailer();
  // / Define required parameters for the email to be sent.
  $mail->SMTPSecure = $mailSMTPEncryptionType;
  $mail->SMTPAutoTLS = $EmailSendEncryptionAutoTLS;
  $mail->AddAddress($address);
  $mail->SetFrom($EmailFromAddress, $EmailFromName);
  $mail->Subject = $subject;
  $mail->Body = $content;
  $mail->isHTML(TRUE); 
  // / Define SMTP specific parameters for the email to be sent.
  if ($EmailUseSMTP) {
    $mail->isSMTP();
    $mail->SMTPAuth = $EmailSMTPRequireAuthentication;
    $mail->Host = $EmailSMTPServer;
    $mail->Port = $EmailSMTPPort;
    $mail->Username = $EmailSMTPUsername;
    $mail->Password = $EmailSMTPPassword; }
  // / Attempt to send the email using PHPMailer & the settings defined above.
  try {
    $mail->Send();
    $EmailSent = TRUE;
    if ($Verbose) logEntry('PHPMailer returned the following: '.PHP_EOL.$mail->ErrorInfo, FALSE); }
  // / Catch any errors generated by PHPMailer and write them to the log.
  catch(Exception $exceptionInfo) { 
    $EmailSent = FALSE; 
    if ($Verbose) logEntry('PHPMailer returned the following error: '.PHP_EOL.$exceptionInfo->getMessage(), FALSE); }
  // / Detect any errors that may have been output by PHPMailer that the exception handler didn't catch.
  $errorArr = array('fail', 'invalid', 'denied', 'rejected', 'refused', 'could not instantiate');
  foreach ($errorArr as $err) if (strpos(strtolower($mail->ErrorInfo), $err) !== FALSE) $EmailSent = FALSE;
  // / Clean up unneeded memory.
  $mailSMTPEncryptionType = $mail = $exceptionInfo = $phpMailerArray = NULL;
  unset($mailSMTPEncryptionType, $mail, $exceptionInfo, $phpMailerArray);
  return $EmailSent; } 

// / A function to verify that the user environment is properly setup.
// / Accepts a $UserID as input and creates a functional environment for that user.
// / Returns an array fill of user specific environment variables.
function verifyUserEnvironment($UserID) { 
  // / Set variables.
  global $Verbose;
  // / Make sure the $UserID is a number.
  if (is_numeric($UserID)) { 
    // / Make note of this $UserID in the logfile.
    logEntry('Creating a user environment for UserID: '.$UserID.'.', TRUE);
    // / This code generates a user log file if none exists. Useful for initializing new users logging-in for the first time.
    // / This code also specifies the $UserDataDir which is a direct handle to the users subdirectory within the DATA library.
    list ($UserLogsExists, $UserLogDir, $UserLogFile, $UserDataDir) = generateUserLogs($UserID);
    if (!$UserLogsExists) dieGracefully(11, 'Could not generate a user log file! ', FALSE);
    else if ($Verbose) logEntry('Verified user log file.', FALSE);
    // / This code generates a user cache file if none exists. Useful for initializing new users logging-in for the first time.
    list ($UserCacheExists, $UserCache, $UserCacheDir) = generateUserCache($UserID);
    if (!$UserCacheExists) dieGracefully(12, 'Could not generate a user cache file!', TRUE);
    else if ($Verbose) logEntry('Verified user cache file.', TRUE);
    // / This code generates a user notifications file if none exists. Useful for initializing new users logging-in for the first time.
    list ($NotificationsFileExists, $NotificationsFile) = generateNotificationsFile($UserID, $UserDataDir);
    if (!$NotificationsFileExists) dieGracefully(13, 'Could not generate a user notifications file!', TRUE);
    else if ($Verbose) logEntry('Verified user notifications file.', TRUE); }
  // / If the $UserID is not an integer it cannot be used to create a user environment.
  else dieGracefully(37, 'The UserID is not valid!', FALSE);
  return array($UserLogsExists, $UserLogDir, $UserLogFile, $UserDataDir, $UserCacheExists, $UserCache, $UserCacheDir, $NotificationsFileExists, $NotificationsFile); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / The following code specifies the logic flow for the session.

// / Reset the time limit for script execution.
set_time_limit(0);

// / This code verifies the date & time for file & log operations.
list ($RawTime, $Date, $Time, $Minute, $LastMinute, $RequestID) = verifyDate();

// / This code verifies the integrity of the application.
// / Also generates required directories in case they are missing & creates required log & cache files.
list ($LogFile, $CacheFile, $InstallationIsVerified) = verifyInstallation();
if (!$InstallationIsVerified) dieGracefully(3, 'Could not verify installation!', FALSE);
else if ($Verbose) logEntry('Verified installation.', FALSE);

// / Stop the application if the request is unencrypted & $ForceHTTPS is enabled. 
list ($ConnectionIsVerified, $ConnectionIsSecure) = verifyConnection();
if (!$ConnectionIsVerified) dieGracefully(33, 'Could not verify connection!', FALSE);
else if ($Verbose) logEntry('Verified connection.', FALSE);

// / This code initialized required variables to default values.
list ($InitializationComplete, $UserOptionCount) = initializeVariables();
if (!$InitializationComplete) dieGracefully(4, 'Could not initialize variables to default values!', FALSE);
else if ($Verbose) logEntry('Initialized variables to default values.', FALSE);

// / Load the compatibility core to make sanity checks possible.
list ($CoresLoaded, $CoreLoadedSuccessfully) = loadCores('COMPATIBILITY');
$VersionsMatch = checkVersionInfo();
if (!$VersionsMatch or !$CoreLoadedSuccessfully) dieGracefully(5, 'Application Version discrepancy detected!', FALSE);
else if ($Verbose) logEntry('Verified version information.', FALSE);

// / This code loads & sanitizes the global cache & prepares the user list.
list ($Users, $CacheIsLoaded) = loadCache();
if (!$CacheIsLoaded) dieGracefully(6, 'Could not load cache file!', FALSE);
else if ($Verbose) logEntry('Loaded cache file.', FALSE);

// / This code takes in all required inputs to build a session and ensures they exist & are a valid type.
// / If the globals cannot be verified, but the user is trying to login we will show them a login form.
list ($UserID, $UserInput, $PasswordInput, $SessionID, $ClientTokenInput, $UserDir, $RequestTokens, $GlobalsAreVerified, $SessionType, $SessionIsVerified, $UsernameAvailabilityRequest, $DesiredUsername, $UsernameAvailabilityResponseNeeded, $NewAccountRequest, $NewUserEmail, $AgreeToTerms, $NewUserPassword, $NewUserPasswordConfirm, $ForgotUsernameRequest, $ForgotUserEmail) = verifyGlobals();
if (!$GlobalsAreVerified) if ($RequestTokens && $UserInput === NULL) requireLogin(); 
else if ($Verbose) logEntry('Verified global variables.', FALSE);

// / This code builds arrays libraries & their various states.
// / Libraries are directory for storing specific types of information. 
list ($LibrariesActive, $LibrariesInactive, $LibrariesCustom, $LibrariesDefault, $LibrariesAreLoaded) = loadLibraries();
if (!$LibrariesAreLoaded) dieGracefully(9, 'Could not load libraries!', FALSE);
else if ($Verbose) logEntry('Loaded libraries.', FALSE);

// / This code verifies that each active library directory exists.
// / Verified libraries receive the $LibrariesActive[3] element & become fully activated. 
// / $LibrariesActive[0] contains the name of the library in all caps. Used as the array key.
// / $LibrariesActive[1] contains a boolean value. TRUE enables the library & FALSE disables the library.
// / $LibrariesActive[2] contains a file path to the user-specific library 
// / $LibrariesActive[3] contains an array containing library contents.
// / Instead of accepting $LibrariesActive as an argument and re-specifying it as a return value, we represent it in global scope in the loadLibraryData function.
list ($LibraryError, $LibraryDataIsLoaded) = loadLibraryData();
if (!$LibraryDataIsLoaded) dieGracefully(10, 'Could not load library data from '.$LibraryError.'!', FALSE);
else if ($Verbose) logEntry('Loaded library data.', FALSE);

// / If the globals have been verified this code will start the authentication process.
// / If the globals have not been verified this code will be skipped.
if ($GlobalsAreVerified) {
  // / This code ensures that a same-origin UI element generated the login request.
  // / Also protects against packet replay attacks by ensuring that the request was generated recently and by making each request unique. 
  list ($ClientToken, $OldClientToken, $ServerToken, $OldServerToken, $TokensAreValid) = generateTokens($ClientTokenInput, $UserInput);
  if (!$TokensAreValid) dieGracefully(7, 'Invalid tokens!', FALSE);
  else if ($Verbose) logEntry('Generated tokens.', FALSE);

  // / If the existing session cannot be verified, require authentication.
  // / Do not require authentication if the user has proven they are in the middle of a valid session.
  if (!$SessionIsVerified) { 
    // / This code validates credentials supplied by the user against the hashed ones stored on the server.
    // / Also removes the $Users user list from memory so it can not be leaked.
    // / Displays a login screen when authentication fails and kills the application. 
    list ($UserID, $UserName, $UserEmail, $PasswordIsCorrect, $UserIsAdmin, $AuthIsComplete, $SessionID, $SessionIsVerified, $UserIsEnabled) = authenticate($UserInput, $PasswordInput, $ClientToken, $ServerToken, $ClientTokenInput);
    if (!$PasswordIsCorrect or !$AuthIsComplete) dieGracefully(8, 'Invalid username or password!', FALSE); 
    if (!$UserIsEnabled)  dieGracefully(28, 'The specified user account is disabled!', FALSE); 
    else if ($Verbose) logEntry('Authenticated UserName '.$UserName.', UserID '.$UserID.', SessionID '.$SessionID.'.', FALSE); }
  else if ($Verbose) logEntry('Verified existing session.', FALSE);

  // / The following code verifies that required user directories & files are present & creates them if they are missing.
  list ($UserLogsExists, $UserLogDir, $UserLogFile, $UserDataDir, $UserCacheExists, $UserCache, $UserCacheDir, $NotificationsFileExists, $NotificationsFile) = verifyUserEnvironment($UserID);
  if (!$UserLogsExists or !$UserCacheExists or !$NotificationsFileExists) dieGracefully(35, 'Could not verify the user environment!', FALSE); 
  else if ($Verbose) logEntry('Verified user environment.', FALSE); }

// / This code is performed when there was not enough information to authenticate the user.
else if ($Verbose) logEntry('Deferred authentication procedure.', FALSE);

// / Return client tokens when requested.
// / This code is performed when an unauthenticated user is trying to log in. 
// / If a user has supplied a user name to the core along with a request for client tokens, tokens for that user will be returned.
if ($RequestTokens && !$SessionIsVerified) { 
  if ($Verbose) logEntry('User requested tokens for login.', FALSE); 

  // / Output a username and client token.
  // / Note that fake tokens are returned if the requested user does not exist.
  list ($UserFound, $ClientToken) = getClientTokens($UserInput, FALSE);
  echo($UserInput.','.$ClientToken.PHP_EOL); }

// / This code returns keep alive tokens to the user.
// / It is performed once all authentication processes are complete and a session has been created.
if ($SessionIsVerified) { 
  if ($Verbose) logEntry('Verified user session.', FALSE); 

  // / This code loads the user cache file containing the user specific settings configuration into memory.
  list ($UserOptions, $UserCacheExists) = loadUserCache();
  if (!$UserCacheExists) dieGracefully(15, 'Could not load the user cache file!', TRUE);
  else if ($Verbose) logEntry('Loaded user cache file.', TRUE); 

  // / Return user name, sessionID, and client token when requested.
  // / Used to continue an existing session.
  echo($UserInput.','.$SessionID.','.$ClientToken.PHP_EOL); }

// / This code is performed when a user submits an Username Availability Request or New Account Request.
// / Only approve  a Username Availability Request if the user is an administrator or user registration is enabled in config.php.
if ($UsernameAvailabilityRequest) if ($DesiredUsername !== '') if ($UserIsAdmin or $AllowUserRegistration) { 
  if ($Verbose) logEntry('Initiating a username availability request.', FALSE); 

  // / Load the admin core to make Username Availability Request proccessing possible.
  if (!in_array('ADMIN', $CoresLoaded)) list ($CoresLoaded, $CoreLoadedSuccessfully) = loadCores('ADMIN');
  if (!$CoreLoadedSuccessfully) dieGracefully(20, 'Could not load the admin core file (adminCore.php)!', FALSE);
  else if ($Verbose) logEntry('Loaded the admin core file.', FALSE); 

  // / Check if the username is available & provide a response to the Username Availability Request if needed.
  // / Logging is performed by the called code.
  list ($UsernameAvailabilityPermissionGranted, $UsernameIsAvailable) = checkUserAvailability($DesiredUsername, $UsernameAvailabilityResponseNeeded);
  if ($Verbose) logEntry('The username availability request is complete.', FALSE);
  
  // / Stop executing code unless the user indends to continue the New Account Creation process.
  if (!$NewAccountRequest) die(); 

  // / The following code is performed when a user submits a New Account Request.
  // / Only approve a New Account Request after a successful Username Availability Request has been approved.
  else { 
    // / Double check that the Username Availability Request completed with the expected results.
    if (!$UsernameAvailabilityPermissionGranted or !$UsernameIsAvailable) dieGracefully(27, 'Could not initiate a new account request!', FALSE);
    // / Initiate the New Account Request
    else { 
      // / Output a log entry detailing the start of the New Account Request.
      if ($Verbose) logEntry('Initiating a new account request.', FALSE); 
    
      // / Create the user account.
      // / Logging is performed by the called code.
      list ($UserCreated, $UserID, $Users) = addUser($DesiredUsername, $NewUserEmail, $NewUserPassword, $NewUserPasswordConfirm);
      if (!$UserCreated) dieGracefully(14, 'Could not create the desired user account!', FALSE);
      else if ($Verbose) logEntry('Successfully created a new user account.', FALSE);

      // / Output a log entry detailing the end of the New Account Request.
      if ($Verbose) logEntry('The new account request is complete.', FALSE);

      // / Stop executing code.
      die(); } } }

// / This code is performed when a user submits an Forgot Username Request.
if ($ForgotUsernameRequest && $ForgotUserEmail !== '') { 
  if ($Verbose) logEntry('Initiating a forgotten username request.', FALSE); 

  // / Load the admin core to make Forgot Username Request proccessing possible.
  if (!in_array('ADMIN', $CoresLoaded)) list ($CoresLoaded, $CoreLoadedSuccessfully) = loadCores('ADMIN');
  if (!$CoreLoadedSuccessfully) dieGracefully(32, 'Could not load the admin core file (adminCore.php)!', FALSE);
  else if ($Verbose) logEntry('Loaded the admin core file.', FALSE); 
  
  // / Gather a list of accounts for the given username & send that information to the user in an email.
  $UsernameRecoveryEmailSent = recoverUsername($ForgotUserEmail);
  if (!$UsernameRecoveryEmailSent) dieGracefully(34, 'Could not send a username recovery email!', FALSE);
  else if ($Verbose) logEntry('Sent a username recovery email.', FALSE); 

  // / Output a log entry detailing the end of the Forgot Username Request.
  if ($Verbose) logEntry('The forgotten username request is complete.', FALSE);

  // / Stop executing code.
  die(); } 
// / ----------------------------------------------------------------------------------