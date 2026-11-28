<?php
// / -----------------------------------------------------------------------------------
// / Copyright Information ...
// / HRProprietary Engine, Copyright on 11/26/2026 by Justin Grimes, www.github.com/zelon88
// /
// / License Information ...
// / This project is protected by the GNU GPLv3 Open-Source license.
// / https://www.gnu.org/licenses/gpl-3.0.html
// /
// / File Information ...
// / v5.0.0.
// / The Page Core, one of the Engine's cores, from v5.0.0.
// / It builds a page for a browser from the components of an interface, & holds no markup of its own.
// / An interface is a folder of components in the application's interface folder, which an administrator may add, choose or remove.
// / Every interface inherits from the base interface the contract names, one level deep & no further. A component an interface
// / does not carry is taken from the base, so an interface carries only what it changes. The base is also the interface every
// / other falls back to.
// / The contract names each page as the components it is drawn from, in order, the assets a page links, & the function that
// / gives a page the application's data. The administrator chooses the interfaces, languages & colors offered.
// / An interface's own version is pinned exactly by the application, as $RequiredInterfaceVersion. A language pack's version is
// / compared with $RequiredLanguageVersion & a mismatch is a warning, because a pack is loaded over a baseline pack string by string.
// / Where $Verbose is TRUE every page it builds is logged with the version, the interface & the fingerprint of every file it was
// / built from, so an error in a page can be traced to one component by elimination.
// / The application pins it exactly via $RequiredPageCoreVersion, & loads it with the Engine's loadPageCore() when it serves a page.
// / Error block 51000 through 51019 is reserved for the Page Core.
// / See Documentation/ABOUT_PAGES.txt.
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
$PageCoreVersion = 'v5.0.0';
// / -----------------------------------------------------------------------------------


// / -----------------------------------------------------------------------------------
// / A function to say whether a string may name an interface.
// / Accepts the name. Returns TRUE when it may.
// / A name is 1 to 64 letters, digits & underscores, because it is a folder's name & a part of every address a page links.
// / Every pattern this core holds a name to ends with D, so a line break at the end of a name is refused rather than ignored.
function interfaceNameIsUsable($interfaceName) {
  // / Set variables.
  global $EnableMemoryProtection;
  $NameIsUsable = (is_string($interfaceName) && preg_match('/^[A-Za-z0-9_]{1,64}$/D', $interfaceName) === 1);
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $interfaceName);
  return $NameIsUsable; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to say whether a string may name a language pack.
// / Accepts the code. Returns TRUE when it may.
// / A code is two or three lower case letters, as ISO 639 writes them, with an optional region of 2 to 8 letters & digits.
function languageCodeIsUsable($languageCode) {
  // / Set variables.
  global $EnableMemoryProtection;
  $CodeIsUsable = (is_string($languageCode) && preg_match('/^[a-z]{2,3}(-[A-Za-z0-9]{2,8})?$/D', $languageCode) === 1);
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $languageCode);
  return $CodeIsUsable; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to say whether a string may name a color an interface offers.
// / Accepts the name. Returns TRUE when it may.
// / A color is a lower case letter & up to 31 more letters & digits, because a page writes it into an attribute.
function colorNameIsUsable($colorName) {
  // / Set variables.
  global $EnableMemoryProtection;
  $ColorIsUsable = (is_string($colorName) && preg_match('/^[a-z][a-z0-9]{0,31}$/D', $colorName) === 1);
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $colorName);
  return $ColorIsUsable; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to say whether a string may name a file inside an interface.
// / Accepts the path. Returns TRUE when it may.
// / A path is relative to the interface's folder, made of letters, digits, dots, dashes, underscores & slashes, & no part of it
// / is empty, a dot or two dots, so it can never leave the interface it names a file of.
function interfacePathIsUsable($relativePath) {
  // / Set variables.
  global $EnableMemoryProtection;
  $PathIsUsable = FALSE;
  $pathParts = array();
  $pathPart = '';
  if (is_string($relativePath) && strlen($relativePath) <= 255 && preg_match('/^[A-Za-z0-9_][A-Za-z0-9_.\/-]*$/D', $relativePath) === 1) {
    $PathIsUsable = TRUE;
    $pathParts = explode('/', $relativePath);
    foreach ($pathParts as $pathPart) if ($pathPart === '' or $pathPart === '.' or $pathPart === '..') $PathIsUsable = FALSE; }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $pathParts, $pathPart, $relativePath);
  return $PathIsUsable; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to read what the contract & the administrator say about pages.
