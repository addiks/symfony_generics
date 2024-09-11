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

final class RoutingCompilerPass implements CompilerPassInterface
{
    public function __construct(
        private string $loaderId = 'symfony_generics.routing.loader.by_tag'
    ) {}

    public function process(ContainerBuilder $container): void
    {
        /** @var array<string, array<int, array<string, mixed>>> $taggedServices */
        $taggedServices = $container->findTaggedServiceIds('symfony_generics.route');
     
        /** @var Definition $loader */
        $loader = $container->getDefinition($this->loaderId);
        

        /** @var array<int, array<string, mixed>> $routeTags */
        foreach ($taggedServices as $controllerServiceId => $routeTags) {
            
            /** @var Definition $controllerService */
            $controllerService = $container->getDefinition($controllerServiceId);
            
            if (!$controllerService->hasTag('controller.service_arguments')) {
                $controllerService->addTag('controller.service_arguments');
            }
            
            foreach ($routeTags as $routeTag) {
                $loader->addMethodCall('addRouteFromServiceTag', [
                    $controllerServiceId, 
                    $routeTag
                ]);
            }
        }
    }

}
