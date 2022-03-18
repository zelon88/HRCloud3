<?php
/* 
HonestRepair Diablo Engine  -  Admin Core
https://www.HonestRepair.net
https://github.com/zelon88

Licensed Under GNU GPLv3
https://www.gnu.org/licenses/gpl-3.0.html

Author: Justin Grimes
Date: 3/18/2022
<3 Open-Source

The Admin Core handles admin related functions like adding/removing users & changing global settings.
*/

// / ----------------------------------------------------------------------------------
// / Make sure the core is loaded.
if (!isset($ConfigIsLoaded) or $ConfigIsLoaded !== TRUE) die('ERROR!!! adminCore0: The requested application does not support out-of-context execution!'.PHP_EOL); 
// / ----------------------------------------------------------------------------------

// / ----------------------------------------------------------------------------------
// / The following code sets the functions for the session.

// / A function to detect information helpful for identifying a client.
function detectClientInfo() { 
  $HashedUserAgent = hash('sha256', $_SERVER['HTTP_USER_AGENT']);
  $ClientIP = $_SERVER['REMOTE_ADDR'];
 return(array($HashedUserAgent, $ClientIP)); }

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
  return($UsernameAvailabilityCacheCreated); } 

// / A function to load the Username Availability Cache into memory.
function loadUserAvailabilityCache($UsernameAvailabilityCacheFile) { 
  // / Set variables.
  global $UACData;
  $UserAvailabilityCacheLoaded = FALSE;
  // / Load the cache file.
  $UserAvailabilityCacheLoaded = include($UsernameAvailabilityCacheFile);
  // / Detect and rewrite a successful return value from the include statement to something predictable.
  if ($UserAvailabilityCacheLoaded === 1) $UserAvailabilityCacheLoaded = TRUE;
  return(array($UserAvailabilityCacheLoaded, $UACData)); }

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
  return(array($IntegrityCheck, $UsernameAvailabilityPermissionGranted)); }

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
  return($UsernameAvailabilityCacheUpdated); }

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
  return(array($ArrayCheck, $UsernameIsAvailable)); }

// / A function to check the availability of a username.
// / 5 attempts in 1 minute, or 10 attempts in 5 minutes, or 15 attempts in 1 hour.
// / One malicious automated client can burn 10 usernames in 2 minutes but then there is a 3 minute cooldown.
// / After that cooldown they get 5 more attempts and then a 57 minute cooldown.
// / These calculations assume that the bot tries userenames constantly with no break in between attempts.
// / A 10 bot botnet would be able to burn 100 usernames in the first 2 minutes, but the cooldown would also burn bots.
// / If we limit the attempts to aggressively we will prevent legitimate username creation attempts.
function checkUserAvailability($desiredUsername) { 
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

  // / Clean up unneeded memory.
  $desiredUsername = $UACData = $UsernameAvailabilityCacheExists = $UsernameAvailabilityCacheFile = $UsernameAvailabilityCacheCreated = $UserAvailabilityCacheLoaded = $IntegrityCheck = $UsernameAvailabilityCacheUpdated = $HashedUserAgent = $ClientIP = $BackupSuccess = NULL;
  unset($desiredUsername, $UACData, $UsernameAvailabilityCacheExists, $UsernameAvailabilityCacheFile, $UsernameAvailabilityCacheCreated, $UserAvailabilityCacheLoaded, $IntegrityCheck, $UsernameAvailabilityCacheUpdated, $HashedUserAgent, $ClientIP, $BackupSuccess); 
  return(array($UsernameAvailabilityPermissionGranted, $UsernameIsAvailable)); } 

// / A function to add a user.
// / Accepts an array as input. 
function addUser($userToAdd) { 

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

