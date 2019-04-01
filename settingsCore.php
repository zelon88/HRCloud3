<?php
/* 
HonestRepair Diablo Engine  -  Settings Core
https://www.HonestRepair.net
https://github.com/zelon88

Licensed Under GNU GPLv3
https://www.gnu.org/licenses/gpl-3.0.html

Author: Justin Grimes
Date: 3/29/2019
<3 Open-Source

The Settings Core allows users to change their preferences like color scheme & password resets.
*/

// / ----------------------------------------------------------------------------------
// / Make sure the core is loaded.
if (!isset($ConfigIsLoaded) or $ConfigIsLoaded !== TRUE) die('ERROR!!! settingsCore: The requested application is currently unavailable.'.PHP_EOL); 
// / ----------------------------------------------------------------------------------

// / ----------------------------------------------------------------------------------
// / The following code sets the functions for the session.
// / All functions accept arrays as inputs. 

// / A function to reset the users password.
function resetPassword($originalPassword, $newPassword) { 

}

// / A function for updating a user setting.
// / Accepts arrays as inputs. Be careful to match corresponding indecies.
function updateUserSetting($setting, $value) {

}

// / A function to return a users settings to default values.
// / Accepts arrays as inputs. Be careful to match corresponding indecies.
function defaultUserSetting($setting, $value) { 
  
}

// / A function to return a users settings to default values.
function generateClient($os, $architecture, $homepage) { 
  
}

// / A function to return a users personal data upon request.
function generateUserData($format) { 
  
}

// / A function to return a users personal data upon request.
function clearUserCache($format) { 
  
}
// / ----------------------------------------------------------------------------------