// / Accepts nothing. Returns the settings, keyed by what this core calls them, & a sanity check, in that order.
// / Returns a sanity check, because it is longer than 35 lines. The settings are whole when the base is among the interfaces
// / offered, English among the languages, & every list is a list, which is what every other function of this core relies on.
// / The contract names the interface folder, the base interface, the pages, the assets & the page data provider.
// / Each administrator's setting is read through applicationSetting(), so this core never knows what the application calls it.
// / Each has a fallback configuration cannot overwrite, so a page can always be drawn from the base interface in English.
// / A page or an asset the contract names that could never be used is left out & named in a warning.
function pageCoreSettings() {
  // / Set variables.
  global $EngineInterfaceFolder, $EngineBaseInterface, $EnginePages, $EngineInterfaceAssets, $EnginePageDataProvider, $EnableMemoryProtection;
  $PageSettings = array();
  $SettingsAreWhole = FALSE;
  $defaultBase = 'Default';
  $defaultFolder = 'UI';
  $baseInterface = $interfaceFolder = $pageName = $componentName = $assetName = $assetPath = $languageCode = $languageName = $colorName = $interfaceEntry = '';
  $supportedInterfaces = $supportedLanguages = $supportedColors = $contractPages = $contractAssets = $pageComponents = $usableComponents = $configuredLanguages = $configuredColors = $configuredInterfaces = array();
  $pageIsUsable = FALSE;
  $baseInterface = (isset($EngineBaseInterface) && interfaceNameIsUsable($EngineBaseInterface)) ? $EngineBaseInterface : $defaultBase;
  $interfaceFolder = (isset($EngineInterfaceFolder) && interfaceNameIsUsable($EngineInterfaceFolder)) ? $EngineInterfaceFolder : $defaultFolder;
  // / The interfaces offered. The base is always among them, because every other inherits from it & falls back to it.
  $configuredInterfaces = applicationSetting('SupportedInterfaces', array($baseInterface));
  if (is_array($configuredInterfaces)) foreach ($configuredInterfaces as $interfaceEntry) if (interfaceNameIsUsable($interfaceEntry) && !in_array($interfaceEntry, $supportedInterfaces, TRUE)) $supportedInterfaces[] = $interfaceEntry;
  if (!in_array($baseInterface, $supportedInterfaces, TRUE)) $supportedInterfaces[] = $baseInterface;
  // / The languages offered, each by its code & its endonym. English is always among them, because it is the last rung of the ladder.
  $configuredLanguages = applicationSetting('SupportedLanguages', array('en' => 'English'));
  if (is_array($configuredLanguages)) foreach ($configuredLanguages as $languageCode => $languageName) if (languageCodeIsUsable($languageCode) && is_string($languageName) && trim($languageName) !== '') $supportedLanguages[$languageCode] = $languageName;
  if (!isset($supportedLanguages['en'])) $supportedLanguages['en'] = 'English';
  // / The colors offered. An empty list offers every color the interface does.
  $configuredColors = applicationSetting('SupportedColors', array());
  if (is_array($configuredColors)) foreach ($configuredColors as $colorName) if (colorNameIsUsable($colorName) && !in_array($colorName, $supportedColors, TRUE)) $supportedColors[] = $colorName;
  // / Each page is the components it is drawn from, in order. A page with any component that is not a file name is left out whole.
  $contractPages = (isset($EnginePages) && is_array($EnginePages)) ? $EnginePages : array();
  foreach ($contractPages as $pageName => $pageComponents) {
    $pageIsUsable = (is_string($pageName) && preg_match('/^[a-z][a-z0-9_-]{0,31}$/D', $pageName) === 1 && is_array($pageComponents) && count($pageComponents) > 0);
    $usableComponents = array();
    if ($pageIsUsable) foreach ($pageComponents as $componentName) {
      if (is_string($componentName) && preg_match('/^[A-Za-z0-9_]{1,64}\.php$/D', $componentName) === 1) $usableComponents[] = $componentName;
      else $pageIsUsable = FALSE; }
    if ($pageIsUsable) $PageSettings['Pages'][$pageName] = $usableComponents;
    else warningEntry('The page '.printableText((string)$pageName, TRUE).' the contract declares is not a page name with a list of components, so it was left out.'); }
  // / Each asset is a file a page links, named by what a page calls it & found by its path inside an interface.
  $contractAssets = (isset($EngineInterfaceAssets) && is_array($EngineInterfaceAssets)) ? $EngineInterfaceAssets : array();
  foreach ($contractAssets as $assetName => $assetPath) {
    if (is_string($assetName) && preg_match('/^[A-Za-z][A-Za-z0-9]{0,63}$/D', $assetName) === 1 && interfacePathIsUsable($assetPath)) $PageSettings['Assets'][$assetName] = $assetPath;
    else warningEntry('The asset '.printableText((string)$assetName, TRUE).' the contract declares is not a name with a path inside an interface, so it was left out.'); }
  $PageSettings['Folder'] = $interfaceFolder;
  $PageSettings['Base'] = $baseInterface;
  if (!isset($PageSettings['Pages'])) $PageSettings['Pages'] = array();
  if (!isset($PageSettings['Assets'])) $PageSettings['Assets'] = array();
  $PageSettings['Provider'] = (isset($EnginePageDataProvider) && is_string($EnginePageDataProvider)) ? trim($EnginePageDataProvider) : '';
  $PageSettings['SupportedInterfaces'] = $supportedInterfaces;
  $PageSettings['DefaultInterface'] = interfaceNameIsUsable(applicationSetting('DefaultInterface', $baseInterface)) ? (string)applicationSetting('DefaultInterface', $baseInterface) : $baseInterface;
  $PageSettings['AllowInterfaceChoice'] = (applicationSetting('AllowUserSelectableInterface', TRUE) === TRUE);
  $PageSettings['SupportedLanguages'] = $supportedLanguages;
  $PageSettings['DefaultLanguage'] = languageCodeIsUsable(applicationSetting('DefaultLanguage', 'en')) ? (string)applicationSetting('DefaultLanguage', 'en') : 'en';
  $PageSettings['AllowLanguageChoice'] = (applicationSetting('AllowUserSelectableLanguage', TRUE) === TRUE);
  $PageSettings['SupportedColors'] = $supportedColors;
  $PageSettings['DefaultColor'] = colorNameIsUsable(applicationSetting('DefaultColor', '')) ? (string)applicationSetting('DefaultColor', '') : '';
  $PageSettings['AllowColorChoice'] = (applicationSetting('AllowUserSelectableColor', TRUE) === TRUE);
  $PageSettings['MarkComponents'] = (applicationSetting('MarkPageComponents', FALSE) === TRUE);
  $SettingsAreWhole = (in_array($PageSettings['Base'], $PageSettings['SupportedInterfaces'], TRUE) && isset($PageSettings['SupportedLanguages']['en'])
    && is_array($PageSettings['Pages']) && is_array($PageSettings['Assets']) && is_array($PageSettings['SupportedColors']));
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $defaultBase, $defaultFolder, $baseInterface, $interfaceFolder, $pageName, $componentName, $assetName, $assetPath, $languageCode, $languageName, $colorName, $supportedInterfaces, $supportedLanguages, $supportedColors, $contractPages, $contractAssets, $pageComponents, $usableComponents, $configuredLanguages, $configuredColors, $configuredInterfaces, $pageIsUsable, $interfaceEntry);
  return array($PageSettings, $SettingsAreWhole); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to read the version a file declares, without executing it.
// / Accepts the path & the name of the variable it is declared in. Returns the version, without a leading v, or an empty string.
// / An interface & a language pack are judged before they are loaded, as every component is.
function readDeclaredVersion($filePath, $versionName) {
  // / Set variables.
  global $EnableMemoryProtection;
  $DeclaredVersion = '';
  $fileContents = '';
  $versionMatches = array();
  if (is_string($filePath) && is_file($filePath) && !is_link($filePath)) $fileContents = (string)@file_get_contents($filePath);
  if ($fileContents !== '' && preg_match('/\$'.preg_quote((string)$versionName, '/').'\s*=\s*\'([^\']+)\'/', $fileContents, $versionMatches) === 1) $DeclaredVersion = ltrim(trim($versionMatches[1]), 'vV');
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $fileContents, $versionMatches, $filePath, $versionName);
  return $DeclaredVersion; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to read the version stamp written in a file's header, which says when the file was last changed.
