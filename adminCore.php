<?php
/* 
HonestRepair Diablo Engine  -  Admin Core
https://www.HonestRepair.net
https://github.com/zelon88

Licensed Under GNU GPLv3
https://www.gnu.org/licenses/gpl-3.0.html

Author: Justin Grimes
Date: 4/8/2022
<3 Open-Source

The Admin Core handles admin related functions like adding/removing users & changing global settings.
*/

// / ----------------------------------------------------------------------------------
// / Make sure the core is loaded.
if (!isset($ConfigIsLoaded) or $ConfigIsLoaded !== TRUE) die('ERROR!!! adminCore0: The requested application does not support out-of-context execution!'.PHP_EOL); 
// / ----------------------------------------------------------------------------------

// / ----------------------------------------------------------------------------------
// / The following code sets the functions for the session.d

// / A function to generate a missing Username Availability Cache file. 
// / Contains a timestamp, hashed client user agent, & client IP of each non-admin Username Availability request.
function generateUserAvailabilityCache($UsernameAvailabilityCacheFile) { 
  // / Set variables. 
  global $UACData, $RawTime;
  $uacLine = array($RawTime,'','');
  $UACData = array($uacLine);
  $UsernameAvailabilityCacheExists = $usernameAvailabilityCacheData = FALSE;
  // / Craft a proper PHP array so it can be written to the User Availability Cache file.
  $usernameAvailabilityCacheData = '<?php'.PHP_EOL.'$UACData = array(array(\''.implode('\',\'', $uacLine).'\'));'.PHP_EOL;
  // / Write default cache data. 
  $UsernameAvailabilityCacheCreated = file_put_contents($UsernameAvailabilityCacheFile, $usernameAvailabilityCacheData);
  // / Clean up unneeded memory.
  $uacLine = $usernameAvailabilityCacheData = NULL;
  unset($uacLine, $usernameAvailabilityCacheData); 
  return $UsernameAvailabilityCacheCreated; } 

// / A function to load the Username Availability Cache into memory.
function loadUserAvailabilityCache($UsernameAvailabilityCacheFile) { 
  // / Set variables.
  global $UACData;
  $UserAvailabilityCacheLoaded = FALSE;
  // / Load the cache file.
  $UserAvailabilityCacheLoaded = include($UsernameAvailabilityCacheFile);
  // / Detect and rewrite a successful return value from the include statement to something predictable.
  if ($UserAvailabilityCacheLoaded === 1) $UserAvailabilityCacheLoaded = TRUE;
  return array($UserAvailabilityCacheLoaded, $UACData); }

