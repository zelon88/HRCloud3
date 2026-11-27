<?php
// HRProprietary Engine v4.3.4ur, from HRCloud3 v1.0.0. The unit suite of the Storage Core, held to every rule ABOUT_STORAGE.txt says it has.
//
//   sudo bash Documentation/Build/Tests/contained.sh php Documentation/Build/Tests/test_storage-cli.php
// Run it from the root of the application under test. A tree named after it is tested instead, & must be that root.
//
// It lifts every function of the tree's own Resources/Engine/Cores/storageCore.php but printStorageReply() & answerStorageRequest(), which
// answer a web request, & the functions they call out of the tree's kernel, Engine, Environment Core & Authentication Manager. Nothing of
// any is copied into this file. Those that remove or rename are lifted
// with unlink, rmdir & rename named as calls this suite expects, & every path they are handed is beneath the scratch folder.
// It makes a data location, libraries, spaces, links that lead out of them & a trash, in the scratch folder, & asks the core of each.
// An upload & a download need a web request, so test_hrcloud3-cli.php asks those of the loader, & this suite asks everything else.
// It removes nothing itself. What the Storage Core removes is what it made, beneath the scratch folder.

if (PHP_SAPI !== 'cli') { if (!headers_sent()) http_response_code(404); die(); }

if (!is_file('Documentation/Build/Tests/testlib-cli.php')) { fwrite(STDERR, 'Run this from the root of the application it tests. See Documentation/Build/Tests/README.txt.'.PHP_EOL); exit(64); }
require 'Documentation/Build/Tests/testlib-cli.php';

$tree = testTree(isset($argv[1]) ? $argv[1] : (string)getcwd());
requireContainment();
$storageCoreFile = $tree.'/Resources/Engine/Cores/storageCore.php';
if (!is_file($storageCoreFile)) testStop($tree.' has no Storage Core. This suite tests Resources/Engine/Cores/storageCore.php, from v4.3.4ur.');

// What the kernel, the Engine & the application would have set before the Storage Core was loaded.
$EnableMemoryProtection = TRUE;
$Verbose = TRUE;
$LogFile = '';
$CoreLoaded = TRUE;
$EngineApplicationName = 'HRCloud3';
$base = makeFolder(scratchPath('storage'), 0700);
$DataRootLoc = makeFolder($base.'/data', 0755);
$InstLoc = makeFolder($base.'/install', 0755);
$HostedLoc = makeFolder($base.'/hosted', 0755);
$documentRoot = makeFolder($base.'/www', 0755);
$LibraryLoc = makeFolder($DataRootLoc.'/Libraries', 0700);
$UserLoc = makeFolder($DataRootLoc.'/Users', 0700);
$EngineSettingNames = array('LibraryLocation' => 'LibraryLoc', 'UserLocation' => 'UserLoc', 'StorageLibraries' => 'TestLibraries', 'TrashLifetime' => 'TestTrashLifetime',
  'UserStorageQuota' => 'TestQuota', 'StorageReserveMegabytes' => 'TestReserve', 'DocumentRoot' => 'TestDocumentRoot');
$TestDocumentRoot = $documentRoot;

liftFunctions($tree.'/Resources/Kernel/kernel.php', array('getChildFunction', 'purgeSensitiveMemory', 'printableText', 'applicationSetting', 'sanitizeString', 'sanitize', 'sanitizePath', 'redeclare'), array());
liftFunctions($tree.'/Resources/Engine/engine.php', array('pathIsSafeToModifyRecursively'), array());
liftFunctions($tree.'/Resources/Engine/Cores/environmentCore.php', array('pathTextIsBelow', 'pathForms', 'pathHoldsPath', 'engineApplicationName', 'engineApplicationSlug'), array());
liftFunctions($tree.'/Resources/Engine/Managers/authenticationManager.php', array('measureFolderBytes'), array());
liftFunctions($storageCoreFile, array('storageNameIsUsable', 'libraryNameIsUsable', 'storagePathNames', 'libraryLocationProblem', 'readLibrarySettings', 'storageSettings',
  'makeStorageFolder', 'libraryIsWritableBy', 'libraryIsPurgeableBy', 'storageKeptName', 'storageSpace', 'storageNameIsKept', 'resolveStorageFolder', 'storageItemKind', 'listStorageFolder',
  'compareStorageEntries', 'freeStorageName', 'storageEntryName', 'incomingStoragePath', 'accountStorageBytes', 'storageWriteAllows', 'makeStorageSubfolder', 'storageRange', 'storageDisposition',
  'findStorageItem', 'copyStorageFile', 'copyStorageTree', 'storageItemBytes', 'copyStorageItem', 'trashIdIsUsable', 'readTrashRecord', 'listStorageTrash', 'sweepStorage',
  'sweepStorageIfDue', 'storageInput', 'storageUploads', 'storageIniBytes', 'storageUploadLimit', 'librariesForAccount', 'storageReportSections', 'releaseStorageOutput', 'sendStorageFile',
  'storeUploadedFiles', 'storageTreeEntry', 'storageTreeEntries', 'recordStorageAuthors', 'readStorageAuthor', 'takeAwayStorageTree', 'trashRecordIsPurgeable'), array());
liftFunctions($storageCoreFile, array('placeStorageItem', 'renameStorageItem', 'moveStorageItem', 'removeStorageTree', 'trashStorageItem', 'restoreStorageItem', 'purgeStorageTrash',
  'sweepStorageFolders', 'removeAccountStorage', 'answerStorageAction', 'storeUploadedFile', 'writeStorageAuthors', 'forgetStorageAuthors'), array('unlink', 'rmdir', 'rename'));

// Sets the administrator's settings for the checks that follow. Anything not named takes the value HRCloud3 ships with.
function testSettings($settings) {
  $GLOBALS['TestLibraries'] = array('Drive' => array('Kind' => 'personal', 'Location' => ''), 'Shared' => array('Kind' => 'shared', 'Location' => '', 'Writers' => 'admins'));
  $GLOBALS['TestTrashLifetime'] = 30;
  $GLOBALS['TestQuota'] = 1024;
  $GLOBALS['TestReserve'] = 0;
  foreach ($settings as $settingName => $settingValue) $GLOBALS[$settingName] = $settingValue;
  $GLOBALS['TestLog'] = array();
  list ($storageSettings, $settingsAreWhole) = storageSettings(TRUE);
  return $storageSettings; }

// Says whether the log holds a line that holds the text named.
function logHolds($expectedText) {
  foreach ((array)$GLOBALS['TestLog'] as $logLine) if (strpos($logLine, $expectedText) !== FALSE) return TRUE;
  return FALSE; }

// Writes a file beneath the scratch folder, making the folders it is in.
function storageFile($filePath, $fileText) {
  makeFolder(dirname($filePath), 0700);
  return writeScratchFile($filePath, $fileText, 0600); }

// Makes a link beneath the scratch folder, pointing wherever it is told to.
function storageLink($linkPath, $target) {
  mustBeScratch($linkPath);
  if (!symlink($target, $linkPath)) testStop($linkPath.' could not be made.');
  clearstatcache();
  return $linkPath; }

// Sets the fields of a request, as a page posts them, for storageInput() & answerStorageAction().
function testRequest($fields) {
  $_POST = $fields;
  $_FILES = array();
  $_SERVER['HTTP_RANGE'] = '';
  return TRUE; }

// Lists the names in a folder, in order, without following a link to it.
function namesIn($folderPath) {
  $folderNames = (is_dir($folderPath) && !is_link($folderPath)) ? @scandir($folderPath) : FALSE;
  if (!is_array($folderNames)) return array('<not a folder>');
  $folderNames = array_values(array_diff($folderNames, array('.', '..')));
  sort($folderNames);
  return $folderNames; }

// Names the entries a listing gives, in its order.
function namesOf($entries) {
  $names = array();
  foreach ((array)$entries as $entry) $names[] = (string)field($entry, 'Name');
  return $names; }

$alice = array('Name' => 'alice-record', 'Username' => 'alice', 'Role' => 'admin', 'Folder' => 'aaaaaaaaaaaaaaaaaa');
$bob = array('Name' => 'bob-record', 'Username' => 'bob', 'Role' => 'user', 'Folder' => 'bbbbbbbbbbbbbbbbbb');