// / Accepts the path. Returns the stamp without its leading v, as every version the Page Core reports is shown, or unstamped.
// / The stamp is the first line that holds nothing but a version & a full stop, inside a comment, as every component, script,
// / stylesheet & image of an interface writes it in its header. It is documentation, & decides nothing.
function readFileStamp($filePath) {
  // / Set variables.
  global $EnableMemoryProtection;
  $FileStamp = 'unstamped';
  $fileContents = '';
  $stampMatches = array();
  if (is_string($filePath) && is_file($filePath) && !is_link($filePath)) $fileContents = (string)@file_get_contents($filePath, FALSE, NULL, 0, 8192);
  if ($fileContents !== '' && preg_match('/^[ \t]*(?:\/\/ \/|\/\/|\*|#|<!--)?[ \t]*v([0-9]+\.[0-9]+\.[0-9]+(?:u?r)?)\.[ \t]*(?:-->)?[ \t]*\r?$/m', $fileContents, $stampMatches) === 1) $FileStamp = $stampMatches[1];
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $fileContents, $stampMatches, $filePath);
  return $FileStamp; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to name exactly which copy of a file a page was built from.
// / Accepts the path. Returns the first 12 hexadecimal digits of its SHA-256, or unreadable.
// / A header stamp says when a file was last changed only where whoever changed it remembered to say so. The fingerprint
// / cannot forget, so two copies that differ are never mistaken for each other in the log.
function fileFingerprint($filePath) {
  // / Set variables.
  global $EnableMemoryProtection;
  $Fingerprint = 'unreadable';
  $fullHash = '';
  if (is_string($filePath) && is_file($filePath) && !is_link($filePath)) $fullHash = (string)@hash_file('sha256', $filePath);
  if (strlen($fullHash) === 64) $Fingerprint = substr($fullHash, 0, 12);
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $fullHash, $filePath);
  return $Fingerprint; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to find a file of an interface, in the interface or in the base it inherits from.
// / Accepts the settings, the interface & the file's path inside it. Returns whether it was found, its path & the interface it was found in, in that order.
// / The interface is looked in first & the base second. A link is never followed & never counts as the file.
function resolveInterfaceFile($pageSettings, $interfaceName, $relativePath) {
  // / Set variables.
  global $InstLoc, $EnableMemoryProtection;
  $FileWasFound = FALSE;
  $FilePath = $FileLayer = '';
  $candidateLayers = array();
  $candidateLayer = $candidatePath = '';
  $candidateLayers = array((string)$interfaceName);
  if ((string)$interfaceName !== (string)$pageSettings['Base']) $candidateLayers[] = (string)$pageSettings['Base'];
  if (interfacePathIsUsable($relativePath)) foreach ($candidateLayers as $candidateLayer) {
    $candidatePath = $InstLoc.DIRECTORY_SEPARATOR.$pageSettings['Folder'].DIRECTORY_SEPARATOR.$candidateLayer.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $relativePath);
    clearstatcache(TRUE, $candidatePath);
    if (!$FileWasFound && is_file($candidatePath) && !is_link($candidatePath)) list ($FileWasFound, $FilePath, $FileLayer) = array(TRUE, $candidatePath, $candidateLayer); }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $candidateLayers, $candidateLayer, $candidatePath, $pageSettings, $interfaceName, $relativePath);
  return array($FileWasFound, $FilePath, $FileLayer); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to read the colors an interface offers from its styleCore.php.
// / Accepts the path of the style file. Returns the colors it offers, in the order it offers them.
// / The style file only assigns values. It sets $interfaceColors, which is initialized here first, so a style file that
// / sets nothing leaves an empty list rather than an undefined variable.
function readInterfaceColors($styleFilePath) {
  // / Set variables.
  global $CoreLoaded, $EnableMemoryProtection;
  $InterfaceColors = array();
  $interfaceColors = array();
  $colorName = '';
  if (is_string($styleFilePath) && is_file($styleFilePath) && !is_link($styleFilePath)) require ($styleFilePath);
  if (is_array($interfaceColors)) foreach ($interfaceColors as $colorName) if (colorNameIsUsable($colorName) && !in_array($colorName, $InterfaceColors, TRUE)) $InterfaceColors[] = $colorName;
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $interfaceColors, $colorName, $styleFilePath);
  return $InterfaceColors; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to judge whether an interface can draw every page, & to find every file it would be drawn from.
// / Accepts the settings & the interface. Returns whether it can, what was found & why it cannot, in that order.
// / What was found holds the interface's name, its version, the base & the base's version, each component & each asset by
// / where it was found, & the colors it offers. Why it cannot is a problem's kind & its detail, for the caller to say.
// / An interface declares its own version in its own uiVersionInfo.php, which is never inherited, & a version is an exact match.
// / An interface that inherits needs its base to declare the version this application requires as well.
// / styleCore.php is a component of every interface, beside every component a page names.
// / Returns a sanity check, because it is longer than 35 lines.
function verifyInterfaceCandidate($pageSettings, $interfaceName) {
  // / Set variables.
  global $InstLoc, $RequiredInterfaceVersion, $EnableMemoryProtection;
  $CandidateIsUsable = FALSE;
  $CandidateRecord = array();
  $CandidateProblem = array('Kind' => '', 'Detail' => '');
  $CandidateIsJudged = FALSE;
  $interfaceRoot = $interfaceFolder = $requiredVersion = $foundVersion = $baseVersion = $componentName = $assetName = $assetPath = $filePath = $fileLayer = $pageName = '';
  $neededComponents = $pageComponents = $offeredColors = array();
  $fileWasFound = FALSE;
  $interfaceRoot = $InstLoc.DIRECTORY_SEPARATOR.$pageSettings['Folder'];
  $requiredVersion = ltrim(trim((string)$RequiredInterfaceVersion), 'vV');
  $interfaceFolder = $interfaceRoot.DIRECTORY_SEPARATOR.(string)$interfaceName;
  if (!interfaceNameIsUsable($interfaceName)) $CandidateProblem = array('Kind' => 'name', 'Detail' => '');
  else if (!is_dir($interfaceFolder) or is_link($interfaceFolder)) $CandidateProblem = array('Kind' => 'missing', 'Detail' => $interfaceRoot);
  else {
    $foundVersion = readDeclaredVersion($interfaceFolder.DIRECTORY_SEPARATOR.'uiVersionInfo.php', 'InterfaceVersion');
    if ((string)$interfaceName !== (string)$pageSettings['Base']) $baseVersion = readDeclaredVersion($interfaceRoot.DIRECTORY_SEPARATOR.$pageSettings['Base'].DIRECTORY_SEPARATOR.'uiVersionInfo.php', 'InterfaceVersion');
    else $baseVersion = $foundVersion;
    if ($foundVersion === '') $CandidateProblem = array('Kind' => 'unversioned', 'Detail' => '');
    else if ($foundVersion !== $requiredVersion) $CandidateProblem = array('Kind' => 'version', 'Detail' => $foundVersion);
    else if ($baseVersion !== $requiredVersion) $CandidateProblem = array('Kind' => 'base', 'Detail' => '');
    else {
      $neededComponents = array('styleCore.php');
      foreach ($pageSettings['Pages'] as $pageName => $pageComponents) foreach ($pageComponents as $componentName) if (!in_array($componentName, $neededComponents, TRUE)) $neededComponents[] = $componentName;
      $CandidateRecord = array('Name' => (string)$interfaceName, 'Version' => $foundVersion, 'Base' => (string)$pageSettings['Base'], 'BaseVersion' => $baseVersion, 'Files' => array(), 'Assets' => array(), 'Colors' => array());
      foreach ($neededComponents as $componentName) {
        list ($fileWasFound, $filePath, $fileLayer) = resolveInterfaceFile($pageSettings, $interfaceName, $componentName);
        if ($fileWasFound) $CandidateRecord['Files'][$componentName] = array('Path' => $filePath, 'Layer' => $fileLayer);
        else if ($CandidateProblem['Kind'] === '') $CandidateProblem = array('Kind' => 'file', 'Detail' => $componentName); }
      foreach ($pageSettings['Assets'] as $assetName => $assetPath) {
        list ($fileWasFound, $filePath, $fileLayer) = resolveInterfaceFile($pageSettings, $interfaceName, $assetPath);
        if ($fileWasFound) $CandidateRecord['Assets'][$assetName] = array('Path' => $filePath, 'Layer' => $fileLayer, 'Relative' => $assetPath);
        else if ($CandidateProblem['Kind'] === '') $CandidateProblem = array('Kind' => 'asset', 'Detail' => $assetPath); }
      if ($CandidateProblem['Kind'] === '') {
        $offeredColors = readInterfaceColors($CandidateRecord['Files']['styleCore.php']['Path']);
        if (count($offeredColors) === 0) $CandidateProblem = array('Kind' => 'colors', 'Detail' => '');
        else {
          $CandidateRecord['Colors'] = $offeredColors;
          $CandidateIsUsable = TRUE; } } } }
  if (!$CandidateIsUsable) $CandidateRecord = array();
  // / The judgement is reached once a problem was found or the interface was found whole, & never both.
  $CandidateIsJudged = ($CandidateIsUsable xor $CandidateProblem['Kind'] !== '');
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $interfaceRoot, $interfaceFolder, $requiredVersion, $foundVersion, $baseVersion, $componentName, $assetName, $assetPath, $filePath, $fileLayer, $pageName, $neededComponents, $pageComponents, $offeredColors, $fileWasFound, $pageSettings, $interfaceName);
  return array($CandidateIsUsable, $CandidateRecord, $CandidateProblem, $CandidateIsJudged); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to say in the log why an interface could not be used.
