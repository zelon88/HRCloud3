<?php
// / -----------------------------------------------------------------------------------
// / Copyright Information ...
// / HRCloud3, Copyright on 11/26/2026 by Justin Grimes, www.github.com/zelon88
// /
// / License Information ...
// / This project is protected by the GNU GPLv3 Open-Source license.
// / https://www.gnu.org/licenses/gpl-3.0.html
// /
// / File Information ...
// / v1.0.0.
// / HRCloud3's Engine settings, loaded after engineConfig.php, so anything it sets wins.
// / A package ships a configuration with working defaults, & the thing being configured drops a file beside it that overrides what it needs, as Apache & PHP do.
// / engineConfig.php belongs to the Engine & is replaced wholesale when a newer Engine arrives, while this file belongs to the application & survives that.
// / So an Engine upgrade is a directory copy.
// / Set only what differs, because a setting repeated here at the Engine's value silently stops tracking the Engine's default when that default changes.
// / HRCloud3 began from the Starter's contract, & adds the Engine's accounts. Each name it leaves out is one the Engine falls back from, & ABOUT_ENGINE_CONTRACT.txt
// / says what each does.
// /
// / <3 Open-Source
// / -----------------------------------------------------------------------------------


// / -----------------------------------------------------------------------------------
// / A component may only be loaded by the core.
if (!isset($CoreLoaded) or $CoreLoaded !== TRUE) die('ERROR!!! HRCloud3-35000, An engine configuration cannot be loaded directly!');
// / -----------------------------------------------------------------------------------


// / -----------------------------------------------------------------------------------
// / What this application is called.
// / The Engine uses the name in log entries & error numbers, & the slug wherever a name has
// / to survive a filesystem or a socket path.
$EngineApplicationName = 'HRCloud3';

// / HRCloud3 is started through cloudCore.php.
$EngineEntryPoint = 'cloudCore.php';
$EngineApplicationSlug = 'hrcloud3';
// / -----------------------------------------------------------------------------------


// / -----------------------------------------------------------------------------------
// / Which way work moves through this application.
// / HRCloud3's pages ask nothing privileged for permission yet, so no listening socket is opened at all.
// / It moves to the Engine's default, 'requests-upward', when a request first asks root for work.
// / engineConfig.php says what each shape means.
$EngineDispatchDirection = 'work-downward';
// / -----------------------------------------------------------------------------------


// / -----------------------------------------------------------------------------------
// / The providers. Every one names a function this application defines, & the Engine calls what it is given without knowing anything else about it.
// / This is the whole of how the Engine learns about an application, through these names.
// / HRCloud3 names three of the four every application needs, & leaves out the rest. It runs no sandboxed tool, keeps no policy on the host,
// / offers no workflows yet, puts nothing on a schedule & adds nothing to -v, so it names no provider for any of those.
// / An Engine with none of them still runs & does less, which is the correct degradation rather than a failure.
// / See ABOUT_ENGINE_CONTRACT.txt.

// /   Returns the configuration model for this application's own half of config.php. What a config file cannot say about itself.
$EngineConfigModelProvider = 'applicationConfigModel';

// /   Returns what only this application knows for the help -h prints, its description, its arguments, its sections & its address.
$EngineHelpProvider = 'applicationHelpInfo';

// /   Moves a manager that has run for a while on to the logfile it should be writing now. It is the kernel's own.
$EngineLogProvider = 'refreshLogFile';

// /   Handles arguments beginning --app. HRCloud3 has none yet, so the kernel refuses every one with status 2.
$EngineApplicationArgumentHandler = '';

// /   This application offers the Engine's accounts. An installation has them only where its
// /   administrator leaves --Enable Authentication-- TRUE in config.php, which is HRCloud3's default.
$EngineEnableAuthentication = TRUE;

// /   This application keeps its users' files in the Engine's libraries, from v1.0.0, which --Libraries-- in config.php names.
$EngineEnableStorage = TRUE;