// / A function to check if the current should be permitted to perform Username Availability Requests.
// / Also removes entries from the cache that are older than 6,000 seconds.
function checkUserAvailabilityCache($UACData, $HashedUserAgent, $ClientIP) { 
  // / Set variables. 
  // / Note that $checkValid and $IntegrityCheck are initialized to FALSE and that $subCheck is initialized to TRUE.
  // / All checks are considered to have passed if $checkValid and $subCheck are TRUE at the end of all loops.
  global $RawTime, $UAHitThresholds, $UATimeThresholds;
  $hitCountOne = $hitCountTwo = $hitCountThree = 0;
  $checkValid = $startTime = $endTime = $timeDifference = $IntegrityCheck = FALSE; 
  $subCheck = $UsernameAvailabilityPermissionGranted = TRUE;
  // / Verify that $UACData is an array.
  if (is_array($UACData)) { 
    $checkValid = TRUE;
    // / Loop through each entry in the UACData array.
    foreach ($UACData as $uacKey => $uacLine) { 
      // / Verify that each element of the UACData array is also an array.
      if (!is_array($uacLine)) $subCheck = FALSE;
      else { 
        // / Determine if the current client is in the UACData entry (uacLine).
        if (in_array($HashedUserAgent, $uacLine) && in_array($ClientIP, $uacLine)) { 
          // / Gather the time of the currently selected request from the data entry.
          // / In seconds since Unix epoch.
          $startTime = $uacLine[0];
          // / Gather the current time.
          // / In seconds since Unix epoch.
          $endTime = $RawTime;
          // / Calculate the time difference between the current data entry and the current time.
          $timeDifference = $endTime - $startTime;
          // / Count the number of requests that have taken place within the past 60 seconds.
          if ($timeDifference <= $UATimeThresholds[0]) { 
            $hitCountOne++;
            // / If there have been more than 3 requests in 60 seconds, deny this request.
            if ($hitCountOne > $UAHitThresholds[0]) $UsernameAvailabilityPermissionGranted = FALSE; }
          // / Count the number of requests that have taken place within the past 600 seconds.
          if ($timeDifference > $UATimeThresholds[0] && $timeDifference <= $UATimeThresholds[1]) { 
            $hitCountTwo++;
            // / If there have been more than 10 requests in 600 seconds, deny this request.
            if ($hitCountTwo > $UAHitThresholds[1]) $UsernameAvailabilityPermissionGranted = FALSE; }
          // / Count the number of requests that have taken place within the past 3,600 seconds.
          if ($timeDifference > $UATimeThresholds[1] && $timeDifference <= $UATimeThresholds[2]) { 
            $hitCountThree++;
            // / If there have been more than 15 requests in 3,600 seconds, deny this request.
            if ($hitCountThree > $UAHitThresholds[2]) $UsernameAvailabilityPermissionGranted = FALSE; }
          // / Remove any entries that are older than 6,001 seconds.
          if ($timeDifference > $UATimeThresholds[2]) { 
            $uacLine[$uacKey] = NULL; 
            unset($uacLine[$uacKey]); } } } } } 
  // / Consolidate all sanity checks performed into one to ensure they all passed.
  // / If $checkValid is flipped to TRUE and $subCheck stays TRUE the entire time, the check is considered to be valid.
  if ($checkValid && $subCheck) $IntegrityCheck = TRUE;
  // / Clean up unneeded memory.
  $hitCountOne = $hitCountTwo = $hitCountThree = $checkValid = $startTime = $endTime = $timeDifference = $uacLine = $subCheck = $uacKey = NULL;
  unset($hitCountOne, $hitCountTwo, $hitCountThree, $checkValid, $startTime, $endTime, $timeDifference, $uacLine, $subCheck, $uacKey); 
  return array($IntegrityCheck, $UsernameAvailabilityPermissionGranted); }

// / A function to update the Username Availability Cache with information about the current request.
function updateUserAvailabilityCache($UsernameAvailabilityCacheFile, $UACData, $HashedUserAgent, $ClientIP) { 
  // / Set variables. 
  global $Verbose, $RawTime;
  $UsernameAvailabilityCacheUpdated = $usernameAvailabilityCacheData = FALSE;
  $uacEntry = array($RawTime, $HashedUserAgent, $ClientIP);
  // / If the Username Availability Cache file is older than 2 days, we delete and regenerate it.
  // / Note that because the existing $UACData is already in memory we will not lose entries that are under 6,000 seconds old.
  if ($RawTime - filemtime($UsernameAvailabilityCacheFile) >= 60 * 60 * 24 * 2) { 
    if ($Verbose) logEntry('Regenerating the username availability cache file.', FALSE); 
    unlink($UsernameAvailabilityCacheFile);
    $UsernameAvailabilityCacheCreated = generateUserAvailabilityCache($UsernameAvailabilityCacheFile);
    if (!$UsernameAvailabilityCacheCreated or !file_exists($UsernameAvailabilityCacheFile)) dieGracefully(26, 'Could not recreate a username availability cache file!', FALSE);
    else if ($Verbose) logEntry('Recreated a username availability cache file.', FALSE); }
  // / Craft a proper PHP array so it can be written to the User Availability Cache file.
  $usernameAvailabilityCacheData = 'array_push($UACData, array(\''.implode('\',\'', $uacEntry).'\'));'.PHP_EOL;
  // / Write default cache data. 
  $UsernameAvailabilityCacheUpdated = file_put_contents($UsernameAvailabilityCacheFile, $usernameAvailabilityCacheData, FILE_APPEND);
  // / Clean up unneeded memory.
  $uacEntry = $usernameAvailabilityCacheData = NULL;
  unset($uacEntry, $usernameAvailabilityCacheData); 
  return $UsernameAvailabilityCacheUpdated; }

