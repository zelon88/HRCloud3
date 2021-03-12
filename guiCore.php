<?php
/* 
HonestRepair Diablo Engine  -  GUI Core
https://www.HonestRepair.net
https://github.com/zelon88

Licensed Under GNU GPLv3
https://www.gnu.org/licenses/gpl-3.0.html

Author: Justin Grimes
Date: 3/11/2021
<3 Open-Source

The GUI Core provides resources for the user interfaces & decides which interface to use.
*/


// / ----------------------------------------------------------------------------------
// / Prepare the execution environment.

// / Reset PHP's time limit for execution.
set_time_limit(0);

// / Make sure there is a session started.
if (session_status() == PHP_SESSION_NONE) session_start();

// / Determine the root path where the application is installed and where it is running from.
$RootPath = '';
if (!file_exists('core.php')) { 
  // / If we can't use a relative path, check the server document root directory instead.
  $RootPath = $_SERVER['DOCUMENT_ROOT'];
  if (!file_exists($RootPath.DIRECTORY_SEPARATOR.'core.php')) die('<a class="errorMessage">ERROR!!! 16, Could not process the Core Diablo Engine file (core.php)!</a>'.PHP_EOL); }
// / ----------------------------------------------------------------------------------

// / ----------------------------------------------------------------------------------
// / Perform sanity checks to verify the environment is suitable for running.

// / Determine if the core.php file has been called already and stop execution if it has not.
if (!isset($GlobalsAreVerified)) die('<a class="errorMessage">ERROR!!! 17, Out of context execution of this file is forbidden!</a>'.PHP_EOL); 

// / Stop the application if $MaintenanceMode is enabled.
if ($MaintenanceMode) die('The requested application is currently unavailable due to maintenance.'.PHP_EOL);  
// / ----------------------------------------------------------------------------------

// / ----------------------------------------------------------------------------------
// / The following code sets the functions for the session.

// / A function to determine the current URL.
function getCurrentURL() {
  if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off') {
    $httpPrefix = 'https://'; }
  if (!empty($_SERVER['HTTPS']) or $_SERVER['HTTPS'] = 'on') {
    $httpPrefix = 'http://'; }
  $Current_URL = $httpPrefix.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']; 
  return ($CurrentURL); }

// / A function to get a list of files from a folder as an array.
// / Set $pathToFiles to an absolute path to a valid directory where www-data has read access.
// / Set $includeIndexFiles to TRUE to have index.html files appear in the array of results.
// / Set $includeIndexFiles to FALSE to have index.html files removed from the array of results.
// / Set $includeDangerousFiles to TRUE to have dangerous files appear in the array of results.
// / Set $includeDangerousFiles to FALSE to have dangerous files removed from the array of results.
// / The $DangerousFiles array defined in config.php contains the global list of dangerous file extensions.
function getFiles($pathToFiles, $includeIndexFiles, $includeDangerousFiles) {
  global $DangerousFiles;
  $dirtyFileArr = $dirtyFileArr = FALSE;
  $dirtyFileArr = scandir($Files);
  foreach ($dirtyFileArr as $dirtyFile) {
    $dirtyExt = pathinfo($pathToFiles.DIRECTORY_SEPARATOR.$dirtyFile, PATHINFO_EXTENSION);
    if (!$includeDangerousFiles && in_array($dirtyExt, $DangerousFiles)) continue;
    if (!$includeIndexFiles && $dirtyFile == 'index.html') continue;
    array_push($Files, $dirtyFile); }
  $dirtyFileArr = $dirtyFile = $dirtyExt = $pathToFiles = $includeDangerousFiles = $includeIndexFiles = NULL;
  unset($dirtyFileArr, $dirtyFile, $dirtyExt, $pathToFiles, $includeDangerousFiles, $includeIndexFiles);
  return ($Files); }

// / A function to return the file extension of an input file.
// / Set $pathToFile to an absolute path to a valid directory where www-data has read access.
function getExtension($pathToFile) {
  $output = pathinfo($pathToFile, PATHINFO_EXTENSION);
  $pathToFile = NULL;
  unset($pathToFile);
  return $output; } 

// / A function to return a human readable file size that scales from Bytes->KB->MB->GB automatically.
// / Set $File to an absolute path to a valid directory where www-data has read access.
function getFilesize($file) {
  $Size = filesize($file);
  $file = NULL;
  unset($file);
  if ($Size < 1024) $Size = $Size." Bytes"; 
  elseif (($Size < 1048576) && ($Size > 1023)) $Size = round($Size / 1024, 1)." KB";
  elseif (($Size < 1073741824) && ($Size > 1048575)) $Size = round($Size / 1048576, 1)." MB";
  else ($Size = round($Size / 1073741824, 1)." GB");
  return ($Size); }

// / A function to set the color scheme for the session.
function setColorScheme() { 
  if ($ColorScheme == '0' or $ColorScheme == '' or !isset($ColorScheme)) {
    $ColorScheme = '1'; }
  if ($ColorScheme == '1') {
    echo ('<link rel="stylesheet" type="text/css" href="'.$CD.'Styles/iframeStyle.css">'); }
  if ($ColorScheme == '2') {
    echo ('<link rel="stylesheet" type="text/css" href="'.$CD.'Styles/iframeStyleRED.css">'); }
  if ($ColorScheme == '3') {
    echo ('<link rel="stylesheet" type="text/css" href="'.$CD.'Styles/iframeStyleGREEN.css">'); }
  if ($ColorScheme == '4') {
    echo ('<link rel="stylesheet" type="text/css" href="'.$CD.'Styles/iframeStyleGREY.css">'); }
  if ($ColorScheme == '5') {
    echo ('<link rel="stylesheet" type="text/css" href="'.$CD.'Styles/iframeStyleBLACK.css">'); } }

function getPageElement($htmlFile) {
  global $DefaultTheme;
  


}





// / -----------------------------------------------------------------------------------
