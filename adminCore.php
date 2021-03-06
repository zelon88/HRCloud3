<?php
/* 
HonestRepair Diablo Engine  -  Admin Core
https://www.HonestRepair.net
https://github.com/zelon88

Licensed Under GNU GPLv3
https://www.gnu.org/licenses/gpl-3.0.html

Author: Justin Grimes
Date: 3/29/2019
<3 Open-Source

The Admin Core handles admin related functions like adding/removing users & changing global settings.
*/

// / ----------------------------------------------------------------------------------
// / Make sure the core is loaded.
if (!isset($ConfigIsLoaded) or $ConfigIsLoaded !== TRUE) die('ERROR!!! adminCore: The requested application is currently unavailable.'.PHP_EOL); 
// / ----------------------------------------------------------------------------------

// / ----------------------------------------------------------------------------------
// / The following code sets the functions for the session.

// / A function to add a user.
// / AcceptS arrays as input. 
function addUser($userToAdd) { 

}

// / A function to delete a user.
// / AcceptS arrays as input. 
function deleteUser($userToDelete) { 

}

// / A function for updating a global setting.
// / AcceptS arrays as input. 
function updateGlobalSetting($setting, $value) {

}

// / A function for updating the primary source for engine updates.
function updateMainSource($source) {

}
// / ----------------------------------------------------------------------------------