<?php

namespace Pawon\Database;

use Illuminate\Database\ConnectionResolverInterface as Resolver;
use Illuminate\Database\Schema\Builder;

abstract class BaseMigration
{
    /**
     *
     */
    public function __construct(Resolver $resolver)
    {
        $this->resolver = $resolver;
    }

    /**
     * Get the migration connection name.
     *
     * @return string
     */
    public function getConnection()
    {
        return $this->connection;
    }

    /**
     *
     */
    protected function getSchemaBuilder()
    {
        return $this->resolver->connection()->getSchemaBuilder();
    }

    /**
     *
     */
    public function setSchemaBuilder(Resolver $resolver)
    {
        $this->resolver = $resolver;
    }
}
