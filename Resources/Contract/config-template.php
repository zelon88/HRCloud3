<?php
// / -----------------------------------------------------------------------------------
// / This is HRCloud3's half of the configuration template, & everything below the marker is a
// / real config.php at default values. Not a description of one.
// / It holds the header & the guard a config.php opens with, $ConfigVersion & every setting only
// / HRCloud3 reads. Every setting the kernel or the Engine reads is the Engine's, in its half,
// / Resources/Engine/config-template.php, & config.php is written from both halves.
// / The kernel reads the names in both to know which settings config.php must hold & which become
// / globals, so no list of them is kept anywhere else. See configurationTemplatePaths() in
// / Resources/Kernel/kernel.php.
// / A missing config.php is written from them, with the Engine's settings first in each section the
// / two share. A repair adds whatever either holds that a live configuration lacks.
// / An operator's value is never replaced by a default here.
// / Adding a setting to this application means adding it here & to applicationConfigModel() in
// / cloudCore.php, & raising $ConfigVersion below & $RequiredConfigVersion in cloudCore.php.
// / Resources/config.php is what --config --repair writes from both halves.
// / Everything below the marker is copied verbatim, including the header & the guard, because a
// / generated configuration needs both. This note is not copied, because a generated
// / configuration is not a template & should not claim to be.
// / -----------------------------------------------------------------------------------

// / Copy from here down.
// / This file is generated from Resources/Contract/config-template.php & Resources/Engine/config-template.php. Edit the values below, never the structure.
// / A repair adds whatever either template holds & keeps every value set here. A structural edit is lost when the file is generated again.
// / -----------------------------------------------------------------------------------
// / Copyright Information ...
// / HRCloud3, Copyright on 11/26/2026 by Justin Grimes, www.github.com/zelon88
// /
// / License Information ...
// / This project is protected by the GNU GPLv3 Open-Source license.
// / https://www.gnu.org/licenses/gpl-3.0.html
// /
// / Application Information ...
// / HRCloud3 is a home-hosted cloud storage platform built on the HRProprietary Engine.
// /
// / File Information ...
// / v1.0.0.
// / This file contains the configuration information for HRCloud3.
// / Fill out this file completely & accurately before running the application.
// / Serious filesystem damage could occur from incorrect directory settings.
// / Be careful to preserve all syntax & formatting.
// /
// / Hardware Requirements ...
// / This application requires at least a Raspberry Pi Model B+ or greater.
// / This application will run on just about any x86 or x64 computer.
// /
// / Dependency Requirements ...
// / The authoritative list of dependencies is Resources/Engine/depends.php & Resources/Contract/depends.php, the Engine's half & the application's, & nothing else.
// / Run  php cloudCore.php -v  to see what this installation has.
// /
// / <3 Open-Source
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Refuse direct execution. This file is a component & has no standalone context.
// / This halt cannot use quickDie. Reaching this line means cloudCore.php was never
// / loaded, so quickDie is not defined & calling it would replace a clear refusal with an
// / undefined function error.
if (!isset($CoreLoaded) or $CoreLoaded !== TRUE) die('ERROR!!! HRCloud3-2: This file cannot process your request! Please submit your request to cloudCore.php instead!'.PHP_EOL);
// / -----------------------------------------------------------------------------------

// / ------------------------------
// /  --Config Version--
// /   The version of HRCloud3 in which this config file last gained or lost a setting.
// /   The core refuses to run against a config file that is missing settings it requires.
// /   Do not change this value by hand. An update & --config --repair keep it current.
$ConfigVersion = 'v1.0.0';
// / ------------------------------