// / A function to perform the actual check to see if the desired username is already in the user list.
function performUserAvailabilityCheck($desiredUsername) { 
  // / Set variables. 
  global $Users;
  $ArrayCheck = $userName = $user = FALSE;
  $UsernameIsAvailable = TRUE;
  // / Verify that all conditions for performing a Username Availability Check have been met.
  if (is_array($Users)) { 
    $ArrayCheck = TRUE;
    // / Iterate through each defined user.
    foreach ($Users as $user) { 
      $userName = $user[1];
      // / Check if the desired username matches the currently selected user.
      if ($desiredUsername === $userName) { 
        $UsernameIsAvailable = FALSE; 
        break; } } }
  // / Clean up unneeded memory.
  $userName = $user = $desiredUsername = NULL;
  unset($userName, $user, $desiredUsername); 
  return array($ArrayCheck, $UsernameIsAvailable); }

// / A function to output the results of a completed Username Availability Request to the user.
function respondUserAvailabilityRequest($desiredUsername, $UsernameAvailabilityPermissionGranted, $UsernameIsAvailable) { 
  // / Set variables.
  global $Verbose;
  // / The following code is performed when the username availability request was approved.
  if ($UsernameAvailabilityPermissionGranted) { 
    // / The following code is performed when the username is available.
    if ($UsernameIsAvailable) {
      if ($Verbose) logEntry('The desired username is AVAILABLE.', FALSE);  
      echo('AVAILABLE,'.$desiredUsername.PHP_EOL); }
    // / The following code is performed when the username is not available.
    if (!$UsernameIsAvailable) {
      if ($Verbose) logEntry('The desired username is NOT AVAILABLE.', FALSE); 
      echo('NOT AVAILABLE'.PHP_EOL); } } 
  // / The following code is performed when the username availability request was denied.
  else { 
    if ($Verbose) logEntry('The username availability request cannot be performed at this time.', FALSE);
    echo('DENIED'.PHP_EOL); } 
  // / Clean up unneeded memory.
  $desiredUsername = NULL;
  unset($desiredUsername); }

