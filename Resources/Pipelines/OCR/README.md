# The Optical Character Recognition pipeline

The Optical Character Recognition pipeline is an operation. It takes a selection of files at once & decides what to do with each one.

This file is written by `Documentation/Build/hrconvert2-approvals.py --readme`.
It is rewritten from the pipeline's own declarations & from the dependency manifest,
so it cannot drift from either. Do not edit it by hand.

## What it declares

- **Version** v5.0.0
- **Kind** operation
- **Family** OCR
- **Entry point** ocrFiles
- **Subsystem** OCR
- **Available to** every surface
- **Offered where** Tesseract is installed

## Formats

**Accepts** pdf, txt, doc, docx, rtf, xls, xlsx, ods, odt, abw, jpg, jpeg, bmp, webp, png, gif

**Produces** txt, doc, docx, rtf, odt, pdf

## Dependencies

- **Bubblewrap** (required, 0.4 or newer, LGPL-2.0). Isolates every conversion from the host & the network.
- **Coreutils** (required, GPL-3.0). Removes & copies files, sets their owner & mode, bounds how long & how hard a command runs, & names the machine.
- **ImageMagick** (optional, 7.1 or newer, ImageMagick). Converts raster images & splits pages for OCR.
- **LibreOffice** (optional, 7.0 or newer, MPL-2.0). Converts documents, spreadsheets & presentations.
- **PHP Zip** (required, PHP-3.01). Opens OOXML documents so external references can be stripped before LibreOffice sees them. Without it the sandbox is the only protection.
- **Poppler Utils** (optional, 20.09 or newer, GPL-2.0). Extracts text & converts XPS documents.
- **Procps** (optional, GPL-2.0-or-later). Says whether a worker is still running & counts the web server's workers. Without it a stream is taken to have finished as it starts.
- **Tesseract** (optional, 4.0 or newer, Apache-2.0). Reads text out of images & scanned documents.

## Approval

`DEPENDENCY_APPROVALS.txt` beside this file is the official record for every
dependency listed above. It holds the approval status, the approval & expiry dates,
& the audit answers. That record is authoritative & this README is a summary of it.

The procedure behind it is `Documentation/ABOUT_DEPENDENCIES.txt`.
