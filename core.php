<?php
/*
HonestRepair Diablo Engine  -  Core
https://www.HonestRepair.net
https://github.com/zelon88

Licensed Under GNU GPLv3
https://www.gnu.org/licenses/gpl-3.0.html

Author: Justin Grimes
Date: 3/10/2021
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

set_time_limit(0);

// / ----------------------------------------------------------------------------------
// / Make sure there is a session started and load the configuration file.
// / Also kill the application if $MaintenanceMode is set to  TRUE.
if (session_status() == PHP_SESSION_NONE) session_start();
if (!file_exists('config.php')) die('ERROR!!! 0, Could not process the Configuration file (config.php)!'.PHP_EOL); 
else require_once ('config.php');
$ConfigIsLoaded = TRUE; 
if ($MaintenanceMode) die('The requested application is currently unavailable due to maintenance.'.PHP_EOL); 
// / ----------------------------------------------------------------------------------

// / ----------------------------------------------------------------------------------
// / Perform sanity checks to verify the environment is suitable for running.

// / Detemine the version of PHP in use to run the application.
// / Any PHP version earlier than 7.0 IS STRICTLY NOT SUPPORTED!!!
// / Specifically, PHP versions earlier than 7.0 require the list() functions used to be unserialized. 
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
    if ($Strict) $Variable = str_replace(str_split('|\\~#[](){};$!#^&%@>*<"\'/'), '', $Variable);
    if (!$Strict) $Variable = str_replace(str_split('|\\~#[](){};$!#^&%@>*<"\''), '', $Variable); }
  return (array($Variable, $VariableIsSanitized)); }

// / A function to set the date and time for internal logic like file cleanup.
// / Set variables. 
function verifyDate() { 
  // / Set variables. Create an accurate human-readable date from the servers timezone.
  $Date = date("m-d-y");
  $Time = date("F j, Y, g:i a"); 
  $Minute = intval(date('i'));
  $LastMinute = $Minute - 1;
  $NextMinute = $Minute + 1;
  // / We need to accomodate the off-chance that execution spans multiple days. 
  // / In other words, the application starts at 11:59am and ends at 12:00am.
  // / I tried to think what would happen if we spanned multiple months or years but I threw up in my mouth. >D
  if ($LastMinute === 0) $LastMinute = 59;
  return(array($Date, $Time, $Minute, $LastMinute)); }

// / A function to generate and validate the operational environment for the Diablo Engine.
function verifyInstallation() { 
  // / Set variables. 
  global $Date, $Time, $Salts;
  $dirCheck = $indexCheck = $dirExists = $indexExists = $logCheck = $cacheCheck = TRUE;
  $requiredDirs = array('Logs', 'Data', 'Cache', 'Cache'.DIRECTORY_SEPARATOR.'Data');
  $InstallationIsVerified = FALSE;
  // / For servers with unprotected directory roots, we must verify (at minimum) that a local index file exists to catch unwanted traversal.
  if (!file_exists('index.html')) $indexCheck = FALSE;
  // / Iterate through the $requiredDirs hard-coded (in this function, under "Set variables" section above).
  foreach ($requiredDirs as $requiredDir) {
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
  $LogFile = 'Logs'.DIRECTORY_SEPARATOR.$Date.'_'.$logHash.'.log';
  // / Create today's $LogFile if it doesn't exist yet.
  if (!file_exists($LogFile)) $logCheck = file_put_contents($LogFile, 'OP-Act: '.$Time.' Created a log file, "'.$LogFile.'".'.PHP_EOL);
  // / Create a unique identifier for the cache file.
  $CacheFile = 'Cache'.DIRECTORY_SEPARATOR.'Cache-'.hash('sha256',$Salts[0].'CACHE').'.php';
  // / If no cache file exists yet (first run) we create one and write the $PostConfigUsers to it. 
  if (!file_exists($CacheFile)) $cacheCheck = file_put_contents($CacheFile, '<?php'.PHP_EOL.'$PostConfigUsers = array();');
  // / Make sure all sanity checks passed.
  if ($dirCheck && $indexCheck && $logCheck && $cacheCheck) $InstallationIsVerified = TRUE;
  // / Clean up unneeded memory.
  $dirCheck = $indexCheck = $logCheck = $cacheCheck = $requiredDirs = $requiredDir = $dirExists = $indexExists = $logHash = NULL;
  unset($dirCheck, $indexCheck, $logCheck, $cacheCheck, $requiredDirs, $requiredDir, $dirExists, $indexExists, $logHash);
  return(array($LogFile, $CacheFile, $InstallationIsVerified)); }

// / A function to initialize global variables to default values.
function initializeVariables() {
  $UserOptionCount = $ucCount = 0;
  global $CoreLoadedSuccessfully, $VersionsMatch, $CacheIsLoaded, $SessionIsVerified, $GlobalsAreVerified, $TokensAreValid, $PasswordIsCorrect, $AuthIsComplete, $LibrariesAreLoaded, $LibraryDataIsLoaded, $UserLogsExists, $UserCacheExists, $NotificationsFileExists, $UserOptions, $UserCacheArrayData, $UserCacheRequiredOptions, $ServerToken, $OldServerToken;
  // / Initialize all required sanity checks to FALSE.
  $CoreLoadedSuccessfully = $VersionsMatch = $CacheIsLoaded = $SessionIsVerified = $GlobalsAreVerified = $TokensAreValid = $PasswordIsCorrect = $AuthIsComplete = $LibrariesAreLoaded = $LibraryDataIsLoaded = $UserLogsExists = $UserCacheExists = $NotificationsFileExists = $ServerToken = $OldServerToken = FALSE;
  // / Initialize all required user options to NULL.
  list ($UserCacheArrayData, $UserCacheRequiredOptions) = generateDefaultUserCacheData();
  $ucCount = count($UserCacheRequiredOptions);
  foreach ($UserCacheRequiredOptions as $ucItem) { 
    $UserOptionCount++;
    $UserOptions[$ucItem] = ' '; }
  // / Initialize ServerTokens.
  list ($ServerToken, $OldServerToken, $TokenIsValid) = generateServerToken();
  // / Ensure sanity checks passed.
  if (intval($ucCount) === intval($UserOptionCount) && $TokenIsValid) $InitializationComplete = TRUE; 
  // / Clean up unneeded memory.
  $ucItem = $ucCount = NULL;
  unset($ucItem, $ucCount);
  return (array($InitializationComplete, $UserOptionCount)); }

// / A function to quickly check if a desired library is active.
// / $LibCheck returns the array key of the desired library if the input library is active.
// / $LibCheck returns FALSE if the input library is not active.
// / $DesiredLibrary returns the array index of the desired library if it is active.
// / $DesiredLibrary returns FALSE if the desired library is not active.
function libCheck($library) { 
  // / Set variables. 
  global $LibrariesActive;
  $lib = $key = $LibCheck = $DesiredLibrary = FALSE;
  // / Check that the $LibrariesActive variable has been defined, that it is the proper type, & that the desired library is present in the array.
  if (isset($LibrariesActive)) if (is_array($LibrariesActive)) foreach ($LibrariesActive as $key => $lib) if ($lib[0] === 'DATA') { 
      $LibCheck = TRUE; 
      $DesiredLibrary = $key; 
      break; } 
  // / Clean up unneeded memory.
  $lib = $key = NULL;
  unset($lib, $key);
  return (array($LibCheck, $DesiredLibrary)); }

// / A function to generate useful, consistent, and easily repeatable error messages.
// / The $ErrorNumber should be an integer representing the unique error identifier.
// / If the $ErrorNumber is not an integer, it will be replaced with '0'.
// / The $ErrorMessage should be a string containing a brief description of the error.
// / Set $LogToUser to TRUE to submit the entry to the users logfile in addition to the default system logfile.
// / Set $LogToUser to FALSE to prevent the entry from being entered into the users logfile.
// / If $LogToUser is set to FALSE, the entry will still be logged in the default system logfile specified in config.php.
function dieGracefully($ErrorNumber, $ErrorMessage, $LogToUser) { 
  // / Set variables. 
  global $LogFile, $UserLogFile, $Time, $UserLogsExist;
  // / Perform a sanity check on the $ErrorNumber. 
  if (!is_numeric($ErrorNumber)) $ErrorNumber = 0;
  $ErrorOutput = 'ERROR!!! '.$ErrorNumber.', '.$Time.', '.$ErrorMessage.PHP_EOL;
  // / Write the primary log file. Note that we don't care about success or failure because we're about to kill the script regardless.
  file_put_contents($LogFile, $ErrorOutput, FILE_APPEND);
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
  global $LogFile, $UserLogFile, $Time, $UserLogsExist;
  $logWrittenA = $logWrittenB = FALSE;
  // / Format the actual log message.
  $EntryOutput = sanitize('OP-Act: '.$Time.', '.$EntryText.PHP_EOL, FALSE);
  // / Write the actual log file.
  $logWrittenA = file_put_contents($LogFile, $EntryOutput, FILE_APPEND);
  if ($LogToUser && $UserLogsExist) $logWrittenB = file_put_contents($UserLogFile, $EntryOutput, FILE_APPEND);
  // / Check if either operation failed.
  if (!$logWrittenA or !$logWrittenB) $LogWritten = FALSE;
  // / Clean up unneeded memory.
  $logWrittenA = $logWrittenB = NULL;
  unset ($logWrittenA, $logWrittenB);
  return($LogWritten); } 

// / A function to load the system cache, which contains the master user list.
// / Cache files are stored as .php files and cache data is stored as an array. This ensures the files
// / cannot simply be viewed with a browser to reveal sensitive content. The data must be programatically
// / displayed or opened locally in a text editor.
// / Outputs a completely populated $Users array.
function loadCache() { 
  // / Set variables. 
  global $Users, $CacheFile, $Salts;
  // / Loop through each hard-coded user in the config.php file.
  foreach ($Users as $User) { 
    // / Check if the user password specified is already hashed.
    if (!$User[4]) $User[3] = hash('sha256', $Salts[0].$User[3].$Salts[0].$Salts[1].$Salts[2].$Salts[3]); } 
  // / Load the cache file containing the rest of the users.
  require ($CacheFile);
  // / Verify that required variables are present in the cache file.
  if (!isset($PostConfigUsers)) $PostConfigUsers = array();
  // / Combine the hard coded users from the config file with the rest of the users from the cache file.
  $Users = array_merge($PostConfigUsers, $Users);
  $CacheIsLoaded = TRUE;
  // / Return an array of all users as well as a boolean to tell us if the function succeeded.
  return(array($Users, $CacheIsLoaded)); }

// / A function to load core files.
// / Accepts either an array of cores or a single string.
// / If input is an array, CoresLoaded output is an array. If input is a string, CoresLoaded output is a string.
function loadCores($coresToLoad) { 
  // / Set variables. 
  global $AvailableCores, $ConfigIsLoaded; 
  $error = FALSE;
  $CoresLoaded = array();
  $CoresAreLoaded = TRUE;
  // / Check if $coresToLoad is an array.
  if (is_array($coresToLoad)) { 
    // / Loop through each core in the array element.
    foreach ($coresToLoad as $coreToLoad) { 
      // / Determine what the name of the specified core should be.
      $coreFile = strtolower($coreToLoad).'Core.php';
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
    $coreFile = strtolower($coresToLoad).'Core.php';
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
  return(array($CoresLoaded, $CoresAreLoaded)); }

// / A function to check that the platform is running a consistent version.
// / Checks that the $EngineVersionInfo variable in 'versionInfo.php' matches the $EngineVersion variable in 'compatibilityCore.php'.
function checkVersionInfo() {
  // / Set variables.
  global $CoresLoaded, $EngineVersion;
  $VersionsMatch = FALSE;
  // / Check that the Compatibility Core is loaded.
  if (in_array('COMPATIBILITY', $CoresLoaded));
  // / If for any reason the Compatibility Core is not loaded we will skip this entire version check.
  else {
    logEntry('Compatibility Core disabled. Skipping version check.', FALSE); 
    $VersionMatch = TRUE; }
  // / If the Compatibility Core is enabled we will also retrieve the '$EngineVersionInfo' variable from versionInfo.php to compare against.
  if (file_exists('versionInfo.php')) require('versionInfo.php');
  // / Now that we've gathered version information from two sources within the engine, we compare them.
  if (isset($EngineVersion) && isset($EngineVersionInfo)) if ($EngineVersion === $EngineVersionInfo) $VersionsMatch = TRUE; 
  // / Return TRUE if the both version strings match. Return FALSE if the two versions strings do not match.
  return($VersionsMatch); }

// / A function to determine if the supplied session is valid.
function verifySession($SessionIDInput, $UserInput, $ClientTokenInput) {
  global $Users, $Salts, $Minute, $LastMinute, $ServerToken, $OldServerToken;
  // / Set variables.
  $SessionIsVerified = $sessionID = $user = $userFound = FALSE;
  if ($SessionIDInput !== FALSE && $UserInput !== FALSE && $ClientTokenInput !== FALSE) {
    // / Loop through the userlist and look for the specified username.
    foreach ($Users as $user) { 
      // / If the username is found we set a flag and stop iterating through the list.
      if ($UserInput === $user[1]) { 
        $userFound = TRUE;
        break; } }
      // / Create a temporary session ID to see if it matches the one supplied by the user.
      $sessionID = hash('sha256', $Minute.$Salts[0].$ClientTokenInput.$user[3].$Salts[1].$ServerToken.$Salts[2].$user[1]);
      // / Perform the check to see if the session ID is current.
      if ($sessionID === $SessionIDInput) $SessionIsVerified = TRUE;
      // / If the session ID is not current generate an older one that is still valid.
      else $sessionID = hash('sha256', $LastMinute.$Salts[0].$ClientTokenInput.$user[3].$Salts[1].$OldServerToken.$Salts[2].$user[1]);
      // / Perform another check to see if the session ID provided is valid.
      if ($sessionID === $SessionIDInput) $SessionIsVerified = TRUE;
  // / Clean up unneeded memory.
  $sessionID = $user = $userFound = NULL;
  unset($sessionID, $user, $userFound);
  return ($SessionIsVerified); } }

// / A function to validate and sanitize requried session and POST variables.
function verifyGlobals() { 
  // / Set variables. 
  $SessionID = $GlobalsAreVerified = $RequestTokens = $SessionType = $SessionIsVerified = $UserID = FALSE;
  $UserDir = '';
  // / Set authentication credentials from supplied inputs when inputs are supplied.
  // / This code is performed when a user submits enough credentials & tokens to start a new session.
  // / There is a structure to how inputs are processed.
  // / Inputs come from the user as POST requests.
  // / POSTS should NEVER be used directly as program output or in any assignment or statement outside of the verifyGlobals() function.
  // / POSTS that have been processed for proper syntax are allowed to become SESSION variables. This happens in the verifyGlobals() function.
  // / SESSION variables should NEVER be used directly as program output or in any assignment or statement outside of the verifySessions() function.
  // / SESSION variables that have been processed for authenticity are allowed to become PHP variables. This happens in the verifyGlobals() function.
  // / PHP variables use regular $VARIABLE syntax. Per Diablo engine conventions, these are expected to be global in scope & have an uppercase first letter.
  // / If you find yourself using native functionality via POSTs or SESSION variables; STOP and evaluate the reasoning for processing unauthenticated data.
  if (isset($_POST['UserInput']) && isset($_POST['PasswordInput']) && isset($_POST['ClientTokenInput'])) { 
    $_SESSION['UserInput'] = $UserInput = str_replace(str_split('|\\/~#[](){};:$!#^&%@>*<"\''), ' ', trim($_POST['UserInput']));
    $_SESSION['PasswordInput'] = $PasswordInput = str_replace(str_split('|\\/~#[](){};:$!#^&%@>*<"\''), ' ', trim($_POST['PasswordInput']));  
    $_SESSION['ClientTokenInput'] = $ClientTokenInput = $_POST['ClientTokenInput']; 
    $SessionType = 'LOGIN';
    $UserID = 'DEFAULT';
    $GlobalsAreVerified = TRUE; }
  // / When no authentication credentials were supplied we initialize all variables to NULL.
  else $UserInput = $PasswordInput = $ClientTokenInput = NULL;
  // / Verify the session ID or set a new one.
  // / This code is performed when a user submits tokens and a session ID via SESSION request to continue an existing session.
  if (isset($_SESSION['UserInput']) && isset($_SESSION['SessionID']) && isset($_SESSION['ClientTokenInput'])) {
    $_SESSION['UserInput'] = $UserInput = str_replace(str_split('|\\/~#[](){};:$!#^&%@>*<"\''), ' ', $_SESSION['UserInput']);  
    $_SESSION['SessionIDInput'] = $SessionIDInput = str_replace(str_split('|\\/~#[](){};:$!#^&%@>*<"\''), ' ', $_SESSION['SessionID']);  
    $_SESSION['ClientTokenInput'] = $ClientTokenInput = str_replace(str_split('|\\/~#[](){};:$!#^&%@>*<"\''), ' ', $_SESSION['ClientTokenInput']);  
    $SessionIsVerified = verifySession($SessionIDInput, $UserInput, $ClientTokenInput);
    if ($SessionIsVerified) $_SESSION['SessionID'] = $SessionID = $SessionIDInput;
    else $_POST['SessionID'] = $_SESSION['SessionID'] = $SessionID = $SessionIDInput = NULL; 
    $SessionType = 'SESSION';
    if ($SessionIsVerified) $GlobalsAreVerified = TRUE; }
  // / This code is performed when a user submits tokens and a session ID via POST request to continue an existing session.
  if (isset($POST['UserInput']) && isset($POST['SessionID']) && isset($POST['ClientTokenInput'])) { 
    $_SESSION['UseUserInputrName'] = $UseriNPUT = str_replace(str_split('|\\/~#[](){};:$!#^&%@>*<"\''), ' ', trim($POST['UserInput']));
    $_SESSION['SessionIDInput'] = $SessionIDInput = str_replace(str_split('|\\/~#[](){};:$!#^&%@>*<"\''), ' ', trim($_POST['SessionID']));
    $_SESSION['ClientTokenInput'] = $ClientTokenInput = str_replace(str_split('|\\/~#[](){};:$!#^&%@>*<"\''), ' ', trim($POST['ClientTokenInput']));
    $SessionIsVerified = verifySession($SessionIDInput, $UserInput, $ClientTokenInput);
    if ($SessionIsVerified) $_SESSION['SessionID'] = $SessionID = $SessionIDInput;
    else $_POST['SessionID'] = $_SESSION['SessionID'] = $SessionID = $SessionIDInput = NULL; 
    $SessionType = 'POST';
    if ($SessionIsVerified) $GlobalsAreVerified = TRUE; }
  // / Check if the user is attempting to login & prepare variables required to generate ClientTokens.
  // / This code is performed when a user requests tokens to begin the login process.
  if (isset($_POST['RequestTokens']) && isset($_POST['UserInput'])) {
    $_SESSION['UserInput'] = $UserInput = str_replace(str_split('|\\/~#[](){};:$!#^&%@>*<"\''), ' ', trim($_POST['UserInput']));
    $RequestTokens = TRUE; }
  if ($SessionType === 'POST' or $SessionID === 'SESSION') { 
    // / Set the UserDir based on user input or most recently used.
    if (isset($_POST['UserDir'])) $_SESSION['UserDir'] = str_replace(str_split('|\\~#[](){};:$!#^&%@>*<"\''), ' ', trim($_POST['UserDir']));
    if (!isset($_SESSION['UserDir']) or $_SESSION['UserDir'] == '') $_SESSION['UserDir'] = DIRECTORY_SEPARATOR; 
    $UserDir = $_SESSION['UserDir']; }
  return(array($UserID, $UserInput, $PasswordInput, $SessionID, $ClientTokenInput, $UserDir, $RequestTokens, $GlobalsAreVerified, $SessionType, $SessionIsVerified)); }

// / A function to throw the login page when needed.
function requireLogin() { 
  // / Check that a login page exits.
  if (file_exists('login.php'))
    // / Load the login page.
    include('login.php');
    // / Kill the script to give the user a chance to use the login page.
    logEntry('User is not logged in.', FALSE);
    die();
  return(array()); }

// / A function to generate initial user tokens before a user has fully logged in.
// / When a user returns to the site they will be prompted to enter their username.
// / The server will generate user tokens for the specified username and provide them to the current user.
// / The current user now has up to 2 minutes to enter the token with the correct password.
// / Requiring a valid token & invalidating issued tokens often prevents replay attacks & complicates eavesdropping for credentials.
// / If the "$Old" variable is set to TRUE, tokens for the previous minute will be generated instead.
function getClientTokens($UserInput, $Old) {
  // / Set variables.
  global $Date, $Users, $Minute, $LastMinute, $Salts;
  if ($Old === TRUE) $Minute = $LastMinute;
  // / Loop through all users to check for the supplied username.
  foreach ($Users as $user) { 
    $UserID = $user[0];
    // / Continue ONLY if the $UserInput matches a valid $UserName.
    if ($user[1] === $UserInput) { 
      $UserName = $user[1];
      $ClientToken = hash('sha256', $Minute.$Salts[0].$user[3].$Salts[0].$Salts[1].$Salts[2].$Salts[3]); }
    // / If the specified user does not exist, provide the user with a fake & invalid client token.
    else {
      $UserName = $UserInput;
      $ClientToken = hash('sha256', $Minute.$Date.$Salts[2].$LastMinute); } } 
  // / Clean up unneeded memory.
  $user = NULL;
  unset($user);
  return($ClientToken); } 

// / A function to generate new server tokens.
function generateServerToken() {
  global $Minute, $LastMinute, $Salts, $ServerToken, $OldServerToken;
  $ServerToken = NULL;
  $TokenIsValid = FALSE;
  $ServerToken = hash('sha256', $Minute.$Salts[1].$Salts[3]);
  $OldServerToken = hash('sha256', $LastMinute.$Salts[1].$Salts[3]);
  $TokenIsValid = TRUE;
  return(array($ServerToken, $OldServerToken, $TokenIsValid)); }

// / A function to generate new user tokens and validate supplied ones.
// / This is the secret sauce behind full password encryption in-transit.
function generateTokens($ClientTokenInput, $UserInput) { 
  // / Set variables. 
  global $Users, $Minute, $LastMinute, $Salts, $ServerToken, $OldServerToken;
  $ClientToken = $oldClientToken = $user = NULL;
  $TokensAreValid = FALSE;
  // / Iterate through each defined user.
  foreach ($Users as $user) { 
    // / Continue ONLY if the $UserInput matches a valid $UserName.
    if ($user[1] === $UserInput) { 
      $ClientToken = hash('sha256', $Minute.$Salts[0].$user[3].$Salts[0].$Salts[1].$Salts[2].$Salts[3]);
      $oldClientToken = hash('sha256', $LastMinute.$Salts[0].$user[3].$Salts[0].$Salts[1].$Salts[2].$Salts[3]);
      if ($ClientTokenInput === $ClientToken) $TokensAreValid = TRUE; 
      if ($ClientTokenInput === $oldClientToken) {
        $ClientToken = $OldClientToken;
        $ServerToken = $OldServerToken; 
        $TokensAreValid = TRUE; } } }
  // / If the specified user does not exist, provide the user with fake & invalid tokens.
  if (!$TokensAreValid) { 
    $ClientToken = hash('sha256', $Minute.$Date.$Salts[2].$Salts[3].$LastMinute);
    $ServerToken = hash('sha256', $LastMinute.$Date.$Salts[0].$Salts[1].$Minute); }
  // / Clean up unneeded memory.
  $oldClientToken = $user = NULL;
  unset($oldServerToken, $user); 
  return(array($ClientToken, $ServerToken, $TokensAreValid)); } 

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
  $sessionIsVerified = $sessionID = $userID = $UserID = $UserName = $PasswordIsCorrect = $UserIsAdmin = $AuthIsComplete = $UserEmail = $SessionID = $SessionIsVerified = FALSE;
  // / Iterate through each defined user.
  foreach ($Users as $user) { 
    $UserID = $user[0];
    // / Continue ONLY if the $UserInput matches a valid $UserName.
    if ($user[1] === $UserInput) { 
      $_SESSION['UserInput'] = $UserName = $user[1];
      // / Continue ONLY if all tokens match and the password hash is correct.
      if ($ServerToken.$ClientToken.$user[3] === $ServerToken.$ClientTokenInput.$PasswordInput) {
        // / Define variables for a new session.
        $_SESSION['SessionID'] = $SessionID = hash('sha256', $Minute.$Salts[0].$ClientToken.$user[3].$Salts[1].$ServerToken.$Salts[2].$user[1]);
        $_SESSION['ClientTokenInput'] = $ClientToken;
        $UserEmail = $user[2];
        // / Call verifyGlobals() again to start the newly defined session.
        list ($userID, $UserInput, $PasswordInput, $sessionID, $ClientTokenInput, $UserDir, $RequestTokens, $GlobalsAreVerified, $SessionType, $sessionIsVerified) = verifyGlobals();
        $PasswordIsCorrect = $AuthIsComplete = $SessionIsVerified = TRUE; 
        // / Here we grant the user their designated permissions.
        if (is_bool($user[4])) { 
          $UserIsAdmin = $User[4]; 
          // / Once we authenticate a user we no longer need to continue iterating through the userlist, so we stop.
          break; } } } }
  // / Clean up unneeded memory.
  $sessionIsVerified = $user = $userID = $sessionID = NULL;
  unset($sessionIsVerified, $user, $userID, $sessionID);
  // / Cleanup sensitive memory only if is it not required to complete authentication.
  // / This code should only fire if the user login credentials were incorrect.
  if ($PasswordIsCorrect && $SessionIsVerified) cleanupSensitiveMemory();
  return(array($UserID, $UserName, $UserEmail, $PasswordIsCorrect, $UserIsAdmin, $SessionID, $AuthIsComplete, $SessionID, $SessionIsVerified)); }

// / A function to generate a user log file to the DATA library.
function generateUserLogs($UserID) { 
  // / Set variables. 
  $UserLogsExist = FALSE;
  global $Date, $Time, $Salts, $SessionIsVerified, $LibrariesActive;
  // / Check that the DATA library is enabled & activated.
  list ($DataLibCheck, $DataLibrary) = libCheck('DATA');
  // / Check that the session is verified, the UserID is set, and the data library passed all checks.
  if (isset($SessionIsVerified)) if ($SessionIsVerified) if (isset($UserID)) if ($UserID !== 'DEFAULT') if ($DataLibCheck) {
    // / Define the directory structure for the logs.
    // / The $LibrariesActive[] array is defined in config.php where the user was instructed specifically to include trailing slashes on all directories.
    $UserDataDir = $LibrariesActive[$DataLibrary][2].$UserID.DIRECTORY_SEPARATOR;
    $UserLogDir = $UserDataDir.'Logs'.DIRECTORY_SEPARATOR;
    $UserLogFile = $UserLogDir.'Log-'.$Date.'-'.hash('sha256', $Salts[2].$UserID.$Salts[3].$Date.$Salts[1]).'.txt'; 
    // / Detect which folders exist already & create any that are missing.
    if (!is_dir($UserDataDir) && $UserDataDir !== '') mkdir($UserDataDir); 
    if (!is_dir($UserLogDir)) mkdir($UserLogDir); 
    if (is_dir($UserLogDir)) file_put_contents($UserLogFile, 'OP-Act: '.$Time.' Created a user log file, "'.$UserLogFile.'".'.PHP_EOL);
    if (file_exists($UserLogFile)) $UserLogsExist = TRUE; }
  return (array($UserLogsExist, $UserLogDir, $UserLogFile, $UserDataDir)); }

// / A function to define the default $UserCacheData used as $arrayData in generateUserCache and loadUserCache. 
// / Without this function this data would need to be hard-coded, making my job harder! We don't want that.
function generateDefaultUserCacheData() { 
  global $UserCacheRequiredOptions, $UserCacheArrayData;
  // / Define the default data for a fresh installation of the $UserCacheFile.
  // / This is specially encoded to be written in a machine-readable .php file that will be included in generateUserCache().
  $UserCacheArrayData = '\'FRIENDS\'=>\'\', \'BLOCKED\'=>\'\', \'COLOR\'=>\'BLUE\', \'FONT\'=>\'ARIAL\', \'TIMEZONE\'=>\'America/New_York\', \'TIPS\'=>\'ENABLED\', \'THEME\'=>\'ENABLED\', \'HRAI\'=>\'ENABLED\', \'HRAIAUDIO\'=>\'HRAIAUDIO\', \'LANDINGPAGE\'=>\'DEFAULT\', \'STAYLOGGEDIN\'=>\'ENABLED\'';
  // / Define an array of default cache elements that every user cache file must contain.
  // / Note that the values in this array MUST match the $UserCacheArrayData above which containing the valid defaults for each element of $UserOptions[].
  $UserCacheRequiredOptions = array('FRIENDS', 'BLOCKED', 'COLOR', 'FONT', 'TIMEZONE', 'DISPLAYNAME', 'TIPS', 'THEME', 'HRAI', 'HRAIAUDIO', 'LANDINGPAGE', 'STAYLOGGEDIN'); 
  return (array($UserCacheArrayData, $UserCacheRequiredOptions)); }

// / A function to generate a missing user cache file. Useful when new users log in for the first time.
// / The $UserCacheData variable gets crudely validated and turned into $UserOptions when loaded 
// / by the loadUserCache() function.
function generateUserCache($UserID) { 
  // / Set variables. Note the $UserCacheExists, $UserCache and $UserCacheDir are all assumed to be false unless they are changed to something valid.
  // / If $UserCacheExists, $UserCache or $UserCacheDir return FALSE, the calling code should assume this function failed.
  global $Salts, $UserCacheRequiredOptions, $UserCacheArrayData;
  $UserCacheExists = $UserCache = $UserCacheDir = FALSE;
  $UserCacheDir = 'Cache'.DIRECTORY_SEPARATOR.'Data'.DIRECTORY_SEPARATOR.$UserID.DIRECTORY_SEPARATOR;
  if (!file_exists($UserCacheDir)) $dirExists = mkdir($UserCacheDir);
  // / Define the $UserCacheFile.
  $UserCacheFile = $UserCacheDir.'UserCache-'.hash('sha256',$Salts[0].'CACHE'.$UserID).'.php';
  // / Wrap the data above in the proper PHP array syntax so it can be included later in the function.
  $userCacheData = '<?php'.PHP_EOL.'$userCacheData = array('.$UserCacheArrayData.');'.PHP_EOL;
  // / Write default cache data to the $UserCacheFile. 
  $UserCacheExists = file_put_contents($UserCacheFile, $userCacheData);
  // / Clean up unneeded memory.
  $userCacheData = $arrayData = $dirExists = NULL;
  unset($userCacheData, $arrayData, $dirExists); 
  return(array($UserCacheExists, $UserCacheFile, $UserCacheDir)); } 

// / A function to save entries to the user cache.
// / If prepend is set to TRUE, this function will PREPEND the entry to the cache file.
// / By default this function will APPEND the entry to the cache file.
// / For security, this function does not accept input from arguments. 
// / Instead it saves the existing $UserOptions from memory to the $UserCache. 
// / Prepended entries are over-rided by newer entries. This is useful when new entries are added for new functionality 
// / without destroying existing configuration data.
function saveUserCache($Prepend) { 
  // / Set variables.
  global $UserCache, $UserOptions;
  // / Note that this particular variable we declare as a blank string by default. Usually we would use FALSE, but here if we do that
  // / we could corrupt a valid $UserCache. So we use a blank string which won't add anything to our PHP syntax'd cache file.
  $key = $uOpt = $newEntry = '';
  foreach ($UserOptions as $key => $uOpt) { 
    $key = '\''.$key.'\'';
    $uOpt = '\''.$uOpt.'\'';
    $newEntry = $key.'=>'.$uOpt.';'; }
  if ($Prepend) { 
    $handle = fopen($UserCache, "r+");
    $len = strlen($newEntry);
    $final_len = filesize($UserCache) + $len;
    $cache_old = fread($handle, $len);
    rewind($handle);
    $i = 1;
    while (ftell($handle) < $final_len) {
      fwrite($handle, $newEntry);
      $newEntry = $cache_old;
      $cache_old = fread($handle, $len);
      fseek($handle, $i * $len);
      $i++; } }
  else $UserCacheWritten = file_put_contents($UserCache, $newEntry, FILE_APPEND);
  // / Clean up unneeded memory.
  $newUserCacheData = $handle = $len = $final_len = $cache_old = $i = $newEntry = $key = $uOpt = $Prepend = NULL;
  unset($newUserCacheData, $handle, $len, $final_len, $cache_old, $i, $newEntry, $key, $uOpt, $Prepend);
  return($UserCacheWritten); }

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
        return(array($UserOptions, $UserCacheIsLoaded)); }
      // / If the cache data isn't an array we return an error and stop.
      if (!is_array($userCacheData)) { 
        $userCache = $userCacheData = NULL; 
        unset($userCache, $userCacheData); 
        return(array($UserOptions, $UserCacheIsLoaded)); }
      // / If the user cache is valid we delete the temporary data and validate each option.
      $UserOptions = $userCacheData;
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
  return(array($UserOptions, $UserCacheIsLoaded)); }

// / A function to initialize the libraries into categories based on their status.
function loadLibraries() { 
  // / Set variables. Note the default libraries that can be used as filters later in the application.
  global $Libraries, $LibrariesDefault;
  $LibrariesActive = array();
  $LibrariesInactive = array();
  $LibrariesCustom = array();
  // / Iterate through each library specified in config.php.
  foreach ($Libraries as $Library) { 
    // / If the array is not part of the default libraries it is assumed to be a custom library.
    if (!in_array($Library[0], $LibrariesDefault) && $Library[1]) array_push($LibrariesCustom, $Library); 
    // / If a libary is enabled it is marked as active and can be used as a filter later or to display as active in a GUI.
    if ($Library[1]) array_push($LibrariesActive, $Library);
    // / If a libary is disabled it is marked as inactive and can be used as a filter later or to display as inactive in a GUI.
    else array_push($LibrariesInactive, $Library); }
  $LibrariesAreLoaded = TRUE;
  return(array($LibrariesActive, $LibrariesInactive, $LibrariesCustom, $LibrariesDefault, $LibrariesAreLoaded)); } 

// / A function to read the data from the supplied array of libraries and load their contents.
// / Note that $LibrariesActive is re-defined after this function runs. So it is specified as an argument to the function and a return value.
function loadLibraryData() {
  // / Set variables. Note that we assume the function is a sucess unless an iteration of the loop changes $LibraryDataIsLoaded to false.
  global $LibrariesActive;
  $LibraryDataIsLoaded = TRUE;
  $LibraryError = FALSE;
  // / Validate the library location for each library.
  foreach ($LibrariesActive as $LibraryActive) {
    // / Check that the selected library is actually supposed to be activated.
    if ($LibraryActive[1]) { 
      // / Check that the libraray data directory exists. 
      // / If the libraray data directory exists we scan its contents to an array at $LibraryActive[3]. 
      if (file_exists($LibraryActive[2])) $LibraryActive[3] = scandir($LibraryActive[2]); 
      // / If the library data directory does not exist, we set the error to $LibraryActive[2] and $LibraryDataIsLoaded to FALSE.
      else { 
        $LibraryDataIsLoaded = FALSE;
        $LibraryError = $LibraryActive[2]; } }
    // / If the selected library is not supposed to be activated, we set the $LibraryError to $LibraryActive[1] & $LibraryDataIsLoaded to FALSE.
    else { 
      $LibraryDataIsLoaded = FALSE;
      $LibraryError = $LibraryActive[0]; }
    // / Stop validating as soon as an error is thrown.
    if (!$LibraryDataIsLoaded) break; }
  return(array($LibraryError, $LibraryDataIsLoaded)); }  

// / A function to determine if a notifications file exists for the current user and generate one if missing.
// / A notification is considered a single line of the notifications file.
function generateNotificationsFile($UserID, $UserDataDir) { 
  // / Set variables. Note that we assume the $notificationsCheck is true until we verify that a $NotificationsFile exists.
  global $Salts;
  $NotificationsFile = $UserDataDir.'UserNotifications-'.hash('sha256',$Salts[1].'NOTIFICATIONS'.$UserID).'.php';
  $NotificationsFileExists = FALSE;
  // / Detect if no file exists and try to create one.
  if (!file_exists($NotificationsFile)) file_put_contents($NotificationsFile, ''); 
  // / If the file operation operation returns false, check again.
  if (file_exists($NotificationsFile)) $NotificationsFileExists = TRUE;
  return(array($NotificationsFileExists, $NotificationsFile)); } 

// / A function for loading notifications.
// / A notification is considered a single line of the notifications file.
function loadNotifications() {
  // / Set variables. 
  global $NotificationsFile;
  $Notifications = file($NotificationsFile);
  // / Check that $Notifications is actually an array.
  if (!is_array($Notifications) or !$Notifications) $Notifications = array(); 
  return($Notifications); }

// / A function to prepare a single notification for the UI.
// / Notifications are comprised of the date they were created and the content of the notification.
// / These two parts of a notification are separated by a "tab" character.
// / NOTE: If a notification entry begins with a single character of whitespace, the notification is "unread." 
// / Example:     [date]["TAB"][notification]
function decodeNotification($Notification) { 
  $NotificationDate = $NotificationContent = FALSE;
  // / Please note that the whitespace in the "explode" below is actually a TAB character!
  // / Also sanitize the $Notification before loading it.
  list ($NotificationDate, $NotificationContent) = explode("  ", sanitize($Notification, TRUE));
  return(array($NotificationDate, $NotificationContent)); }

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
  return(array($NotificationsFileUpdated)); }

// / A function for purging notifications.
// / A notification is considered a single line of the notifications file.
// / Notifications are not stored with any corresponding serialization or indexing data.
// / The date-time of the notifications combined with its content determines unique identity.
function purgeNotification($NotificationToPurge) { 
  // / Set variables. Note that we assume the $NotificationsFileWritten and $NotificationsPurged are false until the notification
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
  return(array($NotificationPurged, $Notifications)); }

// / A function to push a notification to a user.
function pushNotification($NotificationToPush, $TargetUserID) { 
  // / Sanitize the notification with strict character enforcement.
  $NotificationToPush = sanitize($NotificationToPush, TRUE);


  return($NotificationSent); }

// / A function for the user to send an email.
function sendEmail($address, $content, $template) { 

} 
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / The following code specifies the logic flow for the session.

// / Reset the time limit for script execution.
set_time_limit(0);

// / This code verifies the date & time for file & log operations.
list ($Date, $Time, $Minute, $LastMinute) = verifyDate();

// / This code verifies the integrity of the application.
// / Also generates required directories in case they are missing & creates required log & cache files.
list ($LogFile, $CacheFile, $InstallationIsVerified) = verifyInstallation();
if (!$InstallationIsVerified) dieGracefully(3, 'Could not verify installation!', FALSE);
else if ($Verbose) logEntry('Verified installation.', FALSE);

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
list ($UserID, $UserInput, $PasswordInput, $SessionID, $ClientTokenInput, $UserDir, $RequestTokens, $GlobalsAreVerified, $SessionType, $SessionIsVerified) = verifyGlobals();
if (!$GlobalsAreVerified) if ($RequestTokens && $UserInput === NULL) requireLogin(); 
else if ($Verbose) logEntry('Verified global variables.', FALSE);

// / If the globals have been verified, there is enough user supplied information to continue authenticating the user, so the script will continue.
// / If the globals have not been verified, there is not enough user supplied information to continue. The code in the following 'if' statement will be skipped.
if ($GlobalsAreVerified) {
  // / This code ensures that a same-origin UI element generated the login request.
  // / Also protects against packet replay attacks by ensuring that the request was generated recently and by making each request unique. 
  list ($ClientToken, $ServerToken, $TokensAreValid) = generateTokens($ClientTokenInput, $UserInput);
  if (!$TokensAreValid) dieGracefully(7, 'Invalid tokens!', FALSE);
  else if ($Verbose) logEntry('Generated tokens.', FALSE);
  
  // / Do not require authentication if the user has proven they are in the middle of a valid session.
  if (!$SessionIsVerified) { 
    // / This code validates credentials supplied by the user against the hashed ones stored on the server.
    // / Also removes the $Users user list from memory so it can not be leaked.
    // / Displays a login screen when authentication fails and kills the application. 
    list ($UserID, $UserName, $UserEmail, $PasswordIsCorrect, $UserIsAdmin, $SessionID, $AuthIsComplete, $SessionID, $SessionIsVerified) = authenticate($UserInput, $PasswordInput, $ClientToken, $ServerToken, $ClientTokenInput);
    if (!$PasswordIsCorrect or !$AuthIsComplete) dieGracefully(8, 'Invalid username or password!', FALSE); 
    else if ($Verbose) logEntry('Authenticated UserName '.$UserName.', UserID '.$UserID.', SessionID '.$SessionID.'.', FALSE); }
  else if ($Verbose) logEntry('Verified existing session.', FALSE);

  // / This code builds arrays of good & bad libraries.
  // / Libraries are directory for storing specific types of information. 
  list ($LibrariesActive, $LibrariesInactive, $LibrariesCustom, $LibrariesDefault, $LibrariesAreLoaded) = loadLibraries();
  if (!$LibrariesAreLoaded) dieGracefully(9, 'Could not load libraries!', FALSE);
  else if ($Verbose) logEntry('Loaded libraries.', FALSE);

  // / This code verifies each active library directory exists.
  // / Verified libraries receive the $LibrariesActive[3] element & become fully activated. 
  // / $LibrariesActive[0] contains the name of the library in all caps. Used as the array key.
  // / $LibrariesActive[1] contains a boolean value. TRUE enables the library & FALSE disables the library.
  // / $LibrariesActive[2] contains a file path to the user-specific library 
  // / $LibrariesActive[3] contains an array containing library contents.
  // / Instead of accepting $LibrariesActive as an argument and re-specifying it as a return value, we represent it in global scope in the loadLibraryData function.
  list ($LibraryError, $LibraryDataIsLoaded) = loadLibraryData();
  if (!$LibraryDataIsLoaded) dieGracefully(10, 'Could not load library data from '.$LibraryError.'!', FALSE);
  else if ($Verbose) logEntry('Loaded library data.', FALSE);

  // / This code generates a user log file if none exists. Useful for initializing new users logging-in for the first time.
  // / This code also specifies the $UserDataDir which is a direct handle to the users subdirectory within the DATA library.
  list ($UserLogsExists, $UserLogDir, $UserLogFile, $UserDataDir) = generateUserLogs($UserID);
  if (!$UserLogsExists) dieGracefully(11, 'Could not generate a user log file!', FALSE);
  else if ($Verbose) logEntry('Created user logs.', FALSE);

  // / This code generates a user cache file if none exists. Useful for initializing new users logging-in for the first time.
  list ($UserCacheExists, $UserCache, $UserCacheDir) = generateUserCache($UserID);
  if (!$UserCacheExists) dieGracefully(12, 'Could not generate a user cache file!', TRUE);
  else if ($Verbose) logEntry('Created user cache.', TRUE);
  
  // / This code loads the user file containing the user specific settings configuration into memory.
  list ($UserOptions, $UserCacheExists) = loadUserCache($UserID);
  if (!$UserCacheExists) dieGracefully(13, 'Could not load the user cache file!', TRUE);
  else if ($Verbose) logEntry('Loaded user cache.', TRUE);

  // / This code generates a user notifications file if none exists. Useful for initializing new users logging-in for the first time.
  list ($NotificationsFileExists, $NotificationsFile) = generateNotificationsFile($UserID, $UserDataDir);
  if (!$NotificationsFileExists) dieGracefully(14, 'Could not generate a user notifications file!', TRUE);
  else if ($Verbose) logEntry('Created user notifications.', TRUE); }
// / The code in this 'else if' statement is triggered when there was not enough information to authenticate the user.
else if ($Verbose) logEntry('Deferring execution to allow user login.', FALSE);
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / The following code triggers specific core functionality which is outputted for other components to use.

// / Return user tokens when requested.
// / This is usually performed when an unauthenticated user is trying to log in. 
// / If a user has supplied a user name to the core along with a request for user tokens, the user tokens for the specified user name will be returned to the user.
// / User tokens are used to secure the session from delayed eavesdropping attempts or replay attacks, and also serve to invalidate the session after 2 minutes.
if ($RequestTokens && !$SessionIsVerified) echo($UserInput.','.getClientTokens($UserInput, FALSE));

// / Return user name, sessionID, and client token when requested.
// / Used to continue an automatically expiring session.
if ($SessionIsVerified) echo($UserInput.','.$SessionID.','.$ClientToken.','.$UserOptions['STAYLOGGEDIN']);
// / -----------------------------------------------------------------------------------