// / A function to check the availability of a username.
// / 5 attempts in 1 minute, or 10 attempts in 5 minutes, or 15 attempts in 1 hour.
// / One malicious automated client can burn 10 usernames in 2 minutes but then there is a 3 minute cooldown.
// / After that cooldown they get 5 more attempts and then a 57 minute cooldown.
// / These calculations assume that the bot tries userenames constantly with no break in between attempts.
// / A 10 bot botnet would be able to burn 100 usernames in the first 2 minutes, but the cooldown would also burn bots.
// / If we limit the attempts to aggressively we will prevent legitimate username creation attempts.
function checkUserAvailability($desiredUsername, $UsernameAvailabilityResponseNeeded) { 
  // / Set variables. 
  global $Verbose, $Salts, $RootPath, $UserIsAdmin, $AllowUserRegistration, $Users, $DataBackups, $BackupUsernameCheckCache, $CoresLoaded;
  $UACData = '';
  $UsernameIsAvailable = $UsernameAvailabilityCacheExists = $UsernameAvailabilityCacheFile = $UsernameAvailabilityCacheCreated = $UserAvailabilityCacheLoaded = $IntegrityCheck = $UsernameAvailabilityPermissionGranted = $UsernameAvailabilityCacheUpdated = $HashedUserAgent = $ClientIP = $BackupSuccess = $UsernameIsAvailable = FALSE;
  // / Check if the user is an administrator. 
  // / If the user is not an administrator, check to ensure the user is not using brute force to enumerate the user list.
  if (!$UserIsAdmin) { 
    // / Define the Username Availability Cache file.
    $UsernameAvailabilityCacheFile = $RootPath.'Cache'.DIRECTORY_SEPARATOR.'UsernameAvailabilityCache-'.hash('sha256',$Salts[3].'USERNAMEAVAILABILITYCACHE').'.php';
    // / Check that a user cache file exists. Create one if needed
    if (!file_exists($UsernameAvailabilityCacheFile)) { 
      $UsernameAvailabilityCacheCreated = generateUserAvailabilityCache($UsernameAvailabilityCacheFile);
      if (!$UsernameAvailabilityCacheCreated or !file_exists($UsernameAvailabilityCacheFile)) dieGracefully(21, 'Could not create a username availability cache file!', FALSE);
      else if ($Verbose) logEntry('Created a username availability cache file.', FALSE); }
    // / Load the User Availability Cache file contents into memory.
    list ($UserAvailabilityCacheLoaded, $UACData) = loadUserAvailabilityCache($UsernameAvailabilityCacheFile);
    if (!$UserAvailabilityCacheLoaded) dieGracefully(22, 'Could not load the username availability cache file!', FALSE);
    else if ($Verbose) logEntry('Loaded the username availability cache file.', FALSE); 
    // / Detect the hashed user agent and client IP.
    list ($HashedUserAgent, $ClientIP) = detectClientInfo();
    // / Determine if the username availability request should be allowed or denied based on user agent & IP.
    list($IntegrityCheck, $UsernameAvailabilityPermissionGranted) = checkUserAvailabilityCache($UACData, $HashedUserAgent, $ClientIP);
    if (!$IntegrityCheck) dieGracefully(23, 'Could not validate the username availability cache file!', FALSE);
    else if ($Verbose) logEntry('Validated the username availability cache file.', FALSE); 
    // / Log the results of the Username Availability Check.
    if ($UsernameAvailabilityPermissionGranted) if ($Verbose) logEntry('The username availability request has been APPROVED.', FALSE); 
    if (!$UsernameAvailabilityPermissionGranted) if ($Verbose) logEntry('The username availability request has been DENIED.', FALSE); 
    // / Check if backups are enabled by config.php & backup the User Availability Cache file if needed.
    if ($DataBackups && $BackupUsernameCheckCache) { 
      // / Load the data core to make backups possible.
      if (!in_array('DATA', $CoresLoaded)) list ($CoresLoaded, $CoreLoadedSuccessfully) = loadCores('DATA');
      if (!$CoreLoadedSuccessfully) dieGracefully(18, 'Could not load the data core file (dataCore.php)!', FALSE);
      else if ($Verbose) logEntry('Loaded the data core file.', FALSE);  
      // / Backup the Username Avaiability Cache file.
      $BackupSuccess = backupFile('CACHE', $UsernameAvailabilityCacheFile);
      if (!$BackupSuccess) dieGracefully(19, 'Could not backup the usename availability cache file!', FALSE);
      else if ($Verbose) logEntry('Backed up the username availability cache file.', FALSE); }
    // / Update the Username Availability Cache with information about the current request.
    $UsernameAvailabilityCacheUpdated = updateUserAvailabilityCache($UsernameAvailabilityCacheFile, $UACData, $HashedUserAgent, $ClientIP);
    if (!$UsernameAvailabilityCacheUpdated) dieGracefully(24, 'Could not update the username availability cache file!', FALSE);
    else if ($Verbose) logEntry('Updated the username availability cache file.', FALSE); }
  // / Perform the Username Availability Check only if permission has been granted to do so or the user is an administrator.
  if ($UsernameAvailabilityPermissionGranted or $UserIsAdmin) { 
  	$UsernameAvailabilityPermissionGranted = TRUE;
    // / Perform the Username Availability Check.
  	list ($ArrayCheck, $UsernameIsAvailable) = performUserAvailabilityCheck($desiredUsername);
    if (!$ArrayCheck) dieGracefully(25, 'Could not perform the username availability check!', FALSE);
    else if ($Verbose) logEntry('Performed the username availability check.', FALSE); }
  // / Output the results of the Username Availability Request to the user.
  if ($UsernameAvailabilityResponseNeeded) respondUserAvailabilityRequest($desiredUsername, $UsernameAvailabilityPermissionGranted, $UsernameIsAvailable);
  // / Clean up unneeded memory.
  $desiredUsername = $UACData = $UsernameAvailabilityCacheExists = $UsernameAvailabilityCacheFile = $UsernameAvailabilityCacheCreated = $UserAvailabilityCacheLoaded = $IntegrityCheck = $UsernameAvailabilityCacheUpdated = $HashedUserAgent = $ClientIP = $BackupSuccess = NULL;
  unset($desiredUsername, $UACData, $UsernameAvailabilityCacheExists, $UsernameAvailabilityCacheFile, $UsernameAvailabilityCacheCreated, $UserAvailabilityCacheLoaded, $IntegrityCheck, $UsernameAvailabilityCacheUpdated, $HashedUserAgent, $ClientIP, $BackupSuccess); 
  return array($UsernameAvailabilityPermissionGranted, $UsernameIsAvailable); } 