// /   A user's files are kept until the user deletes them, so the only lifetime offered is 0, from v1.0.0.
$EngineFileLifetimes = array(0);

// /   The function that gives a page HRCloud3's own data, with the page's name. A page reads it & $EnginePageState, & no other global.
$EnginePageDataProvider = 'applicationPageData';

// /   HRCloud3 is in development, so a contract that leaves out a value the Engine cannot default stops it, with 31027.
// /   Set this to FALSE before a release is called stable. See --Engine Strict Contract-- in engineConfig.php.
$EngineStrictContract = TRUE;

// /   The defaults this application gives the Engine's settings, where the Engine's own would not do.
// /   A setting's name, documentation & default are the Engine's, in Resources/Engine/config-template.php, & a default here
// /   replaces only the value, in a config.php written from the templates & in what a reset writes.
// /   Each value is written as config.php writes it, so a string carries its quotes.
// /   HRCloud3 names itself, its folders & where it updates from. It has no workflows yet & hands work downward, so the settings
// /   that offer workflows, resource awareness & a ceiling on each piece of work are off. Verbose logging is on while HRCloud3 is
// /   in development, so each page logs every file it was built from.
// /   A cloud keeps people's files, so accounts are on, every visitor must sign in, & only an administrator creates an account.
$EngineSettingDefaults = array(
  'InstLoc' => "'/var/www/html/HRProprietary/HRCloud3'",
  'ConvertLoc' => "'/DATA/HRCloud3'",
  'ApplicationName' => "'HRCloud3'",
  'ApplicationTitle' => "'HRCloud3'",
  'UpdateSourceRepository' => "'zelon88/HRCloud3'",
  'EnableAuthentication' => 'TRUE',
  'AllowAnonymousVisitors' => 'FALSE',
  'AllowRegistration' => 'FALSE',
  'Verbose' => 'TRUE',
  'EnableWorkflows' => 'FALSE',
  'EnableWorkflowBuilder' => 'FALSE',
  'EnableWorkflowImportExport' => 'FALSE',
  'EnableResourceAwareness' => 'FALSE',
  'EnablePerConversionLimits' => 'FALSE');
// / -----------------------------------------------------------------------------------


// / -----------------------------------------------------------------------------------
// / The pipelines this application accepts & the exact version each must report.
// / This is an allowlist. A folder under Resources/Pipelines that is not named here is never
// / read, never required & never enumerated, however it got there. HRCloud3 accepts none yet.
$EngineAcceptedPipelines = array();

// / The pages HRCloud3 draws through the Engine's Page Core, each as the components it is drawn from, in order.
// / Every interface in UI carries these, or takes them from Default, which every other inherits from & falls back to.
// / engine is the page the Engine names, such as signing in, home is the App Launcher, files is the user's libraries, from v1.0.0,
// / & settings is the user's own.
// / See Documentation/ABOUT_PAGES.txt & Documentation/CREATING_INTERFACES.txt.
$EnginePages = array(
  'engine' => array('header.php', 'engineGui.php', 'footer.php'),
  'home' => array('header.php', 'taskbar.php', 'launcherGui.php', 'footer.php'),
  'files' => array('header.php', 'taskbar.php', 'filesGui.php', 'footer.php'),
  'settings' => array('header.php', 'taskbar.php', 'settingsGui.php', 'footer.php'));

// / The files a page links, by the name a page reads each address under, & where each is inside an interface.
// / An address names the interface the file was found in, so a browser's error in one names its interface.
$EngineInterfaceAssets = array(
  'Stylesheet' => 'Resources/CSS/HRCloud3.css',
  'Library' => 'Resources/JS/HRC3-Functions.js',
  'SignIn' => 'Resources/JS/HRC3-SignIn.js',
  'SignInTimer' => 'Resources/JS/HRC3-SignInTimer.js',
  'Files' => 'Resources/JS/HRC3-Files.js',
  'Icon' => 'Resources/Image/favicon.svg');

