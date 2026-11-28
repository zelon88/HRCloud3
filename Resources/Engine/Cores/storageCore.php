<?php
// / -----------------------------------------------------------------------------------
// / Copyright Information ...
// / HRProprietary Engine, Copyright on 11/27/2026 by Justin Grimes, www.github.com/zelon88
// /
// / License Information ...
// / This project is protected by the GNU GPLv3 Open-Source license.
// / https://www.gnu.org/licenses/gpl-3.0.html
// /
// / File Information ...
// / v5.0.0.
// / The Storage Core, one of the Engine's cores, from v5.0.0.
// / It keeps what signed in users store, in libraries an administrator names, & is the only part of the Engine that reaches them.
// / A library is a folder an administrator lists, personal or shared. A personal library holds a space of its own for each user,
// / named for the account's random identifier, which nobody else reaches. A shared library is one tree every signed in user reads,
// / & which its administrator lets admins, everyone or nobody write.
// / A space holds its tree, a trash that what is deleted goes to & a folder an upload waits in, so deleting can be undone & an upload
// / is never half there. The trash is emptied after the number of days the administrator sets.
// / A name is kept exactly as its user wrote it. Every folder a request names is walked a name at a time, & a link is never followed.
// / A file leaves only through this core, read as it is sent & never copied where the web server serves it, so nothing stored is
// / ever served as a page, & a later layer can decrypt it as it is sent.
// / Every write into a personal space is held to --User Storage Quota-- here, whether or not the listener runs, & every write is held
// / to --Storage Reserve--, on the disk the library is on.
// / The application pins it exactly via $RequiredStorageCoreVersion, & offers it with $EngineEnableStorage in its contract.
// / The Engine loads it with loadStorageCore() when a page asks a library for something, to sweep, to remove an account & for -v.
// / Error block 52000 through 52019 is reserved for the Storage Core.
// / See Documentation/ABOUT_STORAGE.txt.
// /
// / <3 Open-Source
// / -----------------------------------------------------------------------------------


// / -----------------------------------------------------------------------------------
// / A component may only be loaded by an application.
// / quickDie is not available here, because a component loaded directly has no core to
// / provide it. This is the one documented exception to quickDie being the only halt.
// / It names the Engine, as every other Engine core does.
if (!isset($CoreLoaded) or $CoreLoaded !== TRUE) die('ERROR!!! Engine-35000, A core component cannot be loaded directly!'.PHP_EOL);
// / -----------------------------------------------------------------------------------


// / -----------------------------------------------------------------------------------
// / The version of this component. Read by the application without executing this file.
$StorageCoreVersion = 'v5.0.0';
// / -----------------------------------------------------------------------------------


// / -----------------------------------------------------------------------------------
// / A function to say whether a string may name a file or a folder in a library.
// / Accepts the name. Returns TRUE when it may.
// / A name is kept exactly as its user wrote it, so it is accepted whole or refused whole & never changed. sanitize() would change
// / one, so the kernel's sanitizePath() checks it instead, which refuses a control character & anything PHP would open as a stream.
// / A name is valid UTF-8 of 1 to 255 bytes, holds no slash, is neither a dot nor two, & neither begins nor ends with a space.
function storageNameIsUsable($itemName) {
  // / Set variables.
  global $EnableMemoryProtection;
  $NameIsUsable = FALSE;
  $checkedName = '';
  $nameIsChecked = FALSE;
  if (is_string($itemName) && $itemName !== '' && strlen($itemName) <= 255) list ($checkedName, $nameIsChecked) = sanitizePath($itemName, FALSE);
  $NameIsUsable = ($nameIsChecked && $checkedName === $itemName && preg_match('//u', $itemName) === 1 && strpos($itemName, '/') === FALSE
    && $itemName !== '.' && $itemName !== '..' && trim($itemName, " \t") === $itemName);
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $checkedName, $nameIsChecked, $itemName);
  return $NameIsUsable; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to say whether a string may name a library.
// / Accepts the name. Returns TRUE when it may.
// / A library's name is 1 to 32 letters, digits, dashes & underscores, beginning with a letter or a digit, because it names a
// / folder in the data location & rides in every request a page sends. Every pattern this core holds a name to ends with D, so a
// / line break at the end of a name is refused rather than ignored.
function libraryNameIsUsable($libraryName) {
  // / Set variables.
  global $EnableMemoryProtection;
  $NameIsUsable = FALSE;
  $NameIsUsable = (is_string($libraryName) && preg_match('/^[A-Za-z0-9][A-Za-z0-9_-]{0,31}$/D', $libraryName) === 1);
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $libraryName);
  return $NameIsUsable; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to turn the folder a request names into the names it is made of.
// / Accepts the folder as text, its names joined by slashes, with or without a slash at either end. Nothing names the top of the tree.
// / Returns whether every name may be used & the names, in that order.
// / A folder is at most 32 names deep & 4096 bytes long, & a slash beside another leaves an empty name, which is refused.
function storagePathNames($folderText) {
  // / Set variables.
  global $EnableMemoryProtection;
  $PathIsUsable = FALSE;
  $PathNames = array();
  $trimmedText = $pathName = '';
  if (is_string($folderText) && strlen($folderText) <= 4096) {
    $PathIsUsable = TRUE;
    $trimmedText = trim($folderText, '/');
    if ($trimmedText !== '') $PathNames = explode('/', $trimmedText);
    if (count($PathNames) > 32) $PathIsUsable = FALSE;
    foreach ($PathNames as $pathName) if (!storageNameIsUsable($pathName)) $PathIsUsable = FALSE; }
  if (!$PathIsUsable) $PathNames = array();
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $trimmedText, $pathName, $folderText);
  return array($PathIsUsable, $PathNames); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to say why a location an administrator gave a library cannot be used, or nothing where it can.
// / Accepts the location as it was written. Returns the reason, as the end of a sentence, or an empty string.
// / A library given a location of its own is kept out of the data location, the installation, the hosted location & the document
// / root, & out of every folder the operating system keeps its own files in, so nothing a user stores can be served, run or taken
// / for the Engine's own, or the host's. It is never one of the folders pathIsSafeToModifyRecursively() refuses, such as /srv itself.
// / Its location must already be a real folder, & is used as it is written, so a link is never followed to reach it.
function libraryLocationProblem($libraryLocation) {
  // / Set variables.
  global $DataRootLoc, $InstLoc, $HostedLoc, $EnableMemoryProtection;
  $LocationProblem = '';
  $writtenLocation = $checkedLocation = $resolvedLocation = $documentRoot = $systemFolder = '';
  $systemFolders = array('/bin', '/boot', '/dev', '/etc', '/lib', '/lib32', '/lib64', '/libx32', '/proc', '/root', '/run', '/sbin', '/sys', '/tmp', '/usr', '/var/tmp');
  $locationIsChecked = FALSE;
  $writtenLocation = is_string($libraryLocation) ? rtrim($libraryLocation, DIRECTORY_SEPARATOR) : '';
  if ($writtenLocation !== '') list ($checkedLocation, $locationIsChecked) = sanitizePath($writtenLocation, TRUE);
  $documentRoot = (string)applicationSetting('DocumentRoot', '');
  // / Where a location is is judged before whether it is there, so a location an administrator has yet to make is told what is wrong with it.
  if (!$locationIsChecked or $checkedLocation !== $writtenLocation) $LocationProblem = 'its location is not an absolute path without a part that is a dot, two dots or empty';
  else foreach ($systemFolders as $systemFolder) if ($LocationProblem === '' && pathHoldsPath($systemFolder, $writtenLocation)) $LocationProblem = 'its location '.$writtenLocation.' is in '.$systemFolder.', where the operating system keeps its own files';
  if ($LocationProblem !== '') $resolvedLocation = '';
  else if ((string)$DataRootLoc !== '' && (pathHoldsPath($DataRootLoc, $writtenLocation) or pathHoldsPath($writtenLocation, $DataRootLoc))) $LocationProblem = 'its location '.$writtenLocation.' is inside the data location or holds it. Leave its Location empty to keep it in the data location';
  else if ((string)$InstLoc !== '' && (pathHoldsPath($InstLoc, $writtenLocation) or pathHoldsPath($writtenLocation, $InstLoc))) $LocationProblem = 'its location '.$writtenLocation.' is inside the installation or holds it';
  else if ((string)$HostedLoc !== '' && (pathHoldsPath($HostedLoc, $writtenLocation) or pathHoldsPath($writtenLocation, $HostedLoc))) $LocationProblem = 'its location '.$writtenLocation.' is inside the hosted location or holds it, where the web server would serve it';
  else if (trim($documentRoot) !== '' && (pathHoldsPath($documentRoot, $writtenLocation) or pathHoldsPath($writtenLocation, $documentRoot))) $LocationProblem = 'its location '.$writtenLocation.' is inside the document root or holds it, where the web server would serve it';
  else {
    $resolvedLocation = (string)@realpath($writtenLocation);
    if ($resolvedLocation === '' or !is_dir($resolvedLocation)) $LocationProblem = 'its location '.$writtenLocation.' is not a folder';
    else if ($resolvedLocation !== $writtenLocation) $LocationProblem = 'its location '.$writtenLocation.' leads to '.$resolvedLocation.', & a link is never followed';
    else if (!pathIsSafeToModifyRecursively($writtenLocation)) $LocationProblem = 'its location '.$writtenLocation.' is a folder the operating system needs'; }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $writtenLocation, $checkedLocation, $resolvedLocation, $documentRoot, $systemFolder, $systemFolders, $locationIsChecked, $libraryLocation);
  return $LocationProblem; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to say why one library an administrator listed cannot be used, or nothing where it can.
// / Accepts the library's name, its settings, the folder libraries are kept in & the roots of the libraries accepted before it.
// / Returns the reason & the library as this core keeps it, in that order.
// / Kind is personal or shared. Location is empty, which keeps the library in Libraries in the data location, or an absolute path.
// / Writers says who may write a shared library, & is admins, everyone or none. A personal library is written only by its owner.
// / Two libraries never overlap, so a folder is never in two libraries at once.
function readLibrarySettings($libraryName, $librarySettings, $librariesFolder, $acceptedRoots) {
  // / Set variables.
  global $EnableMemoryProtection;
  $LibraryProblem = '';
  $LibraryRecord = array();
  $libraryKind = $libraryLocation = $libraryWriters = $libraryRoot = $acceptedName = $acceptedRoot = '';
  if (is_array($librarySettings)) {
    $libraryKind = (isset($librarySettings['Kind']) && is_string($librarySettings['Kind'])) ? $librarySettings['Kind'] : '';
    $libraryLocation = (isset($librarySettings['Location']) && is_string($librarySettings['Location'])) ? rtrim($librarySettings['Location'], DIRECTORY_SEPARATOR) : '';
    $libraryWriters = (isset($librarySettings['Writers']) && is_string($librarySettings['Writers'])) ? $librarySettings['Writers'] : 'admins'; }
  if (!libraryNameIsUsable($libraryName)) $LibraryProblem = 'its name is not 1 to 32 letters, digits, dashes & underscores beginning with a letter or a digit';
  else if (!is_array($librarySettings)) $LibraryProblem = 'it is not a list of settings';
  else if ($libraryKind !== 'personal' && $libraryKind !== 'shared') $LibraryProblem = 'its Kind is neither personal nor shared';
  else if ($libraryKind === 'shared' && !in_array($libraryWriters, array('admins', 'everyone', 'none'), TRUE)) $LibraryProblem = 'its Writers is not admins, everyone or none';
  else if ($libraryLocation === '' && (string)$librariesFolder === '') $LibraryProblem = 'it has no Location, & this installation keeps no Libraries folder in its data location';
  else if ($libraryLocation !== '') $LibraryProblem = libraryLocationProblem($libraryLocation);
  if ($LibraryProblem === '') {
    $libraryRoot = ($libraryLocation === '') ? (string)$librariesFolder.DIRECTORY_SEPARATOR.$libraryName : $libraryLocation;
    foreach ($acceptedRoots as $acceptedName => $acceptedRoot) if ($LibraryProblem === '' && (pathHoldsPath($acceptedRoot, $libraryRoot) or pathHoldsPath($libraryRoot, $acceptedRoot))) $LibraryProblem = 'its location overlaps the library '.$acceptedName;
    if ($LibraryProblem === '') $LibraryRecord = array('Name' => (string)$libraryName, 'Kind' => $libraryKind, 'Root' => $libraryRoot, 'Placed' => ($libraryLocation === ''),
      'Writers' => ($libraryKind === 'shared' ? $libraryWriters : 'owner')); }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $libraryKind, $libraryLocation, $libraryWriters, $libraryRoot, $acceptedName, $acceptedRoot, $libraryName, $librarySettings, $librariesFolder, $acceptedRoots);
  return array($LibraryProblem, $LibraryRecord); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to read what the application & its administrator say about storage.
// / Accepts whether to warn about what cannot be used. -v warns about nothing, because -v changes nothing.
// / Returns the settings, keyed by what this core calls them, & a sanity check, in that order.
// / Each setting is read through applicationSetting(), so this core never knows what the application calls one.
// / A library that cannot be used is left out & named, & the others are kept. Where none can be used, the library this core falls
// / back to is Drive, a personal library in the data location, which configuration cannot overwrite.
// / The trash is emptied after the days TrashLifetime names, 0 for never, & a value that is not a whole number up to 3650 is 30.
// / Each message names a setting in plain words, from v5.0.0, because the Engine cannot know the label an application gives it.
// / Returns a sanity check, because it is longer than 35 lines. The settings are whole when at least one library can be used.
function storageSettings($reportProblems) {
  // / Set variables.
  global $EnableMemoryProtection;
  $StorageSettings = array();
  $SettingsAreWhole = FALSE;
  $defaultLibraries = array('Drive' => array('Kind' => 'personal', 'Location' => ''));
  $defaultTrashDays = 30;
  $configuredLibraries = $libraryRecord = $acceptedRoots = $librarySettings = array();
  $libraryName = $libraryProblem = $librariesFolder = '';
  $trashSetting = $quotaSetting = $reserveSetting = 0;
  $librariesFolder = rtrim((string)applicationSetting('LibraryLocation', ''), DIRECTORY_SEPARATOR);
  $configuredLibraries = applicationSetting('StorageLibraries', $defaultLibraries);
  if (!is_array($configuredLibraries) or count($configuredLibraries) === 0) {
    if ($reportProblems === TRUE) warningEntry('No library is listed, so the library this installation falls back to, Drive, is used.');
    $configuredLibraries = $defaultLibraries; }
  $StorageSettings = array('LibraryLoc' => $librariesFolder, 'Libraries' => array(), 'Refused' => array(), 'UserLoc' => rtrim((string)applicationSetting('UserLocation', ''), DIRECTORY_SEPARATOR),
    'TrashDays' => $defaultTrashDays, 'QuotaBytes' => 0, 'ReserveBytes' => 0);
  foreach ($configuredLibraries as $libraryName => $librarySettings) {
    list ($libraryProblem, $libraryRecord) = readLibrarySettings($libraryName, $librarySettings, $librariesFolder, $acceptedRoots);
    if ($libraryProblem !== '') {
      $StorageSettings['Refused'][printableText((string)$libraryName, TRUE)] = $libraryProblem;
      if ($reportProblems === TRUE) warningEntry('The library '.printableText((string)$libraryName, TRUE).' could not be used, because '.$libraryProblem.'.'); }
    else {
      $StorageSettings['Libraries'][$libraryRecord['Name']] = $libraryRecord;
      $acceptedRoots[$libraryRecord['Name']] = $libraryRecord['Root']; } }
  if (count($StorageSettings['Libraries']) === 0 && $configuredLibraries !== $defaultLibraries) {
    if ($reportProblems === TRUE) warningEntry('No library listed could be used, so the library this installation falls back to, Drive, is used.');
    list ($libraryProblem, $libraryRecord) = readLibrarySettings('Drive', $defaultLibraries['Drive'], $librariesFolder, array());
    if ($libraryProblem === '') $StorageSettings['Libraries']['Drive'] = $libraryRecord; }
  $trashSetting = applicationSetting('TrashLifetime', $defaultTrashDays);
  if (is_string($trashSetting) && ctype_digit($trashSetting)) $trashSetting = (int)$trashSetting;
  if (is_int($trashSetting) && $trashSetting >= 0 && $trashSetting <= 3650) $StorageSettings['TrashDays'] = $trashSetting;
  else if ($reportProblems === TRUE) warningEntry('The trash lifetime is not a whole number of days from 0 to 3650, so the trash is emptied after '.$defaultTrashDays.' days.');
  $quotaSetting = max(0, (int)applicationSetting('UserStorageQuota', 1024));
  $reserveSetting = max(0, (int)applicationSetting('StorageReserveMegabytes', 1024));
  $StorageSettings['QuotaBytes'] = $quotaSetting * 1048576;
  $StorageSettings['ReserveBytes'] = $reserveSetting * 1048576;
  $SettingsAreWhole = (count($StorageSettings['Libraries']) > 0);
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $defaultLibraries, $defaultTrashDays, $configuredLibraries, $libraryRecord, $acceptedRoots, $librarySettings, $libraryName, $libraryProblem, $librariesFolder, $trashSetting, $quotaSetting, $reserveSetting, $reportProblems);
  return array($StorageSettings, $SettingsAreWhole); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to make a folder this core keeps, where it is missing.
