<?php
// HRProprietary Engine v4.3.4ur, from HRCloud3 v0.9.9. The unit suite of the Page Core, held to every rule ABOUT_PAGES.txt says it has.
//
//   sudo bash Documentation/Build/Tests/contained.sh php Documentation/Build/Tests/test_pages-cli.php
// Run it from the root of the application under test. A tree named after it is tested instead, & must be that root.
//
// It loads the tree's own Resources/Engine/Cores/pageCore.php, & lifts the kernel functions that core calls out of the tree's own
// Resources/Kernel/kernel.php. Nothing of either is copied into this file. engineApplicationName() is a stand-in, because it is the
// Engine's & reads the contract.
// It writes interfaces of its own into the scratch folder & builds pages from them: a base, one that inherits from it, one of the
// wrong version, one with no version, one with no colors, & language packs in two languages, one of the wrong version.
// It removes nothing. Every file it writes is beneath the scratch folder, & a file it changes is one it wrote.

if (PHP_SAPI !== 'cli') { if (!headers_sent()) http_response_code(404); die(); }

if (!is_file('Documentation/Build/Tests/testlib-cli.php')) { fwrite(STDERR, 'Run this from the root of the application it tests. See Documentation/Build/Tests/README.txt.'.PHP_EOL); exit(64); }
require 'Documentation/Build/Tests/testlib-cli.php';

$tree = testTree(isset($argv[1]) ? $argv[1] : (string)getcwd());
requireContainment();
$pageCoreFile = $tree.'/Resources/Engine/Cores/pageCore.php';
if (!is_file($pageCoreFile)) testStop($tree.' has no Page Core. This suite tests Resources/Engine/Cores/pageCore.php, from v4.3.4ur.');

// What the kernel, the Engine & the application would have set before the Page Core was loaded.
$EnableMemoryProtection = TRUE;
$Verbose = TRUE;
$LogFile = '';
$CoreLoaded = TRUE;
$EngineApplicationVersion = 'v0.9.9';
$RequiredInterfaceVersion = 'v0.9.9';
$RequiredLanguageVersion = 'v0.9.9';
$InstLoc = makeFolder(scratchPath('pages'), 0700);
$EngineInterfaceFolder = 'UI';
$EngineBaseInterface = 'Default';
$EnginePages = array('main' => array('header.php', 'body.php', 'footer.php'), 'Not A Name' => array('header.php'), 'climbs' => array('../outside.php'), 'empty' => array());
$EngineInterfaceAssets = array('Stylesheet' => 'Resources/CSS/look.css', 'Icon' => 'Resources/Image/icon.svg', 'Escape' => '../../outside.css');
$EnginePageDataProvider = 'testPageData';
$EngineSettingNames = array('SupportedInterfaces' => 'TestInterfaces', 'DefaultInterface' => 'TestDefaultInterface', 'AllowUserSelectableInterface' => 'TestChooseInterface',
  'SupportedLanguages' => 'TestLanguages', 'DefaultLanguage' => 'TestDefaultLanguage', 'AllowUserSelectableLanguage' => 'TestChooseLanguage',
  'SupportedColors' => 'TestColors', 'DefaultColor' => 'TestDefaultColor', 'AllowUserSelectableColor' => 'TestChooseColor', 'MarkPageComponents' => 'TestMarkComponents');

liftFunctions($tree.'/Resources/Kernel/kernel.php', array('getChildFunction', 'purgeSensitiveMemory', 'printableText', 'applicationSetting'), array());
require $pageCoreFile;

// The Engine's name for the application, which reads the contract. Here it is the suite's.
function engineApplicationName() {
  return 'TestApp'; }

// The page data provider the contract names. It says which page it was asked for, so a check can see it was.
function testPageData($pageName) {
  return array('Asked' => $pageName, 'Application' => 'TestApp'); }

