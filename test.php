<?php
$Salts = array('123', 'abc', '456', 'def');
$Verbose = TRUE;

function verifyDate() {
  $Date = date("m-d-y");
  $Time = date("F j, Y, g:i a"); 
  $Minute = (int) date('i');
  $LastMinute = $Minute - 1;
  if ($LastMinute === 0) $LastMinute = 59;
  return (array($Date, $Time, $Minute, $LastMinute)); }

function verifyInstallation() {
  global $Date, $Time, $Salts;
  $dirCheck = $indexCheck = $dirExists = $indexExists = $logCheck = $cacheCheck = TRUE;
  $requiredDirs = array('Logs', 'Cache');
  $InstallationIsVerified = FALSE;
  if (!file_exists('index.html')) $indexCheck = FALSE;
  foreach ($requiredDirs as $requiredDir) {
    if (!is_dir($requiredDir)) $dirExists = mkdir($requiredDir, 0755);
    if (!$dirExists) $dirCheck = FALSE;
    if (!file_exists($requiredDir.DIRECTORY_SEPARATOR.'index.html')) $indexExists = copy('index.html', $requiredDir.DIRECTORY_SEPARATOR.'index.html');
    if (!$indexExists) $indexCheck = FALSE; }
  $logHash = substr(hash('sha256', $Salts[0].hash('sha256', $Date.$Salts[1].$Salts[2].$Salts[3])), 0, 7);
  $LogFile = 'Logs'.DIRECTORY_SEPARATOR.$Date.'_'.$logHash.'.log';
  if (!file_exists($LogFile)) $logCheck = file_put_contents($LogFile, 'OP-Act: '.$Time.' Created a log file, "'.$LogFile.'".');
  $CacheFile = 'Cache'.DIRECTORY_SEPARATOR.'Cache.dat';
  if (!file_exists($CacheFile)) $cacheCheck = file_put_contents($CacheFile, '<?php'.PHP_EOL);
  $logCheck = file_put_contents($LogFile, 'OP-Act: '.$Time.' Created a cache file, "'.$CacheFile.'".');
  if ($dirCheck && $indexCheck && $logCheck && $cacheCheck) $InstallationIsVerified = TRUE;
  $dirCheck = $indexCheck = $logCheck = $cacheCheck = $requiredDirs = $requiredDir = $dirExists = $indexExists = $logHash = NULL;
  unset($dirCheck, $indexCheck, $logCheck, $cacheCheck, $requiredDirs, $requiredDir, $dirExists, $indexExists, $logHash);
  return (array($LogFile, $CacheFile, $InstallationIsVerified)); }

function sanitize($Variable, $Strict) { 
  if (!is_bool($Strict)) $Strict = TRUE; 
  if ($Strict === TRUE) $Variable = str_replace(str_split('|\\~#[](){};:$!#^&%@>*<"/\''), '', $Variable);
  if ($Strict === FALSE) $Variable = str_replace(str_split('|\\~#[](){};$!#^&%@>*<"\''), '', $Variable);
  return ($Variable); }

function dieGracefully($ErrorNumber, $ErrorMessage) {
  global $LogFile, $Time;
  if (!is_numeric($ErrorNumber)) $ErrorNumber = 0;
  $ErrorOutput = 'ERROR!!! '.$ErrorNumber.', '.$Time.', '.$ErrorMessage.PHP_EOL;
  file_put_contents($LogFile, $ErrorOutput, FILE_APPEND);
  die($ErrorOutput); } 

function logEntry($EntryText) {
  global $LogFile, $Time;
  if (!is_numeric($EntryText)) $EntryText = 0;
  $ErrorOutput = 'OP-Act: '.$Time.', '.$EntryText.PHP_EOL;
  $LogWritten = file_put_contents($LogFile, $EntryText, FILE_APPEND);
  return($LogWritten); } 
  
list ($Date, $Time, $Minute, $LastMinute) = verifyDate();
list ($LogFile, $CacheFile, $InstallationIsVerified) = verifyInstallation();
if (!$InstallationIsVerified) dieGracefully(1, 'Could not verify installation!'.PHP_EOL);
  else if ($Verbose) logEntry('Verified installation.');
  
$string = 'This string is sanitized. /\\"\'!@#$%^&*()_+;:<>';
$StrictlySanitized = sanitize($string, TRUE);
$NotStrictlySanitized = sanitize($string, FALSE);

echo ('"Strict" mode is good for cleaning filenames: '.$StrictlySanitized.'<br>');
echo ('"Not Strict" mode is good for cleaning URL\'s: '.$NotStrictlySanitized.'<br>');