// / Accepts the folder. Returns TRUE when it is a real folder the web server account may write.
// / A folder is made private, & something at its name that is not a folder, a link included, is never used.
function makeStorageFolder($folderPath) {
  // / Set variables.
  global $EnableMemoryProtection;
  $FolderIsReady = FALSE;
  $folderStatus = array();
  clearstatcache(TRUE, $folderPath);
  if (!file_exists($folderPath) && !is_link($folderPath)) @mkdir($folderPath, 0700);
  clearstatcache(TRUE, $folderPath);
  $folderStatus = @lstat($folderPath);
  $FolderIsReady = (is_array($folderStatus) && ((int)$folderStatus['mode'] & 0170000) === 0040000 && is_writable($folderPath));
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $folderStatus, $folderPath);
  return $FolderIsReady; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to say whether a signed in account may write a library.
// / Accepts the library as this core keeps it & the account. Returns TRUE when it may.
// / A personal library is written by its owner, & a shared one by admins, by everyone or by nobody, as its Writers says.
function libraryIsWritableBy($libraryRecord, $storageAccount) {
  // / Set variables.
  global $EnableMemoryProtection;
  $MayWrite = FALSE;
  $accountRole = '';
  $accountRole = (string)($storageAccount['Role'] ?? '');
  if (($libraryRecord['Kind'] ?? '') === 'personal') $MayWrite = TRUE;
  else if (($libraryRecord['Writers'] ?? '') === 'everyone') $MayWrite = TRUE;
  else if (($libraryRecord['Writers'] ?? '') === 'admins') $MayWrite = ($accountRole === 'admin');
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $accountRole, $libraryRecord, $storageAccount);
  return $MayWrite; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to say whether a signed in account may empty a library's trash, & take anything in it out for good.
// / Accepts the library as this core keeps it & the account. Returns TRUE when it may.
// / A user empties their own trash. In a shared library only an admin may, so a user who may write it can delete & restore, & take
// / out for good only what they alone added, which trashRecordIsPurgeable() decides, & what they delete of anybody else's can
// / still be put back until the trash lets it go.
function libraryIsPurgeableBy($libraryRecord, $storageAccount) {
  // / Set variables.
  global $EnableMemoryProtection;
  $MayPurge = FALSE;
  $MayPurge = (($libraryRecord['Kind'] ?? '') === 'personal' or (libraryIsWritableBy($libraryRecord, $storageAccount) && (string)($storageAccount['Role'] ?? '') === 'admin'));
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $libraryRecord, $storageAccount);
  return $MayPurge; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to name the folder this core keeps at the top of a shared library, for its trash, what is still being written & who