// Sets the administrator's settings for the checks that follow. Anything not named takes the value an administrator ships with.
function testSettings($settings) {
  $GLOBALS['TestInterfaces'] = array('Default', 'Night', 'Old', 'Newer', 'Unversioned', 'Colorless', 'Absent');
  $GLOBALS['TestDefaultInterface'] = 'Default';
  $GLOBALS['TestChooseInterface'] = TRUE;
  $GLOBALS['TestLanguages'] = array('en' => 'English', 'fr' => 'Français', 'de' => 'Deutsch');
  $GLOBALS['TestDefaultLanguage'] = 'en';
  $GLOBALS['TestChooseLanguage'] = TRUE;
  $GLOBALS['TestColors'] = array();
  $GLOBALS['TestDefaultColor'] = 'blue';
  $GLOBALS['TestChooseColor'] = TRUE;
  $GLOBALS['TestMarkComponents'] = FALSE;
  foreach ($settings as $settingName => $settingValue) $GLOBALS[$settingName] = $settingValue;
  $GLOBALS['TestLog'] = array();
  return TRUE; }

// Says whether the log holds a line that begins as the one named, & says it word for word.
function logHas($expectedLine) {
  foreach ((array)$GLOBALS['TestLog'] as $logLine) if (strpos($logLine, $expectedLine) === 0) return TRUE;
  return FALSE; }

// Counts the warnings & errors in the log.
function logProblems() {
  return count(preg_grep('/^(WARNING|ERROR)!!! /', (array)$GLOBALS['TestLog'])); }

// Writes a file of an interface the suite makes, making the folders it is in.
function interfaceFile($relativePath, $fileText) {
  $filePath = $GLOBALS['InstLoc'].'/UI/'.$relativePath;
  $folderPath = $GLOBALS['InstLoc'].'/UI';
  foreach (explode('/', dirname($relativePath)) as $folderName) {
    $folderPath .= '/'.$folderName;
    if (!is_dir($folderPath)) makeFolder($folderPath, 0700); }
  return writeScratchFile($filePath, $fileText, 0600); }

// The guard every component, pack & style file opens with, & the header stamp a file of the interface writes.
$guard = "<?php\nif (!isset(\$CoreLoaded) or \$CoreLoaded !== TRUE) die('Not directly.');\n";
makeFolder($InstLoc.'/UI', 0700);
interfaceFile('Default/uiVersionInfo.php', $guard."// / v0.9.9.\n\$InterfaceVersion = 'v0.9.9';\n");
interfaceFile('Default/styleCore.php', $guard."// / v0.9.9.\n\$interfaceColors = array('blue', 'dark', 'Not A Color', 'blue');\n");
interfaceFile('Default/header.php', $guard."// / v0.9.9.\nprint('[header '.\$TextHello.' '.\$LanguageToUse.' '.\$ColorToUse.']');\n");
interfaceFile('Default/body.php', $guard."// / v0.9.9.\nprint('[default body '.\$TextOnlyEnglish.' '.(\$PageData['Asked'] ?? 'nothing').']');\n");
interfaceFile('Default/footer.php', $guard."// / v0.9.9.\nprint('[footer '.\$InterfaceAssets['Stylesheet'].']');\n");
interfaceFile('Default/Languages/en/languageStrings.php', $guard."// / v0.9.9.\n\$LanguageVersion = 'v0.9.9';\n\$LanguageDirection = 'ltr';\n\$TextHello = 'Hello';\n\$TextOnlyEnglish = 'Only English';\n");
interfaceFile('Default/Languages/fr/languageStrings.php', $guard."// / v0.9.9.\n\$LanguageVersion = 'v0.9.9';\n\$LanguageDirection = 'ltr';\n\$TextHello = 'Bonjour';\n");
interfaceFile('Default/Resources/CSS/look.css', "/* -----\n * v0.9.9.\n * The base's stylesheet.\n */\nbody { color: black; }\n");
interfaceFile('Default/Resources/Image/icon.svg', "<svg xmlns='http://www.w3.org/2000/svg'>\n  <!-- v0.9.9. -->\n</svg>\n");
interfaceFile('Night/uiVersionInfo.php', $guard."// / v0.9.9.\n\$InterfaceVersion = 'v0.9.9';\n");
interfaceFile('Night/styleCore.php', $guard."// / v0.9.9.\n\$interfaceColors = array('night');\n");
interfaceFile('Night/body.php', $guard."// / v0.9.9.\nprint('[night body '.\$TextHello.']');\n");
interfaceFile('Night/Resources/CSS/look.css', "/*\n * v0.9.9.\n */\nbody { color: white; }\n");
interfaceFile('Old/uiVersionInfo.php', $guard."\$InterfaceVersion = 'v0.9.8';\n");
interfaceFile('Newer/uiVersionInfo.php', $guard."\$InterfaceVersion = 'v0.9.10';\n");
interfaceFile('Unversioned/uiVersionInfo.php', $guard."// / No version is declared here.\n");
interfaceFile('Colorless/uiVersionInfo.php', $guard."\$InterfaceVersion = 'v0.9.9';\n");
interfaceFile('Colorless/styleCore.php', $guard."\$interfaceColors = array();\n");