// Part 1. Names. A name is kept exactly as its user wrote it, so it is accepted whole or refused whole.
$acceptedNames = array('report.pdf', 'a b', '.hidden', 'Ünïcödé 10.txt', '<i>bold', 'a&b;c$d`e', 'tab-less', str_repeat('n', 255), 'x:y', '-starts-with-a-dash');
$refusedNames = array('', '.', '..', 'a/b', ' lead', 'trail ', "line\nbreak", "nul\0byte", "\x01control", str_repeat('n', 256), "\xff\xfe", 'data:x', "\xC2\x85next-line", 'ab://x');
$wrongNames = array();
foreach ($acceptedNames as $name) if (storageNameIsUsable($name) !== TRUE) $wrongNames[] = 'refused '.json_encode($name);
foreach ($refusedNames as $name) if (storageNameIsUsable($name) !== FALSE) $wrongNames[] = 'accepted '.json_encode($name, JSON_INVALID_UTF8_SUBSTITUTE);
check('a name is any UTF-8 of 1 to 255 bytes with no slash or control character, that is not a dot or two, begins & ends with no space, & PHP would not open as a stream',
  $wrongNames === array(), implode(', ', $wrongNames));
check('a name that is not a string is refused', storageNameIsUsable(array('a')) === FALSE && storageNameIsUsable(5) === FALSE && storageNameIsUsable(NULL) === FALSE);
$wrongNames = array();
foreach (array('Drive', 'my-lib_2', 'A', str_repeat('L', 32)) as $name) if (libraryNameIsUsable($name) !== TRUE) $wrongNames[] = 'refused '.$name;
foreach (array('', '-Drive', '_Drive', 'My Drive', str_repeat('L', 33), "Drive\n", 'Dr.ve', 'Dr/ve', 'Ünï') as $name) if (libraryNameIsUsable($name) !== FALSE) $wrongNames[] = 'accepted '.json_encode($name);
check('a library is named with 1 to 32 letters, digits, dashes & underscores, beginning with a letter or a digit, & a line break after one is refused', $wrongNames === array(), implode(', ', $wrongNames));
list ($topIsUsable, $topNames) = storagePathNames('');
list ($pathIsUsable, $pathNames) = storagePathNames('/Photos/2026/');
list ($doubleIsUsable, $doubleNames) = storagePathNames('Photos//2026');
list ($climbIsUsable, $climbNames) = storagePathNames('Photos/../../Accounts');
list ($deepIsUsable, $deepNames) = storagePathNames(implode('/', array_fill(0, 33, 'd')));
list ($longIsUsable, $longNames) = storagePathNames(str_repeat('l/', 2049));
check('a folder is its names joined by slashes, nothing is the top, & an empty name, a climb, more than 32 names or 4096 bytes is refused', $topIsUsable === TRUE && $topNames === array()
  && $pathIsUsable === TRUE && $pathNames === array('Photos', '2026') && $doubleIsUsable === FALSE && $climbIsUsable === FALSE && $deepIsUsable === FALSE && $longIsUsable === FALSE
  && $climbNames === array());
$entryNames = array();
for ($nameNumber = 0; $nameNumber < 200; $nameNumber++) $entryNames[] = storageEntryName();
$sortedNames = $entryNames;
sort($sortedNames);
check('a name made for an entry is usable as a trash entry\'s, is never made twice, & sorts in the order the names were made', count(array_filter($entryNames, 'trashIdIsUsable')) === 200
  && count(array_unique($entryNames)) === 200 && $sortedNames === $entryNames, implode(' ', array_slice($entryNames, 0, 5)));
check('a trash entry is named for when it was deleted & sixteen hexadecimal digits', trashIdIsUsable('20261127061500-1a2b3c4d5e6f7a8b') === TRUE
  && trashIdIsUsable('20261127061500-1a2b3c4d') === FALSE && trashIdIsUsable("20261127061500-1a2b3c4d5e6f7a8b\n") === FALSE && trashIdIsUsable('../20261127061500-1a2b3c4d5e6f7a8b') === FALSE);


// Part 2. Ranges & names in a download's headers.
$rangeCases = array(array('', 10, 'whole', 0, 9), array('bytes=0-4', 10, 'part', 0, 4), array('bytes=5-', 10, 'part', 5, 9), array('bytes=-3', 10, 'part', 7, 9),
  array('bytes=-30', 10, 'part', 0, 9), array('bytes=0-100', 10, 'part', 0, 9), array('bytes=-0', 10, 'unsatisfiable', 0, 9), array('bytes=10-', 10, 'unsatisfiable', 0, 9),
  array('bytes=4-2', 10, 'whole', 0, 9), array('bytes=0-1,4-5', 10, 'whole', 0, 9), array('bytes=-', 10, 'whole', 0, 9), array('items=0-4', 10, 'whole', 0, 9),
  array('bytes=0-', 0, 'unsatisfiable', 0, 0), array('', 0, 'whole', 0, 0), array('bytes=-5', 0, 'unsatisfiable', 0, 0));
$wrongRanges = array();
foreach ($rangeCases as $rangeCase) {
  list ($rangeKind, $firstByte, $lastByte) = storageRange($rangeCase[0], $rangeCase[1]);
  if (array($rangeKind, $firstByte, $lastByte) !== array($rangeCase[2], $rangeCase[3], $rangeCase[4])) $wrongRanges[] = $rangeCase[0].' of '.$rangeCase[1].' was '.$rangeKind.' '.$firstByte.'-'.$lastByte; }
check('one range of bytes is honoured & held to the file, several or a malformed one sends the whole file, & one past the end cannot be satisfied', $wrongRanges === array(), implode(', ', $wrongRanges));
check('a name is carried in UTF-8 in filename*, & as plain text without quotes or backslashes in filename', storageDisposition('Ünï "q" \\b.txt') === "attachment; filename=\"_n_ _q_ _b.txt\"; filename*=UTF-8''%C3%9Cn%C3%AF%20%22q%22%20%5Cb.txt",
  storageDisposition('Ünï "q" \\b.txt'));


// Part 3. What an administrator lists, & what is refused.
$storageSettings = testSettings(array());
check('as HRCloud3 ships, Drive is personal & Shared is shared & written by admins, both in Libraries, & the trash keeps what it holds 30 days', array_keys($storageSettings['Libraries']) === array('Drive', 'Shared')
  && $storageSettings['Libraries']['Drive']['Root'] === $LibraryLoc.'/Drive' && $storageSettings['Libraries']['Drive']['Placed'] === TRUE && $storageSettings['Libraries']['Drive']['Writers'] === 'owner'
  && $storageSettings['Libraries']['Shared']['Writers'] === 'admins' && $storageSettings['TrashDays'] === 30 && $storageSettings['QuotaBytes'] === 1024 * 1048576
  && $storageSettings['Refused'] === array() && $GLOBALS['TestLog'] === array(), json_encode($storageSettings));
$elsewhere = makeFolder($base.'/elsewhere', 0700);
$linkedLibrary = storageLink($base.'/linked', $elsewhere);
$storageSettings = testSettings(array('TestLibraries' => array('Drive' => array('Kind' => 'personal', 'Location' => ''), 'Mine' => array('Kind' => 'shared', 'Location' => $elsewhere, 'Writers' => 'everyone'),
  'Overlap' => array('Kind' => 'shared', 'Location' => $elsewhere), 'InData' => array('Kind' => 'shared', 'Location' => $DataRootLoc.'/Elsewhere'), 'HoldsData' => array('Kind' => 'shared', 'Location' => $base),
  'Linked' => array('Kind' => 'shared', 'Location' => $linkedLibrary), 'Missing' => array('Kind' => 'shared', 'Location' => $base.'/nothing'), 'System' => array('Kind' => 'shared', 'Location' => '/etc/hrcloud3'),
  'Temporary' => array('Kind' => 'shared', 'Location' => '/tmp/hrcloud3'), 'Boot' => array('Kind' => 'shared', 'Location' => '/boot'), 'Var' => array('Kind' => 'shared', 'Location' => '/var'), 'Served' => array('Kind' => 'shared', 'Location' => $documentRoot.'/files'), 'Relative' => array('Kind' => 'shared', 'Location' => 'files/here'),
  'Climbs' => array('Kind' => 'shared', 'Location' => $elsewhere.'/../elsewhere'), 'Kindless' => array('Kind' => 'public', 'Location' => ''), 'Writable' => array('Kind' => 'shared', 'Location' => '', 'Writers' => 'all'),
  'Bad Name' => array('Kind' => 'personal', 'Location' => ''), 'Plain' => 'personal', 'Installed' => array('Kind' => 'shared', 'Location' => $InstLoc), 'Hosted' => array('Kind' => 'shared', 'Location' => $HostedLoc))));
