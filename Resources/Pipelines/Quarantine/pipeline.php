<?php
// / -----------------------------------------------------------------------------------
// / Copyright Information ...
// / HRProprietary Engine, Copyright on 11/16/2026 by Justin Grimes, www.github.com/zelon88
// /
// / License Information ...
// / This project is protected by the GNU GPLv3 Open-Source license.
// / https://www.gnu.org/licenses/gpl-3.0.html
// /
// / File Information ...
// / v4.3.1.
// / The Quarantine pipeline. It moves a file into a quarantine, lists what a quarantine holds, puts a file back & removes one.
// /
// / A quarantined file is kept under a name this pipeline gives it, beside a record that says where it came from,
// / who owned it, what its mode was & what it held, so putting it back is exact.
// / Every operation is handed the quarantine folder, so this pipeline names no application & no setting of one.
// /
// / It is written for root, which moves files out of & into folders other accounts own.
// / An account that owns a folder can rename anything in it, a folder included, & put a link in its place, between any two
// / things root does by name. So a file in such a folder is never reached by its whole path.
// / The kernel's enterFolderWithoutFollowing() walks to the folder holding it one name at a time & makes it the working folder
// / of the process, which is the folder itself & no longer a name anybody can change, & the file is reached by its own name from there.
// / A file is moved with a hard link & the removal of its old name, which never follows a link, never writes over a name that
// / exists & never copies. PHP hands each of those to the system as the name was written, so each acts inside the folder held.
// / PHP does not do that for fopen, which it hands a whole path. So an open handle is always compared with what was examined,
// / by device & inode, before a byte is read through it, & a file is never created by name in a folder that is only held that way.
// / The kernel's functions do the rest. folderIsHeldForRoot() judges a folder, writeFileWithoutFollowing() writes a record,
// / readFileWithoutFollowing() reads one, removeFileWithoutFollowing() removes a name & changeOwnerWithoutFollowing() changes an owner.
// / HRProtect's Documentation/ABOUT_QUARANTINE.txt says how one application uses it.
// /
// / <3 Open-Source
// / -----------------------------------------------------------------------------------


// / -----------------------------------------------------------------------------------
// / A component may only be loaded by the core.
if (!isset($CoreLoaded) or $CoreLoaded !== TRUE) die('ERROR!!! HRConvert2-34000, A pipeline cannot be loaded directly!');
// / -----------------------------------------------------------------------------------


// / -----------------------------------------------------------------------------------
// / A function to say whether text is an identifier this pipeline gives a quarantined file.
// / Accepts the text. Returns TRUE when it is one.
// / An identifier is the date, the time & twelve hexadecimal digits, as 20261116-064500-0123456789ab, & nothing after them, a line break included.
// / It becomes part of a file name, so anything else is refused before it is used as one.
function quarantineIdIsValid($quarantineId) {
  // / Set variables.
  global $EnableMemoryProtection;
  $IdIsValid = (is_string($quarantineId) && preg_match('/^[0-9]{8}-[0-9]{6}-[a-f0-9]{12}$/D', $quarantineId) === 1);
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $quarantineId);
  return $IdIsValid; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to copy what an open file holds into a new file.