// Part 1. What may name an interface, a language, a color & a file inside an interface.
check('an interface is 1 to 64 letters, digits & underscores', interfaceNameIsUsable('Default') && interfaceNameIsUsable('My_Look2') && !interfaceNameIsUsable('../Default')
  && !interfaceNameIsUsable('Two Words') && !interfaceNameIsUsable(str_repeat('a', 65)) && !interfaceNameIsUsable('') && !interfaceNameIsUsable(array('Default')));
check('a language is 2 or 3 lower case letters, with a region after a dash', languageCodeIsUsable('en') && languageCodeIsUsable('pt-BR') && languageCodeIsUsable('haw')
  && !languageCodeIsUsable('EN') && !languageCodeIsUsable('e') && !languageCodeIsUsable('en_US') && !languageCodeIsUsable('en-') && !languageCodeIsUsable("en\n"));
check('a color is a lower case letter, then letters & digits', colorNameIsUsable('blue') && colorNameIsUsable('dark2') && !colorNameIsUsable('Blue') && !colorNameIsUsable('2dark')
  && !colorNameIsUsable("blue'") && !colorNameIsUsable(''));
check('a path inside an interface never climbs out, starts at the top or holds an empty part', interfacePathIsUsable('Resources/CSS/look.css') && !interfacePathIsUsable('../look.css')
  && !interfacePathIsUsable('Resources/../../look.css') && !interfacePathIsUsable('/etc/passwd') && !interfacePathIsUsable('Resources//look.css') && !interfacePathIsUsable('Resources/./look.css')
  && !interfacePathIsUsable(''));


// Part 2. What the contract & the administrator say.
testSettings(array());
list ($pageSettings, $settingsAreWhole) = pageCoreSettings();
check('the settings are read whole, with the folder & the base the contract names', $settingsAreWhole === TRUE && $pageSettings['Folder'] === 'UI' && $pageSettings['Base'] === 'Default');
check('a page the contract names is kept with its components in order, & every page that could never be drawn is left out', $pageSettings['Pages'] === array('main' => array('header.php', 'body.php', 'footer.php')));
check('each page left out is a warning naming it', logHas("WARNING!!! The page Not A Name the contract declares is not a page name with a list of components, so it was left out.")
  && logHas("WARNING!!! The page climbs the contract declares") && logHas("WARNING!!! The page empty the contract declares"), implode("\n", $TestLog));
check('an asset that climbs out of the interface is left out, with a warning', $pageSettings['Assets'] === array('Stylesheet' => 'Resources/CSS/look.css', 'Icon' => 'Resources/Image/icon.svg')
  && logHas("WARNING!!! The asset Escape the contract declares is not a name with a path inside an interface, so it was left out."));
testSettings(array('TestInterfaces' => array('Night', '../x'), 'TestLanguages' => array('fr' => 'Français', 'X' => 'Bad'), 'TestColors' => array('dark', 'Dark')));
list ($pageSettings, $settingsAreWhole) = pageCoreSettings();
check('the base & English are always offered, & a name that could never be used is not', $settingsAreWhole && $pageSettings['SupportedInterfaces'] === array('Night', 'Default')
  && $pageSettings['SupportedLanguages'] === array('fr' => 'Français', 'en' => 'English') && $pageSettings['SupportedColors'] === array('dark'));