// / The folder of the application whose static files a browser fetches, & the types those are.
// / The hosted location holds a copy of each beside the loader, written by -fp as root, so the loader is the only PHP in the web root.
$EngineHostedAssetFolders = array('UI');
$EngineHostedAssetTypes = array('css', 'js', 'svg', 'png', 'ico', 'webp', 'woff2', 'html');

// / Where each of this application's own pins reads its version from.
// / php cloudCore.php --build --pins --write writes every pin from the files named here,
// / so none of this application's pins is moved by hand. The kernel's & the Engine's are,
// / because the build runs inside both & cannot rewrite what loads it.
// / Paths are relative to the installation. The Engine knows where its own
// / components live, so they are not listed.
$EnginePinSources = array(
  'ConfigVersion' => 'Resources/Contract/config-template.php',
  'DependsVersion' => 'Resources/Contract/depends.php',
  'InterfaceVersion' => 'UI/Default/uiVersionInfo.php',
  'LanguageVersion' => 'UI/Default/Languages/en/languageStrings.php');

// / Every location this application keeps on disk, by the setting that holds it, & its role.
// / The role says what a location is. The Engine decides what that means for its owner & its
// / mode, & this contract cannot ask for a mode, so a location can never be made writable by
// / the account root is protecting itself from. -fp & the Environment Manager apply them.
// /   code       The installation. root's, 0755 & 0644, read by the web server account.
// /   data-root  The data location itself. root's & 0755, & never walked.
// /   secret     The folder secrets are kept in. root's, the web server account's group &
// /              0750, & never walked.
// /   web        A folder the web server account writes. 0700 & never walked by root.
// /   root-only  root alone. 0700 & 0600.
// /   shared     A folder other software shares. Checked & never walked.
// / See Documentation/ABOUT_MANAGED_LOCATIONS.txt.
// / HRCloud3 keeps no folder of its own yet, so these are the Engine's, named as every application names them.
// / AccountLoc is Accounts, where the Engine keeps each account, sign in & code, & UserLoc is Users, where each signed in user's own folder is.
// / LibraryLoc is Libraries, from v1.0.0, where each library the configuration leaves without a Location is kept, & which -fp makes.
// / All three are the web server account's, because a request is what signs a user in & what writes their files. None is ever swept
// / by the Engine's session sweep, & only the Storage Core empties a library's trash.
$EngineManagedLocations = array(
  'ProprietaryLoc' => 'shared',
  'InstLoc' => 'code',
  'DataRootLoc' => 'data-root',
  'SecretLoc' => 'secret',
  'SessionLoc' => 'web',
  'CacheLoc' => 'web',
  'LogDir' => 'web',
  'ManagerSocketDir' => 'web',
  'EngineDir' => 'root-only',
  'BackupLoc' => 'root-only',
  'AccountLoc' => 'web',
  'UserLoc' => 'web',
  'LibraryLoc' => 'web',
  'EventLoc' => 'root-only');

// / The entries at the top of a release that make up the application, & nothing else.
// / -fp run from a tree that is not installed copies exactly these into the application folder
// / in the data location, & the hosted location loses only what is a copy of one of them.
// / Whatever else shares the folder a release was unpacked into is never taken along & never removed.
// / See Documentation/ABOUT_MANAGED_LOCATIONS.txt.
$EngineApplicationLayout = array('cloudCore.php', 'README.md', 'LICENSE', 'Documentation', 'Resources', 'UI');