// / added what. Accepts nothing. Returns the name, a dot & the application's name in the form the Engine gives every name on disk,
// / as .hrcloud3. It is the application's identity from its contract, never the name an administrator gives an installation, so it
// / never moves, & each application on the Engine that shares one folder as a library keeps a folder of its own there.
function storageKeptName() {
  // / Set variables.
  $KeptName = '';
  $KeptName = '.'.engineApplicationSlug();
  return $KeptName; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to find the space a signed in account works in, in one library, & to make its folders the first time they are needed.
// / Accepts the settings, the library's name & the account, which carries its Name, Role & Folder. Returns whether the space can be
// / used, the space & what was wrong, in that order.
// / A personal space is the account's own folder in the library, named for its random identifier, which holds Files, Trash & Incoming.
// / A shared space is the library's whole folder, & its trash, its incoming folder & Authors, the record of who added what, are in the
// / folder storageKeptName() names at its top, which no user may name. Account names the account the space is opened for.
// / A library kept in the data location is made in Libraries the first time it is used, & Libraries itself is -fp's to make, which is
// / error 52000 where it is missing. One given a location of its own must be there already, & the web server account must be able to
// / write it for anybody to write it.
// / Returns a sanity check, because it is longer than 35 lines. The finding is empty only where the space can be used.
function storageSpace($storageSettings, $libraryName, $storageAccount) {
  // / Set variables.
  global $EnableMemoryProtection;
  $SpaceIsReady = FALSE;
  $StorageSpace = array();
  $SpaceFinding = 'unavailable';
  $libraryRecord = array();
  $libraryRoot = $spaceFolder = $systemFolder = $accountFolder = $shownName = '';
  $rootIsReady = $mayWrite = $librariesAreReady = FALSE;
  $shownName = printableText((string)$libraryName, TRUE);
  $accountFolder = (string)($storageAccount['Folder'] ?? '');
  if (!is_string($libraryName) or !isset($storageSettings['Libraries'][$libraryName])) $SpaceFinding = 'library-unknown';
  else if (preg_match('/^[a-f0-9]{18}$/D', $accountFolder) !== 1) $SpaceFinding = 'signed-out';
  else {
    $libraryRecord = $storageSettings['Libraries'][$libraryName];
    $libraryRoot = $libraryRecord['Root'];
    $mayWrite = libraryIsWritableBy($libraryRecord, $storageAccount);
    // / A library kept in the data location is made in Libraries, which -fp makes & gives the web server account.
    $librariesAreReady = ($storageSettings['LibraryLoc'] !== '' && is_dir($storageSettings['LibraryLoc']) && !is_link($storageSettings['LibraryLoc']) && is_writable($storageSettings['LibraryLoc']));
    if ($libraryRecord['Placed'] && !$librariesAreReady) errorEntry('The libraries folder '.$storageSettings['LibraryLoc'].' is missing or cannot be written, so no library kept in the data location can be used. Run -fp as root!', 52000, FALSE);
    else if ($libraryRecord['Placed']) $rootIsReady = makeStorageFolder($libraryRoot);
    else $rootIsReady = (is_dir($libraryRoot) && !is_link($libraryRoot) && is_readable($libraryRoot));
    if (!$rootIsReady && !$libraryRecord['Placed']) warningEntry('The library '.$shownName.' at '.$libraryRoot.' is missing or cannot be read by the web server account.');
    else if (!$rootIsReady && $librariesAreReady) warningEntry('The library '.$shownName.' could not be made at '.$libraryRoot.'.');
    if ($rootIsReady && $libraryRecord['Kind'] === 'personal') {
      $spaceFolder = $libraryRoot.DIRECTORY_SEPARATOR.$accountFolder;
      if (makeStorageFolder($spaceFolder) && makeStorageFolder($spaceFolder.DIRECTORY_SEPARATOR.'Files') && makeStorageFolder($spaceFolder.DIRECTORY_SEPARATOR.'Trash') && makeStorageFolder($spaceFolder.DIRECTORY_SEPARATOR.'Incoming')) {
        $StorageSpace = array('Library' => $libraryRecord['Name'], 'Kind' => 'personal', 'Tree' => $spaceFolder.DIRECTORY_SEPARATOR.'Files', 'Trash' => $spaceFolder.DIRECTORY_SEPARATOR.'Trash',
          'Incoming' => $spaceFolder.DIRECTORY_SEPARATOR.'Incoming', 'Authors' => '', 'Account' => $accountFolder, 'MayWrite' => TRUE, 'MayPurge' => TRUE, 'Disk' => $libraryRoot);
        $SpaceIsReady = TRUE; }
      else warningEntry('The space of an account in the library '.$shownName.' could not be made in '.$libraryRoot.'. The web server account must be able to write it.'); }
    else if ($rootIsReady) {
      $systemFolder = $libraryRoot.DIRECTORY_SEPARATOR.storageKeptName();
      // / A library nobody may write needs no trash, & one an account may not write is only read, so neither makes anything.
      if ($mayWrite && !(makeStorageFolder($systemFolder) && makeStorageFolder($systemFolder.DIRECTORY_SEPARATOR.'Trash') && makeStorageFolder($systemFolder.DIRECTORY_SEPARATOR.'Incoming')
        && makeStorageFolder($systemFolder.DIRECTORY_SEPARATOR.'Authors'))) {
        $mayWrite = FALSE;
        warningEntry('The shared library '.$shownName.' at '.$libraryRoot.' cannot be written by the web server account, so it is only read.'); }
      $StorageSpace = array('Library' => $libraryRecord['Name'], 'Kind' => 'shared', 'Tree' => $libraryRoot, 'Trash' => ($mayWrite ? $systemFolder.DIRECTORY_SEPARATOR.'Trash' : ''),
        'Incoming' => ($mayWrite ? $systemFolder.DIRECTORY_SEPARATOR.'Incoming' : ''), 'Authors' => ($mayWrite ? $systemFolder.DIRECTORY_SEPARATOR.'Authors' : ''), 'Account' => $accountFolder,
        'MayWrite' => $mayWrite, 'MayPurge' => ($mayWrite && libraryIsPurgeableBy($libraryRecord, $storageAccount)), 'Disk' => $libraryRoot);
      $SpaceIsReady = TRUE; } }
  if ($SpaceIsReady) $SpaceFinding = '';
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $libraryRecord, $libraryRoot, $spaceFolder, $systemFolder, $accountFolder, $shownName, $rootIsReady, $mayWrite, $librariesAreReady, $storageSettings, $libraryName, $storageAccount);
  return array($SpaceIsReady, $StorageSpace, $SpaceFinding); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to say whether a name is one only this core may use, at the top of a shared library.
// / Accepts the space, the folder's names & the name. Returns TRUE when the name is kept back.
// / A shared library keeps its trash, its incoming folder & who added what in the folder storageKeptName() names at its top, so no
// / user may name anything there by that name. A name is compared with its case folded, because a disk that ignores case, such as
// / one shared from Windows, would open the kept folder by any spelling of it. Without mbstring, only the case of ASCII is folded.
function storageNameIsKept($storageSpace, $pathNames, $itemName) {
  // / Set variables.
  global $EnableMemoryProtection;
  $NameIsKept = FALSE;
  $foldedName = '';
  if (($storageSpace['Kind'] ?? '') === 'shared' && is_array($pathNames) && count($pathNames) === 0 && is_string($itemName))
    $foldedName = function_exists('mb_convert_case') ? (string)mb_convert_case($itemName, MB_CASE_FOLD, 'UTF-8') : strtolower($itemName);
  $NameIsKept = ($foldedName !== '' && $foldedName === strtolower(storageKeptName()));
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $foldedName, $storageSpace, $pathNames, $itemName);
  return $NameIsKept; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to find a folder of a space by its names, without following a link.
// / Accepts the space & the folder's names. Returns whether the folder was found, its path & what was wrong, in that order.
// / Each name is looked at with lstat before the next is joined to it, so a link anywhere in the way is found & never followed.
// / A shared library's own folder at its top is never found this way. What was wrong is missing, link or not-folder, & is empty
// / where the folder was found.
function resolveStorageFolder($storageSpace, $pathNames) {
  // / Set variables.
  global $EnableMemoryProtection;
  $FolderWasFound = FALSE;
  $FolderPath = $FolderFinding = '';
  $pathName = $candidatePath = '';
  $candidateStatus = array();
  $nameIndex = 0;
  $FolderPath = (string)($storageSpace['Tree'] ?? '');
  $candidateStatus = ($FolderPath === '') ? FALSE : @lstat($FolderPath);
  if (!is_array($candidateStatus) or ((int)$candidateStatus['mode'] & 0170000) !== 0040000) $FolderFinding = 'missing';
  if (!is_array($pathNames)) $FolderFinding = 'missing';
  else foreach (array_values($pathNames) as $nameIndex => $pathName) {
    if ($FolderFinding !== '') break;
    if ($nameIndex === 0 && storageNameIsKept($storageSpace, array(), $pathName)) {
      $FolderFinding = 'missing';
      break; }
    redeclare($candidatePath, $FolderPath.DIRECTORY_SEPARATOR.$pathName);
    clearstatcache(TRUE, $candidatePath);
    redeclare($candidateStatus, @lstat($candidatePath));
    if (!is_array($candidateStatus)) $FolderFinding = 'missing';
    else if (((int)$candidateStatus['mode'] & 0170000) === 0120000) $FolderFinding = 'link';
    else if (((int)$candidateStatus['mode'] & 0170000) !== 0040000) $FolderFinding = 'not-folder';
    else $FolderPath = $candidatePath; }
  if ($FolderFinding === '') $FolderWasFound = TRUE;
  else $FolderPath = '';
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $pathName, $candidatePath, $candidateStatus, $nameIndex, $storageSpace, $pathNames);
  return array($FolderWasFound, $FolderPath, $FolderFinding); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to say what one name in a folder of a space is, without following a link.
// / Accepts the folder's path & the name. Returns the kind, which is file, folder, link, other or missing, & the path, in that order.
function storageItemKind($folderPath, $itemName) {
  // / Set variables.
  global $EnableMemoryProtection;
  $ItemKind = 'missing';
  $ItemPath = '';
  $itemStatus = array();
  $itemType = 0;
  $ItemPath = (string)$folderPath.DIRECTORY_SEPARATOR.(string)$itemName;
  clearstatcache(TRUE, $ItemPath);
  $itemStatus = @lstat($ItemPath);
  if (is_array($itemStatus)) {
    $itemType = (int)$itemStatus['mode'] & 0170000;
    if ($itemType === 0100000) $ItemKind = 'file';
    else if ($itemType === 0040000) $ItemKind = 'folder';
    else if ($itemType === 0120000) $ItemKind = 'link';
    else $ItemKind = 'other'; }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $itemStatus, $itemType, $folderPath, $itemName);
  return array($ItemKind, $ItemPath); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to list a folder of a space.
// / Accepts the space & the folder's names. Returns whether it was listed, its entries, whether it holds more than were listed & what
// / was wrong, in that order.
// / Each entry is a file or a folder, with its name, its size in bytes & when it was last changed. Folders come first, then files,
// / each in the order a person reads them, so 2 comes before 10.
// / A link, a name no request could name & anything that is neither a file nor a folder is left out, because none can be acted on,
// / & a folder of more than 10,000 entries lists its first 10,000.
// / Returns a sanity check, because it is longer than 35 lines. The finding is empty only where the folder was listed.
function listStorageFolder($storageSpace, $pathNames) {
  // / Set variables.
  global $EnableMemoryProtection;
  $FolderWasListed = $EntriesWereCut = FALSE;
  $FolderEntries = array();
  $ListFinding = '';
  $folderWasFound = FALSE;
  $folderPath = $folderFinding = $entryName = $entryKind = $entryPath = '';
  $folderNames = $entryStatus = array();
  list ($folderWasFound, $folderPath, $folderFinding) = resolveStorageFolder($storageSpace, $pathNames);
  if (!$folderWasFound) $ListFinding = 'missing';
  else {
    $folderNames = @scandir($folderPath);
    if (!is_array($folderNames)) $ListFinding = 'unavailable';
    else {
      foreach ($folderNames as $entryName) {
        if ($entryName === '.' or $entryName === '..' or !storageNameIsUsable($entryName) or storageNameIsKept($storageSpace, $pathNames, $entryName)) continue;
        if (count($FolderEntries) >= 10000) {
          $EntriesWereCut = TRUE;
          break; }
        list ($entryKind, $entryPath) = storageItemKind($folderPath, $entryName);
        if ($entryKind !== 'file' && $entryKind !== 'folder') continue;
        redeclare($entryStatus, @lstat($entryPath));
        if (is_array($entryStatus)) $FolderEntries[] = array('Name' => (string)$entryName, 'Kind' => $entryKind, 'Size' => ($entryKind === 'file' ? (int)$entryStatus['size'] : 0), 'Modified' => (int)$entryStatus['mtime']); }
      usort($FolderEntries, 'compareStorageEntries');
      $FolderWasListed = TRUE; } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $folderWasFound, $folderPath, $folderFinding, $entryName, $entryKind, $entryPath, $folderNames, $entryStatus, $storageSpace, $pathNames);
  return array($FolderWasListed, $FolderEntries, $EntriesWereCut, $ListFinding); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to order two entries of a folder, for usort().
// / Accepts the two entries. Returns below 0 where the first comes first, above 0 where the second does, & 0 where neither does.
// / A folder comes before a file, & two of a kind are in the order a person reads them, without regard to case.
function compareStorageEntries($firstEntry, $secondEntry) {
  // / Set variables.
  global $EnableMemoryProtection;
  $EntryOrder = 0;
  if ($firstEntry['Kind'] === $secondEntry['Kind']) $EntryOrder = strnatcasecmp($firstEntry['Name'], $secondEntry['Name']);
  else $EntryOrder = ($firstEntry['Kind'] === 'folder') ? -1 : 1;
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $firstEntry, $secondEntry);
  return $EntryOrder; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to find a name nothing in a folder has yet, near the one wanted.
// / Accepts the folder's path & the name wanted. Returns the name, or an empty string where none could be found.
// / The name wanted is used where it is free, & otherwise a number is put before its extension, as report (2).pdf, up to 999.
function freeStorageName($folderPath, $wantedName) {
  // / Set variables.
  global $EnableMemoryProtection;
  $FreeName = '';
  $nameStem = $nameExtension = $candidateName = $candidateKind = $candidatePath = '';
  $dotPosition = $nameNumber = 0;
  list ($candidateKind, $candidatePath) = storageItemKind($folderPath, $wantedName);
  if ($candidateKind === 'missing') $FreeName = (string)$wantedName;
  else {
    $dotPosition = (int)strrpos((string)$wantedName, '.');
    $nameStem = ($dotPosition > 0) ? substr((string)$wantedName, 0, $dotPosition) : (string)$wantedName;
    $nameExtension = ($dotPosition > 0) ? substr((string)$wantedName, $dotPosition) : '';
    for ($nameNumber = 2; $nameNumber <= 999 && $FreeName === ''; $nameNumber++) {
      redeclare($candidateName, $nameStem.' ('.$nameNumber.')'.$nameExtension);
      list ($candidateKind, $candidatePath) = storageItemKind($folderPath, $candidateName);
      if ($candidateKind === 'missing' && storageNameIsUsable($candidateName)) $FreeName = $candidateName; } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $nameStem, $nameExtension, $candidateName, $candidateKind, $candidatePath, $dotPosition, $nameNumber, $folderPath, $wantedName);
  return $FreeName; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to put a file or a folder that is ready in place under a name, without replacing what is there.
// / Accepts the path of what is ready, the folder it goes in & the name. Returns TRUE when it is there under that name.
// / A file is given a second name with link(), which fails where the name is taken, & then loses its first, so two requests putting
// / a file under one name at once can never replace each other's. A disk that keeps no second name for a file has it renamed into
// / place instead, where nothing has the name a moment before. A folder is renamed into place, where nothing has the name.
function placeStorageItem($readyPath, $folderPath, $itemName) {
  // / Set variables.
  global $EnableMemoryProtection;
  $ItemWasPlaced = FALSE;
  $targetPath = $targetKind = '';
  $readyStatus = array();
  $targetPath = (string)$folderPath.DIRECTORY_SEPARATOR.(string)$itemName;
  $readyStatus = @lstat($readyPath);
  if (is_array($readyStatus) && ((int)$readyStatus['mode'] & 0170000) === 0100000 && @link($readyPath, $targetPath)) $ItemWasPlaced = @unlink($readyPath);
  else if (is_array($readyStatus) && (((int)$readyStatus['mode'] & 0170000) === 0100000 or ((int)$readyStatus['mode'] & 0170000) === 0040000)) {
    list ($targetKind, $targetPath) = storageItemKind($folderPath, $itemName);
    if ($targetKind === 'missing') $ItemWasPlaced = @rename($readyPath, $targetPath); }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $targetPath, $targetKind, $readyStatus, $readyPath, $folderPath, $itemName);
  return $ItemWasPlaced; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to make a name nothing else will ever pick, for a trash entry or something this core is still writing.
// / Accepts nothing. Returns the name, as 20261127061500-0a1b2c3d4e5f6a7b.
// / It is the moment it was made, to the second, then the microsecond as five hexadecimal digits & eleven random ones, so names made in
// / one second still sort in the order they were made, & a trash read backwards is newest first.
function storageEntryName() {
  // / Set variables.
  global $EnableMemoryProtection;
  $EntryName = '';
  $momentParts = array();
  $momentParts = explode(' ', microtime());
  $EntryName = gmdate('YmdHis', (int)$momentParts[1]).'-'.sprintf('%05x', min(999999, (int)round((float)$momentParts[0] * 1000000))).substr(bin2hex(random_bytes(6)), 0, 11);
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $momentParts);
  return $EntryName; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to make a name for something this core is still writing, which nothing else will ever pick.
// / Accepts the folder it is written in. Returns the path.
function incomingStoragePath($incomingFolder) {
  // / Set variables.
  global $EnableMemoryProtection;
  $IncomingPath = '';
  $IncomingPath = (string)$incomingFolder.DIRECTORY_SEPARATOR.storageEntryName();
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $incomingFolder);
  return $IncomingPath; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to add up the bytes a signed in account keeps.
// / Accepts the settings & the account. Returns whether everything was measured & the bytes, in that order.
// / It counts the account's folder in Users & its space in every personal library, its trash included, because a trash full of
// / deleted files still fills the disk. A shared library is the administrator's & is counted against nobody.
// / The Authentication Manager measures each folder, as it measures one for the listener.
function accountStorageBytes($storageSettings, $storageAccount) {
  // / Set variables.
  global $EnableMemoryProtection;
  $BytesWereMeasured = TRUE;
  $StoredBytes = 0;
  $measuredFolders = $libraryRecord = array();
  $libraryName = $accountFolder = $measuredFolder = $managerVersion = '';
  $folderWasMeasured = $managerIsLoaded = FALSE;
  $folderBytes = 0;
  $accountFolder = (string)($storageAccount['Folder'] ?? '');
  if (!function_exists('measureFolderBytes') && function_exists('loadEngineManager')) list ($managerIsLoaded, $managerVersion) = loadEngineManager('authenticationManager.php');
  if (preg_match('/^[a-f0-9]{18}$/D', $accountFolder) !== 1 or !function_exists('measureFolderBytes')) $BytesWereMeasured = FALSE;
  else {
    if ($storageSettings['UserLoc'] !== '') $measuredFolders[] = $storageSettings['UserLoc'].DIRECTORY_SEPARATOR.$accountFolder;
    foreach ($storageSettings['Libraries'] as $libraryName => $libraryRecord) if ($libraryRecord['Kind'] === 'personal') $measuredFolders[] = $libraryRecord['Root'].DIRECTORY_SEPARATOR.$accountFolder;
    foreach ($measuredFolders as $measuredFolder) {
      if (!is_dir($measuredFolder) or is_link($measuredFolder)) continue;
      list ($folderWasMeasured, $folderBytes) = measureFolderBytes($measuredFolder);
      if ($folderWasMeasured) $StoredBytes += (int)$folderBytes;
      else $BytesWereMeasured = FALSE; } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $measuredFolders, $libraryRecord, $libraryName, $accountFolder, $measuredFolder, $managerVersion, $folderWasMeasured, $managerIsLoaded, $folderBytes, $storageSettings, $storageAccount);
  return array($BytesWereMeasured, $StoredBytes); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to decide whether a write of some bytes may go into a space.
// / Accepts the settings, the space, the account & the bytes. Returns whether it may & what stops it, in that order.
// / A write into a personal space is held to --User Storage Quota--, which is 0 for none, & is measured here, so the quota holds
// / whether or not the listener runs. What cannot be measured is let through & is a warning, as the listener lets it through.
// / Every write is held to --Storage Reserve--, on the disk of the library it goes to, so no library fills its disk to nothing.
// / What stops it is quota or disk-full, & is empty where it may go ahead.
function storageWriteAllows($storageSettings, $storageSpace, $storageAccount, $writeBytes) {
  // / Set variables.
  global $EnableMemoryProtection;
  $WriteAllowed = FALSE;
  $WriteFinding = '';
  $bytesWereMeasured = FALSE;
  $storedBytes = $incomingBytes = 0;
  $freeBytes = FALSE;
  $incomingBytes = max(0, (int)$writeBytes);
  if (($storageSpace['Kind'] ?? '') === 'personal' && (int)$storageSettings['QuotaBytes'] > 0) {
    list ($bytesWereMeasured, $storedBytes) = accountStorageBytes($storageSettings, $storageAccount);
    if (!$bytesWereMeasured) warningEntry('The files of '.(string)($storageAccount['Username'] ?? '').' could not be measured against --User Storage Quota--, so a write of '.$incomingBytes.' byte(s) was allowed.');
    else if ($storedBytes + $incomingBytes > (int)$storageSettings['QuotaBytes']) $WriteFinding = 'quota'; }
  if ($WriteFinding === '' && (int)$storageSettings['ReserveBytes'] > 0) {
    $freeBytes = @disk_free_space((string)($storageSpace['Disk'] ?? ''));
    if ($freeBytes !== FALSE && (float)$freeBytes - $incomingBytes < (float)$storageSettings['ReserveBytes']) $WriteFinding = 'disk-full'; }
  $WriteAllowed = ($WriteFinding === '');
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $bytesWereMeasured, $storedBytes, $incomingBytes, $freeBytes, $storageSettings, $storageSpace, $storageAccount, $writeBytes);
  return array($WriteAllowed, $WriteFinding); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to make a folder in a folder of a space.
// / Accepts the space, the folder's names & the new folder's name. Returns what happened, as an outcome a page names.
// / made means it is there. A name that is taken, kept back or unusable is refused, & a space the account may not write is read-only.
// / In a shared library, the account is recorded as the one that added the folder.
function makeStorageSubfolder($storageSpace, $pathNames, $folderName) {
  // / Set variables.
  global $EnableMemoryProtection;
  $FolderOutcome = 'unavailable';
  $folderWasFound = $authorsWereRecorded = FALSE;
  $folderPath = $folderFinding = $itemKind = $itemPath = '';
  $treeEntries = array();
  if (empty($storageSpace['MayWrite'])) $FolderOutcome = 'read-only';
  else if (!storageNameIsUsable($folderName) or storageNameIsKept($storageSpace, $pathNames, $folderName)) $FolderOutcome = 'name-invalid';
  else {
    list ($folderWasFound, $folderPath, $folderFinding) = resolveStorageFolder($storageSpace, $pathNames);
    if ($folderWasFound) list ($itemKind, $itemPath) = storageItemKind($folderPath, $folderName);
    if (!$folderWasFound) $FolderOutcome = 'missing';
    else if ($itemKind !== 'missing') $FolderOutcome = 'exists';
    else if (@mkdir($itemPath, 0700)) {
      $FolderOutcome = 'made';
      list ($authorsWereRecorded, $treeEntries) = recordStorageAuthors($storageSpace, $itemPath); } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $folderWasFound, $authorsWereRecorded, $folderPath, $folderFinding, $itemKind, $itemPath, $treeEntries, $storageSpace, $pathNames, $folderName);
  return $FolderOutcome; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to keep one file a page uploaded, in a folder of a space whose writes were allowed.
// / Accepts the space, the folder's path & names, the upload & what to do where its name is taken, which is refuse or keep, which
// / keeps both. Returns the name it was kept under & the reason it was not, in that order. Exactly one of them is empty.
// / It waits in the space's incoming folder until it is whole, & is then put in place, so a half written file is never seen.
// / In a shared library, the account is recorded as the one that added it while it waits, & the record goes with it where it cannot
// / be put in place. The reason is an outcome a page names.
function storeUploadedFile($storageSpace, $folderPath, $pathNames, $uploadedFile, $whenTaken) {
  // / Set variables.
  global $EnableMemoryProtection;
  $StoredName = $RefusedReason = '';
  $uploadError = $authorsForgotten = 0;
  $uploadName = $incomingPath = $targetKind = $targetPath = '';
  $authorsWereRecorded = FALSE;
  $treeEntries = array();
  $uploadError = (int)($uploadedFile['Error'] ?? UPLOAD_ERR_NO_FILE);
  $uploadName = (string)($uploadedFile['Name'] ?? '');
  if ($uploadError === UPLOAD_ERR_INI_SIZE or $uploadError === UPLOAD_ERR_FORM_SIZE) $RefusedReason = 'too-large';
  else if ($uploadError !== UPLOAD_ERR_OK or !is_uploaded_file((string)($uploadedFile['Path'] ?? ''))) $RefusedReason = 'upload-failed';
  else if (!storageNameIsUsable($uploadName) or storageNameIsKept($storageSpace, $pathNames, $uploadName)) $RefusedReason = 'name-invalid';
  else {
    $StoredName = ($whenTaken === 'keep') ? freeStorageName($folderPath, $uploadName) : $uploadName;
    $incomingPath = incomingStoragePath($storageSpace['Incoming']);
    if ($StoredName === '') $RefusedReason = 'exists';
    else if (!@move_uploaded_file((string)$uploadedFile['Path'], $incomingPath)) $RefusedReason = 'upload-failed';
    else {
      @chmod($incomingPath, 0600);
      list ($authorsWereRecorded, $treeEntries) = recordStorageAuthors($storageSpace, $incomingPath);
      if (!placeStorageItem($incomingPath, $folderPath, $StoredName)) {
        list ($targetKind, $targetPath) = storageItemKind($folderPath, $StoredName);
        $RefusedReason = ($targetKind === 'missing') ? 'upload-failed' : 'exists';
        if ($targetKind === 'missing') warningEntry('An upload to the library '.printableText((string)($storageSpace['Library'] ?? ''), TRUE).' could not be put in place from '.$incomingPath.'.');
        $authorsForgotten = forgetStorageAuthors((string)($storageSpace['Authors'] ?? ''), $treeEntries);
        @unlink($incomingPath); } } }
  if ($RefusedReason !== '') $StoredName = '';
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $uploadError, $authorsForgotten, $uploadName, $incomingPath, $targetKind, $targetPath, $authorsWereRecorded, $treeEntries, $storageSpace, $folderPath, $pathNames, $uploadedFile, $whenTaken);
  return array($StoredName, $RefusedReason); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to keep the files a page uploaded, in a folder of a space.
// / Accepts the settings, the space, the account, the folder's names, the uploads & what to do where a name is taken, which is refuse
// / or keep. Returns what happened, the names each file was kept under & the reason each other was not, in that order.
// / Every upload is measured together against the quota & the reserve first, & nothing is kept where they would not allow all of it.
// / uploaded means every file was kept, & partly-uploaded that some were. A file not kept is named with the outcome a page names.
function storeUploadedFiles($storageSettings, $storageSpace, $storageAccount, $pathNames, $uploadedFiles, $whenTaken) {
  // / Set variables.
  global $EnableMemoryProtection;
  $UploadOutcome = 'unavailable';
  $StoredNames = $RefusedNames = array();
  $folderWasFound = $writeAllowed = FALSE;
  $folderPath = $folderFinding = $writeFinding = $storedName = $refusedReason = '';
  $uploadedFile = array();
  $totalBytes = 0;
  if (empty($storageSpace['MayWrite'])) $UploadOutcome = 'read-only';
  else if (!is_array($uploadedFiles) or count($uploadedFiles) === 0) $UploadOutcome = 'upload-failed';
  else {
    list ($folderWasFound, $folderPath, $folderFinding) = resolveStorageFolder($storageSpace, $pathNames);
    foreach ($uploadedFiles as $uploadedFile) $totalBytes += max(0, (int)($uploadedFile['Bytes'] ?? 0));
    if ($folderWasFound) list ($writeAllowed, $writeFinding) = storageWriteAllows($storageSettings, $storageSpace, $storageAccount, $totalBytes);
    if (!$folderWasFound) $UploadOutcome = 'missing';
    else if (!$writeAllowed) $UploadOutcome = $writeFinding;
    else foreach ($uploadedFiles as $uploadedFile) {
      list ($storedName, $refusedReason) = storeUploadedFile($storageSpace, $folderPath, $pathNames, $uploadedFile, $whenTaken);
      if ($storedName !== '') $StoredNames[] = array('Name' => (string)$uploadedFile['Name'], 'StoredAs' => $storedName);
      else $RefusedNames[] = array('Name' => printableText((string)($uploadedFile['Name'] ?? ''), TRUE), 'Outcome' => $refusedReason); }
    if (count($StoredNames) > 0 && count($RefusedNames) === 0) $UploadOutcome = 'uploaded';
    else if (count($StoredNames) > 0) $UploadOutcome = 'partly-uploaded';
    else if (count($RefusedNames) > 0) $UploadOutcome = (string)$RefusedNames[0]['Outcome']; }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $folderWasFound, $writeAllowed, $folderPath, $folderFinding, $writeFinding, $storedName, $refusedReason, $uploadedFile, $totalBytes, $storageSettings, $storageSpace, $storageAccount, $pathNames, $uploadedFiles, $whenTaken);
  return array($UploadOutcome, $StoredNames, $RefusedNames); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to read the part of a file a request asks for, from its Range header.
// / Accepts the header & the file's size. Returns whole, part or unsatisfiable, & the first & last byte to send, in that order.
// / One range of bytes is honoured, as bytes=first-last, bytes=first- or bytes=-count. Anything else, several ranges included,
// / is answered with the whole file, as a server may. A range that begins past the end of the file cannot be satisfied.
function storageRange($rangeHeader, $fileSize) {
  // / Set variables.
  global $EnableMemoryProtection;
  $RangeKind = 'whole';
  $FirstByte = $LastByte = 0;
  $rangeMatches = array();
  $wholeSize = 0;
  $wholeSize = max(0, (int)$fileSize);
  if (is_string($rangeHeader) && preg_match('/^bytes=([0-9]{0,18})-([0-9]{0,18})$/D', trim($rangeHeader), $rangeMatches) === 1 && ($rangeMatches[1] !== '' or $rangeMatches[2] !== '')) {
    if ($rangeMatches[1] === '' && ((int)$rangeMatches[2] === 0 or $wholeSize === 0)) $RangeKind = 'unsatisfiable';
    else if ($rangeMatches[1] === '') {
      $RangeKind = 'part';
      $FirstByte = max(0, $wholeSize - (int)$rangeMatches[2]); }
    else if ((int)$rangeMatches[1] >= $wholeSize) $RangeKind = 'unsatisfiable';
    else if ($rangeMatches[2] === '' or (int)$rangeMatches[2] >= (int)$rangeMatches[1]) {
      $RangeKind = 'part';
      $FirstByte = (int)$rangeMatches[1]; }
    if ($RangeKind === 'part') $LastByte = ($rangeMatches[1] !== '' && $rangeMatches[2] !== '') ? min($wholeSize - 1, (int)$rangeMatches[2]) : $wholeSize - 1; }
  if ($RangeKind !== 'part') {
    $FirstByte = 0;
    $LastByte = max(0, $wholeSize - 1); }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $rangeMatches, $wholeSize, $rangeHeader, $fileSize);
  return array($RangeKind, $FirstByte, $LastByte); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to write a file's name into a Content-Disposition header.
// / Accepts the name. Returns the header's value.
// / Every browser reads filename*, which carries the name in UTF-8. filename carries it with every character outside plain printable
// / text, a quote & a backslash written as an underscore, for a browser that does not. A name is UTF-8, so a character is one underscore.
function storageDisposition($fileName) {
  // / Set variables.
  global $EnableMemoryProtection;
  $DispositionValue = '';
  $plainName = '';
  $plainName = (string)preg_replace('/[^\x20-\x7E]|["\\\\]/u', '_', (string)$fileName);
  $DispositionValue = 'attachment; filename="'.$plainName.'"; filename*=UTF-8\'\''.rawurlencode((string)$fileName);
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $plainName, $fileName);
  return $DispositionValue; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to stop PHP compressing or holding back what is sent next.
// / Accepts nothing. Returns TRUE when nothing will come between what is printed & the browser.
// / Output compression is turned off & every buffer that may be taken away is, because a compressed part of a file is not the part a
// / Range asked for, & a buffer would hold a whole file in memory. A buffer that may not be taken away is left, & the caller is told.
function releaseStorageOutput() {
  // / Set variables.
  $OutputIsReleased = FALSE;
  @ini_set('zlib.output_compression', 'Off');
  while (ob_get_level() > 0 && (ob_get_status()['flags'] & PHP_OUTPUT_HANDLER_REMOVABLE)) ob_end_clean();
  $OutputIsReleased = (ob_get_level() === 0);
  return $OutputIsReleased; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to send a file of a space to the browser that asked for it.
// / Accepts the space, the folder's names, the file's name & the request's Range header. Returns whether it was sent & what was
// / wrong, in that order.
// / The file is opened only where lstat shows a file, & what was opened is compared with it by device & inode, so a link swapped in
// / is never followed. It is handed over as a download that a browser never shows as a page, with nothing sniffed & nothing run.
// / A range that cannot be satisfied is answered with status 416. What was wrong is empty where it was sent.
// / Returns a sanity check, because it is longer than 35 lines.
function sendStorageFile($storageSpace, $pathNames, $fileName, $rangeHeader) {
  // / Set variables.
  global $EnableMemoryProtection;
  $FileWasSent = $SendIsJudged = FALSE;
  $SendFinding = 'unavailable';
  $folderWasFound = $outputIsReleased = FALSE;
  $folderPath = $folderFinding = $itemKind = $itemPath = $rangeKind = $sentChunk = '';
  $fileHandle = NULL;
  // / Each status is what lstat & fstat hand back, which is FALSE until a file is found & opened.
  $itemStatus = $handleStatus = FALSE;
  $firstByte = $lastByte = $bytesLeft = 0;
  list ($folderWasFound, $folderPath, $folderFinding) = resolveStorageFolder($storageSpace, $pathNames);
  if ($folderWasFound && storageNameIsUsable($fileName) && !storageNameIsKept($storageSpace, $pathNames, $fileName)) list ($itemKind, $itemPath) = storageItemKind($folderPath, $fileName);
  if ($itemKind === 'file') {
    $itemStatus = @lstat($itemPath);
    $fileHandle = @fopen($itemPath, 'rb'); }
  if (is_resource($fileHandle)) $handleStatus = @fstat($fileHandle);
  if (!is_array($itemStatus) or !is_array($handleStatus) or (int)$itemStatus['dev'] !== (int)$handleStatus['dev'] or (int)$itemStatus['ino'] !== (int)$handleStatus['ino'] or ((int)$handleStatus['mode'] & 0170000) !== 0100000) $SendFinding = 'missing';
  else if (headers_sent()) warningEntry('A file could not be sent, because something was written before its headers.');
  else {
    list ($rangeKind, $firstByte, $lastByte) = storageRange($rangeHeader, (int)$handleStatus['size']);
    $outputIsReleased = releaseStorageOutput();
    if (!$outputIsReleased) $rangeKind = ($rangeKind === 'part') ? 'whole' : $rangeKind;
    header('Accept-Ranges: '.($outputIsReleased ? 'bytes' : 'none'));
    header('Cache-Control: private, no-store');
    header('X-Content-Type-Options: nosniff');
    header('Content-Security-Policy: default-src \'none\'; sandbox');
    if ($rangeKind === 'unsatisfiable') {
      http_response_code(416);
      header('Content-Range: bytes */'.(int)$handleStatus['size']); }
    else {
      http_response_code($rangeKind === 'part' ? 206 : 200);
      header('Content-Type: application/octet-stream');
      header('Content-Disposition: '.storageDisposition($fileName));
      if ($rangeKind === 'part') header('Content-Range: bytes '.$firstByte.'-'.$lastByte.'/'.(int)$handleStatus['size']);
      $bytesLeft = ((int)$handleStatus['size'] === 0) ? 0 : $lastByte - $firstByte + 1;
      if ($outputIsReleased) header('Content-Length: '.$bytesLeft);
      if ($firstByte > 0) fseek($fileHandle, $firstByte);
      while ($bytesLeft > 0 && !feof($fileHandle) && connection_aborted() === 0) {
        redeclare($sentChunk, (string)fread($fileHandle, (int)min(65536, $bytesLeft)));
        if ($sentChunk === '') break;
        print($sentChunk);
        flush();
        $bytesLeft -= strlen($sentChunk); } }
    $FileWasSent = TRUE;
    $SendFinding = ''; }
  if (is_resource($fileHandle)) fclose($fileHandle);
  $SendIsJudged = ($FileWasSent xor $SendFinding !== '');
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $folderWasFound, $outputIsReleased, $folderPath, $folderFinding, $itemKind, $itemPath, $rangeKind, $sentChunk, $fileHandle, $itemStatus, $handleStatus, $firstByte, $lastByte, $bytesLeft, $storageSpace, $pathNames, $fileName, $rangeHeader);
  return array($FileWasSent, $SendFinding, $SendIsJudged); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to find a file or a folder of a space by its folder's names & its own name.
// / Accepts the space, the folder's names & the name. Returns the kind, which is file or folder, the folder's path & what was wrong,
// / in that order. What was wrong is name-invalid, missing or empty where it was found. A link is never something to act on.
function findStorageItem($storageSpace, $pathNames, $itemName) {
  // / Set variables.
  global $EnableMemoryProtection;
  $ItemKind = $FolderPath = '';
  $ItemFinding = 'missing';
  $folderWasFound = FALSE;
  $folderFinding = $itemPath = $foundKind = '';
  if (!storageNameIsUsable($itemName) or storageNameIsKept($storageSpace, $pathNames, $itemName)) $ItemFinding = 'name-invalid';
  else {
    list ($folderWasFound, $FolderPath, $folderFinding) = resolveStorageFolder($storageSpace, $pathNames);
    if ($folderWasFound) list ($foundKind, $itemPath) = storageItemKind($FolderPath, $itemName);
    if ($foundKind === 'file' or $foundKind === 'folder') {
      $ItemKind = $foundKind;
      $ItemFinding = ''; } }
  if ($ItemFinding !== '') $FolderPath = '';
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $folderWasFound, $folderFinding, $itemPath, $foundKind, $storageSpace, $pathNames, $itemName);
  return array($ItemKind, $FolderPath, $ItemFinding); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to give a file or a folder of a space a new name, in the folder it is in.
// / Accepts the space, the folder's names, the name it has & the new name. Returns what happened, as an outcome a page names.
// / renamed means it has the new name. A new name that is taken is refused, & nothing is ever replaced.
function renameStorageItem($storageSpace, $pathNames, $itemName, $newName) {
  // / Set variables.
  global $EnableMemoryProtection;
  $RenameOutcome = 'unavailable';
  $itemKind = $folderPath = $itemFinding = $targetKind = $targetPath = '';
  list ($itemKind, $folderPath, $itemFinding) = findStorageItem($storageSpace, $pathNames, $itemName);
  if (empty($storageSpace['MayWrite'])) $RenameOutcome = 'read-only';
  else if ($itemFinding !== '') $RenameOutcome = $itemFinding;
  else if (!storageNameIsUsable($newName) or storageNameIsKept($storageSpace, $pathNames, $newName)) $RenameOutcome = 'name-invalid';
  else if ((string)$newName === (string)$itemName) $RenameOutcome = 'renamed';
  else {
    list ($targetKind, $targetPath) = storageItemKind($folderPath, $newName);
    // / A new name that differs only by case names the same file where the disk ignores case, & is a rename of it there.
    if ($targetKind !== 'missing' && strtolower((string)$newName) !== strtolower((string)$itemName)) $RenameOutcome = 'exists';
    else if (@rename($folderPath.DIRECTORY_SEPARATOR.$itemName, $targetPath)) $RenameOutcome = 'renamed'; }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $itemKind, $folderPath, $itemFinding, $targetKind, $targetPath, $storageSpace, $pathNames, $itemName, $newName);
  return $RenameOutcome; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to move a file or a folder to another folder of the same space.
// / Accepts the space, the folder's names, the name & the names of the folder it goes to. Returns what happened, as an outcome a page names.
// / moved means it is in the other folder. A folder is never moved into itself or into a folder inside it, a name that is taken there
// / is refused, & nothing is ever replaced. It never leaves its space, so it costs nothing but a rename.
function moveStorageItem($storageSpace, $pathNames, $itemName, $destinationNames) {
  // / Set variables.
  global $EnableMemoryProtection;
  $MoveOutcome = 'unavailable';
  $itemKind = $folderPath = $itemFinding = $destinationPath = $destinationFinding = $targetKind = $targetPath = '';
  $destinationWasFound = FALSE;
  $itemNames = array();
  list ($itemKind, $folderPath, $itemFinding) = findStorageItem($storageSpace, $pathNames, $itemName);
  $itemNames = array_merge(array_values((array)$pathNames), array((string)$itemName));
  if (empty($storageSpace['MayWrite'])) $MoveOutcome = 'read-only';
  else if ($itemFinding !== '') $MoveOutcome = $itemFinding;
  else if ($itemKind === 'folder' && is_array($destinationNames) && array_slice(array_values($destinationNames), 0, count($itemNames)) === $itemNames) $MoveOutcome = 'into-itself';
  else {
    list ($destinationWasFound, $destinationPath, $destinationFinding) = resolveStorageFolder($storageSpace, $destinationNames);
    if ($destinationWasFound) list ($targetKind, $targetPath) = storageItemKind($destinationPath, $itemName);
    if (!$destinationWasFound) $MoveOutcome = 'missing';
    else if ($destinationPath === $folderPath) $MoveOutcome = 'moved';
    else if (storageNameIsKept($storageSpace, $destinationNames, $itemName)) $MoveOutcome = 'name-invalid';
    else if ($targetKind !== 'missing') $MoveOutcome = 'exists';
    else if (@rename($folderPath.DIRECTORY_SEPARATOR.$itemName, $targetPath)) $MoveOutcome = 'moved'; }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $itemKind, $folderPath, $itemFinding, $destinationPath, $destinationFinding, $targetKind, $targetPath, $destinationWasFound, $itemNames, $storageSpace, $pathNames, $itemName, $destinationNames);
  return $MoveOutcome; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to copy one file without following a link, keeping when it was last changed.
// / Accepts the file & where its copy goes, which must not exist. Returns TRUE when the copy is whole.
// / The source is opened only where lstat shows a file & is compared with what was opened by device & inode. The copy is created
// / with x, which fails on any name that exists, a link included, & is private.
function copyStorageFile($sourcePath, $copyPath) {
  // / Set variables.
  global $EnableMemoryProtection;
  $FileWasCopied = FALSE;
  $sourceHandle = $copyHandle = NULL;
  // / Each status is what lstat & fstat hand back, which is FALSE until a file is found & opened.
  $sourceStatus = $handleStatus = FALSE;
  $bytesCopied = 0;
  $sourceStatus = @lstat($sourcePath);
  if (is_array($sourceStatus) && ((int)$sourceStatus['mode'] & 0170000) === 0100000) $sourceHandle = @fopen($sourcePath, 'rb');
  if (is_resource($sourceHandle)) $handleStatus = @fstat($sourceHandle);
  if (is_array($handleStatus) && (int)$handleStatus['dev'] === (int)$sourceStatus['dev'] && (int)$handleStatus['ino'] === (int)$sourceStatus['ino']) $copyHandle = @fopen($copyPath, 'xb');
  if (is_resource($copyHandle)) {
    $bytesCopied = (int)stream_copy_to_stream($sourceHandle, $copyHandle);
    $FileWasCopied = (fclose($copyHandle) && $bytesCopied === (int)$handleStatus['size']);
    @chmod($copyPath, 0600);
    if ($FileWasCopied) @touch($copyPath, (int)$sourceStatus['mtime']); }
  if (is_resource($sourceHandle)) fclose($sourceHandle);
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $sourceHandle, $copyHandle, $sourceStatus, $handleStatus, $bytesCopied, $sourcePath, $copyPath);
  return $FileWasCopied; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to copy a file or a folder & everything in it, without following a link.
// / Accepts what is copied & where its copy goes, which must not exist. Returns whether the copy is whole & how many links were left
// / out, in that order. A link is never copied & never followed, because what it leads to is not what the user stored.
// / The tree is walked with a list rather than by calling itself, so a deep tree cannot exhaust the stack.
function copyStorageTree($sourcePath, $copyPath) {
  // / Set variables.
  global $EnableMemoryProtection;
  $TreeWasCopied = FALSE;
  $LinksLeftOut = 0;
  $folderPairs = $folderPair = $sourceStatus = $entryStatus = $folderNames = array();
  $entryName = $entrySource = $entryCopy = '';
  $copyFailed = FALSE;
  $sourceStatus = @lstat($sourcePath);
  if (is_array($sourceStatus) && ((int)$sourceStatus['mode'] & 0170000) === 0100000) $TreeWasCopied = copyStorageFile($sourcePath, $copyPath);
  else if (is_array($sourceStatus) && ((int)$sourceStatus['mode'] & 0170000) === 0040000 && @mkdir($copyPath, 0700)) {
    $folderPairs = array(array($sourcePath, $copyPath));
    while (count($folderPairs) > 0 && !$copyFailed) {
      redeclare($folderPair, array_pop($folderPairs));
      redeclare($folderNames, @scandir($folderPair[0]));
      if (!is_array($folderNames)) $copyFailed = TRUE;
      else foreach ($folderNames as $entryName) {
        if ($entryName === '.' or $entryName === '..' or $copyFailed) continue;
        redeclare($entrySource, $folderPair[0].DIRECTORY_SEPARATOR.$entryName);
        redeclare($entryCopy, $folderPair[1].DIRECTORY_SEPARATOR.$entryName);
        redeclare($entryStatus, @lstat($entrySource));
        if (!is_array($entryStatus)) $copyFailed = TRUE;
        else if (((int)$entryStatus['mode'] & 0170000) === 0040000 && @mkdir($entryCopy, 0700)) $folderPairs[] = array($entrySource, $entryCopy);
        else if (((int)$entryStatus['mode'] & 0170000) === 0040000) $copyFailed = TRUE;
        else if (((int)$entryStatus['mode'] & 0170000) === 0100000) $copyFailed = !copyStorageFile($entrySource, $entryCopy);
        else $LinksLeftOut++; } }
    $TreeWasCopied = !$copyFailed; }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $folderPairs, $folderPair, $sourceStatus, $entryStatus, $folderNames, $entryName, $entrySource, $entryCopy, $copyFailed, $sourcePath, $copyPath);
  return array($TreeWasCopied, $LinksLeftOut); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to take away a file or a folder & everything in it, without following a link or crossing a disk.
// / Accepts what is taken away. Returns whether nothing of it is left & how many names were taken away, in that order.
// / Every name is looked at with lstat. A link & a file are taken away as names & never followed, & a folder is emptied before it is.
// / A folder on another disk than the tree, which is something mounted inside it, stops the removal, because it is not the tree's.
// / The tree is walked with a list rather than by calling itself, so a deep tree cannot exhaust the stack. Something already gone counts
// / as taken away. It runs as the web server account, in folders that account writes.
// / Returns a sanity check, because it is longer than 35 lines.
function removeStorageTree($treePath) {
  // / Set variables.
  global $EnableMemoryProtection;
  $TreeWasRemoved = $RemovalIsJudged = FALSE;
  $NamesRemoved = 0;
  $treeStatus = $entryStatus = $foldersToVisit = $foldersFound = $folderNames = array();
  $folderPath = $entryName = $entryPath = '';
  $treeDevice = -1;
  $removalFailed = FALSE;
  clearstatcache(TRUE, $treePath);
  $treeStatus = @lstat($treePath);
  if (!is_array($treeStatus)) $TreeWasRemoved = TRUE;
  else if (((int)$treeStatus['mode'] & 0170000) !== 0040000) {
    $TreeWasRemoved = @unlink($treePath);
    if ($TreeWasRemoved) $NamesRemoved++; }
  else {
    $treeDevice = (int)$treeStatus['dev'];
    $foldersToVisit = array((string)$treePath);
    while (count($foldersToVisit) > 0 && !$removalFailed) {
      redeclare($folderPath, array_pop($foldersToVisit));
      $foldersFound[] = $folderPath;
      redeclare($folderNames, @scandir($folderPath));
      if (!is_array($folderNames)) $removalFailed = TRUE;
      else foreach ($folderNames as $entryName) {
        if ($entryName === '.' or $entryName === '..' or $removalFailed) continue;
        redeclare($entryPath, $folderPath.DIRECTORY_SEPARATOR.$entryName);
        redeclare($entryStatus, @lstat($entryPath));
        if (!is_array($entryStatus)) continue;
        if (((int)$entryStatus['mode'] & 0170000) === 0040000 && (int)$entryStatus['dev'] !== $treeDevice) $removalFailed = TRUE;
        else if (((int)$entryStatus['mode'] & 0170000) === 0040000) $foldersToVisit[] = $entryPath;
        else if (@unlink($entryPath)) $NamesRemoved++;
        else $removalFailed = TRUE; } }
    // / A folder is found before every folder inside it, so taking them in the opposite order empties each before it is taken away.
    if (!$removalFailed) foreach (array_reverse($foldersFound) as $folderPath) {
      if (@rmdir($folderPath)) $NamesRemoved++;
      else $removalFailed = TRUE; }
    clearstatcache(TRUE, $treePath);
    $TreeWasRemoved = (!$removalFailed && !file_exists($treePath) && !is_link($treePath)); }
  // / What is said of the tree agrees with the disk, so a removal that reports success has left nothing.
  clearstatcache(TRUE, $treePath);
  $RemovalIsJudged = ($TreeWasRemoved === (!file_exists($treePath) && !is_link($treePath)));
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $treeStatus, $entryStatus, $foldersToVisit, $foldersFound, $folderNames, $folderPath, $entryName, $entryPath, $treeDevice, $removalFailed, $treePath);
  return array($TreeWasRemoved, $NamesRemoved, $RemovalIsJudged); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to measure what a copy would write, before it is made.
// / Accepts the path of a file or a folder. Returns whether it was measured & the bytes, in that order.
// / A file is its size, & a folder is measured as the Authentication Manager measures one for a quota.
function storageItemBytes($itemPath) {
  // / Set variables.
  global $EnableMemoryProtection;
  $BytesWereMeasured = FALSE;
  $ItemBytes = 0;
  $itemStatus = array();
  $managerIsLoaded = FALSE;
  $managerVersion = '';
  $itemStatus = @lstat($itemPath);
  if (!function_exists('measureFolderBytes') && function_exists('loadEngineManager')) list ($managerIsLoaded, $managerVersion) = loadEngineManager('authenticationManager.php');
  if (is_array($itemStatus) && ((int)$itemStatus['mode'] & 0170000) === 0100000) {
    $BytesWereMeasured = TRUE;
    $ItemBytes = (int)$itemStatus['size']; }
  else if (is_array($itemStatus) && function_exists('measureFolderBytes')) list ($BytesWereMeasured, $ItemBytes) = measureFolderBytes($itemPath);
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $itemStatus, $managerIsLoaded, $managerVersion, $itemPath);
  return array($BytesWereMeasured, $ItemBytes); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to describe one file or folder for storageTreeEntries().
// / Accepts what lstat said of it. Returns its inode, its disk, its kind &, for a file, its size & when it was last changed.
function storageTreeEntry($entryStatus) {
  // / Set variables.
  global $EnableMemoryProtection;
  $TreeEntry = array();
  $entryIsFile = FALSE;
  $entryIsFile = (((int)$entryStatus['mode'] & 0170000) === 0100000);
  $TreeEntry = array('Inode' => (int)$entryStatus['ino'], 'Device' => (int)$entryStatus['dev'], 'Kind' => ($entryIsFile ? 'file' : 'folder'),
    'Size' => ($entryIsFile ? (int)$entryStatus['size'] : 0), 'Modified' => ($entryIsFile ? (int)$entryStatus['mtime'] : 0));
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $entryIsFile, $entryStatus);
  return $TreeEntry; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to list a file or a folder & everything in it, as this core keeps track of who added each.
// / Accepts the path. Returns whether the list is whole & what storageTreeEntry() says of each, in that order.
// / A link is never followed. A link, anything that is neither a file nor a folder, & a folder on another disk are not listed, & leave
// / the list less than whole, as a name that vanishes while it is read does. At most 200,000 names are listed, as the Authentication
// / Manager measures a folder. The tree is walked with a list rather than by calling itself, so a deep tree cannot exhaust the stack.
function storageTreeEntries($itemPath) {
  // / Set variables.
  global $EnableMemoryProtection;
  $TreeIsWhole = FALSE;
  $TreeEntries = array();
  $itemStatus = $entryStatus = $folderNames = $foldersToVisit = array();
  $folderPath = $entryName = $entryPath = '';
  $treeDevice = -1;
  $walkFailed = FALSE;
  clearstatcache(TRUE, $itemPath);
  $itemStatus = @lstat($itemPath);
  if (is_array($itemStatus) && (((int)$itemStatus['mode'] & 0170000) === 0100000 or ((int)$itemStatus['mode'] & 0170000) === 0040000)) {
    $treeDevice = (int)$itemStatus['dev'];
    $TreeEntries[] = storageTreeEntry($itemStatus);
    if (((int)$itemStatus['mode'] & 0170000) === 0040000) $foldersToVisit[] = (string)$itemPath;
    while (count($foldersToVisit) > 0 && !$walkFailed) {
      redeclare($folderPath, array_pop($foldersToVisit));
      redeclare($folderNames, @scandir($folderPath));
      if (!is_array($folderNames)) $walkFailed = TRUE;
      else foreach ($folderNames as $entryName) {
        if ($entryName === '.' or $entryName === '..' or $walkFailed) continue;
        redeclare($entryPath, $folderPath.DIRECTORY_SEPARATOR.$entryName);
        redeclare($entryStatus, @lstat($entryPath));
        if (count($TreeEntries) >= 200000 or !is_array($entryStatus) or (int)$entryStatus['dev'] !== $treeDevice) $walkFailed = TRUE;
        else if (((int)$entryStatus['mode'] & 0170000) === 0040000) {
          $TreeEntries[] = storageTreeEntry($entryStatus);
          $foldersToVisit[] = $entryPath; }
        else if (((int)$entryStatus['mode'] & 0170000) === 0100000) $TreeEntries[] = storageTreeEntry($entryStatus);
        else $walkFailed = TRUE; } }
    $TreeIsWhole = !$walkFailed; }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $itemStatus, $entryStatus, $folderNames, $foldersToVisit, $folderPath, $entryName, $entryPath, $treeDevice, $walkFailed, $itemPath);
  return array($TreeIsWhole, $TreeEntries); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to record that the account a space is opened for added each file & folder of a list to a shared library.
// / Accepts the space & what storageTreeEntries() listed. Returns whether every one was recorded & how many were, in that order.
// / Each is recorded in the space's Authors folder under its inode, which a rename, a move & the trash keep, with the account, its
// / kind &, for a file, its size & when it was last changed, so a record left behind by something taken away outside this core is
// / not taken for a file that reuses its inode. A record replaces an old one only by being made anew, so a link is never written
// / through. Something on another disk than the Authors folder is not recorded. A space that keeps no authors, as a personal one
// / does not, has nothing to record & is answered TRUE.
function writeStorageAuthors($storageSpace, $treeEntries) {
  // / Set variables.
  global $EnableMemoryProtection;
  $AuthorsWereWritten = TRUE;
  $AuthorsWritten = 0;
  $authorsFolder = $accountFolder = $recordText = $recordPath = '';
  // / The status is what lstat hands back, which is FALSE until the Authors folder is found.
  $authorsStatus = FALSE;
  $treeEntry = array();
  $recordHandle = NULL;
  $recordWasWritten = FALSE;
  $authorsFolder = (string)($storageSpace['Authors'] ?? '');
  $accountFolder = (string)($storageSpace['Account'] ?? '');
  if ($authorsFolder !== '') $authorsStatus = @lstat($authorsFolder);
  if ($authorsFolder !== '' && (!is_array($authorsStatus) or ((int)$authorsStatus['mode'] & 0170000) !== 0040000 or preg_match('/^[a-f0-9]{18}$/D', $accountFolder) !== 1 or !is_array($treeEntries))) $AuthorsWereWritten = FALSE;
  else if ($authorsFolder !== '') foreach ($treeEntries as $treeEntry) {
    redeclare($recordWasWritten, FALSE);
    if ((int)$treeEntry['Device'] === (int)$authorsStatus['dev']) {
      redeclare($recordText, (string)json_encode(array('Author' => $accountFolder, 'Kind' => $treeEntry['Kind'], 'Size' => $treeEntry['Size'], 'Modified' => $treeEntry['Modified'])));
      redeclare($recordPath, $authorsFolder.DIRECTORY_SEPARATOR.(int)$treeEntry['Inode']);
      @unlink($recordPath);
      redeclare($recordHandle, @fopen($recordPath, 'xb'));
      if (is_resource($recordHandle)) {
        $recordWasWritten = (fwrite($recordHandle, $recordText) === strlen($recordText));
        if (!fclose($recordHandle)) $recordWasWritten = FALSE; } }
    if ($recordWasWritten) $AuthorsWritten++;
    else $AuthorsWereWritten = FALSE; }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $authorsFolder, $accountFolder, $recordText, $recordPath, $authorsStatus, $treeEntry, $recordHandle, $recordWasWritten, $storageSpace, $treeEntries);
  return array($AuthorsWereWritten, $AuthorsWritten); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to record who added a file or a folder & everything in it to a shared library, & to say so where it cannot be.
// / Accepts the space & the path of what was added. Returns whether all of it was recorded & what storageTreeEntries() listed of it,
// / in that order, so a caller that cannot put it in place can take the records away again. An upload & a copy are recorded while
// / they wait in the incoming folder, where no request reaches, so a record can never be written for something put in their place.
// / What cannot be recorded can be deleted for good only by an administrator, which is a warning. A personal space records nothing.
function recordStorageAuthors($storageSpace, $itemPath) {
  // / Set variables.
  global $EnableMemoryProtection;
  $AuthorsWereRecorded = TRUE;
  $TreeEntries = array();
  $treeIsWhole = $authorsWereWritten = FALSE;
  $authorsWritten = 0;
  if ((string)($storageSpace['Authors'] ?? '') !== '') {
    list ($treeIsWhole, $TreeEntries) = storageTreeEntries($itemPath);
    list ($authorsWereWritten, $authorsWritten) = writeStorageAuthors($storageSpace, $TreeEntries);
    $AuthorsWereRecorded = ($treeIsWhole && $authorsWereWritten);
    if (!$AuthorsWereRecorded) warningEntry('Who added something to the library '.printableText((string)($storageSpace['Library'] ?? ''), TRUE).' could not be recorded, so only an administrator can delete it forever.'); }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $treeIsWhole, $authorsWereWritten, $authorsWritten, $storageSpace, $itemPath);
  return array($AuthorsWereRecorded, $TreeEntries); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to find the one account that added a file or a folder & everything in it to a shared library.
// / Accepts the space & the path. Returns the account's folder, its random identifier, or an empty string where anything in it was
// / added by another account or by something other than this core, or cannot be told, so nothing is ever taken for one account's
// / alone that is not. A record is believed only where it is a file, names an account, & agrees with the kind, the size & the time
// / of what it is the record of.
// / Returns a sanity check, because it is longer than 35 lines. The author found is empty, or every record named it.
function readStorageAuthor($storageSpace, $itemPath) {
  // / Set variables.
  global $EnableMemoryProtection;
  $ItemAuthor = '';
  $AuthorIsJudged = FALSE;
  $authorsFolder = $recordText = $recordPath = $foundAuthor = '';
  // / Each status is what lstat hands back, which is FALSE until what it describes is found.
  $authorsStatus = $recordStatus = FALSE;
  $treeEntries = $treeEntry = $recordFields = array();
  $treeIsWhole = $authorIsSole = FALSE;
  $recordsMatched = 0;
  $authorsFolder = (string)($storageSpace['Authors'] ?? '');
  if ($authorsFolder !== '') list ($treeIsWhole, $treeEntries) = storageTreeEntries($itemPath);
  if ($treeIsWhole) $authorsStatus = @lstat($authorsFolder);
  if (is_array($authorsStatus) && ((int)$authorsStatus['mode'] & 0170000) === 0040000 && count($treeEntries) > 0) {
    $authorIsSole = TRUE;
    foreach ($treeEntries as $treeEntry) {
      if (!$authorIsSole) break;
      redeclare($recordPath, $authorsFolder.DIRECTORY_SEPARATOR.(int)$treeEntry['Inode']);
      redeclare($recordStatus, @lstat($recordPath));
      redeclare($recordText, (is_array($recordStatus) && ((int)$recordStatus['mode'] & 0170000) === 0100000) ? (string)@file_get_contents($recordPath, FALSE, NULL, 0, 4096) : '');
      redeclare($recordFields, json_decode($recordText, TRUE));
      if ((int)$treeEntry['Device'] !== (int)$authorsStatus['dev'] or !is_array($recordFields) or !isset($recordFields['Author'], $recordFields['Kind']) or !is_string($recordFields['Author'])
        or preg_match('/^[a-f0-9]{18}$/D', $recordFields['Author']) !== 1 or $recordFields['Kind'] !== $treeEntry['Kind'] or ($foundAuthor !== '' && $recordFields['Author'] !== $foundAuthor)
        or ($treeEntry['Kind'] === 'file' && ((int)($recordFields['Size'] ?? -1) !== $treeEntry['Size'] or (int)($recordFields['Modified'] ?? -1) !== $treeEntry['Modified']))) $authorIsSole = FALSE;
      else {
        $foundAuthor = $recordFields['Author'];
        $recordsMatched++; } } }
  if ($authorIsSole) $ItemAuthor = $foundAuthor;
  // / An author is found only where every file & folder listed had a record naming it.
  $AuthorIsJudged = ($ItemAuthor === '' or $recordsMatched === count($treeEntries));
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $authorsFolder, $recordText, $recordPath, $foundAuthor, $authorsStatus, $recordStatus, $treeEntries, $treeEntry, $recordFields, $treeIsWhole, $authorIsSole, $recordsMatched, $storageSpace, $itemPath);
  return array($ItemAuthor, $AuthorIsJudged); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to take away the records of who added files & folders this core is about to take away, or could not put in place.
// / Accepts the Authors folder & what storageTreeEntries() listed. Returns how many records were taken away.
// / Records are taken away before what they are the records of, so a file that later reuses an inode is never taken for the one that
// / was there. Only a record of something on the Authors folder's own disk is taken away, because it is the only kind ever written,
// / & only where Authors is a folder, so a link put in its place is never followed.
function forgetStorageAuthors($authorsFolder, $treeEntries) {
  // / Set variables.
  global $EnableMemoryProtection;
  $AuthorsForgotten = 0;
  // / The status is what lstat hands back, which is FALSE until the Authors folder is found.
  $authorsStatus = FALSE;
  $treeEntry = array();
  if ((string)$authorsFolder !== '' && is_array($treeEntries)) $authorsStatus = @lstat((string)$authorsFolder);
  if (is_array($authorsStatus) && ((int)$authorsStatus['mode'] & 0170000) === 0040000) foreach ($treeEntries as $treeEntry)
    if ((int)$treeEntry['Device'] === (int)$authorsStatus['dev'] && @unlink((string)$authorsFolder.DIRECTORY_SEPARATOR.(int)$treeEntry['Inode'])) $AuthorsForgotten++;
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $authorsStatus, $treeEntry, $authorsFolder, $treeEntries);
  return $AuthorsForgotten; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to take a trash entry, or something left in an incoming folder, away for good, with the records of who added it.
// / Accepts the path & the Authors folder of its space, or an empty string where the space keeps none.
// / Returns whether nothing of it is left & the sanity check removeStorageTree() returned, in that order.
function takeAwayStorageTree($treePath, $authorsFolder) {
  // / Set variables.
  global $EnableMemoryProtection;
  $TreeWasRemoved = $RemovalIsJudged = FALSE;
  $treeIsWhole = FALSE;
  $treeEntries = array();
  $authorsForgotten = $namesRemoved = 0;
  if ((string)$authorsFolder !== '') {
    list ($treeIsWhole, $treeEntries) = storageTreeEntries($treePath);
    $authorsForgotten = forgetStorageAuthors($authorsFolder, $treeEntries); }
  list ($TreeWasRemoved, $namesRemoved, $RemovalIsJudged) = removeStorageTree($treePath);
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $treeIsWhole, $treeEntries, $authorsForgotten, $namesRemoved, $treePath, $authorsFolder);
  return array($TreeWasRemoved, $RemovalIsJudged); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to copy a file or a folder of one space into a folder of a space the account may write, the same one or another.
// / Accepts the settings, the account, the source space, its folder's names, the name, the destination space, its folder's names &
// / what to do where the name is taken there, which is refuse or keep. Returns what happened & the copy's name, in that order.
// / The copy is measured against the quota & the reserve of where it goes first, is written in that space's incoming folder, & is put
// / in place only once it is whole, so a half written copy is never seen. copied means it is there. A link inside is left out.
// / In a shared library, the account is recorded as the one that added the copy & everything in it, as copies are new files.
// / Returns a sanity check, because it is longer than 35 lines.
function copyStorageItem($storageSettings, $storageAccount, $sourceSpace, $pathNames, $itemName, $destinationSpace, $destinationNames, $whenTaken) {
  // / Set variables.
  global $EnableMemoryProtection;
  $CopyOutcome = 'unavailable';
  $CopiedName = '';
  $CopyIsJudged = FALSE;
  $itemKind = $folderPath = $itemFinding = $destinationPath = $destinationFinding = $incomingPath = $writeFinding = $targetKind = $targetPath = '';
  $destinationWasFound = $bytesWereMeasured = $writeAllowed = $treeWasCopied = $treeWasRemoved = $removalIsJudged = $authorsWereRecorded = FALSE;
  $itemBytes = $linksLeftOut = $namesRemoved = $authorsForgotten = 0;
  $treeEntries = array();
  list ($itemKind, $folderPath, $itemFinding) = findStorageItem($sourceSpace, $pathNames, $itemName);
  if ($itemFinding === '') list ($destinationWasFound, $destinationPath, $destinationFinding) = resolveStorageFolder($destinationSpace, $destinationNames);
  if (empty($destinationSpace['MayWrite'])) $CopyOutcome = 'read-only';
  else if ($itemFinding !== '') $CopyOutcome = $itemFinding;
  else if (!$destinationWasFound) $CopyOutcome = 'missing';
  else if (storageNameIsKept($destinationSpace, $destinationNames, $itemName)) $CopyOutcome = 'name-invalid';
  else if ($itemKind === 'folder' && pathHoldsPath($folderPath.DIRECTORY_SEPARATOR.$itemName, $destinationPath)) $CopyOutcome = 'into-itself';
  else {
    list ($bytesWereMeasured, $itemBytes) = storageItemBytes($folderPath.DIRECTORY_SEPARATOR.$itemName);
    if ($bytesWereMeasured) list ($writeAllowed, $writeFinding) = storageWriteAllows($storageSettings, $destinationSpace, $storageAccount, $itemBytes);
    if ($bytesWereMeasured && $writeAllowed && $whenTaken === 'keep') $CopiedName = freeStorageName($destinationPath, $itemName);
    else if ($bytesWereMeasured && $writeAllowed) {
      list ($targetKind, $targetPath) = storageItemKind($destinationPath, $itemName);
      $CopiedName = ($targetKind === 'missing') ? (string)$itemName : ''; }
    if (!$bytesWereMeasured) warningEntry('Something in the library '.printableText((string)($sourceSpace['Library'] ?? ''), TRUE).' was not copied, because it holds more than can be measured.');
    if (!$bytesWereMeasured) $CopyOutcome = 'too-large';
    else if (!$writeAllowed) $CopyOutcome = $writeFinding;
    else if ($CopiedName === '') $CopyOutcome = 'exists';
    else {
      $incomingPath = incomingStoragePath($destinationSpace['Incoming']);
      list ($treeWasCopied, $linksLeftOut) = copyStorageTree($folderPath.DIRECTORY_SEPARATOR.$itemName, $incomingPath);
      if ($treeWasCopied) list ($authorsWereRecorded, $treeEntries) = recordStorageAuthors($destinationSpace, $incomingPath);
      if ($treeWasCopied && placeStorageItem($incomingPath, $destinationPath, $CopiedName)) $CopyOutcome = 'copied';
      else {
        $authorsForgotten = forgetStorageAuthors((string)($destinationSpace['Authors'] ?? ''), $treeEntries);
        list ($treeWasRemoved, $namesRemoved, $removalIsJudged) = removeStorageTree($incomingPath);
        list ($targetKind, $targetPath) = storageItemKind($destinationPath, $CopiedName);
        if ($treeWasCopied && $targetKind !== 'missing') $CopyOutcome = 'exists';
        if (!$treeWasRemoved) warningEntry('A copy that did not finish could not be taken away from '.$incomingPath.'. The next sweep takes it away.'); }
      if ($linksLeftOut > 0) warningEntry($linksLeftOut.' link(s) were left out of a copy in the library '.printableText((string)($destinationSpace['Library'] ?? ''), TRUE).', because a link is never followed.'); } }
  if ($CopyOutcome !== 'copied') $CopiedName = '';
  $CopyIsJudged = ($CopyOutcome === 'copied') === ($CopiedName !== '');
  // / The arguments are purged apart from the locals below, because they are many.
  purgeSensitiveMemory($EnableMemoryProtection, $storageSettings, $storageAccount, $sourceSpace, $pathNames, $itemName, $destinationSpace, $destinationNames, $whenTaken);
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $itemKind, $folderPath, $itemFinding, $destinationPath, $destinationFinding, $incomingPath, $writeFinding, $targetKind, $targetPath, $destinationWasFound, $bytesWereMeasured, $writeAllowed, $treeWasCopied, $treeWasRemoved, $removalIsJudged, $authorsWereRecorded, $itemBytes, $linksLeftOut, $namesRemoved, $authorsForgotten, $treeEntries);
  return array($CopyOutcome, $CopiedName, $CopyIsJudged); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to say whether a string may name something in a trash.
// / Accepts the name. Returns TRUE when it may.
// / A trash entry is named for when it was deleted & sixteen hexadecimal digits, as storageEntryName() names it.
function trashIdIsUsable($trashId) {
  // / Set variables.
  global $EnableMemoryProtection;
  $IdIsUsable = FALSE;
  $IdIsUsable = (is_string($trashId) && preg_match('/^[0-9]{14}-[a-f0-9]{16}$/D', $trashId) === 1);
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $trashId);
  return $IdIsUsable; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to read what a trash entry says about what it holds.
// / Accepts the entry's folder. Returns whether it was read & the record, in that order.
// / The record names what was deleted, the folder it was in, whether it is a file or a folder & when it was deleted. A record that is
// / not whole, or names anything a request could not, is not read, so a damaged one never leads a restore anywhere it should not.
// / In a shared library it names the one account that added what was deleted, as Author, which is empty where none did alone.
function readTrashRecord($entryFolder) {
  // / Set variables.
  global $EnableMemoryProtection;
  $RecordWasRead = FALSE;
  $TrashRecord = array();
  $recordText = $folderName = '';
  $recordFields = array();
  $namesAreUsable = TRUE;
  $recordText = (string)@file_get_contents((string)$entryFolder.DIRECTORY_SEPARATOR.'Record.json', FALSE, NULL, 0, 65536);
  $recordFields = json_decode($recordText, TRUE);
  if (is_array($recordFields) && isset($recordFields['Name'], $recordFields['Folder'], $recordFields['Kind'], $recordFields['Deleted']) && is_array($recordFields['Folder']) && count($recordFields['Folder']) <= 32) {
    foreach ($recordFields['Folder'] as $folderName) if (!storageNameIsUsable($folderName)) $namesAreUsable = FALSE;
    if ($namesAreUsable && storageNameIsUsable($recordFields['Name']) && in_array($recordFields['Kind'], array('file', 'folder'), TRUE) && is_int($recordFields['Deleted'])) {
      $TrashRecord = array('Name' => (string)$recordFields['Name'], 'Folder' => array_values($recordFields['Folder']), 'Kind' => (string)$recordFields['Kind'], 'Deleted' => (int)$recordFields['Deleted'],
        'Author' => ((isset($recordFields['Author']) && is_string($recordFields['Author']) && preg_match('/^[a-f0-9]{18}$/D', $recordFields['Author']) === 1) ? $recordFields['Author'] : ''));
      $RecordWasRead = TRUE; } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $recordText, $folderName, $recordFields, $namesAreUsable, $entryFolder);
  return array($RecordWasRead, $TrashRecord); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to say whether the account a space is opened for may take one entry out of its trash for good.
// / Accepts the space & the entry's record. Returns TRUE when it may.
// / An account that may empty the trash may take out any entry. In a shared library, an account that may write it may take out an
// / entry it alone added, as the record says, so nobody deletes for good what somebody else added.
function trashRecordIsPurgeable($storageSpace, $trashRecord) {
  // / Set variables.
  global $EnableMemoryProtection;
  $RecordIsPurgeable = FALSE;
  $entryAuthor = '';
  $entryAuthor = (string)($trashRecord['Author'] ?? '');
  $RecordIsPurgeable = (!empty($storageSpace['MayWrite']) && (!empty($storageSpace['MayPurge']) or ($entryAuthor !== '' && $entryAuthor === (string)($storageSpace['Account'] ?? ''))));
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $entryAuthor, $storageSpace, $trashRecord);
  return $RecordIsPurgeable; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to move a file or a folder of a space to the space's trash.
// / Accepts the space, the folder's names & the name. Returns what happened, as an outcome a page names.
// / The trash entry is a folder named for when it was deleted, holding what was deleted as Item & a record saying where it was. It is
// / moved there by rename, which never copies, so a deleted folder of any size goes in an instant. trashed means it is in the trash.
// / In a shared library, the one account that added what was deleted is read once it is in the trash, where no request can add to it,
// / & the record is replaced whole with that account in it, so a record is never half written.
// / Returns a sanity check, because it is longer than 35 lines.
function trashStorageItem($storageSpace, $pathNames, $itemName) {
  // / Set variables.
  global $EnableMemoryProtection;
  $TrashOutcome = 'unavailable';
  $TrashIsJudged = FALSE;
  $itemKind = $folderPath = $itemFinding = $entryFolder = $recordText = $authorText = $itemAuthor = '';
  $recordFields = array();
  $recordWasWritten = $itemWasMoved = $authorIsJudged = $authorWasKept = FALSE;
  list ($itemKind, $folderPath, $itemFinding) = findStorageItem($storageSpace, $pathNames, $itemName);
  if (empty($storageSpace['MayWrite']) or (string)($storageSpace['Trash'] ?? '') === '') $TrashOutcome = 'read-only';
  else if ($itemFinding !== '') $TrashOutcome = $itemFinding;
  else {
    $entryFolder = $storageSpace['Trash'].DIRECTORY_SEPARATOR.storageEntryName();
    $recordFields = array('Name' => (string)$itemName, 'Folder' => array_values((array)$pathNames), 'Kind' => $itemKind, 'Deleted' => time(), 'Author' => '');
    $recordText = (string)json_encode($recordFields, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    if (@mkdir($entryFolder, 0700)) $recordWasWritten = (@file_put_contents($entryFolder.DIRECTORY_SEPARATOR.'Record.json', $recordText, LOCK_EX) === strlen($recordText));
    if ($recordWasWritten) $itemWasMoved = @rename($folderPath.DIRECTORY_SEPARATOR.$itemName, $entryFolder.DIRECTORY_SEPARATOR.'Item');
    if ($itemWasMoved) $TrashOutcome = 'trashed';
    else {
      @unlink($entryFolder.DIRECTORY_SEPARATOR.'Record.json');
      @rmdir($entryFolder);
      warningEntry('Something in the library '.printableText((string)($storageSpace['Library'] ?? ''), TRUE).' could not be moved to its trash, so it was left where it was. Something mounted inside a library cannot be moved there.'); }
    if ($itemWasMoved && (string)($storageSpace['Authors'] ?? '') !== '') list ($itemAuthor, $authorIsJudged) = readStorageAuthor($storageSpace, $entryFolder.DIRECTORY_SEPARATOR.'Item');
    if ($itemAuthor !== '' && $authorIsJudged) {
      $recordFields['Author'] = $itemAuthor;
      $authorText = (string)json_encode($recordFields, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
      if (@file_put_contents($entryFolder.DIRECTORY_SEPARATOR.'Record.part', $authorText, LOCK_EX) === strlen($authorText) && @rename($entryFolder.DIRECTORY_SEPARATOR.'Record.part', $entryFolder.DIRECTORY_SEPARATOR.'Record.json')) $authorWasKept = TRUE;
      else @unlink($entryFolder.DIRECTORY_SEPARATOR.'Record.part'); }
    if ($itemAuthor !== '' && !$authorWasKept) warningEntry('The trash of the library '.printableText((string)($storageSpace['Library'] ?? ''), TRUE).' could not record who added something deleted there, so only an administrator can delete it forever.'); }
  $TrashIsJudged = (($TrashOutcome === 'trashed') === $itemWasMoved);
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $itemKind, $folderPath, $itemFinding, $entryFolder, $recordText, $authorText, $itemAuthor, $recordFields, $recordWasWritten, $itemWasMoved, $authorIsJudged, $authorWasKept, $storageSpace, $pathNames, $itemName);
  return array($TrashOutcome, $TrashIsJudged); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to list what is in a space's trash.
// / Accepts the space. Returns whether it was listed & its entries, newest first, in that order.
// / Each entry carries its Id, what was deleted, the folder it was in, joined by slashes, whether it is a file or a folder, its size
// / where it is a file, when it was deleted & whether the account may take it out for good. An entry whose record cannot be read is
// / left out, & the sweep takes it away in time.
function listStorageTrash($storageSpace) {
  // / Set variables.
  global $EnableMemoryProtection;
  $TrashWasListed = FALSE;
  $TrashEntries = array();
  // / The names are what scandir() hands back, which is FALSE until a trash is read, so a space without one lists nothing.
  $trashNames = FALSE;
  $trashRecord = $itemStatus = array();
  $trashId = '';
  $recordWasRead = FALSE;
  // / An entry is named for when it was deleted, so a listing read backwards is newest first, & the newest 10,000 are listed.
  if ((string)($storageSpace['Trash'] ?? '') !== '') $trashNames = @scandir($storageSpace['Trash'], SCANDIR_SORT_DESCENDING);
  if (is_array($trashNames)) {
    foreach ($trashNames as $trashId) {
      if (!trashIdIsUsable($trashId) or count($TrashEntries) >= 10000) continue;
      list ($recordWasRead, $trashRecord) = readTrashRecord($storageSpace['Trash'].DIRECTORY_SEPARATOR.$trashId);
      if (!$recordWasRead) continue;
      redeclare($itemStatus, @lstat($storageSpace['Trash'].DIRECTORY_SEPARATOR.$trashId.DIRECTORY_SEPARATOR.'Item'));
      $TrashEntries[] = array('Id' => $trashId, 'Name' => $trashRecord['Name'], 'Folder' => implode('/', $trashRecord['Folder']), 'Kind' => $trashRecord['Kind'],
        'Size' => (($trashRecord['Kind'] === 'file' && is_array($itemStatus)) ? (int)$itemStatus['size'] : 0), 'Deleted' => $trashRecord['Deleted'],
        'Purgeable' => trashRecordIsPurgeable($storageSpace, $trashRecord)); }
    $TrashWasListed = TRUE; }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $trashNames, $trashRecord, $itemStatus, $trashId, $recordWasRead, $storageSpace);
  return array($TrashWasListed, $TrashEntries); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to put something in a space's trash back where it was.
// / Accepts the space & the entry's Id. Returns what happened, the name it was put back under & the folder it was put back in, in
// / that order. It goes back to the folder it was deleted from where that is still a folder, & to the top of the space where it is
// / not. A name that is taken there by now is kept beside it with a number, so nothing is ever replaced. restored means it is back.
// / Returns a sanity check, because it is longer than 35 lines.
function restoreStorageItem($storageSpace, $trashId) {
  // / Set variables.
  global $EnableMemoryProtection;
  $RestoreOutcome = 'unavailable';
  $RestoredName = $RestoredFolder = '';
  $RestoreIsJudged = FALSE;
  $entryFolder = $folderPath = $folderFinding = $itemKind = $itemPath = $topPath = $topFinding = '';
  $trashRecord = $folderNames = array();
  $recordWasRead = $folderWasFound = $topWasFound = FALSE;
  if (empty($storageSpace['MayWrite']) or (string)($storageSpace['Trash'] ?? '') === '') $RestoreOutcome = 'read-only';
  else if (!trashIdIsUsable($trashId)) $RestoreOutcome = 'missing';
  else {
    $entryFolder = $storageSpace['Trash'].DIRECTORY_SEPARATOR.$trashId;
    list ($recordWasRead, $trashRecord) = readTrashRecord($entryFolder);
    if ($recordWasRead) list ($itemKind, $itemPath) = storageItemKind($entryFolder, 'Item');
    if (!$recordWasRead or ($itemKind !== 'file' && $itemKind !== 'folder')) $RestoreOutcome = 'missing';
    else {
      list ($folderWasFound, $folderPath, $folderFinding) = resolveStorageFolder($storageSpace, $trashRecord['Folder']);
      list ($topWasFound, $topPath, $topFinding) = resolveStorageFolder($storageSpace, array());
      $folderNames = $folderWasFound ? $trashRecord['Folder'] : array();
      if (!$folderWasFound) $folderPath = $topPath;
      if (($folderWasFound or $topWasFound) && !storageNameIsKept($storageSpace, $folderNames, $trashRecord['Name'])) $RestoredName = freeStorageName($folderPath, $trashRecord['Name']);
      if ($RestoredName !== '' && placeStorageItem($itemPath, $folderPath, $RestoredName)) {
        @unlink($entryFolder.DIRECTORY_SEPARATOR.'Record.json');
        @rmdir($entryFolder);
        $RestoredFolder = implode('/', $folderNames);
        $RestoreOutcome = 'restored'; } } }
  if ($RestoreOutcome !== 'restored') {
    $RestoredName = '';
    $RestoredFolder = ''; }
  $RestoreIsJudged = (($RestoreOutcome === 'restored') === ($RestoredName !== ''));
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $entryFolder, $folderPath, $folderFinding, $itemKind, $itemPath, $topPath, $topFinding, $trashRecord, $folderNames, $recordWasRead, $folderWasFound, $topWasFound, $storageSpace, $trashId);
  return array($RestoreOutcome, $RestoredName, $RestoredFolder, $RestoreIsJudged); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to take something out of a space's trash for good, or everything in it.
// / Accepts the space & the entry's Id, or an empty Id for every entry. Returns what happened & how many entries were taken away, in
// / that order. purged means the entry is gone, & emptied that every entry is. Nothing taken away this way can be put back.
// / Only an account that may empty the trash empties it, & takes any entry out. In a shared library a user who may write it takes
// / out only an entry its record says they alone added, & is answered not-allowed for any other, as for emptying it.
function purgeStorageTrash($storageSpace, $trashId) {
  // / Set variables.
  global $EnableMemoryProtection;
  $PurgeOutcome = 'unavailable';
  $EntriesPurged = 0;
  $trashNames = $trashRecord = array();
  $entryId = $entryKind = $entryPath = '';
  $treeWasRemoved = $removalIsJudged = $purgeFailed = $recordWasRead = $mayEmpty = FALSE;
  $mayEmpty = !empty($storageSpace['MayPurge']);
  if ((string)$trashId !== '' && !$mayEmpty && trashIdIsUsable($trashId) && (string)($storageSpace['Trash'] ?? '') !== '') list ($recordWasRead, $trashRecord) = readTrashRecord($storageSpace['Trash'].DIRECTORY_SEPARATOR.$trashId);
  if (empty($storageSpace['MayWrite']) or (string)($storageSpace['Trash'] ?? '') === '') $PurgeOutcome = 'read-only';
  else if ((string)$trashId === '' && !$mayEmpty) $PurgeOutcome = 'not-allowed';
  else if ((string)$trashId !== '' && (!trashIdIsUsable($trashId) or (!$mayEmpty && !$recordWasRead))) $PurgeOutcome = 'missing';
  else if ((string)$trashId !== '' && !$mayEmpty && !trashRecordIsPurgeable($storageSpace, $trashRecord)) $PurgeOutcome = 'not-allowed';
  else {
    $trashNames = ((string)$trashId === '') ? @scandir($storageSpace['Trash']) : array((string)$trashId);
    if (is_array($trashNames)) foreach ($trashNames as $entryId) {
      if (!trashIdIsUsable($entryId)) continue;
      list ($entryKind, $entryPath) = storageItemKind($storageSpace['Trash'], $entryId);
      if ($entryKind !== 'folder') continue;
      list ($treeWasRemoved, $removalIsJudged) = takeAwayStorageTree($entryPath, (string)($storageSpace['Authors'] ?? ''));
      if ($treeWasRemoved) $EntriesPurged++;
      else $purgeFailed = TRUE; }
    if ($purgeFailed) warningEntry('Something in the trash of the library '.printableText((string)($storageSpace['Library'] ?? ''), TRUE).' could not be taken away.');
    else if ((string)$trashId === '') $PurgeOutcome = 'emptied';
    else if ($EntriesPurged === 1) $PurgeOutcome = 'purged';
    else $PurgeOutcome = 'missing'; }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $trashNames, $trashRecord, $entryId, $entryKind, $entryPath, $treeWasRemoved, $removalIsJudged, $purgeFailed, $recordWasRead, $mayEmpty, $storageSpace, $trashId);
  return array($PurgeOutcome, $EntriesPurged); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to take away what is old in one trash & one incoming folder.
// / Accepts the trash, the incoming folder & the days a trash keeps what it holds, 0 for ever. Returns whether nothing that was due
// / was left & how many entries were taken away, in that order.
// / A trash entry is due once it was deleted longer ago than the days, judged by its record, & by its folder where the record cannot
// / be read. Something waiting in the incoming folder for a day is an upload or a copy that never finished, & is taken away too.
// / A shared library keeps Authors beside its trash & its incoming folder, & what is taken away from either takes its records along.
function sweepStorageFolders($trashFolder, $incomingFolder, $trashDays) {
  // / Set variables.
  global $EnableMemoryProtection;
  $SweepCompleted = TRUE;
  $EntriesRemoved = 0;
  $entryNames = $trashRecord = array();
  $entryName = $entryPath = $authorsFolder = '';
  $recordWasRead = $treeWasRemoved = $removalIsJudged = FALSE;
  $deletedTime = 0;
  if ((string)$trashFolder !== '') $authorsFolder = dirname((string)$trashFolder).DIRECTORY_SEPARATOR.'Authors';
  if ($authorsFolder !== '' && (!is_dir($authorsFolder) or is_link($authorsFolder))) $authorsFolder = '';
  if ((int)$trashDays > 0 && (string)$trashFolder !== '' && is_dir($trashFolder) && !is_link($trashFolder)) $entryNames = @scandir($trashFolder);
  if (is_array($entryNames)) foreach ($entryNames as $entryName) {
    if (!trashIdIsUsable($entryName)) continue;
    redeclare($entryPath, $trashFolder.DIRECTORY_SEPARATOR.$entryName);
    list ($recordWasRead, $trashRecord) = readTrashRecord($entryPath);
    redeclare($deletedTime, $recordWasRead ? (int)$trashRecord['Deleted'] : (int)@filemtime($entryPath));
    if ($deletedTime + (int)$trashDays * 86400 > time()) continue;
    list ($treeWasRemoved, $removalIsJudged) = takeAwayStorageTree($entryPath, $authorsFolder);
    if ($treeWasRemoved) $EntriesRemoved++;
    else $SweepCompleted = FALSE; }
  redeclare($entryNames, ((string)$incomingFolder !== '' && is_dir($incomingFolder) && !is_link($incomingFolder)) ? @scandir($incomingFolder) : array());
  if (is_array($entryNames)) foreach ($entryNames as $entryName) {
    if ($entryName === '.' or $entryName === '..') continue;
    redeclare($entryPath, $incomingFolder.DIRECTORY_SEPARATOR.$entryName);
    if ((int)@filemtime($entryPath) + 86400 > time()) continue;
    list ($treeWasRemoved, $removalIsJudged) = takeAwayStorageTree($entryPath, $authorsFolder);
    if ($treeWasRemoved) $EntriesRemoved++;
    else $SweepCompleted = FALSE; }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $entryNames, $trashRecord, $entryName, $entryPath, $authorsFolder, $recordWasRead, $treeWasRemoved, $removalIsJudged, $deletedTime, $trashFolder, $incomingFolder, $trashDays);
  return array($SweepCompleted, $EntriesRemoved); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to sweep the trash & the incoming folder of every space in every library.
// / Accepts the settings. Returns whether nothing that was due was left & how many entries were taken away, in that order.
// / A personal library holds one space for each account folder in it, & a shared library one space at its top.
function sweepStorage($storageSettings) {
  // / Set variables.
  global $EnableMemoryProtection;
  $SweepCompleted = TRUE;
  $EntriesRemoved = 0;
  $libraryRecord = $spaceNames = $sweptFolders = $sweptFolder = array();
  $libraryName = $spaceName = $systemFolder = '';
  $foldersWereSwept = FALSE;
  $entriesSwept = 0;
  foreach ($storageSettings['Libraries'] as $libraryName => $libraryRecord) {
    if (!is_dir($libraryRecord['Root']) or is_link($libraryRecord['Root'])) continue;
    redeclare($systemFolder, $libraryRecord['Root'].DIRECTORY_SEPARATOR.storageKeptName());
    if ($libraryRecord['Kind'] === 'shared') $sweptFolders[] = array($systemFolder.DIRECTORY_SEPARATOR.'Trash', $systemFolder.DIRECTORY_SEPARATOR.'Incoming');
    else {
      redeclare($spaceNames, @scandir($libraryRecord['Root']));
      if (is_array($spaceNames)) foreach ($spaceNames as $spaceName) if (preg_match('/^[a-f0-9]{18}$/D', (string)$spaceName) === 1)
        $sweptFolders[] = array($libraryRecord['Root'].DIRECTORY_SEPARATOR.$spaceName.DIRECTORY_SEPARATOR.'Trash', $libraryRecord['Root'].DIRECTORY_SEPARATOR.$spaceName.DIRECTORY_SEPARATOR.'Incoming'); } }
  foreach ($sweptFolders as $sweptFolder) {
    list ($foldersWereSwept, $entriesSwept) = sweepStorageFolders($sweptFolder[0], $sweptFolder[1], $storageSettings['TrashDays']);
    $EntriesRemoved += $entriesSwept;
    if (!$foldersWereSwept) $SweepCompleted = FALSE; }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $libraryRecord, $spaceNames, $sweptFolders, $sweptFolder, $libraryName, $spaceName, $systemFolder, $foldersWereSwept, $entriesSwept, $storageSettings);
  return array($SweepCompleted, $EntriesRemoved); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to sweep every library, where a sweep is due.
// / Accepts the fewest seconds since the last sweep, 0 for a sweep now. Returns whether a sweep ran & how many entries it took away,
// / in that order. The time of the last sweep is a file in Libraries, & a sweep holds a lock on it, so two requests never sweep at once.
// / Whether one is due is decided before the settings are read, so a request that does not sweep reads nothing.
// / Only the web server account sweeps, because the libraries are that account's. The Engine decides that before it calls this.
function sweepStorageIfDue($sweepInterval) {
  // / Set variables.
  global $EnableMemoryProtection;
  $SweepRan = FALSE;
  $EntriesRemoved = 0;
  $storageSettings = array();
  $settingsAreWhole = $sweepCompleted = FALSE;
  $librariesFolder = $markerPath = '';
  $markerHandle = NULL;
  $librariesFolder = rtrim((string)applicationSetting('LibraryLocation', ''), DIRECTORY_SEPARATOR);
  $markerPath = $librariesFolder.DIRECTORY_SEPARATOR.'swept';
  clearstatcache(TRUE, $markerPath);
  if ($librariesFolder !== '' && is_dir($librariesFolder) && !is_link($librariesFolder) && !is_link($markerPath) && (!file_exists($markerPath) or (int)@filemtime($markerPath) + (int)$sweepInterval <= time())) $markerHandle = @fopen($markerPath, 'c');
  if (is_resource($markerHandle)) {
    if (flock($markerHandle, LOCK_EX | LOCK_NB)) {
      @touch($markerPath);
      list ($storageSettings, $settingsAreWhole) = storageSettings(TRUE);
      if ($settingsAreWhole) list ($sweepCompleted, $EntriesRemoved) = sweepStorage($storageSettings);
      $SweepRan = $settingsAreWhole;
      if ($settingsAreWhole && !$sweepCompleted) warningEntry('A sweep of the libraries did not finish, & what it left is swept next time.');
      else if ($EntriesRemoved > 0) logEntry('Swept '.$EntriesRemoved.' entry or entries from the trash & the unfinished uploads of every library.');
      flock($markerHandle, LOCK_UN); }
    fclose($markerHandle); }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $storageSettings, $settingsAreWhole, $sweepCompleted, $librariesFolder, $markerPath, $markerHandle, $sweepInterval);
  return array($SweepRan, $EntriesRemoved); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to take away an account's space in every personal library, once the account is gone.
// / Accepts the account's folder, its random identifier. Returns TRUE when no space of it is left.
// / The Engine's removeAccountLibraries() calls it as an administrator removes the account, beside the account's folder in Users.
// / A library that was taken off the list StorageLibraries names is not reached, & what an account kept there stays where it is.
function removeAccountStorage($accountFolder) {
  // / Set variables.
  global $EnableMemoryProtection;
  $StorageWasRemoved = TRUE;
  $storageSettings = $libraryRecord = array();
  $settingsAreWhole = $treeWasRemoved = $removalIsJudged = FALSE;
  $libraryName = '';
  $namesRemoved = 0;
  list ($storageSettings, $settingsAreWhole) = storageSettings(TRUE);
  if (preg_match('/^[a-f0-9]{18}$/D', (string)$accountFolder) !== 1) $StorageWasRemoved = FALSE;
  else foreach ($storageSettings['Libraries'] as $libraryName => $libraryRecord) {
    if ($libraryRecord['Kind'] !== 'personal') continue;
    list ($treeWasRemoved, $namesRemoved, $removalIsJudged) = removeStorageTree($libraryRecord['Root'].DIRECTORY_SEPARATOR.$accountFolder);
    if (!$treeWasRemoved) {
      $StorageWasRemoved = FALSE;
      warningEntry('The space of a removed account in the library '.$libraryName.' could not be taken away from '.$libraryRecord['Root'].'.'); } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $storageSettings, $libraryRecord, $settingsAreWhole, $treeWasRemoved, $removalIsJudged, $libraryName, $namesRemoved, $accountFolder);
  return $StorageWasRemoved; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to read one of this core's own fields from a request.
// / Accepts the field's name & its kind. Returns the value & whether it is one this core may use, in that order.
// / An action, a library, a trash entry & what to do where a name is taken are sanitized, & refused when sanitizing changed them,
// / so a value is never quietly altered. A name & a folder are what a user wrote, which sanitize() would change, so each is checked
// / whole by storageNameIsUsable() instead, as the Authentication Core checks a password, & a folder is handed back as the names it is
// / made of. A folder that is not sent is the top of the tree. A field that is sent as anything but text of up to 4096 bytes is never used.
function storageInput($fieldName, $fieldKind) {
  // / Set variables.
  global $EnableMemoryProtection;
  $InputValue = '';
  $InputIsUsable = FALSE;
  $rawValue = $cleanValue = '';
  $valueWasCleaned = $pathIsUsable = $fieldIsMalformed = FALSE;
  $pathNames = array();
  $storageActions = array('libraries', 'list', 'folder', 'upload', 'download', 'rename', 'move', 'copy', 'delete', 'trash', 'restore', 'purge', 'empty');
  if (isset($_POST[$fieldName]) && is_string($_POST[$fieldName]) && strlen($_POST[$fieldName]) <= 4096) $rawValue = (string)$_POST[$fieldName];
  else if (isset($_POST[$fieldName])) $fieldIsMalformed = TRUE;
  if ($fieldIsMalformed) $InputIsUsable = FALSE;
  else if ($fieldKind === 'folder') {
    list ($pathIsUsable, $pathNames) = storagePathNames($rawValue);
    if ($pathIsUsable) {
      $InputValue = $pathNames;
      $InputIsUsable = TRUE; } }
  else if ($fieldKind === 'name') {
    if (storageNameIsUsable($rawValue)) {
      $InputValue = $rawValue;
      $InputIsUsable = TRUE; } }
  else if ($rawValue !== '') {
    list ($cleanValue, $valueWasCleaned) = sanitize($rawValue, TRUE);
    if ($valueWasCleaned && $cleanValue === $rawValue) {
      if ($fieldKind === 'action' && in_array($cleanValue, $storageActions, TRUE)) $InputIsUsable = TRUE;
      else if ($fieldKind === 'library' && libraryNameIsUsable($cleanValue)) $InputIsUsable = TRUE;
      else if ($fieldKind === 'trash-id' && trashIdIsUsable($cleanValue)) $InputIsUsable = TRUE;
      else if ($fieldKind === 'when-taken' && in_array($cleanValue, array('refuse', 'keep'), TRUE)) $InputIsUsable = TRUE;
      if ($InputIsUsable) $InputValue = $cleanValue; } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $rawValue, $cleanValue, $valueWasCleaned, $pathIsUsable, $fieldIsMalformed, $pathNames, $storageActions, $fieldName, $fieldKind);
  return array($InputValue, $InputIsUsable); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to read the files a page uploaded.
// / Accepts nothing. Returns each file as its Name, the Path PHP keeps it at until the request ends, its Error & its Bytes.
// / A page uploads them as StorageUpload, one or many, & at most 100 are read.
function storageUploads() {
  // / Set variables.
  global $EnableMemoryProtection;
  $UploadedFiles = array();
  $uploadField = array();
  $fileIndex = 0;
  if (isset($_FILES['StorageUpload']) && is_array($_FILES['StorageUpload'])) $uploadField = $_FILES['StorageUpload'];
  if (isset($uploadField['name']) && is_array($uploadField['name'])) foreach (array_keys($uploadField['name']) as $fileIndex) {
    if (count($UploadedFiles) >= 100) break;
    if (is_array($uploadField['name'][$fileIndex])) continue;
    $UploadedFiles[] = array('Name' => (string)($uploadField['name'][$fileIndex] ?? ''), 'Path' => (string)($uploadField['tmp_name'][$fileIndex] ?? ''),
      'Error' => (int)($uploadField['error'][$fileIndex] ?? UPLOAD_ERR_NO_FILE), 'Bytes' => (int)($uploadField['size'][$fileIndex] ?? 0)); }
  else if (isset($uploadField['name']) && is_string($uploadField['name'])) $UploadedFiles[] = array('Name' => (string)$uploadField['name'], 'Path' => (string)($uploadField['tmp_name'] ?? ''),
    'Error' => (int)($uploadField['error'] ?? UPLOAD_ERR_NO_FILE), 'Bytes' => (int)($uploadField['size'] ?? 0));
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $uploadField, $fileIndex);
  return $UploadedFiles; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to read a size PHP's configuration writes, such as 5000M, in bytes.
// / Accepts the setting's name. Returns the bytes, or 0 where the setting says nothing a size can be read from or sets no limit.
function storageIniBytes($settingName) {
  // / Set variables.
  global $EnableMemoryProtection;
  $SettingBytes = 0;
  $settingText = '';
  $sizeMatches = array();
  $unitBytes = array('' => 1, 'k' => 1024, 'm' => 1048576, 'g' => 1073741824);
  $settingText = trim((string)ini_get((string)$settingName));
  if (preg_match('/^([0-9]{1,12})\s*([KMGkmg]?)$/D', $settingText, $sizeMatches) === 1) $SettingBytes = (int)$sizeMatches[1] * (int)$unitBytes[strtolower($sizeMatches[2])];
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $settingText, $sizeMatches, $unitBytes, $settingName);
  return $SettingBytes; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to say how large a file a page may upload in one request.
// / Accepts nothing. Returns the bytes, or 0 where PHP sets no limit.
// / It is the smaller of upload_max_filesize & post_max_size, & a setting of 0 sets no limit of its own.
function storageUploadLimit() {
  // / Set variables.
  global $EnableMemoryProtection;
  $UploadLimit = 0;
  $fileLimit = $postLimit = 0;
  $fileLimit = storageIniBytes('upload_max_filesize');
  $postLimit = storageIniBytes('post_max_size');
  if ($fileLimit > 0 && $postLimit > 0) $UploadLimit = min($fileLimit, $postLimit);
  else $UploadLimit = max($fileLimit, $postLimit);
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $fileLimit, $postLimit);
  return $UploadLimit; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to list the libraries a signed in account may open.
// / Accepts the settings & the account. Returns each library's Name, its Kind & whether the account may write it.
// / Every library is listed, because every signed in account has a space in each personal one & reads every shared one.
function librariesForAccount($storageSettings, $storageAccount) {
  // / Set variables.
  global $EnableMemoryProtection;
  $AccountLibraries = array();
  $libraryRecord = array();
  $libraryName = '';
  foreach ($storageSettings['Libraries'] as $libraryName => $libraryRecord) $AccountLibraries[] = array('Name' => (string)$libraryName, 'Kind' => $libraryRecord['Kind'],
    'Writable' => libraryIsWritableBy($libraryRecord, $storageAccount), 'Purgeable' => libraryIsPurgeableBy($libraryRecord, $storageAccount));
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $libraryRecord, $libraryName, $storageSettings, $storageAccount);
  return $AccountLibraries; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to carry out one of the actions a page asks a library for.
// / Accepts the settings, the action & the signed in account. Returns the reply, whether a file was sent in its place, & a sanity
// / check, in that order. The reply names the outcome & carries what the action found.
// / libraries lists the libraries. list lists a folder. folder makes one. upload keeps uploaded files. download sends a file & answers
// / nothing else where it was sent. rename, move & copy do what they say. delete moves to the trash. trash lists it, restore puts back
// / & purge takes away one entry, & empty takes away every one. A field an action needs that cannot be used is answered as the
// / matching failure, without anything being tried.
// / Returns a sanity check, because it is longer than 35 lines.
function answerStorageAction($storageSettings, $storageAction, $storageAccount) {
  // / Set variables.
  global $EnableMemoryProtection;
  $StorageReply = array('Outcome' => 'unavailable');
  $FileWasSent = $ReplyIsJudged = FALSE;
  $storageSpace = $destinationSpace = $pathNames = $destinationNames = $folderEntries = $storedNames = $refusedNames = $trashEntries = array();
  $libraryName = $destinationLibrary = $itemName = $newName = $trashId = $whenTaken = $spaceFinding = $destinationFinding = $listFinding = $sendFinding = $outcomeText = $resultName = $resultFolder = '';
  $libraryIsUsable = $folderIsUsable = $nameIsUsable = $newNameIsUsable = $idIsUsable = $whenIsUsable = $destinationLibraryIsUsable = $destinationIsUsable = FALSE;
  $spaceIsReady = $destinationIsReady = $folderWasListed = $entriesWereCut = $trashWasListed = $actionIsJudged = FALSE;
  $entriesPurged = 0;
  list ($libraryName, $libraryIsUsable) = storageInput('StorageLibrary', 'library');
  list ($pathNames, $folderIsUsable) = storageInput('StorageFolder', 'folder');
  list ($itemName, $nameIsUsable) = storageInput('StorageName', 'name');
  list ($newName, $newNameIsUsable) = storageInput('StorageNewName', 'name');
  list ($trashId, $idIsUsable) = storageInput('StorageTrashId', 'trash-id');
  list ($whenTaken, $whenIsUsable) = storageInput('StorageWhenTaken', 'when-taken');
  list ($destinationLibrary, $destinationLibraryIsUsable) = storageInput('StorageDestinationLibrary', 'library');
  list ($destinationNames, $destinationIsUsable) = storageInput('StorageDestinationFolder', 'folder');
  if (!$whenIsUsable) $whenTaken = 'refuse';
  if (!$destinationLibraryIsUsable) $destinationLibrary = $libraryName;
  if ($storageAction === 'libraries') $StorageReply = array('Outcome' => 'libraries', 'Libraries' => librariesForAccount($storageSettings, $storageAccount),
    'UploadLimit' => storageUploadLimit(), 'TrashDays' => (int)$storageSettings['TrashDays']);
  else if (!$libraryIsUsable) $StorageReply = array('Outcome' => 'library-unknown');
  else if (!$folderIsUsable) $StorageReply = array('Outcome' => 'folder-invalid');
  else {
    list ($spaceIsReady, $storageSpace, $spaceFinding) = storageSpace($storageSettings, $libraryName, $storageAccount);
    // / Every action below acts on a space, so a space that is not ready answers what was wrong with it, & nothing else is tried.
    if (!$spaceIsReady) $StorageReply = array('Outcome' => $spaceFinding);
    else if ($storageAction === 'list') {
      list ($folderWasListed, $folderEntries, $entriesWereCut, $listFinding) = listStorageFolder($storageSpace, $pathNames);
      $StorageReply = $folderWasListed ? array('Outcome' => 'listed', 'Entries' => $folderEntries, 'Cut' => $entriesWereCut, 'Writable' => !empty($storageSpace['MayWrite']), 'Purgeable' => !empty($storageSpace['MayPurge'])) : array('Outcome' => $listFinding); }
    else if ($storageAction === 'folder') $StorageReply = array('Outcome' => ($newNameIsUsable ? makeStorageSubfolder($storageSpace, $pathNames, $newName) : 'name-invalid'));
    else if ($storageAction === 'upload') {
      list ($outcomeText, $storedNames, $refusedNames) = storeUploadedFiles($storageSettings, $storageSpace, $storageAccount, $pathNames, storageUploads(), $whenTaken);
      $StorageReply = array('Outcome' => $outcomeText, 'Stored' => $storedNames, 'Refused' => $refusedNames); }
    else if ($storageAction === 'download') {
      list ($FileWasSent, $sendFinding, $actionIsJudged) = $nameIsUsable ? sendStorageFile($storageSpace, $pathNames, $itemName, (string)($_SERVER['HTTP_RANGE'] ?? '')) : array(FALSE, 'name-invalid', TRUE);
      $StorageReply = array('Outcome' => ($FileWasSent ? 'sent' : $sendFinding)); }
    else if ($storageAction === 'rename') $StorageReply = array('Outcome' => (($nameIsUsable && $newNameIsUsable) ? renameStorageItem($storageSpace, $pathNames, $itemName, $newName) : 'name-invalid'));
    else if ($storageAction === 'move') $StorageReply = array('Outcome' => (!$nameIsUsable ? 'name-invalid' : ($destinationIsUsable ? moveStorageItem($storageSpace, $pathNames, $itemName, $destinationNames) : 'folder-invalid')));
    else if ($storageAction === 'copy') {
      if ($nameIsUsable && $destinationIsUsable) list ($destinationIsReady, $destinationSpace, $destinationFinding) = storageSpace($storageSettings, $destinationLibrary, $storageAccount);
      if (!$nameIsUsable) $StorageReply = array('Outcome' => 'name-invalid');
      else if (!$destinationIsUsable) $StorageReply = array('Outcome' => 'folder-invalid');
      else if (!$destinationIsReady) $StorageReply = array('Outcome' => $destinationFinding);
      else {
        list ($outcomeText, $resultName, $actionIsJudged) = copyStorageItem($storageSettings, $storageAccount, $storageSpace, $pathNames, $itemName, $destinationSpace, $destinationNames, $whenTaken);
        $StorageReply = array('Outcome' => $outcomeText, 'Name' => $resultName); } }
    else if ($storageAction === 'delete') {
      list ($outcomeText, $actionIsJudged) = $nameIsUsable ? trashStorageItem($storageSpace, $pathNames, $itemName) : array('name-invalid', TRUE);
      $StorageReply = array('Outcome' => $outcomeText); }
    else if ($storageAction === 'trash') {
      list ($trashWasListed, $trashEntries) = listStorageTrash($storageSpace);
      $StorageReply = $trashWasListed ? array('Outcome' => 'trash-listed', 'Entries' => $trashEntries, 'Writable' => !empty($storageSpace['MayWrite']), 'Purgeable' => !empty($storageSpace['MayPurge'])) : array('Outcome' => ((string)$storageSpace['Trash'] === '' ? 'read-only' : 'unavailable')); }
    else if ($storageAction === 'restore') {
      list ($outcomeText, $resultName, $resultFolder, $actionIsJudged) = $idIsUsable ? restoreStorageItem($storageSpace, $trashId) : array('missing', '', '', TRUE);
      $StorageReply = array('Outcome' => $outcomeText, 'Name' => $resultName, 'Folder' => $resultFolder); }
    else if ($storageAction === 'purge') {
      list ($outcomeText, $entriesPurged) = $idIsUsable ? purgeStorageTrash($storageSpace, $trashId) : array('missing', 0);
      $StorageReply = array('Outcome' => $outcomeText); }
    else if ($storageAction === 'empty') {
      list ($outcomeText, $entriesPurged) = purgeStorageTrash($storageSpace, '');
      $StorageReply = array('Outcome' => $outcomeText, 'Count' => $entriesPurged); } }
  $ReplyIsJudged = (isset($StorageReply['Outcome']) && is_string($StorageReply['Outcome']) && $StorageReply['Outcome'] !== '');
  // / The flags & the arguments are purged apart from the locals below, because they are many.
  purgeSensitiveMemory($EnableMemoryProtection, $libraryIsUsable, $folderIsUsable, $nameIsUsable, $newNameIsUsable, $idIsUsable, $whenIsUsable, $destinationLibraryIsUsable, $destinationIsUsable, $spaceIsReady, $destinationIsReady, $folderWasListed, $entriesWereCut, $trashWasListed, $actionIsJudged, $entriesPurged, $storageSettings, $storageAction, $storageAccount);
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $storageSpace, $destinationSpace, $pathNames, $destinationNames, $folderEntries, $storedNames, $refusedNames, $trashEntries, $libraryName, $destinationLibrary, $itemName, $newName, $trashId, $whenTaken, $spaceFinding, $destinationFinding, $listFinding, $sendFinding, $outcomeText, $resultName, $resultFolder);
  return array($StorageReply, $FileWasSent, $ReplyIsJudged); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to write a library's answer for the page that asked.
// / Accepts the reply. Returns TRUE when it was written.
// / The answer is one line of JSON under Storage, as the Authentication Core's is under SignIn, & is never cached.
// / The characters HTML gives meaning to are escaped, so the line is inert if anything shows it as a page, as a download's frame does.
function printStorageReply($storageReply) {
  // / Set variables.
  global $EnableMemoryProtection;
  $ReplyWasPrinted = FALSE;
  $encodedReply = '';
  $encodedReply = (string)json_encode(array('Storage' => $storageReply), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_INVALID_UTF8_SUBSTITUTE | JSON_PARTIAL_OUTPUT_ON_ERROR);
  if ($encodedReply !== '') {
    if (!headers_sent()) header('Cache-Control: no-store');
    print($encodedReply.PHP_EOL);
    $ReplyWasPrinted = TRUE; }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $encodedReply, $storageReply);
  return $ReplyWasPrinted; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to answer a request that asks a library for something, where it does.
// / Accepts nothing. Returns answered where the request asked & was answered, & an empty string where it did not ask.
// / A request asks by sending StorageAction. Only a signed in visitor is answered with anything but signed-out, & only the web server
// / account answers, because the libraries are that account's. A visitor is never handed a page in place of an answer, so a script
// / that asked can always read what happened.
// / An action whose sanity check fails is answered unavailable, & that is a warning.
function answerStorageRequest() {
  // / Set variables.
  global $SignedInAccount, $CurrentUser, $ApacheUser, $RunningFromCLI, $EnableMemoryProtection;
  $EngineAnswer = '';
  $storageSettings = $storageReply = array();
  $storageAction = '';
  $actionIsUsable = $settingsAreWhole = $fileWasSent = $replyIsJudged = FALSE;
  if (isset($_POST['StorageAction']) && $RunningFromCLI !== TRUE && (string)$CurrentUser === (string)$ApacheUser) {
    list ($storageAction, $actionIsUsable) = storageInput('StorageAction', 'action');
    if (!$actionIsUsable) $storageReply = array('Outcome' => 'action-invalid');
    else if (!is_array($SignedInAccount) or !isset($SignedInAccount['Folder']) or (string)$SignedInAccount['Folder'] === '') $storageReply = array('Outcome' => 'signed-out');
    else {
      list ($storageSettings, $settingsAreWhole) = storageSettings(TRUE);
      if ($settingsAreWhole) list ($storageReply, $fileWasSent, $replyIsJudged) = answerStorageAction($storageSettings, $storageAction, $SignedInAccount);
      if (!$settingsAreWhole) $storageReply = array('Outcome' => 'unavailable');
      else if (!$replyIsJudged) {
        $storageReply = array('Outcome' => 'unavailable');
        warningEntry('A library answered '.printableText((string)$storageAction, TRUE).' with nothing a page can read, so it was answered unavailable.'); } }
    if ($fileWasSent or printStorageReply($storageReply)) $EngineAnswer = 'answered'; }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $storageSettings, $storageReply, $storageAction, $actionIsUsable, $settingsAreWhole, $fileWasSent, $replyIsJudged);
  return $EngineAnswer; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to describe every library an administrator listed, for the report -v prints.
// / Accepts nothing. Returns the report's sections, each a list of rows with a label, a text & whether the row failed.
// / A library is OK where its folder is there & is the web server account's, & is named as made on first use where it is kept in
// / the data location & no account has used it yet. A shared library nobody writes need only be there.
// / A library that cannot be used says why. Nothing is made, changed or logged, because -v changes nothing.
function storageReportSections() {
  // / Set variables.
  global $ApacheUser, $EnableMemoryProtection;
  $ReportSections = array('Libraries' => array());
  $storageSettings = $libraryRecord = $webRecord = array();
  $settingsAreWhole = FALSE;
  $libraryName = $rowText = $problemText = '';
  $ownerNumber = $webNumber = -1;
  list ($storageSettings, $settingsAreWhole) = storageSettings(FALSE);
  if (function_exists('posix_getpwnam')) $webRecord = @posix_getpwnam((string)$ApacheUser);
  $webNumber = is_array($webRecord) ? (int)$webRecord['uid'] : -1;
  foreach ($storageSettings['Libraries'] as $libraryName => $libraryRecord) {
    redeclare($ownerNumber, (is_dir($libraryRecord['Root']) && !is_link($libraryRecord['Root'])) ? (int)@fileowner($libraryRecord['Root']) : -1);
    if ($libraryRecord['Placed'] && ($storageSettings['LibraryLoc'] === '' or !is_dir($storageSettings['LibraryLoc']))) $rowText = 'FAILED, '.$storageSettings['LibraryLoc'].' is missing. Run -fp as root';
    else if ($libraryRecord['Placed'] && $ownerNumber === -1) $rowText = 'OK, '.$libraryRecord['Kind'].', made on first use at '.$libraryRecord['Root'];
    else if ($ownerNumber === -1) $rowText = 'FAILED, '.$libraryRecord['Root'].' is missing';
    else if ($webNumber !== -1 && $ownerNumber !== $webNumber && $libraryRecord['Writers'] !== 'none') $rowText = 'FAILED, '.$libraryRecord['Root'].' is not '.$ApacheUser.'\'s, & must be for it to be written';
    else $rowText = 'OK, '.$libraryRecord['Kind'].($libraryRecord['Kind'] === 'shared' ? ', '.($libraryRecord['Writers'] === 'none' ? 'nobody writes' : $libraryRecord['Writers'].' write') : '').', '.$libraryRecord['Root'];
    $ReportSections['Libraries'][] = array('Label' => (string)$libraryName, 'Text' => $rowText, 'Failed' => (strpos($rowText, 'FAILED') === 0)); }
  foreach ($storageSettings['Refused'] as $libraryName => $problemText) $ReportSections['Libraries'][] = array('Label' => (string)$libraryName, 'Text' => 'FAILED, '.$problemText, 'Failed' => TRUE);
  $ReportSections['Libraries'][] = array('Label' => 'Trash', 'Text' => ((int)$storageSettings['TrashDays'] === 0 ? 'Kept until it is emptied' : 'Emptied after '.(int)$storageSettings['TrashDays'].' day(s)'), 'Failed' => !$settingsAreWhole);
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $storageSettings, $libraryRecord, $webRecord, $settingsAreWhole, $libraryName, $rowText, $problemText, $ownerNumber, $webNumber);
  return $ReportSections; }
// / -----------------------------------------------------------------------------------
?>
