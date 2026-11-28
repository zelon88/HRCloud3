<?php
// / -----------------------------------------------------------------------------------
// / Copyright Information ...
// / ScanCore, Copyright on 3/31/2024 by Justin Grimes, www.github.com/zelon88
// /
// / License Information ...
// / This project is protected by the GNU GPLv3 Open-Source license.
// / BSD or MIT licensing is available. Reach out to @zelon88 for more information.
// / https://www.gnu.org/licenses/gpl-3.0.html
// /
// / Application Information ...
// / This application is designed to scan files & folders for viruses.
// /
// / File Information ...
// / v2.0.0.
// / ScanCore Behaviour Test.
// / This file runs ScanCore.php the way a caller does & checks what it prints, what it writes & how it exits.
// / Every file it scans is built here, in a scratch folder that is removed afterwards.
// / Nothing is written to the installation, & no real malware & no antivirus test file is ever written anywhere.
// / Every answer it expects was written by hand, so a pass means the behaviour is the behaviour that was intended.
// / Documentation/ABOUT_QUALITY_CONTROL.txt says what each group of cases proves & how to prove that this test can fail.
// /
// / Run it from the root of an installation, beside ScanCore.php.
// /   php Documentation/Build/scancore-test-cli.php
// /   php Documentation/Build/scancore-test-cli.php --list
// /   php Documentation/Build/scancore-test-cli.php --only png
// /   php Documentation/Build/scancore-test-cli.php --keep
// /   php Documentation/Build/scancore-test-cli.php --core /path/to/another/ScanCore.php
// / It exits 0 when every case passes, 1 when any case fails & 2 when it could not run.
// / Nothing stops early, so one run reports every case that fails.
// /
// / This file is a tool & is no part of the application.
// / ScanCore.php never loads it, & it loads nothing of ScanCore.php.
// /
// / Dependency Requirements ...
// / This tool requires PHP 8.0 or later with the zlib extension, & an interpreter that may run a command.
// /
// / <3 Open-Source
// / -----------------------------------------------------------------------------------

if (PHP_SAPI !== 'cli') { if (!headers_sent()) http_response_code(404); die(); }