// / Accepts the settings, the interface & the problem verifyInterfaceCandidate() found. Returns TRUE once it was said.
// / Each kind of problem is its own sentence, so the catalogue documents each.
function warnInterfaceProblem($pageSettings, $interfaceName, $candidateProblem) {
  // / Set variables.
  global $RequiredInterfaceVersion, $EnableMemoryProtection;
  $ProblemWasSaid = TRUE;
  $shownName = $shownDetail = $baseName = '';
  $shownName = printableText((string)$interfaceName, TRUE);
  $shownDetail = printableText((string)($candidateProblem['Detail'] ?? ''), TRUE);
  $baseName = (string)$pageSettings['Base'];
  if ($candidateProblem['Kind'] === 'name') warningEntry('An interface was named that cannot be a folder name, so it was not used.');
  else if ($candidateProblem['Kind'] === 'missing') warningEntry('The interface '.$shownName.' could not be used, because it has no folder in '.$shownDetail.'.');
  else if ($candidateProblem['Kind'] === 'unversioned') warningEntry('The interface '.$shownName.' could not be used, because its uiVersionInfo.php declares no $InterfaceVersion.');
  else if ($candidateProblem['Kind'] === 'version') warningEntry('The interface '.$shownName.' reports version '.$shownDetail.' & this application requires '.ltrim((string)$RequiredInterfaceVersion, 'vV').'. It was not used.');
  else if ($candidateProblem['Kind'] === 'base') warningEntry('The interface '.$shownName.' could not be used, because the interface it inherits from, '.$baseName.', does not report the version this application requires.');
  else if ($candidateProblem['Kind'] === 'file') warningEntry('The interface '.$shownName.' could not be used, because its component '.$shownDetail.' is in neither it nor '.$baseName.'.');
  else if ($candidateProblem['Kind'] === 'asset') warningEntry('The interface '.$shownName.' could not be used, because its asset '.$shownDetail.' is in neither it nor '.$baseName.'.');
  else if ($candidateProblem['Kind'] === 'colors') warningEntry('The interface '.$shownName.' could not be used, because its styleCore.php offers no color.');
  else $ProblemWasSaid = FALSE;
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $shownName, $shownDetail, $baseName, $pageSettings, $interfaceName, $candidateProblem);
  return $ProblemWasSaid; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to choose the interface a page is drawn with.
// / Accepts the settings & the interface the request asked for. Returns whether one can be used & what was found of it, in that order.
// / The ladder is the request's choice where users may choose & it is offered, then the administrator's default, then the base.
// / A request asking for an interface that is not offered is not an error, & is passed over without a word.
// / Every rung that is tried & cannot be used is a warning, & the next is tried. A user cannot choose their way out of a broken
// / interface, so the fallback is silent to them & said in the log. Only the base failing as well is an error, 51000.
function choosePageInterface($pageSettings, $requestedInterface) {
  // / Set variables.
  global $EnableMemoryProtection;
  $InterfaceIsSet = FALSE;
  $InterfaceRecord = array();
  $candidateInterfaces = $candidateRecord = $candidateProblem = array();
  $candidateInterface = '';
  $candidateIsUsable = $candidateIsJudged = $problemWasSaid = FALSE;
  if ($pageSettings['AllowInterfaceChoice'] && interfaceNameIsUsable($requestedInterface) && in_array($requestedInterface, $pageSettings['SupportedInterfaces'], TRUE)) $candidateInterfaces[] = (string)$requestedInterface;
  if (in_array($pageSettings['DefaultInterface'], $pageSettings['SupportedInterfaces'], TRUE) && !in_array($pageSettings['DefaultInterface'], $candidateInterfaces, TRUE)) $candidateInterfaces[] = $pageSettings['DefaultInterface'];
  if (!in_array($pageSettings['Base'], $candidateInterfaces, TRUE)) $candidateInterfaces[] = $pageSettings['Base'];
  foreach ($candidateInterfaces as $candidateInterface) {
    if ($InterfaceIsSet) break;
    list ($candidateIsUsable, $candidateRecord, $candidateProblem, $candidateIsJudged) = verifyInterfaceCandidate($pageSettings, $candidateInterface);
    if (!$candidateIsJudged) warningEntry('The interface '.printableText($candidateInterface, TRUE).' could not be judged, so it was not used.');
    else if ($candidateIsUsable) list ($InterfaceIsSet, $InterfaceRecord) = array(TRUE, $candidateRecord);
    else $problemWasSaid = warnInterfaceProblem($pageSettings, $candidateInterface, $candidateProblem); }
  if (!$InterfaceIsSet) errorEntry('No interface could be used, not even '.$pageSettings['Base'].', so no page can be drawn!', 51000, FALSE);
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $candidateInterfaces, $candidateRecord, $candidateProblem, $candidateInterface, $candidateIsUsable, $candidateIsJudged, $problemWasSaid, $pageSettings, $requestedInterface);
  return array($InterfaceIsSet, $InterfaceRecord); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to choose the language a page is drawn in.
