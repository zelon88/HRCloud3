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
// / ScanCore Consistency Check.
// / This file holds the parts of ScanCore to each other.
// / The parts are the core, the configuration file, the classifier definitions, the documents & the list of files that an update installs.
// / It reads them & prints what it found.
// / It writes nothing & removes nothing.
// / The only thing it runs is the syntax check of the interpreter, which parses a file & runs none of it.
// / Documentation/ABOUT_QUALITY_CONTROL.txt says what each check holds, what it cannot see & how to prove that it can fail.
// /
// / Run it from the root of an installation, beside ScanCore.php.
// /   php Documentation/Build/scancore-check-cli.php
// /   php Documentation/Build/scancore-check-cli.php --list
// /   php Documentation/Build/scancore-check-cli.php --only switches
// /   php Documentation/Build/scancore-check-cli.php --quiet
// /   php Documentation/Build/scancore-check-cli.php --release
// / The core carries a copy of every default, so every check holds what it reads to the core.
// / An installation whose configuration file has been changed therefore passes, & is told which entries were changed.
// / With --release an entry that is not at its default is a fault, because a release ships every entry at its default.
// / It exits 0 when every check passed, 1 when anything was reported & 2 when it could not run.
// / Nothing stops early, so one run reports everything that there is to report.
// /
// / This file is a tool & is no part of the application.
// / ScanCore.php never loads it, & it loads nothing of ScanCore.php.
// /
// / Dependency Requirements ...
// / This tool requires PHP 8.0 or later with the tokenizer extension.
// / The syntax check also needs an interpreter that may run a command.
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
// / Give up root where it was given, because this tool only reads.
// / The group is given up before the user, because an account that is no longer root may not change its group.
// / SUDO_UID names whoever ran sudo.
// / Without it the account is nobody.
if (function_exists('posix_geteuid') && posix_geteuid() === 0) {
  $LandingGroup = (getenv('SUDO_GID') !== FALSE && ctype_digit(strval(getenv('SUDO_GID')))) ? intval(getenv('SUDO_GID')) : 65534;
  $LandingUser = (getenv('SUDO_UID') !== FALSE && ctype_digit(strval(getenv('SUDO_UID')))) ? intval(getenv('SUDO_UID')) : 65534;
  if (!posix_setgid($LandingGroup) or !posix_setuid($LandingUser)) {
    echo 'ERROR!!! Root could not be given up, so nothing was read.'.PHP_EOL;
    exit(2); }
  echo 'Running as user '.posix_geteuid().' & group '.posix_getegid().', because root is not needed to read.'.PHP_EOL; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to read one file of the tree.
// / Returns its text, which is empty where the file is not there.
function readTreeFile($relativePath) {
  $Text = is_file($relativePath) ? strval(@file_get_contents($relativePath)) : '';
  return $Text; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to say whether a path is something that a running installation makes for itself.
// / A report folder, an index of the definitions, a staged or a retired file & a backup of the configuration file are all of that kind.
// / What git keeps for itself is of that kind as well.
// / None of it is part of a release, so no check reads it.
function isMadeAtRuntime($relativePath, $reportFolders) {
  $baseName = basename($relativePath);
  $MadeAtRuntime = ($baseName === '.git' or in_array($relativePath, $reportFolders, TRUE) or preg_match('/\.(idx|new|old)$/', $baseName) === 1 or preg_match('/_Backup_[0-9]+\.php$/', $baseName) === 1);
  return $MadeAtRuntime; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to list every file beneath a folder, each by its path from the root of the tree.
// / A path is written with forward slashes on every system, as the subscription list writes it.
// / A link is listed & is never followed.
function listTreeFiles($folder, $reportFolders) {
  $Files = array();
  $entryList = @scandir(($folder === '') ? '.' : $folder);
  if (!is_array($entryList)) $entryList = array();
  foreach ($entryList as $entryName) if ($entryName !== '.' && $entryName !== '..') {
    $entryPath = ($folder === '') ? $entryName : $folder.'/'.$entryName;
    if (!isMadeAtRuntime($entryPath, $reportFolders)) {
      if (is_dir($entryPath) && !is_link($entryPath)) $Files = array_merge($Files, listTreeFiles($entryPath, $reportFolders));
      else $Files[] = $entryPath; } }
  sort($Files);
  return $Files; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to read the tokens of PHP source, without its whitespace & its comments.
function meaningfulTokens($source) {
  $Tokens = array();
  foreach (token_get_all($source) as $token) if (!is_array($token) or !in_array($token[0], array(T_WHITESPACE, T_COMMENT, T_DOC_COMMENT, T_OPEN_TAG, T_CLOSE_TAG), TRUE)) $Tokens[] = $token;
  return $Tokens; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to give the text of one token, whichever of its two shapes it has.
function tokenText($token) {
  $Text = is_array($token) ? $token[1] : $token;
  return $Text; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to give the value of a string as the interpreter reads it, from the way it is written in the source.
function stringValue($literal) {
  $Value = substr($literal, 1, -1);
  if (substr($literal, 0, 1) === "'") $Value = str_replace(array('\\\\', '\\\''), array('\\', '\''), $Value);
  else $Value = stripcslashes($Value);
  return $Value; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to read the tokens of one expression, from an index to the first stop mark that sits outside every bracket.
// / The closing bracket of whatever holds the expression ends it as well.
// / Each token is returned with the depth of the brackets that it sits inside.
// / Returns the expression & the index it stopped at, in that order.
function readExpression($tokens, $startIndex, $stopMarks) {
  $Expression = array();
  $StopIndex = $startIndex;
  $depth = 0;
  $stopped = FALSE;
  $tokenCount = count($tokens);
  while ($StopIndex < $tokenCount && !$stopped) {
    $text = tokenText($tokens[$StopIndex]);
    if (in_array($text, array(')', ']', '}'), TRUE)) $depth--;
    if ($depth < 0 or ($depth === 0 && in_array($text, $stopMarks, TRUE))) $stopped = TRUE;
    else {
      $Expression[] = array($tokens[$StopIndex], $depth);
      if (in_array($text, array('(', '[', '{'), TRUE)) $depth++;
      $StopIndex++; } }
  return array($Expression, $StopIndex); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to write an expression back out as it was written, without its spacing.
// / Two expressions that differ in their spacing alone are written out the same.
function expressionText($expression) {
  $Text = '';
  foreach ($expression as $entry) $Text .= tokenText($entry[0]);
  return $Text; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to find every call to a named function in a list of tokens.
// / The definition of the function is not a call to it.
// / Returns each call as the line it is on & the expressions that were handed to it.
function findCalls($tokens, $functionName) {
  $Calls = array();
  foreach ($tokens as $index => $token) if (is_array($token) && $token[0] === T_STRING && $token[1] === $functionName && isset($tokens[$index + 1]) && $tokens[$index + 1] === '(') {
    $isDefinition = ($index > 0 && is_array($tokens[$index - 1]) && $tokens[$index - 1][0] === T_FUNCTION);
    $argumentList = array();
    $argumentIndex = $index + 2;
    $finished = ($isDefinition or tokenText($tokens[$argumentIndex]) === ')');
    while (!$finished) {
      list($expression, $argumentIndex) = readExpression($tokens, $argumentIndex, array(','));
      $argumentList[] = $expression;
      if (!isset($tokens[$argumentIndex]) or tokenText($tokens[$argumentIndex]) !== ',') $finished = TRUE;
      else $argumentIndex++; }
    if (!$isDefinition) $Calls[] = array('line' => $token[2], 'arguments' => $argumentList); }
  return $Calls; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to read the text of a message out of the expression that builds it.
// / A string that is written out is kept as it is.
// / Whatever is joined to it is not known until ScanCore runs, & is kept as one zero byte.
// / The end of line that closes a printed message is no part of the message.
// / countWithNoun() writes a count, a space & a noun, & the noun of any count but one is the last string that it is handed.
// / A count is therefore read as an unknown part that is followed by that noun, which is how a document writes it.
function messageSkeleton($expression) {
  $Skeleton = '';
  $insideCount = FALSE;
  $pluralNoun = '';
  foreach ($expression as $entry) {
    $text = tokenText($entry[0]);
    $isString = (is_array($entry[0]) && $entry[0][0] === T_CONSTANT_ENCAPSED_STRING);
    $isJoin = ($entry[1] === 0 && in_array($text, array('.', '$EOL', 'PHP_EOL'), TRUE));
    if ($entry[1] === 0 && $text === 'countWithNoun') $insideCount = TRUE;
    if ($insideCount && $entry[1] === 1 && $isString) $pluralNoun = stringValue($text);
    if ($entry[1] === 0 && $isString) $Skeleton .= stringValue($text);
    else if (!$isJoin && substr($Skeleton, -1) !== "\0") $Skeleton .= "\0";
    if ($insideCount && $entry[1] === 0 && $text === ')') {
      $Skeleton .= ' '.$pluralNoun;
      $insideCount = FALSE; } }
  return $Skeleton; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to read the text of a message out of a line of a document.
// / A part in angle brackets is filled in when the message is raised, so it is kept as one zero byte.
function documentSkeleton($documentText) {
  $Skeleton = preg_replace('/\x00+/', "\0", preg_replace('/<[a-z][a-z0-9 ]*>/', "\0", trim($documentText)));
  return $Skeleton; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to say whether two message skeletons could be the same message.
// / They agree where either of them, with anything at all allowed in its unknown parts, reads as the other.
// / A skeleton with fewer than ten characters of its own would agree with nearly anything, so it agrees with nothing.
function skeletonsAgree($firstSkeleton, $secondSkeleton) {
  $Agree = FALSE;
  $longEnough = (strlen(str_replace("\0", '', $firstSkeleton)) >= 10 && strlen(str_replace("\0", '', $secondSkeleton)) >= 10);
  foreach (array(array($firstSkeleton, $secondSkeleton), array($secondSkeleton, $firstSkeleton)) as $skeletonPair) {
    $pattern = '/^'.str_replace('\\000', '.*', preg_quote($skeletonPair[0], '/')).'$/s';
    if ($longEnough && preg_match($pattern, $skeletonPair[1]) === 1) $Agree = TRUE; }
  return $Agree; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to write a skeleton the way a person reads it, with three dots for each unknown part.
function shownSkeleton($skeleton) {
  $Shown = str_replace("\0", '...', $skeleton);
  return $Shown; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to compare two version numbers, part by part & as numbers.
// / A leading v is no part of a version.
// / A version that is not made of numbers & dots cannot be compared.
// / Returns -1, 0 or 1 where the first is older than, the same as or newer than the second, & 2 where they cannot be compared.
function compareVersionNumbers($firstVersion, $secondVersion) {
  $Comparison = 2;
  $firstClean = ltrim(strtolower(trim($firstVersion)), 'v');
  $secondClean = ltrim(strtolower(trim($secondVersion)), 'v');
  if (preg_match('/^[0-9]+(\.[0-9]+)*$/', $firstClean) === 1 && preg_match('/^[0-9]+(\.[0-9]+)*$/', $secondClean) === 1) {
    $Comparison = 0;
    $firstParts = array_map('intval', explode('.', $firstClean));
    $secondParts = array_map('intval', explode('.', $secondClean));
    for ($partIndex = 0; $partIndex < max(count($firstParts), count($secondParts)) && $Comparison === 0; $partIndex++) $Comparison = (isset($firstParts[$partIndex]) ? $firstParts[$partIndex] : 0) <=> (isset($secondParts[$partIndex]) ? $secondParts[$partIndex] : 0); }
  return $Comparison; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to work out a number that is written as a product, as the configuration file writes its sizes.
// / Returns the number as text, which is empty where the text is not a number or a product of numbers.
function productValue($productText) {
  $Value = '';
  $product = 1;
  if (preg_match('/^[0-9]+(\*[0-9]+)*$/', $productText) === 1) {
    foreach (explode('*', $productText) as $factor) $product = $product * intval($factor);
    $Value = strval($product); }
  return $Value; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to give the strings that a list is written with, such as the names in  array('one', 'two').
function listedStrings($expressionText) {
  preg_match_all('/\'([^\']*)\'/', $expressionText, $stringParts);
  $Strings = $stringParts[1];
  return $Strings; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to read the entries of the configuration file.
// / An entry is a variable that is given a value outside every bracket, under a comment block that opens with its title.
// / Returns each entry under its name, as its value the way it is written, its title, its stated default & its line.
function readConfigEntries($configSource) {
  $Entries = array();
  $titleText = $defaultText = '';
  foreach (explode("\n", $configSource) as $lineIndex => $configLine) {
    if (preg_match('/^\/\/ \/  --(.+)--$/', $configLine, $lineParts) === 1) {
      $titleText = $lineParts[1];
      $defaultText = ''; }
    if (preg_match('/^\/\/ \/   Default is (.+)\.$/', $configLine, $lineParts) === 1) $defaultText = $lineParts[1];
    if (preg_match('/^\$([A-Za-z0-9_]+) = (.+);$/', $configLine, $lineParts) === 1) {
      $valueTokens = meaningfulTokens('<?php '.$lineParts[2].';');
      list($valueExpression) = readExpression($valueTokens, 0, array(';'));
      $Entries[$lineParts[1]] = array('value' => expressionText($valueExpression), 'title' => $titleText, 'default' => $defaultText, 'line' => $lineIndex + 1);
      $titleText = $defaultText = ''; } }
  return $Entries; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to give the source of one function of the core, from its opening line to the separator that closes it.
function functionSource($coreSource, $functionName) {
  $Source = '';
  if (preg_match('/^function '.preg_quote($functionName, '/').'\(.*?^\/\/ \/ -{20,}$/ms', $coreSource, $sourceParts) === 1) $Source = $sourceParts[0];
  return $Source; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to collect every error & every warning that a PHP file of ScanCore can raise.
// / An error or a warning is raised by processOutput(), or is printed where the report cannot be used yet.
// / A fault of the configuration is gathered in $ConfigFaults where it is met, & every one of them is raised later as a warning.
// / Returns each message as its kind, its number, its skeleton, its file, its line & whether it stops ScanCore & is written to the report.
function collectMessages($fileName, $tokens) {
  $Messages = array();
  foreach (findCalls($tokens, 'processOutput') as $call) if (count($call['arguments']) === 6) {
    $levelText = expressionText($call['arguments'][1]);
    $skeleton = messageSkeleton($call['arguments'][0]);
    if (($levelText === "'error'" or $levelText === "'warning'") && $skeleton !== "\0") $Messages[] = array('kind' => trim($levelText, "'"), 'number' => expressionText($call['arguments'][2]), 'skeleton' => $skeleton, 'file' => $fileName, 'line' => $call['line'], 'fatal' => (expressionText($call['arguments'][5]) === 'TRUE'), 'logged' => (expressionText($call['arguments'][3]) === 'TRUE')); }
  foreach ($tokens as $index => $token) {
    $expression = array();
    // / Collect a fault of the configuration, which is raised as a warning once the report can be used.
    if (is_array($token) && $token[0] === T_VARIABLE && $token[1] === '$ConfigFaults' && isset($tokens[$index + 3]) && $tokens[$index + 1] === '[' && $tokens[$index + 2] === ']' && $tokens[$index + 3] === '=') {
      list($expression) = readExpression($tokens, $index + 4, array(';'));
      $Messages[] = array('kind' => 'warning', 'number' => '0', 'skeleton' => messageSkeleton($expression), 'file' => $fileName, 'line' => $token[2], 'fatal' => FALSE, 'logged' => TRUE); }
    // / Collect an error that is printed with its label, because the report cannot be used where it is raised.
    if (is_array($token) && $token[0] === T_ECHO) {
      list($expression) = readExpression($tokens, $index + 1, array(';'));
      if (preg_match('/^ERROR!!! ScanCore-([0-9]+)(?: on \x00)?, (.*)$/s', messageSkeleton($expression), $labelParts) === 1) $Messages[] = array('kind' => 'error', 'number' => $labelParts[1], 'skeleton' => $labelParts[2], 'file' => $fileName, 'line' => $token[2], 'fatal' => NULL, 'logged' => FALSE); } }
  return $Messages; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to read the error catalogue.
// / An error is a block that opens with the label ERROR MESSAGE, & a block may give more than one message for its number.
// / A retired number is a line that holds the number alone, followed by the release that retired it.
// / Returns the errors by number, the warnings & the retired numbers, in that order.
function readCatalogue($catalogueText) {
  $Errors = $Warnings = $Retired = array();
  $blockNumbers = array();
  $label = '';
  $loneNumber = 0;
  foreach (explode("\n", $catalogueText) as $lineIndex => $catalogueLine) {
    if (preg_match('/^(ERROR MESSAGE|FILE|FATAL|LOGGED|ERROR DESCRIPTION)$/', $catalogueLine, $lineParts) === 1) {
      if ($lineParts[1] === 'ERROR MESSAGE') $blockNumbers = array();
      $label = $lineParts[1]; }
    else if (preg_match('/^-{20,}$/', $catalogueLine) === 1) $label = '';
    else if ($label === 'ERROR MESSAGE' && preg_match('/^\s+ERROR!!! ScanCore-([0-9]+)(?: on <time>)?, (.*)$/', $catalogueLine, $lineParts) === 1) {
      if (!isset($Errors[$lineParts[1]])) $Errors[$lineParts[1]] = array('messages' => array(), 'file' => '', 'fatal' => '', 'logged' => '', 'line' => $lineIndex + 1, 'raised' => FALSE);
      $Errors[$lineParts[1]]['messages'][] = documentSkeleton($lineParts[2]);
      $blockNumbers[] = $lineParts[1]; }
    else if (in_array($label, array('FILE', 'FATAL', 'LOGGED'), TRUE) && trim($catalogueLine) !== '') foreach ($blockNumbers as $blockNumber) {
      if ($Errors[$blockNumber][strtolower($label)] === '') $Errors[$blockNumber][strtolower($label)] = ltrim(trim($catalogueLine), '/'); }
    else if (preg_match('/^ScanCore-([0-9]+)$/', $catalogueLine, $lineParts) === 1) $loneNumber = $lineParts[1];
    else if ($loneNumber !== 0 && preg_match('/^\s+Retired in v[0-9.]+\. /', $catalogueLine) === 1) {
      $Retired[] = $loneNumber;
      $loneNumber = 0; }
    else if (preg_match('/^\s+WARNING!!! ScanCore on <time>, (.*)$/', $catalogueLine, $lineParts) === 1) $Warnings[] = array('skeleton' => documentSkeleton($lineParts[1]), 'line' => $lineIndex + 1, 'raised' => FALSE); }
  return array($Errors, $Warnings, $Retired); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to give one section of a plain text document, by the title that it opens with.
// / Returns the lines between the title & the rule that closes the section.
function documentSection($documentText, $sectionTitle) {
  $Section = '';
  if (preg_match('/^--'.preg_quote($sectionTitle, '/').'--\n(.*?)^-{20,}$/ms', $documentText, $sectionParts) === 1) $Section = $sectionParts[1];
  return $Section; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to read the records of the classifier definitions file.
// / Returns the types, the types that have a handler record, the types that have a magic record, the embed records with their confidence & the lines that are no record, in that order.
function readClassifierRecords($classifierText) {
  $Types = $HandlerTypes = $MagicTypes = $Embeds = $Unknown = array();
  $recordLines = array();
  foreach (explode("\n", $classifierText) as $lineIndex => $classifierLine) if (trim($classifierLine) !== '' && substr(ltrim($classifierLine), 0, 1) !== '#') $recordLines[$lineIndex + 1] = trim($classifierLine);
  foreach ($recordLines as $recordLine) if (preg_match('/^type\s+(\S+)\s*=/', $recordLine, $recordParts) === 1) $Types[] = strtolower($recordParts[1]);
  foreach ($recordLines as $lineNumber => $recordLine) {
    $firstWord = strtolower(strval(strtok($recordLine, " \t")));
    if (preg_match('/^magic\s+(\S+)\s*=/', $recordLine, $recordParts) === 1) $MagicTypes[] = strtolower($recordParts[1]);
    else if (preg_match('/^embed\s+([A-Za-z0-9_]+)\((high|medium|low)\)\s*=/', $recordLine, $recordParts) === 1) $Embeds[$recordParts[1]] = $recordParts[2];
    else if (in_array($firstWord, $Types, TRUE)) $HandlerTypes[] = $firstWord;
    else if ($firstWord !== 'type' && $firstWord !== 'lang') $Unknown[] = $lineNumber; }
  return array($Types, array_values(array_unique($HandlerTypes)), $MagicTypes, $Embeds, $Unknown); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A check that the interpreter parses every PHP file of the tree.
function checkSyntax($Tree) {
  $Findings = array();
  foreach ($Tree['files'] as $treeFile) if (substr($treeFile, -4) === '.php') {
    $outputLines = array();
    $exitStatus = -1;
    if (!function_exists('exec')) $Findings[] = $treeFile.' was not parsed, because this interpreter may not run a command. Run  php -l '.$treeFile.'  by hand.';
    else {
      exec(escapeshellarg(PHP_BINARY).' -l '.escapeshellarg($treeFile).' 2>&1', $outputLines, $exitStatus);
      if ($exitStatus !== 0) $Findings[] = $treeFile.' does not parse. The interpreter said:  '.trim(implode(' ', $outputLines)); } }
  return $Findings; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A check that every file that states a version states the version of the core.
// / A PHP file, the classifier definitions & a plain text document each state it on the line under their File Information label.
// / The changelog also opens with the entry of that version.
// / A line of sample output in a document names the version too, so it is held as well.
// / The changelog & the development notes are history & name every version.
function checkStamps($Tree) {
  $Findings = array();
  $version = $Tree['version'];
  foreach ($Tree['files'] as $treeFile) {
    $fileText = readTreeFile($treeFile);
    $stampPattern = '';
    if (substr($treeFile, -4) === '.php') $stampPattern = '/^\/\/ \/ File Information \.\.\.\n\/\/ \/ (\S+)\.$/m';
    if ($treeFile === $Tree['classifierFile']) $stampPattern = '/^# File Information \.\.\.\n# (\S+)\.$/m';
    if (substr($treeFile, -4) === '.txt') $stampPattern = '/^FILE INFORMATION \.\.\.\n(\S+)\.$/m';
    if ($stampPattern !== '') {
      if (preg_match($stampPattern, $fileText, $stampParts) !== 1) $Findings[] = $treeFile.' states no version. Write  '.$version.'.  on the line under its File Information label.';
      else if ($stampParts[1] !== $version) $Findings[] = $treeFile.' states '.$stampParts[1].' & the core is '.$version.'. Write  '.$version.'.  on the line under its File Information label.'; }
    if (in_array(substr($treeFile, -4), array('.txt', '.php'), TRUE) or substr($treeFile, -3) === '.md') if (!in_array(basename($treeFile), array('CHANGELOG.txt', 'DEVELOPMENT_NOTES.txt'), TRUE)) {
      preg_match_all('/ScanCore (v[0-9]+\.[0-9]+(?:\.[0-9]+)?)\b/', $fileText, $sampleParts);
      foreach (array_unique($sampleParts[1]) as $sampleVersion) if ($sampleVersion !== $version) $Findings[] = $treeFile.' shows ScanCore '.$sampleVersion.' in an example & the core is '.$version.'. Write the example with '.$version.'.'; } }
  $changelogText = readTreeFile('Documentation/CHANGELOG.txt');
  if (preg_match('/^COMMIT [0-9]+\/[0-9]+\/[0-9]+\n(v[0-9.]+) - .+\n\n-(v[0-9.]+)\.$/m', $changelogText, $entryParts) !== 1) $Findings[] = 'Documentation/CHANGELOG.txt opens with no entry of the shape  COMMIT m/d/yyyy,  vX - title.,  a blank line &  -vX.  Write the entry of '.$version.' that way.';
  else if ($entryParts[1] !== $version or $entryParts[2] !== $version) $Findings[] = 'Documentation/CHANGELOG.txt opens with the entry of '.$entryParts[1].' & the core is '.$version.'. Write the entry of '.$version.' above it.';
  return $Findings; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A check that the configuration file & the core agree about every entry.
// / The core must accept the configuration file beside it.
// / Every entry must reach the core, be validated by it & have a title.
// / A default is stated by the fallback of the core, by the check of an override & by the comment above the entry, & the three must be written the same way.
// / The value of an entry is another matter.
// / An administrator may change it, so it is held to its default only where a release is being checked.
function checkConfig($Tree) {
  global $CheckNotes;
  $Findings = array();
  $entries = $Tree['configEntries'];
  $fallbacks = $Tree['fallbacks'];
  $configFile = $Tree['configFile'];
  $titlesSeen = $changedEntries = array();
  $validateSource = functionSource($Tree['coreSource'], 'validateConfig');
  $loadSource = functionSource($Tree['coreSource'], 'loadConfig');
  // / Hold the three versions to each other.
  $configVersion = isset($entries['ConfigVersion']) ? trim($entries['ConfigVersion']['value'], "'") : '';
  if (!in_array(compareVersionNumbers($Tree['requiredConfigVersion'], $configVersion), array(-1, 0), TRUE)) $Findings[] = 'The core requires a configuration file of '.$Tree['requiredConfigVersion'].' & '.$configFile.' says '.$configVersion.', so the core refuses the file beside it. Raise $ConfigVersion in '.$configFile.' to the release that last changed its entries.';
  if (!in_array(compareVersionNumbers($configVersion, $Tree['version']), array(-1, 0), TRUE)) $Findings[] = $configFile.' says '.$configVersion.', which is not a release as old as the core, '.$Tree['version'].'. $ConfigVersion names the release that last changed the entries.';
  // / Hold every entry of the file to the core.
  foreach ($entries as $entryName => $entry) {
    $where = '$'.$entryName.' on line '.$entry['line'].' of '.$configFile;
    $defaultIsWritten = (preg_match('/^(TRUE|FALSE|[0-9\'"$]|[a-z_]+\()/', $entry['default']) === 1);
    $statedDefault = preg_replace('/\s+/', '', $entry['default']);
    if ($entry['title'] === '') $Findings[] = $where.' has no title. Open its comment block with a line that holds its title between two pairs of dashes, as the other entries do.';
    if ($entry['title'] !== '' && in_array($entry['title'], $titlesSeen, TRUE)) $Findings[] = $where.' has the title --'.$entry['title'].'--, which another entry has as well. Give each entry a title of its own.';
    $titlesSeen[] = $entry['title'];
    if ($entryName !== 'ConfigVersion' && $entry['default'] === '') $Findings[] = $where.' states no default. Close its comment block with a line of the shape  // /   Default is <value>.';
    // / The core knows where it is, so the fallback of the installation directory is written its own way.
    if ($defaultIsWritten && isset($fallbacks[$entryName]) && $entryName !== 'InstallDir' && $statedDefault !== $fallbacks[$entryName]['text']) $Findings[] = 'The comment of '.$where.' says that the default is '.$entry['default'].', & the fallback on line '.$fallbacks[$entryName]['line'].' of '.$Tree['coreFile'].' is '.$fallbacks[$entryName]['text'].'. Write the same default in both.';
    // / An entry is at its default where it is written as its comment writes it, or as the core writes it where the comment describes it in words.
    if ($defaultIsWritten && $statedDefault !== $entry['value']) $changedEntries[] = $entryName;
    if (!$defaultIsWritten && isset($fallbacks[$entryName]) && $fallbacks[$entryName]['text'] !== $entry['value']) $changedEntries[] = $entryName;
    foreach (array('loadConfig' => $loadSource, 'validateConfig' => $validateSource) as $functionName => $source) if (preg_match('/^  global .*\$'.$entryName.'\b/m', $source) !== 1) $Findings[] = $where.' is not named in a global statement of '.$functionName.'(), so its value never reaches the core. Add it there.';
    if ($entryName !== 'ConfigVersion' && preg_match('/applyConfigFallback\(\$'.$entryName.',|isset\(\$'.$entryName.'\)/', $validateSource) !== 1) $Findings[] = $where.' is not validated. Give it a fallback in validateConfig().'; }
  // / Hold every fallback of the core to the file.
  foreach ($fallbacks as $entryName => $fallback) {
    $where = 'The fallback of $'.$entryName.' on line '.$fallback['line'].' of '.$Tree['coreFile'];
    if ($fallback['reportedAs'] !== "'".$entryName."'") $Findings[] = $where.' reports itself as '.$fallback['reportedAs'].'. Name the entry that it falls back for.';
    if (!isset($entries[$entryName])) $Findings[] = $where.' has no entry in '.$configFile.'. Add the entry, or remove the fallback.'; }
  foreach (findCalls($Tree['coreTokens'], 'validateNumericSetting') as $call) if (count($call['arguments']) === 4) {
    $entryName = ltrim(expressionText($call['arguments'][0]), '$');
    $defaultText = expressionText($call['arguments'][1]);
    if (substr($defaultText, 0, 1) !== '$' && isset($fallbacks[$entryName]) && $defaultText !== $fallbacks[$entryName]['text']) $Findings[] = 'The override of $'.$entryName.' on line '.$call['line'].' of '.$Tree['coreFile'].' falls back to '.$defaultText.' & the fallback of the entry on line '.$fallbacks[$entryName]['line'].' is '.$fallbacks[$entryName]['text'].'. Write the same default in both.'; }
  // / Hold every default that a document states in a table, as the name of a setting, a number & what the setting does.
  foreach ($Tree['files'] as $treeFile) if (preg_match('/^Documentation\/[A-Z_]+\.txt$/', $treeFile) === 1 && !in_array(basename($treeFile), array('CHANGELOG.txt', 'DEVELOPMENT_NOTES.txt'), TRUE)) {
    foreach (explode("\n", readTreeFile($treeFile)) as $lineIndex => $documentLine) if (preg_match('/^\s+([A-Z][A-Za-z]+)\s{2,}([0-9]+)\s{2,}\S/', $documentLine, $lineParts) === 1) {
      $entryName = isset($fallbacks[$lineParts[1]]) ? $lineParts[1] : 'Default'.$lineParts[1];
      if (isset($fallbacks[$entryName]) && productValue($fallbacks[$entryName]['text']) !== $lineParts[2]) $Findings[] = 'Line '.($lineIndex + 1).' of '.$treeFile.' says that '.$lineParts[1].' is '.$lineParts[2].', & the fallback on line '.$fallbacks[$entryName]['line'].' of '.$Tree['coreFile'].' is '.$fallbacks[$entryName]['text'].'. Write the number that the fallback works out to.'; } }
  // / What an administrator changed is a fault in a release, & is worth saying anywhere else.
  if ($Tree['release']) foreach ($changedEntries as $entryName) $Findings[] = '$'.$entryName.' on line '.$entries[$entryName]['line'].' of '.$configFile.' is not at its default'.((strlen($entries[$entryName]['value']) > 60) ? '' : ', & is '.$entries[$entryName]['value']).'. A release ships every entry at its default.';
  if (!$Tree['release'] && count($changedEntries) > 0) $CheckNotes[] = count($changedEntries).' of the entries in '.$configFile.' are not at their default:  $'.implode(', $', $changedEntries).'. That is a fault only in a release, which --release checks.';
  return $Findings; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A check that every switch is read, is listed by -help & is documented, & that nothing documents a switch that is not read.
// / A switch is read where parseArgs() compares an argument with it, or where loadConfig() looks for it ahead of everything else.
// / Documentation/ABOUT_COMMAND_LINE_ARGUMENTS.txt also names the entry that a switch overrides, its default & its minimum, & each of those is held to the code.
function checkSwitches($Tree) {
  $Findings = array();
  $tokens = $Tree['coreTokens'];
  $readSwitches = $helpSwitches = $guideSwitches = $readmeSwitches = array();
  $entries = $Tree['configEntries'];
  $minimums = $titles = $guideDefaults = array();
  $guideSwitch = '';
  // / Gather what the core reads.
  foreach ($tokens as $index => $token) if (is_array($token) && $token[0] === T_VARIABLE && $token[1] === '$currentSwitch' && isset($tokens[$index + 2]) && tokenText($tokens[$index + 1]) === '===' && is_array($tokens[$index + 2]) && $tokens[$index + 2][0] === T_CONSTANT_ENCAPSED_STRING) $readSwitches[] = stringValue($tokens[$index + 2][1]);
  foreach (findCalls($tokens, 'findSwitch') as $call) if (count($call['arguments']) === 2) foreach ($call['arguments'][1] as $entry) if (is_array($entry[0]) && $entry[0][0] === T_CONSTANT_ENCAPSED_STRING) $readSwitches[] = stringValue($entry[0][1]);
  $readSwitches = array_values(array_unique($readSwitches));
  // / Gather what -help lists.
  foreach ($tokens as $index => $token) if (is_array($token) && $token[0] === T_VARIABLE && $token[1] === '$switchTable' && isset($tokens[$index + 1]) && $tokens[$index + 1] === '=') {
    list($tableExpression) = readExpression($tokens, $index + 2, array(';'));
    foreach ($tableExpression as $entry) if (is_array($entry[0]) && $entry[0][0] === T_CONSTANT_ENCAPSED_STRING && substr(stringValue($entry[0][1]), 0, 1) === '-') $helpSwitches[] = strval(strtok(stringValue($entry[0][1]), ' ')); }
  // / Gather what the documents name.
  $guideText = readTreeFile('Documentation/ABOUT_COMMAND_LINE_ARGUMENTS.txt');
  foreach (explode("\n", $guideText) as $guideLine) if (preg_match('/^  -[a-z]/', $guideLine) === 1) {
    preg_match_all('/(?<![A-Za-z0-9-])-[a-z]+\b/', $guideLine, $switchParts);
    $guideSwitches = array_merge($guideSwitches, $switchParts[0]); }
  preg_match_all('/`(-[a-z]+)(?: [^`]*)?`/', readTreeFile('README.md'), $switchParts);
  $readmeSwitches = array_values(array_unique($switchParts[1]));
  if (count($readSwitches) === 0) $Findings[] = 'No switch could be read out of parseArgs(). This check reads  $currentSwitch === \'-name\'  & must be taught the new shape.';
  foreach (array('the list that -help prints, in buildHelpInformation()' => $helpSwitches, 'Documentation/ABOUT_COMMAND_LINE_ARGUMENTS.txt' => $guideSwitches, 'README.md' => $readmeSwitches) as $placeName => $placeSwitches) {
    if (count(array_diff($readSwitches, $placeSwitches)) > 0) $Findings[] = 'The core reads '.implode(', ', array_diff($readSwitches, $placeSwitches)).', which '.$placeName.' does not name. Add each one there.';
    if (count(array_diff($placeSwitches, $readSwitches)) > 0) $Findings[] = ucfirst($placeName).' names '.implode(', ', array_unique(array_diff($placeSwitches, $readSwitches))).', which the core does not read. Remove each one there, or read it in parseArgs().'; }
  // / Hold each default that README.md gives to the default that the guide gives for the same switch.
  foreach (explode("\n", $guideText) as $guideLine) {
    if (preg_match('/^  (-[a-z]+)/', $guideLine, $lineParts) === 1) $guideSwitch = $lineParts[1];
    if (preg_match('/\. Default ([0-9]+)\./', $guideLine, $lineParts) === 1) $guideDefaults[$guideSwitch] = $lineParts[1]; }
  foreach (explode("\n", readTreeFile('README.md')) as $readmeLine) if (preg_match('/^\| `(-[a-z]+)[^|]*\|.*\bDefault ([0-9]+)\b/', $readmeLine, $lineParts) === 1) {
    if (!isset($guideDefaults[$lineParts[1]]) or $guideDefaults[$lineParts[1]] !== $lineParts[2]) $Findings[] = 'README.md says that the default of '.$lineParts[1].' is '.$lineParts[2].', & Documentation/ABOUT_COMMAND_LINE_ARGUMENTS.txt says '.(isset($guideDefaults[$lineParts[1]]) ? $guideDefaults[$lineParts[1]] : 'nothing about it').'. Write the same number in both.'; }
  // / Hold what the guide says each switch overrides, with its default & its minimum, to the code.
  foreach ($entries as $entryName => $entry) $titles[$entry['title']] = $entryName;
  foreach (findCalls($tokens, 'validateNumericSetting') as $call) if (count($call['arguments']) === 4) $minimums[trim(expressionText($call['arguments'][3]), "'")] = productValue(expressionText($call['arguments'][2]));
  preg_match_all('/--([A-Za-z][A-Za-z ]+)--, \$([A-Za-z]+)\.(?: Default ([0-9]+)\.)?(?: Minimum ([0-9]+)\.)?/', $guideText, $overrideParts, PREG_SET_ORDER);
  foreach ($overrideParts as $override) {
    $settingName = (substr($override[2], 0, 7) === 'Default') ? substr($override[2], 7) : $override[2];
    if (!isset($titles[$override[1]])) $Findings[] = 'Documentation/ABOUT_COMMAND_LINE_ARGUMENTS.txt names the entry --'.$override[1].'--, & no entry of '.$Tree['configFile'].' has that title. Name the entry by its title.';
    else if ($titles[$override[1]] !== $override[2]) $Findings[] = 'Documentation/ABOUT_COMMAND_LINE_ARGUMENTS.txt says that --'.$override[1].'-- is $'.$override[2].', & in '.$Tree['configFile'].' it is $'.$titles[$override[1]].'. Name the variable of that entry.';
    else {
      if (isset($override[3]) && $override[3] !== '' && (!isset($Tree['fallbacks'][$override[2]]) or $override[3] !== productValue($Tree['fallbacks'][$override[2]]['text']))) $Findings[] = 'Documentation/ABOUT_COMMAND_LINE_ARGUMENTS.txt says that the default of $'.$override[2].' is '.$override[3].', & the core falls back to '.(isset($Tree['fallbacks'][$override[2]]) ? $Tree['fallbacks'][$override[2]]['text'] : 'nothing').'. Write the number that the fallback works out to.';
      if (isset($override[4]) && $override[4] !== '' && (!isset($minimums[$settingName]) or $override[4] !== $minimums[$settingName])) $Findings[] = 'Documentation/ABOUT_COMMAND_LINE_ARGUMENTS.txt says that the minimum of $'.$override[2].' is '.$override[4].', & parseArgs() holds it to '.(isset($minimums[$settingName]) ? $minimums[$settingName] : 'no minimum').'. Write the minimum that validateNumericSetting() is handed.'; } }
  return $Findings; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A check that every numbered error the code can raise is in the error catalogue as it is raised, & that the catalogue describes no error that nothing raises.
// / An error is held by its number, its words, the file that raises it, whether it stops ScanCore & whether it is written to the report.
// / A number that the catalogue retires must not be raised again.
function checkErrors($Tree) {
  $Findings = array();
  list($catalogueErrors, $catalogueWarnings, $retiredNumbers) = $Tree['catalogue'];
  if (!in_array('error', array_column($Tree['messages'], 'kind'), TRUE)) $Findings[] = 'No error could be read out of the code. This check reads the calls to processOutput() & must be taught the new shape.';
  foreach ($Tree['messages'] as $message) if ($message['kind'] === 'error') {
    $where = 'Line '.$message['line'].' of '.$message['file'];
    $number = $message['number'];
    $wordsAgree = FALSE;
    if (preg_match('/^[1-9][0-9]*$/', $number) !== 1) $Findings[] = $where.' raises an error whose number is '.$number.'. Write the number as digits, so that it can be held to the catalogue.';
    else if (in_array($number, $retiredNumbers, TRUE)) $Findings[] = $where.' raises ScanCore-'.$number.', which '.$Tree['catalogueFile'].' retires. A retired number is never used again, so give the error a new one.';
    else if (!isset($catalogueErrors[$number])) $Findings[] = $where.' raises ScanCore-'.$number.', which '.$Tree['catalogueFile'].' does not describe. Add an ERROR MESSAGE block for it.';
    else {
      $catalogueErrors[$number]['raised'] = TRUE;
      foreach ($catalogueErrors[$number]['messages'] as $documentedSkeleton) if (skeletonsAgree($message['skeleton'], $documentedSkeleton)) $wordsAgree = TRUE;
      if (!$wordsAgree) $Findings[] = $where.' raises ScanCore-'.$number.' as  '.shownSkeleton($message['skeleton']).'  & '.$Tree['catalogueFile'].' gives other words for it. Write the message there as it is raised.';
      if ($catalogueErrors[$number]['file'] !== $message['file']) $Findings[] = $where.' raises ScanCore-'.$number.', & '.$Tree['catalogueFile'].' says that /'.$catalogueErrors[$number]['file'].' raises it. Name the file that does.';
      if ($message['fatal'] !== NULL && ($catalogueErrors[$number]['fatal'] === 'YES') !== $message['fatal']) $Findings[] = $where.' raises ScanCore-'.$number.' & '.($message['fatal'] ? 'stops' : 'does not stop').' there, & '.$Tree['catalogueFile'].' says FATAL '.$catalogueErrors[$number]['fatal'].'. Make the two agree.';
      if (($catalogueErrors[$number]['logged'] === 'YES') !== $message['logged']) $Findings[] = $where.' raises ScanCore-'.$number.' & '.($message['logged'] ? 'writes' : 'does not write').' it to the report, & '.$Tree['catalogueFile'].' says LOGGED '.$catalogueErrors[$number]['logged'].'. Make the two agree.'; } }
  // / Hold every reason that an error or a warning may give to the catalogue, which lists each one as it is written.
  // / A reason is a string that is given to one of the variables that carry one.
  foreach ($Tree['coreTokens'] as $index => $token) if (is_array($token) && $token[0] === T_VARIABLE && in_array($token[1], array('$VerificationFault', '$ConfigLoadFault', '$VerificationNote', '$LintNote', '$TestNote'), TRUE) && isset($Tree['coreTokens'][$index + 1]) && $Tree['coreTokens'][$index + 1] === '=') {
    list($reasonExpression) = readExpression($Tree['coreTokens'], $index + 2, array(';'));
    $reasonSkeleton = messageSkeleton($reasonExpression);
    // / A reason is listed where a line of the catalogue holds it, with anything at all in its unknown parts.
    $isListed = (preg_match('/'.str_replace('\\000', '.*', preg_quote($reasonSkeleton, '/')).'/', $Tree['catalogueText']) === 1);
    if (strlen(str_replace("\0", '', $reasonSkeleton)) >= 10 && !$isListed) $Findings[] = 'Line '.$token[2].' of '.$Tree['coreFile'].' gives the reason  '.shownSkeleton($reasonSkeleton).'  & '.$Tree['catalogueFile'].' does not list it. Add it under the error or the warning that gives it.'; }
  foreach ($catalogueErrors as $number => $catalogueError) {
    if (!$catalogueError['raised']) $Findings[] = 'Line '.$catalogueError['line'].' of '.$Tree['catalogueFile'].' describes ScanCore-'.$number.', which nothing raises. Replace its block with a line that retires the number.';
    foreach ($catalogueError['messages'] as $documentedSkeleton) {
      $isRaised = FALSE;
      foreach ($Tree['messages'] as $message) if ($message['kind'] === 'error' && $message['number'] === strval($number) && skeletonsAgree($message['skeleton'], $documentedSkeleton)) $isRaised = TRUE;
      if ($catalogueError['raised'] && !$isRaised) $Findings[] = 'Line '.$catalogueError['line'].' of '.$Tree['catalogueFile'].' gives  '.shownSkeleton($documentedSkeleton).'  for ScanCore-'.$number.', & nothing raises that number with those words. Remove the line, or write it as it is raised.'; }
    foreach (array('file', 'fatal', 'logged') as $labelName) if ($catalogueError[$labelName] === '') $Findings[] = 'The block of ScanCore-'.$number.' on line '.$catalogueError['line'].' of '.$Tree['catalogueFile'].' has nothing under its '.strtoupper($labelName).' label. Fill it in.';
    if (in_array(strval($number), $retiredNumbers, TRUE)) $Findings[] = $Tree['catalogueFile'].' both describes & retires ScanCore-'.$number.'. Keep one of the two.'; }
  return $Findings; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A check that every warning the code can raise is in the error catalogue as it is raised, & that the catalogue describes no warning that nothing raises.
// / A warning carries no number, so it is held by its words alone.
function checkWarnings($Tree) {
  $Findings = array();
  list($catalogueErrors, $catalogueWarnings, $retiredNumbers) = $Tree['catalogue'];
  if (!in_array('warning', array_column($Tree['messages'], 'kind'), TRUE)) $Findings[] = 'No warning could be read out of the code. This check reads the calls to processOutput() & must be taught the new shape.';
  foreach ($Tree['messages'] as $message) if ($message['kind'] === 'warning') {
    $isDocumented = FALSE;
    foreach ($catalogueWarnings as $warningIndex => $catalogueWarning) if (skeletonsAgree($message['skeleton'], $catalogueWarning['skeleton'])) {
      $isDocumented = TRUE;
      $catalogueWarnings[$warningIndex]['raised'] = TRUE; }
    if (strlen(str_replace("\0", '', $message['skeleton'])) < 10) $Findings[] = 'Line '.$message['line'].' of '.$message['file'].' raises a warning with fewer than ten characters that are written out, which cannot be held to anything. Write the words that it opens with as a string.';
    else if (!$isDocumented) $Findings[] = 'Line '.$message['line'].' of '.$message['file'].' raises the warning  '.shownSkeleton($message['skeleton']).'  & '.$Tree['catalogueFile'].' does not describe it. Add it under the WARNINGS heading that it belongs to.'; }
  foreach ($catalogueWarnings as $catalogueWarning) if (!$catalogueWarning['raised']) $Findings[] = 'Line '.$catalogueWarning['line'].' of '.$Tree['catalogueFile'].' describes the warning  '.shownSkeleton($catalogueWarning['skeleton']).'  & nothing raises it. Remove it, or write it as it is raised.';
  return $Findings; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A check that Documentation/ABOUT_REPORTS.txt describes what the core prints.
// / It holds every structural finding with its confidence, every way of matching, every reason on an Incomplete line & every line of the summary.
// / Each of the four is held in both directions, so the document describes nothing that the core no longer prints.
function checkReports($Tree) {
  $Findings = array();
  $reportsFile = 'Documentation/ABOUT_REPORTS.txt';
  $reportsText = readTreeFile($reportsFile);
  $codeFindings = $documentFindings = $codeReasons = $codeSummary = array();
  $confidence = '';
  list($types, $handlerTypes, $magicTypes, $embeds) = $Tree['classifiers'];
  // / Hold the structural findings, each with the confidence that it is recorded with.
  foreach (findCalls($Tree['coreTokens'], 'recordDetection') as $call) if (count($call['arguments']) === 6) {
    $findingName = messageSkeleton($call['arguments'][1]);
    if (preg_match('/^[A-Z][A-Za-z]+\.[A-Za-z.]+$/', $findingName) === 1) $codeFindings[$findingName] = trim(expressionText($call['arguments'][4]), "'");
    if (strpos(expressionText($call['arguments'][2]), ' Match\'') !== FALSE && strpos($reportsText, trim(messageSkeleton($call['arguments'][2]), "\0 ")) === FALSE) $Findings[] = 'Line '.$call['line'].' of '.$Tree['coreFile'].' records a detection as a  '.shownSkeleton(messageSkeleton($call['arguments'][2])).'  & '.$reportsFile.' does not name that way of matching. Add it to the list under <how>.'; }
  foreach ($embeds as $embedName => $embedConfidence) $codeFindings['Embedded.'.$embedName] = $embedConfidence;
  foreach (explode("\n", documentSection($reportsText, 'Structural Findings')) as $sectionLine) {
    if (preg_match('/^  (HIGH|MEDIUM|LOW) CONFIDENCE/', $sectionLine, $lineParts) === 1) $confidence = strtolower($lineParts[1]);
    if (preg_match('/^    ([A-Z][A-Za-z]+\.[A-Za-z.]+)\s{2,}\S/', $sectionLine, $lineParts) === 1) $documentFindings[$lineParts[1]] = $confidence; }
  if (count($codeFindings) === 0) $Findings[] = 'No structural finding could be read out of the core. This check reads the calls to recordDetection() & must be taught the new shape.';
  foreach ($codeFindings as $findingName => $findingConfidence) {
    if (!isset($documentFindings[$findingName])) $Findings[] = 'The core reports the finding '.$findingName.' & '.$reportsFile.' does not list it. Add it under --Structural Findings--.';
    else if ($documentFindings[$findingName] !== $findingConfidence) $Findings[] = 'The core reports '.$findingName.' with '.$findingConfidence.' confidence & '.$reportsFile.' lists it under '.$documentFindings[$findingName].' confidence. List it under the confidence that it is reported with.'; }
  foreach (array_diff(array_keys($documentFindings), array_keys($codeFindings)) as $findingName) $Findings[] = $reportsFile.' lists the finding '.$findingName.', which the core does not report. Remove it there.';
  // / Hold the reasons on an Incomplete line.
  foreach (findCalls($Tree['coreTokens'], 'noteIncomplete') as $call) if (count($call['arguments']) === 2) $codeReasons[$call['line']] = messageSkeleton($call['arguments'][1]);
  $documentReasons = array();
  foreach (explode("\n", documentSection($reportsText, 'Why A File Is Incomplete')) as $sectionLine) if (preg_match('/^    ([a-z].*)$/', $sectionLine, $lineParts) === 1) $documentReasons[] = documentSkeleton($lineParts[1]);
  foreach ($codeReasons as $reasonLine => $codeReason) {
    $isDocumented = FALSE;
    foreach ($documentReasons as $documentReason) if (skeletonsAgree($codeReason, $documentReason)) $isDocumented = TRUE;
    if (!$isDocumented) $Findings[] = 'Line '.$reasonLine.' of '.$Tree['coreFile'].' gives the reason  '.shownSkeleton($codeReason).'  on an Incomplete line & '.$reportsFile.' does not list it. Add it under --Why A File Is Incomplete--.'; }
  foreach ($documentReasons as $documentReason) {
    $isGiven = FALSE;
    foreach ($codeReasons as $codeReason) if (skeletonsAgree($codeReason, $documentReason)) $isGiven = TRUE;
    if (!$isGiven) $Findings[] = $reportsFile.' lists the reason  '.shownSkeleton($documentReason).'  & the core never gives it. Remove it there, or write it as it is given.'; }
  // / Hold the lines of the summary, which are the entries that are both written & printed & that open with Scanned or with Found.
  foreach (findCalls($Tree['coreTokens'], 'processOutput') as $call) if (count($call['arguments']) === 6 && expressionText($call['arguments'][3]) === 'TRUE' && expressionText($call['arguments'][4]) === 'TRUE' && preg_match('/^(Scanned|Found) /', messageSkeleton($call['arguments'][0])) === 1) $codeSummary[$call['line']] = messageSkeleton($call['arguments'][0]);
  $documentSummary = array();
  foreach (explode("\n", documentSection($reportsText, 'The Summary')) as $sectionLine) if (preg_match('/^  ((?:Scanned|Found) .*)$/', $sectionLine, $lineParts) === 1) $documentSummary[] = documentSkeleton($lineParts[1]);
  foreach ($codeSummary as $summaryLine => $codeLine) {
    $isDocumented = FALSE;
    foreach ($documentSummary as $documentLine) if (skeletonsAgree($codeLine, $documentLine)) $isDocumented = TRUE;
    if (!$isDocumented) $Findings[] = 'Line '.$summaryLine.' of '.$Tree['coreFile'].' closes a scan with  '.shownSkeleton($codeLine).'  & '.$reportsFile.' does not list that line. Add it under --The Summary--.'; }
  foreach ($documentSummary as $documentLine) {
    $isPrinted = FALSE;
    foreach ($codeSummary as $codeLine) if (skeletonsAgree($codeLine, $documentLine)) $isPrinted = TRUE;
    if (!$isPrinted) $Findings[] = $reportsFile.' lists the summary line  '.shownSkeleton($documentLine).'  & the core never prints it. Remove it there, or write it as it is printed.'; }
  return $Findings; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A check that the classifier definitions & the classifiers that are switched on by default are the same set.
// / The defaults are the ones that the core falls back to, so a configuration file that switches some off is not a fault.
// / Every type must be switched on by default, every default must name a type & every type must have a record that says what it reaches for.
// / Language is the one default that names no type, because it switches the detection of code on.
function checkClassifiers($Tree) {
  $Findings = array();
  list($types, $handlerTypes, $magicTypes, $embeds, $unknownLines) = $Tree['classifiers'];
  $classifierFile = $Tree['classifierFile'];
  $enabledByDefault = array();
  $where = 'The fallback of $EnabledClassifiers in '.$Tree['coreFile'];
  foreach (listedStrings(isset($Tree['fallbacks']['EnabledClassifiers']) ? $Tree['fallbacks']['EnabledClassifiers']['text'] : '') as $enabledName) $enabledByDefault[] = strtolower($enabledName);
  if (count($types) === 0) $Findings[] = 'No type record could be read out of '.$classifierFile.'. A type record has the shape  type <name> = <extension>,<extension>';
  if (!in_array('language', $enabledByDefault, TRUE)) $Findings[] = $where.' does not name Language, so no code is detected by default. Add it there & to the entry in '.$Tree['configFile'].'.';
  foreach (array_diff($types, $enabledByDefault) as $typeName) $Findings[] = $classifierFile.' declares the type '.$typeName.' & it is not switched on by default. Add it to the fallback of $EnabledClassifiers in '.$Tree['coreFile'].' & to the entry in '.$Tree['configFile'].'.';
  foreach (array_diff($enabledByDefault, $types, array('language')) as $enabledName) $Findings[] = $where.' names '.$enabledName.', & '.$classifierFile.' declares no such type. Remove the name, or declare the type.';
  foreach (array_diff($types, $handlerTypes) as $typeName) $Findings[] = $classifierFile.' declares the type '.$typeName.' & gives it no handler record, so nothing is ever reported for it. Add a line that opens with '.$typeName.' & lists its handlers.';
  foreach (array_diff($magicTypes, $types) as $typeName) $Findings[] = $classifierFile.' has a magic record for '.$typeName.', which is not a declared type. Declare the type, or remove the record.';
  foreach ($unknownLines as $unknownLine) $Findings[] = 'Line '.$unknownLine.' of '.$classifierFile.' is no record that ScanCore reads. A record opens with type, lang, magic, embed or the name of a declared type.';
  return $Findings; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A check that an update installs exactly the files that ship.
// / The subscription list that the core falls back to must name every file of the tree & nothing else.
// / Two kinds of file ship & are in no list.
// / A file whose path opens with a dot belongs to the repository & not to an installation.
// / The virus definitions file is installed by a definition update, which builds it from the definitions repository.
// / Every folder must also hold an index.html, & every index.html must be the same file.
function checkFiles($Tree) {
  $Findings = array();
  $shipped = $indexHashes = array();
  $where = 'The fallback of $ApplicationUpdateSubscriptions in '.$Tree['coreFile'];
  $subscribed = listedStrings(isset($Tree['fallbacks']['ApplicationUpdateSubscriptions']) ? $Tree['fallbacks']['ApplicationUpdateSubscriptions']['text'] : '');
  $definitionsName = $Tree['definitionsFile'];
  foreach ($Tree['files'] as $treeFile) if (substr($treeFile, 0, 1) !== '.' && $treeFile !== $definitionsName) $shipped[] = $treeFile;
  if (count($subscribed) === 0) $Findings[] = 'No file could be read out of '.lcfirst($where).'. An update of the application would install nothing.';
  foreach (array_diff($shipped, $subscribed) as $treeFile) $Findings[] = $treeFile.' is in the tree & no update installs it. Add it to the fallback of $ApplicationUpdateSubscriptions in '.$Tree['coreFile'].' & to the entry in '.$Tree['configFile'].', or remove the file.';
  foreach (array_diff($subscribed, $shipped) as $subscribedFile) $Findings[] = $where.' names '.$subscribedFile.', which is not in the tree, so every update of the application would be abandoned. Add the file, or remove the name there & from the entry in '.$Tree['configFile'].'.';
  foreach (array_count_values($subscribed) as $subscribedFile => $timesNamed) if ($timesNamed > 1) $Findings[] = $where.' names '.$subscribedFile.' '.$timesNamed.' times. Name it once.';
  if ($definitionsName !== '' && !in_array($definitionsName, $Tree['files'], TRUE)) $Findings[] = 'The virus definitions file, '.$definitionsName.', is not in the tree. ScanCore stops with error 3 without it.';
  foreach ($shipped as $treeFile) {
    $folder = (dirname($treeFile) === '.') ? '' : dirname($treeFile).'/';
    if (!in_array($folder.'index.html', $Tree['files'], TRUE) && !isset($indexHashes[$folder])) $Findings[] = 'The folder /'.$folder.' holds no index.html. Copy the index.html of the root into it, & subscribe the copy.';
    $indexHashes[$folder] = in_array($folder.'index.html', $Tree['files'], TRUE) ? hash('sha256', readTreeFile($folder.'index.html')) : ''; }
  foreach ($indexHashes as $folder => $indexHash) if ($indexHash !== '' && $indexHash !== $indexHashes['']) $Findings[] = '/'.$folder.'index.html is not the same file as /index.html. Copy the index.html of the root over it.';
  return $Findings; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A check that every text file of the tree is plain.
// / A plain file is UTF-8 with no byte order mark, ends each line with a line feed alone, holds no tab & no space at the end of a line, & ends with one line feed.
// / The licence is the text of its publisher & is left as it was published.
// / The virus definitions file is built elsewhere & is held to its encoding & its line ends alone.
function checkText($Tree) {
  $Findings = array();
  $definitionsName = $Tree['definitionsFile'];
  foreach ($Tree['files'] as $treeFile) if (preg_match('/\.(php|def|txt|md|html)$|^\.git[a-z]+$/', $treeFile) === 1) {
    $fileText = readTreeFile($treeFile);
    $faults = array();
    if (substr($fileText, 0, 3) === "\xEF\xBB\xBF") $faults[] = 'opens with a byte order mark';
    if (strpos($fileText, "\r") !== FALSE) $faults[] = 'holds a carriage return';
    if (preg_match('//u', $fileText) !== 1) $faults[] = 'is not UTF-8';
    if ($treeFile !== $definitionsName) {
      if (strpos($fileText, "\t") !== FALSE) $faults[] = 'holds a tab on line '.(substr_count(substr($fileText, 0, strpos($fileText, "\t")), "\n") + 1);
      if (preg_match('/[ \t]+$/m', $fileText, $faultParts, PREG_OFFSET_CAPTURE) === 1) $faults[] = 'ends line '.(substr_count(substr($fileText, 0, $faultParts[0][1]), "\n") + 1).' with a space';
      if ($fileText !== '' && substr($fileText, -1) !== "\n") $faults[] = 'does not end with a line feed';
      if (substr($fileText, -2) === "\n\n") $faults[] = 'ends with a blank line'; }
    if (count($faults) > 0) $Findings[] = $treeFile.' '.implode(', ', $faults).'. Save it as plain UTF-8 text with line feeds, no tabs & nothing after the last character of a line.'; }
  return $Findings; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A check that every file & every configuration entry that a file names is there.
// / A document is named by its path beneath the Documentation folder, or by its own name where the file that names it sits in that folder.
// / A link in a Markdown file is read against the folder of that file.
// / An entry is named by its title between two pairs of dashes, as the configuration file writes it.
// / The changelog & the development notes are history, & name things that are gone.
// / Neither is read.
function checkReferences($Tree) {
  $Findings = array();
  $titles = array();
  foreach ($Tree['configEntries'] as $entry) $titles[] = $entry['title'];
  foreach ($Tree['files'] as $treeFile) if (preg_match('/\.(php|def|txt|md)$/', $treeFile) === 1 && !in_array(basename($treeFile), array('CHANGELOG.txt', 'DEVELOPMENT_NOTES.txt'), TRUE)) {
    $fileText = readTreeFile($treeFile);
    $namedFiles = array();
    preg_match_all('/\bDocumentation\/[A-Za-z0-9_\/-]+\.(?:txt|md|php|png|html)\b/', $fileText, $pathParts);
    foreach ($pathParts[0] as $namedPath) $namedFiles[] = $namedPath;
    preg_match_all('/(?<![A-Za-z0-9_\/])(?:ABOUT_[A-Z_]+|ERROR_DESCRIPTIONS|DEVELOPMENT_NOTES|CHANGELOG|SECURITY)\.(?:txt|md)\b/', $fileText, $pathParts);
    foreach ($pathParts[0] as $namedDocument) $namedFiles[] = 'Documentation/'.$namedDocument;
    if (substr($treeFile, -3) === '.md') {
      preg_match_all('/\]\(([^)#\s]+)(?:#[^)]*)?\)|\bsrc="([^"]+)"/', $fileText, $linkParts, PREG_SET_ORDER);
      foreach ($linkParts as $link) {
        $target = ($link[1] !== '') ? $link[1] : $link[2];
        $resolved = array();
        foreach (explode('/', ((dirname($treeFile) === '.') ? '' : dirname($treeFile).'/').$target) as $pathPart) {
          if ($pathPart === '..') array_pop($resolved);
          else if ($pathPart !== '.' && $pathPart !== '') $resolved[] = $pathPart; }
        if (preg_match('/^[a-z]+:/', $target) !== 1) $namedFiles[] = implode('/', $resolved); } }
    // / A name is there where it is a file of the tree, or a folder that holds one.
    foreach (array_unique($namedFiles) as $namedFile) if (!in_array($namedFile, $Tree['files'], TRUE) && count(preg_grep('/^'.preg_quote($namedFile, '/').'\//', $Tree['files'])) === 0) $Findings[] = $treeFile.' names '.$namedFile.', which is not in the tree. Add the file, or correct the name.';
    preg_match_all('/(?<!^)(?<!-)--([A-Z][A-Za-z]*(?: [A-Za-z&]+)*)--(?!-)(?!$)/m', $fileText, $titleParts);
    foreach (array_unique($titleParts[1]) as $namedTitle) if (!in_array($namedTitle, $titles, TRUE) && preg_match('/^(?:\/\/ \/ |# )?\s*--'.preg_quote($namedTitle, '/').'--$/m', $Tree['everyText']) !== 1) $Findings[] = $treeFile.' names --'.$namedTitle.'--, which is neither the title of a configuration entry nor the title of a section. Name it as it is written.'; }
  return $Findings; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / The main logic of the program.

// / Read what was asked for.
$ListOnly = in_array('--list', $argv, TRUE);
$Quiet = in_array('--quiet', $argv, TRUE);
$Release = in_array('--release', $argv, TRUE);
$CheckNotes = array();
$OnlyName = (array_search('--only', $argv, TRUE) !== FALSE && isset($argv[array_search('--only', $argv, TRUE) + 1])) ? strtolower($argv[array_search('--only', $argv, TRUE) + 1]) : '';

// / List every check, by the name that --only takes, with one line that says what it holds.
$Checks = array(
  'syntax' => array('checkSyntax', 'The interpreter parses every PHP file.'),
  'stamps' => array('checkStamps', 'Every file that states a version states the version of the core.'),
  'config' => array('checkConfig', 'The configuration file & the core agree about every entry & every default.'),
  'switches' => array('checkSwitches', 'Every switch is read, is listed by -help & is documented, with its default & its minimum.'),
  'errors' => array('checkErrors', 'Every numbered error is in the catalogue as it is raised, & nothing there is stale.'),
  'warnings' => array('checkWarnings', 'Every warning is in the catalogue as it is raised, & nothing there is stale.'),
  'reports' => array('checkReports', 'Every finding, reason & summary line that is printed is in ABOUT_REPORTS.txt, & nothing there is stale.'),
  'classifiers' => array('checkClassifiers', 'The classifier definitions & the classifiers that are on by default are the same set.'),
  'files' => array('checkFiles', 'An update installs exactly the files that ship, & every folder holds the index.html.'),
  'text' => array('checkText', 'Every text file is plain UTF-8 with line feeds, no tabs & no trailing spaces.'),
  'references' => array('checkReferences', 'Every file & every configuration entry that a file names is there.'));

// / Answer --list & stop.
if ($ListOnly) {
  foreach ($Checks as $CheckName => $Check) echo sprintf('  %-12s %s', $CheckName, $Check[1]).PHP_EOL;
  exit(0); }

// / Refuse to start where the answer could not mean anything.
if ($OnlyName !== '' && !isset($Checks[$OnlyName])) {
  echo 'ERROR!!! There is no check named '.$OnlyName.'. Run this file with --list to see every name.'.PHP_EOL;
  exit(2); }
if (!function_exists('token_get_all')) {
  echo 'ERROR!!! The tokenizer extension is required to read the code.'.PHP_EOL;
  exit(2); }
if (!is_file('ScanCore.php') or !is_file('ScanCore_Config.php') or !is_file('Documentation/ERROR_DESCRIPTIONS.txt')) {
  echo 'ERROR!!! Run this file from the root of a ScanCore installation, beside ScanCore.php.'.PHP_EOL;
  exit(2); }

// / Read the tree once, so every check reads the same thing.
$Tree = array('coreFile' => 'ScanCore.php', 'configFile' => 'ScanCore_Config.php', 'catalogueFile' => 'Documentation/ERROR_DESCRIPTIONS.txt', 'release' => $Release, 'fallbacks' => array());
$Tree['coreSource'] = readTreeFile($Tree['coreFile']);
$Tree['coreTokens'] = meaningfulTokens($Tree['coreSource']);
$Tree['configEntries'] = readConfigEntries(readTreeFile($Tree['configFile']));
$Tree['version'] = (preg_match('/^  \$Version = \'(v[0-9]+(?:\.[0-9]+)*)\';$/m', $Tree['coreSource'], $VersionParts) === 1) ? $VersionParts[1] : '';
$Tree['requiredConfigVersion'] = (preg_match('/^  \$RequiredConfigVersion = \'(v[0-9]+(?:\.[0-9]+)*)\';$/m', $Tree['coreSource'], $VersionParts) === 1) ? $VersionParts[1] : '';
// / Read the default of every entry, as the fallback of the core writes it.
foreach (findCalls($Tree['coreTokens'], 'applyConfigFallback') as $FallbackCall) if (count($FallbackCall['arguments']) === 4) $Tree['fallbacks'][ltrim(expressionText($FallbackCall['arguments'][0]), '$')] = array('text' => expressionText($FallbackCall['arguments'][1]), 'reportedAs' => expressionText($FallbackCall['arguments'][2]), 'line' => $FallbackCall['line']);
$Tree['classifierFile'] = isset($Tree['fallbacks']['ClassifierFileName']) ? trim($Tree['fallbacks']['ClassifierFileName']['text'], "'") : 'ScanCore_Classifiers.def';
$Tree['definitionsFile'] = isset($Tree['fallbacks']['DefsFileName']) ? trim($Tree['fallbacks']['DefsFileName']['text'], "'") : 'ScanCore_Combined_Definitions.def';
$Tree['classifiers'] = readClassifierRecords(readTreeFile($Tree['classifierFile']));
$Tree['catalogueText'] = readTreeFile($Tree['catalogueFile']);
$Tree['catalogue'] = readCatalogue($Tree['catalogueText']);
$Tree['messages'] = array_merge(collectMessages($Tree['coreFile'], $Tree['coreTokens']), collectMessages($Tree['configFile'], meaningfulTokens(readTreeFile($Tree['configFile']))));
// / The report folder is the one that the core falls back to, & the one that the configuration file names where that is another.
$Tree['files'] = listTreeFiles('', array(isset($Tree['fallbacks']['ReportDir']) ? trim($Tree['fallbacks']['ReportDir']['text'], "'") : 'Logs', isset($Tree['configEntries']['ReportDir']) ? trim($Tree['configEntries']['ReportDir']['value'], "'") : 'Logs'));
$Tree['everyText'] = '';
foreach ($Tree['files'] as $TreeFile) if (preg_match('/\.(php|def|txt|md)$/', $TreeFile) === 1) $Tree['everyText'] .= readTreeFile($TreeFile)."\n";
if ($Tree['version'] === '' or $Tree['requiredConfigVersion'] === '') {
  echo 'ERROR!!! The version of the core could not be read out of '.$Tree['coreFile'].', so nothing could be held to it.'.PHP_EOL;
  exit(2); }

// / Run the checks.
// / Nothing stops early.
$ChecksRun = $ChecksFailed = 0;
if (!$Quiet) echo 'Checking ScanCore '.$Tree['version'].' in '.getcwd().'.'.PHP_EOL;
foreach ($Checks as $CheckName => $Check) if ($OnlyName === '' or $OnlyName === $CheckName) {
  $ChecksRun++;
  $CheckNotes = array();
  $Findings = call_user_func($Check[0], $Tree);
  if (count($Findings) > 0) $ChecksFailed++;
  if (!$Quiet) echo sprintf('  %s  %-12s %s', (count($Findings) > 0) ? 'FAIL' : 'pass', $CheckName, $Check[1]).PHP_EOL;
  if (!$Quiet) foreach ($Findings as $Finding) echo '        '.$Finding.PHP_EOL;
  if (!$Quiet) foreach ($CheckNotes as $CheckNote) echo '        note  '.$CheckNote.PHP_EOL; }

// / Say how it went, & exit with a status that a script can act on.
if ($ChecksFailed > 0) {
  if (!$Quiet) echo $ChecksFailed.' of '.$ChecksRun.' checks reported something.'.PHP_EOL;
  exit(1); }
if (!$Quiet) echo ($ChecksRun === 1) ? 'The check passed.'.PHP_EOL : 'Every check passed.'.PHP_EOL;
exit(0);
// / -----------------------------------------------------------------------------------
