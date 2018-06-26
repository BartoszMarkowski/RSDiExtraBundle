<?php
namespace RS\DiExtraBundle\Converter;

use Doctrine\Common\Annotations\AnnotationReader;
use RS\DiExtraBundle\Converter\Parser\ClassParser;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\Definition;

class DefinitionConverter
{
    /** @var AnnotationReader */
    protected $reader;

    /** @var string */
    protected $environment;

    public function inject(AnnotationReader $reader, $environment)
    {
        $this->reader = $reader;
        $this->environment = $environment;
    }

    /**
     * @param $classFile
     * @return Definition[]
     */
    public function convert($classFile)
    {
        $definitions = array();
        $classMeta = $this->parseClassFile($classFile);

        while($classMeta) {
            if($definition = $this->convertDefinition($classMeta)){
                $definitions[$classMeta->id] = $definition;
            }
            $classMeta = $classMeta->nextClassMeta;
        }

        return $definitions;
    }

    protected function convertDefinition(ClassMeta $classMeta)
    {
        if($classMeta->id === null){
            return null;
        }

        if(!$this->isEnabledInEnvironment($classMeta->environments)){
            return null;
        }

        if($classMeta->parent){
            $definition = new ChildDefinition($classMeta->parent);
        }
        else {
            $definition = new Definition($classMeta->class);
            $definition
                ->setAutoconfigured($classMeta->autoconfigured);
        }

        return $definition
            ->setPrivate($classMeta->private)
            ->setPublic($classMeta->public)
            ->setShared($classMeta->shared)
            ->setAbstract($classMeta->abstract)
            ->setDeprecated($classMeta->deprecated)
            ->setSynthetic($classMeta->synthetic)
            ->setTags($classMeta->tags)
            ->setFactory($classMeta->factoryMethod)
            ->setMethodCalls($classMeta->methodCalls)
            ->setAutowired($classMeta->autowire)
            ->setArguments($classMeta->arguments)
            ->setLazy($classMeta->lazy)
            ->setProperties($classMeta->properties)
            ->setDecoratedService($classMeta->decorates, $classMeta->decorationInnerName, $classMeta->decorationPriority)
            ;
    }

    protected function getReflectionClass($classFile)
    {
        $className = $this->getClassName($classFile);
        require_once $classFile;
        return new \ReflectionClass($className);
    }

    protected function getClassName($classFile)
    {
        $src = file_get_contents($classFile);
        if (!preg_match('/\bnamespace\s+([^;\{\s]+)\s*?[;\{]/s', $src, $match)) {
            throw new \RuntimeException(sprintf('Namespace could not be determined for file "%s".', $classFile));
        }
        $namespace = $match[1];

        if (!preg_match('/\b(?:class|trait)\s+([^\s]+)\s+(?:extends|implements|{)/is', $src, $match)) {
            throw new \RuntimeException(sprintf('Could not extract class name from file "%s".', $classFile));
        }

        return $namespace.'\\'.$match[1];
    }

    protected function isEnabledInEnvironment(array $environments)
    {
        return in_array($this->environment, $environments);
    }

    /**
     * @param $classFile
     * @return ClassMeta
     */
    protected function parseClassFile($classFile)
    {
        $classMeta = new ClassMeta();
        $reflectionClass = $this->getReflectionClass($classFile);
        $classParser = new ClassParser($this->reader, $reflectionClass);
        $classParser->parse($classMeta);
        return $classMeta;
    }
}