// / Accepts the settings, the interface chosen & the language the request asked for. Returns whether a pack can be used & what was found of it, in that order.
// / The ladder is the request's choice where users may choose & it is offered, then the administrator's default, then English.
// / A pack is Languages/<code>/languageStrings.php, in the interface or in the base it inherits from, & the first rung that has one wins.
// / The next rung that has one is the baseline, which is loaded first so a pack that lacks a string inherits it.
// / A pack whose $LanguageVersion is not the one this application requires is a warning & is still used, because the baseline
// / underneath gives every string the page reads a value. Only English missing as well is an error, 51001.
// / Returns a sanity check, because it is longer than 35 lines.
function choosePageLanguage($pageSettings, $interfaceRecord, $requestedLanguage) {
  // / Set variables.
  global $RequiredLanguageVersion, $EnableMemoryProtection;
  $LanguageIsSet = FALSE;
  $LanguageRecord = array();
  $LanguageIsJudged = FALSE;
  $candidateLanguages = array();
  $candidateLanguage = $stringsPath = $stringsLayer = $requiredVersion = $shownInterface = '';
  $stringsWereFound = $baselineWasChosen = FALSE;
  $candidateIndex = 0;
  $requiredVersion = ltrim(trim((string)$RequiredLanguageVersion), 'vV');
  $shownInterface = printableText((string)($interfaceRecord['Name'] ?? ''), TRUE);
  if ($pageSettings['AllowLanguageChoice'] && languageCodeIsUsable($requestedLanguage) && isset($pageSettings['SupportedLanguages'][$requestedLanguage])) $candidateLanguages[] = (string)$requestedLanguage;
  if (isset($pageSettings['SupportedLanguages'][$pageSettings['DefaultLanguage']]) && !in_array($pageSettings['DefaultLanguage'], $candidateLanguages, TRUE)) $candidateLanguages[] = $pageSettings['DefaultLanguage'];
  if (!in_array('en', $candidateLanguages, TRUE)) $candidateLanguages[] = 'en';
  foreach ($candidateLanguages as $candidateIndex => $candidateLanguage) {
    if ($baselineWasChosen) break;
    list ($stringsWereFound, $stringsPath, $stringsLayer) = resolveInterfaceFile($pageSettings, (string)($interfaceRecord['Name'] ?? ''), 'Languages/'.$candidateLanguage.'/languageStrings.php');
    if (!$stringsWereFound) {
      // / The last rung failing is error 51001 below, so only a rung with another after it is a warning.
      if (!$LanguageIsSet && $candidateIndex < count($candidateLanguages) - 1) warningEntry('The interface '.$shownInterface.' carries no '.$candidateLanguage.' language pack, so the next language was tried.'); }
    else if (!$LanguageIsSet) {
      $LanguageRecord = array('Code' => $candidateLanguage, 'Path' => $stringsPath, 'Layer' => $stringsLayer, 'Version' => readDeclaredVersion($stringsPath, 'LanguageVersion'),
        'BaselineCode' => '', 'BaselinePath' => '', 'BaselineLayer' => '');
      $LanguageIsSet = TRUE; }
    else list ($LanguageRecord['BaselineCode'], $LanguageRecord['BaselinePath'], $LanguageRecord['BaselineLayer'], $baselineWasChosen) = array($candidateLanguage, $stringsPath, $stringsLayer, TRUE); }
  if (!$LanguageIsSet) errorEntry('No language pack could be used in the interface '.$shownInterface.', not even en, so no page can be drawn!', 51001, FALSE);
  else if ($LanguageRecord['Version'] !== $requiredVersion && $LanguageRecord['BaselineCode'] !== '') warningEntry('The language pack '.$LanguageRecord['Code'].' reports version '.printableText(($LanguageRecord['Version'] === '' ? 'none' : $LanguageRecord['Version']), TRUE).' & this application requires '.$requiredVersion.'. Any string it lacks is taken from the '.$LanguageRecord['BaselineCode'].' pack.');
  else if ($LanguageRecord['Version'] !== $requiredVersion) warningEntry('The language pack '.$LanguageRecord['Code'].' reports version '.printableText(($LanguageRecord['Version'] === '' ? 'none' : $LanguageRecord['Version']), TRUE).' & this application requires '.$requiredVersion.', & no other pack is there to give a string it lacks.');
  // / The judgement is reached once a pack was chosen or none could be.
  $LanguageIsJudged = ($LanguageIsSet === (count($LanguageRecord) > 0));
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $candidateLanguages, $candidateLanguage, $candidateIndex, $stringsPath, $stringsLayer, $requiredVersion, $shownInterface, $stringsWereFound, $baselineWasChosen, $pageSettings, $interfaceRecord, $requestedLanguage);
  return array($LanguageIsSet, $LanguageRecord, $LanguageIsJudged); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to choose the color a page is drawn in.
// / Accepts the settings, the interface chosen & the color the request asked for. Returns the color.
// / A color is offered where the interface offers it & the administrator's list names it, or names none.
// / The ladder is the request's choice where users may choose & it is offered, then the administrator's default, then the
// / first the interface offers. A default the interface does not offer is a warning, because the administrator chose it.
function choosePageColor($pageSettings, $interfaceRecord, $requestedColor) {
  // / Set variables.
  global $EnableMemoryProtection;
  $ColorToUse = '';
  $offeredColors = array();
  $offeredColor = '';
  foreach ((array)($interfaceRecord['Colors'] ?? array()) as $offeredColor) if (count($pageSettings['SupportedColors']) === 0 or in_array($offeredColor, $pageSettings['SupportedColors'], TRUE)) $offeredColors[] = $offeredColor;
  if (count($offeredColors) === 0) $offeredColors = (array)($interfaceRecord['Colors'] ?? array());
  if ($pageSettings['AllowColorChoice'] && colorNameIsUsable($requestedColor) && in_array($requestedColor, $offeredColors, TRUE)) $ColorToUse = (string)$requestedColor;
  else if ($pageSettings['DefaultColor'] !== '' && in_array($pageSettings['DefaultColor'], $offeredColors, TRUE)) $ColorToUse = $pageSettings['DefaultColor'];
  else {
    $ColorToUse = isset($offeredColors[0]) ? (string)$offeredColors[0] : '';
    if ($pageSettings['DefaultColor'] !== '') warningEntry('The default color '.$pageSettings['DefaultColor'].' is not one the interface '.printableText((string)($interfaceRecord['Name'] ?? ''), TRUE).' offers, so '.$ColorToUse.' is used.'); }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $offeredColors, $offeredColor, $pageSettings, $interfaceRecord, $requestedColor);
  return $ColorToUse; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to choose the interface, the language & the color every page of a request is drawn with.
