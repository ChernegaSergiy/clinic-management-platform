<?php

namespace App\Tests\Shared\Service {

    use App\Entity\Attachment;
    use App\Entity\AttachmentVersion;
    use App\Shared\Repository\AttachmentAclRepository;
    use App\Shared\Repository\AttachmentRepository;
    use App\Shared\Repository\AttachmentVersionRepository;
    use App\Shared\Service\AttachmentService;
    use Doctrine\ORM\EntityManagerInterface;
    use Doctrine\Persistence\ManagerRegistry;
    use PHPUnit\Framework\TestCase;

    class AttachmentServiceTest extends TestCase
    {
        private $mockRegistry;
        private $mockEntityManager;
        private $mockAttachmentRepository;
        private $mockAclRepository;
        private $mockVersionRepository;
        private AttachmentService $service;
        private string $uploadDir;

        protected function setUp() : void
        {
            $this->mockRegistry = $this->createMock(ManagerRegistry::class);
            $this->mockEntityManager = $this->createMock(EntityManagerInterface::class);
            $this->mockRegistry->method('getManager')->willReturn($this->mockEntityManager);

            $this->mockAttachmentRepository = $this->createMock(AttachmentRepository::class);
            $this->mockAclRepository = $this->createMock(AttachmentAclRepository::class);
            $this->mockVersionRepository = $this->createMock(AttachmentVersionRepository::class);

            $this->uploadDir = sys_get_temp_dir() . '/medcore_attachment_test_' . uniqid('', true);

            $this->service = new AttachmentService(
                $this->mockRegistry,
                $this->mockAttachmentRepository,
                $this->mockAclRepository,
                $this->mockVersionRepository,
                $this->uploadDir
            );
        }

        protected function tearDown() : void
        {
            if (is_dir($this->uploadDir)) {
                $this->removeDirectory($this->uploadDir);
            }
            parent::tearDown();
        }

        private function removeDirectory(string $dir) : void
        {
            $items = scandir($dir);
            foreach ($items as $item) {
                if ('.' === $item || '..' === $item) {
                    continue;
                }
                $path = $dir . '/' . $item;
                is_dir($path) ? $this->removeDirectory($path) : unlink($path);
            }
            rmdir($dir);
        }

        private function fakeUploadedFile(string $content = 'hello world') : array
        {
            $tmpFile = tempnam(sys_get_temp_dir(), 'upload_');
            file_put_contents($tmpFile, $content);

            return [
                'name' => 'report.pdf',
                'type' => 'application/pdf',
                'size' => strlen($content),
                'tmp_name' => $tmpFile,
                'error' => UPLOAD_ERR_OK,
            ];
        }

        public function testUploadAttachmentReturnsFalseWhenUploadErrored() : void
        {
            $fileData = $this->fakeUploadedFile();
            $fileData['error'] = UPLOAD_ERR_NO_FILE;

            $result = $this->service->uploadAttachment($fileData, 'medical_record', 1);

            $this->assertFalse($result);
            unlink($fileData['tmp_name']);
        }

        public function testUploadAttachmentPersistsAttachmentAndFirstVersion() : void
        {
            $fileData = $this->fakeUploadedFile();

            $persisted = [];
            $this->mockEntityManager->method('persist')->willReturnCallback(function ($entity) use (&$persisted) {
                $persisted[] = $entity;
                if ($entity instanceof Attachment) {
                    $ref = new \ReflectionProperty(Attachment::class, 'id');
                    $ref->setAccessible(true);
                    $ref->setValue($entity, 42);
                }
            });
            $this->mockEntityManager->expects($this->once())->method('flush');

            $result = $this->service->uploadAttachment($fileData, 'medical_record', 7, 3);

            $this->assertSame(42, $result);
            $this->assertCount(2, $persisted);
            $this->assertInstanceOf(Attachment::class, $persisted[0]);
            $this->assertInstanceOf(AttachmentVersion::class, $persisted[1]);
            $this->assertSame('medical_record', $persisted[0]->getEntityType());
            $this->assertSame(7, $persisted[0]->getEntityId());
            $this->assertSame(3, $persisted[0]->getCreatedBy());
            $this->assertSame(1, $persisted[1]->getVersionNumber());
            $this->assertSame($persisted[0], $persisted[1]->getAttachment());
        }

