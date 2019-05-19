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

namespace Addiks\SymfonyGenerics\Arguments;

use Addiks\SymfonyGenerics\Arguments\ArgumentContextInterface;
use Webmozart\Assert\Assert;

final class ArgumentContext implements ArgumentContextInterface
{

    /**
     * @var array<string, mixed>
     */
    private $variables = array();

    public function set(string $key, $value): void
    {
        $this->variables[$key] = $value;
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->variables);
    }

    public function get(string $key)
    {
        Assert::keyExists($this->variables, $key);

        return $this->variables[$key];
    }

    public function clear(): void
    {
        $this->variables = array();
    }

}
