<?php

/*
 * This file is part of the Access to Memory (AtoM) software.
 *
 * Access to Memory (AtoM) is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Access to Memory (AtoM) is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Access to Memory (AtoM).  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * Job worker validating several CSV files of one object type in a single run.
 */
class arMultiValidateCsvJob extends arValidateCsvJob
{
    public function runJob($parameters)
    {
        if (empty($parameters['files']) || !is_array($parameters['files'])) {
            $this->job->setStatusError($this->i18n->__('Validation file names not set.'));
            $this->job->save();

            return false;
        }

        $names = array_column($parameters['files'], 'name');
        $this->info($this->i18n->__('Validating files: %1%.', ['%1%' => implode(', ', $names)]));

        try {
            $this->info($this->runMultiCsvValidator($this->context, $parameters));
        } finally {
            foreach ($parameters['files'] as $file) {
                if (!empty($file['tmp_name']) && is_file($file['tmp_name'])) {
                    unlink($file['tmp_name']);
                }
            }
        }

        $this->zipFileDownload = new arZipFileDownload(
            $this->job->id,
            $this->downloadFileExtension,
            $this->logger,
        );
        $tempPath = $this->zipFileDownload->createJobTempDir();

        file_put_contents($tempPath.DIRECTORY_SEPARATOR.'validation_results.txt', $this->verboseReportContents);

        $this->info($this->i18n->__(
            'Creating ZIP file %1.',
            ['%1' => $this->zipFileDownload->getDownloadFilePath()]
        ));

        $errors = $this->zipFileDownload->createZipForDownload($tempPath, $this->user->isAdministrator());

        if (!empty($errors)) {
            $this->error(
                $this->i18n->__('Failed to create ZIP file.')
                .' : '.implode(' : ', $errors)
            );

            return false;
        }

        $this->job->downloadPath = $this->zipFileDownload->getDownloadRelativeFilePath();
        $this->job->setStatusCompleted();
        $this->job->save();

        return true;
    }

    public function runMultiCsvValidator($context, array $options = [])
    {
        $validatorOptions = self::mapImportTypeToClassName(self::setOptions($options));

        // The validator expects display name => path.
        $filenames = [];
        foreach ($options['files'] as $file) {
            $filenames[$file['name']] = $file['tmp_name'];
        }

        $validator = new CsvImportValidator($context, null, $validatorOptions);
        $validator->setFilenames($filenames);
        $results = $validator->validate();

        $this->verboseReportContents = $results->renderResultsAsText(true);

        return $results->renderResultsAsText(false);
    }
}
