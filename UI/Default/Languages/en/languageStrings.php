<?php
// / -----------------------------------------------------------------------------------
// / COPYRIGHT INFORMATION ...
// / HRCloud3, Copyright on 11/26/2026 by Justin Grimes, www.github.com/zelon88
// /
// / LICENSE INFORMATION ...
// / This project is protected by the GNU GPLv3 Open-Source license.
// / https://www.gnu.org/licenses/gpl-3.0.html
// /
// / APPLICATION INFORMATION ...
// / HRCloud3 is a home-hosted cloud storage platform built on the HRProprietary Engine.
// /
// / FILE INFORMATION ...
// / v1.0.0.
// / The English language pack of the Default interface. Every string a page shows, & nothing else.
// / The Page Core requires it from inside the function that builds a page, so every string is local to that call & nothing
// / outside it can read one. English is the last rung of the language ladder, so this pack must define every string a page reads.
// / Another pack defines only what it translates, & the Page Core loads this one under it.
// / UTF-8, with no byte order mark.
// / See Documentation/CREATING_INTERFACES.txt.
// /
// / HARDWARE REQUIREMENTS ...
// / This application requires at least a Raspberry Pi Model B+ or greater.
// / This application will run on just about any x86 or x64 computer.
// /
// / DEPENDENCY REQUIREMENTS ...
// / The authoritative list of dependencies is Resources/Engine/depends.php & Resources/Contract/depends.php, the Engine's half & the application's, & nothing else.
// / Run  php cloudCore.php -v  to see what this installation has.
// /
// / <3 Open-Source
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Refuse direct execution. This file is a component & has no standalone context.
if (!isset($CoreLoaded) or $CoreLoaded !== TRUE) die('ERROR!!! HRCloud3-2: This file cannot process your request! Please submit your request to cloudCore.php instead!'.PHP_EOL);
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / The version of this pack, which the Page Core compares with $RequiredLanguageVersion, & the way it reads.
$LanguageVersion = 'v1.0.0';
$LanguageDirection = 'ltr';
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / What each page is called, in the title of the window.
$TextPageTitles = array('engine' => 'Sign in', 'home' => 'Home', 'files' => 'Files', 'settings' => 'Settings');
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / The bar at the top of every page a signed in user sees.
$TextMainNavigation = 'Main';
$TextHome = 'Home';
$TextFiles = 'Files';
$TextSettings = 'Settings';
$TextSignIn = 'Sign in';
$TextSignOut = 'Sign out';
$TextSignedInAs = 'Signed in as';
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / The home page, which is the App Launcher.
$TextLauncherHeading = 'Apps';
$TextWelcome = 'Welcome,';
$TextNoApps = 'No apps are installed yet.';
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / The Files page, where a signed in user keeps files in the libraries an administrator names, from v1.0.0.
// / A library's own name is the administrator's, & is shown as it is written.
// / {name}, {count}, {days} & {percent} are replaced by the page. A sentence about a number is given for each plural category the
// / language has, as Intl.PluralRules names them, & English has one & other.
$TextFilesHeading = 'Files';
$TextLibrary = 'Library';
$TextLibraryPersonal = 'Only you can see this library.';
$TextLibraryShared = 'Everybody who signs in can see this library.';
$TextLibrarySharedReadOnly = 'Everybody who signs in can see this library. You can only read it.';
$TextFolderTrail = 'Folder';
$TextUpload = 'Upload files';
$TextNewFolder = 'New folder';
$TextShowTrash = 'Trash';
$TextShowFiles = 'Back to files';
$TextEmptyTrash = 'Empty the trash';
$TextFilesCaption = 'What this folder holds';
$TextTrashCaption = 'What was deleted from this library';
$TextColumnName = 'Name';
$TextColumnSize = 'Size';
$TextColumnChanged = 'Changed';
$TextColumnFrom = 'Deleted from';
$TextColumnDeleted = 'Deleted';
$TextColumnActions = 'Actions';
$TextDownload = 'Download';
$TextRename = 'Rename';
$TextMove = 'Move';
$TextCopy = 'Copy';
$TextDelete = 'Delete';
$TextRestore = 'Put back';
$TextDeleteForever = 'Delete forever';
$TextOpenFolder = 'Open the folder {name}';
$TextTopOfLibrary = 'the top of the library';
$TextFolderEmpty = 'This folder is empty. Upload files or drop them here.';
$TextTrashEmpty = 'The trash is empty.';
$TextLoading = 'Loading';
$TextNoLibraries = 'No library is available. Contact the administrator.';
$TextFilesNotSignedIn = 'Sign in to see your files.';
$TextNameLabel = 'Name';
$TextIntoLibrary = 'Into the library';
$TextIntoFolder = 'Into the folder';
$TextIntoFolderNote = 'Separate the names of folders with /. Leave it empty for the top of the library.';
$TextCancel = 'Cancel';
$TextConfirmDelete = 'Move {name} to the trash?';
$TextConfirmPurge = 'Delete {name} forever? It cannot be put back.';
$TextConfirmEmpty = 'Empty the trash? Nothing in it can be put back.';
$TextUploading = 'Uploading {name}, {percent}%';
$TextUploadedSome = array('one' => '{count} file was uploaded. {name}: ', 'other' => '{count} files were uploaded. {name}: ');
$TextUploadedAll = array('one' => '{count} file was uploaded.', 'other' => '{count} files were uploaded.');
$TextTrashKeeps = array('one' => 'What is deleted is kept here for {days} day, then deleted forever.', 'other' => 'What is deleted is kept here for {days} days, then deleted forever.');
$TextTrashKeepsForever = 'What is deleted is kept here until the trash is emptied.';
$TextFolderCut = array('one' => 'This folder holds more than {count} item, so only the first is shown.', 'other' => 'This folder holds more than {count} items, so only the first {count} are shown.');
$TextSizeUnits = array('B', 'KB', 'MB', 'GB', 'TB');
// / What the Files page says when a library answers, by the outcome the Engine's Storage Core names.
$TextFileOutcomes = array(
  'made' => 'The folder was made.',
  'uploaded' => 'Uploaded.',
  'renamed' => 'Renamed.',
  'moved' => 'Moved.',
  'copied' => 'Copied.',
  'trashed' => 'Moved to the trash.',
  'restored' => 'Put back.',
  'purged' => 'Deleted forever.',
  'emptied' => 'The trash was emptied.',
  'exists' => 'Something with that name is already there.',
  'name-invalid' => 'That name cannot be used. A name holds no slash, does not begin or end with a space, and is not . or ..',
  'missing' => 'That is not there any more.',
  'into-itself' => 'A folder cannot go inside itself.',
  'read-only' => 'You can only read this library.',
  'not-allowed' => 'Only an administrator, or whoever added it, can delete that forever.',
  'quota' => 'That would take you past the space you may use.',
  'disk-full' => 'The server does not have room for that.',
  'too-large' => 'That is larger than this server accepts.',
  'upload-failed' => 'That upload did not finish. Please try again.',
  'library-unknown' => 'That library is not available.',
  'folder-invalid' => 'That folder cannot be used.',
  'action-invalid' => 'That could not be done.',
  'unavailable' => 'That could not be done right now. Please try again later.',
  'signed-out' => 'You have been signed out.',
  'no-answer' => 'The server did not answer. Please try again.');
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / The page the Engine names, which signs a visitor in, makes an account, confirms an address & recovers an account.
// / Where HRConvert2's English pack already says a thing, it is said here in the same words.
$TextSignInHeading = 'Sign in';
$TextRegisterHeading = 'Create an account';
$TextConfirmHeading = 'Confirm your account';
$TextRecoverHeading = 'Recover your account';
$TextResetHeading = 'Choose a new password';
$TextUsername = 'Username';
$TextPassword = 'Password';
$TextEmail = 'Email address';
$TextCode = 'Code';
$TextNewPassword = 'New password';
$TextCurrentPassword = 'Current password';
$TextConfirmButton = 'Confirm';
$TextSendAgain = 'Send the code again';
$TextSendCode = 'Send a code';
$TextSavePassword = 'Change password';
$TextForgotPassword = 'Forgot your password?';
$TextHaveCode = 'Have a code?';
$TextBackToSignIn = 'Back to sign in';
$TextContinueWithout = 'Continue without signing in';
$TextSigningInUnavailable = 'Signing in is unavailable on this server right now.';
$TextNoEmail = 'This server cannot send email, so this cannot be done here. Contact the administrator.';
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / The settings page.
$TextAccountHeading = 'Your account';
$TextAddressLabel = 'Email address';
$TextChangePasswordHeading = 'Change your password';
$TextChangePasswordNote = 'Changing your password signs you out everywhere, including here. Then you sign in with the new password.';
$TextChangePasswordButton = 'Change password';
$TextChangeAddressHeading = 'Change your email address';
$TextChangeAddressNote = 'We email a code to the new address. Your account keeps its current address until you enter that code.';
$TextNewAddress = 'New email address';
$TextSendAddressCode = 'Send a code';
$TextConfirmAddressButton = 'Confirm';
$TextSave = 'Save';
$TextAppearanceHeading = 'Appearance';
$TextInterface = 'Interface';
$TextLanguage = 'Language';
$TextColor = 'Color';
$TextColorNames = array('blue' => 'Blue', 'green' => 'Green', 'red' => 'Red', 'grey' => 'Gray', 'orange' => 'Orange', 'purple' => 'Purple', 'dark' => 'Dark');
$TextNotSignedIn = 'Sign in to change your account.';
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / What a page says when the server answers, by the outcome the Engine names.
// / password-short is followed by the fewest characters a password may have, which the page adds.
$TextOutcomes = array(
  'credentials-wrong' => 'The username or password is incorrect.',
  'too-many' => 'Too many attempts. Please wait a few minutes and try again.',
  'unconfirmed' => 'Confirm your account with the code we emailed you first.',
  'disabled' => 'This account cannot sign in. Contact the administrator.',
  'unavailable' => 'That could not be done right now. Please try again later.',
  'registration-closed' => 'New accounts cannot be created here. Contact the administrator.',
  'username-invalid' => 'A username is 3 to 64 letters, digits, dots, dashes or underscores, and starts with a letter or a digit.',
  'username-taken' => 'That username is taken. Choose another.',
  'email-invalid' => 'That email address cannot be used.',
  'password-short' => 'Passwords must have at least this many characters:',
  'registered-confirm' => 'Your account was created. We emailed you a code to confirm it.',
  'registered-unsent' => 'Your account was created, but this server could not email your code. Contact the administrator.',
  'registered-ready' => 'Your account was created. You can sign in now.',
  'code-wrong' => 'That code is not right.',
  'code-expired' => 'That code can no longer be used. Ask for a new one.',
  'confirmed' => 'Your account is confirmed. You can sign in now.',
  'resent' => 'If that account is waiting to be confirmed, a new code is on its way.',
  'recovery-sent' => 'If that account exists, a code is on its way to its email address.',
  'reset-done' => 'Your password was changed. You can sign in now.',
  'signed-out' => 'You have been signed out.',
  'ended' => 'You were signed out, because your sign in was used somewhere else.',
  'no-answer' => 'The server did not answer. Please try again.',
  'password-changed' => 'Your password was changed, and you were signed out everywhere. Sign in with the new password.',
  'address-code-sent' => 'We emailed a code to the new address. Enter it below.',
  'address-unsent' => 'This server could not email a code to that address.',
  'address-changed' => 'Your email address was changed. We let the old address know.');
// / -----------------------------------------------------------------------------------
