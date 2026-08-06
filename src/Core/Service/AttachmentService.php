<?php

namespace App\Core\Service;

use Doctrine\Persistence\ManagerRegistry;

class AttachmentService
{
    private ManagerRegistry $registry;
    private string $uploadDir = __DIR__ . '/../../../uploads';
    public function __construct(ManagerRegistry $registry, ?string $uploadDir = null)
    {
        $this->registry = $registry;
        if (null !== $uploadDir) {
            $this->uploadDir = $uploadDir;
        }
        if (!is_dir($this->uploadDir)) {
            mkdir($this->uploadDir, 0775, true);
        }
    }

    public function uploadAttachment(array $fileData, string $entityType, int $entityId, ?int $userId = null)
    {
        if (UPLOAD_ERR_OK !== $fileData['error']) {
            return false;
        }

        $filename = basename($fileData['name']);
        $mimeType = $fileData['type'];
        $size = $fileData['size'];
        $tempPath = $fileData['tmp_name'];

        $targetDir = $this->uploadDir . '/' . $entityType . '/' . $entityId;
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0775, true);
        }

        $uniqueFilename = uniqid('', true) . '_' . $filename;
        $targetPath = $targetDir . '/' . $uniqueFilename;
        $relativePath = str_replace($this->uploadDir . '/', '', $targetPath);

        if (!move_uploaded_file($tempPath, $targetPath)) {
            return false;
        }

        $conn = $this->registry->getConnection();
        $conn->beginTransaction();
        try {
            $sql = "INSERT INTO attachments (entity_type, entity_id, filename, filepath, mime_type, size, created_by) VALUES (:entity_type, :entity_id, :filename, :filepath, :mime_type, :size, :created_by)";
            $conn->executeStatement($sql, [
                'entity_type' => $entityType,
                'entity_id' => $entityId,
                'filename' => $filename,
                'filepath' => $relativePath,
                'mime_type' => $mimeType,
                'size' => $size,
                'created_by' => $userId,
            ]);
            $attachmentId = $conn->lastInsertId();

            $sql = "INSERT INTO attachment_versions (attachment_id, version_number, filepath, filename, size, created_by) VALUES (:attachment_id, 1, :filepath, :filename, :size, :created_by)";
            $conn->executeStatement($sql, [
                'attachment_id' => $attachmentId,
                'filepath' => $relativePath,
                'filename' => $filename,
                'size' => $size,
                'created_by' => $userId,
            ]);

            $conn->commit();
            return (int)$attachmentId;
        } catch (\Exception $e) {
            $conn->rollBack();
            unlink($targetPath);
            return false;
        }
    }

    public function createNewVersion(int $attachmentId, array $fileData, ?int $userId = null)
    {
        $currentAttachment = $this->getAttachmentById($attachmentId);
        if (!$currentAttachment) {
            return false;
        }

        $filename = basename($fileData['name']);
        $mimeType = $fileData['type'];
        $size = $fileData['size'];
        $tempPath = $fileData['tmp_name'];

        $targetDir = $this->uploadDir . '/' . $currentAttachment['entity_type'] . '/' . $currentAttachment['entity_id'];
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0775, true);
        }

        $uniqueFilename = uniqid('', true) . '_' . $filename;
        $targetPath = $targetDir . '/' . $uniqueFilename;
        $relativePath = str_replace($this->uploadDir . '/', '', $targetPath);

        if (!move_uploaded_file($tempPath, $targetPath)) {
            return false;
        }

        $conn = $this->registry->getConnection();
        $conn->beginTransaction();
        try {
            $sql = "SELECT MAX(version_number) FROM attachment_versions WHERE attachment_id = :attachment_id";
            $nextVersionNumber = $conn->fetchOne($sql, ['attachment_id' => $attachmentId]) + 1;

            $sql = "INSERT INTO attachment_versions (attachment_id, version_number, filepath, filename, size, created_by) VALUES (:attachment_id, :version_number, :filepath, :filename, :size, :created_by)";
            $conn->executeStatement($sql, [
                'attachment_id' => $attachmentId,
                'version_number' => $nextVersionNumber,
                'filepath' => $relativePath,
                'filename' => $filename,
                'size' => $size,
                'created_by' => $userId,
            ]);

            $sql = "UPDATE attachments SET filepath = :filepath, filename = :filename, mime_type = :mime_type, size = :size, updated_at = NOW() WHERE id = :attachment_id";
            $conn->executeStatement($sql, [
                'filepath' => $relativePath,
                'filename' => $filename,
                'mime_type' => $mimeType,
                'size' => $size,
                'attachment_id' => $attachmentId,
            ]);

            $conn->commit();
            return (int)$nextVersionNumber;
        } catch (\Exception $e) {
            $conn->rollBack();
            unlink($targetPath);
            return false;
        }
    }

    public function getAttachmentById(int $attachmentId) : ?array
    {
        $conn = $this->registry->getConnection();
        $sql = "SELECT * FROM attachments WHERE id = :id";
        $result = $conn->fetchAssociative($sql, ['id' => $attachmentId]);
        return $result ?: null;
    }

    public function getAttachmentsForEntity(string $entityType, int $entityId) : array
    {
        $conn = $this->registry->getConnection();
        $sql = "SELECT * FROM attachments WHERE entity_type = :entity_type AND entity_id = :entity_id ORDER BY created_at DESC";
        return $conn->fetchAllAssociative($sql, [
            'entity_type' => $entityType,
            'entity_id' => $entityId,
        ]);
    }

    public function getAttachmentVersions(int $attachmentId) : array
    {
        $conn = $this->registry->getConnection();
        $sql = "SELECT * FROM attachment_versions WHERE attachment_id = :attachment_id ORDER BY version_number DESC";
        return $conn->fetchAllAssociative($sql, ['attachment_id' => $attachmentId]);
    }

    public function getAttachmentVersion(int $attachmentId, int $versionNumber) : ?array
    {
        $conn = $this->registry->getConnection();
        $sql = "SELECT * FROM attachment_versions WHERE attachment_id = :attachment_id AND version_number = :version_number";
        $result = $conn->fetchAssociative($sql, [
            'attachment_id' => $attachmentId,
            'version_number' => $versionNumber,
        ]);
        return $result ?: null;
    }

    public function checkViewAccess(int $attachmentId, int $userId, int $userRoleId) : bool
    {
        $conn = $this->registry->getConnection();

        $sql = "SELECT COUNT(*) FROM attachment_acl WHERE attachment_id = :attachment_id AND user_id = :user_id AND can_view = TRUE";
        if ($conn->fetchOne($sql, ['attachment_id' => $attachmentId, 'user_id' => $userId]) > 0) {
            return true;
        }

        $sql = "SELECT COUNT(*) FROM attachment_acl WHERE attachment_id = :attachment_id AND role_id = :role_id AND can_view = TRUE";
        if ($conn->fetchOne($sql, ['attachment_id' => $attachmentId, 'role_id' => $userRoleId]) > 0) {
            return true;
        }

        $sql = "SELECT COUNT(*) FROM attachment_acl WHERE attachment_id = :attachment_id";
        $hasAclEntries = $conn->fetchOne($sql, ['attachment_id' => $attachmentId]) > 0;

        if (!$hasAclEntries) {
            return false;
        }

        return false;
    }

    public function updateAccess(int $attachmentId, ?int $userId = null, ?int $roleId = null, bool $canView = false, bool $canEdit = false) : bool
    {
        if (null === $userId && null === $roleId) {
            return false;
        }

        $conn = $this->registry->getConnection();
        $sql = "
            INSERT INTO attachment_acl (attachment_id, user_id, role_id, can_view, can_edit)
            VALUES (:attachment_id, :user_id, :role_id, :can_view, :can_edit)
            ON DUPLICATE KEY UPDATE can_view = :can_view, can_edit = :can_edit
        ";
        return $conn->executeStatement($sql, [
            'attachment_id' => $attachmentId,
            'user_id' => $userId,
            'role_id' => $roleId,
            'can_view' => $canView ? 1 : 0,
            'can_edit' => $canEdit ? 1 : 0,
        ]) > 0;
    }
}
