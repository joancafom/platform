<?php

namespace Oro\Bundle\ScopeBundle\Entity\Hydrator;

use Doctrine\ORM\Internal\Hydration\AbstractHydrator;

/**
 * Custom hydrator that increases performance when getting the matching entity.
 * Requires matchedScopeId to be selected
 */
abstract class AbstractMatchingEntityHydrator extends AbstractHydrator
{
    /**
     * @return string
     */
    abstract protected function getRootEntityAlias(): string;

    /**
     * @return string
     */
    abstract protected function getEntityClass(): string;

    /**
     * @param mixed $entityId
     * @return bool
     */
    abstract protected function hasScopes($entityId): bool;

    /**
     * @return array
     */
    protected function hydrateAllData()
    {
        $rows = $this->_stmt->fetchAll(\PDO::FETCH_ASSOC);
        foreach ($rows as $key => $row) {
            $id = [key($this->_rsm->aliasMap) => ''];
            $nonemptyComponents = [];
            $rows[$key] = $this->gatherRowData($row, $id, $nonemptyComponents);
        }

        usort($rows, function ($a, $b) {
            if ($a['scalars']['matchedScopeId'] === null && $b['scalars']['matchedScopeId'] === null) {
                return 0;
            }
            if ($a['scalars']['matchedScopeId'] === null) {
                return 1;
            }
            if ($b['scalars']['matchedScopeId'] === null) {
                return -1;
            }

            return 0;
        });

        $alias = $this->getRootEntityAlias();
        foreach ($rows as $row) {
            if ($row['scalars']['matchedScopeId'] || !$this->hasScopes($row['data'][$alias]['id'])) {
                return [$this->_uow->createEntity($this->getEntityClass(), $row['data'][$alias], $this->_hints)];
            }
        }

        return [];
    }
}
