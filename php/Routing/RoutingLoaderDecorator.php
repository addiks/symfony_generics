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

namespace Addiks\SymfonyGenerics\Routing;

use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\Config\Loader\LoaderResolverInterface;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\Route;

class RoutingLoaderDecorator implements LoaderInterface
{
    /** @var array<int, array<string, mixed>> */
    private array $routes = array();

    public function __construct(
        private LoaderInterface $innerLoader
    ) {}
    
    public function addRoute(
        string $controllerServiceId,
        string $path,
        string|array $methods = [],
        string|null $id = null,
        int $priority = 0,
        string|null $host = '',
        string|null $condition = '',
        array $defaults = [],
        array $requirements = [],
        array $options = [],
        string|array $schemes = [],
    ) {
        $this->routes[] = [
            'path' => $path,
            'methods' => $methods,
            'controllerServiceId' => $controllerServiceId,
            'id' => $id ?? $controllerServiceId,
            'priority' => $priority,
            'host' => $host,
            'condition' => $condition,
            'defaults' => $defaults,
            'requirements' => $requirements,
            'options' => $options,
            'schemes' => $schemes,
        ];
    }
    
    public function addRouteFromServiceTag(
        string $controllerServiceId,
        array $tagData
    ): void {
        $this->addRoute(
            $controllerServiceId,
            $tagData['path'],
            $tagData['methods'] ?? [],
            $tagData['id'] ?? null,
            $tagData['priority'] ?? 0,
            $tagData['host'] ?? '',
            $tagData['condition'] ?? '',
            $tagData['defaults'] ?? [],
            $tagData['requirements'] ?? [],
            $tagData['options'] ?? [],
            $tagData['schemes'] ?? [],
        );
    }
    
    public function load(mixed $resource, string $type = null): mixed
    {
        /** @var mixed $result */
        $result = $this->innerLoader->load($resource, $type);
        
        if ($result instanceof RouteCollection) {
            foreach ($this->routes as $route) {
                $result->add(
                    $route['id'],
                    new Route(
                        $route['path'], 
                        array_merge(
                            $route['defaults'],
                            ['_controller' => $route['controllerServiceId']],
                        ),
                        $route['requirements'], 
                        $route['options'], 
                        $route['host'], 
                        $route['schemes'], 
                        $route['methods'], 
                        $route['condition']
                    ),
                    $route['priority']
                );
            }
        }
        
        return $result;
    }

    public function supports(mixed $resource, string $type = null): bool
    {
        return $this->innerLoader->supports($resource, $type);
    }

    public function getResolver(): LoaderResolverInterface
    {
        return $this->innerLoader->getResolver();
    }

    public function setResolver(LoaderResolverInterface $resolver): void
    {
        $this->innerLoader->setResolver($resolver);
    }

}
