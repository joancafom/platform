<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Unit\ImportExport\Reader;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\BatchBundle\Entity\StepExecution;
use Oro\Bundle\ImportExportBundle\Context\ContextInterface;
use Oro\Bundle\ImportExportBundle\Context\ContextRegistry;
use Oro\Bundle\IntegrationBundle\Reader\EntityReaderById;
use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataProviderInterface;
use Oro\Component\TestUtils\ORM\OrmTestCase;

class EntityReaderByIdTest extends OrmTestCase
{
    private const TEST_ENTITY_ID = 11;

    /** @var ContextRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $contextRegistry;

    /** @var EntityManager|\PHPUnit\Framework\MockObject\MockObject */
    private $em;

    /** @var EntityReaderById */
    private $reader;

    protected function setUp(): void
    {
        $this->contextRegistry = $this->createMock(ContextRegistry::class);
        $managerRegistry = $this->createMock(ManagerRegistry::class);
        $ownershipMetadataProvider = $this->createMock(OwnershipMetadataProviderInterface::class);

        $this->em = $this->getTestEntityManager();
        $config = $this->em->getConfiguration();
        $config->setMetadataDriverImpl(new AnnotationDriver(
            new AnnotationReader(),
            'Oro\Bundle\IntegrationBundle\Entity'
        ));
        $config->setEntityNamespaces(['OroIntegrationBundle' => 'Oro\Bundle\IntegrationBundle\Entity']);

        $this->reader = new EntityReaderById(
            $this->contextRegistry,
            $managerRegistry,
            $ownershipMetadataProvider
        );
    }

    public function testInitialization()
    {
        $entityName = 'OroIntegrationBundle:Channel';
        $qb = $this->em->createQueryBuilder()
            ->select('e')
            ->from($entityName, 'e');

        $context = $this->createMock(ContextInterface::class);
        $context->expects($this->any())
            ->method('hasOption')
            ->willReturnMap([
                ['entityName', false],
                ['queryBuilder', true],
                [EntityReaderById::ID_FILTER, true]
            ]);
        $context->expects($this->any())
            ->method('getOption')
            ->willReturnMap([
                ['queryBuilder', null, $qb],
                [EntityReaderById::ID_FILTER, null, self::TEST_ENTITY_ID]
            ]);

        $stepExecution = $this->createMock(StepExecution::class);
        $this->contextRegistry->expects($this->any())
            ->method('getByStepExecution')
            ->with($stepExecution)
            ->willReturn($context);

        $this->reader->setStepExecution($stepExecution);

        $this->assertSame('SELECT e FROM OroIntegrationBundle:Channel e WHERE o.id = :id', $qb->getDQL());
        $this->assertSame(self::TEST_ENTITY_ID, $qb->getParameter('id')->getValue());
    }
}
