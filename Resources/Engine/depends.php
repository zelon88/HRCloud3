<?php
// / -----------------------------------------------------------------------------------
// / Copyright Information ...
// / HRProprietary Engine, Copyright on 9/4/2026 by Justin Grimes, www.github.com/zelon88
// /
// / License Information ...
// / This project is protected by the GNU GPLv3 Open-Source license.
// / https://www.gnu.org/licenses/gpl-3.0.html
// /
// / File Information ...
// / v5.0.0.
// / THIS IS THE ENGINE'S HALF OF THE DEPENDENCY MANIFEST, & it lists what the Engine needs whatever application bundles it.
// / This file is data. It defines nothing & does nothing.
// / The Engine's verifyDependsManifest() reads it, & nothing else may.
// /
// / The dependency manifest is one list with two halves, as config.php is.
// /   This file                       What the Engine needs. Every program it runs, from v4.3.4, which is the parts of
// /                                   the operating system it runs, PHP & the extensions the Engine calls, the web
// /                                   server, Curl, Tar, Bubblewrap & AppArmor's tools.
// /   Resources/Contract/depends.php  What the application needs, which is every tool it runs.
// / The two are read as one list, this half first, so everything the Engine needs is installed before anything an
// / application runs. A name is in one half only. An application that names one the Engine's half names is told so,
// / & the Engine's entry is the one used.
// / Each entry is written as the application's half documents an entry, with every field it describes.
// / This half ships with the Engine & is replaced with it. The Engine pins it EXACTLY, in getAcceptedEngineManifest()
// / in engine.php, so an Engine laid over another release's half is caught. The application pins its own half.
// /
// / A program the Engine runs is declared here whoever wrote it, from v4.3.4, the operating system's included.
// / Type system is a part of the operating system. It is checked & reported like any other entry, & it is never installed,
// / updated or removed, because the host's own package manager keeps it & everything else on the host runs it.
// / Programs names the other programs the Engine runs from the same dependency, & an entry is present only where all are.
// /
// / Hardware Requirements ...
// / This application requires at least a Raspberry Pi Model B+ or greater.
// / This application will run on just about any x86 or x64 computer.
// /
// / Dependency Requirements ...
// / This file & Resources/Contract/depends.php are the authoritative list of dependencies, & nothing else is.
// /
// / <3 Open-Source
// / -----------------------------------------------------------------------------------


// / -----------------------------------------------------------------------------------
// / A component may only be loaded by an application.
// / This file is the Engine's, so its guard names the Engine.
if (!isset($CoreLoaded) or $CoreLoaded !== TRUE) die('ERROR!!! Engine-35000, The Engine dependency manifest cannot be loaded directly!'.PHP_EOL);
// / -----------------------------------------------------------------------------------


// / -----------------------------------------------------------------------------------
// / The version of this half. The Engine reads it without executing the file, & pins it exactly.
$EngineDependsVersion = 'v5.0.0';
// / -----------------------------------------------------------------------------------