$savedNames = $EngineSettingNames;
$EngineSettingNames = array();
testSettings(array());
list ($pageSettings, $settingsAreWhole) = pageCoreSettings();
check('an application that maps none of the settings offers the base, English & every color, & marks nothing', $settingsAreWhole && $pageSettings['SupportedInterfaces'] === array('Default')
  && $pageSettings['SupportedLanguages'] === array('en' => 'English') && $pageSettings['SupportedColors'] === array() && $pageSettings['MarkComponents'] === FALSE
  && $pageSettings['AllowInterfaceChoice'] === TRUE);
$EngineSettingNames = $savedNames;
// / From here on the contract declares only what can be used, so a warning in a check below is the check's own.
$EnginePages = array('main' => array('header.php', 'body.php', 'footer.php'));
$EngineInterfaceAssets = array('Stylesheet' => 'Resources/CSS/look.css', 'Icon' => 'Resources/Image/icon.svg');
testSettings(array());
list ($pageSettings, $settingsAreWhole) = pageCoreSettings();
check('a contract that declares only what can be used is read without a word', $settingsAreWhole && $TestLog === array(), implode("\n", $TestLog));


// Part 3. A version, a stamp & a fingerprint are read without running the file.
$runsIfRun = writeScratchFile($InstLoc.'/runs.php', "<?php print('it ran'); \$InterfaceVersion = 'v1.2.3';\n", 0600);
ob_start();
$readVersion = readDeclaredVersion($runsIfRun, 'InterfaceVersion');
$printed = ob_get_clean();
check('a declared version is read without the leading v, & the file is never run', $readVersion === '1.2.3' && $printed === '');
check('a file that declares none reads as an empty version', readDeclaredVersion($InstLoc.'/UI/Unversioned/uiVersionInfo.php', 'InterfaceVersion') === '' && readDeclaredVersion($InstLoc.'/nothing.php', 'InterfaceVersion') === '');
check('a stamp is read from a PHP comment, a stylesheet comment & an HTML comment alike, without its v', readFileStamp($InstLoc.'/UI/Default/header.php') === '0.9.9'
  && readFileStamp($InstLoc.'/UI/Default/Resources/CSS/look.css') === '0.9.9' && readFileStamp($InstLoc.'/UI/Default/Resources/Image/icon.svg') === '0.9.9');
check('a file with no stamp, & one that is not there, read as unstamped', readFileStamp($InstLoc.'/UI/Old/uiVersionInfo.php') === 'unstamped' && readFileStamp($InstLoc.'/nothing.php') === 'unstamped');
$headerFingerprint = substr(hash_file('sha256', $InstLoc.'/UI/Default/header.php'), 0, 12);
check('a fingerprint is the first 12 hexadecimal digits of the SHA-256', fileFingerprint($InstLoc.'/UI/Default/header.php') === $headerFingerprint && preg_match('/^[0-9a-f]{12}$/', $headerFingerprint) === 1);


// Part 4. The interface ladder.
testSettings(array());
list ($pageSettings, $settingsAreWhole) = pageCoreSettings();
$TestLog = array();
list ($interfaceIsSet, $interfaceRecord) = choosePageInterface($pageSettings, 'Night');
check('an interface that is offered & asked for is used', $interfaceIsSet && $interfaceRecord['Name'] === 'Night' && $interfaceRecord['Base'] === 'Default' && logProblems() === 0, implode("\n", $TestLog));
check('a file it lacks is taken from the base, & one it carries is its own', $interfaceRecord['Files']['header.php']['Layer'] === 'Default' && $interfaceRecord['Files']['body.php']['Layer'] === 'Night'
  && $interfaceRecord['Assets']['Stylesheet']['Layer'] === 'Night' && $interfaceRecord['Assets']['Icon']['Layer'] === 'Default' && $interfaceRecord['Colors'] === array('night'));