// / A function to add a user.
// / Accepts an array as input. 
function addUser($DesiredUsername, $NewUserEmail, $NewUserPassword, $NewUserPasswordConfirm) { 
  // / Set variables.
  global $Users, $CacheFile;
  $UserCreated = $PasswordsMatch = $UserID = $newCacheLine = $cacheCheck = FALSE;
  $userNum = 1000;
  if ($NewUserPassword === $NewUserPasswordConfirm) if (is_string($NewUserPassword)) if (strlen($NewUserPassword) === 64) $PasswordsMatch = TRUE;
  if ($PasswordsMatch) { 
    logEntry('Password validation complete.', FALSE);
    while (isset($Users[$userNum])) $userNum++; 
    $UserID = $userNum;
    $newCacheLine = '$PostConfigUsers['.$UserID.'] = array(\''.$UserID.'\',\''.$DesiredUsername.'\',\''.$NewUserEmail.'\',\''.$NewUserPassword.'\',FALSE,TRUE);'; 
    $cacheCheck = file_put_contents($CacheFile, $newCacheLine.PHP_EOL, FILE_APPEND);
    if ($cacheCheck) { 
      logEntry('Inserted user data into existing cache file.', FALSE);
      $UserCreated = TRUE;
      $Users[$UserID] = array($UserID, $DesiredUsername, $NewUserEmail, $NewUserPassword, FALSE); } 
    else dieGracefully(30, 'Could not update the cache file!', FALSE); }
  else dieGracefully(31, 'Could not validate supplied passwords!', FALSE);
  // / The following code verifies that required user directories & files are present & creates them if they are missing.
  list ($UserLogsExists, $UserLogDir, $UserLogFile, $UserDataDir, $UserCacheExists, $UserCache, $UserCacheDir, $NotificationsFileExists, $NotificationsFile) = verifyUserEnvironment($UserID);
  if (!$UserLogsExists or !$UserCacheExists or !$NotificationsFileExists) dieGracefully(36, 'Could not verify the user environment!', FALSE); 
  else if ($Verbose) logEntry('Verified user environment.', FALSE); 
  // / Output the results of the Create Account process.
  if ($UserCreated) echo('APPROVED'.PHP_EOL);
  else echo('NOT APPROVED'.PHP_EOL);
  $PasswordsMatch = $userNum = $newCacheLine = $cacheCheck = $userLogsExists = $userLogDir = $userLogFile = $userDataDir = $userCacheExist = $notificationsFileExists = $notificationsFile = NULL;
  unset($PasswordsMatch, $userNum, $newCacheLine, $cacheCheck, $userLogsExists, $userLogDir, $userLogFile, $userDataDir, $userCacheExist, $notificationsFileExists, $notificationsFile);
  return array($UserCreated, $UserID, $Users); }

// / A function to gather all accounts and statuses owned by a supplied email address.
// / Returns an array where $key is the name of the account and value is the status of the account.
// / Returns FALSE when no accounts were found for the supplied email address.
function gatherAccounts($ForgotUserEmailAddress) { 
  // / Set variables.
  global $Users;
  $ReturnArray = FALSE;
  // / If the currently selected user account has the same email address as the one supplied add it to the ReturnArray().
  foreach ($Users as $user) if ($user[2] === $ForgotUserEmailAddress) $ReturnArray[$user[1]] = $user[5]; 
  // / Clean up unneeded memory.
  $user = NULL;
  unset($user);
  return $ReturnArray; }