$refusals = $storageSettings['Refused'];
check('a library with a location of its own is used where it is a real folder outside everything the Engine keeps, & named as it is written', array_keys($storageSettings['Libraries']) === array('Drive', 'Mine')
  && $storageSettings['Libraries']['Mine']['Root'] === $elsewhere && $storageSettings['Libraries']['Mine']['Placed'] === FALSE && $storageSettings['Libraries']['Mine']['Writers'] === 'everyone',
  json_encode($storageSettings['Libraries']));
$expectedRefusals = array('Overlap' => 'its location overlaps the library Mine', 'InData' => 'is inside the data location or holds it', 'HoldsData' => 'is inside the data location or holds it',
  'Linked' => 'leads to '.$elsewhere.', & a link is never followed', 'Missing' => 'is not a folder', 'System' => 'is in /etc, where the operating system keeps its own files',
  'Temporary' => 'is in /tmp, where the operating system keeps its own files', 'Boot' => 'is in /boot, where the operating system keeps its own files',
  'Var' => 'is a folder the operating system needs', 'Served' => 'is inside the document root or holds it', 'Relative' => 'is not an absolute path', 'Climbs' => 'is not an absolute path',
  'Kindless' => 'its Kind is neither personal nor shared', 'Writable' => 'its Writers is not admins, everyone or none', 'Bad Name' => 'its name is not 1 to 32 letters',
  'Plain' => 'it is not a list of settings', 'Installed' => 'is inside the installation or holds it', 'Hosted' => 'is inside the hosted location or holds it');
$wrongRefusals = array();
foreach ($expectedRefusals as $libraryName => $expectedReason) if (!isset($refusals[$libraryName]) or strpos($refusals[$libraryName], $expectedReason) === FALSE) $wrongRefusals[] = $libraryName.': '.field($refusals, $libraryName);
check('each library that cannot be used is left out with the reason it cannot, & the others are kept', $wrongRefusals === array() && count($refusals) === count($expectedRefusals),
  implode(' | ', $wrongRefusals).' '.json_encode(array_keys($refusals)));
check('& each is a warning', count(preg_grep('/^WARNING!!! The library .* could not be used, because /', $GLOBALS['TestLog'])) === count($expectedRefusals), implode("\n", $GLOBALS['TestLog']));
$GLOBALS['TestLog'] = array();
list ($quietSettings, $quietAreWhole) = storageSettings(FALSE);
check('read for -v, the same settings warn about nothing', $GLOBALS['TestLog'] === array() && count($quietSettings['Refused']) === count($expectedRefusals) && $quietAreWhole === TRUE);
$storageSettings = testSettings(array('TestLibraries' => array('System' => array('Kind' => 'shared', 'Location' => '/etc/x')), 'TestTrashLifetime' => 'forever'));
check('where no library can be used, Drive is, which configuration cannot overwrite, & a trash lifetime that is not a whole number of days is 30', array_keys($storageSettings['Libraries']) === array('Drive')
  && $storageSettings['Libraries']['Drive']['Kind'] === 'personal' && $storageSettings['TrashDays'] === 30 && logHolds('No library in --Libraries-- could be used')
  && logHolds('--Trash Lifetime-- is not a whole number of days from 0 to 3650'), implode("\n", $GLOBALS['TestLog']));
$storageSettings = testSettings(array('TestLibraries' => 'Drive', 'TestTrashLifetime' => '7'));
check('a list of libraries that is not a list is the fallback, & a lifetime written as digits is read', array_keys($storageSettings['Libraries']) === array('Drive') && $storageSettings['TrashDays'] === 7
  && logHolds('No library is listed in --Libraries--'));


// Part 4. Spaces. A personal space is made the first time it is needed, & a shared library's own folder only where its user may write it.
$storageSettings = testSettings(array());
list ($spaceIsReady, $bobDrive, $spaceFinding) = storageSpace($storageSettings, 'Drive', $bob);
check('bob\'s space in Drive is made, named for his account\'s folder, with its tree, its trash & its upload folder, each private, & no record of who added what', $spaceIsReady === TRUE && $spaceFinding === ''
  && $bobDrive['Tree'] === $LibraryLoc.'/Drive/bbbbbbbbbbbbbbbbbb/Files' && $bobDrive['MayWrite'] === TRUE && $bobDrive['MayPurge'] === TRUE && $bobDrive['Authors'] === ''
  && $bobDrive['Account'] === 'bbbbbbbbbbbbbbbbbb' && namesIn($LibraryLoc.'/Drive/bbbbbbbbbbbbbbbbbb') === array('Files', 'Incoming', 'Trash')
  && (fileperms($LibraryLoc.'/Drive/bbbbbbbbbbbbbbbbbb/Trash') & 0777) === 0700 && (fileperms($LibraryLoc.'/Drive') & 0777) === 0700, json_encode($bobDrive));
list ($spaceIsReady, $bobShared, $spaceFinding) = storageSpace($storageSettings, 'Shared', $bob);
check('bob only reads Shared, so nothing is made in it for him', $spaceIsReady === TRUE && $bobShared['MayWrite'] === FALSE && $bobShared['Trash'] === '' && $bobShared['Authors'] === ''
  && namesIn($LibraryLoc.'/Shared') === array(),
  json_encode($bobShared));
list ($spaceIsReady, $aliceShared, $spaceFinding) = storageSpace($storageSettings, 'Shared', $alice);
check('alice, an administrator, writes Shared & empties its trash, which are kept with who added what in .hrcloud3, named for the application, at its top',
  $spaceIsReady === TRUE && $aliceShared['MayWrite'] === TRUE && $aliceShared['MayPurge'] === TRUE && $aliceShared['Trash'] === $LibraryLoc.'/Shared/.hrcloud3/Trash'
  && $aliceShared['Authors'] === $LibraryLoc.'/Shared/.hrcloud3/Authors' && $aliceShared['Account'] === 'aaaaaaaaaaaaaaaaaa'
  && namesIn($LibraryLoc.'/Shared/.hrcloud3') === array('Authors', 'Incoming', 'Trash'), json_encode($aliceShared));
list ($spaceIsReady, $unknownSpace, $spaceFinding) = storageSpace($storageSettings, 'Elsewhere', $bob);
list ($signedOutReady, $signedOutSpace, $signedOutFinding) = storageSpace($storageSettings, 'Drive', array('Folder' => '../../etc'));
check('a library that is not listed is unknown, & an account whose folder is not eighteen hexadecimal digits is signed out', $spaceFinding === 'library-unknown' && $signedOutFinding === 'signed-out'
  && namesIn($LibraryLoc.'/Drive') === array('bbbbbbbbbbbbbbbbbb'));
$storageSettings = testSettings(array('TestLibraries' => array('Shared' => array('Kind' => 'shared', 'Location' => '', 'Writers' => 'everyone'))));
list ($spaceIsReady, $bobEveryone, $spaceFinding) = storageSpace($storageSettings, 'Shared', $bob);
check('where everyone writes a shared library, bob writes it & does not empty its trash', $bobEveryone['MayWrite'] === TRUE && $bobEveryone['MayPurge'] === FALSE, json_encode($bobEveryone));
$storageSettings = testSettings(array('TestLibraries' => array('Shared' => array('Kind' => 'shared', 'Location' => '', 'Writers' => 'none'))));
list ($spaceIsReady, $aliceNone, $spaceFinding) = storageSpace($storageSettings, 'Shared', $alice);
check('where nobody writes it, not even an administrator does', $aliceNone['MayWrite'] === FALSE && $aliceNone['MayPurge'] === FALSE, json_encode($aliceNone));
$storageSettings = testSettings(array());
$storageSettings['LibraryLoc'] = $DataRootLoc.'/NoLibraries';
list ($spaceIsReady, $noSpace, $spaceFinding) = storageSpace($storageSettings, 'Drive', $alice);
check('where Libraries is missing, no library kept in the data location is used, & that is error 52000, which says to run -fp', $spaceIsReady === FALSE && $spaceFinding === 'unavailable'
  && logHolds('52000') && logHolds('Run -fp as root!') && !file_exists($DataRootLoc.'/NoLibraries'), implode("\n", $GLOBALS['TestLog']));
