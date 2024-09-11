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

namespace Addiks\SymfonyGenerics;

use Addiks\SymfonyGenerics\DependencyInjection\Compiler\RoutingCompilerPass;
use Addiks\SymfonyGenerics\DependencyInjection\Compiler\ObjectValidationCompilerPass;
use Addiks\SymfonyGenerics\DependencyInjection\Compiler\DecoratorTemplateCompilerPass;
use Addiks\SymfonyGenerics\DependencyInjection\Compiler\ControllerErrorHandlingPass;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;

final class SymfonyGenericsBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(
            new RoutingCompilerPass(),
            PassConfig::TYPE_BEFORE_OPTIMIZATION,
            200
        );
        $container->addCompilerPass(new ObjectValidationCompilerPass());
        $container->addCompilerPass(new DecoratorTemplateCompilerPass());
        $container->addCompilerPass(new ControllerErrorHandlingPass());
    }
    
    public function getContainerExtension(): ?ExtensionInterface
    {
        return new class() implements ExtensionInterface {
            public function load(array $configs, ContainerBuilder $container)
            {
                $loader = new XmlFileLoader($container, new FileLocator(\dirname(__DIR__)));
                $loader->load('services.xml');
            }

            public function getNamespace()
            {
                return 'http://addiks.de/schema/dic/symfony_generics';
            }

            public function getXsdValidationBasePath()
            {
                return false;
            }

            public function getAlias()
            {
                return 'symfony_generics';
            }
        };
    }
    
}