list ($interfaceIsSet, $interfaceRecord) = choosePageInterface($pageSettings, 'Elsewhere');
check('one that is not offered is passed over without a word, & the default is used', $interfaceIsSet && $interfaceRecord['Name'] === 'Default' && logProblems() === 0);
check('the base offers each of its colors once, & none that cannot name a color', $interfaceRecord['Colors'] === array('blue', 'dark'));
testSettings(array('TestChooseInterface' => FALSE));
list ($pageSettings, $settingsAreWhole) = pageCoreSettings();
list ($interfaceIsSet, $interfaceRecord) = choosePageInterface($pageSettings, 'Night');
check('where users may not choose, the request is not a rung', $interfaceIsSet && $interfaceRecord['Name'] === 'Default' && logProblems() === 0);
testSettings(array());
list ($pageSettings, $settingsAreWhole) = pageCoreSettings();
foreach (array('Old' => "WARNING!!! The interface Old reports version 0.9.8 & this application requires 0.9.9. It was not used.",
  'Newer' => "WARNING!!! The interface Newer reports version 0.9.10 & this application requires 0.9.9. It was not used.",
  'Unversioned' => "WARNING!!! The interface Unversioned could not be used, because its uiVersionInfo.php declares no \$InterfaceVersion.",
  'Colorless' => "WARNING!!! The interface Colorless could not be used, because its styleCore.php offers no color.",
  'Absent' => "WARNING!!! The interface Absent could not be used, because it has no folder in ".$InstLoc."/UI.") as $brokenName => $expectedWarning) {
  $TestLog = array();
  list ($interfaceIsSet, $interfaceRecord) = choosePageInterface($pageSettings, $brokenName);
  check('an offered interface that cannot be used, '.$brokenName.', is a warning, & the default is used', $interfaceIsSet && $interfaceRecord['Name'] === 'Default' && logHas($expectedWarning) && logProblems() === 1,
    implode("\n", $TestLog)); }
testSettings(array('TestDefaultInterface' => 'Old'));
list ($pageSettings, $settingsAreWhole) = pageCoreSettings();
list ($interfaceIsSet, $interfaceRecord) = choosePageInterface($pageSettings, '');
check('a default that cannot be used is a warning, & the base is used', $interfaceIsSet && $interfaceRecord['Name'] === 'Default' && logHas("WARNING!!! The interface Old reports version 0.9.8"));
$RequiredInterfaceVersion = 'v0.9.10';
testSettings(array());
list ($pageSettings, $settingsAreWhole) = pageCoreSettings();
list ($interfaceIsSet, $interfaceRecord) = choosePageInterface($pageSettings, 'Night');
check('versions are compared whole, so 0.9.9 is not 0.9.10, & with no rung left the base failing is error 51000',
  !$interfaceIsSet && $interfaceRecord === array() && logHas("ERROR!!! 51000, No interface could be used, not even Default, so no page can be drawn!")
  && logHas("WARNING!!! The interface Night reports version 0.9.9 & this application requires 0.9.10. It was not used."), implode("\n", $TestLog));
$RequiredInterfaceVersion = 'v0.9.9';
interfaceFile('Inherits/uiVersionInfo.php', $guard."\$InterfaceVersion = 'v0.9.9';\n");
$baseVersionFile = $InstLoc.'/UI/Default/uiVersionInfo.php';
$baseVersionText = (string)file_get_contents($baseVersionFile);
writeScratchFile($baseVersionFile, str_replace("'v0.9.9'", "'v0.9.8'", $baseVersionText), 0600);
testSettings(array('TestInterfaces' => array('Inherits')));
list ($pageSettings, $settingsAreWhole) = pageCoreSettings();
list ($interfaceIsSet, $interfaceRecord) = choosePageInterface($pageSettings, 'Inherits');
check('an interface whose base reports another version is not used, because it takes every file it lacks from the base',
  !$interfaceIsSet && logHas("WARNING!!! The interface Inherits could not be used, because the interface it inherits from, Default, does not report the version this application requires."), implode("\n", $TestLog));
writeScratchFile($baseVersionFile, $baseVersionText, 0600);


// Part 5. The language ladder.
testSettings(array());
list ($pageSettings, $settingsAreWhole) = pageCoreSettings();
list ($interfaceIsSet, $nightRecord) = choosePageInterface($pageSettings, 'Night');
list ($languageIsSet, $languageRecord, $languageIsJudged) = choosePageLanguage($pageSettings, $nightRecord, 'fr');
check('a language offered & asked for is used, from the base, with the next rung under it', $languageIsSet && $languageIsJudged && $languageRecord['Code'] === 'fr' && $languageRecord['Layer'] === 'Default'
  && $languageRecord['Version'] === '0.9.9' && $languageRecord['BaselineCode'] === 'en' && logProblems() === 0, implode("\n", $TestLog));