$storageSettings = testSettings(array());
list ($spaceIsReady, $aliceDrive, $spaceFinding) = storageSpace($storageSettings, 'Drive', $alice);


// Part 5. Folders are walked a name at a time, & a link is never followed, listed or acted on.
$bobTree = $bobDrive['Tree'];
makeFolder($bobTree.'/Photos/2026', 0700);
storageFile($bobTree.'/Photos/2026/beach.jpg', 'sand');
storageFile($bobTree.'/notes.txt', 'notes');
storageFile($base.'/outside/secret.txt', 'secret');
storageLink($bobTree.'/escape', $base.'/outside');
storageLink($bobTree.'/secret.txt', $base.'/outside/secret.txt');
storageLink($bobTree.'/Photos/up', '..');
list ($found, $path, $finding) = resolveStorageFolder($bobDrive, array('Photos', '2026'));
list ($linkFound, $linkPath, $linkFinding) = resolveStorageFolder($bobDrive, array('escape'));
list ($upFound, $upPath, $upFinding) = resolveStorageFolder($bobDrive, array('Photos', 'up', 'Photos'));
list ($fileFound, $filePath, $fileFinding) = resolveStorageFolder($bobDrive, array('notes.txt'));
list ($missingFound, $missingPath, $missingFinding) = resolveStorageFolder($bobDrive, array('Nothing'));
check('a folder is found by its names, & a link, a file or nothing in the way is said to be so, & no path is handed back', $found === TRUE && $path === $bobTree.'/Photos/2026'
  && $linkFound === FALSE && $linkFinding === 'link' && $linkPath === '' && $upFinding === 'link' && $fileFinding === 'not-folder' && $missingFinding === 'missing');
list ($keptFound, $keptPath, $keptFinding) = resolveStorageFolder($aliceShared, array('.hrcloud3', 'Trash'));
list ($deeperFound, $deeperPath, $deeperFinding) = resolveStorageFolder($aliceShared, array());
check('the top of a shared library is found, & its own folder never is', $keptFound === FALSE && $keptFinding === 'missing' && $deeperFound === TRUE);
for ($fileNumber = 1; $fileNumber <= 12; $fileNumber++) storageFile($bobTree.'/Photos/IMG '.$fileNumber.'.jpg', (string)$fileNumber);
makeFolder($bobTree.'/Photos/b-folder', 0700);
makeFolder($bobTree.'/Photos/A-folder', 0700);
list ($folderWasListed, $folderEntries, $entriesWereCut, $listFinding) = listStorageFolder($bobDrive, array('Photos'));
check('a listing gives folders first, then files, each in the order a person reads them, & leaves out a link', $folderWasListed === TRUE && $entriesWereCut === FALSE
  && namesOf($folderEntries) === array('2026', 'A-folder', 'b-folder', 'IMG 1.jpg', 'IMG 2.jpg', 'IMG 3.jpg', 'IMG 4.jpg', 'IMG 5.jpg', 'IMG 6.jpg', 'IMG 7.jpg', 'IMG 8.jpg', 'IMG 9.jpg',
  'IMG 10.jpg', 'IMG 11.jpg', 'IMG 12.jpg') && field($folderEntries[3], 'Size') === 1 && field($folderEntries[0], 'Size') === 0, implode(', ', namesOf($folderEntries)));
list ($topWasListed, $topEntries, $topWasCut, $topFinding) = listStorageFolder($bobDrive, array());
list ($escapeWasListed, $escapeEntries, $escapeWasCut, $escapeFinding) = listStorageFolder($bobDrive, array('escape'));
check('neither link at the top is listed, & a link is never entered', namesOf($topEntries) === array('Photos', 'notes.txt') && $escapeWasListed === FALSE && $escapeFinding === 'missing', implode(', ', namesOf($topEntries)));
makeFolder($LibraryLoc.'/Shared/Team', 0700);
list ($sharedWasListed, $sharedEntries, $sharedWasCut, $sharedFinding) = listStorageFolder($aliceShared, array());
check('a shared library\'s own folder is never listed', namesOf($sharedEntries) === array('Team'), implode(', ', namesOf($sharedEntries)));
$manyFolder = makeFolder($bobTree.'/Many', 0700);
for ($fileNumber = 0; $fileNumber <= 10000; $fileNumber++) touch($manyFolder.'/'.sprintf('%05d', $fileNumber));
list ($manyWasListed, $manyEntries, $manyWasCut, $manyFinding) = listStorageFolder($bobDrive, array('Many'));
check('a folder of more than 10,000 names lists 10,000, & says it was cut', $manyWasListed === TRUE && count($manyEntries) === 10000 && $manyWasCut === TRUE);


// Part 6. Nothing is ever replaced.
check('a free name is the one wanted, & otherwise is numbered before its extension', freeStorageName($bobTree, 'free.txt') === 'free.txt' && freeStorageName($bobTree, 'notes.txt') === 'notes (2).txt'
  && freeStorageName($bobTree, 'Photos') === 'Photos (2)' && freeStorageName($bobTree, 'secret.txt') === 'secret (2).txt');
storageFile($bobTree.'/notes (2).txt', 'second');
check('& counts past what is taken', freeStorageName($bobTree, 'notes.txt') === 'notes (3).txt');
$readyFile = storageFile($bobDrive['Incoming'].'/ready', 'ready');
$placedOver = placeStorageItem($readyFile, $bobTree, 'notes.txt');
check('a file is never put in place over one that is there, & what was ready is left as it was', $placedOver === FALSE && file_get_contents($bobTree.'/notes.txt') === 'notes' && file_get_contents($readyFile) === 'ready');
$placedOverLink = placeStorageItem($readyFile, $bobTree, 'secret.txt');
check('nor over a link, which is left leading where it led', $placedOverLink === FALSE && is_link($bobTree.'/secret.txt') && file_get_contents($base.'/outside/secret.txt') === 'secret');
check('& one with a free name is put in place, & is gone from where it was ready', placeStorageItem($readyFile, $bobTree, 'ready.txt') === TRUE && file_get_contents($bobTree.'/ready.txt') === 'ready'
  && !file_exists($readyFile));
check('a folder is made where its name is free', makeStorageSubfolder($bobDrive, array(), 'New') === 'made' && makeStorageSubfolder($bobDrive, array(), 'New') === 'exists'
  && makeStorageSubfolder($bobDrive, array(), 'escape') === 'exists' && makeStorageSubfolder($bobDrive, array('Nothing'), 'New') === 'missing'
  && makeStorageSubfolder($bobShared, array(), 'Mine') === 'read-only' && makeStorageSubfolder($aliceShared, array(), '.hrcloud3') === 'name-invalid');
check('a rename never replaces, & a rename to another case of the same name is one', renameStorageItem($bobDrive, array(), 'notes.txt', 'ready.txt') === 'exists'
  && renameStorageItem($bobDrive, array(), 'notes.txt', 'Notes.txt') === 'renamed' && file_get_contents($bobTree.'/Notes.txt') === 'notes'
  && renameStorageItem($bobDrive, array(), 'secret.txt', 'mine.txt') === 'missing' && is_link($bobTree.'/secret.txt') && renameStorageItem($bobDrive, array(), 'Notes.txt', 'a/b') === 'name-invalid');
