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

use Throwable;

interface ControllerHelperInterface
{

    public function renderTemplate(string $templatePath, array $arguments = array()): string;

    public function persistEntity($entity): void;

    public function flushORM(): void;

    public function handleException(Throwable $exception): void;

    public function addFlashMessage(string $message, string $type = "default"): void;

}