list ($languageIsSet, $languageRecord, $languageIsJudged) = choosePageLanguage($pageSettings, $nightRecord, 'de');
check('a language offered with no pack is a warning, & the next is used', $languageIsSet && $languageRecord['Code'] === 'en' && $languageRecord['BaselineCode'] === ''
  && logHas("WARNING!!! The interface Night carries no de language pack, so the next language was tried."), implode("\n", $TestLog));
testSettings(array('TestChooseLanguage' => FALSE));
list ($pageSettings, $settingsAreWhole) = pageCoreSettings();
list ($languageIsSet, $languageRecord, $languageIsJudged) = choosePageLanguage($pageSettings, $nightRecord, 'fr');
check('where users may not choose, the default language is used', $languageIsSet && $languageRecord['Code'] === 'en' && logProblems() === 0);
$frenchFile = $InstLoc.'/UI/Default/Languages/fr/languageStrings.php';
$frenchText = (string)file_get_contents($frenchFile);
writeScratchFile($frenchFile, str_replace("\$LanguageVersion = 'v0.9.9';", "\$LanguageVersion = 'v0.9.8';", $frenchText), 0600);
testSettings(array());
list ($pageSettings, $settingsAreWhole) = pageCoreSettings();
list ($languageIsSet, $languageRecord, $languageIsJudged) = choosePageLanguage($pageSettings, $nightRecord, 'fr');
check('a pack of another version is used with a warning, & the pack under it gives what it lacks', $languageIsSet && $languageRecord['Code'] === 'fr'
  && logHas("WARNING!!! The language pack fr reports version 0.9.8 & this application requires 0.9.9. Any string it lacks is taken from the en pack."), implode("\n", $TestLog));
writeScratchFile($frenchFile, $frenchText, 0600);
testSettings(array('TestLanguages' => array('de' => 'Deutsch'), 'TestDefaultLanguage' => 'de'));
list ($pageSettings, $settingsAreWhole) = pageCoreSettings();
$englishFile = $InstLoc.'/UI/Default/Languages/en/languageStrings.php';
$englishText = (string)file_get_contents($englishFile);
writeScratchFile($englishFile, $englishText, 0600);
$englishAside = setAside($englishFile);
list ($languageIsSet, $languageRecord, $languageIsJudged) = choosePageLanguage($pageSettings, $nightRecord, '');
check('with no pack on any rung, not even English, it is error 51001', !$languageIsSet && $languageIsJudged
  && logHas("ERROR!!! 51001, No language pack could be used in the interface Night, not even en, so no page can be drawn!"), implode("\n", $TestLog));
list ($movedStatus, $movedSaid) = runCommand(array('mv', '-T', $englishAside, $englishFile));
if ($movedStatus !== 0 or !is_file($englishFile)) testStop('The English pack the suite set aside could not be put back. '.$movedSaid);


// Part 6. The color ladder.
testSettings(array());
list ($pageSettings, $settingsAreWhole) = pageCoreSettings();
list ($interfaceIsSet, $defaultRecord) = choosePageInterface($pageSettings, 'Default');
check('a color offered & asked for is used', choosePageColor($pageSettings, $defaultRecord, 'dark') === 'dark' && logProblems() === 0);
check('one the interface does not offer is passed over for the default', choosePageColor($pageSettings, $defaultRecord, 'green') === 'blue' && choosePageColor($pageSettings, $defaultRecord, "x'y") === 'blue' && logProblems() === 0);
testSettings(array('TestDefaultColor' => 'purple'));
list ($pageSettings, $settingsAreWhole) = pageCoreSettings();
check('a default the interface does not offer is a warning, & its first color is used', choosePageColor($pageSettings, $defaultRecord, '') === 'blue'
  && logHas("WARNING!!! The default color purple is not one the interface Default offers, so blue is used."));