// / A function to create the email that will be sent to the user containing a list of owned accounts and statuses.
function craftForgotUserEmail($userData, $ForgotUserEmailAddress) { 
  // / Set variables.
  global $Time, $ApplicationName, $ApplicationURL;
  $EmailCrafted = $EmailData = FALSE;
  $accountList = '';
  $dataEcho = 'DISABLED';
  // / Craft the beginning of the email.
  $emailHead = 'Hello '.$ForgotUserEmailAddress.'! <br /><br />On '.$Time.' you requested assistance recovering your '.$ApplicationName.' account at <a href=\''.$ApplicationURL.'\'>'.$ApplicationURL.'</a>. <br /><br />The following is a list of '.$ApplicationName.' accounts associated with this email address: <br /><br /><ul>';
  // / Craft the middle of the email with a bulleted (unordered) list of all accounts and statuses.
  if (is_array($userData)) { 
    $EmailCrafted = TRUE;
    // / Iterate through the userlist and gather all accounts & statuses for any account that matches the email address specified.
    foreach ($userData as $key => $data) { 
      if ($data === TRUE) $dataEcho = 'ENABLED';
      else $dataEcho = 'DISABLED'; 
      $accountList = $accountList.'<li>Username: <b>'.$key.'</b> | Status: <b>'.$dataEcho.'</b></li>'; } }
  // / Make sure the $accountList came out as expected, and replace it with a placeholder if it is still blank.
  if ($accountList == '') $accountList = '<li><b>No Accounts Found!</b></li>'; 
  // / Craft the end of the email.
  $emailFoot = '</ul> <br /><br />Please visit <a href=\''.$ApplicationURL.'\'>'.$ApplicationName.'</a> to login or create a new account.';
  // / Craft the entire email message from the components assembled above.
  $EmailData = $emailHead.$accountList.$emailFoot;
  // / Clean up unneeded memory.
  $accountList = $emailHead = $emailFoot = $key = $data = $dataEcho = $userData = NULL;
  unset($accountList, $emailHead, $emailFoot, $key, $data, $dataEcho, $userData);
  return array($EmailCrafted, $EmailData); }

// / A function to send an email containing usernames owned by a supplied email address.
function recoverUsername($ForgotUserEmailAddress) { 
  // / Set variables.
  global $EmailFromName, $Verbose;
  $ownedAccounts = $emailCrafted = $EmailSent = FALSE;
  // / Gather account information.
  $ownedAccounts = gatherAccounts($ForgotUserEmailAddress);
  if ($Verbose) logEntry('Gathered a list of accounts for the supplied email address.', FALSE); 
  // / Craft a username recovery email message.
  list ($emailCrafted, $emailData) = craftForgotUserEmail($ownedAccounts, $ForgotUserEmailAddress);
  if ($Verbose) logEntry('Crafted a recovery email.', FALSE); 
  // / Send the recovery email.
  if ($emailCrafted) $EmailSent = sendEmail($ForgotUserEmailAddress, $EmailFromName, $emailData);
  // / Clean up unneeded memory.
  $ownedAccounts = $emailCrafted = $emailData = NULL;
  unset($ownedAccounts, $emailCrafted, $emailData);
  return $EmailSent; }

// / A function to generate a cryptographically secure 8 digit recovery code.
function generatePasswordRecoveryCode() { 
  // / Set variables.
  $RecoveryCode = random_int(10000000, 99999999);
  return $RecoveryCode; } 

// / A function to generate a password recovery cache file to a user's cache directory.
function generatePasswordRecoveryCache($UserInput) { 
  // / Set variables.
  global $Users, $RawTime;
  $UserFound = $RecoveyCacheCreated = $UserEmail = FALSE;
  // / Iterate through the userlist 
  foreach ($Users as $user) if ($UserInput === $user[1]) { 
    $UserFound = TRUE;
    $UserID = $user[0]; 
    $UserEmail = $user[2];
    break; }
  // / Only create a recovery cache file if the specified user exists.
  if ($UserFound) { 
    // / Verify that the user environment exists so that we don't run into errors during creation of the recovery cache file.
    list ($UserLogsExists, $UserLogDir, $UserLogFile, $UserDataDir, $UserCacheExists, $UserCache, $UserCacheDir, $NotificationsFileExists, $NotificationsFile) = verifyUserEnvironment($UserID);
    // / Detect some identifying information about the client making the request.
    list ($hashedUserAgent, $clientIP) = detectClientInfo();
    // / Generate a cryptographically secure recovery code.
    $RecoveryCode = generatePasswordRecoveryCode();
    // / Create a recovery cache file in the users cache directory.
    $RecoveryCacheFile = $UserCahceDir.'RecoveryCache-'.hash('sha256',$Salts[0].'CACHE'.$UserID).'.php';
    // / Craft the contents of the recovery cache file in PHP syntax.
    $RecoveryCacheData = '<?php $recCode = '.$RecoveryCode.'; $recTime = '.$RawTime.'; $recUA = '.$hashedUserAgent.'; $recIP = '.$clientIP.'; $recFailedAttempts = 0; $recSuccessAttempts = 0;'.PHP_EOL;
    // / Check if a recovery cache file already exists & delete it.
    if (file_exists($RecoveryCacheFile)) unlink($RecoveryCacheFile);
    // / Make sure that any existing cache file was removed & create a new one.
    if (!file_exists($RecoveryCacheFile)) $RecoveryCacheCreated = file_put_contents($RecoveryCacheFile, $RecoveryCacheData, FILE_APPEND); }
  // / Clean up unneeded memory.
  $user = $hashedUserAgent = $clientIP = NULL;
  unset($user, $HashedUserAgent, $ClientIP);
  return array($UserFound, $UserEmail, $RecoveyCacheCreated, $RecoveryCacheFile); }

