<?php
/* 
HonestRepair Diablo Engine  -  Admin Core
https://www.HonestRepair.net
https://github.com/zelon88

Licensed Under GNU GPLv3
https://www.gnu.org/licenses/gpl-3.0.html

Author: Justin Grimes
Date: 3/14/2022
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
function detectClientinfo() { 
  $HashedUserAgent = hash('sha256', $_SERVER['HTTP_USER_AGENT']);
  $ClientIP = $_SERVER['REMOTE_ADDR'];
 return(array($HashedUserAgent, $ClientIP)); }

// / A function to generate a missing Username Availability Cache file. 
// / Contains a timestamp, hashed client user agent, & client IP of each non-admin username availability request.
function generateUserAvailabilityCache($UsernameAvailabilityCacheFile) { 
  // / Set variables. 
  global $UACData, $RawTime;
  $UACData = array(array($RawTime,'',''));
  $UsernameAvailabilityCacheExists = $usernameAvailabilityCacheData = FALSE;
  // / Craft a proper PHP array so it can be written to the User Availability Cache file.
  $usernameAvailabilityCacheData = '<?php'.PHP_EOL.'$UACData = array(\''.explode('\',\'', $UACData).'\');'.PHP_EOL;
  // / Write default cache data. 
  $UsernameAvailabilityCacheCreated = file_put_contents($UsernameAvailabilityCacheFile, $usernameAvailabilityCacheData);
  // / Clean up unneeded memory.
  $usernameAvailabilityCacheData = NULL;
  unset($usernameAvailabilityCacheData); 
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

function checkUserAvailabilityCache($UACData) { 


}

// / A function to check the availability of a username.
function checkUserAvailability($desiredUsername) { 
  // / Set variables. 
  global $Salts, $RootPath, $UserIsAdmin, $AllowUserRegistration, $Users;
  $UsernameIsAvailable = $UsernameAvailabilityCacheExists = $UsernameAvailabilityCacheFile = $UsernameAvailabilityCacheCreated = $UserAvailabilityCacheLoaded = FALSE;

  // / Define the Username Availability Cache file.
  $UsernameAvailabilityCacheFile = $RootPath.'Cache'.DIRECTORY_SEPARATOR.'UsernameAvailabilityCache-'.hash('sha256',$Salts[4].'USERNAMEAVAILABILITYCACHE').'.php';
  // / Check that a user cache file exists. Create one if needed
  if (!file_exists($UsernameAvailabilityCacheFile)) $UsernameAvailabilityCacheCreated = generateUserAvailabilityCache($UsernameAvailabilityCacheFile);
  if (file_exists($UsernameAvailabilityCacheFile)) list ($UserAvailabilityCacheLoaded, $UACData) = loadUserAvailabilityCache($UsernameAvailabilityCacheFile);

    // / Verify that all conditions for performing a Username Availability Check have been met.
    if ($checkIsAllowed) { 
      // / Iterate through each defined user.
      foreach ($Users as $user) { 
        $userName = $user[1];
        // / Check if the desired username matches the currently selected user.
        if ($desiredUsername === $UserInput) { 
          $UsernameIsAvailable = FALSE; 
          break; } } }


// / Check if backups are enabled by config.php & backup the User Availability Cache file if needed.
if ($DataBackups && $BackupUsernameCheckCache) { 
  // / Load the data core to make backups possible.
  if (!in_array('DATA', $CoresLoaded) list ($CoresLoaded, $CoreLoadedSuccessfully) = loadCores('DATA');
  if (!$CoreLoadedSuccessfully) dieGracefully(18, 'Could not load the data core file (dataCore.php)!', FALSE);
  else if ($Verbose) logEntry('Loaded the data core file.', FALSE); } 
  // / Backup the Username Avaiability Cache file.
  $BackupSuccess = backupFile('CACHE', $BackupUsernameCheckCache);
  if (!$BackupSuccess) dieGracefully(19, 'Could not backup the usename availability cache file!', FALSE);
  else if ($Verbose) logEntry('Backed up the username availability cache file.', FALSE); } 


          unlink($UsernameAvailabilityCacheFile); 
          $UsernameAvailabilityCacheExists = generateUserAvailabilityCache($UsernameAvailabilityCacheFile);

  

  	

  	// / If the user is an administrator we can approve the request without consulting the Username Availibility Cache.
    if ($UserIsAdmin) $CheckIsAllowed = TRUE; 
    // / If the user is not an administrator we must consult the Username Availability Cache before approving the request.
    else { 
      // / Check if a cache file exists
      if (file_exists($UsernameAvailabilityCacheFile) $UsernameAvailabilityCacheExists = TRUE;
      else $UsernameAvailabilityCacheExists = generateUserAvailabilityCache($UsernameAvailabilityCacheFile);
      if ($UsernameAvailabilityCacheExists) { 


      }
      // / -Check if cache exists, create one.
      // / -Load the cache file.
      // /   -Check if the current user is in the cache data.
      // /   -Update cache data as needed.
      // / --Backup cache file.
      // / --Delete cache file.
      // / -Regenerate the cache.



 } 


      // / 5 attempts in 1 minute, or 10 attempts in 5 minutes, or 15 attempts in 1 hour.
        // / One malicious automated client can burn 10 usernames in 2 minutes but then there is a 3 minute cooldown.
        // / After that cooldown they get 5 more attempts and then a 57 minute cooldown.
        // / These calculations assume that the bot tries userenames constantly with no break in between attempts.
        // / A 10 bot botnet would be able to burn 100 usernames in the first 2 minutes, but the cooldown would also burn bots.
        // / If we limit the attempts to aggressively we will prevent legitimate username creation attempts.


    
  
}

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

