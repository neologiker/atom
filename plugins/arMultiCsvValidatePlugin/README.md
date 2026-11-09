# arMultiCsvValidatePlugin

Adds a web UI page to validate several CSV files of the **same object type** in
one submission. Core AtoM files are not modified.

- Page: `/object/multiValidateCsv` (administrators only)
- Runs one `arMultiValidateCsvJob` using a single `CsvImportValidator`, so
  cross-file checks work. Results go to the job log, and a zipped verbose report
  (`validation_results.txt`) is attached to the job.
- Uploads are limited by PHP `max_file_uploads`, `upload_max_filesize` and
  `post_max_size`.

## Installation

1. Enable the plugin (Admin > Plugins, or add it to the `sf_plugins` setting).
2. Clear the cache: `php symfony cc`.
3. Add the Import menu link: `php symfony csv:multi-validate-menu`
   (`--remove` deletes it). Then `php symfony cc` if the menu is cached.
4. Restart the job workers (`jobs:worker`) so the new `arMultiValidateCsvJob`
   ability is registered. The plugin's `config/gearman.yml` adds it under the
   `multi_csv_validation` worker type.
