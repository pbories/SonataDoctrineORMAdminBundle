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

use Sonata\AdminBundle\Datagrid\ProxyQueryInterface;
use Sonata\AdminBundle\Form\Type\Filter\DefaultType;
use Sonata\Form\Type\BooleanType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;

/**
 * @final since sonata-project/doctrine-orm-admin-bundle 3.24
 */
class BooleanFilter extends Filter
{
    public function filter(ProxyQueryInterface $queryBuilder, $alias, $field, $value)
    {
        if (!$value || !\is_array($value) || !\array_key_exists('type', $value) || !\array_key_exists('value', $value)) {
            return;
        }

        if (\is_array($value['value'])) {
            $values = [];
            foreach ($value['value'] as $v) {
                if (!\in_array($v, [BooleanType::TYPE_NO, BooleanType::TYPE_YES], true)) {
                    continue;
                }

                $values[] = (BooleanType::TYPE_YES === $v) ? 1 : 0;
            }

            if (0 === \count($values)) {
                return;
            }

            $this->applyWhere($queryBuilder, $queryBuilder->expr()->in(sprintf('%s.%s', $alias, $field), $values));
        } else {
            if (!\in_array($value['value'], [BooleanType::TYPE_NO, BooleanType::TYPE_YES], true)) {
                return;
            }

            $parameterName = $this->getNewParameterName($queryBuilder);
            $this->applyWhere($queryBuilder, sprintf('%s.%s = :%s', $alias, $field, $parameterName));
            $queryBuilder->setParameter($parameterName, (BooleanType::TYPE_YES === $value['value']) ? 1 : 0);
        }
    }

    public function getDefaultOptions()
    {
        return [
            'field_type' => BooleanType::class,
            'operator_type' => HiddenType::class,
            'operator_options' => [],
        ];
    }

    public function getRenderSettings()
    {
        return [DefaultType::class, [
            'field_type' => $this->getFieldType(),
            'field_options' => $this->getFieldOptions(),
            'operator_type' => $this->getOption('operator_type'),
            'operator_options' => $this->getOption('operator_options'),
            'label' => $this->getLabel(),
        ]];
    }
}