// / Accepts the open handle to read, the name to create & the mode it is created with, in that order.
// / Returns whether every byte was copied, how many were & the reason for a failure, in that order.
// / The new file is opened with x, which fails on any name that already exists, a link included, so nothing is ever written through one.
// / The mode is set by the umask as the file is created, so nothing is changed by name afterwards.
// / The handle to read is the caller's, which has already compared it with the file it examined, & is left open.
// / A copy that stopped short is removed, because a part of a file is not the file.
function copyHandleToNewFile($sourceHandle, $targetPath, $fileMode) {
  // / Set variables.
  global $EnableMemoryProtection;
  $FileWasCopied = FALSE;
  $BytesCopied = 0;
  $CopyNotice = '';
  $targetHandle = FALSE;
  $previousMask = 0;
  $copiedCount = FALSE;
  $previousMask = umask(0777 & ~((int)$fileMode));
  $targetHandle = @fopen((string)$targetPath, 'x');
  umask($previousMask);
  if (!is_resource($sourceHandle)) $CopyNotice = 'There was nothing open to copy from.';
  else if ($targetHandle === FALSE) $CopyNotice = 'A new file could not be created at '.printableText($targetPath, TRUE).'.';
  else {
    $copiedCount = @stream_copy_to_stream($sourceHandle, $targetHandle);
    if ($copiedCount === FALSE or !@fflush($targetHandle)) $CopyNotice = 'The copy at '.printableText($targetPath, TRUE).' could not be written in full.';
    else {
      $BytesCopied = (int)$copiedCount;
      $FileWasCopied = TRUE; } }
  if (is_resource($targetHandle)) @fclose($targetHandle);
  // / A name this call created & could not fill is removed. Removing a name never follows it.
  if (!$FileWasCopied && $targetHandle !== FALSE) @unlink((string)$targetPath);
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  // / $sourceHandle is the caller's, which holds it open, so letting go of it here closes nothing.
  purgeSensitiveMemory($EnableMemoryProtection, $targetHandle, $previousMask, $copiedCount, $sourceHandle, $targetPath, $fileMode);
  return array($FileWasCopied, $BytesCopied, $CopyNotice); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to read the record of one quarantined file.
// / Accepts the quarantine folder & the identifier, in that order.
// / Returns whether the record was read, the record & the reason it was not, in that order.
// / A record is JSON, written by quarantineFile(), & every field is checked before it is believed, because a record decides
// / where root puts a file & who it gives it to.
// / The path a file came from is kept twice. OriginalPath is what a person reads, with every control character replaced, &
// / OriginalPathEncoded is the path byte for byte, in base64, because a name on disk may hold bytes JSON cannot.
// / The record gains StoredPath & StoredExists, which say where the file is kept now & whether it is still there.
function readQuarantineRecord($quarantineFolder, $quarantineId) {
  // / Set variables.
  global $EnableMemoryProtection;
  $RecordWasRead = FALSE;
  $QuarantineRecord = array();
  $RecordNotice = '';
  $recordPath = $recordText = $readNotice = $originalPath = '';
  $fileWasRead = FALSE;
  $decodedRecord = NULL;
  $quarantineFolder = rtrim((string)$quarantineFolder, DIRECTORY_SEPARATOR);
  if (!quarantineIdIsValid($quarantineId)) $RecordNotice = 'That is not the identifier of a quarantined file.';
  else {
    $recordPath = $quarantineFolder.DIRECTORY_SEPARATOR.$quarantineId.'.json';
    list ($fileWasRead, $recordText, $readNotice) = readFileWithoutFollowing($recordPath, 1048576);
    if (!$fileWasRead) $RecordNotice = 'The quarantine holds no record for '.$quarantineId.'. '.$readNotice;
    else {
      $decodedRecord = json_decode($recordText, TRUE);
      $originalPath = (is_array($decodedRecord) && isset($decodedRecord['OriginalPathEncoded']) && is_string($decodedRecord['OriginalPathEncoded'])) ? (string)base64_decode($decodedRecord['OriginalPathEncoded'], TRUE) : '';
      if (!is_array($decodedRecord) or ($decodedRecord['Id'] ?? '') !== $quarantineId) $RecordNotice = 'The record for '.$quarantineId.' could not be read as a record.';
      else if ($originalPath === '' or substr($originalPath, 0, 1) !== DIRECTORY_SEPARATOR or strpos($originalPath, "\0") !== FALSE or basename($originalPath) === '') $RecordNotice = 'The record for '.$quarantineId.' does not say where the file came from.';
      else if (!is_int($decodedRecord['Owner'] ?? NULL) or !is_int($decodedRecord['Group'] ?? NULL) or !is_int($decodedRecord['Mode'] ?? NULL) or !is_int($decodedRecord['Size'] ?? NULL) or !is_int($decodedRecord['Modified'] ?? NULL) or !is_string($decodedRecord['Sha256'] ?? NULL) or preg_match('/^[a-f0-9]{64}$/D', $decodedRecord['Sha256']) !== 1) $RecordNotice = 'The record for '.$quarantineId.' is missing what a file is put back with.';
      else {
        $QuarantineRecord = $decodedRecord;
        $QuarantineRecord['OriginalPathBytes'] = $originalPath;
        $QuarantineRecord['OriginalPath'] = printableText($originalPath, TRUE);
        $QuarantineRecord['StoredPath'] = $quarantineFolder.DIRECTORY_SEPARATOR.$quarantineId.'.quarantined';
        clearstatcache(TRUE);
        $QuarantineRecord['StoredExists'] = is_array(@lstat($QuarantineRecord['StoredPath']));
        $RecordWasRead = TRUE; } } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  // / $QuarantineRecord is not purged, because it is a return value.
  purgeSensitiveMemory($EnableMemoryProtection, $recordPath, $recordText, $readNotice, $originalPath, $fileWasRead, $decodedRecord, $quarantineFolder, $quarantineId);
  return array($RecordWasRead, $QuarantineRecord, $RecordNotice); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to list what a quarantine holds.
// / Accepts the quarantine folder. Returns whether the folder was read, every record in it & what it holds besides, in that order.
// / The records are in the order they were quarantined, because an identifier opens with the date & the time.
// / What it holds besides is the name of every file there that is neither a record nor a quarantined file with a record,
// / so a record that cannot be read & a file with no record are both named rather than passed over.
function listQuarantinedFiles($quarantineFolder) {
  // / Set variables.
  global $EnableMemoryProtection;
  $ListWasRead = FALSE;
  $QuarantineRecords = $UnknownEntries = array();
  $folderEntries = $quarantineRecord = $knownNames = array();
  $folderEntry = $entryId = $recordNotice = '';
  $recordWasRead = FALSE;
  $quarantineFolder = rtrim((string)$quarantineFolder, DIRECTORY_SEPARATOR);
  $folderEntries = @scandir($quarantineFolder);
  if (is_array($folderEntries)) {
    $ListWasRead = TRUE;
    sort($folderEntries, SORT_STRING);
    foreach ($folderEntries as $folderEntry) {
      if (substr($folderEntry, -5) !== '.json') continue;
      redeclare($entryId, substr($folderEntry, 0, -5));
      list ($recordWasRead, $quarantineRecord, $recordNotice) = readQuarantineRecord($quarantineFolder, $entryId);
      if (!$recordWasRead) continue;
      $QuarantineRecords[] = $quarantineRecord;
      $knownNames[] = $folderEntry;
      $knownNames[] = $entryId.'.quarantined'; }
    foreach ($folderEntries as $folderEntry) if ($folderEntry !== '.' && $folderEntry !== '..' && $folderEntry !== 'index.html' && !in_array($folderEntry, $knownNames, TRUE)) $UnknownEntries[] = printableText($folderEntry, TRUE); }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $folderEntries, $quarantineRecord, $knownNames, $folderEntry, $entryId, $recordNotice, $recordWasRead, $quarantineFolder);
  return array($ListWasRead, $QuarantineRecords, $UnknownEntries); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to say whether a quarantine is a folder root alone can open.
// / Accepts the quarantine folder. Returns whether it is & the reason it is not, in that order.
// / Everything this pipeline does rests on the answer. A quarantined file is out of reach because of the folder it is in,
// / so the folder must be one nothing but root can replace, must belong to root & must give no other account a way in.
// / Only root is served, because only root can take a file out of a folder another account owns & give it back as it was.
function quarantineIsRootsAlone($quarantineFolder) {
  // / Set variables.
  global $EnableMemoryProtection;
  $QuarantineIsPrivate = FALSE;
  $PrivacyNotice = $holdNotice = '';
  $folderIsHeld = FALSE;
  $folderStatus = array();
  $quarantineFolder = rtrim((string)$quarantineFolder, DIRECTORY_SEPARATOR);
  if ($quarantineFolder !== '') list ($folderIsHeld, $holdNotice) = folderIsHeldForRoot($quarantineFolder);
  clearstatcache(TRUE, $quarantineFolder);
  $folderStatus = @lstat($quarantineFolder);
  if (!function_exists('posix_geteuid') or posix_geteuid() !== 0) $PrivacyNotice = 'A quarantine is kept by root, & this is not root.';
  else if (!$folderIsHeld) $PrivacyNotice = rtrim('The quarantine at '.$quarantineFolder.' is not a folder only root can replace. '.$holdNotice);
  else if (!is_array($folderStatus) or ((int)$folderStatus['mode'] & 0170000) !== 0040000) $PrivacyNotice = 'The quarantine at '.$quarantineFolder.' is not a folder.';
  else if ((int)$folderStatus['uid'] !== 0 or ((int)$folderStatus['mode'] & 0077) !== 0) $PrivacyNotice = 'The quarantine at '.$quarantineFolder.' can be opened by an account other than root.';
  else $QuarantineIsPrivate = TRUE;
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $holdNotice, $folderIsHeld, $folderStatus, $quarantineFolder);
  return array($QuarantineIsPrivate, $PrivacyNotice); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to move one file into a quarantine.
// / Accepts the quarantine folder, the absolute path of the file, what the file was when it was judged & what the caller wants kept with it, in that order.
// / Returns whether the file is now in the quarantine, its identifier & what a person should be told, in that order.
// /
// / What the file was is its Device, Inode, Size & Modified time, as lstat gave them when it was read to be judged. A file that
// / is no longer that file was not the one judged, so it is left where it is. An empty list holds the file to nothing.
// / What the caller wants kept is written into the record as Details, & this pipeline never reads it.
// /
// / The quarantine must pass quarantineIsRootsAlone(). The folder holding the file is entered with the kernel's
// / enterFolderWithoutFollowing(), & the file is reached by its own name from there. Only a plain file is moved, because a link is not a file.
// / A file with one name is given a second name in the quarantine & its first name is removed, so it is never copied & never in two states.
// / A file with more than one name is copied instead & the one name is removed, so its other names are left exactly as they are, each to be judged on its own.
// / So is a file on another filesystem, which cannot be given a name on this one. A copy is read through a handle compared with what was examined.
// / The record is written before the name is removed, so a record that cannot be written leaves the file exactly as it was, where it was.
// / A quarantined file belongs to root & is 0600, whoever owned it, as everything in a folder of the root-only role does.
// / Linux removes a file capability when an owner changes, so one is not kept.
function quarantineFile($quarantineFolder, $sourcePath, $expectedIdentity, $recordDetails) {
  // / Set variables.
  global $EnableMemoryProtection;
  $FileWasQuarantined = FALSE;
  $QuarantineId = $QuarantineNotice = '';
  $sourceFolder = $sourceName = $previousFolder = $privacyNotice = $entryNotice = $storedPath = $recordPath = $recordText = $storedHash = $copyNotice = $writeNotice = $ownerNotice = $identityField = $statusField = $shownPath = '';
  $quarantineIsPrivate = $folderWasEntered = $fileIsStored = $fileWasLinked = $fileWasCopied = $recordWasWritten = $ownerWasChanged = $identityMatches = FALSE;
  $sourceStatus = $storedStatus = $handleStatus = $recheckStatus = $quarantineRecord = $identityFields = array();
  $sourceHandle = FALSE;
  $bytesCopied = 0;
  $quarantineFolder = rtrim((string)$quarantineFolder, DIRECTORY_SEPARATOR);
  $sourceFolder = dirname((string)$sourcePath);
  $sourceName = basename((string)$sourcePath);
  $shownPath = printableText($sourcePath, TRUE);
  $previousFolder = (string)@getcwd();
  $identityFields = array('Device' => 'dev', 'Inode' => 'ino', 'Size' => 'size', 'Modified' => 'mtime');
  list ($quarantineIsPrivate, $privacyNotice) = quarantineIsRootsAlone($quarantineFolder);
  if (!$quarantineIsPrivate) $QuarantineNotice = $privacyNotice;
  else if (substr((string)$sourcePath, 0, 1) !== DIRECTORY_SEPARATOR or $sourceName === '' or $sourceName === '.' or $sourceName === '..') $QuarantineNotice = $shownPath.' is not the absolute path of a file.';
  else {
    list ($folderWasEntered, $entryNotice) = enterFolderWithoutFollowing($sourceFolder);
    if (!$folderWasEntered) $QuarantineNotice = $shownPath.' was left where it is, because the folder holding it could not be held. '.printableText($entryNotice, TRUE);
    else {
      clearstatcache(TRUE);
      $sourceStatus = @lstat($sourceName);
      $identityMatches = is_array($sourceStatus);
      if (is_array($sourceStatus) && is_array($expectedIdentity)) foreach ($identityFields as $identityField => $statusField) if (isset($expectedIdentity[$identityField]) && $expectedIdentity[$identityField] !== $sourceStatus[$statusField]) $identityMatches = FALSE;
      if (!is_array($sourceStatus)) $QuarantineNotice = $shownPath.' is no longer there.';
      else if (((int)$sourceStatus['mode'] & 0170000) !== 0100000) $QuarantineNotice = $shownPath.' is not a plain file, so it was left where it is.';
      else if (!$identityMatches) $QuarantineNotice = $shownPath.' changed after it was judged, so it was left where it is. Scan it again.';
      else {
        $QuarantineId = date('Ymd-His').'-'.bin2hex(random_bytes(6));
        $storedPath = $quarantineFolder.DIRECTORY_SEPARATOR.$QuarantineId.'.quarantined';
        $recordPath = $quarantineFolder.DIRECTORY_SEPARATOR.$QuarantineId.'.json';
        // / A file with one name is given a second. That fails cleanly where the quarantine is on another filesystem, & then the file is copied.
        if ((int)$sourceStatus['nlink'] === 1) $fileWasLinked = @link($sourceName, $storedPath);
        if (!$fileWasLinked) {
          $sourceHandle = @fopen($sourceName, 'rn');
          if (is_resource($sourceHandle)) $handleStatus = @fstat($sourceHandle);
          if (!is_resource($sourceHandle) or !is_array($handleStatus) or $handleStatus['dev'] !== $sourceStatus['dev'] or $handleStatus['ino'] !== $sourceStatus['ino'] or ((int)$handleStatus['mode'] & 0170000) !== 0100000) $copyNotice = 'It changed while it was being opened.';
          else list ($fileWasCopied, $bytesCopied, $copyNotice) = copyHandleToNewFile($sourceHandle, $storedPath, 0600);
          if (is_resource($sourceHandle)) @fclose($sourceHandle);
          if ($fileWasCopied && $bytesCopied !== (int)$sourceStatus['size']) {
            $fileWasCopied = FALSE;
            $copyNotice = 'It changed size while it was being copied.';
            @unlink($storedPath); }
          // / A copy is given the time the file was last changed, so it goes back as it came.
          if ($fileWasCopied) @touch($storedPath, (int)$sourceStatus['mtime']); }
        // / What is now in the quarantine must be the file that was examined, or a whole copy of it.
        clearstatcache(TRUE);
        $storedStatus = @lstat($storedPath);
        $fileIsStored = (is_array($storedStatus) && ((int)$storedStatus['mode'] & 0170000) === 0100000 && ($fileWasCopied or ($fileWasLinked && $storedStatus['dev'] === $sourceStatus['dev'] && $storedStatus['ino'] === $sourceStatus['ino'])));
        if (!$fileIsStored) {
          if (is_array($storedStatus)) @unlink($storedPath);
          $QuarantineNotice = rtrim($shownPath.' could not be moved into the quarantine, so it was left where it is. '.$copyNotice);
          $QuarantineId = ''; }
        else {
          $storedHash = (string)@hash_file('sha256', $storedPath);
          $quarantineRecord = array(
            'Id' => $QuarantineId,
            'OriginalPath' => $shownPath,
            'OriginalPathEncoded' => base64_encode((string)$sourcePath),
            'Owner' => (int)$sourceStatus['uid'],
            'Group' => (int)$sourceStatus['gid'],
            'Mode' => ((int)$sourceStatus['mode'] & 07777),
            'Size' => (int)$sourceStatus['size'],
            'Modified' => (int)$sourceStatus['mtime'],
            'Sha256' => $storedHash,
            'QuarantinedAt' => time(),
            'Method' => $fileWasLinked ? 'moved' : 'copied',
            'OtherNames' => max(0, (int)$sourceStatus['nlink'] - 1),
            'Details' => is_array($recordDetails) ? $recordDetails : array());
          $recordText = (string)json_encode($quarantineRecord, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);
          if (preg_match('/^[a-f0-9]{64}$/D', $storedHash) === 1 && $recordText !== '') list ($recordWasWritten, $writeNotice) = writeFileWithoutFollowing($recordPath, $recordText.PHP_EOL, 0600);
          // / The name goes only once the record is safe. It is removed by name from the folder held, which never follows a link.
          clearstatcache(TRUE);
          $recheckStatus = @lstat($sourceName);
          if ($recordWasWritten && is_array($recheckStatus) && $recheckStatus['dev'] === $sourceStatus['dev'] && $recheckStatus['ino'] === $sourceStatus['ino'] && @unlink($sourceName)) $FileWasQuarantined = TRUE;
          if (!$FileWasQuarantined) {
            // / Nothing was taken from where it was, so what was made in the quarantine is taken back out of it.
            @unlink($storedPath);
            if ($recordWasWritten) @unlink($recordPath);
            $QuarantineNotice = rtrim($shownPath.' was left where it is, because '.($recordWasWritten ? 'its name could not be removed once it was in the quarantine.' : 'its record could not be written. '.$writeNotice));
            $QuarantineId = ''; }
          else {
            // / The file is root's own from here, whoever owned it. A copy already is.
            if ($fileWasLinked) list ($ownerWasChanged, $ownerNotice) = changeOwnerWithoutFollowing($storedPath, 'root', 'root');
            @chmod($storedPath, 0600);
            if ($fileWasLinked && !$ownerWasChanged) $QuarantineNotice = 'The quarantined file kept the owner it had. '.$ownerNotice;
            else if ((int)$sourceStatus['nlink'] > 1) $QuarantineNotice = 'It has '.((int)$sourceStatus['nlink'] - 1).' other name(s), which were left as they are. This name was removed & a copy is kept.'; } } } }
    if ($previousFolder === '' or !@chdir($previousFolder)) @chdir(DIRECTORY_SEPARATOR); }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $sourceFolder, $sourceName, $previousFolder, $privacyNotice, $entryNotice, $storedPath, $recordPath, $recordText, $storedHash, $copyNotice, $writeNotice, $ownerNotice, $identityField, $statusField, $shownPath, $quarantineIsPrivate, $folderWasEntered, $fileIsStored, $fileWasLinked, $fileWasCopied, $recordWasWritten, $ownerWasChanged, $identityMatches, $sourceStatus, $storedStatus, $handleStatus, $recheckStatus, $quarantineRecord, $identityFields, $sourceHandle, $bytesCopied, $quarantineFolder, $sourcePath, $expectedIdentity, $recordDetails);
  return array($FileWasQuarantined, $QuarantineId, $QuarantineNotice); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to put one quarantined file back.
// / Accepts the quarantine folder, the identifier & the folder to put the file in, in that order.
// / Returns whether the file was put back, the path it now has & what a person should be told, in that order.
// / An empty folder means the folder the file came from. The file keeps the name it had.
// /
// / Nothing is ever written over. A name that already exists stops the restore.
// / The file is given back its owner, its group & its mode while it is still in the quarantine, where no other account can reach it.
// / It is then given its name where it belongs, from a folder entered with the kernel's enterFolderWithoutFollowing(), so it arrives
// / exactly as it left & nothing above it is followed. An account with no name on this host cannot be given a file by name, so
// / that file goes back as root's & 0600, & the caller is told what it was.
// / A file cannot be given a name on another filesystem, so there it is copied, into a name that must not exist.
// / A copy that comes out shorter than the file kept is removed again & the file stays in the quarantine, because the copy is all there would be.
// / PHP creates a file by its whole path, so a copy is made only in a folder folderIsHeldForRoot() holds, which nothing but root can replace.
// / It is given its owner & mode only where that folder is root's alone as well, because only there can nobody swap the name between
// / the two steps. Anywhere else it stays root's & 0600, & the caller is told to finish by hand.
// / A file that is not as it was when it was quarantined is still put back, & the caller is told, because something that had it
// / open may have gone on writing to it & the quarantine is the only place it is kept.
function restoreQuarantinedFile($quarantineFolder, $quarantineId, $destinationFolder) {
  // / Set variables.
  global $EnableMemoryProtection;
  $FileWasRestored = FALSE;
  $RestoredPath = $RestoreNotice = '';
  $recordNotice = $privacyNotice = $entryNotice = $holdNotice = $storedPath = $recordPath = $targetFolder = $targetName = $targetPath = $previousFolder = $ownerName = $groupName = $ownerNotice = $copyNotice = $removalNotice = $handNotice = '';
  $recordWasRead = $quarantineIsPrivate = $folderWasEntered = $ownerWasChanged = $ownerWasReverted = $fileWasLinked = $fileWasCopied = $targetIsHeld = $folderIsRootsAlone = $recordWasRemoved = $storedWasRemoved = $contentChanged = FALSE;
  $quarantineRecord = $storedStatus = $handleStatus = $hereStatus = $ownerRecord = $groupRecord = array();
  $storedHandle = FALSE;
  $bytesCopied = 0;
  $quarantineFolder = rtrim((string)$quarantineFolder, DIRECTORY_SEPARATOR);
  $previousFolder = (string)@getcwd();
  list ($quarantineIsPrivate, $privacyNotice) = quarantineIsRootsAlone($quarantineFolder);
  if ($quarantineIsPrivate) list ($recordWasRead, $quarantineRecord, $recordNotice) = readQuarantineRecord($quarantineFolder, $quarantineId);
  if (!$quarantineIsPrivate) $RestoreNotice = $privacyNotice;
  else if (!$recordWasRead) $RestoreNotice = $recordNotice;
  else {
    $storedPath = (string)$quarantineRecord['StoredPath'];
    $recordPath = $quarantineFolder.DIRECTORY_SEPARATOR.$quarantineRecord['Id'].'.json';
    $targetFolder = (trim((string)$destinationFolder) !== '') ? rtrim((string)$destinationFolder, DIRECTORY_SEPARATOR) : dirname((string)$quarantineRecord['OriginalPathBytes']);
    if ($targetFolder === '') $targetFolder = DIRECTORY_SEPARATOR;
    $targetName = basename((string)$quarantineRecord['OriginalPathBytes']);
    $targetPath = rtrim($targetFolder, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.$targetName;
    clearstatcache(TRUE);
    $storedStatus = @lstat($storedPath);
    if (!is_array($storedStatus) or ((int)$storedStatus['mode'] & 0170000) !== 0100000) $RestoreNotice = 'The quarantine no longer holds the file for '.$quarantineRecord['Id'].'.';
    else {
      $contentChanged = ((string)@hash_file('sha256', $storedPath) !== (string)$quarantineRecord['Sha256']);
      list ($folderWasEntered, $entryNotice) = enterFolderWithoutFollowing($targetFolder);
      clearstatcache(TRUE);
      if (!$folderWasEntered) $RestoreNotice = 'The file was not put back, because '.printableText($targetFolder, TRUE).' could not be held. '.printableText($entryNotice, TRUE);
      else if (is_array(@lstat($targetName))) $RestoreNotice = 'The file was not put back, because '.printableText($targetPath, TRUE).' already exists & nothing is written over.';
      else {
        // / The owner & the mode go back on while the file is still where only root can reach it.
        if (function_exists('posix_getpwuid')) $ownerRecord = @posix_getpwuid((int)$quarantineRecord['Owner']);
        if (function_exists('posix_getgrgid')) $groupRecord = @posix_getgrgid((int)$quarantineRecord['Group']);
        $ownerName = (is_array($ownerRecord) && isset($ownerRecord['name'])) ? (string)$ownerRecord['name'] : '';
        $groupName = (is_array($groupRecord) && isset($groupRecord['name'])) ? (string)$groupRecord['name'] : '';
        if ($ownerName !== '' && $groupName !== '') list ($ownerWasChanged, $ownerNotice) = changeOwnerWithoutFollowing($storedPath, $ownerName, $groupName);
        if ($ownerWasChanged) @chmod($storedPath, ((int)$quarantineRecord['Mode'] & 07777));
        // / A second name where the file belongs, which fails where a name is already there & where the folder is on another filesystem.
        $fileWasLinked = @link($storedPath, $targetName);
        if (!$fileWasLinked) {
          // / The file kept goes back to being root's own before anything else, so a restore that stops here leaves the quarantine as it was.
          if ($ownerWasChanged) list ($ownerWasReverted, $ownerNotice) = changeOwnerWithoutFollowing($storedPath, 'root', 'root');
          @chmod($storedPath, 0600);
          $ownerWasChanged = FALSE;
          list ($targetIsHeld, $holdNotice) = folderIsHeldForRoot($targetFolder);
          if (!$targetIsHeld) $copyNotice = 'It could not be given a name there, & a file is never created by name in a folder another account could replace. '.printableText($holdNotice, TRUE).' Put it back into a folder only root can change, & move it from there by hand.';
          else {
            $storedHandle = @fopen($storedPath, 'rn');
            if (is_resource($storedHandle)) $handleStatus = @fstat($storedHandle);
            if (!is_resource($storedHandle) or !is_array($handleStatus) or $handleStatus['dev'] !== $storedStatus['dev'] or $handleStatus['ino'] !== $storedStatus['ino']) $copyNotice = 'The file kept could not be opened.';
            else list ($fileWasCopied, $bytesCopied, $copyNotice) = copyHandleToNewFile($storedHandle, $targetPath, 0600);
            if (is_resource($storedHandle)) @fclose($storedHandle);
            // / A copy shorter than the file kept is not the file, so it is taken away again & the quarantine keeps what it has.
            // / A read that fails partway looks like the end of the file to PHP, so the count is the only thing that says so.
            // / A longer copy is the file with what was written to it since, which goes back as a file that is moved does.
            if ($fileWasCopied && $bytesCopied < (int)$handleStatus['size']) {
              $fileWasCopied = FALSE;
              $copyNotice = 'It could not be read whole, so the copy that was begun was taken away again.';
              @unlink($targetName); }
            // / A copy is given its owner, its mode & its time by name only where nobody but root can change the folder it is in.
            clearstatcache(TRUE);
            $hereStatus = @stat('.');
            $folderIsRootsAlone = (is_array($hereStatus) && (int)$hereStatus['uid'] === 0 && ((int)$hereStatus['mode'] & 0022) === 0);
            if ($fileWasCopied && $folderIsRootsAlone && $ownerName !== '' && $groupName !== '') {
              list ($ownerWasChanged, $ownerNotice) = changeOwnerWithoutFollowing($targetPath, $ownerName, $groupName);
              if ($ownerWasChanged) @chmod($targetPath, ((int)$quarantineRecord['Mode'] & 07777));
              @touch($targetPath, (int)$quarantineRecord['Modified']); } } }
        if (!$fileWasLinked && !$fileWasCopied) $RestoreNotice = 'The file was not put back. '.$copyNotice;
        else {
          $FileWasRestored = TRUE;
          $RestoredPath = $targetPath;
          // / The file is where it belongs, so what the quarantine kept of it goes. A name that will not go is said, & the file stays put back.
          list ($storedWasRemoved, $removalNotice) = removeFileWithoutFollowing($storedPath);
          list ($recordWasRemoved, $removalNotice) = removeFileWithoutFollowing($recordPath);
          $handNotice = 'Give it the owner '.(int)$quarantineRecord['Owner'].', the group '.(int)$quarantineRecord['Group'].' & the mode '.sprintf('%04o', (int)$quarantineRecord['Mode']).' by hand.';
          if (!$ownerWasChanged && ($ownerName === '' or $groupName === '')) $RestoreNotice = 'It belongs to root & only root can read it, because the account or the group that owned it has no name on this host. '.$handNotice;
          else if (!$ownerWasChanged) $RestoreNotice = rtrim('It belongs to root & only root can read it, because it could not be given back safely where it is. '.$handNotice.' '.$ownerNotice);
          if ($contentChanged) $RestoreNotice = trim($RestoreNotice.' It is not as it was when it was quarantined. Something that had it open may have written to it since.');
          if (!$storedWasRemoved or !$recordWasRemoved) $RestoreNotice = trim($RestoreNotice.' What the quarantine kept of it could not be removed, so it is still listed.'); } } }
    if ($previousFolder === '' or !@chdir($previousFolder)) @chdir(DIRECTORY_SEPARATOR); }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $recordNotice, $privacyNotice, $entryNotice, $holdNotice, $storedPath, $recordPath, $targetFolder, $targetName, $targetPath, $previousFolder, $ownerName, $groupName, $ownerNotice, $copyNotice, $removalNotice, $handNotice, $recordWasRead, $quarantineIsPrivate, $folderWasEntered, $ownerWasChanged, $ownerWasReverted, $fileWasLinked, $fileWasCopied, $targetIsHeld, $folderIsRootsAlone, $recordWasRemoved, $storedWasRemoved, $contentChanged, $quarantineRecord, $storedStatus, $handleStatus, $hereStatus, $ownerRecord, $groupRecord, $storedHandle, $bytesCopied, $quarantineFolder, $quarantineId, $destinationFolder);
  return array($FileWasRestored, $RestoredPath, $RestoreNotice); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to remove one quarantined file for good.
// / Accepts the quarantine folder & the identifier, in that order.
// / Returns whether nothing of it is left & the reason something is, in that order.
// / The file & its record are removed by the kernel's removeFileWithoutFollowing(), which removes a name & never follows one.
// / The file goes first, so a purge that stops halfway leaves a record that still says what the file was.
// / A file whose record cannot be read is still removed by its identifier, because a record nobody can read should not keep a threat on disk.
// / Nothing is overwritten before it is removed. What a disk does with a removed file's blocks is the disk's own business.
function purgeQuarantinedFile($quarantineFolder, $quarantineId) {
  // / Set variables.
  global $EnableMemoryProtection;
  $FileWasPurged = FALSE;
  $PurgeNotice = '';
  $storedPath = $recordPath = $privacyNotice = $storedNotice = $recordNotice = '';
  $quarantineIsPrivate = $storedWasRemoved = $recordWasRemoved = FALSE;
  $quarantineFolder = rtrim((string)$quarantineFolder, DIRECTORY_SEPARATOR);
  list ($quarantineIsPrivate, $privacyNotice) = quarantineIsRootsAlone($quarantineFolder);
  if (!quarantineIdIsValid($quarantineId)) $PurgeNotice = 'That is not the identifier of a quarantined file.';
  else if (!$quarantineIsPrivate) $PurgeNotice = $privacyNotice;
  else {
    $storedPath = $quarantineFolder.DIRECTORY_SEPARATOR.$quarantineId.'.quarantined';
    $recordPath = $quarantineFolder.DIRECTORY_SEPARATOR.$quarantineId.'.json';
    clearstatcache(TRUE);
    if (!is_array(@lstat($storedPath)) && !is_array(@lstat($recordPath))) $PurgeNotice = 'The quarantine holds nothing for '.$quarantineId.'.';
    else {
      list ($storedWasRemoved, $storedNotice) = removeFileWithoutFollowing($storedPath);
      if ($storedWasRemoved) list ($recordWasRemoved, $recordNotice) = removeFileWithoutFollowing($recordPath);
      if ($storedWasRemoved && $recordWasRemoved) $FileWasPurged = TRUE;
      else $PurgeNotice = trim($storedNotice.' '.$recordNotice); } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $storedPath, $recordPath, $privacyNotice, $storedNotice, $recordNotice, $quarantineIsPrivate, $storedWasRemoved, $recordWasRemoved, $quarantineFolder, $quarantineId);
  return array($FileWasPurged, $PurgeNotice); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to carry out one quarantine operation, which is the entry point the application calls.
// / Accepts the operation, the quarantine folder & what the operation needs, in that order.
// / Returns whether the operation succeeded, what it produced & what a person should be told, in that order.
// /   quarantine  Needs Path, & may carry Identity & Details. It produces the identifier.
// /   list        Needs nothing. It produces Records & Unknown, the records & the names of whatever else is in the folder.
// /   read        Needs Id. It produces the record.
// /   restore     Needs Id, & may carry Folder, the folder to put the file in. It produces the path the file now has.
// /   purge       Needs Id. It produces nothing.
// / An operation it does not know is refused, & says so.
function operateOnQuarantine($requestedOperation, $quarantineFolder, $operationArguments) {
  // / Set variables.
  global $EnableMemoryProtection;
  $OperationSucceeded = FALSE;
  $OperationResult = '';
  $OperationNotice = '';
  $quarantineRecords = $unknownEntries = array();
  if (!is_array($operationArguments)) $operationArguments = array();
  if ($requestedOperation === 'quarantine') list ($OperationSucceeded, $OperationResult, $OperationNotice) = quarantineFile($quarantineFolder, (string)($operationArguments['Path'] ?? ''), ($operationArguments['Identity'] ?? array()), ($operationArguments['Details'] ?? array()));
  else if ($requestedOperation === 'list') {
    list ($OperationSucceeded, $quarantineRecords, $unknownEntries) = listQuarantinedFiles($quarantineFolder);
    $OperationResult = array('Records' => $quarantineRecords, 'Unknown' => $unknownEntries);
    if (!$OperationSucceeded) $OperationNotice = 'The quarantine at '.$quarantineFolder.' could not be read.'; }
  else if ($requestedOperation === 'read') list ($OperationSucceeded, $OperationResult, $OperationNotice) = readQuarantineRecord($quarantineFolder, (string)($operationArguments['Id'] ?? ''));
  else if ($requestedOperation === 'restore') list ($OperationSucceeded, $OperationResult, $OperationNotice) = restoreQuarantinedFile($quarantineFolder, (string)($operationArguments['Id'] ?? ''), (string)($operationArguments['Folder'] ?? ''));
  else if ($requestedOperation === 'purge') list ($OperationSucceeded, $OperationNotice) = purgeQuarantinedFile($quarantineFolder, (string)($operationArguments['Id'] ?? ''));
  else $OperationNotice = 'The Quarantine pipeline has no operation called '.printableText($requestedOperation, TRUE).'.';
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  // / $OperationResult is not purged, because it is a return value.
  purgeSensitiveMemory($EnableMemoryProtection, $quarantineRecords, $unknownEntries, $requestedOperation, $quarantineFolder, $operationArguments);
  return array($OperationSucceeded, $OperationResult, $OperationNotice); }
// / -----------------------------------------------------------------------------------
?>
