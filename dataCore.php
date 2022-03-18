<?php
/* 
HonestRepair Diablo Engine  -  Data Core
https://www.HonestRepair.net
https://github.com/zelon88

Licensed Under GNU GPLv3
https://www.gnu.org/licenses/gpl-3.0.html

Author: Justin Grimes
Date: 3/15/2022
<3 Open-Source

The Data Core handles complex bulk data operations like compression/extraction & encryption/decryption.
Functions are prefaced with DC so they don't collide with other functions of other cores.
*/

// / ----------------------------------------------------------------------------------
// / Make sure the core is loaded.
if (!isset($ConfigIsLoaded) or $ConfigIsLoaded !== TRUE) die('ERROR!!! dataCore0: The requested application does not support out-of-context execution'.PHP_EOL); 
// / ----------------------------------------------------------------------------------

// / ----------------------------------------------------------------------------------
// / The following code sets the functions for the session.

// Data Backup specific functions & logic.

// / A function to verify the status of the backup ecosystem. 
function backupCheck() { 
  // / Set variables.
  global $DataBackups;
  $BackupLoc = $BackupsActive = $LibraryIsSelected = $LibraryName = $LibraryDir = $LibraryContents = $LibraryExists = $LibraryIsActive = $LibraryIsInactive = $LibraryIsCustom = $LibraryIsDefault = FALSE;
  // / Select the BACKUPS library, if it is active.
  list ($LibraryIsSelected, $LibraryName, $LibraryDir, $LibraryContents, $LibraryExists, $LibraryIsActive, $LibraryIsInactive, $LibraryIsCustom, $LibraryIsDefault) = selectLibrary('BACKUPS');
  // / All checks are considered to have passed if the BACKUPS library is active & selected.
  if ($LibraryExists && $LibraryIsActive && $LibraryIsSelected && $DataBackups) { 
    $BackupsActive = TRUE;
    $BackupLoc = $LibraryDir; }
  return(array($BackupsActive, $BackupLoc)); }

// / A function to backup individual files.
// / Supported Data Types include:
// /   'CACHE'
// /   'USERCACHE'
// /   'LOG'
// /   'USERLOG'
// /   'CONFIG'
function backupFile($DataType, $Data) { 
  global $Date;
  $iterator = 0;
  $BackupSuccess = $backupCacheLocExists = FALSE;
  list ($BackupsActive, $BackupLoc) = backupCheck();
  if ($BackupsActive) { 
    if ($DataType === 'CACHE') { 
      $backupCacheLoc = $BackupLoc.'Cache'.DIRECTORY_SEPARATOR;
      if (!file_exists($backupCacheLoc)) $backupCacheLocExists = mkdir($backupCacheLoc);
      else $backupCacheLocExists = TRUE;
      $dataExtension = '.php.';
      $dataFile = basename($Data, $dataExtension);
      $dataFilename = basename($Data);
      $backupData = $backupCacheLoc.$dataFile.'_'.$Date.'_'.$iterator.$dataExtension;
      while (file_exists($backupData)) { 
        $iterator++;
        $backupData = $backupCacheLoc.$dataFile.'_'.$Date.'_'.$iterator.$dataExtension; }
      if (file_exists($Data)) copy($Data, $backupData); 
      if (file_exists($backupData) or !$backupCacheLocExists) $BackupSuccess = TRUE;
    // / Clean up unneeded memory.
    $backupCacheLoc = $dataExtension = $dataFile = $dataFilename = $backupData = $backupCacheLocExists = NULL;
    unset($backupCacheLoc, $dataExtension, $dataFile, $dataFilename, $backupData, $backupCacheLocExists);
    return($BackupSuccess); } } }

// / A function to initialize encryption by ensuring that user supplied keys exist.
function DCinitEncryption() { 
  global $UserID, $LibrariesActive; 

  return ($EncryptionIsInitialized);
}

// / A function to encrypt a large amount of data.
function DCencryptData($data, $dataType, $key) { 

}

// / A function to decrypt a large amount of data.
function DCdecryptData($data, $dataType, $key) { 

}

// / A function to reencrypt a large amount of data.
// / Useful for when a user changes their encryption keys and files need to be reprocessed.
function DCrecryptData($data, $dataType, $oldKey, $newKey) { 

} 

// / A function for loading a list of dictionary ID's.
function DCloadDictionaryList() { 
  global $LibrariesActive;
  $Result = $DictionaryList = FALSE;
  if (isset($LibrariesActive['DATA'])) {
    $dataLib = $LibrariesActive['DATA'];
    if (!is_dir($dataLib)) { 
      $Result = mkdir($dataLib, 0755); } 
    if (is_dir($dataLib)) {
      $dataCacheFile = $dataLib.'dictionaryCache.php'; 
      if (!file_exists($dataCacheFile)) { file_put_contents($dataCacheFile, '<?php $DictionaryList = array(); '.PHP_EOL); }
      if (file_exists($dataCacheFile)); } }
  $dataLib = $dataCacheFile = NULL;
  unset($dataLib, $dataCacheFile);
  return (array($Result, $DictionaryList)); }

// / A function for loading data from a specific dictionary ID into memory.
function DCloadDictionary($dictionaryID) { }

// / A function to select a dictionary to use based on input file attributes.
function DCselectDictionary($fileSize, $fileType, $mimeType) { 
  global $DictionaryList;
  $IsCompressible = $DictionaryID = FALSE;
  // DO SOME MAGIC TO DETERMINE THE BEST DICTIONARY TO USE.
  return (array($IsCompressible, $DictionaryID)); }

// / A function to gather attributes about the input file for dictionary selection.
function DCanalyzeFile($file) { 
  global $DictionaryList;
  $IsCompressible = $DictionaryID = FALSE;
  if (!is_dir($file) && !is_link($file)) { 
    if (is_file($file)) {  
      $fileSize = filesize($file); 
      $fileType = filetype($file); 
      $mimeType = mime_content_type($file);
      list ($IsCompressible, $DictionaryID) = $DCselectDictionary($fileSize, $fileType, $mimeType); } }
  $fileSize = $fileType = $mimeType = NULL;
  unset($fileSize, $fileType, $mimeType);
  return (array($IsCompressible, $DictionaryID)); }

// / A function to load the contents of a dictionary file into memory. 
function DCloadDictFile($dictFile) {
  $FileIsLoaded = TRUE;
  $Data = FALSE;
  if (file_exists($file)) $Data = file_get_contents($dictFile);
  if ($Data === FALSE) $FileIsLoaded = FALSE;
  return (array($FileIsLoaded, $Data)); }

function DCpreCompress($data, $dictionary) { 
  $Result = $Data = FALSE;
  return (array($Result, $Data)); }

function DCcompressFolder($path) { }

function DCcompressFile($data, $dictionary) { 
  $Result = $Data = FALSE;
  return (array($Result, $Data, $Dictionary)); }

function DCwriteFile($data, $dictionary, $file) { } 
// / ----------------------------------------------------------------------------------