check('a folder is never moved into itself or a folder inside it, & is moved into one that only begins with its name', moveStorageItem($bobDrive, array(), 'Photos', array('Photos', '2026')) === 'into-itself'
  && moveStorageItem($bobDrive, array(), 'Photos', array('Photos')) === 'into-itself' && makeStorageSubfolder($bobDrive, array(), 'Photos2') === 'made'
  && moveStorageItem($bobDrive, array(), 'New', array('Photos2')) === 'moved' && is_dir($bobTree.'/Photos2/New'));
check('a move to where a name is taken never replaces it, & one to a link never follows it', moveStorageItem($bobDrive, array(), 'ready.txt', array('Photos2')) === 'moved'
  && storageFile($bobTree.'/ready.txt', 'again') !== '' && moveStorageItem($bobDrive, array(), 'ready.txt', array('Photos2')) === 'exists'
  && moveStorageItem($bobDrive, array(), 'ready.txt', array('escape')) === 'missing' && !file_exists($base.'/outside/ready.txt'));


// Part 7. Copies. A copy is whole before it is seen, is measured first, & never carries a link.
storageLink($bobTree.'/Photos/2026/link-out', $base.'/outside');
list ($copyOutcome, $copiedName, $copyIsJudged) = copyStorageItem($storageSettings, $bob, $bobDrive, array(), 'Photos', $bobDrive, array(), 'keep');
check('a folder is copied beside itself, numbered, with every file in it & no link', $copyOutcome === 'copied' && $copiedName === 'Photos (2)' && $copyIsJudged === TRUE
  && file_get_contents($bobTree.'/Photos (2)/2026/beach.jpg') === 'sand' && !file_exists($bobTree.'/Photos (2)/2026/link-out') && !is_link($bobTree.'/Photos (2)/2026/link-out')
  && !is_link($bobTree.'/Photos (2)/up') && namesIn($bobDrive['Incoming']) === array() && logHolds('link(s) were left out of a copy'), $copyOutcome.' '.$copiedName);
list ($intoOutcome, $intoName, $intoJudged) = copyStorageItem($storageSettings, $bob, $bobDrive, array(), 'Photos', $bobDrive, array('Photos', '2026'), 'keep');
list ($refuseOutcome, $refuseName, $refuseJudged) = copyStorageItem($storageSettings, $bob, $bobDrive, array(), 'Notes.txt', $bobDrive, array(), 'refuse');
list ($sharedOutcome, $sharedName, $sharedJudged) = copyStorageItem($storageSettings, $bob, $bobDrive, array(), 'Notes.txt', $bobShared, array(), 'keep');
list ($aliceOutcome, $aliceName, $aliceJudged) = copyStorageItem($storageSettings, $alice, $bobShared, array('Team'), 'Missing.txt', $aliceDrive, array(), 'keep');
check('a folder is never copied into itself, a taken name is refused where it is told to be, a library its user only reads takes nothing, & nothing missing is copied',
  $intoOutcome === 'into-itself' && $refuseOutcome === 'exists' && $sharedOutcome === 'read-only' && $aliceOutcome === 'missing' && namesIn($bobDrive['Incoming']) === array());
storageFile($LibraryLoc.'/Shared/Team/plan.txt', 'the plan');
list ($teamOutcome, $teamName, $teamJudged) = copyStorageItem($storageSettings, $bob, $bobShared, array('Team'), 'plan.txt', $bobDrive, array(), 'keep');
check('a reader copies out of a shared library into his own', $teamOutcome === 'copied' && file_get_contents($bobTree.'/plan.txt') === 'the plan');
$storageSettings = testSettings(array('TestQuota' => 1));
storageFile($bobTree.'/big.bin', str_repeat('b', 700000));
list ($quotaOutcome, $quotaName, $quotaJudged) = copyStorageItem($storageSettings, $bob, $bobDrive, array(), 'big.bin', $bobDrive, array(), 'keep');
list ($writeAllowed, $writeFinding) = storageWriteAllows($storageSettings, $bobDrive, $bob, 100);
list ($sharedAllowed, $sharedFinding) = storageWriteAllows($storageSettings, $aliceShared, $bob, 700000);
check('with a quota of 1 MB, a copy that would pass it is refused, a small write is not, & a shared library is counted against nobody', $quotaOutcome === 'quota'
  && !file_exists($bobTree.'/big (2).bin') && $writeAllowed === TRUE && $sharedAllowed === TRUE);
$userFolder = makeFolder($UserLoc.'/bbbbbbbbbbbbbbbbbb', 0700);
storageFile($userFolder.'/kept.bin', str_repeat('u', 400000));
list ($bytesWereMeasured, $storedBytes) = accountStorageBytes($storageSettings, $bob);
list ($smallAllowed, $smallFinding) = storageWriteAllows($storageSettings, $bobDrive, $bob, 100);
check('a user\'s folder in Users counts against the quota with every personal space of theirs, their trash included', $bytesWereMeasured === TRUE && $storedBytes > 1100000
  && $smallAllowed === FALSE && $smallFinding === 'quota', (string)$storedBytes);
$storageSettings = testSettings(array('TestReserve' => 1000000000));
list ($reserveAllowed, $reserveFinding) = storageWriteAllows($storageSettings, $aliceShared, $alice, 1);
check('a write that would take a disk within --Storage Reserve-- is refused, in any library', $reserveAllowed === FALSE && $reserveFinding === 'disk-full');
$storageSettings = testSettings(array());


// Part 8. The trash. What is deleted is kept whole, beside where it was, until its lifetime is over, & is put back without replacing anything.
list ($trashOutcome, $trashIsJudged) = trashStorageItem($bobDrive, array('Photos'), '2026');
list ($trashWasListed, $trashEntries) = listStorageTrash($bobDrive);
$trashEntry = isset($trashEntries[0]) ? $trashEntries[0] : array();
check('a folder goes to the trash whole, with a record of where it was', $trashOutcome === 'trashed' && $trashIsJudged === TRUE && !file_exists($bobTree.'/Photos/2026') && count($trashEntries) === 1
  && field($trashEntry, 'Name') === '2026' && field($trashEntry, 'Folder') === 'Photos' && field($trashEntry, 'Kind') === 'folder'
  && is_file($bobDrive['Trash'].'/'.field($trashEntry, 'Id').'/Item/beach.jpg') && is_link($bobDrive['Trash'].'/'.field($trashEntry, 'Id').'/Item/link-out'), json_encode($trashEntries));
list ($linkTrashOutcome, $linkTrashJudged) = trashStorageItem($bobDrive, array(), 'escape');
check('a link is never deleted, so what it leads to is never reached', $linkTrashOutcome === 'missing' && is_link($bobTree.'/escape') && file_get_contents($base.'/outside/secret.txt') === 'secret');
setAside($bobTree.'/Photos');
list ($restoreOutcome, $restoredName, $restoredFolder, $restoreIsJudged) = restoreStorageItem($bobDrive, field($trashEntry, 'Id'));
check('put back where its folder is gone, it goes to the top of the space', $restoreOutcome === 'restored' && $restoredName === '2026' && $restoredFolder === '' && is_file($bobTree.'/2026/beach.jpg')
  && listStorageTrash($bobDrive)[1] === array());
trashStorageItem($bobDrive, array(), '2026');
makeFolder($bobTree.'/2026', 0700);
list ($trashWasListed, $trashEntries) = listStorageTrash($bobDrive);
list ($restoreOutcome, $restoredName, $restoredFolder, $restoreIsJudged) = restoreStorageItem($bobDrive, field($trashEntries[0], 'Id'));
check('& beside what took its name since, replacing nothing', $restoreOutcome === 'restored' && $restoredName === '2026 (2)' && is_file($bobTree.'/2026 (2)/beach.jpg') && is_dir($bobTree.'/2026'));
check('an entry that is not there, or not named as one is, is missing', restoreStorageItem($bobDrive, '20000101000000-0000000000000000')[0] === 'missing'
  && restoreStorageItem($bobDrive, '../../Files')[0] === 'missing' && restoreStorageItem($bobShared, '20000101000000-0000000000000000')[0] === 'read-only');
