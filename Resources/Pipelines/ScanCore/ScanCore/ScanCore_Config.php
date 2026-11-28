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
// / ScanCore Configuration.
// / This file contains the configuration entries of the application.
// / An entry that is missing or invalid falls back to a built in default & is reported as a warning.
// / Most entries can be overridden for one run from the command line.
// / Documentation/ABOUT_COMMAND_LINE_ARGUMENTS.txt names the switch that overrides each entry.
// / Be careful to preserve all syntax & formatting.
// /
// / <3 Open-Source
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Refuse to be read by anything but a command line interpreter.
// / ScanCore reads this file, & ScanCore runs from a command line alone.
if (PHP_SAPI !== 'cli') {
  echo 'ERROR!!! ScanCore-11, The configuration file can only be read by ScanCore from a command line!'.PHP_EOL;
  exit(1); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Version Information ...
// /
// /  --Configuration Version--
// /   The version of ScanCore in which this file last gained or lost an entry.
// /   The core refuses a configuration file that is older than the version it requires, because entries would be missing.
// /   A configuration file that is newer than the core requires is accepted.
// /   Do not change this value by hand.
// /   Must be formatted as a string.
$ConfigVersion = 'v1.9';
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Update Information ...
// /
// /  --Allow Application Updates--
// /   Allow the -updateapplication switch to update this installation.
// /   An update replaces every subscribed file, this one included, & keeps a numbered backup of this file beside it.
// /   Valid options are TRUE or FALSE.
// /   Default is TRUE.
$ApplicationUpdates = TRUE;
// /  --Application Update URL--
// /   The URL of the repository that application updates are downloaded from.
// /   Valid options are the URL of a ScanCore source code repository, formatted as a string.
// /   Default is 'https://github.com/zelon88/ScanCore'.
$ApplicationUpdateURL = 'https://github.com/zelon88/ScanCore';
// /  --Application Update Domain--
// /   The host name that is tested for a connection before an application update is attempted.
// /   Supply a bare host name.
// /   A scheme or a trailing path is stripped before the test.
// /   Default is 'github.com'.
$ApplicationUpdateDomain = 'github.com';
// /  --Application Repository Name--
// /   The name of the repository that holds application updates.
// /   This also names the temporary folder that an update is downloaded into, inside the installation directory.
// /   Valid options are the name of the repository, formatted as a string.
// /   Default is 'ScanCore'.
$ApplicationRepositoryName = 'ScanCore';
// /  --Application Branch Name--
// /   The branch of the repository that application updates are taken from.
// /   Valid options are the name of the branch, formatted as a string.
// /   Default is 'master'.
$ApplicationBranchName = 'master';
// /  --Application Subscriptions--
// /   The files that an application update installs, each named by its path inside the repository.
// /   An update is abandoned as a whole where the repository does not hold a subscribed file.
// /   A name that holds two dots in a row is refused, because it would be written outside the installation directory.
// /   Must be formatted as an array.
// /   Default is every file that ships with the application, apart from the virus definitions.
$ApplicationUpdateSubscriptions = array('README.md', 'LICENSE', 'index.html', 'ScanCore.php', 'ScanCore_Config.php', 'ScanCore_Classifiers.def', 'Documentation/index.html', 'Documentation/CHANGELOG.txt', 'Documentation/ERROR_DESCRIPTIONS.txt', 'Documentation/DEVELOPMENT_NOTES.txt', 'Documentation/ABOUT_COMMAND_LINE_ARGUMENTS.txt', 'Documentation/ABOUT_REPORTS.txt', 'Documentation/ABOUT_SCANNING.txt', 'Documentation/ABOUT_QUALITY_CONTROL.txt', 'Documentation/SECURITY.md', 'Documentation/SECURITY.txt', 'Documentation/Build/index.html', 'Documentation/Build/scancore-check-cli.php', 'Documentation/Build/scancore-test-cli.php', 'Documentation/Logos/index.html', 'Documentation/Logos/ScanCore_banner.png', 'Documentation/Logos/ScanCore_square.png');
// /  --Allow Definition Updates--
// /   Allow the -updatedefinitions switch to update the virus definitions.
// /   Valid options are TRUE or FALSE.
// /   Default is TRUE.
$DefinitionUpdates = TRUE;
// /  --Definition Update URL--
// /   The URL of the repository that definition updates are downloaded from.
// /   Valid options are the URL of a ScanCore definitions repository, formatted as a string.
// /   Default is 'https://github.com/zelon88/ScanCore_Definitions'.
$DefinitionUpdateURL = 'https://github.com/zelon88/ScanCore_Definitions';
// /  --Definition Update Domain--
// /   The host name that is tested for a connection before a definition update is attempted.
// /   Supply a bare host name.
// /   A scheme or a trailing path is stripped before the test.
// /   Default is 'github.com'.
$DefinitionUpdateDomain = 'github.com';
// /  --Definition Repository Name--
// /   The name of the repository that holds definition updates.
// /   This also names the temporary folder that an update is downloaded into, inside the installation directory.
// /   Valid options are the name of the repository, formatted as a string.
// /   Default is 'ScanCore_Definitions'.
$DefinitionRepositoryName = 'ScanCore_Definitions';
// /  --Definition Branch Name--
// /   The branch of the repository that definition updates are taken from.
// /   Valid options are the name of the branch, formatted as a string.
// /   Default is 'main'.
$DefinitionBranchName = 'main';
// /  --Definition Subscriptions--
// /   The definition categories that a definition update downloads & combines into the virus definitions file.
// /   A category is published as a file of its own, so the category 'Virus' is the file ScanCore_Virus.def.
// /   A category that the repository does not publish is left out, & the update reports how many of the subscribed categories it installed.
// /   Must be formatted as an array.
// /   Valid options are the categories that the definitions repository publishes.
// /   Default is array('Virus').
$DefinitionsUpdateSubscriptions = array('Virus');
// /  --Update Method--
// /   The method that downloads an update.
// /   The 'git' method clones the repository with git, & falls back to the 'raw' method where git produces nothing.
// /   The 'raw' method downloads each subscribed file by itself, with PHP where PHP may open a URL & with curl where it may not.
// /   A value that is neither falls back to 'git' & is reported as a warning.
// /   Valid options are 'git' or 'raw'.
// /   Default is 'git'.
$UpdateMethod = 'git';
// /  --Connection Timeout--
// /   The number of seconds to wait for an update host before giving up on it.
// /   Must be formatted as an integer, or an equation that evaluates to an integer.
// /   Default is 15.
$ConnectionTimeout = 15;
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Report Information ...
// /
// /  --Default Maximum Log Size--
// /   The number of bytes that the report file may hold before a new one is started.
// /   A full report file is renamed with the date & a number, & its new name ends in .old.
// /   Must be formatted as an integer, or an equation that evaluates to an integer.
// /   Default is 1024*1024*32.
$DefaultMaxLogSize = 1024*1024*32;
// /  --Enable Debug Mode--
// /   Write every entry to the report file, including the entries that are otherwise left out of it.
// /   Valid options are TRUE or FALSE.
// /   Default is FALSE.
$Debug = FALSE;
// /  --Enable Verbose Mode--
// /   Print every entry to the console, including the entries that are otherwise left off it.
// /   Valid options are TRUE or FALSE.
// /   Default is FALSE.
$Verbose = FALSE;
// /  --Report Suspicious Findings--
// /   Report a detection of medium or low confidence as a Suspicious line.
// /   A detection of high confidence is always reported as an Infected line, whatever this entry is set to.
// /   Set this to FALSE to report detections of high confidence alone.
// /   Valid options are TRUE or FALSE.
// /   Default is TRUE.
$ReportSuspicious = TRUE;
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Scan Information ...
// /
// /  --Memory Limit--
// /   The number of bytes of memory that a scan may use.
// /   This value is handed to the interpreter as its memory limit before a scan starts.
// /   A read budget that would not fit within this limit is lowered for the run & is reported as a warning.
// /   Must be formatted as an integer, or an equation that evaluates to an integer.
// /   Default is 1024*1024*512.
$DefaultMemoryLimit = 1024*1024*512;
// /  --Chunk Size--
// /   The number of bytes of a file that are read & searched at a time, however large the file is.
// /   A signature that spans two chunks is still found.
// /   A chunk is held in memory twice while it is searched, so this is held to a quarter of the memory limit.
// /   A smaller value lowers the memory that a scan needs.
// /   A larger value lowers the number of reads that a scan makes.
// /   Must be formatted as an integer, or an equation that evaluates to an integer.
// /   Default is 1024*1024*8.
$DefaultChunkSize = 1024*1024*8;
// /  --Maximum Scan Depth--
// /   The number of folders, one inside another, that a recursive scan opens.
// /   A folder that sits deeper is not opened & is reported as a warning.
// /   Must be formatted as an integer, or an equation that evaluates to an integer.
// /   Default is 64.
$MaxScanDepth = 64;
// /  --Follow Symbolic Links--
// /   Follow a symbolic link that is found inside a scanned folder.
// /   A link is skipped while this is FALSE, so a link cannot lead a scan out of the folder it was given.
// /   Valid options are TRUE or FALSE.
// /   Default is FALSE.
$FollowSymlinks = FALSE;
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Definition Information ...
// /
// /  --Minimum Data Signature Length--
// /   The number of bytes that a data signature must hold before it is searched for.
// /   A shorter signature matches ordinary content, so it is left out & the number left out is reported as a warning.
// /   Must be formatted as an integer, or an equation that evaluates to an integer.
// /   Default is 4.
$MinimumDataSignatureLength = 4;
// /  --Use Hash Index--
// /   Keep a packed index of the hash definitions beside the virus definitions file, & load it in place of that file.
// /   A large set of hashes loads from the index in a fraction of the time & the memory that reading the file takes.
// /   The index is rebuilt whenever the virus definitions file changes.
// /   Valid options are TRUE or FALSE.
// /   Default is TRUE.
$UseHashIndex = TRUE;
// /  --Hash Index Threshold--
// /   The number of hash definitions that the virus definitions file must hold before an index is built for it.
// /   A smaller set is read from the virus definitions file on every scan & needs no second file.
// /   Must be formatted as an integer, or an equation that evaluates to an integer.
// /   Default is 10000.
$HashIndexThreshold = 10000;
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Classifier Information ...
// /
// /  --Classify Content--
// /   Report what a scanned file is able to do, as well as whether it matches a definition.
// /   A file that holds executable code is reported as code.
// /   Any other file is reported for its handlers, which are the constructs that let a data file reach another file or a host.
// /   A finding is written as a Classified line & never as an Infected line.
// /   Valid options are TRUE or FALSE.
// /   Default is TRUE.
$ClassifyContent = TRUE;
// /  --Enabled Classifiers--
// /   The classifiers that are switched on.
// /   A classifier that is not named here is never loaded, so it costs nothing to scan for.
// /   A name is matched without regard to case.
// /   The entry 'Language' controls the detection of executable code.
// /   Without it a source file is no longer reported as code & is classified for its handlers.
// /   Every other entry names a type that the classifier definitions file declares.
// /   An empty array switches every classifier off.
// /   Must be formatted as an array.
// /   Default is every classifier that the classifier definitions file declares.
$EnabledClassifiers = array('Language', 'SCAD', 'PDF', 'Document', 'Spreadsheet', 'Presentation', 'XPS', 'SVG', 'Markup', 'Stream', 'Transport', 'Model', 'Drawing', 'Ebook', 'Subtitle');
// /  --Classifier File Name--
// /   The name of the classifier definitions file, which sits beside the core.
// /   This file ships with the application & is updated with the application.
// /   Default is 'ScanCore_Classifiers.def'.
$ClassifierFileName = 'ScanCore_Classifiers.def';
// /  --Minimum Language Signatures--
// /   The number of signatures of one language that a file must carry before it is reported as that language.
// /   This applies to a file whose extension names no language, because an extension that names one settles the question.
// /   A value of 1 reports ordinary prose as source code.
// /   Must be formatted as an integer, or an equation that evaluates to an integer.
// /   Default is 2.
$MinimumLanguageSignatures = 2;
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Container Information ...
// /
// /  --Inspect Archived Content--
// /   Open a zip, gzip or tar container & inspect every member inside it.
// /   A member is matched against the virus definitions & is classified, as a file is.
// /   A container that is found inside a container is opened in turn.
// /   A zip that is appended to a file of another format is found & opened as well.
// /   Valid options are TRUE or FALSE.
// /   Default is TRUE.
$InspectArchivedContent = TRUE;
// /  --Maximum Archive Entries--
// /   The number of members that are inspected inside one scanned file, nested containers included.
// /   A file that holds more members is reported as incomplete.
// /   Must be formatted as an integer, or an equation that evaluates to an integer.
// /   Default is 4096.
$MaxArchiveEntries = 4096;
// /  --Maximum Archive Depth--
// /   The number of containers, one inside another, that are opened.
// /   A container that is nested deeper is reported as infected & is not opened.
// /   Must be formatted as an integer, or an equation that evaluates to an integer.
// /   Default is 5.
$MaxArchiveDepth = 5;
// /  --Maximum Archive Entry Size--
// /   The number of bytes that are read from any one member of a container.
// /   A read stops at this value whatever the container declares, so a dishonest header cannot cause a larger read.
// /   A larger member is inspected in part & its file is reported as incomplete.
// /   Must be formatted as an integer, or an equation that evaluates to an integer.
// /   Default is 1024*1024*8.
$MaxArchiveEntrySize = 1024*1024*8;
// /  --Maximum Archive Total Size--
// /   The number of bytes that are read from the members of one scanned file in total.
// /   Reading stops at this value & the file is reported as incomplete.
// /   A zip whose members declare more than this between them, at more than the ratio below, is reported as infected.
// /   Must be formatted as an integer, or an equation that evaluates to an integer.
// /   Default is 1024*1024*64.
$MaxArchiveTotalSize = 1024*1024*64;
// /  --Maximum Archive Compression Ratio--
// /   The number of times that a member may expand beyond its stored size.
// /   A member that is larger than the entry size above & expands by more than this is reported as infected.
// /   Ordinary markup compresses between five & twenty times.
// /   Must be formatted as an integer, or an equation that evaluates to an integer.
// /   Default is 200.
$MaxArchiveCompressionRatio = 200;
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Image Information ...
// /
// /  --Inspect Images--
// /   Check the structure of a png & search every png, jpeg & gif for embedded code.
// /   A pixel flood, an expanding text chunk, an image data overrun, a damaged chunk & data after the end of the image are each reported.
// /   A file that is named as a png & is not one is reported as well.
// /   Valid options are TRUE or FALSE.
// /   Default is TRUE.
$InspectImages = TRUE;
// /  --Maximum Image Pixels--
// /   The largest image, as its width multiplied by its height, that is not reported as infected.
// /   Set this to the largest image that your own decoder can afford to open.
// /   Must be formatted as an integer, or an equation that evaluates to an integer.
// /   Default is 16384*16384.
$MaxImagePixels = 16384*16384;
// /  --Maximum Image Chunk Size--
// /   The number of bytes that a compressed text chunk or profile chunk of a png may expand to.
// /   A chunk that expands further is reported as infected.
// /   Image data that inflates this far past the size its header declares is reported as infected as well.
// /   Must be formatted as an integer, or an equation that evaluates to an integer.
// /   Default is 1024*1024*8.
$MaxImageChunkSize = 1024*1024*8;
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Ownership Information ...
// /
// /  --Preserve Ownership--
// /   Hand anything that ScanCore creates as root to the user & the group that own the installation directory.
// /   This does nothing for any other account, because only root may give a file away.
// /   A file that an update replaces keeps the mode it had, & keeps its owner where root runs the update.
// /   Valid options are TRUE or FALSE.
// /   Default is TRUE.
$PreserveOwnership = TRUE;
// /  --Owner User--
// /   The user that owns anything ScanCore creates as root.
// /   Leave this empty to use the owner of the installation directory.
// /   Valid options are a user name, formatted as a string, or an empty string.
// /   Default is ''.
$OwnerUser = '';
// /  --Owner Group--
// /   The group that owns anything ScanCore creates as root.
// /   Leave this empty to use the group of the installation directory.
// /   Valid options are a group name, formatted as a string, or an empty string.
// /   Default is ''.
$OwnerGroup = '';
// /  --File Permissions--
// /   The mode that is given to a file ScanCore creates as root.
// /   Must be formatted as an octal integer.
// /   Default is 0644.
$FilePermissions = 0644;
// /  --Directory Permissions--
// /   The mode that is given to a folder ScanCore creates as root.
// /   Must be formatted as an octal integer.
// /   Default is 0755.
$DirectoryPermissions = 0755;
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Directory Information ...
// /
// /  --Scan Location--
// /   The path that is scanned when ScanCore is run with no path to scan.
// /   A relative path is resolved against the installation directory.
// /   Default is ''.
$ScanLoc = '';
// /  --Report Location--
// /   The folder that holds the report file.
// /   A relative path is resolved against the installation directory.
// /   The folder is created when the first entry is written to the report file.
// /   Default is 'Logs'.
$ReportDir = 'Logs';
// /  --Report File Name--
// /   The name of the report file.
// /   Default is 'ScanCore_Report.txt'.
$ReportFileName = 'ScanCore_Report.txt';
// /  --Definitions File Name--
// /   The name of the virus definitions file.
// /   Default is 'ScanCore_Combined_Definitions.def'.
$DefsFileName = 'ScanCore_Combined_Definitions.def';
// /  --Installation Directory--
// /   The absolute path of the folder that holds ScanCore.php.
// /   Set this by hand where this file is kept in another folder & is named with the -configfile switch.
// /   A folder that does not hold the core is replaced by the folder that does, & that is reported as a warning.
// /   Default is realpath(dirname(__FILE__)).
$InstallDir = realpath(dirname(__FILE__));
// /  --Definitions File--
// /   The absolute path of the virus definitions file.
// /   Default is $InstallDir.DIRECTORY_SEPARATOR.$DefsFileName.
$DefsFile = $InstallDir.DIRECTORY_SEPARATOR.$DefsFileName;
// / -----------------------------------------------------------------------------------
