<?php

declare(strict_types=1);

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\DoctrineORMAdminBundle\Filter;

use Doctrine\ORM\QueryBuilder;
use Sonata\AdminBundle\Datagrid\ProxyQueryInterface;
use Sonata\AdminBundle\Filter\Filter as BaseFilter;

abstract class Filter extends BaseFilter
{
    /**
     * @var bool
     */
    protected $active = false;

    public function apply($query, $value)
    {
        $this->value = $value;
        if (\is_array($value) && \array_key_exists('value', $value)) {
            [$alias, $field] = $this->association($query, $value);

            $this->filter($query, $alias, $field, $value);
        }
    }

    public function isActive()
    {
        return $this->active;
    }

    /**
     * @param mixed[] $value
     *
     * @return string[]
     */
    protected function association(ProxyQueryInterface $queryBuilder, array $value)
    {
        $alias = $queryBuilder->entityJoin($this->getParentAssociationMappings());

        return [$alias, $this->getFieldName()];
    }

    /**
     * @param ProxyQueryInterface|QueryBuilder $queryBuilder
     * @param mixed                            $parameter
     */
    protected function applyWhere(ProxyQueryInterface $queryBuilder, $parameter)
    {
        if (self::CONDITION_OR === $this->getCondition()) {
            $queryBuilder->orWhere($parameter);
        } else {
            $queryBuilder->andWhere($parameter);
        }

        // filter is active since it's added to the queryBuilder
        $this->active = true;
    }

    /**
     * @return string
     */
    protected function getNewParameterName(ProxyQueryInterface $queryBuilder)
    {
        // dots are not accepted in a DQL identifier so replace them
        // by underscores.
        return str_replace('.', '_', $this->getName()).'_'.$queryBuilder->getUniqueParameterId();
    }
}
