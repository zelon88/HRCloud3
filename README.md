# HRCloud3

A home-hosted cloud storage platform, built on the [HRProprietary Engine](https://github.com/zelon88/HRProprietary).

HRCloud3 is the successor to [HRCloud2](https://github.com/zelon88/HRCloud2). It is being rebuilt from scratch. It installs, keeps accounts, answers the Engine's commands & serves its pages: signing in, an empty App Launcher, Files & settings.

No cookies. No databases. No third party connections. No analytics.

## Where The Rebuild Stands

v1.0.0 is HRCloud3 on the HRProprietary Engine v4.3.4, keeping files.

What v1.0.0 adds:
- Libraries, kept by a new Engine core, the Storage Core. A personal library gives every user a space of their own. A shared library is read by every signed in user & written by admins, everyone or nobody, as you set. It records who added what, so an admin or whoever added something can delete it forever.
- A Files page: folders, upload (or drop), download, rename, move, copy & delete.
- A trash. Put things back, delete them forever, or let the trash empty itself after 30 days.
- Names are kept exactly as typed, a link is never followed, & nothing is ever written over.
- The quota & the storage reserve hold for every library, with or without the listener.

What v0.9.9 added:
- A modular interface in `UI/Default`, built by a new Engine core, the Page Core, from separate components, language packs, colors & scripts. An interface of your own carries only the files it changes.
- Signing in, the App Launcher (empty for now) & a settings page.
- Changing your own password or email address while signed in.
- With verbose logging on, every page logs exactly which files, at which versions, it was built from.

What the Engine already gives it:
- Accounts with no cookies & no database, email confirmation, account recovery & limits on attempts.
- A folder of their own for every signed in user, outside the web root.
- An installer, a repair command, self updating, a dependency manifest, sandboxing & logging.

What is planned, roughly in this order:
- Encryption at rest, per library, at an install level & at a user level the administrator cannot read.
- Compression with a dictionary of each user's own.
- The rest of the web interface: administration.
- An App Launcher for sandboxed apps, & first party apps such as Notes & HRAI.
- Sharing, friends & teams.

The prototype that held this repository until v0.9.7 is retired. Its notes are kept in `Documentation/CHANGELOG.txt`.

## Installing

HRCloud3 runs on Linux with Apache & PHP, & installs as every application on the HRProprietary Engine does. Unpack a release into `/var/www/html/HRProprietary/HRCloud3`, then from there:

```
sudo php cloudCore.php --config --repair
sudo php cloudCore.php --setup
sudo php cloudCore.php -fp
sudo php cloudCore.php -v
```

`-h` lists every command. Accounts are added from the command line, & every visitor signs in by default:

```
sudo php cloudCore.php --account --add <USERNAME> <ADDRESS>
sudo php cloudCore.php --account --list
```

Libraries are listed in `Resources/config.php` under `--Libraries--`. By default there is a personal library, Drive, & a shared one, Shared, both kept in the data location. `-v` names each library & whether it can be used.

## Documentation

- `Documentation/CHANGELOG.txt` lists every change, newest first.
- `Documentation/ABOUT_AUTHENTICATION.txt` describes accounts.
- `Documentation/CREATING_INTERFACES.txt` describes HRCloud3's interfaces & how to make one.
- `Documentation/ABOUT_PAGES.txt` describes how the Engine's Page Core builds a page.
- `Documentation/ABOUT_STORAGE.txt` describes libraries, the Files page & the trash.
- `Documentation/ABOUT_ENGINE_CONTRACT.txt` describes what HRCloud3 supplies to the Engine.
- `Documentation/ENGINE_RECONCILIATION_HRCLOUD3.txt` records every change HRCloud3 makes to its copy of the Engine.
- `Documentation/ERROR_DESCRIPTIONS.txt` describes every warning & numbered error.

## License

GNU GPLv3. See `LICENSE`.

<3 Open-Source

[HRCloud3](https://github.com/zelon88/HRCloud3) by [HonestRepair](https://www.HonestRepair.net)