// / Accepts the interface, the language & the color the request asked for, each sanitized by the application & each possibly empty.
// / Returns whether a page can be drawn, & the interface, the language & the color chosen, in that order.
// / What was chosen is kept in $PageSelection, which buildEnginePage() draws from, so a request decides once.
// / $InterfaceToUse, $LanguageToUse & $ColorToUse name the choices, so a page can carry them to the next.
function preparePages($requestedInterface, $requestedLanguage, $requestedColor) {
  // / Set variables.
  global $PageSelection, $InterfaceToUse, $LanguageToUse, $ColorToUse, $EnableMemoryProtection;
  $PagesAreReady = FALSE;
  $pageSettings = $interfaceRecord = $languageRecord = array();
  $interfaceIsSet = $languageIsSet = $languageIsJudged = $settingsAreWhole = FALSE;
  $InterfaceToUse = $LanguageToUse = $ColorToUse = '';
  $PageSelection = array('Ready' => FALSE);
  list ($pageSettings, $settingsAreWhole) = pageCoreSettings();
  if (!$settingsAreWhole) warningEntry('The Page Core could not read its settings whole, so no page can be drawn.');
  else list ($interfaceIsSet, $interfaceRecord) = choosePageInterface($pageSettings, $requestedInterface);
  if ($interfaceIsSet) list ($languageIsSet, $languageRecord, $languageIsJudged) = choosePageLanguage($pageSettings, $interfaceRecord, $requestedLanguage);
  if ($interfaceIsSet && $languageIsSet && $languageIsJudged) {
    $InterfaceToUse = $interfaceRecord['Name'];
    $LanguageToUse = $languageRecord['Code'];
    $ColorToUse = choosePageColor($pageSettings, $interfaceRecord, $requestedColor);
    $PageSelection = array('Ready' => TRUE, 'Settings' => $pageSettings, 'Interface' => $interfaceRecord, 'Language' => $languageRecord, 'Color' => $ColorToUse);
    $PagesAreReady = TRUE; }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $pageSettings, $interfaceRecord, $languageRecord, $interfaceIsSet, $languageIsSet, $languageIsJudged, $settingsAreWhole, $requestedInterface, $requestedLanguage, $requestedColor);
  return array($PagesAreReady, $InterfaceToUse, $LanguageToUse, $ColorToUse); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to give each asset a page links the address a browser fetches it at.
// / Accepts what preparePages() chose. Returns the addresses, keyed by the names the contract gives the assets.
// / An address is relative to the page, & names the interface the file was found in, so an error a browser reports in a
// / script or a stylesheet names the interface it came from. It carries the file's modification time, so a browser fetches
// / a file again the moment it changes & caches it for as long as it does not.
function pageAssetAddresses($pageSelection) {
  // / Set variables.
  global $EnableMemoryProtection;
  $AssetAddresses = array();
  $assetName = $assetAddress = '';
  $assetRecord = array();
  $assetTime = 0;
  foreach ((array)($pageSelection['Interface']['Assets'] ?? array()) as $assetName => $assetRecord) {
    $assetTime = (int)@filemtime($assetRecord['Path']);
    $assetAddress = $pageSelection['Settings']['Folder'].'/'.$assetRecord['Layer'].'/'.$assetRecord['Relative'];
    $AssetAddresses[$assetName] = $assetAddress.($assetTime > 0 ? '?v='.$assetTime : ''); }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $assetName, $assetAddress, $assetRecord, $assetTime, $pageSelection);
  return $AssetAddresses; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to list what a page may offer a user to choose between.
// / Accepts what preparePages() chose. Returns the interfaces, the languages, by their code & endonym, & the colors on offer, keyed by those names.
// / Only what a user may choose is listed, so a page that draws the lists offers nothing an administrator turned off.
// / An interface is listed only where it can be used, from v5.0.0, so a user is never offered one that would fall back.
// / Each other interface offered is judged as a request for it would be, without a word, because -v says why one cannot be used.
// / A language is listed only where the interface chosen carries its pack, or the base does.
function pageChoices($pageSelection) {
  // / Set variables.
  global $EnableMemoryProtection;
  $PageChoices = array('Interfaces' => array(), 'Languages' => array(), 'Colors' => array());
  $pageSettings = $candidateRecord = $candidateProblem = array();
  $languageCode = $languageName = $colorName = $stringsPath = $stringsLayer = $interfaceName = '';
  $stringsWereFound = $candidateIsUsable = $candidateIsJudged = FALSE;
  $pageSettings = $pageSelection['Settings'];
  if ($pageSettings['AllowInterfaceChoice']) foreach ($pageSettings['SupportedInterfaces'] as $interfaceName) {
    if ($interfaceName === $pageSelection['Interface']['Name']) list ($candidateIsUsable, $candidateIsJudged) = array(TRUE, TRUE);
    else list ($candidateIsUsable, $candidateRecord, $candidateProblem, $candidateIsJudged) = verifyInterfaceCandidate($pageSettings, $interfaceName);
    if (!$candidateIsJudged) warningEntry('The interface '.printableText((string)$interfaceName, TRUE).' could not be judged, so it was not used.');
    else if ($candidateIsUsable) $PageChoices['Interfaces'][] = (string)$interfaceName; }
  if ($pageSettings['AllowLanguageChoice']) foreach ($pageSettings['SupportedLanguages'] as $languageCode => $languageName) {
    list ($stringsWereFound, $stringsPath, $stringsLayer) = resolveInterfaceFile($pageSettings, $pageSelection['Interface']['Name'], 'Languages/'.$languageCode.'/languageStrings.php');
    if ($stringsWereFound) $PageChoices['Languages'][$languageCode] = $languageName; }
  if ($pageSettings['AllowColorChoice']) foreach ($pageSelection['Interface']['Colors'] as $colorName) if (count($pageSettings['SupportedColors']) === 0 or in_array($colorName, $pageSettings['SupportedColors'], TRUE)) $PageChoices['Colors'][] = $colorName;
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $pageSettings, $candidateRecord, $candidateProblem, $languageCode, $languageName, $colorName, $stringsPath, $stringsLayer, $interfaceName, $stringsWereFound, $candidateIsUsable, $candidateIsJudged, $pageSelection);
  return $PageChoices; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to describe one file a page was built from, for the log.
