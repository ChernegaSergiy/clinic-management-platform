<?php

namespace App\Core;

use App\Database;
use PDO;

class AttachmentService
{
    private PDO $pdo;
    private string $uploadDir = __DIR__ . '/../../uploads'; // Base upload directory

    public function __construct()
    {
        $this->pdo = Database::getInstance();
        if (!is_dir($this->uploadDir)) {
            mkdir($this->uploadDir, 0775, true);
        }
    }

    /**
     * Uploads a file, saves its metadata, and creates its first version.
     *
     * @param array $fileData Array from $_FILES, e.g., $_FILES['my_file']
     * @param string $entityType The type of entity this attachment belongs to (e.g., 'medical_record', 'patient')
     * @param int $entityId The ID of the entity
     * @param int|null $userId The ID of the user uploading the file (optional)
     * @return int|false The ID of the new attachment, or false on failure
     */
    public function uploadAttachment(array $fileData, string $entityType, int $entityId, ?int $userId = null)
    {
        if ($fileData['error'] !== UPLOAD_ERR_OK) {
            // Handle various upload errors
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
            // Save attachment metadata
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

            // Create first version
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
            // Log error
            unlink($targetPath); // Clean up uploaded file
            return false;
        }
    }

    /**
     * Creates a new version for an existing attachment.
     *
     * @param int $attachmentId
     * @param array $fileData Array from $_FILES
     * @param int|null $userId
     * @return int|false The new version number, or false on failure
     */
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
            // Get next version number
            $stmt = $this->pdo->prepare("SELECT MAX(version_number) FROM attachment_versions WHERE attachment_id = :attachment_id");
            $stmt->execute([':attachment_id' => $attachmentId]);
            $nextVersionNumber = $stmt->fetchColumn() + 1;

            // Create new version entry
            $stmt = $this->pdo->prepare("INSERT INTO attachment_versions (attachment_id, version_number, filepath, filename, size, created_by) VALUES (:attachment_id, :version_number, :filepath, :filename, :size, :created_by)");
            $stmt->execute([
                ':attachment_id' => $attachmentId,
                ':version_number' => $nextVersionNumber,
                ':filepath' => $relativePath,
                ':filename' => $filename,
                ':size' => $size,
                ':created_by' => $userId,
            ]);

            // Update main attachment record
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

    /**
     * Retrieves an attachment by its ID.
     *
     * @param int $attachmentId
     * @return array|null
     */
    public function getAttachmentById(int $attachmentId): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM attachments WHERE id = :id");
        $stmt->execute([':id' => $attachmentId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result === false ? null : $result;
    }

    /**
     * Retrieves all attachments for a specific entity.
     *
     * @param string $entityType
     * @param int $entityId
     * @return array
     */
    public function getAttachmentsForEntity(string $entityType, int $entityId): array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM attachments WHERE entity_type = :entity_type AND entity_id = :entity_id ORDER BY created_at DESC");
        $stmt->execute([
            ':entity_type' => $entityType,
            ':entity_id' => $entityId,
        ]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Retrieves all versions for a specific attachment.
     *
     * @param int $attachmentId
     * @return array
     */
    public function getAttachmentVersions(int $attachmentId): array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM attachment_versions WHERE attachment_id = :attachment_id ORDER BY version_number DESC");
        $stmt->execute([':attachment_id' => $attachmentId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Retrieves a specific version of an attachment.
     *
     * @param int $attachmentId
     * @param int $versionNumber
     * @return array|null
     */
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

    /**
     * Checks if a user has view access to an attachment.
     *
     * @param int $attachmentId
     * @param int $userId
     * @param int $userRoleId
     * @return bool
     */
    public function checkViewAccess(int $attachmentId, int $userId, int $userRoleId): bool
    {
        // Check for specific user access
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM attachment_acl WHERE attachment_id = :attachment_id AND user_id = :user_id AND can_view = TRUE");
        $stmt->execute([':attachment_id' => $attachmentId, ':user_id' => $userId]);
        if ($stmt->fetchColumn() > 0) {
            return true;
        }

        // Check for specific role access
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM attachment_acl WHERE attachment_id = :attachment_id AND role_id = :role_id AND can_view = TRUE");
        $stmt->execute([':attachment_id' => $attachmentId, ':role_id' => $userRoleId]);
        if ($stmt->fetchColumn() > 0) {
            return true;
        }

        // Default to restricted if no explicit access is defined
        // Or implement a default "public" if attachment_acl is empty for this attachment
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM attachment_acl WHERE attachment_id = :attachment_id");
        $stmt->execute([':attachment_id' => $attachmentId]);
        $hasAclEntries = $stmt->fetchColumn() > 0;

        if (!$hasAclEntries) {
            // If no ACL entries, maybe it's public by default? Or inherit from entity?
            // For now, let's assume if no ACL entry, it's restricted by default.
            // This logic can be adjusted based on business rules.
            return false;
        }

        return false; // No explicit view access
    }

    /**
     * Grants or revokes access to an attachment for a user or role.
     *
     * @param int $attachmentId
     * @param int|null $userId
     * @param int|null $roleId
     * @param bool $canView
     * @param bool $canEdit
     * @return bool
     */
    public function updateAccess(int $attachmentId, ?int $userId = null, ?int $roleId = null, bool $canView, bool $canEdit): bool
    {
        if ($userId === null && $roleId === null) {
            return false; // Must specify either user or role
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