trashStorageItem($bobDrive, array(), 'Notes.txt');
list ($trashWasListed, $trashEntries) = listStorageTrash($bobDrive);
$tamperedRecord = $bobDrive['Trash'].'/'.field($trashEntries[0], 'Id').'/Record.json';
$keptRecord = (string)file_get_contents($tamperedRecord);
$tamperedReads = array();
foreach (array('{"Name":"Notes.txt","Folder":[".."],"Kind":"file","Deleted":1}', '{"Name":"../Notes.txt","Folder":[],"Kind":"file","Deleted":1}', '{"Name":"Notes.txt","Folder":[],"Kind":"link","Deleted":1}',
  '{"Name":"Notes.txt","Folder":"Photos","Kind":"file","Deleted":1}', '{"Name":"Notes.txt","Folder":[],"Kind":"file","Deleted":"1"}', 'not json') as $tamperedText) {
  writeScratchFile($tamperedRecord, $tamperedText, 0600);
  list ($tamperedWasListed, $tamperedEntries) = listStorageTrash($bobDrive);
  if ($tamperedEntries !== array() or restoreStorageItem($bobDrive, field($trashEntries[0], 'Id'))[0] !== 'missing') $tamperedReads[] = $tamperedText; }
check('a trash record that names what no request could is never read, & never leads a restore', $tamperedReads === array()
  && is_file($bobDrive['Trash'].'/'.field($trashEntries[0], 'Id').'/Item'), implode(' | ', $tamperedReads));
writeScratchFile($tamperedRecord, $keptRecord, 0600);
check('only an account that may takes something out of the trash for good', purgeStorageTrash($bobEveryone, '')[0] === 'not-allowed' && purgeStorageTrash($bobShared, '')[0] === 'read-only'
  && purgeStorageTrash($bobDrive, 'not-an-id')[0] === 'missing' && purgeStorageTrash($bobDrive, '20000101000000-0000000000000000')[0] === 'missing');
list ($purgeOutcome, $entriesPurged) = purgeStorageTrash($bobDrive, field($trashEntries[0], 'Id'));
check('& what it takes is gone, & nothing else', $purgeOutcome === 'purged' && $entriesPurged === 1 && listStorageTrash($bobDrive)[1] === array() && is_file($bobTree.'/2026/beach.jpg') === FALSE && is_dir($bobTree.'/2026'));
trashStorageItem($bobDrive, array(), '2026 (2)');
trashStorageItem($bobDrive, array(), 'plan.txt');
list ($emptyOutcome, $entriesEmptied) = purgeStorageTrash($bobDrive, '');
check('emptying takes every entry, & a link inside one is taken away as a link, never followed', $emptyOutcome === 'emptied' && $entriesEmptied === 2 && namesIn($bobDrive['Trash']) === array()
  && file_get_contents($base.'/outside/secret.txt') === 'secret');


// Part 9. Who added what to a shared library. An administrator, or whoever alone added something, deletes it forever, & nobody else.
$sharedRoot = $LibraryLoc.'/Shared';
$authorsFolder = $sharedRoot.'/.hrcloud3/Authors';
$foldedNames = (storageNameIsKept($bobEveryone, array(), '.HRCloud3') === TRUE && storageNameIsKept($bobEveryone, array(), '.hrcloud3') === TRUE
  && storageNameIsKept($bobEveryone, array('Team'), '.hrcloud3') === FALSE && storageNameIsKept($bobDrive, array(), '.hrcloud3') === FALSE);
$teamKeptOutcome = makeStorageSubfolder($bobEveryone, array('Team'), '.hrcloud3');
check('the folder a shared library keeps is named for the application, no spelling of it may be used at the top, & it is an ordinary name anywhere else',
  storageKeptName() === '.hrcloud3' && $foldedNames && makeStorageSubfolder($bobEveryone, array(), '.HRCLOUD3') === 'name-invalid' && $teamKeptOutcome === 'made');
storageFile($bobTree.'/bobs.txt', 'by bob');
storageFile($bobTree.'/bobs-too.txt', 'also by bob');
storageFile($aliceDrive['Tree'].'/alices.txt', 'by alice');
// Reads the record of who added something to Shared, by what it is.
$recordOf = function ($itemPath) use ($authorsFolder) {
  $itemStatus = lstat($itemPath);
  return json_decode((string)@file_get_contents($authorsFolder.'/'.$itemStatus['ino']), TRUE); };
// Says, for each entry of a trash listing, whether it may be deleted forever.
$purgeableIn = function ($entries) {
  $purgeable = array();
  foreach ((array)$entries as $entry) $purgeable[(string)field($entry, 'Name')] = field($entry, 'Purgeable');
  ksort($purgeable);
  return $purgeable; };
// Finds the Id of a trash entry by the name of what was deleted.
$entryIdIn = function ($entries, $entryName) {
  foreach ((array)$entries as $entry) if (field($entry, 'Name') === $entryName) return (string)field($entry, 'Id');
  return ''; };
list ($bobCopy, $bobCopyName, $bobCopyJudged) = copyStorageItem($storageSettings, $bob, $bobDrive, array(), 'bobs.txt', $bobEveryone, array(), 'keep');
$madeOutcome = makeStorageSubfolder($bobEveryone, array(), 'Bobs Folder');
list ($innerCopy, $innerName, $innerJudged) = copyStorageItem($storageSettings, $bob, $bobDrive, array(), 'bobs-too.txt', $bobEveryone, array('Bobs Folder'), 'keep');
check('what bob copies into it & the folder he makes are recorded as his, by what each is, & his own library records nothing', $bobCopy === 'copied' && $madeOutcome === 'made' && $innerCopy === 'copied'
  && field($recordOf($sharedRoot.'/bobs.txt'), 'Author') === 'bbbbbbbbbbbbbbbbbb' && field($recordOf($sharedRoot.'/bobs.txt'), 'Size') === 6
  && field($recordOf($sharedRoot.'/Bobs Folder'), 'Author') === 'bbbbbbbbbbbbbbbbbb' && field($recordOf($sharedRoot.'/Bobs Folder'), 'Kind') === 'folder'
  && field($recordOf($sharedRoot.'/Bobs Folder/bobs-too.txt'), 'Author') === 'bbbbbbbbbbbbbbbbbb' && namesIn($sharedRoot.'/.hrcloud3/Incoming') === array()
  && !file_exists($LibraryLoc.'/Drive/bbbbbbbbbbbbbbbbbb/Authors'), json_encode($recordOf($sharedRoot.'/bobs.txt')));
list ($aliceCopy, $aliceCopyName, $aliceCopyJudged) = copyStorageItem($storageSettings, $alice, $aliceDrive, array(), 'alices.txt', $aliceShared, array(), 'keep');
list ($aliceInner, $aliceInnerName, $aliceInnerJudged) = copyStorageItem($storageSettings, $alice, $aliceDrive, array(), 'alices.txt', $aliceShared, array('Bobs Folder'), 'keep');
setAside($aliceDrive['Tree'].'/alices.txt');
storageFile($sharedRoot.'/by-another-program.txt', 'nobody recorded this');
$deletedOutcomes = array();
foreach (array('bobs.txt', 'Bobs Folder', 'alices.txt', 'by-another-program.txt') as $deletedName) $deletedOutcomes[] = trashStorageItem($bobEveryone, array(), $deletedName)[0];
list ($bobTrashListed, $bobTrashEntries) = listStorageTrash($bobEveryone);
list ($aliceTrashListed, $aliceTrashEntries) = listStorageTrash($aliceShared);
check('bob deletes all four, & the trash says he may delete forever only what he alone added, while alice may delete anything forever', $aliceCopy === 'copied' && $aliceInner === 'copied'
  && $deletedOutcomes === array('trashed', 'trashed', 'trashed', 'trashed')
  && $purgeableIn($bobTrashEntries) === array('Bobs Folder' => FALSE, 'alices.txt' => FALSE, 'bobs.txt' => TRUE, 'by-another-program.txt' => FALSE)
  && $purgeableIn($aliceTrashEntries) === array('Bobs Folder' => TRUE, 'alices.txt' => TRUE, 'bobs.txt' => TRUE, 'by-another-program.txt' => TRUE), json_encode($bobTrashEntries));
