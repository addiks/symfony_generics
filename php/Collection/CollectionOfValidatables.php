<?php
/**
 * Copyright (C) 2019 Gerrit Addiks.
 * This package (including this file) was released under the terms of the GPL-3.0.
 * You should have received a copy of the GNU General Public License along with this program.
 * If not, see <http://www.gnu.org/licenses/> or send me a mail so i can send you a copy.
 *
 * @license GPL-3.0
 *
 * @author Gerrit Addiks <gerrit@addiks.de>
 */

namespace Addiks\SymfonyGenerics\Collection;

use Addiks\SymfonyGenerics\SelfValidating;
use IteratorAggregate;
use Iterator;
use ArrayIterator;

/** @implements IteratorAggregate<SelfValidating> */
final class CollectionOfValidatables implements IteratorAggregate
{

    /** @var array<string, SelfValidating> */
    private array $validatables = array();

    public function __construct(array $validatables)
    {
        /** @var SelfValidating $validatable */
        foreach ($validatables as $serviceId => $validatable) {
            $this->add($serviceId, $validatable);
        }
    }

    public function add(string $serviceId, SelfValidating $validatable): void
    {
        $this->validatables[$serviceId] = $validatable;
    }

    public function validateAll(): void
    {
        /** @var SelfValidating $validatable */
        foreach ($this->validatables as $validatable) {
            $validatable->selfValidate();
        }
    }

    public function getIterator(): Iterator
    {
        return new ArrayIterator($this->validatables);
    }

}
