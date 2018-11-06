<?php
/**
 * Copyright (C) 2018 Gerrit Addiks.
 * This package (including this file) was released under the terms of the GPL-3.0.
 * You should have received a copy of the GNU General Public License along with this program.
 * If not, see <http://www.gnu.org/licenses/> or send me a mail so i can send you a copy.
 *
 * @license GPL-3.0
 *
 * @author Gerrit Addiks <gerrit@addiks.de>
 */

namespace Addiks\SymfonyGenerics\Controllers;

use ErrorException;

trait ApplyDataTemplateTrait
{

    private function applyDataTemplate(array $data, array $dataTemplate): array
    {
        /** @var array $result */
        $result = array();

        foreach ($dataTemplate as $key => $templateEntry) {
            /** @var string|array $templateEntry */

            /** @var mixed $entryResult */
            $entryResult = null;

            if (is_string($templateEntry)) {
                $entryResult = $this->extractValueFromDataArray($data, explode(".", $templateEntry));

            } elseif (is_array($templateEntry)) {
                $entryResult = $this->applyDataTemplate($data, $templateEntry);

            } else {
                throw new ErrorException("Invalid entry for data-template, must be string or array!");
            }

            $result[$key] = $entryResult;
        }

        return $result;
    }

    /**
     * @return mixed
     */
    private function extractValueFromDataArray(array $data, array $path)
    {
        /** @var string $key */
        $key = array_shift($path);

        /** @var mixed $value */
        $value = null;

        if (isset($data[$key])) {
            $value = $data[$key];

            if (!empty($path)) {
                $value = $this->extractValueFromDataArray($value, $path);
            }
        }

        return $value;
    }

}
