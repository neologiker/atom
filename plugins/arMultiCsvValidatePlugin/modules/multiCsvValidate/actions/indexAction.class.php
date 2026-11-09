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

class multiCsvValidateIndexAction extends sfAction
{
    public const OBJECT_TYPES = [
        'informationObject',
        'accession',
        'authorityRecord',
        'authorityRecordRelationship',
        'event',
        'repository',
    ];

    public function execute($request)
    {
        $this->form = new sfForm();
        $this->form->getValidatorSchema()->setOption('allow_extra_fields', true);

        $this->title = $this->context->i18n->__('Validate multiple CSV files');
        $this->response->setTitle("{$this->title} - {$this->response->getTitle()}");
        $this->maxFileUploads = (int) ini_get('max_file_uploads');

        if (!$request->isMethod('post')) {
            return sfView::SUCCESS;
        }

        $this->form->bind($request->getPostParameters());
        if (!$this->form->isValid()) {
            return sfView::SUCCESS;
        }

        $this->startValidation($request);

        return sfView::SUCCESS;
    }

    protected function startValidation($request)
    {
        $route = ['module' => 'multiCsvValidate', 'action' => 'index'];

        $objectType = $request->getParameter('objectType');
        if (!in_array($objectType, self::OBJECT_TYPES, true)) {
            $this->failWith($this->context->i18n->__('Invalid object type.'), $route);
        }

        $uploads = array_values(array_filter(
            (array) $request->getFiles('files'),
            fn ($file) => is_array($file) && UPLOAD_ERR_NO_FILE !== $file['error']
        ));

        if (0 === count($uploads)) {
            $this->failWith($this->context->i18n->__('Select at least one CSV file.'), $route);
        }

        // Validate everything before moving anything out of PHP's temp dir.
        $names = [];
        foreach ($uploads as $upload) {
            if (UPLOAD_ERR_OK !== $upload['error']) {
                $this->failWith($this->context->i18n->__('Upload failed for file %1%.', ['%1%' => $upload['name']]), $route);
            }

            if ('csv' !== strtolower(pathinfo($upload['name'], PATHINFO_EXTENSION))) {
                $this->failWith($this->context->i18n->__('%1% is not a CSV file.', ['%1%' => $upload['name']]), $route);
            }

            if (in_array($upload['name'], $names, true)) {
                $this->failWith($this->context->i18n->__('Duplicate file name: %1%.', ['%1%' => $upload['name']]), $route);
            }

            $names[] = $upload['name'];
        }

        $files = [];
        foreach ($uploads as $upload) {
            try {
                $files[] = Qubit::moveUploadFile($upload);
            } catch (sfException $e) {
                $this->failWith($e->getMessage(), $route);
            }
        }

        try {
            $job = QubitJob::runJob('arMultiValidateCsvJob', [
                'objectType' => $objectType,
                'importType' => 'csv',
                'files' => $files,
            ]);

            $this->getUser()->setFlash('notice', $this->context->i18n->__('CSV validation initiated for %1% files. Check %2%job %3%%4% to view the results of the validation.', [
                '%1%' => count($files),
                '%2%' => sprintf('<a class="alert-link" href="%s">', $this->context->routing->generate(null, ['module' => 'jobs', 'action' => 'report', 'id' => $job->id])),
                '%3%' => $job->id,
                '%4%' => '</a>',
            ]), ['persist' => false]);
        } catch (sfException $e) {
            $this->failWith($e->getMessage(), $route);
        }
    }

    protected function failWith($message, array $route)
    {
        $this->getUser()->setFlash('error', $message);
        $this->redirect($route);
    }
}