        public function testCreateNewVersionReturnsFalseWhenAttachmentNotFound() : void
        {
            $this->mockAttachmentRepository->method('findById')->with(99)->willReturn(null);

            $result = $this->service->createNewVersion(99, $this->fakeUploadedFile());

            $this->assertFalse($result);
        }

        public function testCreateNewVersionIncrementsVersionNumber() : void
        {
            $attachment = (new Attachment())->setEntityType('medical_record')->setEntityId(5);
            $this->mockAttachmentRepository->method('findById')->with(1)->willReturn($attachment);
            $this->mockVersionRepository->method('getMaxVersionNumber')->with(1)->willReturn(2);
            $this->mockEntityManager->expects($this->once())->method('persist')->with($this->isInstanceOf(AttachmentVersion::class));
            $this->mockEntityManager->expects($this->once())->method('flush');

            $result = $this->service->createNewVersion(1, $this->fakeUploadedFile('new content'));

            $this->assertSame(3, $result);
            $this->assertSame('medical_record', $attachment->getEntityType());
        }

        public function testGetAttachmentByIdReturnsNullWhenNotFound() : void
        {
            $this->mockAttachmentRepository->method('findById')->willReturn(null);

            $this->assertNull($this->service->getAttachmentById(123));
        }

        public function testGetAttachmentByIdReturnsArrayShape() : void
        {
            $attachment = (new Attachment())
                ->setEntityType('medical_record')
                ->setEntityId(5)
                ->setFilename('x.pdf')
                ->setFilepath('medical_record/5/x.pdf')
                ->setMimeType('application/pdf')
                ->setSize(100)
                ->setCreatedBy(1);
            $this->mockAttachmentRepository->method('findById')->with(10)->willReturn($attachment);

            $result = $this->service->getAttachmentById(10);

            $this->assertIsArray($result);
            $this->assertSame('medical_record', $result['entity_type']);
            $this->assertSame(5, $result['entity_id']);
            $this->assertSame('x.pdf', $result['filename']);
            $this->assertSame('medical_record/5/x.pdf', $result['filepath']);
        }

        public function testGetAttachmentsForEntityMapsEachToArray() : void
        {
            $a1 = (new Attachment())->setEntityType('medical_record')->setEntityId(5)->setFilename('a.pdf')->setFilepath('a');
            $a2 = (new Attachment())->setEntityType('medical_record')->setEntityId(5)->setFilename('b.pdf')->setFilepath('b');
            $this->mockAttachmentRepository->method('getAttachmentsForEntity')
                ->with('medical_record', 5)
                ->willReturn([$a1, $a2]);

            $result = $this->service->getAttachmentsForEntity('medical_record', 5);

            $this->assertCount(2, $result);
            $this->assertSame('a.pdf', $result[0]['filename']);
            $this->assertSame('b.pdf', $result[1]['filename']);
        }

        public function testCheckViewAccessDelegatesToAclRepository() : void
        {
            $this->mockAclRepository->expects($this->once())
                ->method('checkViewAccess')
                ->with(1, 2, 3)
                ->willReturn(true);

            $this->assertTrue($this->service->checkViewAccess(1, 2, 3));
        }

        public function testUpdateAccessDelegatesToAclRepository() : void
        {
            $this->mockAclRepository->expects($this->once())
                ->method('updateAccess')
                ->with(1, 2, null, true, false)
                ->willReturn(true);

            $this->assertTrue($this->service->updateAccess(1, 2, null, true, false));
        }
    }

}

namespace App\Shared\Service {
    function move_uploaded_file($from, $to)
    {
        return copy($from, $to);
    }
}
