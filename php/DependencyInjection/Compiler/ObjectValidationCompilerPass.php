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

namespace Addiks\SymfonyGenerics\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Webmozart\Assert\Assert;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use ErrorException;
use Addiks\SymfonyGenerics\SelfValidating;
use Symfony\Component\ErrorHandler\Error\ClassNotFoundError;
use Throwable;
use Error;

final class ObjectValidationCompilerPass implements CompilerPassInterface
{

    private string $validatablesCollectionServiceId;

    public function __construct(
        string $validatablesCollectionServiceId = 'symfony_generics.validatables.collection'
    ) {
        $this->validatablesCollectionServiceId = $validatablesCollectionServiceId;
    }

    public function process(ContainerBuilder $container): void
    {
        /** @var Definition $collection */
        $collection = $container->getDefinition($this->validatablesCollectionServiceId);

        foreach ($container->getServiceIds() as $serviceId) {

            if ($container->hasDefinition($serviceId)) {
                /** @var Definition $definition */
                $definition = $container->getDefinition($serviceId);

                /** @var string|null $serviceClass */
                $serviceClass = $definition->getClass();

                try {
                    if (!empty($serviceClass) && is_subclass_of($serviceClass, SelfValidating::class)) {
                        $collection->addMethodCall('add', [$serviceId, new Reference($serviceId)]);
                    }

                } catch (Error $error) {
                    if (!preg_match('/^Class \'.*\' not found$/is', $error->getMessage())) {
                        throw $error;
                    }
                }
            }
        }
    }

}
