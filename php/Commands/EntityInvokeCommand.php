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

namespace Addiks\SymfonyGenerics\Commands;

use Symfony\Component\Console\Command\Command;
use Addiks\SymfonyGenerics\SelfValidating;
use Psr\Container\ContainerInterface;
use Addiks\SymfonyGenerics\Services\ArgumentCompilerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Webmozart\Assert\Assert;
use ReflectionMethod;
use ReflectionParameter;
use Exception;
use Doctrine\Persistence\ObjectRepository;
use Addiks\SymfonyGenerics\SelfValidateTrait;
use Symfony\Component\Console\Input\InputOption;
use InvalidArgumentException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Input\InputArgument;

final class EntityInvokeCommand extends Command implements SelfValidating
{
    use SelfValidateTrait;

    private string $name;
    private string $description;
    
    /** @var class-string $entityClass */
    private string $entityClass;
    
    private string $method;

    /** @var array<string, mixed> */
    private array $arguments;
    
    /** @var array<int, string>|null */
    private array|null $argumentsOrder = null;
    
    private ReflectionMethod|null $methodReflection = null;
    
    private ReflectionMethod|null $repositoryMethod = null;

    public function __construct(
        private ContainerInterface $container,
        private ArgumentCompilerInterface $argumentCompiler,
        private ObjectRepository $repository,
        private EntityManagerInterface $entityManager,
        array $options
    ) {
        Assert::keyExists($options, 'name');
        Assert::keyExists($options, 'entity-class');
        Assert::classExists($options['entity-class']);
        Assert::true(is_a($repository->getClassName(), $options['entity-class'], true));

        /** @var array<string, mixed> $defaults */
        $defaults = array(
            'arguments' => [],
            'method' => '__invoke',
            'description' => '',
            'repository-method' => null,
        );

        $options = array_merge($defaults, $options);

        Assert::isArray($options['arguments']);

        $this->arguments = $options['arguments'];
        $this->name = (string) $options['name'];
        $this->description = (string) $options['description'];
        $this->entityClass = (string) $options['entity-class'];
        $this->method = (string) $options['method'];
        
        Assert::methodExists($this->entityClass, $this->method);
        
        if (!empty($options['repository-method'])) {
            $this->repositoryMethod = new ReflectionMethod($repository, (string) $options['repository-method']);
        }
        
        parent::__construct();
    }

    public function isSelfValid(?string &$reason = null): bool
    {
        try {
            Assert::methodExists($this->entityClass, $this->method);
            
            $this->buildArguments();
            
            return true;
            
        } catch (Exception $exception) {
            $reason = $exception->getMessage();
            
            return false;
        }
    }
    
    protected function buildInvalidMessage(string $reason): string
    {
        return sprintf(
            'Configuration of Entity-Invoke-Command "%s" is invalid: "%s"!',
            $this->name,
            $reason
        );
    }

    protected function configure(): void
    {
        $this->setName($this->name);
        
        if (!empty($this->description)) {
            $this->setDescription($this->description);
        }
        
        if (is_null($this->argumentsOrder)) {
            $this->buildArguments();
        }
        
        foreach ($this->arguments as $key => $config) {
            /** @var int $mode */
            $mode = InputArgument::REQUIRED;

            if ($config['optional'] ?? false) {
                $mode = InputArgument::OPTIONAL;
            }

            if ($config['is-array'] ?? false) {
                $mode = $mode + InputArgument::IS_ARRAY;
            }

            $this->addArgument($key, $mode, $config['description'] ?? '');
        }
        
        $this->addOption('id', null, InputOption::VALUE_OPTIONAL, 'ID of the entity.');
        $this->addOption('all', null, InputOption::VALUE_NONE, 'Execute on all entities.');
    }
    
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        /** @var string|null $id */
        $id = $input->getOption('id');
        
        /** @var bool $all */
        $all = (bool) $input->getOption('all');
        
        try {
            /** @var array<array-key, mixed> $callArguments */
            $callArguments = $this->buildCallArguments();
        
            /** @var array<int, object> $entities */
            $entities = array();
            
            if (is_string($id)) {
                $entity = $this->repository->find($id);
                
                Assert::object($entity, sprintf('Entity with id "%s" not found!', $id));
                
                $entities = [$entity];
                
            } elseif ($all) {
                $entities = $this->repository->findAll();
                
            } elseif (is_object($this->repositoryMethod)) {
                $entities = $this->repositoryMethod->invoke($this->repository);
                
            } else {
                throw new InvalidArgumentException('Must specify what entities to execute on (with --id or --all)');
            }
            
            foreach ($entities as $entity) {
                $this->methodReflection()->invokeArgs($entity, $callArguments);
            }
        
            $this->entityManager->flush();
        
            return 0;
        
        } catch (Exception $exception) {
            $output->write((string) $exception);
            
            return -1;
        }
    }
    
    private function buildCallArguments(): array
    {
        /** @var array<int, mixed> $arguments */
        $arguments = array();
        
        if (is_null($this->argumentsOrder)) {
            $this->buildArguments();
        }
        
        foreach ($this->argumentsOrder as $key) {
            $arguments[] = $this->arguments[$key];
        }
        
        /** @var array<array-key, mixed> $callArguments */
        $callArguments = $this->argumentCompiler->buildCallArguments($this->methodReflection(), $arguments);
        
        return $callArguments;
    }
    
    private function buildArguments(): void
    {
        $this->argumentsOrder = array();

        /** @var ReflectionParameter $reflectionParameter */
        foreach ($this->methodReflection()->getParameters() as $reflectionParameter) {
            
            /** @var string $key */
            $key = $reflectionParameter->name;
            
            $this->argumentsOrder[] = $key;
            
            if (isset($this->arguments[$key])) {
                continue;
            }
            
            $this->arguments[$key] = [
                'optional' => $reflectionParameter->isOptional(),
                'is-array' => false, # TODO
                'description' => null, # TODO
            ];
        }
    }
    
    private function methodReflection(): ReflectionMethod
    {
        if (is_null($this->methodReflection)) {
            $this->methodReflection = new ReflectionMethod($this->entityClass, $this->method);
        }
        
        return $this->methodReflection;
    }
    
}