// / The settings in config.php that each shared name reads, & the values the kernel & the Engine work
// / out from them as they start. The shared layers ask for a value by a name that says nothing about
// / this application. HostedLocation is the folder the loader is kept in, ServerURL is the address of
// / that folder, & DocumentRoot is the web server's document root. MaintainProtectionFile says whether
// / the Engine keeps a protection file in a folder a web server could reach, & LiftNamespaceRestriction
// / whether -fp may lift the kernel's AppArmor restriction on unprivileged namespaces.
// / No workflow setting is mapped, so this application has no workflows, whatever config.php holds.
// / EnableAuthentication & every setting after it are the Authentication Core's & the Email Core's. AccountLocation & UserLocation are
// / where accounts & users' files are kept. ApplicationTitle & ServerName are what an email calls this server, & ServerName is
// / --Server URL-- as an administrator typed it. The four after EmailRecoveryBody are the emails a signed in user's own change of address or
// / password sends, from v0.9.9.
// / SupportedInterfaces & every setting after it to MarkPageComponents are the Page Core's, from v0.9.9, & live in HRCloud3's own half
// / of config.php. StorageReserveMegabytes is the Engine's --Storage Reserve--, & the three after MarkPageComponents are the Storage
// / Core's, from v1.0.0. LibraryLocation is where libraries without a Location of their own are kept, & StorageLibraries & TrashLifetime
// / live in HRCloud3's own half of config.php.
$EngineSettingNames = array(
  'HostedLocation' => 'HostedLoc',
  'ServerURL' => 'FullURL',
  'DocumentRoot' => 'ServerRootDir',
  'MaintainProtectionFile' => 'MaintainHTAccess',
  'LiftNamespaceRestriction' => 'AllowUnprivilegedNamespaces',
  'EnableAuthentication' => 'EnableAuthentication',
  'AllowAnonymousVisitors' => 'AllowAnonymousVisitors',
  'AllowRegistration' => 'AllowRegistration',
  'RequireEmailConfirmation' => 'RequireEmailConfirmation',
  'UserStorageQuota' => 'UserStorageQuota',
  'StorageReserveMegabytes' => 'StorageReserve',
  'SignInKeyLifetime' => 'SignInKeyLifetime',
  'SignInExchangeInterval' => 'SignInExchangeInterval',
  'SignInMaximumLifetime' => 'SignInMaximumLifetime',
  'MinimumPasswordLength' => 'MinimumPasswordLength',
  'SignInAttemptLimit' => 'SignInAttemptLimit',
  'SignInAttemptWindow' => 'SignInAttemptWindow',
  'UnconfirmedAccountLifetime' => 'UnconfirmedAccountLifetime',
  'RecoveryCodeLifetime' => 'RecoveryCodeLifetime',
  'EmailSender' => 'EmailSender',
  'EmailConfirmationSubject' => 'EmailConfirmationSubject',
  'EmailConfirmationBody' => 'EmailConfirmationBody',
  'EmailRecoverySubject' => 'EmailRecoverySubject',
  'EmailRecoveryBody' => 'EmailRecoveryBody',
  'EmailAddressSubject' => 'EmailAddressSubject',
  'EmailAddressBody' => 'EmailAddressBody',
  'EmailNoticeSubject' => 'EmailNoticeSubject',
  'EmailNoticeBody' => 'EmailNoticeBody',
  'AccountLocation' => 'AccountLoc',
  'UserLocation' => 'UserLoc',
  'ApplicationTitle' => 'ApplicationTitle',
  'ServerName' => 'URL',
  'SupportedInterfaces' => 'SupportedInterfaces',
  'DefaultInterface' => 'DefaultInterface',
  'AllowUserSelectableInterface' => 'AllowUserSelectableInterface',
  'SupportedLanguages' => 'SupportedLanguages',
  'DefaultLanguage' => 'DefaultLanguage',
  'AllowUserSelectableLanguage' => 'AllowUserSelectableLanguage',
  'SupportedColors' => 'SupportedColors',
  'DefaultColor' => 'DefaultColor',
  'AllowUserSelectableColor' => 'AllowUserSelectableColor',
  'MarkPageComponents' => 'MarkPageComponents',
  'LibraryLocation' => 'LibraryLoc',
  'StorageLibraries' => 'Libraries',
  'TrashLifetime' => 'TrashLifetime');
// / -----------------------------------------------------------------------------------
?>