// / -----------------------------------------------------------------------------------
$EngineDependsManifest = array(

  // / ---- The operating system, from v4.3.4. What the host provides & the Engine runs. ----
  // / Every command the Engine & an application start runs through the shell, & so does every builtin they use.
  // / dash is /bin/sh on Debian & Ubuntu, & it reports no version of its own.
  array('Name' => 'Shell', 'Binary' => 'sh', 'Type' => 'system', 'Package' => 'dash',
    'MinimumVersion' => '', 'VersionCommand' => '', 'VersionPattern' => '',
    'Required' => TRUE, 'Subsystem' => 'Core', 'Requires' => array(),
    'License' => 'BSD-3-Clause', 'Source' => 'https://git.kernel.org/pub/scm/utils/dash/dash.git', 'Purpose' => 'Runs every command the Engine & an application start, & its builtins cd, command, echo, exec, exit, export, kill, test & umask.'),
  // / head & tr are run by a version source an application's half writes, & by pipelines, & are always part of coreutils.
  array('Name' => 'Coreutils', 'Binary' => 'rm', 'Programs' => array('chmod', 'chown', 'cp', 'env', 'head', 'nice', 'nohup', 'timeout', 'tr', 'true', 'uname'), 'Type' => 'system', 'Package' => 'coreutils',
    'MinimumVersion' => '', 'VersionCommand' => 'rm --version', 'VersionPattern' => '/\(GNU coreutils\) (\d+\.\d+)/',
    'Required' => TRUE, 'Subsystem' => 'Core', 'Requires' => array(),
    'License' => 'GPL-3.0', 'Source' => 'https://www.gnu.org/software/coreutils', 'Purpose' => 'Removes & copies files, sets their owner & mode, bounds how long & how hard a command runs, & names the machine.'),
  array('Name' => 'Findutils', 'Binary' => 'find', 'Type' => 'system', 'Package' => 'findutils',
    'MinimumVersion' => '', 'VersionCommand' => 'find --version', 'VersionPattern' => '/\(GNU findutils\) (\d+\.\d+)/',
    'Required' => TRUE, 'Subsystem' => 'Core', 'Requires' => array(),
    'License' => 'GPL-3.0', 'Source' => 'https://www.gnu.org/software/findutils', 'Purpose' => 'Walks a managed location, so -fp can set the owner & mode of everything in it.'),
  array('Name' => 'Util-linux', 'Binary' => 'setsid', 'Type' => 'system', 'Package' => 'util-linux',
    'MinimumVersion' => '', 'VersionCommand' => 'setsid --version', 'VersionPattern' => '/util-linux (\d+\.\d+)/',
    'Required' => TRUE, 'Subsystem' => 'Core', 'Requires' => array(),
    'License' => 'GPL-2.0-or-later', 'Source' => 'https://github.com/util-linux/util-linux', 'Purpose' => 'Starts a worker in a session of its own, so it outlives the request that started it.'),
  // / prlimit is apart from Util-linux, because a host can have setsid without it, & limitCommand() warns & runs a conversion
  // / without the limit where it is missing, so it is not required.
  array('Name' => 'Prlimit', 'Binary' => 'prlimit', 'Type' => 'system', 'Package' => 'util-linux',
    'MinimumVersion' => '', 'VersionCommand' => 'prlimit --version', 'VersionPattern' => '/util-linux (\d+\.\d+)/',
    'Required' => FALSE, 'Subsystem' => 'Core', 'Requires' => array(),
    'License' => 'GPL-2.0-or-later', 'Source' => 'https://github.com/util-linux/util-linux', 'Purpose' => 'Holds a conversion to the address space its sandbox profile asks for, & to a data limit where no resource scope can be made.'),
  // / procps is apt rather than system, because a slim container image has none & installing it is harmless.
  // / It is in Core, so --uninstall-depends never removes it.
  array('Name' => 'Procps', 'Binary' => 'ps', 'Programs' => array('pgrep'), 'Type' => 'apt', 'Package' => 'procps',
    'MinimumVersion' => '', 'VersionCommand' => 'ps --version', 'VersionPattern' => '/procps-ng (?:version )?(\d+\.\d+)/',
    'Required' => FALSE, 'Subsystem' => 'Core', 'Requires' => array(),
    'License' => 'GPL-2.0-or-later', 'Source' => 'https://gitlab.com/procps-ng/procps', 'Purpose' => 'Says whether a worker is still running & counts the web server\'s workers. Without it a stream is taken to have finished as it starts.'),
  array('Name' => 'E2fsprogs', 'Binary' => 'chattr', 'Type' => 'system', 'Package' => 'e2fsprogs',
    'MinimumVersion' => '', 'VersionCommand' => '', 'VersionPattern' => '',
    'Required' => FALSE, 'Subsystem' => 'Core', 'Requires' => array(),
    'License' => 'GPL-2.0', 'Source' => 'https://e2fsprogs.sourceforge.net', 'Purpose' => 'Clears the immutable & append only attributes from a file before the Engine removes it.'),
  // / A container usually has no systemd, & the Engine then runs no unit, no timer & no bound through it.
  array('Name' => 'Systemd', 'Binary' => 'systemctl', 'Programs' => array('loginctl', 'systemd-analyze', 'systemd-run'), 'Type' => 'system', 'Package' => 'systemd',
    'MinimumVersion' => '', 'VersionCommand' => 'systemctl --version', 'VersionPattern' => '/^systemd (\d+)/',
    'Required' => FALSE, 'Subsystem' => 'Core', 'Requires' => array(),
    'License' => 'LGPL-2.1', 'Source' => 'https://systemd.io', 'Purpose' => 'Runs the listener & the managers as units & timers, & bounds the memory & processor a conversion may use.'),

  // / ---- Package managers, from v4.3.4. ----
  // / The Dependency Core installs through whichever one the host has, & the Environment Core asks dpkg-query, or rpm where
  // / there is none, which package a file belongs to. A host has one of them at most.
  array('Name' => 'APT', 'Binary' => 'apt-get', 'Programs' => array('dpkg-query'), 'Type' => 'system', 'Package' => 'apt dpkg',
    'MinimumVersion' => '', 'VersionCommand' => 'apt-get --version', 'VersionPattern' => '/^apt (\d+\.\d+)/',
    'Required' => FALSE, 'Subsystem' => 'Core', 'Requires' => array(),
    'License' => 'GPL-2.0-or-later', 'Source' => 'https://salsa.debian.org/apt-team/apt', 'Purpose' => 'Installs, upgrades & removes a dependency on Debian & Ubuntu, & dpkg-query says which package a file belongs to.'),
  array('Name' => 'DNF', 'Binary' => 'dnf', 'Programs' => array('rpm'), 'Type' => 'system', 'Package' => 'dnf rpm',
    'MinimumVersion' => '', 'VersionCommand' => '', 'VersionPattern' => '',
    'Required' => FALSE, 'Subsystem' => 'Core', 'Requires' => array(),
    'License' => 'GPL-2.0-or-later', 'Source' => 'https://github.com/rpm-software-management/dnf', 'Purpose' => 'Installs, upgrades & removes a dependency on Fedora & its relatives, & rpm says which package a file belongs to.'),
  array('Name' => 'YUM', 'Binary' => 'yum', 'Programs' => array('rpm'), 'Type' => 'system', 'Package' => 'yum rpm',
    'MinimumVersion' => '', 'VersionCommand' => '', 'VersionPattern' => '',
    'Required' => FALSE, 'Subsystem' => 'Core', 'Requires' => array(),
    'License' => 'GPL-2.0-or-later', 'Source' => 'https://github.com/rpm-software-management/yum', 'Purpose' => 'Installs, upgrades & removes a dependency on an older Red Hat host without dnf, & rpm says which package a file belongs to.'),
  array('Name' => 'APK', 'Binary' => 'apk', 'Type' => 'system', 'Package' => 'apk-tools',
    'MinimumVersion' => '', 'VersionCommand' => '', 'VersionPattern' => '',
    'Required' => FALSE, 'Subsystem' => 'Core', 'Requires' => array(),
    'License' => 'GPL-2.0', 'Source' => 'https://gitlab.alpinelinux.org/alpine/apk-tools', 'Purpose' => 'Installs, upgrades & removes a dependency on Alpine.'),
  array('Name' => 'Pacman', 'Binary' => 'pacman', 'Type' => 'system', 'Package' => 'pacman',
    'MinimumVersion' => '', 'VersionCommand' => '', 'VersionPattern' => '',
    'Required' => FALSE, 'Subsystem' => 'Core', 'Requires' => array(),
    'License' => 'GPL-2.0-or-later', 'Source' => 'https://gitlab.archlinux.org/pacman/pacman', 'Purpose' => 'Installs, upgrades & removes a dependency on Arch.'),
  // / The official PHP container image has no package for an extension, & builds one with this instead. It belongs to the image.
  array('Name' => 'PHP Ext Installer', 'Binary' => 'docker-php-ext-install', 'Type' => 'system', 'Package' => '',
    'MinimumVersion' => '', 'VersionCommand' => '', 'VersionPattern' => '',
    'Required' => FALSE, 'Subsystem' => 'Core', 'Requires' => array(),
    'License' => 'MIT', 'Source' => 'https://github.com/docker-library/php', 'Purpose' => 'Installs a PHP extension on the official PHP container image, which has no package for one.'),

  // / ---- Core. Nothing else can be installed or run without these. ----
  // / mbstring is the Authentication Core's, which counts the characters in a password.
  array('Name' => 'PHP', 'Binary' => 'php', 'Type' => 'apt', 'Package' => 'php-cli php-mbstring',
    'MinimumVersion' => '8.0', 'VersionCommand' => 'php -v', 'VersionPattern' => '/^PHP (\d+\.\d+)/',
    'Required' => TRUE, 'Subsystem' => 'Core', 'Requires' => array(),
    'License' => 'PHP-3.01', 'Source' => 'https://www.php.net', 'Purpose' => 'Runs the application.'),
  // / A PHP extension is not proved by the PHP binary being present. It is asked for by name, from PHP itself,
  // / & is its own entry for that reason.
  array('Name' => 'PHP OpenSSL', 'Binary' => '', 'Type' => 'php-extension', 'Package' => 'openssl',
    'MinimumVersion' => '', 'VersionCommand' => 'php -r "exit(extension_loaded(\'openssl\') ? 0 : 1);"', 'VersionPattern' => '',
    'Required' => FALSE, 'Subsystem' => 'Resource Awareness', 'Requires' => array('PHP'),
    'License' => 'PHP-3.01', 'Source' => 'https://www.php.net/manual/en/book.openssl.php', 'Purpose' => 'Encrypts every message on a manager socket. Resource awareness cannot work without it & fails quietly rather than loudly.'),
  // / Root hands a command to the web server account by forking & dropping to it, which takes pcntl & posix.
  // / Without either, such a command is refused rather than run as root, so -fp & the listener commands root hands over fail.
  // / The command line PHP of a distribution carries both. The official PHP container image carries posix & not pcntl.
  array('Name' => 'PHP Pcntl', 'Binary' => '', 'Type' => 'php-extension', 'Package' => 'pcntl',
    'MinimumVersion' => '', 'VersionCommand' => 'php -r "exit(extension_loaded(\'pcntl\') ? 0 : 1);"', 'VersionPattern' => '',
    'Required' => FALSE, 'Subsystem' => 'Core', 'Requires' => array('PHP'),
    'License' => 'PHP-3.01', 'Source' => 'https://www.php.net/manual/en/book.pcntl.php', 'Purpose' => 'Lets root run a command as the web server account. Without it that command is refused.'),
  array('Name' => 'PHP Posix', 'Binary' => '', 'Type' => 'php-extension', 'Package' => 'posix',
    'MinimumVersion' => '', 'VersionCommand' => 'php -r "exit(extension_loaded(\'posix\') ? 0 : 1);"', 'VersionPattern' => '',
    'Required' => FALSE, 'Subsystem' => 'Core', 'Requires' => array('PHP'),
    'License' => 'PHP-3.01', 'Source' => 'https://www.php.net/manual/en/book.posix.php', 'Purpose' => 'Names accounts & lets root run a command as the web server account. Without it that command is refused.'),
  // / The Engine tests & reloads the configuration it writes with apachectl, or apache2ctl where that is all there is, & turns
  // / off a configuration an install turned on with a2disconf. All three come with the package that brings apache2.
  array('Name' => 'Apache', 'Binary' => 'apache2', 'Programs' => array(array('apachectl', 'apache2ctl'), 'a2disconf'), 'Type' => 'apt', 'Package' => 'apache2 libapache2-mod-php',
    'MinimumVersion' => '2.4', 'VersionCommand' => 'apache2 -v', 'VersionPattern' => '/Apache\/(\d+\.\d+)/',
    'Required' => TRUE, 'Subsystem' => 'Core', 'Requires' => array('PHP'),
    'License' => 'Apache-2.0', 'Source' => 'https://httpd.apache.org', 'Purpose' => 'Serves the web interface.'),
  array('Name' => 'Curl', 'Binary' => 'curl', 'Type' => 'apt', 'Package' => 'curl',
    'MinimumVersion' => '7.0', 'VersionCommand' => 'curl --version', 'VersionPattern' => '/^curl (\d+\.\d+)/',
    'Required' => TRUE, 'Subsystem' => 'Core', 'Requires' => array(),
    'License' => 'curl', 'Source' => 'https://curl.se', 'Purpose' => 'Fetches update packages & remote streams.'),

  // / ---- Updates. ----
  // / An update is fetched with Curl & unpacked with tar. Tar was the application's until v4.3.4, when the Engine's update
  // / was found running it undeclared. It is part of the operating system, so it is never installed or removed.
  array('Name' => 'Tar', 'Binary' => 'tar', 'Type' => 'system', 'Package' => 'tar',
    'MinimumVersion' => '1.30', 'VersionCommand' => 'tar --version', 'VersionPattern' => '/tar.*?(\d+\.\d+)/',
    'Required' => TRUE, 'Subsystem' => 'Updates', 'Requires' => array(),
    'License' => 'GPL-3.0', 'Source' => 'https://www.gnu.org/software/tar', 'Purpose' => 'Unpacks the release an update fetches, & creates & extracts tar archives for an application that offers them.'),

  // / ---- Isolation. Installed before anything an application runs, because everything it runs runs inside it. ----
  array('Name' => 'Bubblewrap', 'Binary' => 'bwrap', 'Type' => 'apt', 'Package' => 'bubblewrap',
    'MinimumVersion' => '0.4', 'VersionCommand' => 'bwrap --version', 'VersionPattern' => '/bubblewrap (\d+\.\d+)/',
    'Required' => TRUE, 'Subsystem' => 'Sandbox', 'Requires' => array(),
    'License' => 'LGPL-2.0', 'Source' => 'https://github.com/containers/bubblewrap', 'Purpose' => 'Isolates every conversion from the host & the network.'),
  array('Name' => 'AppArmor Utils', 'Binary' => 'apparmor_parser', 'Type' => 'apt', 'Package' => 'apparmor-utils',
    'MinimumVersion' => '', 'VersionCommand' => '', 'VersionPattern' => '',
    'Required' => FALSE, 'Subsystem' => 'Sandbox', 'Requires' => array('Bubblewrap'),
    'License' => 'GPL-2.0', 'Source' => 'https://gitlab.com/apparmor/apparmor', 'Purpose' => 'Loads the profile bubblewrap needs on a restricted kernel.'));
// / -----------------------------------------------------------------------------------
?>
