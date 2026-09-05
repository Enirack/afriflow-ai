<?php

namespace App\Doctrine;

use App\Entity\CompanyOwnedInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query\Filter\SQLFilter;

final class CompanyFilter extends SQLFilter
{
    public function addFilterConstraint(ClassMetadata $targetEntity, $targetTableAlias): string
    {
        if (!$targetEntity->getReflectionClass()->implementsInterface(CompanyOwnedInterface::class)) {
            return '';
        }

        if (!$this->hasParameter('company_id')) {
            return '';
        }

        return sprintf(
            '%s.company_id = %s',
            $targetTableAlias,
            $this->getParameter('company_id')
        );
    }
}
