<?php

/*
 * NOTICE OF LICENSE
 *
 * Part of the Rinvex Cacheable Package.
 *
 * This source file is subject to The MIT License (MIT)
 * that is bundled with this package in the LICENSE file.
 *
 * Package: Rinvex Cacheable Package
 * License: The MIT License (MIT)
 * Link:    https://rinvex.com
 */

declare(strict_types=1);

namespace Rinvex\Cacheable;

use Illuminate\Database\Eloquent\Builder;

/**
 * @mixin \Illuminate\Database\Query\Builder
 */
class EloquentBuilder extends Builder
{
    /**
     * Execute the query as a "select" statement.
     *
     * @param array $columns
     *
     * @return \Illuminate\Database\Eloquent\Collection|static[]
     */
    public function get($columns = ['*'])
    {
        $builder = $this->applyScopes();

        $closure = function () use ($builder, $columns) {
            $models = $builder->getModels($columns);

            // if we actually found models we will also eager load any relationships that
            // have been specified as needing to be eager loaded, which will solve the
            // n+1 query issue for the developers to avoid running a lot of queries.
            if (count($models) > 0) {
                $models = $builder->eagerLoadRelations($models);
            }

            return $builder->getModel()->newcollection($models);
        };

        // Check if cache is enabled
        if ($builder->getModel()->getCacheLifetime()) {
            return $builder->getModel()->cacheQuery($builder, $columns, $closure);
        }

        // Cache disabled, just execute query & return result
        $result = call_user_func($closure);

        // We're done, let's clean up!
        $builder->getModel()->resetCacheConfig();

        return $result;
    }
}
