<?php

namespace App\Core\Service;

use App\Database\Database;
use PDO;

class AttachmentService
{
    private PDO $pdo;
    private string $uploadDir = __DIR__ . '/../../../uploads';
    public function __construct(?PDO $pdo = null, ?string $uploadDir = null)
    {
        $this->pdo = $pdo ?? Database::getInstance();
        if ($uploadDir !== null) {
            $this->uploadDir = $uploadDir;
        }
        if (!is_dir($this->uploadDir)) {
            mkdir($this->uploadDir, 0775, true);
        }
    }

    public function uploadAttachment(array $fileData, string $entityType, int $entityId, ?int $userId = null)
    {
        if ($fileData['error'] !== UPLOAD_ERR_OK) {
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

        $this->pdo->beginTransaction();
        try {
            $stmt = $this->pdo->prepare("INSERT INTO attachments (entity_type, entity_id, filename, filepath, mime_type, size, created_by) VALUES (:entity_type, :entity_id, :filename, :filepath, :mime_type, :size, :created_by)");
            $stmt->execute([
                ':entity_type' => $entityType,
                ':entity_id' => $entityId,
                ':filename' => $filename,
                ':filepath' => $relativePath,
                ':mime_type' => $mimeType,
                ':size' => $size,
                ':created_by' => $userId,
            ]);
            $attachmentId = $this->pdo->lastInsertId();

            $stmt = $this->pdo->prepare("INSERT INTO attachment_versions (attachment_id, version_number, filepath, filename, size, created_by) VALUES (:attachment_id, 1, :filepath, :filename, :size, :created_by)");
            $stmt->execute([
                ':attachment_id' => $attachmentId,
                ':filepath' => $relativePath,
                ':filename' => $filename,
                ':size' => $size,
                ':created_by' => $userId,
            ]);

            $this->pdo->commit();
            return (int)$attachmentId;
        } catch (\PDOException $e) {
            $this->pdo->rollBack();
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

        $this->pdo->beginTransaction();
        try {
            $stmt = $this->pdo->prepare("SELECT MAX(version_number) FROM attachment_versions WHERE attachment_id = :attachment_id");
            $stmt->execute([':attachment_id' => $attachmentId]);
            $nextVersionNumber = $stmt->fetchColumn() + 1;

            $stmt = $this->pdo->prepare("INSERT INTO attachment_versions (attachment_id, version_number, filepath, filename, size, created_by) VALUES (:attachment_id, :version_number, :filepath, :filename, :size, :created_by)");
            $stmt->execute([
                ':attachment_id' => $attachmentId,
                ':version_number' => $nextVersionNumber,
                ':filepath' => $relativePath,
                ':filename' => $filename,
                ':size' => $size,
                ':created_by' => $userId,
            ]);

            $stmt = $this->pdo->prepare("UPDATE attachments SET filepath = :filepath, filename = :filename, mime_type = :mime_type, size = :size, updated_at = NOW() WHERE id = :attachment_id");
            $stmt->execute([
                ':filepath' => $relativePath,
                ':filename' => $filename,
                ':mime_type' => $mimeType,
                ':size' => $size,
                ':attachment_id' => $attachmentId,
            ]);

            $this->pdo->commit();
            return (int)$nextVersionNumber;
        } catch (\PDOException $e) {
            $this->pdo->rollBack();
            unlink($targetPath);
            return false;
        }
    }

    public function getAttachmentById(int $attachmentId): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM attachments WHERE id = :id");
        $stmt->execute([':id' => $attachmentId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result === false ? null : $result;
    }

    public function getAttachmentsForEntity(string $entityType, int $entityId): array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM attachments WHERE entity_type = :entity_type AND entity_id = :entity_id ORDER BY created_at DESC");
        $stmt->execute([
            ':entity_type' => $entityType,
            ':entity_id' => $entityId,
        ]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAttachmentVersions(int $attachmentId): array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM attachment_versions WHERE attachment_id = :attachment_id ORDER BY version_number DESC");
        $stmt->execute([':attachment_id' => $attachmentId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAttachmentVersion(int $attachmentId, int $versionNumber): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM attachment_versions WHERE attachment_id = :attachment_id AND version_number = :version_number");
        $stmt->execute([
            ':attachment_id' => $attachmentId,
            ':version_number' => $versionNumber,
        ]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result === false ? null : $result;
    }

    public function checkViewAccess(int $attachmentId, int $userId, int $userRoleId): bool
    {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM attachment_acl WHERE attachment_id = :attachment_id AND user_id = :user_id AND can_view = TRUE");
        $stmt->execute([':attachment_id' => $attachmentId, ':user_id' => $userId]);
        if ($stmt->fetchColumn() > 0) {
            return true;
        }

        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM attachment_acl WHERE attachment_id = :attachment_id AND role_id = :role_id AND can_view = TRUE");
        $stmt->execute([':attachment_id' => $attachmentId, ':role_id' => $userRoleId]);
        if ($stmt->fetchColumn() > 0) {
            return true;
        }

        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM attachment_acl WHERE attachment_id = :attachment_id");
        $stmt->execute([':attachment_id' => $attachmentId]);
        $hasAclEntries = $stmt->fetchColumn() > 0;

        if (!$hasAclEntries) {
            return false;
        }

        return false;
    }

    public function updateAccess(int $attachmentId, ?int $userId = null, ?int $roleId = null, bool $canView = false, bool $canEdit = false): bool
    {
        if ($userId === null && $roleId === null) {
            return false;
        }

        $stmt = $this->pdo->prepare("
            INSERT INTO attachment_acl (attachment_id, user_id, role_id, can_view, can_edit)
            VALUES (:attachment_id, :user_id, :role_id, :can_view, :can_edit)
            ON DUPLICATE KEY UPDATE can_view = :can_view, can_edit = :can_edit
        ");
        return $stmt->execute([
            ':attachment_id' => $attachmentId,
            ':user_id' => $userId,
            ':role_id' => $roleId,
            ':can_view' => $canView,
            ':can_edit' => $canEdit,
        ]);
    }
}
