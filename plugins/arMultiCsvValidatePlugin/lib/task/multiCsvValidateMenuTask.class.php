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
 * Add (or remove) the "Validate multiple CSVs" entry in the Import menu.
 */
class multiCsvValidateMenuTask extends arBaseTask
{
    public const MENU_NAME = 'multiValidateCsv';

    protected function configure()
    {
        $this->addOptions([
            new sfCommandOption('application', null, sfCommandOption::PARAMETER_OPTIONAL, 'The application name', true),
            new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'The environment', 'cli'),
            new sfCommandOption('connection', null, sfCommandOption::PARAMETER_REQUIRED, 'The connection name', 'propel'),
            new sfCommandOption('remove', null, sfCommandOption::PARAMETER_NONE, 'Remove the menu entry instead of adding it'),
        ]);

        $this->namespace = 'csv';
        $this->name = 'multi-validate-menu';
        $this->briefDescription = 'Add or remove the multiple CSV validation menu link.';
        $this->detailedDescription = <<<'EOF'
The [csv:multi-validate-menu|INFO] task adds a "Validate multiple CSVs" link
under the Import menu. It is safe to run more than once.

  php symfony csv:multi-validate-menu
  php symfony csv:multi-validate-menu --remove
EOF;
    }

    protected function execute($arguments = [], $options = [])
    {
        parent::execute($arguments, $options);

        $menu = QubitMenu::getByName(self::MENU_NAME);

        if ($options['remove']) {
            if (null === $menu) {
                $this->logSection('csv', 'Menu entry not found, nothing to remove.');

                return;
            }

            $menu->delete();
            $this->logSection('csv', 'Menu entry removed.');

            return;
        }

        if (null !== $menu) {
            $this->logSection('csv', 'Menu entry already exists.');

            return;
        }

        if (null === $parent = QubitMenu::getByName('import')) {
            throw new sfException('Import menu not found.');
        }

        $menu = new QubitMenu();
        $menu->parentId = $parent->id;
        $menu->name = self::MENU_NAME;
        $menu->path = '@multi_csv_validate';
        $menu->sourceCulture = 'en';
        $menu->label = 'Validate multiple CSVs';
        $menu->save();

        $this->logSection('csv', 'Menu entry added.');
    }
}
