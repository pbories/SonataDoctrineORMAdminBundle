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
use Sonata\AdminBundle\Form\Type\Filter\ChoiceType;
use Sonata\AdminBundle\Form\Type\Operator\StringOperatorType;

/**
 * @final since sonata-project/doctrine-orm-admin-bundle 3.24
 */
class StringFilter extends Filter
{
    public const TRIM_NONE = 0;
    public const TRIM_LEFT = 1;
    public const TRIM_RIGHT = 2;
    public const TRIM_BOTH = self::TRIM_LEFT | self::TRIM_RIGHT;

    public const CHOICES = [
        StringOperatorType::TYPE_CONTAINS => 'LIKE',
        StringOperatorType::TYPE_STARTS_WITH => 'LIKE',
        StringOperatorType::TYPE_ENDS_WITH => 'LIKE',
        StringOperatorType::TYPE_NOT_CONTAINS => 'NOT LIKE',
        StringOperatorType::TYPE_EQUAL => '=',
        StringOperatorType::TYPE_NOT_EQUAL => '<>',
    ];

    /**
     * Filtering types do not make sense for searching by empty value.
     */
    private const MEANINGLESS_TYPES = [
        StringOperatorType::TYPE_CONTAINS,
        StringOperatorType::TYPE_STARTS_WITH,
        StringOperatorType::TYPE_ENDS_WITH,
        StringOperatorType::TYPE_NOT_CONTAINS,
    ];

    public function filter(ProxyQueryInterface $queryBuilder, $alias, $field, $value)
    {
        if (!\is_array($value) || !\array_key_exists('value', $value)) {
            return;
        }

        $value['value'] = $this->trim((string) ($value['value'] ?? ''));
        $type = $value['type'] ?? StringOperatorType::TYPE_CONTAINS;

        // ignore empty value if it doesn't make sense
        if ('' === $value['value'] &&
            (!$this->getOption('allow_empty') || \in_array($type, self::MEANINGLESS_TYPES, true))
        ) {
            return;
        }

        $operator = $this->getOperator((int) $type);

        // c.name > '1' => c.name OPERATOR :FIELDNAME
        $parameterName = $this->getNewParameterName($queryBuilder);

        $or = $queryBuilder->expr()->orX();

        if ($this->getOption('case_sensitive')) {
            $or->add(sprintf('%s.%s %s :%s', $alias, $field, $operator, $parameterName));
        } else {
            $or->add(sprintf('LOWER(%s.%s) %s :%s', $alias, $field, $operator, $parameterName));
        }

        if (StringOperatorType::TYPE_NOT_CONTAINS === $type || StringOperatorType::TYPE_NOT_EQUAL === $type) {
            $or->add($queryBuilder->expr()->isNull(sprintf('%s.%s', $alias, $field)));
        }

        $this->applyWhere($queryBuilder, $or);

        switch ($type) {
            case StringOperatorType::TYPE_EQUAL:
            case StringOperatorType::TYPE_NOT_EQUAL:
                $format = '%s';
                break;
            case StringOperatorType::TYPE_STARTS_WITH:
                $format = '%s%%';
                break;
            case StringOperatorType::TYPE_ENDS_WITH:
                $format = '%%%s';
                break;
            default:
                // NEXT_MAJOR: Remove this line, uncomment the following and remove the deprecation
                $format = $this->getOption('format');
                // $format = '%%%s%%';

                if ('%%%s%%' !== $format) {
                    @trigger_error(
                        'The "format" option is deprecated since sonata-project/doctrine-orm-admin-bundle 3.21 and will be removed in version 4.0.',
                        E_USER_DEPRECATED
                    );
                }
        }

        $queryBuilder->setParameter(
            $parameterName,
            sprintf(
                $format,
                $this->getOption('case_sensitive') ? $value['value'] : mb_strtolower($value['value'])
            )
        );
    }

    public function getDefaultOptions()
    {
        return [
            // NEXT_MAJOR: Remove the format option.
            'format' => '%%%s%%',
            'case_sensitive' => true,
            'trim' => self::TRIM_BOTH,
            'allow_empty' => false,
        ];
    }

    public function getRenderSettings()
    {
        return [ChoiceType::class, [
            'field_type' => $this->getFieldType(),
            'field_options' => $this->getFieldOptions(),
            'label' => $this->getLabel(),
        ]];
    }

    private function getOperator(int $type): string
    {
        return self::CHOICES[$type] ?? self::CHOICES[StringOperatorType::TYPE_CONTAINS];
    }

    private function trim(string $string): string
    {
        $trimMode = $this->getOption('trim');

        if ($trimMode & self::TRIM_LEFT) {
            $string = ltrim($string);
        }

        if ($trimMode & self::TRIM_RIGHT) {
            $string = rtrim($string);
        }

        return $string;
    }
}