// / Accepts what the file is called, its path & the interface it was found in. Returns the description.
// / A description names the interface, the stamp in the file's header & the fingerprint of what the file holds now.
function describePageFile($fileLabel, $filePath, $fileLayer) {
  // / Set variables.
  global $EnableMemoryProtection;
  $FileDescription = '';
  $FileDescription = (string)$fileLabel.' from '.(string)$fileLayer.' '.readFileStamp($filePath).' #'.fileFingerprint($filePath);
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $fileLabel, $filePath, $fileLayer);
  return $FileDescription; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to describe everything a page is about to be built from, for the log.
// / Accepts the page & what preparePages() chose. Returns the description.
// / It names the Page Core & the application, the interface & its base with their versions, the language pack & its baseline,
// / the color, every component in the order it is drawn, the style file & every asset the page links. Each file is named with
// / the interface it was found in, its header stamp & its fingerprint, so a fault in the page can be traced to one file.
function describePageBuild($pageName, $pageSelection) {
  // / Set variables.
  global $PageCoreVersion, $EngineApplicationVersion, $EnableMemoryProtection;
  $BuildDescription = '';
  $interfaceRecord = $languageRecord = $componentParts = $assetParts = $assetRecord = array();
  $componentName = $assetName = $interfaceText = $languageText = '';
  $interfaceRecord = $pageSelection['Interface'];
  $languageRecord = $pageSelection['Language'];
  $interfaceText = $interfaceRecord['Name'].' '.$interfaceRecord['Version'].(($interfaceRecord['Name'] !== $interfaceRecord['Base']) ? ' over '.$interfaceRecord['Base'].' '.$interfaceRecord['BaseVersion'] : '');
  $languageText = $languageRecord['Code'].' '.($languageRecord['Version'] === '' ? 'unversioned' : $languageRecord['Version']).(($languageRecord['BaselineCode'] !== '') ? ' over '.$languageRecord['BaselineCode'] : '');
  foreach ((array)($pageSelection['Settings']['Pages'][$pageName] ?? array()) as $componentName) $componentParts[] = describePageFile($componentName, $interfaceRecord['Files'][$componentName]['Path'], $interfaceRecord['Files'][$componentName]['Layer']);
  $componentParts[] = describePageFile('styleCore.php', $interfaceRecord['Files']['styleCore.php']['Path'], $interfaceRecord['Files']['styleCore.php']['Layer']);
  $componentParts[] = describePageFile('Languages/'.$languageRecord['Code'], $languageRecord['Path'], $languageRecord['Layer']);
  if ($languageRecord['BaselineCode'] !== '') $componentParts[] = describePageFile('Languages/'.$languageRecord['BaselineCode'], $languageRecord['BaselinePath'], $languageRecord['BaselineLayer']);
  foreach ((array)$interfaceRecord['Assets'] as $assetName => $assetRecord) $assetParts[] = describePageFile($assetName, $assetRecord['Path'], $assetRecord['Layer']);
  $BuildDescription = 'Built the '.$pageName.' page with the Page Core '.ltrim((string)$PageCoreVersion, 'vV').' for '.engineApplicationName().' '.ltrim((string)$EngineApplicationVersion, 'vV')
    .'. Interface '.$interfaceText.', language '.$languageText.', color '.$pageSelection['Color'].'. Components: '.implode(', ', $componentParts).'. Assets: '.(count($assetParts) > 0 ? implode(', ', $assetParts) : 'none').'.';
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $interfaceRecord, $languageRecord, $componentParts, $assetParts, $assetRecord, $componentName, $assetName, $interfaceText, $languageText, $pageName, $pageSelection);
  return $BuildDescription; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to write the comment that marks where a component begins or ends in a page.
// / Accepts whether it begins, the component & what preparePages() chose. Returns the comment.
// / A component is named exactly as the provenance entry in the log names it, so a marker found in a page can be matched to that entry.
// / A comment never carries two dashes together, so nothing a file is called can end it early.
function pageComponentMarker($markerBegins, $componentName, $pageSelection) {
  // / Set variables.
  global $EnableMemoryProtection;
  $ComponentMarker = '';
  $componentRecord = array();
  $componentRecord = $pageSelection['Interface']['Files'][$componentName];
  if ($markerBegins) $ComponentMarker = 'Page component begins: '.describePageFile($componentName, $componentRecord['Path'], $componentRecord['Layer']);
  else $ComponentMarker = 'Page component ends: '.$componentName;
  $ComponentMarker = PHP_EOL.'<!-- '.str_replace('--', '- -', $ComponentMarker).' -->'.PHP_EOL;
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $componentRecord, $markerBegins, $componentName, $pageSelection);
  return $ComponentMarker; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to build a page from the components the contract names for it, & send it.
