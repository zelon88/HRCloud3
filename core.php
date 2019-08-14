<?php
/*
HonestRepair Diablo Engine  -  Core
https://www.HonestRepair.net
https://github.com/zelon88

Licensed Under GNU GPLv3
https://www.gnu.org/licenses/gpl-3.0.html

Author: Justin Grimes
Date: 4/18/2019
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
// / Perform sanity checks to verify the environment is suitable for running.

// / Detemine the version of PHP in use to run the application.
// / Any PHP version earlier than 7.0 IS STRICTLY NOT SUPPORTED!!!
// / Specifically, PHP versions earlier than 7.0 require the list() functions used to be unserialized. 
// / If you run this application on a PHP version earlier than 7.0 you may experience extremely bizarre or even dangerous behavior.
// / PLEASE DO NOT RUN THIS APPLICATION ON ANYTHING EARLIER THAN PHP 7.0!!! 
// / HONESTREPAIR ASSUMES NO LIABILITY FOR USING THIS SOFTWARE!!!
if (version_compare(PHP_VERSION, '7.0.0') <= 0) die('<a class="errorMessage">ERROR!!! 0, This application is NOT compatible with PHP versions earlier than 7.0. Running this application on unsupported PHP versions WILL cause unexpected behavior!</a>'.PHP_EOL); 

// / Determine the operating system in use to run the application.
// / Any version of Windows IS STRICTLY NOT SUPPORTED!!!
// / Specifically, only Debian-based Linux distros.
// / PLEASE DO NOT RUN THIS APPLICATION ON A WINDOWS OPERATING SYSTEM!!! 
// / HONESTREPAIR ASSUMES NO LIABILITY FOR USING THIS SOFTWARE!!!
if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') die('<a class="errorMessage">ERROR!!! 1, This application is NOT compatible with the Windows Operating System. Running this application on unsupported operating systems WILL cause unexpected behavior!</a>'.PHP_EOL); 
// / ----------------------------------------------------------------------------------

// / ----------------------------------------------------------------------------------
// / Make sure there is a session started and load the configuration file.
// / Also kill the application if $MaintenanceMode is set to  TRUE.
if (session_status() == PHP_SESSION_NONE) session_start();
if (!file_exists('config.php')) $ConfigIsLoaded = FALSE; 
else require_once ('config.php'); 
$ConfigIsLoaded = TRUE; 
if ($MaintenanceMode === TRUE) die('The requested application is currently unavailable due to maintenance.'.PHP_EOL); 
// / ----------------------------------------------------------------------------------

// / ----------------------------------------------------------------------------------
// / The following code sets the functions for the session.

// / A function for sanitizing input strings with varying degrees of tolerance.
// / Filters a given string of | \ ~ # [ ] ( ) { } ; : $ ! # ^ & % @ > * < " / '
// / This function will replace any of the above specified charcters with NOTHING. No character at all. An empty string.
// / Set $strict to TRUE to also filter out backslash characters as well. Example:  /
function sanitize($Variable, $strict) { 
  if (!is_bool($strict)) $strict = TRUE; 
  // / Note that when $strict is TRUE we also filter out backslashes. Not good if you're filtering a URL or path.
  if ($strict === TRUE) $Variable = str_replace(str_split('|\\~#[](){};:$!#^&%@>*<"/\''), '', $Variable);
  if ($strict === FALSE) $Variable = str_replace(str_split('|\\~#[](){};$!#^&%@>*<"\''), '', $Variable);
  return ($Variable); }

// / A function to set the date and time for internal logic like file cleanup.
// / Set variables. 
function verifyDate() { 
  // / Set variables. Create an accurate human-readable date from the servers timezone.
  $Date = date("m-d-y");
  $Time = date("F j, Y, g:i a"); 
  $Minute = int(date('i'));
  $LastMinute = $Minute - 1;
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
  if (!file_exists($LogFile)) $logCheck = file_put_contents($LogFile, 'OP-Act: '.$Time.' Created a log file, "'.$LogFile.'".');
  // / Create a unique identifier for the cache file.
  $CacheFile = 'Cache'.DIRECTORY_SEPARATOR.'Cache-'.hash('sha256',$Salts[0].'CACHE').'.php';
  // / If no cache file exists yet (first run) we create one and write the $PostConfigUsers to it. 
  if (!file_exists($CacheFile)) $cacheCheck = file_put_contents($CacheFile, '<?php'.PHP_EOL.'$PostConfigUsers = array();');
  // / Make sure all sanity checks passed.
  if ($dirCheck && $indexCheck && $logCheck && $cacheCheck) $InstallationIsVerified = TRUE;
  // / Clean up unneeded memory.
  $dirCheck = $indexCheck = $logCheck = $cacheCheck = $requiredDirs = $requiredDir = $dirExists = $indexExists = $logHash = NULL;
  unset($dirCheck, $indexCheck, $logCheck, $cacheCheck, $requiredDirs, $requiredDir, $dirExists, $indexExists, $logHash);
  return(array($LogFile, $CacheFile, $NotificationsFile, $InstallationIsVerified)); }

// / A function to generate useful, consistent, and easily repeatable error messages.
function dieGracefully($ErrorNumber, $ErrorMessage) { 
  // / Set variables. 
  global $LogFile, $Time;
  // / Perform a sanity check on the $ErrorNumber. Hackers are creative and this is a sensitive operation
  // / that could be the target of XSS attacks.
  if (!is_numeric($ErrorNumber)) $ErrorNumber = 0;
  $ErrorOutput = 'ERROR!!! '.$ErrorNumber.', '.$Time.', '.$ErrorMessage.PHP_EOL;
  // / Write the log file. Note that we don't care about success or failure because we're about to kill the script regardless.
  file_put_contents($LogFile, $ErrorOutput, FILE_APPEND);
  die('<a class="errorMessage">'.$ErrorOutput.'</a>'); } 

// / A function to generate useful, consistent, and easily repeatable log messages.
function logEntry($EntryText) { 
  // / Set variables. 
  global $LogFile, $Time;
  // / Format the actual log message.
  $EntryOutput = sanitize('OP-Act: '.$Time.', '.$EntryText.PHP_EOL, TRUE);
  // / Write the actual log file.
  $LogWritten = file_put_contents($LogFile, $EntryOutput, FILE_APPEND);
  return($LogWritten); } 

// / A function to load the system cache, which contains the master user list.
// / Cache files are stored as .php files and cache data is stored as an array. This ensures the files
// / cannot simply be viewed with a browser to reveal sensitive content. The data must be programatically
// / displayed or opened locally in a text editor.
function loadCache() { 
  // / Set variables. 
  global $Users, $CacheFile, $HashConfigUserinfo;
  foreach ($Users as $User) { 
    if ($HashConfigUserinfo) $User[3] = hash('sha256', $Salts[0].$User[3].$Salts[0].$Salts[1].$Salts[2].$Salts[3]); } 
  require ($CacheFile);
  if (!isset($PostConfigUsers)) $PostConfigUsers = array();
  $Users = array_merge($PostConfigUsers, $Users);
  // / Clean up unneeded memory.
  $CacheIsLoaded = TRUE;
  return(array($Users, $CacheIsLoaded)); }

// / A function to load core files.
// / Accepts either an array of cores or a single string.
// / If input is an array, CoresLoaded output is an array. If input is a string, CoresLoaded output is a string.
function loadCores($coresToLoad) { 
  // / Set variables. 
  global $AvailableCores; 
  $CoresLoaded = $error = FALSE;
  if (is_array($coresToLoad)) { 
    $CoresLoaded = array();
    foreach ($coresToLoad as $coreToLoad) { 
      $coreFile = strtolower($coreToLoad).'Core.php';
      if (file_exists($coreFile) && in_array(strtoupper($coreToLoad), $AvailableCores)) { 
        require($coreFile);
        $CoresLoaded = array_push($CoresLoaded, strtoupper($coreToLoad)); }
      else $error = TRUE; } }
  if is_string($coresToLoad) { 
    $coreFile = strtolower($coresToLoad).'Core.php';
    if (file_exists($coreFile) && in_array(strtoupper($coresToLoad), $AvailableCores)) { 
      require($coreFile);
      $CoresLoaded = strtoupper($coresToLoad); } }
  // / Clean up unneeded memory.
  $coresToLoad  = $coreFile = $coreToLoad = NULL;
  unset($coresToLoad, $coreFile, $coreToLoad);
  return ($CoresLoaded, $error); }

// / A function to validate and sanitize requried session and POST variables.
function verifyGlobals() { 
  // / Set variables. 
  global $Salts, $Data;
  $SessionID = $GlobalsAreVerified = FALSE;
  // / Set authentication credentials from supplied inputs when inputs are supplied.
  if (isset($_POST['UserInput']) && isset($_POST['PasswordInput']) && isset($_POST['ClientTokenInput'])) { 
    $_SESSION['UserInput'] = $UserInput = str_replace(str_split('|\\/~#[](){};:$!#^&%@>*<"\''), ' ', $_POST['UserInput']), ENT_QUOTES, 'UTF-8');
    $_SESSION['PasswordInput'] = $PasswordInput = str_replace(str_split('|\\/~#[](){};:$!#^&%@>*<"\''), ' ', $_POST['PasswordInput']), ENT_QUOTES, 'UTF-8');  
    $_SESSION['ClientTokenInput'] = $ClientTokenInput = hash('sha256', $_POST['ClientTokenInput']), ENT_QUOTES, 'UTF-8');  
    $SessionID = $Salts[3].$Date.$Salts[0].$PasswordInput.$UserInput; }
  // / Verify the session ID or set a new one.
  if (isset($_SESSION['SessionID'])) if ($_SESSION['SessionID'] === $Salts[3].$Date.$Salts[0].$PasswordInput.$UserInput) $SessionID = $_SESSION['SessionID']; 
  // / Set the UserDir based on user input or most recently used.
  if (isset($_POST['UserDir'])) $_SESSION['UserDir'] = str_replace(str_split('|\\~#[](){};:$!#^&%@>*<"\''), ' ', $_POST['UserDir']), ENT_QUOTES, 'UTF-8');
  if (!isset($_SESSION['UserDir']) or $_SESSION['UserDir'] == '') $_SESSION['UserDir'] = DIRECTORY_SEPARATOR;
  $UserDir = $_SESSION['UserDir'];
  // / Detect if required variables are set.
  if ($SessionID !== FALSE) $GlobalsAreVerified = TRUE;
  return($UserInput, $PasswordInput, $SessionID, $ClientTokenInput, $UserDir, $GlobalsAreVerified); }

// / A function to throw the login page when needed.
function requireLogin() { 
 if (file_exists('login.php'))
 require ('login.php');
 return(array()); }

// / A function to generate new user tokens and validate supplied ones.
// / This is the secret sauce behind fully password encryption in-transit.
// / Please excuse the lack of comments. Security through obscurity is a bad practice.
// / But no lock is pick proof, especially ones that come with instructions for picking them.
function generateTokens($ClientTokenInput, $PasswordInput) { 
  // / Set variables. 
  global $Minute, $LastMinute;
  $ServerToken = $ClientToken = NULL;
  $TokensAreValid = FALSE;
  $ServerToken = hash('sha256', $Minute.$Salts[1].$Salts[3]);
  $CLientToken = hash('sha256', $Minute.$PasswordInput); 
  $oldServerToken = hash('sha256', $LastMinute.$Salts[1].$Salts[3]);
  $oldCLientToken = hash('sha256', $LastMinute.$PasswordInput);
  if ($ClientTokenInput === $oldClientToken) {
    $ClientToken = $oldClientToken;
    $ServerToken = $oldServerToken; }
  if ($ServerToken !== NULL && $ClientToken !== NULL) $TokensAreValid = TRUE;
  // / Clean up unneeded memory.
  $oldClientToken = $oldServerToken = NULL;
  unset($oldClientToken, $oldServerToken); 
  return(array($ClientToken, $ServerToken, $TokensAreValid); }

// / A function to sanitize an input string. Useful for preparing URL's or filepaths.
function sanitize($Variable, $Strict) { 
  // / Set variables.  
  $VariableIsSanitized = TRUE;
  if (!is_bool($Strict)) $Strict = TRUE; 
  if (!is_string($Variable)) $VariableIsSanitized = FALSE;
  else { 
    if ($Strict === TRUE) $Variable = str_replace(str_split('|\\~#[](){};:$!#^&%@>*<"/\''), '', $Variable);
    if ($Strict === FALSE) $Variable = str_replace(str_split('|\\~#[](){};$!#^&%@>*<"\''), '', $Variable); }
  return (array($Variable, $VariableIsSanitized)); }

// / A function to authenticate a user and verify an encrypted input password with supplied tokens.
function authenticate($UserInput, $PasswordInput, $ServerToken, $ClientToken) { 
  // / Set variables. Note that we try not to include anything here we don't have to because
  // / It's going to be hammered by someone, somewhere, eventually. Less is more in terms of code & security.
  global $Users;
  $UserID = $UserName = $PasswordIsCorrect = $UserIsAdmin = $AuthIsComplete = FALSE;
  // / Iterate through each defined user.
  foreach ($Users as $User) { 
    $UserID = $User[0];
    // / Continue ONLY if the $UserInput matches the a valid $UserName.
    if ($User[1] === $UserInput) { 
      $UserName = $User[1];
      // / Continue ONLY if all tokens match and the password hash is correct.
      if (hash('sha256', $ServerToken.hash('sha256', $ClientToken.$User[3])) === hash('sh256', $ServerToken.hash('sha256', $Salts[0].$PasswordInput.$Salts[0].$Salts[1].$Salts[2].$Salts[3]))) { 
        $PasswordIsCorrect = TRUE; 
        // / Here we grant the user their designated permissions and only then decide $AuthIsComplete.
        if (is_bool($User[4])) {
          $UserIsAdmin = $User[4]; 
          $AuthIsComplete = TRUE; 
          // / Once we authenticate a user we no longer need to continue iterating through the userlist, so we stop.
          break; } } } }
  // / Clean up unneeded memory.
  $UserInput = $PasswordInput = $User = $Users = NULL;
  unset($UserInput, $PasswordInput, $User, $Users); 
  return(array($UserID, $UserName, $UserEmail, $PasswordIsCorrect, $UserIsAdmin, $AuthIsComplete)); }

// / A function to generate a missing user cache file. Useful when new users log in for the first time.
// / The $UserCacheData variable gets crudely validated and turned into $UserOptions when loaded 
// / by the loadUserCache() function.
function generateUserCache() {
  global $Salts, $UserID;
  $UserCacheExists = $UserCache = $UserDataDir = FALSE;
  $UserDataDir = 'Data'.DIRECTORY_SEPARATOR.$UserID.DIRECTORY_SEPARATOR;
  $UserCache = $UserDataDir.'UserCache-'.hash('sha256',$Salts[0].'CACHE'.$UserID).'.php');
  $arrayData = '\'COLOR\'=>\'BLUE\', \'FONT\'=>\'ARIAL\', \'TIMEZONE\'=>\'America/New_York\', \'TIPS\'=>\'ENABLED\', \'THEME\'=>\'ENABLED\', \'HRAI\'=>\'ENABLED\', \'HRAIAUDIO\'=>\'HRAIAUDIO\', \'LANDINGPAGE\'=>\'DEFAULT\',';
  $userCacheData = '<?php'.PHP_EOL.'$userCacheData = array('.$arrayData.');'.PHP_EOL; 
  $UserCacheExists = file_put_contents($UserCacheFile, $userCacheData);
  $userCacheData = $arrayData = NULL;
  unset($userCacheData, $arrayData); 
  return($UserCacheExists, $UserCache, $UserDataDir); } 

// / A function to load the user cache, which contains an individual users option settings.
// / Cache files are stored as .php files and cache data is stored as an array. This ensures the files
// / cannot simply be viewed with a browser to reveal sensitive content. The data must be programatically
// / displayed or opened locally in a text editor.
function loadUserCache() {
  // / Set variables. Note the default options that are used as filters for validating the $UserOptions later.
  // / Also note the user cache is hashed with salts.
  global $Salts, $UserID, $UserCache;
    $requiredOptions = array('COLOR', 'FONT', 'TIMEZONE', 'DISPLAYNAME', 'TIPS', 'THEME', 'HRAI', 'HRAIAUDIO', 'LANDINGPAGE');
    $UserOptions = array();
    $UserCacheIsLoaded = FALSE;
    // / If the user cache exists, load it.
    if (file_exists($UserCache)) {
      require ($UserCache);
      // / If the cache data variable is not set in the cache return an error and stop.
      if (!isset($userCacheData)) { 
        $requiredOptions = $userCache = NULL; 
        unset($requiredOptions, $userCache); 
        return($UserOptions, $UserCacheIsLoaded); }
      // / If the cache data isn't an array we return an error and stop.
      if (!is_array($userCacheData)) { 
        $requiredOptions = $userCache = $userCacheData = NULL; 
        unset($requiredOptions, $userCache, $userCacheData); 
        return($UserOptions, $UserCacheIsLoaded); }
      // / If the user cache is valid we delete the temporary data and validate each option.
      $UserOptions = $userCacheData;
      $userCacheData = NULL;
      unset($userCacheData);
      foreach ($UserOptions as $option => $value) {
        // / If an option is not valid it is removed from memory.
        if (!in_array($option, $requiredOptions)) { 
          $UserOptions[$option] = NULL;
          unset($UserOptions[$option]); } 
      $UserCacheIsLoaded = TRUE; }
  // / Clean up unneeded memory.
  $requiredOptions = $option = $value = NULL;
  unset($requiredOptions, $option, $value);
  return(array($UserOptions, $UserCacheIsLoaded)); }

// / A function to initialize the libraries into categories based on their status.
function loadLibraries() { 
  // / Set variables. Note the default libraries that can be used as filters later in the application.
  global $Libraries;
  $LibrariesDefault = array('DATA', 'MOVIES', 'MUSIC', 'SHOWS', 'CHANNELS', 'DRIVE', 'STREAMS', 'IMAGES', 'DOCUMENTS'); 
  $LibrariesActive = array();
  $LibrariesInactive = array();
  $LibrariesCustom = array();
  // / Iterate through each library specified in config.php.
  foreach ($Libraries as $Library) { 
    // / If the array is not part of the default libraries it is assumed to be a custom library.
    if (!in_array($Library[0], $LibrariesDefault) && $Library[1] == TRUE) array_push($LibrariesCustom, $Library); 
    // / If a libary is disabled it is marked as inactive and can be used as a filter later or to display as inactive in a GUI.
    if ($Library[1] == FALSE) { 
      array_push($LibrariesInactive, $Library);
      continue; }
    // / Any libraries that haven't been filtered on already are assumed to be active and ready for use.
    array_push($LibrariesActive, $Library); }
  $LibrariesAreLoaded = TRUE;
  return(array($LibrariesActive, $LibrariesInactive, $LibrariesCustom, $LibrariesDefault, $LibrariesAreLoaded)); } 

// / A function to read the data from the supplied array of libraries and load their contents.
function loadLibraryData($LibrariesActive) {
  // / Set variables. Note that we assume the function is a sucess unless an iteration of the loop changes $LibraryDataIsLoaded to false.
  $LibraryDataIsLoaded = TRUE;
  $LibraryError = FALSE;
  // / Validate the library location for each library.
  foreach ($LibrariesActive as $LibraryActive) {
    if (file_exists($LibraryActive[2])) $LibraryActive[3] = scandir($LibraryActive[2]);
    // / Throw an error and set the problematic directory as a the $LibraryError.
    if (!file_exists($LibraryActive[2])) { 
      $LibraryDataIsLoaded = FALSE;
      $LibraryError = $LibrariesActive[2];
    // / Stop validating as soon as an error is thrown.
    if (!$LibraryDataIsLoaded) break; }
  return(array($LibrariesActive, $LibraryError, $LibraryDataIsLoaded)); } 

// / A function to determine if a notifications file exists for the current user and generate one if missing.
// / A notification is considered a single line of the notifications file.
function generateNotificationsFile() { 
  // / Set variables. Note that we assume the $notificationsCheck is true until we verify that a $NotificationsFile exists.
  global $Salts, $UserID, $UserDataDir;
  $notificationsCheck = TRUE;
  $NotificationsFile = $UserDataDir.'UserNotifications-'.hash('sha256',$Salts[1].'NOTIFICATIONS'.$UserID).'.php');
  // / Detect if no file exists and try to create one.
  if (!file_exists($NotificationsFile)) $notificationsCheck = file_put_contents($NotificationsFile, ''); 
  if (!$notificationsCheck) $NotificationsFileExists = $NotificationsFile = FALSE;
  // / Clean up unneeded memory.
  $notificationsCheck = NULL;
  unset($notificationsCheck);
  return($NotificationsFileExists, $NotificationsFile); } 

// / A function for loading notifications.
// / A notification is considered a single line of the notifications file.
function loadNotifications($NotificationsFile) { 
  $Notifications = file($NotificationsFile);
  // / Check that $Notifications is actually an array.
  if (!is_array($Notifications) or $Notifications === FALSE) $Notifications = array(); 
  return($Notifications); }

// / A function to prepare a single notification for the UI.
// / Notifications are comprised of the date they were created and the content of the notification.
// / These two parts of a notification are separated by a "tab" character.
// / NOTE: If a notification entry begins with a single character of whitespace, the notification is "unread." 
// / Example:     [date]["TAB"][notification]
function decodeNotification($Notification) { 
  $NotificationDate = $NotificationContent = FALSE;
  // / Please note that the whitespace in the "explode" below is actually a TAB character!
  list ($NotificationDate, $NotificationContent) = explode("  ", $Notification);
  return($NotificationDate, $NotificationContent); }

// / A function for marking notifications as read.
// / A notification is considered a single line of the notifications file.
// / Lines that begin with a single charactere of whitespace are "unread."
// / To mark an item as "read" we simply remove the leading whitespace from unread notifications. 
function readNotification() { 
  // / Set variables.
  global $Notifications;
  // / Iterate through the notifications and detect the first character. 
  foreach ($Notifications as $key=>$notification) { 
    $firstChar = substr($notification, 0, 1);
    // / If the first character of the notification is not currently a space we assume it us currently "unread."
    if ($firstChar !== ' ') { 
      $notification = ltrim($notification); 
      // / We've just detected the removed the space in front of an unread notification and now we're saving it back to the array. 
      $Notifications[$key] = $notification; } }
  // / Write the new array back to the notifications file. 
  // / Note we don't need to reload the $NotificationsFile because we also modifed the copy already in memory.
  $NotificationsFileUpdated = file_put_contents($NotificationsFile, $Notifications);]
  // / Clean up unneeded memory.
  $key = $notification = $firstChar = NULL;
  unset($key, $notification, $firstChar);
  return($NotificationsFileUpdated, $Notifications); }

// / A function for purging notifications.
// / A notification is considered a single line of the notifications file.
// / Notifications are not stored with any corresponding serialization or indexing data.
// / The date-time of the notifications combined with its content determines unique identity.
function purgeNotifications($NotificationToPurge) { 
  // / Set variables. Note that we assume the $NotificationsFileWritten and $NotificationsPurged are false until the notification
  // / is deleted and a file is created. If either of those fail we assume that the operation failed.
  global $NotificationFile, $Notifications;
  $notificationFileWritten = $NotificationPurged = FALSE;
  // / Iterate through the notifications looking for ones that match the specified notification.
  foreach ($Notifications as $key=>$notificationToCheck) { 
    // / If we find a matching notification we remove it from the array that is in memory.
    if ($NotificationToPurge === $notificationToCheck) !== FALSE) unset($Notifications[$key]);
    // / With a new $Notifications array saved to memory, we can dump the modified data back into the $NotificationsFile.
    $notificationFileWritten = file_put_contents($NotificationFile, $Notifications);
    // / Note that below we only mark the check a success once we've verified that we both detected a match and re-wrote the file.
    // / Note that continuing to run after a failure of this operation could result in different states between $Notifications in memory
    // / and the data contained in the $NotificationsFile. 
    if ($notificationFileWritten === TRUE) $NotificationPurged = TRUE; }
  // / Clean up unneeded memory.
  $notificationFileWritten = $key = $notificationsCheck = NULL;
  unset($notificationFileWritten, $key, $notificationsToCheck);
  return($NotificationPurged, $Notifications); }

// / A function to push a notification to a user.
function pushNotification($NotificationToPush, $UserID) { 
  
  return($NotificationSent); }

// / A function to send an email.
function sendEmail($address, $content, $template) { 

}
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / The following code specifies the logic flow for the session.

// / This code verifies the date & time for file & log operations.
list ($Date, $Time, $Minute, $LastMinute) = verifyDate();
if (!$ConfigIsLoaded) die('ERROR!!! 2, '.$Time.', Could not process the Configuration file (config.php)!'.PHP_EOL); 
else if ($Verbose) logEntry('Verified time & configuration.');

// / This code verifies the integrity of the application.
// / Also generates required directories in case they are missing & creates required log & cache files.
list ($LogFile, $CacheFile, $NotificationsFile, $InstallationIsVerified) = verifyInstallation();
if (!$InstallationIsVerified) dieGracefully(3, 'Could not verify installation!');
else if ($Verbose) logEntry('Verified installation.');

// / This code loads & sanitizes the global cache & prepares the user list.
list ($Users, $CacheIsLoaded) = loadCache();
if (!$CacheIsLoaded) dieGracefully(4, 'Could not load cache file!');
else if ($Verbose) logEntry('Loaded cache file.');

// / This code takes in all required inputs to build a session and ensures they exist & are a valid type.
list ($UserInput, $PasswordInput, $SessionID, $ClientTokenInput, $UserDir, $GlobalsAreVerified) = verifyGlobals();
if (!$GlobalsAreVerified) requireLogin(); dieGracefully(5, 'User is not logged in!');
else if ($Verbose) logEntry('Verified global variables.');

// / This code ensures that a same-origin UI element generated the login request.
// / Also protects against packet replay attacks by ensuring that the request was generated recently and by making each request unique. 
list ($ClientToken, $ServerToken, $TokensAreVerified) = generateTokens($ClientTokenInput, $PasswordInput);
if (!$TokensAreValid) dieGracefully(6, 'Invalid tokens!');
else if ($Verbose) logEntry('Generated tokens.');

// / This code validates credentials supplied by the user against the hashed ones stored on the server.
// / Also removes the $Users user list from memory so it can not be leaked.
// / Displays a login screen when authentication fails and kills the application. 
list ($UserID, $UserName, $UserEmail, $PasswordIsCorrect, $UserIsAdmin, $AuthIsComplete) = authenticate($UserInput, $PasswordInput, $ClientToken, $ServerToken);
if (!$PasswordIsCorrect or !$AuthIsComplete) dieGracefully(7, 'Invalid username or password!'); 
else if ($Verbose) logEntry('Authenticated '.$UserName.', '.$UserID.'.');

// / This code builds arrays of good & bad libraries.
// / Libraries are directory for storing specific types of information. 
list ($LibrariesActive, $LibrariesInactive, $LibrariesCustom, $LibrariesDefault, $LibrariesAreLoaded) = loadLibraries();
if (!$LibrariesAreLoaded) dieGracefully(8, 'Could not load libraries!');
else if ($Verbose) logEntry('Loaded libraries.');

// / This code verifies each active library directory exists.
// / Verified libraries receive the $LibrariesActive[3] element & become fully activated. 
// / $LibrariesActive[0] contains the name of the library in all caps. Used as the array key.
// / $LibrariesActive[1] contains a boolean value. TRUE enables the library & FALSE disables the library.
// / $LibrariesActive[2] contains a file path to the user-specific library 
// / $LibrariesActive[3] contains an array containing library contents.
list ($LibrariesActive, $LibraryError, $LibraryDataIsLoaded) = loadLibraryData($LibrariesActive);
if (!$LibraryDataIsLoaded) dieGracefully(9, 'Could not load library data from '.$LibraryError.'!');
else if ($Verbose) logEntry('Loaded library data.');

// / This code generates a user cache file if none exists. Useful for initializing new users logging-in for the first time.
list ($UserCacheExists, $UserCache, $UserDataDir) = generateUserCache();
if (!$UserCacheExists or !$UserDataDir) dieGracefully(10, 'Could not generate a user cache file!');
else if ($Verbose) logEntry('Created user cache.');

// / This code generates a user notifications file if none exists. Useful for initializing new users logging-in for the first time.
list ($NotificationsFileExists, $NotificationsFile) = generateNotificationsFile();
if (!$NotificationsFileExists or !$NotificationsFile) dieGracefully(11, 'Could not generate a user notifications file!');
else if ($Verbose) logEntry('Created user notifications.');
// / -----------------------------------------------------------------------------------
