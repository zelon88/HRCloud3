<?php 
/* 
HonestRepair Diablo Engine  -  Cloud Core
https://www.HonestRepair.net
https://github.com/zelon88

Licensed Under GNU GPLv3
https://www.gnu.org/licenses/gpl-3.0.html

Author: Justin Grimes
Date: 4/17/2019
<3 Open-Source

The Cloud Core handles userland data-related operations like file creation, conversion, manipulation & removal.
*/

// / ----------------------------------------------------------------------------------
// / Make sure the core is loaded.
if (!isset($ConfigIsLoaded) or $ConfigIsLoaded !== TRUE) die('ERROR!!! cloudCore: The requested application is currently unavailable.'.PHP_EOL); 
// / ----------------------------------------------------------------------------------

// / ----------------------------------------------------------------------------------
// / The following code sets the functions for the session. 

// / A function for verifying that the desired operation is allowable in the current context of execution.
// / Also useful for debugging new functionality as a built-in integrity/type-checking test.
function verifyOperation($operation, $arguments) { 
  global $UserID, $UserIsAdmin;


return $OperationIsVerified;
}

// / A function for detecting the active library and ensuring that it is active.
function defineLibrary($LibrarySelected) { 
  global $LibrariesActive, $LibrariesInactive;
  $LibraryIsDefined = FALSE;
  if (in_array($LibrarySelected, $LibrariesActive) && !in_array($LibrarySelected, $LibrariesInactive)) { 
    if (is_dir($LibrariesActive[$LibrarySelected][3]) && is_writable($LibrariesActive[$LibrarySelected][3])) $LibraryIsDefined = TRUE; } 
  return(array($LibrarySelected, $LibraryIsDefined)); }

// / A function for verifying the active folder and constructing writable paths for Cloud operations.
function defineFolder($Library, $UserDir) { 
  global $UserID;
  $UserDirPath = $FolderIsDefined = FALSE;
  $dirToCheck = $Library[3].DIRECTORY_SEPARATOR.$UserID.DIRECTORY_SEPARATOR.$UserDir);
  if (is_dir($dirToCheck)) { 
    $FolderIsDefined = TRUE;
    $UserDirPath = $dirToCheck; } 
  $dirToCheck = NULL;
  unset($dirToCheck);
  return(array($UserDir, $UserDirPath, $FolderIsDefined)); }

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
        if (!is_dir($dirToMake)) $pathCheck = FALSE; }
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

// / ----------------------------------------------------------------------------------
// / The following code specifies the logic flow for the session.

// / Sanitize LibrarySelected POST input.
list ($LibrarySelected, $VariableIsSanitized) = sanitize($_POST['LibrarySelected'], TRUE); 
if (!$VariableIsSanitized) dieGracefully(100, 'Could not sanitize library input!');
else if ($Verbose) logEntry('Sanitized library input.');

// / Sanitize UserDir POST input.
list ($UserDir, $VariableIsSanitized) = sanitize($_POST['UserDir'], TRUE); 
if (!$VariableIsSanitized) dieGracefully(101, 'Could not sanitize selected directory input!');
else if ($Verbose) logEntry('Sanitized selected directory input.')

// / Define the selected library to use for the session.
list ($LibrarySelected, $LibraryIsDefined) = defineLibrary($LibrarySelected);
if (!$LibraryIsDefined) dieGracefully(102, 'Could not define library!');
else if ($Verbose) logEntry('Defined library.');

// / Define the selected folder to use for the session.
list ($UserDir, $UserDirPath, $UserDirIsDefined) = defineFolder($LibrarySelected, $UserDir);
if (!$FolderIsDefined) dieGracefully(103, 'Could not define user directory!');
else if ($Verbose) logEntry('Defined user directory.');

// / Create a folder when the MakeFolder POST input is defined.
if (isset($_POST['MakeFolder'])) { 
  // / Sanitize folder POST input first.
  list ($NewFolderPaths, $VariableIsSanitized) = sanitize($_POST['MakeFolder'], TRUE); 
  if (!$VariableIsSanitized) dieGracefully(104, 'Could not sanitize folder inputs!');
  else if ($Verbose) logEntry('Sanitized folder inputs.');
  // / Create the folder.
  list ($NewFolderPaths, $FolderExists) makeFolders($LibrarySelected, $NewFolderPaths);
  if (!$FolderExists) logEntry('Could not create all desired folders.'); 
  else if ($Verbose) logEntry('Folders created successfully.'); }
// / ----------------------------------------------------------------------------------