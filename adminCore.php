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
if (!isset($ConfigIsLoaded) or $ConfigIsLoaded !== TRUE) die('ERROR!!! adminCore: The requested application is currently unavailable.'.PHP_EOL); 
// / ----------------------------------------------------------------------------------

// / ----------------------------------------------------------------------------------
// / The following code sets the functions for the session.

// / A function to generate a missing Username Availability Cache file. 
// / Contains the client IP, client user agent, and timestamp of each non-admin username availability request.
function generateUserAvailabilityCache($UsernameAvailabilityCacheFile) { 
  // / Set variables. Note the $UsernameAvailabilityCacheExists is assumed to be false unless it is changed to TRUE.
  // / If $UserAvailabilityCacheExists returns FALSE, the calling code should assume this function failed.
  $UsernameAvailabilityCacheExists = $usernameAvailabilityCacheData = FALSE;
  // / Craft a proper PHP array so it can be written to the User Availability Cache file.
  $usernameAvailabilityCacheData = '<?php'.PHP_EOL.'$usernameAvailabilityCacheData = array();'.PHP_EOL;
  // / Write default cache data. 
  $UsernameAvailabilityCacheExists = file_put_contents($UsernameAvailabilityCacheFile, $usernameAvailabilityCacheData);
  // / Clean up unneeded memory.
  $usernameAvailabilityCacheData = NULL;
  unset($usernameAvailabilityCacheData); 
  return($UsernameAvailabilityCacheExists); } 

// / A function to check the availability of a username.
function checkAvailability($desiredUsername) { 
  // / Set variables. 
  global $Salts, $RootPath, $UserIsAdmin, $AllowUserRegistration, $Users;
  $UsernameIsAvailable = TRUE;
  $CheckIsAllowed = $UsernameAvailabilityCacheExists = $UsernameAvailabilityCacheFile = FALSE;
  // / Define the $usernameAvailabilityCacheFile.
  $UsernameAvailabilityCacheFile = $RootPath.'Cache'.DIRECTORY_SEPARATOR.'UsernameAvailabilityCache-'.hash('sha256',$Salts[4].'USERNAMEAVAILABILITYCACHE').'.php';
  // / Only allow the function to continue if the user is an administrator or user registration is enabled in config.php.
  if ($UserIsAdmin or $AllowUserRegistration) { 
  	

  	// / If the user is an administrator we can approve the request without consulting the Username Availibility Cache.
    if ($UserIsAdmin) $CheckIsAllowed = TRUE; 
    // / If the user is not an administrator we must consult the Username Availability Cache before approving the request.
    else { 
      // / Check if a cache file exists
      if (file_exists($UsernameAvailabilityCacheFile) $UsernameAvailabilityCacheExists = TRUE;
      else $UsernameAvailabilityCacheExists = generateUserAvailabilityCache($UsernameAvailabilityCacheFile);
      if ($UsernameAvailabilityCacheExists) { 
      	// / Check if the Username Availability Cache File is more than 24 hours old.
        if (time()-filemtime($UsernameAvailabilityCacheFile) > 24 * 3600) { 
          // / Check if backups are enabled by config.php.
          if ($DataBackups && $BackupUsernameCheckCache) { 
          	// / Check if the 'BACKUPS' library is active.
          	list ($LibraryExists, $LibraryIsActive, $LibraryIsInactive, $LibraryIsCustom, $LibraryIsDefault) = checkLibraryStatus('BACKUPS');
          	if ($LibraryExists && $LibraryIsActive) backup('CACHE', $UsernameAvailabilityCacheFile)
            

        }
          unlink($UsernameAvailabilityCacheFile); 
          $UsernameAvailabilityCacheExists = generateUserAvailabilityCache($UsernameAvailabilityCacheFile);
      }
    	// / Create a cache file.
    	  // / Backup Cache File.
      // / Load the cache file.
      // / Purge old cache data.
    	// / Check if backups are enabled.
      // / Check if the current user is in the cache data.
      // / Update cache data as needed.
    	// / Rewrite the data only if changes were made
      // / Check the date of the cache file.
    	// / Check if backups are enabled.
    	  // / Backup cache.
    	// / Delete cache. 


 } 
    // / Verify that all conditions for performing a Username Availability Check have been met.
    if ($CheckIsAllowed) { 
      // / Iterate through each defined user.
      foreach ($Users as $user) { 
        $userName = $user[1];
        // / Check if the desired username matches the currently selected user.
        if ($desiredUsername === $UserInput) { 
          $UsernameIsAvailable = FALSE; 
          break; } }

      // / 5 attempts in 1 minute, or 10 attempts in 5 minutes, or 15 attempts in 1 hour.
        // / One malicious automated client can burn 10 usernames in 2 minutes but then there is a 3 minute cooldown.
        // / After that cooldown they get 5 more attempts and then a 57 minute cooldown.
        // / These calculations assume that the bot tries userenames constantly with no break in between attempts.
        // / A 10 bot botnet would be able to burn 100 usernames in the first 2 minutes, but the cooldown would also burn bots.
        // / If we limit the attempts to aggressively we will prevent legitimate username creation attempts.


    }
  }
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