$bobsEntry = $entryIdIn($bobTrashEntries, 'bobs.txt');
$bobsStatus = lstat($bobEveryone['Trash'].'/'.$bobsEntry.'/Item');
$bobsRecordWasThere = is_file($authorsFolder.'/'.$bobsStatus['ino']);
list ($ownPurge, $ownPurged) = purgeStorageTrash($bobEveryone, $bobsEntry);
list ($mixedPurge, $mixedPurged) = purgeStorageTrash($bobEveryone, $entryIdIn($bobTrashEntries, 'Bobs Folder'));
list ($othersPurge, $othersPurged) = purgeStorageTrash($bobEveryone, $entryIdIn($bobTrashEntries, 'alices.txt'));
list ($unknownPurge, $unknownPurged) = purgeStorageTrash($bobEveryone, $entryIdIn($bobTrashEntries, 'by-another-program.txt'));
list ($bobEmpty, $bobEmptied) = purgeStorageTrash($bobEveryone, '');
check('bob deletes forever what he alone added, with its record, & neither alice\'s, nor a folder he shares with her, nor what no account added, & does not empty the trash',
  $ownPurge === 'purged' && $bobsRecordWasThere && !file_exists($authorsFolder.'/'.$bobsStatus['ino']) && $mixedPurge === 'not-allowed' && $othersPurge === 'not-allowed'
  && $unknownPurge === 'not-allowed' && $bobEmpty === 'not-allowed' && count(namesIn($bobEveryone['Trash'])) === 3, $ownPurge.' '.$mixedPurge.' '.$othersPurge.' '.$unknownPurge.' '.$bobEmpty);
list ($againCopy, $againName, $againJudged) = copyStorageItem($storageSettings, $bob, $bobDrive, array(), 'bobs.txt', $bobEveryone, array(), 'keep');
$renameOutcome = renameStorageItem($bobEveryone, array(), 'bobs.txt', 'renamed.txt');
$moveOutcome = moveStorageItem($bobEveryone, array(), 'renamed.txt', array('Team'));
trashStorageItem($bobEveryone, array('Team'), 'renamed.txt');
list ($renamedListed, $renamedEntries) = listStorageTrash($bobEveryone);
list ($restoreOutcome, $restoredName, $restoredFolder, $restoreIsJudged) = restoreStorageItem($bobEveryone, $entryIdIn($renamedEntries, 'renamed.txt'));
trashStorageItem($bobEveryone, array('Team'), 'renamed.txt');
list ($renamedListed, $renamedEntries) = listStorageTrash($bobEveryone);
check('what bob added stays his through a rename, a move, the trash & a restore', $againCopy === 'copied' && $renameOutcome === 'renamed' && $moveOutcome === 'moved'
  && $restoreOutcome === 'restored' && $restoredFolder === 'Team' && field($purgeableIn($renamedEntries), 'renamed.txt') === TRUE, json_encode($renamedEntries));
list ($changedCopy, $changedName, $changedJudged) = copyStorageItem($storageSettings, $bob, $bobDrive, array(), 'bobs-too.txt', $bobEveryone, array(), 'keep');
writeScratchFile($sharedRoot.'/bobs-too.txt', 'changed by another program', 0600);
trashStorageItem($bobEveryone, array(), 'bobs-too.txt');
list ($changedListed, $changedEntries) = listStorageTrash($bobEveryone);
check('a file another program changed is no longer taken for what was recorded, so only an administrator deletes it forever', $changedCopy === 'copied'
  && field($purgeableIn($changedEntries), 'bobs-too.txt') === FALSE, json_encode($changedEntries));
list ($aliceEmpty, $aliceEmptied) = purgeStorageTrash($aliceShared, '');
check('alice empties the trash, & every record of what it held goes with it, leaving only the records of what is still there', $aliceEmpty === 'emptied' && $aliceEmptied === 5
  && namesIn($aliceShared['Trash']) === array() && namesIn($authorsFolder) === array((string)(is_dir($sharedRoot.'/Team/.hrcloud3') ? lstat($sharedRoot.'/Team/.hrcloud3')['ino'] : -1)),
  implode(' ', namesIn($authorsFolder)));
list ($oldCopy, $oldName, $oldJudged) = copyStorageItem($storageSettings, $bob, $bobDrive, array(), 'bobs.txt', $bobEveryone, array(), 'keep');
trashStorageItem($bobEveryone, array(), 'bobs.txt');
list ($oldListed, $oldEntries) = listStorageTrash($bobEveryone);
$oldSharedEntry = $bobEveryone['Trash'].'/'.$entryIdIn($oldEntries, 'bobs.txt');
$oldSharedStatus = lstat($oldSharedEntry.'/Item');
$oldSharedRecord = $oldSharedEntry.'/Record.json';
writeScratchFile($oldSharedRecord, preg_replace('/"Deleted":[0-9]+/', '"Deleted":'.(time() - 40 * 86400), (string)file_get_contents($oldSharedRecord)), 0600);
$recordBeforeSweep = is_file($authorsFolder.'/'.$oldSharedStatus['ino']);
list ($sharedSweepCompleted, $sharedSwept) = sweepStorageFolders($bobEveryone['Trash'], $bobEveryone['Incoming'], 30);
check('a sweep of a shared library\'s trash takes the record of who added what it takes', $oldCopy === 'copied' && $recordBeforeSweep && $sharedSweepCompleted === TRUE && $sharedSwept === 1
  && !file_exists($oldSharedEntry) && !file_exists($authorsFolder.'/'.$oldSharedStatus['ino']), implode(' ', namesIn($authorsFolder)));


// Part 10. Sweeps & removal. Only what is due is taken, a link is taken as a link, & something mounted inside is never crossed.
trashStorageItem($bobDrive, array(), 'Photos2');
trashStorageItem($bobDrive, array(), 'big.bin');
list ($trashWasListed, $trashEntries) = listStorageTrash($bobDrive);
$oldEntry = $bobDrive['Trash'].'/'.field($trashEntries[0], 'Id');
$youngEntry = $bobDrive['Trash'].'/'.field($trashEntries[1], 'Id');
$oldRecord = $oldEntry.'/Record.json';
writeScratchFile($oldRecord, preg_replace('/"Deleted":[0-9]+/', '"Deleted":'.(time() - 8 * 86400), (string)file_get_contents($oldRecord)), 0600);
$unfinished = storageFile($bobDrive['Incoming'].'/20000101000000-0000000000000000', 'unfinished');
touch($unfinished, time() - 90000);
$young = storageFile($bobDrive['Incoming'].'/20990101000000-0000000000000000', 'young');
list ($sweepCompleted, $entriesSwept) = sweepStorageFolders($bobDrive['Trash'], $bobDrive['Incoming'], 7);
check('a sweep takes a trash entry deleted longer ago than the days it keeps them, & an upload that never finished, & nothing younger', $sweepCompleted === TRUE && $entriesSwept === 2
  && !file_exists($oldEntry) && is_dir($youngEntry) && !file_exists($unfinished) && file_exists($young), implode(' ', namesIn($bobDrive['Trash'])));
list ($foreverCompleted, $foreverSwept) = sweepStorageFolders($bobDrive['Trash'], $bobDrive['Incoming'], 0);
check('a trash kept for ever is never swept, & its upload folder still is', $foreverCompleted === TRUE && $foreverSwept === 0 && count(namesIn($bobDrive['Trash'])) === 1);
$GLOBALS['TestLog'] = array();
list ($sweepRan, $entriesRemoved) = sweepStorageIfDue(600);
list ($sweepRanAgain, $entriesRemovedAgain) = sweepStorageIfDue(600);
list ($sweepRanNow, $entriesRemovedNow) = sweepStorageIfDue(0);
check('a sweep is due once its interval has passed since the last, which a file in Libraries keeps', $sweepRan === TRUE && $sweepRanAgain === FALSE && $sweepRanNow === TRUE && is_file($LibraryLoc.'/swept'));
$outsideTree = makeFolder($base.'/outside-tree', 0700);
storageFile($outsideTree.'/keep.txt', 'keep');
$doomed = makeFolder($bobTree.'/Doomed/deeper/deepest', 0700);
storageFile($doomed.'/file.txt', 'file');
storageLink($bobTree.'/Doomed/out', $outsideTree);
storageLink($bobTree.'/Doomed/deeper/dangling', $base.'/nowhere');
list ($treeWasRemoved, $namesRemoved, $removalIsJudged) = removeStorageTree($bobTree.'/Doomed');
check('a tree is removed whole, its links as links, & nothing a link led to', $treeWasRemoved === TRUE && $removalIsJudged === TRUE && $namesRemoved === 6 && !file_exists($bobTree.'/Doomed')
  && file_get_contents($outsideTree.'/keep.txt') === 'keep', (string)$namesRemoved);