// / A function to create the email that will be sent to the user containing a recovery code for resetting their password.
function craftForgotPasswordEmail($RecoveryCode, $UserInput) { 
  // / Set variables.
  global $Time, $ApplicationName, $ApplicationURL;
  $EmailCrafted = $EmailData = FALSE;
  // / Craft the beginning of the email.
  $emailHead = 'Hello '.$ForgotUserEmailAddress.'! <br /><br />On '.$Time.' you requested assistance recovering your '.$ApplicationName.' account at <a href=\''.$ApplicationURL.'\'>'.$ApplicationURL.'</a>. <br /><br />Please enter the recovery code below when prompted to continue with the account recovery process. <br /><br /><ul>';
  // / Craft the middle of the email with a bulleted (unordered) list containing the recovery code.
  $emailMiddle = '<li>Recovery Code: <b>'.$RecoveryCode.'</b>'; 
  // / Craft the end of the email.
  $emailFoot = '</ul> <br /><br />Please visit <a href=\''.$ApplicationURL.'\'>'.$ApplicationName.'</a> to login or create a new account.';
  // / Craft the entire email message from the components assembled above.
  $EmailData = $emailHead.$emailMiddle.$emailFoot;
  $EmailCrafted = TRUE;
  // / Clean up unneeded memory.
  $emailHead = $emailMiddle = $emailFoot = NULL;
  unset($emailHead, $emailMiddle, $emailFoot);
  return array($EmailCrafted, $EmailData); }

// / A function to send an email containing a password recovery code to a username on file.
function sendPasswordRecoveryCode($UserInput) { 
  // / Set variables.
  global $EmailFromName, $Verbose;
  // / Generate a cryptographically secure recovery code.
  $RecoveryCode = generatePasswordRecoveryCode();
  // / Attempt to generate a password recovery cache file in the users cache directory.
  list ($UserFound, $UserEmail, $RecoveyCacheCreated, $RecoveryCacheFile, $RecoveryCode) = generatePasswordRecoveryCache($UserInput);
  // / If the user was found & the recovery cache was created then craft an recovery email and send it to the email address on file for the user. 
  if ($UserFound && $RecoveryCacheCreated) { 
    // / Craft a password recovery email message.
    list ($emailCrafted, $emailData) = craftForgotPasswordEmail($RecoveryCode, $UserInput);
    if ($Verbose) logEntry('Crafted a password recovery email.', FALSE); 
    // / Send the recovery email.
    $EmailSent = sendEmail($UserEmail, $EmailFromName, $emailData); }
  // / If the user was not found we do not create a cache file but we also do not throw any errors that would give away information which usernames are taken.
  else if ($Verbose) logEntry('Could not craft a password recovery email.', FALSE); 
  // / Clean up unneeded memory.
  $emailCrafted = $emailData = NULL;
  unset($emailCrafted, $emailData);
  return $EmailSent; } 

// / A function to validate a password recovery cache against supplied credentials.
function validatePasswordRecoveryCache($UserInput, $RecoveryCodeInput) { 

return array($RecoveryCacheIsValid, $RecoveryCodeIsValid);
}

// / A function to reset a password using a recovery code.
function recoverPassword($UserInput, $RecoveryCodeInput) { 
  list($RecoveryCacheIsValid, $RecoveryCodeIsValid) = validatePasswordRecoveryCache();

}

// / A function to delete a user.
// / Accepts an array as input. 
function deleteUser($userToDelete) { 

}

// / A function for updating a global setting.
// / Accepts an array as input. 
function updateGlobalSetting($setting, $value) {

}

// / A function for updating the primary source for engine updates.
function updateMainSource($source) {

}
// / ----------------------------------------------------------------------------------