// / ------------------------------
// / ---Interface Information---
// /
// /  --Supported Interfaces--
// /   The interfaces this server offers, each a folder of the same name in UI.
// /   Default is always offered, because every other inherits from it & falls back to it.
// /   An interface listed here that is missing, or that reports a version this release does not require, is not used, & the log says why.
// /   Default is 'Default'.
$SupportedInterfaces = array('Default');
// /  --Default Interface--
// /   The interface a page is drawn with where a user has not chosen one.
// /   Valid options are the names in --Supported Interfaces--.
// /   Default is 'Default'.
$DefaultInterface = 'Default';
// /  --Allow User Selectable Interface--
// /   Whether a user may choose another interface from --Supported Interfaces--, in their settings or with ?interface= in an address.
// /   Valid options are TRUE or FALSE.
// /   Default is TRUE.
$AllowUserSelectableInterface = TRUE;
// /  --Supported Languages--
// /   The languages this server offers. The key is the language's code, which names its folder in an interface's Languages,
// /   & the value is its endonym, its name in its own language, so a user who landed on a language they cannot read still finds theirs.
// /   A language an interface does not carry falls back to --Default Language--, then to English.
// /   Default is 'en' => 'English'.
$SupportedLanguages = array('en' => 'English');
// /  --Default Language--
// /   The language a page is drawn in where a user has not chosen one.
// /   Valid options are the codes in --Supported Languages--.
// /   Default is 'en'.
$DefaultLanguage = 'en';
// /  --Allow User Selectable Language--
// /   Whether a user may choose another language from --Supported Languages--, in their settings or with ?language= in an address.
// /   Valid options are TRUE or FALSE.
// /   Default is TRUE.
$AllowUserSelectableLanguage = TRUE;
// /  --Supported Colors--
// /   The colors this server offers, from those an interface offers in its styleCore.php.
// /   An empty list offers every color the interface offers.
// /   Default is array(), which offers every one.
$SupportedColors = array();
// /  --Default Color--
// /   The color a page is drawn in where a user has not chosen one.
// /   A color the interface does not offer is a warning, & the first it offers is used.
// /   Default is 'blue'.
$DefaultColor = 'blue';
// /  --Allow User Selectable Color--
// /   Whether a user may choose another color, in their settings or with ?color= in an address.
// /   Valid options are TRUE or FALSE.
// /   Default is TRUE.
$AllowUserSelectableColor = TRUE;
// /  --Mark Page Components--
// /   Whether every page marks where each of its components begins & ends, with a comment a browser's page source shows.
// /   Each mark names the component, the interface it came from, its stamp & its fingerprint, so an error a browser reports
// /   in a page can be traced to one file. It shows anybody who reads the page source how the interface is put together,
// /   so leave it off on a server others use. --Enhanced Logging Verbosity-- logs the same for every page, without showing it.
// /   Valid options are TRUE or FALSE.
// /   Default is FALSE.
$MarkPageComponents = FALSE;
// / ------------------------------

// / ------------------------------
// / ---Storage Information---
// /
// /  --Libraries--
// /   The folders signed in users keep files in, each by its name, which a page shows as it is written.
// /   A name is 1 to 32 letters, digits, dashes & underscores, & begins with a letter or a digit.
// /   Kind is personal, where each user has a space of their own that no other user reaches, or shared,
// /   which every signed in user reads.
// /   Writers says who may write a shared library, & is admins, everyone or none. Default is admins.
// /   Only an admin takes something out of a shared library's trash for good, so what a user deletes
// /   there can still be put back.
// /   Location is empty, which keeps the library in Libraries in the data location, made the first time
// /   it is used, or the absolute path of a folder that is there already. A folder of your own must be
// /   the web server account's to be written. It is never in the data location, the installation, the
// /   document root or a folder the operating system keeps its own files in, & never holds one of them.
// /   A library taken off this list or renamed keeps its files where they are, & nobody reaches them.
// /   A library that cannot be used is left out & the log says why. Where none can be, a personal
// /   library named Drive is used.
// /   Run  php cloudCore.php -v  to see each library & whether it can be used.
// /   Requires --Enable Authentication--.
// /   Default is a personal library named Drive & a shared library named Shared that admins write.
$Libraries = array(
  'Drive' => array('Kind' => 'personal', 'Location' => ''),
  'Shared' => array('Kind' => 'shared', 'Location' => '', 'Writers' => 'admins'));
// /  --Trash Lifetime--
// /   The days something deleted stays in a library's trash, where it can be put back, before it is
// /   taken away for good. Set it to 0 to keep the trash until it is emptied.
// /   Requires --Enable Authentication--.
// /   Valid options are integers from 0 to 3650.
// /   Default is 30.
$TrashLifetime = 30;
// / ------------------------------