testSettings(array('TestColors' => array('dark'), 'TestDefaultColor' => ''));
list ($pageSettings, $settingsAreWhole) = pageCoreSettings();
check('the administrator\'s list narrows what the interface offers', choosePageColor($pageSettings, $defaultRecord, 'blue') === 'dark' && logProblems() === 0);
testSettings(array('TestChooseColor' => FALSE, 'TestDefaultColor' => 'dark'));
list ($pageSettings, $settingsAreWhole) = pageCoreSettings();
check('where users may not choose, the default is used', choosePageColor($pageSettings, $defaultRecord, 'blue') === 'dark');


// Part 7. Building a page.
$PageSelection = NULL;
testSettings(array());
ob_start();
list ($pageWasBuilt, $pageIsJudged) = buildEnginePage('main');
$printed = ob_get_clean();
check('a page asked for before anything was chosen is error 51003, & nothing is drawn', !$pageWasBuilt && $pageIsJudged && $printed === ''
  && logHas("ERROR!!! 51003, A page was asked to be drawn before an interface & a language were chosen!"));
$RequiredInterfaceVersion = 'v9.9.9';
list ($pagesAreReady, $interfaceToUse, $languageToUse, $colorToUse) = preparePages('Night', 'fr', 'night');
$TestLog = array();
ob_start();
list ($pageWasBuilt, $pageIsJudged) = buildEnginePage('main');
$printed = ob_get_clean();
check('a page asked for after preparePages() could not choose is error 51003 too, & nothing is drawn', !$pagesAreReady && $PageSelection === array('Ready' => FALSE)
  && !$pageWasBuilt && $printed === '' && logHas("ERROR!!! 51003, A page was asked to be drawn before an interface & a language were chosen!"), implode("\n", $TestLog));
$RequiredInterfaceVersion = 'v0.9.9';
list ($pagesAreReady, $interfaceToUse, $languageToUse, $colorToUse) = preparePages('Night', 'fr', 'night');
check('preparePages() chooses once for the request', $pagesAreReady && $interfaceToUse === 'Night' && $languageToUse === 'fr' && $colorToUse === 'night' && $PageSelection['Ready'] === TRUE);
$TestLog = array();
ob_start();
list ($pageWasBuilt, $pageIsJudged) = buildEnginePage('nothing');
$printed = ob_get_clean();
check('a page the contract does not declare is error 51002, & nothing is drawn', !$pageWasBuilt && $pageIsJudged && $printed === ''
  && logHas("ERROR!!! 51002, A page named nothing was asked for & the contract declares none by that name!"));
$TestLog = array();
ob_start();
list ($pageWasBuilt, $pageIsJudged) = buildEnginePage('main');
$printed = ob_get_clean();
$stylesheetTime = filemtime($InstLoc.'/UI/Night/Resources/CSS/look.css');
check('a page is drawn from its components in order, from the layer each lives in, with every string read', $pageWasBuilt && $pageIsJudged
  && $printed === '[header Bonjour fr night][night body Bonjour][footer UI/Night/Resources/CSS/look.css?v='.$stylesheetTime.']', $printed);
check('a component reads the page, the application\'s data, the choices & the addresses it is handed', $PageName === 'main' && $PageData === array('Asked' => 'main', 'Application' => 'TestApp')
  && $InterfaceAssets === array('Stylesheet' => 'UI/Night/Resources/CSS/look.css?v='.$stylesheetTime, 'Icon' => 'UI/Default/Resources/Image/icon.svg?v='.filemtime($InstLoc.'/UI/Default/Resources/Image/icon.svg'))
  && $PageChoices['Languages'] === array('en' => 'English', 'fr' => 'Français') && $PageChoices['Colors'] === array('night'));