// / Accepts the page. Returns whether it was built, & a sanity check, in that order.
// / preparePages() must have chosen an interface & a language first. A page the contract does not declare is error 51002,
// / & a page asked for before anything was chosen is 51003. Neither draws anything, so the application can say so plainly.
// / Every component of the page is required from inside this function, so the language strings are function locals that
// / nothing outside this call can read. The baseline pack is required first & the chosen pack over it, so every string a
// / component reads has a value. Each component sees the same variables.
// /   $PageName           The page being drawn.
// /   $PageData           What the application's page data provider gave for it.
// /   $InterfaceToUse     The interface, $LanguageToUse the language & $ColorToUse the color chosen.
// /   $InterfaceAssets    The address of each asset, by the name the contract gives it.
// /   $PageChoices        The interfaces, languages & colors a user may choose between.
// /   $EnginePageState    What the Engine says about the request, as the Authentication Core sets it.
// / This function's own variables begin pageCore, so no component or pack can overwrite one by naming its own.
// / Where $Verbose is TRUE every page is logged before it is drawn, with every file it is drawn from, so even a page that
// / stops part way names what it was being built from. Where the administrator asks, each component is marked in the page.
// / Returns a sanity check, because it is longer than 35 lines.
function buildEnginePage($pageCoreRequestedPage) {
  // / Set variables.
  global $CoreLoaded, $PageSelection, $PageName, $PageData, $InterfaceToUse, $LanguageToUse, $ColorToUse, $InterfaceAssets, $PageChoices, $EnginePageState, $Verbose, $EnableMemoryProtection;
  $PageWasBuilt = FALSE;
  $PageIsJudged = FALSE;
  $pageCoreComponents = array();
  $pageCoreComponent = $pageCoreProvider = '';
  $pageCoreMarks = FALSE;
  $PageName = $PageData = $InterfaceAssets = $PageChoices = NULL;
  if (!is_array($PageSelection) or empty($PageSelection['Ready'])) errorEntry('A page was asked to be drawn before an interface & a language were chosen!', 51003, FALSE);
  else if (!is_string($pageCoreRequestedPage) or !isset($PageSelection['Settings']['Pages'][$pageCoreRequestedPage])) errorEntry('A page named '.printableText((string)$pageCoreRequestedPage, TRUE).' was asked for & the contract declares none by that name!', 51002, FALSE);
  else {
    $PageName = $pageCoreRequestedPage;
    $pageCoreComponents = $PageSelection['Settings']['Pages'][$PageName];
    $pageCoreMarks = (bool)$PageSelection['Settings']['MarkComponents'];
    $pageCoreProvider = $PageSelection['Settings']['Provider'];
    $PageData = array();
    if ($pageCoreProvider !== '' && function_exists($pageCoreProvider)) $PageData = (array)$pageCoreProvider($PageName);
    else if ($pageCoreProvider !== '') warningEntry('A page data provider named '.printableText($pageCoreProvider, TRUE).' was declared & does not exist. The page '.$PageName.' is drawn without the application\'s data.');
    $InterfaceAssets = pageAssetAddresses($PageSelection);
    $PageChoices = pageChoices($PageSelection);
    if ($Verbose) logEntry(describePageBuild($PageName, $PageSelection));
    // / The baseline loads first, & the chosen pack over it, so a string the chosen pack lacks keeps the baseline's value.
    if ($PageSelection['Language']['BaselinePath'] !== '') require ($PageSelection['Language']['BaselinePath']);
    require ($PageSelection['Language']['Path']);
    foreach ($pageCoreComponents as $pageCoreComponent) {
      if ($pageCoreMarks) print(pageComponentMarker(TRUE, $pageCoreComponent, $PageSelection));
      require ($PageSelection['Interface']['Files'][$pageCoreComponent]['Path']);
      if ($pageCoreMarks) print(pageComponentMarker(FALSE, $pageCoreComponent, $PageSelection)); }
    $PageWasBuilt = TRUE; }
  // / The judgement is reached once the page was built or refused, which is every path above.
  $PageIsJudged = ($PageWasBuilt or $PageName === NULL);
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  // / The language strings are left to go out of scope with this call, as a language pack's strings always are.
  purgeSensitiveMemory($EnableMemoryProtection, $pageCoreComponents, $pageCoreComponent, $pageCoreProvider, $pageCoreMarks, $pageCoreRequestedPage);
  return array($PageWasBuilt, $PageIsJudged); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to describe every interface an administrator offers, & the language packs each carries, for the report -v prints.
// / Accepts nothing. Returns the report's sections, each a list of rows with a label, a text & whether the row failed.
// / An interface is OK where it could draw every page, with its version, & says which interface it inherits from.
// / A language pack counts as OK where the interface or its base carries it & it reports the version this application requires.
// / Nothing is drawn & nothing is logged, because -v changes nothing.
function pageCoreReportSections() {
  // / Set variables.
  global $RequiredLanguageVersion, $EnableMemoryProtection;
  $ReportSections = array('Installed interfaces' => array(), 'Language packs' => array());
  $pageSettings = $candidateRecord = $candidateProblem = $failedLanguages = array();
  $interfaceName = $languageCode = $languageName = $stringsPath = $stringsLayer = $requiredVersion = $rowText = '';
  $candidateIsUsable = $candidateIsJudged = $stringsWereFound = $settingsAreWhole = FALSE;
  $packsOk = 0;
  list ($pageSettings, $settingsAreWhole) = pageCoreSettings();
  // / Settings that are not whole are said once, & no interface is judged with them.
  if (!$settingsAreWhole) {
    $ReportSections['Installed interfaces'][] = array('Label' => 'Page Core', 'Text' => 'FAILED, its settings could not be read whole', 'Failed' => TRUE);
    $pageSettings['SupportedInterfaces'] = array(); }
  $requiredVersion = ltrim(trim((string)$RequiredLanguageVersion), 'vV');
  foreach ($pageSettings['SupportedInterfaces'] as $interfaceName) {
    list ($candidateIsUsable, $candidateRecord, $candidateProblem, $candidateIsJudged) = verifyInterfaceCandidate($pageSettings, $interfaceName);
    if ($candidateIsUsable) $rowText = 'OK, '.$candidateRecord['Version'].(($interfaceName !== $pageSettings['Base']) ? ', inherits from '.$pageSettings['Base'] : ', the base');
    else if ($candidateProblem['Kind'] === 'missing') $rowText = 'MISSING, no folder';
    else if ($candidateProblem['Kind'] === 'version') $rowText = 'FAILED, reports '.$candidateProblem['Detail'];
    else if ($candidateProblem['Kind'] === 'unversioned') $rowText = 'FAILED, reports no version';
    else if ($candidateProblem['Kind'] === 'base') $rowText = 'FAILED, its base '.$pageSettings['Base'].' does not match';
    else if ($candidateProblem['Kind'] === 'file' or $candidateProblem['Kind'] === 'asset') $rowText = 'FAILED, '.$candidateProblem['Detail'].' is missing';
    else if ($candidateProblem['Kind'] === 'colors') $rowText = 'FAILED, offers no color';
    else $rowText = 'FAILED, could not be judged';
    $ReportSections['Installed interfaces'][] = array('Label' => $interfaceName, 'Text' => $rowText, 'Failed' => !$candidateIsUsable);
    $packsOk = 0;
    $failedLanguages = array();
    foreach ($pageSettings['SupportedLanguages'] as $languageCode => $languageName) {
      list ($stringsWereFound, $stringsPath, $stringsLayer) = resolveInterfaceFile($pageSettings, $interfaceName, 'Languages/'.$languageCode.'/languageStrings.php');
      if ($stringsWereFound && readDeclaredVersion($stringsPath, 'LanguageVersion') === $requiredVersion) $packsOk++;
      else $failedLanguages[] = $languageCode; }
    $ReportSections['Language packs'][] = array('Label' => $interfaceName, 'Text' => $packsOk.' of '.count($pageSettings['SupportedLanguages']).' OK'.(count($failedLanguages) > 0 ? ', failed: '.implode(' ', $failedLanguages) : ''), 'Failed' => (count($failedLanguages) > 0)); }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $pageSettings, $settingsAreWhole, $candidateRecord, $candidateProblem, $failedLanguages, $interfaceName, $languageCode, $languageName, $stringsPath, $stringsLayer, $requiredVersion, $rowText, $candidateIsUsable, $candidateIsJudged, $stringsWereFound, $packsOk);
  return $ReportSections; }
// / -----------------------------------------------------------------------------------
?>
