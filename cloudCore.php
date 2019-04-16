<?php 
/* 
HonestRepair Diablo Engine  -  Cloud Core
https://www.HonestRepair.net
https://github.com/zelon88

Licensed Under GNU GPLv3
https://www.gnu.org/licenses/gpl-3.0.html

Author: Justin Grimes
Date: 4/15/2019
<3 Open-Source

The Cloud Core handles userland data-related operations like file creation, conversion, manipulation & removal.
*/

// / ----------------------------------------------------------------------------------
// / Make sure the core is loaded.
if (!isset($ConfigIsLoaded) or $ConfigIsLoaded !== TRUE) die('ERROR!!! cloudCore: The requested application is currently unavailable.'.PHP_EOL); 
// / ----------------------------------------------------------------------------------

// / ----------------------------------------------------------------------------------
// / The following code sets the functions for the session.
// / All functions accept arrays as inputs. 
// / If using arrays as be sure all your array indicies align properly.

// / A function for verifying that the desired operation is allowable in the current context of execution.
// / Also useful for debugging new functionality as a built-in integrity/type-checking test.
function verifyOperation($operation, $arguments) { 
  global $UserID, $UserIsAdmin;


return $OperationIsVerified;
}

// / A function for verifying the active folder and constructing writable paths for Cloud operations.
function defineFolder($Library, $UserDir) { 
  $DirPath = $FolderIsDefined = FALSE;
  $dirToCheck = $Library[3].DIRECTORY_SEPARATOR.$UserDir);

  logEntry('Initiating directory writer in library \''.$Library.'\'.');

}

// / A function for making simple files in the users Cloud.
function makeFile($library, $path, $fileType) { 
  // DON'T FORGET TO ENCODE/SANITIZE THE DATA

}

// / A function for making new folders in the users Cloud.
// / Accepts a string or array of strings.
// / Strings must be a subdirectory of the selected $Library directory set in config.php.
// / If an intermediate directory doesn't exist this will fail and result in an error. 
// / Library must be a valid $LibraryActive array element.
function makeFolders($Library, $Paths) { 
  $FoldersExist = $secCheck = FALSE;
  // / Will be tripped to FALSE by the end of the loop if any errors occured.
  $pathCheck = TRUE;
  if (!is_array($Paths)) $Paths = array($Paths);
  if (in_array($Library, $LibrariesActive) && !in_array($Library, $LibrariesInactive)) {
    if (is_dir($Library[3]) && is_writable($Library[3])) { 
      foreach ($Paths as $path) { 
        if (!is_dir($dirToMake = $Library[3].DIRECTORY_SEPARATOR.$path)) mkdir($dirToMake);
        if (!is_dir($dirToMake)) { 
      	  $pathCheck = FALSE;
      	  logEntry('Could not create folder \''.$path.'\' in library \''.$Library[0].'\'.'); } }
    if ($pathCheck && $secCheck) $FoldersExist = TRUE; } }
  $dirToMake = $path = $secCheck = $pathCheck = NULL;
  unset($dirToMake, $path, $secCheck, $pathCheck);
  return(array($Paths, $FoldersExist)); }

// / A function for uploading files to a users Cloud.
function uploadFiles($library, $path, $uploadData) { 
  global $AvailableCores, $LibrariesActive, $UserID, $LogFile, $UserDir;
  
}

// / A function for downloading files from a users Cloud.
function downloadFiles($library, $path) { 
  
}

// / A function for compressing a users files into a portable archive.
// / Also works on folders.
function compressFiles($library, $path, $newPath) { 
  
}

// / A function for decompressing a users files into a folder.
function decompressFiles($library, $path, $newPath) { 
  
}

// / A function for converting files between formats.
function convertFiles($library, $path, $newPath, $extension) { 
  
}

// / A function for encrypting files for the user.
// / Also works on folders.
function encryptFiles($library, $path, $newPath, $encryptionType, $key) { 

}

// / A function for decrypting files for the user.
// / Also works on folders.
function decryptFiles($library, $path, $newPath, $encryptionType, $key) { 

}

// / A function for copying files between Cloud locations.
// / Also works on folders.
function moveFiles($library, $path, $newPath) { 
  
} 

// / A function for renaming files in a users Cloud.
// / Also works on directories.
function renameFiles($library, $path, $newPath) { 
  
}

// / A function for cutting files with the users clipboard.
// / Also works on directories.
function clipboardCut($library, $path, $newPath) { 
  
}

// / A function for copying files to the users clipboard.
// / Also works on directories.
function clipboardCopy($library, $path, $newPath) { 
  
}

// / A function for pasting files from the users clipboard.
// / Also works on directories.
function clipboardPaste($library, $path, $newPath) { 
  
}

// / A function for deleting files and folders from the users Cloud.
// / The token must be a string identical to the client token.
function deleteFiles($library, $path, $confirmToken) { 

}
// / ----------------------------------------------------------------------------------