$linkAtTop = storageLink($bobTree.'/TopLink', $outsideTree);
list ($linkWasRemoved, $linkNamesRemoved, $linkIsJudged) = removeStorageTree($linkAtTop);
check('& a link handed to it is removed as a link', $linkWasRemoved === TRUE && $linkNamesRemoved === 1 && !is_link($linkAtTop) && is_file($outsideTree.'/keep.txt'));
list ($goneWasRemoved, $goneNamesRemoved, $goneIsJudged) = removeStorageTree($bobTree.'/NeverThere');
check('& something already gone counts as removed', $goneWasRemoved === TRUE && $goneNamesRemoved === 0 && $goneIsJudged === TRUE);
list ($aliceSpaceReady, $aliceDriveAgain, $aliceFinding) = storageSpace($storageSettings, 'Drive', $alice);
check('removing an account takes its space in every personal library, & no other account\'s', removeAccountStorage('bbbbbbbbbbbbbbbbbb') === TRUE && !file_exists($LibraryLoc.'/Drive/bbbbbbbbbbbbbbbbbb')
  && is_dir($LibraryLoc.'/Drive/aaaaaaaaaaaaaaaaaa') && is_dir($LibraryLoc.'/Shared/Team') && file_get_contents($base.'/outside/secret.txt') === 'secret' && removeAccountStorage('../..') === FALSE);


// Part 11. A request, as answerStorageAction() reads one. Every field is checked before anything is tried.
testRequest(array('StorageAction' => 'list', 'StorageLibrary' => 'Drive', 'StorageFolder' => ''));
list ($storageReply, $fileWasSent, $replyIsJudged) = answerStorageAction($storageSettings, 'list', $alice);
check('a list is answered with its entries & what the user may do', field($storageReply, 'Outcome') === 'listed' && field($storageReply, 'Entries') === array() && field($storageReply, 'Writable') === TRUE
  && field($storageReply, 'Purgeable') === TRUE && $fileWasSent === FALSE && $replyIsJudged === TRUE, json_encode($storageReply));
$fieldCases = array(array(array('StorageLibrary' => 'Dr:ive'), 'list', 'library-unknown'), array(array('StorageLibrary' => 'Elsewhere'), 'list', 'library-unknown'),
  array(array('StorageLibrary' => 'Drive', 'StorageFolder' => '../x'), 'list', 'folder-invalid'), array(array('StorageLibrary' => 'Drive', 'StorageFolder' => array('x')), 'list', 'folder-invalid'),
  array(array('StorageLibrary' => 'Drive', 'StorageNewName' => 'a/b'), 'folder', 'name-invalid'), array(array('StorageLibrary' => 'Drive', 'StorageNewName' => 'a&b;$c.txt'), 'folder', 'made'),
  array(array('StorageLibrary' => 'Drive', 'StorageName' => 'a&b;$c.txt', 'StorageNewName' => 'Ok'), 'rename', 'renamed'),
  array(array('StorageLibrary' => 'Drive', 'StorageName' => 'Ok', 'StorageDestinationFolder' => '../..'), 'move', 'folder-invalid'),
  array(array('StorageLibrary' => 'Drive', 'StorageName' => 'Ok'), 'copy', 'exists'), array(array('StorageLibrary' => 'Drive', 'StorageName' => 'Ok', 'StorageWhenTaken' => 'keep'), 'copy', 'copied'), array(array('StorageLibrary' => 'Drive', 'StorageName' => 'Ok', 'StorageDestinationLibrary' => 'Nowhere'), 'copy', 'library-unknown'),
  array(array('StorageLibrary' => 'Drive', 'StorageName' => 'Ok'), 'delete', 'trashed'), array(array('StorageLibrary' => 'Drive', 'StorageTrashId' => 'x'), 'restore', 'missing'),
  array(array('StorageLibrary' => 'Drive'), 'trash', 'trash-listed'), array(array('StorageLibrary' => 'Drive'), 'empty', 'emptied'),
  array(array('StorageLibrary' => 'Drive', 'StorageName' => 'none.txt'), 'download', 'missing'), array(array('StorageLibrary' => 'Drive'), 'upload', 'upload-failed'));
$wrongAnswers = array();
foreach ($fieldCases as $fieldCase) {
  testRequest($fieldCase[0]);
  list ($storageReply, $fileWasSent, $replyIsJudged) = answerStorageAction($storageSettings, $fieldCase[1], $alice);
  if (field($storageReply, 'Outcome') !== $fieldCase[2] or $replyIsJudged !== TRUE) $wrongAnswers[] = $fieldCase[1].' '.json_encode($fieldCase[0]).' was '.json_encode($storageReply); }
check('each action answers what its fields allow, & a name is used exactly as it was written', $wrongAnswers === array() && is_dir($LibraryLoc.'/Drive/aaaaaaaaaaaaaaaaaa/Files/Ok (2)'), implode("\n", $wrongAnswers));
testRequest(array());
list ($storageReply, $fileWasSent, $replyIsJudged) = answerStorageAction($storageSettings, 'libraries', $bob);
check('the libraries are listed with what each user may do, & how long the trash keeps what it holds', field($storageReply, 'Outcome') === 'libraries' && field($storageReply, 'TrashDays') === 30
  && json_encode(field($storageReply, 'Libraries')) === '[{"Name":"Drive","Kind":"personal","Writable":true,"Purgeable":true},{"Name":"Shared","Kind":"shared","Writable":false,"Purgeable":false}]',
  json_encode($storageReply));
testRequest(array('StorageAction' => 'list', 'StorageName' => "name\0", 'StorageTrashId' => '20261127061500-1a2b3c4d5e6f7a8b', 'StorageWhenTaken' => 'keep '));
check('a field is read only where it is exactly what it must be', storageInput('StorageAction', 'action') === array('list', TRUE) && storageInput('StorageName', 'name') === array('', FALSE)
  && storageInput('StorageTrashId', 'trash-id') === array('20261127061500-1a2b3c4d5e6f7a8b', TRUE) && storageInput('StorageWhenTaken', 'when-taken') === array('', FALSE)
  && storageInput('StorageMissing', 'library') === array('', FALSE) && storageInput('StorageFolder', 'folder') === array(array(), TRUE));


// Part 12. What -v says of each library. Nothing is made, changed or logged.
$storageSettings = testSettings(array('TestLibraries' => array('Drive' => array('Kind' => 'personal', 'Location' => ''), 'Fresh' => array('Kind' => 'personal', 'Location' => ''),
  'Mine' => array('Kind' => 'shared', 'Location' => $elsewhere, 'Writers' => 'none'), 'System' => array('Kind' => 'shared', 'Location' => '/etc/x')), 'TestTrashLifetime' => 0));
$GLOBALS['TestLog'] = array();
$ApacheUser = 'root';
$reportSections = storageReportSections();
$reportRows = array();
foreach ($reportSections['Libraries'] as $reportRow) $reportRows[] = $reportRow['Label'].': '.$reportRow['Text'].($reportRow['Failed'] ? ' (failed)' : '');
check('-v names a library in use, one made on first use, one of its own location, one refused & the trash, & logs & makes nothing', $reportRows === array(
  'Drive: OK, personal, '.$LibraryLoc.'/Drive', 'Fresh: OK, personal, made on first use at '.$LibraryLoc.'/Fresh', 'Mine: OK, shared, nobody writes, '.$elsewhere,
  'System: FAILED, its location /etc/x is in /etc, where the operating system keeps its own files (failed)', 'Trash: Kept until it is emptied') && $GLOBALS['TestLog'] === array()
  && !file_exists($LibraryLoc.'/Fresh'), implode("\n", $reportRows));

finish('test_storage');