preparePages('Default', 'fr', '');
ob_start();
buildEnginePage('main');
$printed = ob_get_clean();
check('a string the chosen pack lacks is read from the pack under it', $printed === '[header Bonjour fr blue][default body Only English main][footer UI/Default/Resources/CSS/look.css?v='.filemtime($InstLoc.'/UI/Default/Resources/CSS/look.css').']', $printed);
preparePages('Night', 'fr', 'night');
check('no string of the pack is left where anything outside the page can read it', !isset($TextHello) && !isset($GLOBALS['TextHello']) && !isset($GLOBALS['TextOnlyEnglish']));
$fingerprintOf = function ($relativePath) use ($InstLoc) { return substr(hash_file('sha256', $InstLoc.'/UI/'.$relativePath), 0, 12); };
$provenance = 'Op-Act: Built the main page with the Page Core 4.3.4ur for TestApp 0.9.9. Interface Night 0.9.9 over Default 0.9.9, language fr 0.9.9 over en, color night. '
  .'Components: header.php from Default 0.9.9 #'.$fingerprintOf('Default/header.php').', body.php from Night 0.9.9 #'.$fingerprintOf('Night/body.php')
  .', footer.php from Default 0.9.9 #'.$fingerprintOf('Default/footer.php').', styleCore.php from Night 0.9.9 #'.$fingerprintOf('Night/styleCore.php')
  .', Languages/fr from Default 0.9.9 #'.$fingerprintOf('Default/Languages/fr/languageStrings.php').', Languages/en from Default 0.9.9 #'.$fingerprintOf('Default/Languages/en/languageStrings.php')
  .'. Assets: Stylesheet from Night 0.9.9 #'.$fingerprintOf('Night/Resources/CSS/look.css').', Icon from Default 0.9.9 #'.$fingerprintOf('Default/Resources/Image/icon.svg').'.';
check('where $Verbose is TRUE, the page is logged with every file it was built from, its layer, its stamp & its fingerprint', in_array($provenance, $TestLog, TRUE), implode("\n", $TestLog)."\nexpected\n".$provenance);
$Verbose = FALSE;
$TestLog = array();
ob_start();
buildEnginePage('main');
ob_end_clean();
check('where it is FALSE, nothing is logged', $TestLog === array());
$Verbose = TRUE;
testSettings(array('TestMarkComponents' => TRUE));
preparePages('Night', 'fr', 'night');
ob_start();
buildEnginePage('main');
$printed = ob_get_clean();
check('where the administrator asks, every component is marked, named exactly as the log names it', strpos($printed, "\n<!-- Page component begins: body.php from Night 0.9.9 #".$fingerprintOf('Night/body.php')." -->\n[night body Bonjour]\n<!-- Page component ends: body.php -->\n") !== FALSE
  && substr_count($printed, '<!-- Page component begins: ') === 3 && substr_count($printed, '<!-- Page component ends: ') === 3, $printed);
$EnginePageDataProvider = 'noSuchProvider';
testSettings(array());
preparePages('', '', '');
ob_start();
list ($pageWasBuilt, $pageIsJudged) = buildEnginePage('main');
ob_end_clean();
check('a provider that does not exist is a warning, & the page is drawn without the application\'s data', $pageWasBuilt && $PageData === array()
  && logHas("WARNING!!! A page data provider named noSuchProvider was declared & does not exist. The page main is drawn without the application's data."));
$EnginePageDataProvider = 'testPageData';


// Part 8. What -v reports.
testSettings(array('TestInterfaces' => array('Default', 'Night', 'Old', 'Absent')));
$reportSections = pageCoreReportSections();
check('-v reports every interface offered, & says why one cannot be used', $reportSections['Installed interfaces'] === array(
  array('Label' => 'Default', 'Text' => 'OK, 0.9.9, the base', 'Failed' => FALSE),
  array('Label' => 'Night', 'Text' => 'OK, 0.9.9, inherits from Default', 'Failed' => FALSE),
  array('Label' => 'Old', 'Text' => 'FAILED, reports 0.9.8', 'Failed' => TRUE),
  array('Label' => 'Absent', 'Text' => 'MISSING, no folder', 'Failed' => TRUE)), print_r($reportSections, TRUE));
check('& counts the packs each carries at the version required, naming each that fails', $reportSections['Language packs'][0] === array('Label' => 'Default', 'Text' => '2 of 3 OK, failed: de', 'Failed' => TRUE),
  print_r($reportSections['Language packs'], TRUE));
check('& logs nothing', $TestLog === array(), implode("\n", $TestLog));

finish('test_pages');
