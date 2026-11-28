# The ScanCore pipeline

The ScanCore pipeline examines a file rather than converting it. It reports what it finds & converts nothing.

This file is written by `Documentation/Build/hrconvert2-approvals.py --readme`.
It is rewritten from the pipeline's own declarations & from the dependency manifest,
so it cannot drift from either. Do not edit it by hand.

## What it declares

- **Version** v5.0.1
- **Kind** scanner
- **Family** ScanCore
- **Entry point** scanWithScanCore
- **Subsystem** Security
- **Available to** cli, web, gui
- **Offered where** ScanCore is installed

## Dependencies

- **Bubblewrap** (required, 0.4 or newer, LGPL-2.0). Isolates every conversion from the host & the network.
- **Curl** (required, 7.0 or newer, curl). Fetches update packages & remote streams.
- **Git** (optional, 2.0 or newer, GPL-2.0). Clones ScanCore's update repository, where ScanCore's UpdateMethod is git.
- **PHP** (required, 8.0 or newer, PHP-3.01). Runs the application.
- **PHP Zlib** (optional, PHP-3.01). Lets ScanCore read compressed container members & png chunks, which it reports as incomplete without it.
- **ScanCore** (optional, 2.0 or newer, GPL-3.0). Scans every uploaded file. Written & distributed by this project.

## Approval

`DEPENDENCY_APPROVALS.txt` beside this file is the official record for every
dependency listed above. It holds the approval status, the approval & expiry dates,
& the audit answers. That record is authoritative & this README is a summary of it.

The procedure behind it is `Documentation/ABOUT_DEPENDENCIES.txt`.
