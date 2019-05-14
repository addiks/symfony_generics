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
use Symfony\Component\DependencyInjection\Definition;
use Webmozart\Assert\Assert;

final class ControllerErrorHandlingPass implements CompilerPassInterface
{

    public function process(ContainerBuilder $container): void
    {
        /** @var array<string, array> $taggedErrorHandlers */
        $taggedErrorHandlers = $container->findTaggedServiceIds("symfony_generics.error_handler");

        foreach ($taggedErrorHandlers as $errorHandlerServiceId => $errorHandlerTags) {
            foreach ($errorHandlerTags as $errorHandlerTag) {
                Assert::keyExists($errorHandlerTag, 'key');

                /** @var string $errorHandlerKey */
                $errorHandlerKey = $errorHandlerTag['key'];

                unset($errorHandlerTag['key']);

                /** @var array<string, array> $taggedControllers */
                $taggedControllers = $container->findTaggedServiceIds(sprintf(
                    "symfony_generics.error_handler.%s",
                    $errorHandlerKey
                ));

                /** @var Definition $errorHandlerDefinition */
                $errorHandlerDefinition = $container->getDefinition($errorHandlerServiceId);

                foreach ($taggedControllers as $controllerServiceId => $tags) {
                    /** @var array $tags */

                    foreach ($tags as $tagData) {
                        /** @var array $tagData */
                        $tagData = array_merge([
                            'decorates' => $controllerServiceId
                        ], $errorHandlerTag, $tagData);

                        $errorHandlerDefinition->addTag("symfony_generics.decorates", $tagData);
                    }
                }
            }
        }
    }

}
