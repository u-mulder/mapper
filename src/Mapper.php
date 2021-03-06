<?php

namespace Tarantool\Mapper;

use Tarantool\Client\Client as TarantoolClient;
use Exception;

class Mapper
{
    private $client;
    private $plugins = [];
    private $schema;
    private $bootstrap;

    public function __construct(TarantoolClient $client)
    {
        $this->client = $client;
    }

    public function getPlugin($mixed)
    {
        if (!is_subclass_of($mixed, Plugin::class)) {
            throw new Exception("Plugin should extend " . Plugin::class . " class");
        }

        $plugin = is_object($mixed) ? $mixed : new $mixed($this);
        $class = get_class($plugin);

        if ($plugin == $mixed && array_key_exists($class, $this->plugins)) {
            // overwrite plugin instance
            throw new Exception($class.' is registered');
        }

        if (!array_key_exists($class, $this->plugins)) {
            $this->plugins[$class] = $plugin;
        }

        return $this->plugins[$class];
    }

    public function create(string $space, $data)
    {
        return $this->getRepository($space)->create($data)->save();
    }

    public function findOne(string $space, $params = [])
    {
        return $this->getRepository($space)->findOne($params);
    }

    public function findOrCreate(string $space, $params = [])
    {
        return $this->getRepository($space)->findOrCreate($params)->save();
    }

    public function findOrFail(string $space, $params = [])
    {
        return $this->getRepository($space)->findOrFail($params);
    }

    public function find(string $space, $params = [])
    {
        return $this->getRepository($space)->find($params);
    }

    public function findRepository(Entity $instance)
    {
        foreach ($this->getSchema()->getSpaces() as $space) {
            if ($space->getRepository()->knows($instance)) {
                return $space->getRepository();
            }
        }

        throw new Exception("No Repository for given Entity");
    }

    public function getBootstrap()
    {
        return $this->bootstrap ?: $this->bootstrap = new Bootstrap($this);
    }

    public function getClient()
    {
        return $this->client;
    }

    public function getMeta()
    {
        return [
            'schema' => $this->getSchema()->getMeta(),
        ];
    }

    public function hasPlugin($class)
    {
        return array_key_exists($class, $this->plugins);
    }

    public function getPlugins()
    {
        return array_values($this->plugins);
    }

    public function getRepository($space)
    {
        return $this->getSchema()->getSpace($space)->getRepository();
    }

    public function getRepositories()
    {
        $repositories = [];
        foreach ($this->getSchema()->getSpaces() as $space) {
            if ($space->repositoryExists()) {
                $repositories[] = $space->getRepository();
            }
        }
        return $repositories;
    }

    public function getSchema()
    {
        return $this->schema ?: $this->schema = new Schema($this);
    }

    public function remove($space, $params = [])
    {
        if ($space instanceof Entity) {
            $this->findRepository($space)->removeEntity($space);
        } else {
            $this->getRepository($space)->remove($params);
        }
    }

    public function save(Entity $instance)
    {
        $this->findRepository($instance)->save($instance);
    }

    public function setMeta($meta)
    {
        if ($this->schema) {
            $this->schema->setMeta($meta['schema']);
        } else {
            $this->schema = new Schema($this, $meta['schema']);
        }
    }
}
