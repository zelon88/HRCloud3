# HRCloud3

A home-hosted cloud storage platform, built on the [HRProprietary Engine](https://github.com/zelon88/HRProprietary).

HRCloud3 is the successor to [HRCloud2](https://github.com/zelon88/HRCloud2). It is being rebuilt from scratch, & this release is the first step. It installs, keeps accounts & answers the Engine's commands. It has no web interface yet.

No cookies. No databases. No third party connections. No analytics.

## Where The Rebuild Stands

v0.9.8 is HRCloud3 on the HRProprietary Engine v4.3.4, with nothing else of its own yet.

What the Engine already gives it:
- Accounts with no cookies & no database, email confirmation, account recovery & limits on attempts.
- A folder of their own for every signed in user, outside the web root, with a quota.
- An installer, a repair command, self updating, a dependency manifest, sandboxing & logging.

What is planned, roughly in this order:
- A sign in interface & changing your own password while signed in.
- Folders within folders, & libraries an administrator defines.
- Encryption at rest, per library, at an install level & at a user level the administrator cannot read.
- Compression with a dictionary of each user's own.
- The web interface: files, settings & administration.
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

`-h` lists every command. Accounts are managed from the command line until the interface arrives:

```
sudo php cloudCore.php --account --add <USERNAME> <ADDRESS>
sudo php cloudCore.php --account --list
```

## Documentation

- `Documentation/CHANGELOG.txt` lists every change, newest first.
- `Documentation/ABOUT_AUTHENTICATION.txt` describes accounts.
- `Documentation/ABOUT_ENGINE_CONTRACT.txt` describes what HRCloud3 supplies to the Engine.
- `Documentation/ENGINE_RECONCILIATION_HRCLOUD3.txt` records every change HRCloud3 makes to its copy of the Engine.
- `Documentation/ERROR_DESCRIPTIONS.txt` describes every warning & numbered error.

## License

GNU GPLv3. See `LICENSE`.

<3 Open-Source

[HRCloud3](https://github.com/zelon88/HRCloud3) by [HonestRepair](https://www.HonestRepair.net)