// / -----------------------------------------------------------------------------------
// / Refuse a web request that reached a command line interpreter by another road.
// / A request carries a method, a host, a gateway, a server name or a remote address, & a tool that is run from a terminal carries none of them.
foreach (array('REQUEST_METHOD', 'HTTP_HOST', 'GATEWAY_INTERFACE', 'SERVER_NAME', 'REMOTE_ADDR') as $RequestVariable) if (getenv($RequestVariable) !== FALSE or isset($_SERVER[$RequestVariable])) die();
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Give up root where it was given, because nothing that this test does needs it.
// / The group is given up before the user, because an account that is no longer root may not change its group.
// / SUDO_UID names whoever ran sudo.
// / Without it the account is nobody.
if (function_exists('posix_geteuid') && posix_geteuid() === 0) {
  $LandingGroup = (getenv('SUDO_GID') !== FALSE && ctype_digit(strval(getenv('SUDO_GID')))) ? intval(getenv('SUDO_GID')) : 65534;
  $LandingUser = (getenv('SUDO_UID') !== FALSE && ctype_digit(strval(getenv('SUDO_UID')))) ? intval(getenv('SUDO_UID')) : 65534;
  if (!posix_setgid($LandingGroup) or !posix_setuid($LandingUser)) {
    echo 'ERROR!!! Root could not be given up, so nothing was run.'.PHP_EOL;
    exit(2); }
  echo 'Running as user '.posix_geteuid().' & group '.posix_getegid().', because root is not needed to test.'.PHP_EOL; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to build one chunk of a png, with the checksum a decoder expects.
function pngChunk($chunkType, $chunkData) {
  $Chunk = pack('N', strlen($chunkData)).$chunkType.$chunkData.pack('N', crc32($chunkType.$chunkData));
  return $Chunk; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to build a valid grey png of a given size.
// / Chunks may be placed ahead of the image data, & bytes may be placed after the end of the image.
function buildPng($imageWidth, $imageHeight, $extraChunks, $trailingBytes) {
  $scanlines = str_repeat("\0".str_repeat("\x80", $imageWidth), $imageHeight);
  $Png = "\x89PNG\r\n\x1a\n".pngChunk('IHDR', pack('NNCCCCC', $imageWidth, $imageHeight, 8, 0, 0, 0, 0)).$extraChunks.pngChunk('IDAT', gzcompress($scanlines)).pngChunk('IEND', '').$trailingBytes;
  return $Png; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to describe one member of a zip that is about to be built.
// / The method is 0 for a stored member & 8 for a deflated one.
// / A member may lie about itself through the fields a caller sets on what this returns.
function zipMember($memberName, $memberData, $compressionMethod) {
  $Member = array('name' => $memberName, 'localName' => $memberName, 'data' => $memberData, 'method' => $compressionMethod, 'flags' => 0, 'stored' => ($compressionMethod === 8) ? gzdeflate($memberData) : $memberData, 'sizeField' => pack('V', strlen($memberData)), 'compressedField' => '', 'extra' => '', 'offset' => -1);
  $Member['compressedField'] = pack('V', strlen($Member['stored']));
  return $Member; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to build a zip archive from members.
// / A member whose offset is set is given no data of its own, so two members can be made to share the same stored bytes.
function buildZip($memberList, $archiveComment) {
  $archiveBody = $archiveDirectory = '';
  $memberOffset = 0;
  foreach ($memberList as $member) {
    $memberOffset = $member['offset'];
    if ($memberOffset < 0) {
      $memberOffset = strlen($archiveBody);
      $archiveBody .= "PK\x03\x04".pack('vvvvvV', 20, $member['flags'], $member['method'], 0, 0, crc32($member['data'])).$member['compressedField'].$member['sizeField'].pack('vv', strlen($member['localName']), 0).$member['localName'].$member['stored']; }
    $archiveDirectory .= "PK\x01\x02".pack('vvvvvvV', 20, 20, $member['flags'], $member['method'], 0, 0, crc32($member['data'])).$member['compressedField'].$member['sizeField'].pack('vvvvvVV', strlen($member['name']), strlen($member['extra']), 0, 0, 0, 0, $memberOffset).$member['name'].$member['extra']; }
  $Zip = $archiveBody.$archiveDirectory."PK\x05\x06".pack('vvvvVVv', 0, 0, count($memberList), count($memberList), strlen($archiveDirectory), strlen($archiveBody), strlen($archiveComment)).$archiveComment;
  return $Zip; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to build one entry of a tar archive, padded to a whole block.
// / The type is 0 for a file, 5 for a folder & L for a long name that belongs to the entry after it.
function tarEntry($entryName, $entryData, $entryType) {
  $header = str_pad($entryName, 100, "\0").str_pad('0000644', 8, "\0").str_pad('0000000', 8, "\0").str_pad('0000000', 8, "\0").sprintf('%011o', strlen($entryData))."\0".sprintf('%011o', 0)."\0".'        '.$entryType.str_repeat("\0", 100).'ustar'."\0".'00'.str_repeat("\0", 247);
  $header = substr_replace($header, sprintf('%06o', array_sum(array_map('ord', str_split($header))))."\0 ", 148, 8);
  $Entry = $header.$entryData.str_repeat("\0", (512 - (strlen($entryData) % 512)) % 512);
  return $Entry; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to remove a folder & everything in it.
// / A link is removed as a link & is never followed.
function removeTree($folder) {
  $TreeRemoved = FALSE;
  $entryList = (is_dir($folder) && !is_link($folder)) ? scandir($folder) : array();
  foreach ($entryList as $entryName) if ($entryName !== '.' && $entryName !== '..') {
    if (is_dir($folder.DIRECTORY_SEPARATOR.$entryName) && !is_link($folder.DIRECTORY_SEPARATOR.$entryName)) removeTree($folder.DIRECTORY_SEPARATOR.$entryName);
    else {
      @chmod($folder.DIRECTORY_SEPARATOR.$entryName, 0600);
      @unlink($folder.DIRECTORY_SEPARATOR.$entryName); } }
  if (is_dir($folder) && !is_link($folder)) $TreeRemoved = @rmdir($folder);
  return $TreeRemoved; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to run ScanCore.php with arguments, as a caller would.
// / The interpreter is told to print every diagnostic, so a warning from PHP itself fails a case.
// / Returns the exit status & the lines that were printed, in that order.
function runScanCore($coreFile, $argumentList) {
  $ExitStatus = -1;
  $OutputLines = array();
  $command = escapeshellarg(PHP_BINARY).' -d display_errors=1 -d error_reporting=-1 -d log_errors=0 '.escapeshellarg($coreFile);
  foreach ($argumentList as $argument) $command .= ' '.escapeshellarg(strval($argument));
  exec($command.' 2>&1', $OutputLines, $ExitStatus);
  return array($ExitStatus, $OutputLines); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to read the verdict on one path out of what ScanCore said, the way a caller is told to.
// / This is the contract that Documentation/ABOUT_REPORTS.txt describes, & nothing more than that.
// / A line counts where one of the six words stands before the first slash & the path follows the word.
// / The path follows the last colon of a ScanCore-600 or ScanCore-610 line instead.
// / Returns infected, suspicious, incomplete or clean.
function readVerdict($outputLines, $scannedPath) {
  $Verdict = 'clean';
  $wordList = array('Infected: ', 'Suspicious: ', 'Classified: ', 'Incomplete: ', 'ScanCore-600 ', 'ScanCore-610 ');
  foreach ($outputLines as $outputLine) {
    $lineWord = '';
    $wordPosition = FALSE;
    $slashPosition = strpos($outputLine, '/');
    $lineHead = ($slashPosition === FALSE) ? $outputLine : substr($outputLine, 0, $slashPosition);
    foreach ($wordList as $candidateWord) if ($lineWord === '' && strpos($lineHead, $candidateWord) !== FALSE) {
      $lineWord = $candidateWord;
      $wordPosition = strpos($lineHead, $candidateWord); }
    $namesThePath = FALSE;
    if ($lineWord === 'ScanCore-600 ' or $lineWord === 'ScanCore-610 ') $namesThePath = (substr($outputLine, 0 - strlen(':  '.$scannedPath)) === ':  '.$scannedPath);
    else if ($lineWord !== '') $namesThePath = (strpos(substr($outputLine, $wordPosition + strlen($lineWord)), $scannedPath.' ') === 0);
    if ($namesThePath) {
      if ($lineWord === 'Infected: ') $Verdict = 'infected';
      if ($lineWord === 'Suspicious: ' && $Verdict !== 'infected') $Verdict = 'suspicious';
      if (in_array($lineWord, array('Incomplete: ', 'ScanCore-600 ', 'ScanCore-610 '), TRUE) && $Verdict === 'clean') $Verdict = 'incomplete'; } }
  return $Verdict; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to read the counts out of the summary that closes a scan.
// / Returns each count under a short name, & -1 for a count that the summary did not hold.
function readSummary($outputLines) {
  $Summary = array('files' => -1, 'folders' => -1, 'handlers' => -1, 'code' => -1, 'incomplete' => -1, 'suspicious' => -1, 'infected' => -1);
  $countParts = array();
  foreach ($outputLines as $outputLine) {
    if (preg_match('/^Scanned (\d+) files? in (\d+) folders?\.$/', $outputLine, $countParts)) {
      $Summary['files'] = intval($countParts[1]);
      $Summary['folders'] = intval($countParts[2]); }
    if (preg_match('/^Found (\d+) files? that may contain file or URL handlers\.$/', $outputLine, $countParts)) $Summary['handlers'] = intval($countParts[1]);
    if (preg_match('/^Found (\d+) files? that may contain executable code\.$/', $outputLine, $countParts)) $Summary['code'] = intval($countParts[1]);
    if (preg_match('/^Found (\d+) files? that could not be fully inspected\.$/', $outputLine, $countParts)) $Summary['incomplete'] = intval($countParts[1]);
    if (preg_match('/^Found (\d+) suspicious files?\.$/', $outputLine, $countParts)) $Summary['suspicious'] = intval($countParts[1]);
    if (preg_match('/^Found (\d+) infected files?\.$/', $outputLine, $countParts)) $Summary['infected'] = intval($countParts[1]); }
  return $Summary; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to describe one case.
// / The expected lines are each the opening of a line that must be printed, with {path} standing for the path that was scanned.
// / The refused texts must appear in no line at all.
// / The summary names only the counts that the case is about, & the verdict is what a caller must conclude about the path.
// / A case may also name another definitions file, another exit status & texts that must be written to the report.
function scanCase($caseName, $scanTarget, $switchList, $expectedLines, $refusedTexts, $expectedSummary, $expectedVerdict) {
  $Case = array('name' => $caseName, 'target' => $scanTarget, 'switches' => $switchList, 'expect' => $expectedLines, 'refuse' => $refusedTexts, 'summary' => $expectedSummary, 'verdict' => $expectedVerdict, 'exit' => 0, 'definitions' => 'test.def', 'report' => TRUE, 'written' => array());
  return $Case; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to run one case & judge it.
// / Every line that is expected on the console is expected in the report as well, behind its timestamp.
// / Returns every way in which the case failed, which is nothing where it passed.
function judgeCase($coreFile, $scratchFolder, $caseIndex, $case) {
  $Failures = array();
  $targetPath = $scratchFolder.DIRECTORY_SEPARATOR.'scan'.DIRECTORY_SEPARATOR.$case['target'];
  $reportFile = $scratchFolder.DIRECTORY_SEPARATOR.'reports'.DIRECTORY_SEPARATOR.'case-'.$caseIndex.'.txt';
  $argumentList = array_merge(array($targetPath, '-configfile', $scratchFolder.DIRECTORY_SEPARATOR.'test_config.php', '-defsfile', $scratchFolder.DIRECTORY_SEPARATOR.'definitions'.DIRECTORY_SEPARATOR.$case['definitions']), $case['switches']);
  if ($case['report']) $argumentList = array_merge($argumentList, array('-reportfile', $reportFile));
  list($exitStatus, $outputLines) = runScanCore($coreFile, $argumentList);
  $reportLines = is_file($reportFile) ? file($reportFile, FILE_IGNORE_NEW_LINES) : array();
  if ($exitStatus !== $case['exit']) $Failures[] = 'It exited '.$exitStatus.' where '.$case['exit'].' was expected.';
  foreach ($outputLines as $outputLine) if (preg_match('/^(PHP )?(Warning|Notice|Deprecated|Fatal error|Parse error)/', $outputLine)) $Failures[] = 'The interpreter complained:  '.$outputLine;
  foreach ($case['expect'] as $expectedLine) {
    $expectedLine = str_replace('{path}', $targetPath, $expectedLine);
    $lineWasPrinted = $lineWasWritten = FALSE;
    foreach ($outputLines as $outputLine) if (strpos($outputLine, $expectedLine) === 0) $lineWasPrinted = TRUE;
    foreach ($reportLines as $reportLine) if (strpos($reportLine, ', '.$expectedLine) !== FALSE) $lineWasWritten = TRUE;
    if (!$lineWasPrinted) $Failures[] = 'This line was not printed:  '.$expectedLine;
    if ($lineWasPrinted && $case['report'] && !$lineWasWritten) $Failures[] = 'This line was printed & was not written to the report:  '.$expectedLine; }
  foreach ($case['written'] as $writtenText) if (strpos(implode("\n", $reportLines), $writtenText) === FALSE) $Failures[] = 'This was not written to the report:  '.$writtenText;
  foreach ($case['refuse'] as $refusedText) foreach ($outputLines as $outputLine) if (strpos($outputLine, str_replace('{path}', $targetPath, $refusedText)) !== FALSE) $Failures[] = 'This line should not have been printed:  '.$outputLine;
  $summary = readSummary($outputLines);
  foreach ($case['summary'] as $countName => $expectedCount) if ($summary[$countName] !== $expectedCount) $Failures[] = 'The summary counted '.$summary[$countName].' under '.$countName.' where '.$expectedCount.' was expected.';
  if ($case['verdict'] !== '') {
    if (readVerdict($outputLines, $targetPath) !== $case['verdict']) $Failures[] = 'A caller reading the console would conclude '.readVerdict($outputLines, $targetPath).' where '.$case['verdict'].' was expected.';
    if ($case['report'] && readVerdict($reportLines, $targetPath) !== $case['verdict']) $Failures[] = 'A caller reading the report would conclude '.readVerdict($reportLines, $targetPath).' where '.$case['verdict'].' was expected.'; }
  if (count($Failures) > 0) foreach (array_slice($outputLines, 0, 12) as $outputLine) $Failures[] = '    | '.$outputLine;
  return $Failures; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to describe one answer that is not a scan of the scratch folder.
// / ScanCore is run with the arguments exactly as they are given here, & nothing is added to them.
// / The openings are what the first lines that are printed must open with, in order.
// / A case may also name texts that must or must not be printed, & a report file with texts that must or must not be written to it.
function answerCase($caseName, $argumentList, $expectedExit, $expectedOpenings) {
  $Case = array('name' => $caseName, 'arguments' => $argumentList, 'exit' => $expectedExit, 'openings' => $expectedOpenings, 'printed' => array(), 'unprinted' => array(), 'reportFile' => '', 'written' => array(), 'unwritten' => array());
  return $Case; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to ask for one answer & judge it.
// / Returns every way in which the answer was wrong, which is nothing where it was right.
function judgeAnswer($coreFile, $case) {
  $Failures = array();
  list($exitStatus, $outputLines) = runScanCore($coreFile, $case['arguments']);
  $printedText = "\n".implode("\n", $outputLines);
  $reportText = ($case['reportFile'] !== '' && is_file($case['reportFile'])) ? "\n".strval(file_get_contents($case['reportFile'])) : '';
  if ($exitStatus !== $case['exit']) $Failures[] = 'It exited '.$exitStatus.' where '.$case['exit'].' was expected.';
  foreach ($outputLines as $outputLine) if (preg_match('/^(PHP )?(Warning|Notice|Deprecated|Fatal error|Parse error)/', $outputLine)) $Failures[] = 'The interpreter complained:  '.$outputLine;
  foreach ($case['openings'] as $lineIndex => $expectedOpening) if (!isset($outputLines[$lineIndex]) or strpos($outputLines[$lineIndex], $expectedOpening) !== 0) $Failures[] = 'Line '.($lineIndex + 1).' of what was printed does not open with:  '.$expectedOpening;
  foreach ($case['printed'] as $printedPart) if (strpos($printedText, $printedPart) === FALSE) $Failures[] = 'This was not printed:  '.trim($printedPart);
  foreach ($case['unprinted'] as $unprintedPart) if (strpos($printedText, $unprintedPart) !== FALSE) $Failures[] = 'This should not have been printed:  '.trim($unprintedPart);
  foreach ($case['written'] as $writtenPart) if (strpos($reportText, $writtenPart) === FALSE) $Failures[] = 'This was not written to the report:  '.trim($writtenPart);
  foreach ($case['unwritten'] as $unwrittenPart) if (strpos($reportText, $unwrittenPart) !== FALSE) $Failures[] = 'This should not have been written to the report:  '.trim($unwrittenPart);
  if (count($Failures) > 0) foreach (array_slice($outputLines, 0, 12) as $outputLine) $Failures[] = '    | '.$outputLine;
  return $Failures; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / The main logic of the program.

// / Read what was asked for.
$ListOnly = in_array('--list', $argv, TRUE);
$KeepScratch = in_array('--keep', $argv, TRUE);
$OnlyWord = (array_search('--only', $argv, TRUE) !== FALSE && isset($argv[array_search('--only', $argv, TRUE) + 1])) ? strtolower($argv[array_search('--only', $argv, TRUE) + 1]) : '';
$CoreFile = (array_search('--core', $argv, TRUE) !== FALSE && isset($argv[array_search('--core', $argv, TRUE) + 1])) ? $argv[array_search('--core', $argv, TRUE) + 1] : 'ScanCore.php';
$CoreFile = realpath($CoreFile);

// / Refuse to start where the test could not mean anything.
if ($CoreFile === FALSE or !is_file($CoreFile)) {
  echo 'ERROR!!! Run this file from the root of a ScanCore installation, beside ScanCore.php, or name another core with --core.'.PHP_EOL;
  exit(2); }
if (!is_readable($CoreFile)) {
  echo 'ERROR!!! '.$CoreFile.' cannot be read by the account that this test runs as.'.PHP_EOL;
  exit(2); }
if (!function_exists('gzcompress') or !function_exists('gzdeflate') or !function_exists('gzencode')) {
  echo 'ERROR!!! The zlib extension is required to build the files that this test scans.'.PHP_EOL;
  exit(2); }
if (!function_exists('exec')) {
  echo 'ERROR!!! This interpreter may not run a command, so ScanCore.php cannot be run as a caller would run it.'.PHP_EOL;
  exit(2); }
// / Whether the installation holds a report folder is noted before ScanCore is run for the first time.
$LogsExistedBefore = is_dir(dirname($CoreFile).DIRECTORY_SEPARATOR.'Logs');
// / The version is looked for on every line here, so that an older core that prints something ahead of it can still be tested.
// / A case further down holds the version to the first line.
list($VersionStatus, $VersionLines) = runScanCore($CoreFile, array('-version'));
if ($VersionStatus !== 0 or !preg_match('/^ScanCore (v[0-9]+\.[0-9]+(\.[0-9]+)?) by /m', implode("\n", $VersionLines), $VersionParts)) {
  echo 'ERROR!!! '.$CoreFile.' did not answer -version with its name & its version, so nothing else was tried.'.PHP_EOL;
  foreach (array_slice($VersionLines, 0, 6) as $VersionLine) echo '    | '.$VersionLine.PHP_EOL;
  exit(2); }
$CoreVersion = $VersionParts[1];
$InstallDir = dirname($CoreFile);
$InstallWritable = is_writable($InstallDir);
$OnWindows = (DIRECTORY_SEPARATOR === '\\');

// / Build the scratch folder.
// / The configuration file in it holds two entries, so every other entry falls back to the default that is built into the core.
$Scratch = rtrim(sys_get_temp_dir(), '/\\').DIRECTORY_SEPARATOR.'scancore-test-'.bin2hex(random_bytes(6));
$Scan = $Scratch.DIRECTORY_SEPARATOR.'scan';
$Definitions = $Scratch.DIRECTORY_SEPARATOR.'definitions';
foreach (array($Scratch, $Scan, $Definitions, $Scratch.DIRECTORY_SEPARATOR.'reports', $Scan.DIRECTORY_SEPARATOR.'tree', $Scan.DIRECTORY_SEPARATOR.'tree'.DIRECTORY_SEPARATOR.'inner', $Scan.DIRECTORY_SEPARATOR.'tree'.DIRECTORY_SEPARATOR.'inner'.DIRECTORY_SEPARATOR.'deeper', $Scan.DIRECTORY_SEPARATOR.'copy', $Scan.DIRECTORY_SEPARATOR.'locked') as $ScratchFolder) if (!@mkdir($ScratchFolder, 0700)) {
  echo 'ERROR!!! Cannot create the scratch folder:  '.$ScratchFolder.PHP_EOL;
  exit(2); }
file_put_contents($Scratch.DIRECTORY_SEPARATOR.'test_config.php', '<?php'."\n".'// / Written by scancore-test-cli.php.'."\n".'$ConfigVersion = '.var_export($CoreVersion, TRUE).';'."\n".'$InstallDir = '.var_export($InstallDir, TRUE).';'."\n");

// / The sample stands in for malware.
// / It is harmless text that the test definitions name by its hashes.
$Sample = str_repeat('This is the ScanCore self test sample, which is harmless. ', 40);
$SecondSample = str_repeat('This is a second harmless sample for the ScanCore self test. ', 30);
$SampleSha256 = hash('sha256', $Sample);
$Prose = str_repeat("Nothing in this file is of any interest to a scanner.\n", 20);
$Padding = str_repeat("an ordinary line of text that means nothing\n", 4096);

// / These are the files that are scanned.
// / Each one exists to draw one line out of the scanner, or to draw none.
$Fixtures = array();
$Fixtures['clean.txt'] = $Prose;
$Fixtures['empty.txt'] = '';
$Fixtures['sample.bin'] = $Sample;
$Fixtures['data.txt'] = $Prose.'SCANCORE-SELF-TEST-DATA-SIGNATURE'."\n".$Prose;
$Fixtures['selftest-name-indicator.txt'] = $Prose;
$Fixtures['network.txt'] = $Prose.'See http://bad.example/payload.exe, or ask www.evil-host.example about it.'."\n";
$Fixtures['network-near-miss.txt'] = $Prose.'See http://bad.example/payload.exe.old, or ask notevil-host.example & evil-host.example.org about it.'."\n";
$Fixtures['scoped.scad'] = '// SCOPED-SELF-TEST-SIGNATURE'."\n".'cube(1);'."\n";
$Fixtures['scoped.txt'] = 'SCOPED-SELF-TEST-SIGNATURE'."\n".$Prose;
// / These names are cut by the join between two chunks, in each direction, or must not be hidden by it.
$Fixtures['join-host.txt'] = substr($Padding, 0, 4096 - 12).' www.evil-host.example/path and the rest'."\n".$Padding;
$Fixtures['join-url.txt'] = substr($Padding, 0, 4096 - 10).' see http://bad.example/payload.exe now'."\n".$Padding;
$Fixtures['join-data.txt'] = substr($Padding, 0, 4096 - 15).' SCANCORE-SELF-TEST-DATA-SIGNATURE'."\n".$Padding;
$Fixtures['join-left-cut.txt'] = substr($Padding, 0, 4096 - 32 - 4).' notevil-host.example and the rest'."\n".$Padding;
$Fixtures['join-right-cut.txt'] = substr($Padding, 0, 4096 - 17 - 1).' evil-host.examples and the rest'."\n".$Padding;
$Fixtures['join-hashed.txt'] = substr($Padding, 0, 150000).'the end of a file that is read in many chunks'."\n";
// / These files are classified.
$Fixtures['handler.scad'] = 'difference() {'."\n".'  cube(10);'."\n".'  surface /* a comment */ ("heights.dat");'."\n".'}'."\n";
$Fixtures['no-handler.scad'] = 'module mysurface(size) { cube(size); }'."\n".'include_lid = 4;'."\n".'mysurface(include_lid);'."\n";
$Fixtures['script.php'] = '<'.'?php echo "hello"; ?'.'>'."\n";
$Fixtures['unnamed-code'] = '<'.'?php'."\n".'$greeting = Greeter::make();'."\n".'$greeting->say("hello");'."\n";
$Fixtures['prose-with-one-signature.txt'] = $Prose.'The arrow -> points right.'."\n";
// / These are containers.
$Fixtures['sample-deflated.zip'] = buildZip(array(zipMember('readme.txt', $Prose, 8), zipMember('folder/sample.bin', $Sample, 8)), '');
$Fixtures['sample-stored.zip'] = buildZip(array(zipMember('sample.bin', $Sample, 0)), 'a comment that holds PK'."\x05\x06".' to mislead a reader');
$Fixtures['sample-nested.zip'] = buildZip(array(zipMember('inner.zip', buildZip(array(zipMember('deep/sample.bin', $Sample, 8)), ''), 0)), '');
$Fixtures['sample.tar'] = tarEntry('notes.txt', $Prose, '0').tarEntry('sample.bin', $Sample, '0').str_repeat("\0", 1024);
$Fixtures['sample.tar.gz'] = gzencode($Fixtures['sample.tar']);
$Fixtures['sample.gz'] = gzencode($Sample);
$Fixtures['clean.zip'] = buildZip(array(zipMember('readme.txt', $Prose, 8), zipMember('notes/more.txt', $Prose, 0)), '');
$Fixtures['empty.zip'] = buildZip(array(), '');
$Fixtures['document.docx'] = buildZip(array(zipMember('[Content_Types].xml', '<Types/>', 8), zipMember('word/_rels/document.xml.rels', '<Relationships><Relationship Id="rId1" Target="http://tracker.example/pixel.png" TargetMode="External"/></Relationships>', 8)), '');
$Fixtures['handler.tar'] = tarEntry('././@LongLink', str_repeat('a-very-long-folder-name/', 6)."\0", 'L').tarEntry('a-very-long-folder-name', '', '5').tarEntry('short.scad', 'surface("heights.dat");'."\n", '0').str_repeat("\0", 1024);
$Encrypted = zipMember('secret.txt', $Prose, 8);
$Encrypted['flags'] = 1;
$Fixtures['encrypted.zip'] = buildZip(array($Encrypted), '');
$Truncated = zipMember('cut.txt', $Padding.$Sample, 8);
$Truncated['stored'] = substr($Truncated['stored'], 0, intdiv(strlen($Truncated['stored']), 2));
$Truncated['compressedField'] = pack('V', strlen($Truncated['stored']));
$Fixtures['truncated.zip'] = buildZip(array($Truncated), '');
$Mismatch = zipMember('report.txt', $Prose, 8);
$Mismatch['localName'] = 'report.exe';
$Fixtures['mismatch.zip'] = buildZip(array($Mismatch), '');
$Unsupported = zipMember('odd.bin', $Prose, 0);
$Unsupported['method'] = 12;
$Fixtures['unsupported.zip'] = buildZip(array($Unsupported), '');
// / These are bombs.
// / Each declares or produces far more than it holds, & none holds more than a few kilobytes.
$DeclaredBomb = zipMember('big.bin', str_repeat("\0", 65536), 8);
$DeclaredBomb['sizeField'] = pack('V', 32 * 1024 * 1024);
$Fixtures['bomb-declared-ratio.zip'] = buildZip(array($DeclaredBomb), '');
$TotalBomb = zipMember('huge.bin', str_repeat("\0", 65536), 8);
$TotalBomb['sizeField'] = pack('V', 1024 * 1024 * 1024);
$Fixtures['bomb-total.zip'] = buildZip(array($TotalBomb), '');
$LyingBomb = zipMember('lies.bin', str_repeat("\0", 3 * 1024 * 1024), 8);
$LyingBomb['sizeField'] = pack('V', 100);
$Fixtures['bomb-measured-ratio.zip'] = buildZip(array($LyingBomb), '');
$FirstShared = zipMember('first.bin', str_repeat("\0", 65536), 8);
$SecondShared = $FirstShared;
$SecondShared['name'] = 'second.bin';
$SecondShared['offset'] = 0;
$Fixtures['bomb-overlap.zip'] = buildZip(array($FirstShared, $SecondShared), '');
$Nested = buildZip(array(zipMember('core.txt', $Prose, 0)), '');
for ($NestIndex = 0; $NestIndex < 7; $NestIndex++) $Nested = buildZip(array(zipMember('level'.$NestIndex.'.zip', $Nested, 0)), '');
$Fixtures['bomb-nested.zip'] = $Nested;
$Fixtures['bomb.gz'] = gzencode(str_repeat("\0", 3 * 1024 * 1024));
$Heavy = zipMember('heavy.bin', random_bytes(6 * 1024 * 1024), 0);
$Fixtures['heavy-nested.zip'] = buildZip(array(zipMember('middle.zip', buildZip(array(zipMember('inner.zip', buildZip(array($Heavy), ''), 0)), ''), 0)), '');
// / A zip64 archive that tells the truth, & two that declare a size no integer can hold.
$Wide = zipMember('sample.bin', $Sample, 0);
$Wide['extra'] = pack('vv', 1, 16).pack('V', strlen($Sample))."\0\0\0\0".pack('V', strlen($Sample))."\0\0\0\0";
$Wide['sizeField'] = $Wide['compressedField'] = "\xFF\xFF\xFF\xFF";
$Fixtures['zip64-honest.zip'] = buildZip(array($Wide), '');
$Overflow = zipMember('a.bin', 'a', 0);
$Overflow['extra'] = pack('vv', 1, 8).str_repeat("\xFF", 8);
$Overflow['sizeField'] = "\xFF\xFF\xFF\xFF";
$Fixtures['zip64-overflow.zip'] = buildZip(array($Overflow, zipMember('b.bin', 'b', 0)), '');
$TopBit = zipMember('a.bin', 'a', 0);
$TopBit['extra'] = pack('vv', 1, 8).str_repeat("\0", 7)."\x80";
$TopBit['sizeField'] = "\xFF\xFF\xFF\xFF";
$Fixtures['zip64-top-bit.zip'] = buildZip(array($TopBit), '');
// / These are images.
$Fixtures['valid.png'] = buildPng(64, 48, '', '');
$Fixtures['text.png'] = buildPng(16, 16, pngChunk('tEXt', "Comment\0an ordinary caption"), '');
$Fixtures['trailing.png'] = buildPng(16, 16, '', str_repeat('x', 120));
$Fixtures['php-text.png'] = buildPng(16, 16, pngChunk('tEXt', "Comment\0<".'?php echo "hello"; ?'.'>'), '');
$Fixtures['php-compressed-text.png'] = buildPng(16, 16, pngChunk('zTXt', "Comment\0\0".gzcompress('<'.'?= 1 + 1 ?'.'>')), '');
$Fixtures['chunk-bomb.png'] = buildPng(16, 16, pngChunk('zTXt', "Comment\0\0".gzcompress(str_repeat('A', 1024 * 1024))), '');
$Fixtures['flood.png'] = "\x89PNG\r\n\x1a\n".pngChunk('IHDR', pack('NNCCCCC', 20000, 20000, 8, 0, 0, 0, 0)).pngChunk('IDAT', gzcompress(str_repeat("\0", 1024))).pngChunk('IEND', '');
$Fixtures['overrun.png'] = "\x89PNG\r\n\x1a\n".pngChunk('IHDR', pack('NNCCCCC', 4, 4, 8, 0, 0, 0, 0)).pngChunk('IDAT', gzcompress(str_repeat("\0", 1024 * 1024))).pngChunk('IEND', '');
$Fixtures['bad-checksum.png'] = substr_replace(buildPng(16, 16, '', ''), "\0\0\0\0", -16, 4);
$Fixtures['bad-chunk-name.png'] = "\x89PNG\r\n\x1a\n".pngChunk('IHDR', pack('NNCCCCC', 16, 16, 8, 0, 0, 0, 0)).pack('N', 4).'12#4'.'data'.pack('N', 0);
$Fixtures['not-a-png.png'] = $Prose;
$Fixtures['hidden-archive.png'] = buildPng(16, 16, '', '').buildZip(array(zipMember('sample.bin', $Sample, 8)), '');
$Fixtures['php.jpg'] = "\xFF\xD8\xFF\xE0\x00\x10JFIF\x00\x01\x01\x00\x00\x01\x00\x01\x00\x00".str_repeat("\x11\x22\x33\x44", 64).'<'.'?php echo "hello"; ?'.'>'."\xFF\xD9";
$Fixtures['plain.jpg'] = "\xFF\xD8\xFF\xE0\x00\x10JFIF\x00\x01\x01\x00\x00\x01\x00\x01\x00\x00".str_repeat("\x11\x22\x33\x44", 64)."\xFF\xD9";
$Fixtures['plain.gif'] = 'GIF89a'."\x01\x00\x01\x00\x80\x00\x00\x00\x00\x00\xFF\xFF\xFF".'!'."\xF9\x04\x01\x00\x00\x00\x00".','."\x00\x00\x00\x00\x01\x00\x01\x00\x00\x02\x02D\x01\x00;";
// / This is a small tree for the walk.
$Fixtures['tree'.DIRECTORY_SEPARATOR.'one.txt'] = $Prose;
$Fixtures['tree'.DIRECTORY_SEPARATOR.'sample.bin'] = $Sample;
$Fixtures['tree'.DIRECTORY_SEPARATOR.'inner'.DIRECTORY_SEPARATOR.'two.txt'] = $Prose;
$Fixtures['tree'.DIRECTORY_SEPARATOR.'inner'.DIRECTORY_SEPARATOR.'second.bin'] = $SecondSample;
$Fixtures['tree'.DIRECTORY_SEPARATOR.'inner'.DIRECTORY_SEPARATOR.'deeper'.DIRECTORY_SEPARATOR.'three.txt'] = $Prose;
$Fixtures['locked'.DIRECTORY_SEPARATOR.'readable.txt'] = $Prose;
$Fixtures['locked'.DIRECTORY_SEPARATOR.'unreadable.txt'] = $Prose;
foreach ($Fixtures as $FixtureName => $FixtureData) file_put_contents($Scan.DIRECTORY_SEPARATOR.$FixtureName, $FixtureData);

// / These are the definitions that most cases use, & a few that exist to be wrong in one way each.
file_put_contents($Definitions.DIRECTORY_SEPARATOR.'test.def', implode("\n", array(
  '# Written by scancore-test-cli.php.',
  'sha256 Test.Sample = '.$SampleSha256.' src=selftest',
  'md5 Test.SecondSample = '.hash('md5', $SecondSample),
  'data Test.Data = "SCANCORE-SELF-TEST-DATA-SIGNATURE"',
  'name Test.Name = "selftest-name-indicator" confidence=low',
  'host Test.Host = "evil-host.example"',
  'url Test.Url = "http://bad.example/payload.exe"',
  'data Test.Scoped = "SCOPED-SELF-TEST-SIGNATURE" scope=scad',
  'sha256 Test.Chunked = '.hash('sha256', $Fixtures['join-hashed.txt']), ''))."\n");
file_put_contents($Definitions.DIRECTORY_SEPARATOR.'network.def', 'host Test.Host = "evil-host.example"'."\n".'url Test.Url = "http://bad.example/payload.exe"'."\n");
file_put_contents($Definitions.DIRECTORY_SEPARATOR.'case.def', 'sha256 Test.Sample = '.strtoupper($SampleSha256).' confidence=High'."\n");
file_put_contents($Definitions.DIRECTORY_SEPARATOR.'attributes.def', 'sha256 Test.Sample = '.$SampleSha256.' kind=data name=Renamed value=nothing'."\n");
file_put_contents($Definitions.DIRECTORY_SEPARATOR.'heuristic.def', 'sha256 Test.Sample = '.$SampleSha256.' confidence=medium'."\n");
file_put_contents($Definitions.DIRECTORY_SEPARATOR.'malformed.def', implode("\n", array('sha256 Test.Odd = abc', 'sha256 Test.NotHex = '.str_repeat('z', 64), 'md5 Test.Short = '.str_repeat('0', 30), 'nonsense Test.Kind = "value"', 'sha256 Test.Sample = '.$SampleSha256, ''))."\n");
file_put_contents($Definitions.DIRECTORY_SEPARATOR.'short.def', 'data Test.Short = "ab"'."\n".'sha256 Test.Sample = '.$SampleSha256."\n");
$IndexedDefinitions = 'md5 Test.Short = '.str_repeat('0', 30)."\n";
for ($FillIndex = 0; $FillIndex < 40; $FillIndex++) $IndexedDefinitions .= 'md5 Test.Fill'.$FillIndex.' = '.hash('md5', 'fill '.$FillIndex)."\n";
file_put_contents($Definitions.DIRECTORY_SEPARATOR.'indexed.def', $IndexedDefinitions.'md5 Test.SecondSample = '.hash('md5', $SecondSample)."\n".'sha256 Test.Sample = '.$SampleSha256."\n");
file_put_contents($Definitions.DIRECTORY_SEPARATOR.'retired.def', "Test.Sample\t".$SampleSha256."\n");
copy($Definitions.DIRECTORY_SEPARATOR.'test.def', $Scan.DIRECTORY_SEPARATOR.'copy'.DIRECTORY_SEPARATOR.'backup-of-the-definitions.txt');
// / This is a path longer than a report line usually is, & a folder whose name tries to write a line of its own.
$LongName = str_repeat('a', 240).'.bin';
$HostileFolder = $Scan.DIRECTORY_SEPARATOR.'tree'.DIRECTORY_SEPARATOR.'ordinary'."\n".'FORGED LINE';
if (!$OnWindows) file_put_contents($Scan.DIRECTORY_SEPARATOR.$LongName, $Sample);
$HostileWasMade = (!$OnWindows && @mkdir($HostileFolder, 0700) && file_put_contents($HostileFolder.DIRECTORY_SEPARATOR.'four.txt', $Prose) !== FALSE);
$LinkWasMade = (!$OnWindows && @symlink($Scan.DIRECTORY_SEPARATOR.'sample.bin', $Scan.DIRECTORY_SEPARATOR.'tree'.DIRECTORY_SEPARATOR.'link-to-sample.bin'));
// / This is a file that nothing may open.
// / An account that can open it all the same cannot run the two cases that are about it.
$LockedWasMade = (!$OnWindows && @chmod($Scan.DIRECTORY_SEPARATOR.'locked'.DIRECTORY_SEPARATOR.'unreadable.txt', 0000) && !is_readable($Scan.DIRECTORY_SEPARATOR.'locked'.DIRECTORY_SEPARATOR.'unreadable.txt'));

// / These are the cases, each with the answer that was written for it by hand.
$HashMatch = 'Infected: {path} (Test.Sample, SHA256 Hash Match: '.$SampleSha256;
$Cases = array();
// / These cases are about the definitions.
$Case = scanCase('definitions: a clean file draws no line, & an entry the configuration lacks falls back', 'clean.txt', array(), array('Starting ScanCore!', 'Scanned 1 file in 0 folders.'), array('Infected:', 'Suspicious:', 'Incomplete:', 'Classified:'), array('files' => 1, 'incomplete' => 0, 'suspicious' => 0, 'infected' => 0), 'clean');
$Case['written'] = array('Configuration entry $MaxScanDepth is missing or invalid, using the built in default instead.');
$Cases[] = $Case;
$Cases[] = scanCase('definitions: an empty file is scanned & is clean', 'empty.txt', array(), array(), array('Infected:', 'Suspicious:', 'Incomplete:'), array('files' => 1, 'infected' => 0), 'clean');
$Cases[] = scanCase('definitions: a known sample is reported by its hash', 'sample.bin', array(), array($HashMatch.')'), array('Suspicious:', 'Incomplete:'), array('files' => 1, 'suspicious' => 0, 'infected' => 1), 'infected');
$Cases[] = scanCase('definitions: a data signature is reported as suspicious', 'data.txt', array(), array('Suspicious: {path} (Test.Data, Data Match: SCANCORE-SELF-TEST-DATA-SIGNATURE)'), array('Infected:'), array('suspicious' => 1, 'infected' => 0), 'suspicious');
$Cases[] = scanCase('definitions: a name indicator is reported as suspicious', 'selftest-name-indicator.txt', array(), array('Suspicious: {path} (Test.Name, Name Match: selftest-name-indicator)'), array('Infected:'), array('suspicious' => 1, 'infected' => 0), 'suspicious');
$Cases[] = scanCase('definitions: a url & a host under a longer name are both reported', 'network.txt', array(), array('Suspicious: {path} (Test.Url, URL Match: http://bad.example/payload.exe)', 'Suspicious: {path} (Test.Host, Host Match: evil-host.example)'), array('Infected:'), array('suspicious' => 1), 'suspicious');
$Cases[] = scanCase('definitions: a longer url & a longer host are not the indicator', 'network-near-miss.txt', array(), array(), array('Suspicious:', 'Infected:'), array('suspicious' => 0), 'clean');
$Cases[] = scanCase('definitions: -nosuspicious leaves a heuristic out of everything that is printed', 'data.txt', array('-nosuspicious'), array(), array('Suspicious:', 'suspicious file'), array('infected' => 0), 'clean');
$Cases[] = scanCase('definitions: a scoped definition applies to the type it names', 'scoped.scad', array(), array('Suspicious: {path} (Test.Scoped, Data Match: SCOPED-SELF-TEST-SIGNATURE)'), array(), array('suspicious' => 1), 'suspicious');
$Cases[] = scanCase('definitions: a scoped definition applies to no other type', 'scoped.txt', array(), array(), array('Suspicious:'), array('suspicious' => 0), 'clean');
$Case = scanCase('definitions: a confidence & a hash are read whatever their case', 'sample.bin', array(), array($HashMatch.')'), array('Suspicious:'), array('infected' => 1), 'infected');
$Case['definitions'] = 'case.def';
$Cases[] = $Case;
$Case = scanCase('definitions: a hash of medium confidence is reported as suspicious', 'sample.bin', array(), array('Suspicious: {path} (Test.Sample, SHA256 Hash Match: '.$SampleSha256.')'), array('Infected:'), array('suspicious' => 1, 'infected' => 0), 'suspicious');
$Case['definitions'] = 'heuristic.def';
$Cases[] = $Case;
$Case = scanCase('definitions: an attribute cannot rewrite the record it trails', 'sample.bin', array(), array($HashMatch.')'), array('Renamed'), array('infected' => 1), 'infected');
$Case['definitions'] = 'attributes.def';
$Cases[] = $Case;
$Case = scanCase('definitions: a malformed record is ignored & the rest still match', 'sample.bin', array(), array($HashMatch.')'), array(), array('infected' => 1), 'infected');
$Case['definitions'] = 'malformed.def';
$Case['written'] = array('Ignored 4 malformed definition records.');
$Cases[] = $Case;
$Case = scanCase('definitions: a data signature under the minimum length is not searched for', 'network.txt', array(), array(), array('Suspicious:', 'Test.Short'), array('suspicious' => 0), 'clean');
$Case['definitions'] = 'short.def';
$Case['written'] = array('Ignored 1 data signature shorter than the MinimumDataSignatureLength of 4.');
$Cases[] = $Case;
$Case = scanCase('definitions: -mindatasignature admits a shorter data signature', 'network.txt', array('-mindatasignature', '2'), array('Suspicious: {path} (Test.Short, Data Match: ab)'), array(), array('suspicious' => 1), 'suspicious');
$Case['definitions'] = 'short.def';
$Cases[] = $Case;
$Case = scanCase('definitions: an index is searched on the run that builds it', 'tree'.DIRECTORY_SEPARATOR.'inner'.DIRECTORY_SEPARATOR.'second.bin', array('-hashindexthreshold', '10'), array('Infected: {path} (Test.SecondSample, MD5 Hash Match: '.hash('md5', $SecondSample).')'), array(), array('infected' => 1), 'infected');
$Case['definitions'] = 'indexed.def';
$Cases[] = $Case;
$Case = scanCase('definitions: an index that was built earlier answers the same', 'sample.bin', array('-hashindexthreshold', '10'), array($HashMatch.')'), array(), array('infected' => 1), 'infected');
$Case['definitions'] = 'indexed.def';
$Cases[] = $Case;
$Case = scanCase('definitions: -nohashindex answers the same without an index', 'sample.bin', array('-hashindexthreshold', '10', '-nohashindex'), array($HashMatch.')'), array(), array('infected' => 1), 'infected');
$Case['definitions'] = 'indexed.def';
$Cases[] = $Case;
$Case = scanCase('definitions: a file in the retired format stops the scan with an error', 'sample.bin', array(), array('The definitions file holds 1 record in the retired tab delimited format', 'Cannot load definitions!'), array('Scanned'), array(), '');
$Case['definitions'] = 'retired.def';
$Case['exit'] = 1;
$Cases[] = $Case;
$Cases[] = scanCase('definitions: a copy of the definitions file in use is not scanned', 'copy', array(), array('Scanned 0 files in 1 folder.'), array('Suspicious:', 'Infected:'), array('files' => 0, 'suspicious' => 0), '');
// / These cases are about the join between two chunks.
$Case = scanCase('chunks: a host that spans two chunks is found', 'join-host.txt', array('-chunksize', '4096', '-noclassify'), array('Suspicious: {path} (Test.Host, Host Match: evil-host.example)'), array(), array('suspicious' => 1), 'suspicious');
$Case['definitions'] = 'network.def';
$Cases[] = $Case;
$Case = scanCase('chunks: a url that spans two chunks is found', 'join-url.txt', array('-chunksize', '4096', '-noclassify'), array('Suspicious: {path} (Test.Url, URL Match: http://bad.example/payload.exe)'), array(), array('suspicious' => 1), 'suspicious');
$Case['definitions'] = 'network.def';
$Cases[] = $Case;
$Cases[] = scanCase('chunks: a data signature that spans two chunks is found', 'join-data.txt', array('-chunksize', '4096', '-noclassify'), array('Suspicious: {path} (Test.Data, Data Match: SCANCORE-SELF-TEST-DATA-SIGNATURE)'), array(), array('suspicious' => 1), 'suspicious');
$Cases[] = scanCase('chunks: a longer name is not read from its middle', 'join-left-cut.txt', array('-chunksize', '4096', '-noclassify'), array(), array('Suspicious:'), array('suspicious' => 0), 'clean');
$Cases[] = scanCase('chunks: a longer name is not read short of its end', 'join-right-cut.txt', array('-chunksize', '4096', '-noclassify'), array(), array('Suspicious:'), array('suspicious' => 0), 'clean');
$Cases[] = scanCase('chunks: a file that is read in many chunks is still known by its hash', 'join-hashed.txt', array('-chunksize', '4096'), array('Infected: {path} (Test.Chunked, SHA256 Hash Match: '), array(), array('infected' => 1), 'infected');
// / These cases are about classification.
$Cases[] = scanCase('classify: a handler is found across whitespace & a comment', 'handler.scad', array(), array('Classified: {path} (File may contain file or URL handlers - Type: scad, Handlers: surface(file))'), array('Infected:', 'Suspicious:'), array('handlers' => 1, 'code' => 0), 'clean');
$Cases[] = scanCase('classify: a longer identifier is not a handler', 'no-handler.scad', array(), array(), array('Classified:'), array('handlers' => 0), 'clean');
$Cases[] = scanCase('classify: an extension that names a language is reported as code', 'script.php', array(), array('Classified: {path} (File may contain executable code - Detected Language: PHP)'), array(), array('code' => 1, 'handlers' => 0), 'clean');
$Cases[] = scanCase('classify: code under an unclaimed name is recognised by its content', 'unnamed-code', array(), array('Classified: {path} (File may contain executable code - Detected Language: PHP)'), array(), array('code' => 1), 'clean');
$Cases[] = scanCase('classify: one signature in prose is not code', 'prose-with-one-signature.txt', array(), array(), array('executable code - Detected'), array('code' => 0), 'clean');
$Cases[] = scanCase('classify: -noclassify draws no line & no count', 'handler.scad', array('-noclassify'), array(), array('Classified:', 'handlers.', 'executable code.'), array(), 'clean');
$Cases[] = scanCase('classify: -classifiers leaves the others out', 'handler.scad', array('-classifiers', 'pdf'), array(), array('Classified:'), array('handlers' => 0), 'clean');
$Cases[] = scanCase('classify: a handler inside a document names its member', 'document.docx', array(), array('Classified: {path} (File may contain file or URL handlers - Type: document, Handlers: '), array('Infected:'), array('handlers' => 1), 'clean');
$Cases[] = scanCase('classify: a long name that belongs to a folder is not given to the next file', 'handler.tar', array(), array('Classified: {path} (File may contain file or URL handlers - Type: scad, Handlers: surface(file) in short.scad)'), array('a-very-long-folder-name'), array('handlers' => 1), 'clean');
// / These cases are about containers.
$Cases[] = scanCase('containers: a sample in a deflated member is found', 'sample-deflated.zip', array(), array($HashMatch.' in folder/sample.bin)'), array('Incomplete:'), array('infected' => 1, 'incomplete' => 0), 'infected');
$Cases[] = scanCase('containers: a sample in a stored member is found behind a misleading comment', 'sample-stored.zip', array(), array($HashMatch.' in sample.bin)'), array(), array('infected' => 1), 'infected');
$Cases[] = scanCase('containers: a sample in a zip inside a zip is found', 'sample-nested.zip', array(), array($HashMatch.' in inner.zip > deep/sample.bin)'), array(), array('infected' => 1), 'infected');
$Cases[] = scanCase('containers: a sample in a tar is found', 'sample.tar', array(), array($HashMatch.' in sample.bin)'), array(), array('infected' => 1), 'infected');
$Cases[] = scanCase('containers: a sample in a gzipped tar is found', 'sample.tar.gz', array(), array($HashMatch.' in gzip stream > sample.bin)'), array(), array('infected' => 1), 'infected');
$Cases[] = scanCase('containers: a sample in a gzip stream is found', 'sample.gz', array(), array($HashMatch.' in gzip stream)'), array(), array('infected' => 1), 'infected');
$Cases[] = scanCase('containers: a sample in a zip64 archive is found', 'zip64-honest.zip', array(), array($HashMatch.' in sample.bin)'), array('Incomplete:'), array('infected' => 1), 'infected');
$Cases[] = scanCase('containers: an ordinary archive draws no line', 'clean.zip', array(), array(), array('Infected:', 'Suspicious:', 'Incomplete:'), array('infected' => 0, 'incomplete' => 0), 'clean');
$Cases[] = scanCase('containers: an empty archive draws no line', 'empty.zip', array(), array(), array('Infected:', 'Suspicious:', 'Incomplete:'), array('infected' => 0, 'incomplete' => 0), 'clean');
$Cases[] = scanCase('containers: -noinspectarchives opens nothing', 'sample-deflated.zip', array('-noinspectarchives'), array(), array('Infected:'), array('infected' => 0), 'clean');
$Cases[] = scanCase('containers: an encrypted member is reported as incomplete', 'encrypted.zip', array(), array('Incomplete: {path} (an encrypted member cannot be inspected)'), array('Infected:'), array('incomplete' => 1, 'infected' => 0), 'incomplete');
$Cases[] = scanCase('containers: a member that is cut short is reported as damaged', 'truncated.zip', array(), array('Incomplete: {path} (a damaged member could not be read)'), array('entry budget'), array('incomplete' => 1), 'incomplete');
$Cases[] = scanCase('containers: a compression method that is not read is reported as incomplete', 'unsupported.zip', array(), array('Incomplete: {path} (a member uses a compression method that is not read)'), array(), array('incomplete' => 1), 'incomplete');
$Cases[] = scanCase('containers: two headers that disagree about a member are reported', 'mismatch.zip', array(), array('Suspicious: {path} (Malformed.Archive.HeaderMismatch, Structure Match: the two headers describing the member disagree in report.txt)'), array(), array('suspicious' => 1), 'suspicious');
$Cases[] = scanCase('containers: a member past the entry budget is inspected in part', 'sample-stored.zip', array('-maxarchiveentrysize', '1024'), array('Incomplete: {path} (a member larger than the entry budget was inspected in part)'), array('Infected:'), array('incomplete' => 1, 'infected' => 0), 'incomplete');
$Cases[] = scanCase('containers: members past the entry count are reported as incomplete', 'sample-deflated.zip', array('-maxarchiveentries', '1'), array('Incomplete: {path} (an archive lists 2 entries, which is more than the entry budget of 1)'), array('Infected:'), array('incomplete' => 1), 'incomplete');
// / These cases are about bombs.
$Cases[] = scanCase('bombs: a member that declares a huge expansion is reported unread', 'bomb-declared-ratio.zip', array(), array('Infected: {path} (Bomb.Archive.CompressionRatio, Structure Match: declares 33554432 bytes from '), array(), array('infected' => 1), 'infected');
$Cases[] = scanCase('bombs: a directory that declares a huge total is reported', 'bomb-total.zip', array(), array('Infected: {path} (Bomb.Archive.TotalExpansion, Structure Match: 1 member declares 1073741824 bytes from an archive of '), array(), array('infected' => 1), 'infected');
$Cases[] = scanCase('bombs: a member that lies about its size is caught by measuring it', 'bomb-measured-ratio.zip', array('-maxarchiveentrysize', '1048576'), array('Infected: {path} (Bomb.Archive.CompressionRatio, Structure Match: expands past 1048576 bytes from '), array(), array('infected' => 1), 'infected');
$Cases[] = scanCase('bombs: members that share stored bytes are reported', 'bomb-overlap.zip', array(), array('Infected: {path} (Bomb.Archive.OverlappingEntries, Structure Match: members of the archive share the same stored bytes)'), array(), array('infected' => 1), 'infected');
$Cases[] = scanCase('bombs: containers nested past the depth limit are reported', 'bomb-nested.zip', array(), array('Infected: {path} (Bomb.Archive.NestingDepth, Structure Match: containers are nested more than 5 deep in '), array(), array('infected' => 1), 'infected');
$Cases[] = scanCase('bombs: -maxarchivedepth moves the depth limit', 'bomb-nested.zip', array('-maxarchivedepth', '9'), array(), array('Infected:', 'Incomplete:'), array('infected' => 0), 'clean');
$Cases[] = scanCase('bombs: a gzip stream that expands past its budget is reported', 'bomb.gz', array('-maxarchiveentrysize', '1048576'), array('Infected: {path} (Bomb.Archive.CompressionRatio, Structure Match: expands past 1048576 bytes from '), array(), array('infected' => 1), 'infected');
$Cases[] = scanCase('bombs: a size that no integer holds is reported & stops nothing', 'zip64-overflow.zip', array(), array('Infected: {path} (Bomb.Archive.TotalExpansion, Structure Match: 2 members declare 18446744073709551616 bytes from an archive of '), array(), array('infected' => 1), 'infected');
$Cases[] = scanCase('bombs: a size with its top bit set is not read as a negative number', 'zip64-top-bit.zip', array(), array('Infected: {path} (Bomb.Archive.TotalExpansion, Structure Match: 1 member declares 9223372036854775808 bytes from an archive of '), array(), array('infected' => 1), 'infected');
// / These cases are about images.
$Cases[] = scanCase('images: a valid png draws no line', 'valid.png', array(), array(), array('Infected:', 'Suspicious:', 'Incomplete:', 'Classified:'), array('infected' => 0, 'suspicious' => 0), 'clean');
$Cases[] = scanCase('images: a png with an ordinary caption draws no line', 'text.png', array(), array(), array('Infected:', 'Suspicious:'), array('infected' => 0, 'suspicious' => 0), 'clean');
$Cases[] = scanCase('images: an ordinary jpeg draws no line', 'plain.jpg', array(), array(), array('Infected:', 'Suspicious:', 'Classified:'), array('infected' => 0, 'suspicious' => 0), 'clean');
$Cases[] = scanCase('images: an ordinary gif draws no line', 'plain.gif', array(), array(), array('Infected:', 'Suspicious:', 'Classified:'), array('infected' => 0, 'suspicious' => 0), 'clean');
$Cases[] = scanCase('images: data after the end of a png is reported', 'trailing.png', array(), array('Suspicious: {path} (Polyglot.PNG.TrailingData, Structure Match: 120 bytes follow the end of the image)'), array('Infected:'), array('suspicious' => 1), 'suspicious');
$Cases[] = scanCase('images: code in a text chunk is reported as infected', 'php-text.png', array(), array('Infected: {path} (Embedded.PHP, Structure Match: <'.'?php '), array(), array('infected' => 1), 'infected');
$Cases[] = scanCase('images: code in a compressed text chunk is reported, & names its chunk', 'php-compressed-text.png', array(), array('Infected: {path} (Embedded.PHP, Structure Match: <'.'?= in zTXt chunk)'), array(), array('infected' => 1), 'infected');
$Cases[] = scanCase('images: a text chunk that expands past its budget is reported', 'chunk-bomb.png', array('-maximagechunksize', '65536'), array('Infected: {path} (Bomb.PNG.ChunkExpansion, Structure Match: expands past 65536 bytes from '), array(), array('infected' => 1), 'infected');
$Cases[] = scanCase('images: a header that declares a pixel flood is reported', 'flood.png', array(), array('Infected: {path} (Bomb.PNG.PixelFlood, Structure Match: 20000 by 20000 pixels decodes to 400020000 bytes from a file of '), array(), array('infected' => 1), 'infected');
$Cases[] = scanCase('images: -maximagepixels moves the pixel limit', 'flood.png', array('-maximagepixels', '500000000'), array(), array('Bomb.PNG.PixelFlood'), array(), '');
$Cases[] = scanCase('images: image data that overruns its header is reported', 'overrun.png', array('-maximagechunksize', '65536'), array('Infected: {path} (Bomb.PNG.DataOverrun, Structure Match: the image data inflates past '), array(), array('infected' => 1), 'infected');
$Cases[] = scanCase('images: a chunk that fails its checksum is reported', 'bad-checksum.png', array(), array('Suspicious: {path} (Malformed.PNG, Structure Match: the IDAT chunk fails its checksum)'), array('Infected:'), array('suspicious' => 1), 'suspicious');
$Cases[] = scanCase('images: a chunk with an impossible name is reported for what it is', 'bad-chunk-name.png', array(), array('Suspicious: {path} (Malformed.PNG, Structure Match: a chunk is named with something other than four letters)'), array(), array('suspicious' => 1), 'suspicious');
$Cases[] = scanCase('images: a file that is only named as a png is reported', 'not-a-png.png', array(), array('Suspicious: {path} (Masquerade.PNG, Structure Match: the file is named as a png & does not begin with a png signature)'), array(), array('suspicious' => 1), 'suspicious');
$Cases[] = scanCase('images: an archive behind an image is reported & opened', 'hidden-archive.png', array(), array('Suspicious: {path} (Polyglot.Archive.Hidden, Structure Match: a zip archive of 1 entry sits behind a png header)', $HashMatch.' in appended archive > sample.bin)'), array(), array('infected' => 1), 'infected');
$Cases[] = scanCase('images: code in a jpeg is reported as infected', 'php.jpg', array(), array('Infected: {path} (Embedded.PHP, Structure Match: <'.'?php )'), array(), array('infected' => 1), 'infected');
$Cases[] = scanCase('images: -noinspectimages draws no line for an image', 'php-text.png', array('-noinspectimages'), array(), array('Embedded.', 'Malformed.', 'Polyglot.'), array(), '');
// / These cases are about the walk.
$Cases[] = scanCase('walk: a folder is not entered without recursion', 'tree', array('-norecursion'), array('Infected: {path}'.DIRECTORY_SEPARATOR.'sample.bin (Test.Sample, SHA256 Hash Match: '), array('second.bin'), array('files' => 2, 'folders' => 1, 'infected' => 1), '');
$Cases[] = scanCase('walk: a folder is entered with recursion', 'tree', array('-recursion'), array('Infected: {path}'.DIRECTORY_SEPARATOR.'sample.bin (Test.Sample, SHA256 Hash Match: ', 'Infected: {path}'.DIRECTORY_SEPARATOR.'inner'.DIRECTORY_SEPARATOR.'second.bin (Test.SecondSample, MD5 Hash Match: '), array('link-to-sample'), array('files' => ($HostileWasMade ? 6 : 5), 'folders' => ($HostileWasMade ? 4 : 3), 'infected' => 2), '');
$Cases[] = scanCase('walk: a folder past the depth limit is not opened & that is said', 'tree', array('-recursion', '-maxdepth', '1'), array('Reached the maximum scan depth, this folder was not opened:  '), array('three.txt'), array('files' => ($HostileWasMade ? 5 : 4), 'folders' => ($HostileWasMade ? 3 : 2), 'infected' => 2), '');
if ($LinkWasMade) $Cases[] = scanCase('walk: a link is followed only when that is asked for', 'tree', array('-norecursion', '-followsymlinks'), array('Infected: {path}'.DIRECTORY_SEPARATOR.'link-to-sample.bin (Test.Sample, SHA256 Hash Match: '), array(), array('files' => 3, 'infected' => 2), '');
if ($LockedWasMade) $Cases[] = scanCase('walk: a file that cannot be opened is reported as incomplete, & the walk goes on', 'locked', array(), array('Incomplete: {path}'.DIRECTORY_SEPARATOR.'unreadable.txt (the file could not be opened)'), array('Infected:'), array('files' => 2, 'incomplete' => 1), '');
if ($LockedWasMade) {
  $Case = scanCase('walk: a path that cannot be read at all is refused with an error', 'locked'.DIRECTORY_SEPARATOR.'unreadable.txt', array(), array('The specified path cannot be read:  {path}'), array('Scanned'), array(), '');
  $Case['exit'] = 1;
  $Cases[] = $Case; }
// / These cases are about the report, the limits & the answers that are not a scan.
if (!$OnWindows) $Cases[] = scanCase('report: a path longer than 255 bytes is named whole, so a caller can find it', $LongName, array(), array($HashMatch.')'), array('...'), array('infected' => 1), 'infected');
$Case = scanCase('report: a scan with no report it can write still reports on the console', 'sample.bin', array('-reportfile', $Scratch.DIRECTORY_SEPARATOR.'no-such-folder'.DIRECTORY_SEPARATOR.'report.txt'), array('ERROR!!! ScanCore-910 on ', $HashMatch.')', 'Found 1 infected file.'), array(), array('infected' => 1), 'infected');
$Case['report'] = FALSE;
$Cases[] = $Case;
$Cases[] = scanCase('limits: a scan that would not fit its memory limit is finished in part & says so', 'heavy-nested.zip', array('-memorylimit', '33554432'), array('Incomplete: {path} ('), array('Allowed memory size'), array('files' => 1, 'incomplete' => 1), 'incomplete');
$Case = scanCase('limits: a value that is no number falls back, & a budget that does not fit the memory limit is lowered', 'sample.bin', array('-memorylimit', '16777216', '-chunksize', 'many'), array($HashMatch.')'), array(), array('infected' => 1), 'infected');
$Case['written'] = array('The ChunkSize value is missing or below the minimum of 4096, using 8388608 instead.', 'The ChunkSize of 8388608 does not fit the MemoryLimit, using 4194304 instead.');
$Cases[] = $Case;
$Case = scanCase('answers: a path that does not exist is refused with an error', 'no-such-file.bin', array(), array('The specified file was not found!'), array('Scanned'), array(), '');
$Case['exit'] = 1;
$Cases[] = $Case;

// / These are the answers that are not a scan of the scratch folder, each with the arguments that ask for it.
$Reports = $Scratch.DIRECTORY_SEPARATOR.'reports'.DIRECTORY_SEPARATOR;
$TestConfig = $Scratch.DIRECTORY_SEPARATOR.'test_config.php';
file_put_contents($Scratch.DIRECTORY_SEPARATOR.'broken_config.php', '<?php'."\n".'$ConfigVersion = ;'."\n");
file_put_contents($Scratch.DIRECTORY_SEPARATOR.'old_config.php', '<?php'."\n".'$ConfigVersion = \'v0.1\';'."\n");
$Answers = array();
$Answer = answerCase('answers: -version exits 0, opens with the name & the version & writes no answer to the report', array('-version', '-configfile', $TestConfig, '-reportfile', $Reports.'version.txt'), 0, array('ScanCore '.$CoreVersion.' by '));
$Answer['reportFile'] = $Reports.'version.txt';
$Answer['unwritten'] = array('by Justin Grimes');
$Answers[] = $Answer;
$Answer = answerCase('answers: -help exits 0, lists the switches & writes no answer to the report', array('-help', '-configfile', $TestConfig, '-reportfile', $Reports.'help.txt'), 0, array('ScanCore '.$CoreVersion.' by '));
$Answer['printed'] = array('-updatedefinitions', '-logfile /path/to/file');
$Answer['reportFile'] = $Reports.'help.txt';
$Answer['unwritten'] = array('Optional Arguments Include');
$Answers[] = $Answer;
$Answers[] = answerCase('answers: no path & no switch is refused with exit 1', array('-configfile', $TestConfig, '-reportfile', $Reports.'nothing.txt'), 1, array());
$Answers[] = answerCase('answers: a configuration file that does not exist is refused with ScanCore-2', array($Scan.DIRECTORY_SEPARATOR.'clean.txt', '-configfile', $Scratch.DIRECTORY_SEPARATOR.'no-such-config.php'), 1, array('ERROR!!! ScanCore-2, '));
$Answers[] = answerCase('answers: a configuration file that does not parse is refused with ScanCore-2, & with what is wrong', array($Scan.DIRECTORY_SEPARATOR.'clean.txt', '-configfile', $Scratch.DIRECTORY_SEPARATOR.'broken_config.php'), 1, array('ERROR!!! ScanCore-2, ', 'The interpreter said:'));
$Answers[] = answerCase('answers: a configuration file that is too old is refused with ScanCore-4, & with both versions', array($Scan.DIRECTORY_SEPARATOR.'clean.txt', '-configfile', $Scratch.DIRECTORY_SEPARATOR.'old_config.php'), 1, array('ERROR!!! ScanCore-4, ', 'The file reports v0.1 & this core accepts '));
$Answers[] = answerCase('answers: a definitions file that does not exist is refused with ScanCore-3', array($Scan.DIRECTORY_SEPARATOR.'clean.txt', '-configfile', $TestConfig, '-defsfile', $Scratch.DIRECTORY_SEPARATOR.'no-such.def', '-reportfile', $Reports.'nodefs.txt'), 1, array('ERROR!!! ScanCore-3, '));
$Answer = answerCase('answers: -logfile names the report as -reportfile does', array($Scan.DIRECTORY_SEPARATOR.'sample.bin', '-configfile', $TestConfig, '-defsfile', $Definitions.DIRECTORY_SEPARATOR.'test.def', '-logfile', $Reports.'alias.txt'), 0, array());
$Answer['reportFile'] = $Reports.'alias.txt';
$Answer['written'] = array(', Infected: ');
$Answers[] = $Answer;
if ($HostileWasMade) {
  $Answer = answerCase('report: a name that holds a line break cannot write a line of its own', array($Scan.DIRECTORY_SEPARATOR.'tree', '-recursion', '-verbose', '-configfile', $TestConfig, '-defsfile', $Definitions.DIRECTORY_SEPARATOR.'test.def', '-reportfile', $Reports.'hostile.txt'), 0, array());
  $Answer['unprinted'] = array("\n".'FORGED LINE');
  $Answer['reportFile'] = $Reports.'hostile.txt';
  $Answer['written'] = array('ordinary\x0aFORGED LINE');
  $Answer['unwritten'] = array("\n".'FORGED LINE');
  $Answers[] = $Answer; }
$InstallCase = 'answers: nothing that was run wrote a report folder into the installation';

// / Answer --list & stop.
if ($ListOnly) {
  foreach (array_merge($Cases, $Answers) as $Case) echo $Case['name'].PHP_EOL;
  if ($InstallWritable) echo $InstallCase.PHP_EOL;
  removeTree($Scratch);
  exit(0); }

// / Run the cases.
// / Nothing stops early.
$CasesRun = $CasesFailed = 0;
echo 'Testing '.$CoreFile.', which answers as ScanCore '.$CoreVersion.'.'.PHP_EOL;
foreach (array_merge($Cases, $Answers) as $CaseIndex => $Case) if ($OnlyWord === '' or strpos(strtolower($Case['name']), $OnlyWord) !== FALSE) {
  $CasesRun++;
  $Failures = isset($Case['arguments']) ? judgeAnswer($CoreFile, $Case) : judgeCase($CoreFile, $Scratch, $CaseIndex, $Case);
  if (count($Failures) > 0) $CasesFailed++;
  echo ((count($Failures) > 0) ? '  FAIL  ' : '  pass  ').$Case['name'].PHP_EOL;
  foreach ($Failures as $Failure) echo '        '.str_replace($Scan.DIRECTORY_SEPARATOR, '', $Failure).PHP_EOL; }

// / The installation is looked at last, after everything that might have written to it has run.
if ($InstallWritable && ($OnlyWord === '' or strpos(strtolower($InstallCase), $OnlyWord) !== FALSE)) {
  $CasesRun++;
  $InstallUntouched = ($LogsExistedBefore or !is_dir($InstallDir.DIRECTORY_SEPARATOR.'Logs'));
  if (!$InstallUntouched) $CasesFailed++;
  echo ($InstallUntouched ? '  pass  ' : '  FAIL  ').$InstallCase.PHP_EOL;
  if (!$InstallUntouched) echo '        A Logs folder was made in '.$InstallDir.', although every run was given a report of its own.'.PHP_EOL; }

// / Say what was left out, so a pass is never mistaken for more than it is.
if ($OnWindows) echo '  note  The cases for a link, a long path, a hostile name & a file that cannot be opened were left out on Windows.'.PHP_EOL;
if (!$OnWindows && !$LockedWasMade) echo '  note  The two cases for a file that cannot be opened were left out, because this account can open every file.'.PHP_EOL;
if (!$InstallWritable) echo '  note  The case that nothing writes into the installation was left out, because this account cannot write there in any case.'.PHP_EOL;
if (!$OnWindows && !$LinkWasMade) echo '  note  The case for a link was left out, because a link could not be made.'.PHP_EOL;
if (!$OnWindows && !$HostileWasMade) echo '  note  The case for a name that holds a line break was left out, because such a folder could not be made.'.PHP_EOL;

// / Remove the scratch folder, or say where it is.
if ($KeepScratch) echo 'The scratch folder was kept at '.$Scratch.PHP_EOL;
else removeTree($Scratch);

// / Say how it went, & exit with a status a script can act on.
if ($CasesFailed > 0) {
  echo $CasesFailed.' of '.$CasesRun.' cases failed.'.PHP_EOL;
  exit(1); }
echo 'All '.$CasesRun.' cases passed.'.PHP_EOL;
exit(0);
// / -----------------------------------------------------------------------------------
