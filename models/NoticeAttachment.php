<?php
require_once 'Model.php';

class NoticeAttachment extends Model
{
    private $id;
    private $notice_id;
    private $file_name;
    private $file_path;
    private $file_type;
    private $file_size;

    public function __construct($db)
    {
        parent::__construct($db);
    }

    /**
     * Create multiple attachment for a notice
     */
    public function createAttachment(int $noticeId, array $attachment): bool
    {
        try {
            $sql = "INSERT INTO notice_attachment (notice_id, file_name, file_path, file_type, file_size) 
                    VALUES (:notice_id, :file_name, :file_path, :file_type, :file_size)";

            $stmt = $this->conn->prepare($sql);

            $stmt->execute([
                ':notice_id' => $noticeId,
                ':file_name' => $attachment['file_name'],
                ':file_path' => $attachment['file_path'],
                ':file_type' => $attachment['file_type'],
                ':file_size' => $attachment['file_size']
            ]);

            return true;
        } catch (Exception $e) {
            error_log($e->getMessage());
            return false;
        }
    }

    /**
     * Get Attachment for a notice
     */
    public function getAttachment(int $noticeId): array
    {
        $sql = "SELECT * FROM notice_attachment WHERE notice_id = :notice_id";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':notice_id' => $noticeId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Delete attachment for a notice
     */
    public function deleteAttachment(int $noticeId): bool
    {
        try {
            $attachments = $this->getAttachment($noticeId);
            $sql = "DELETE FROM notice_attachment WHERE notice_id = :notice_id";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([':notice_id' => $noticeId]);

            // Delete all attachment files
            foreach ($attachments as $attachment) {
                if (file_exists($attachment['file_path'])) {
                    unlink($attachment['file_path']);
                }
            }
            return true;
        } catch (Exception $e) {
            error_log($e->getMessage());
            return false;
        }
    }

    public function deleteAttachmentById(int $attachmentId): bool
    {
        try {
            // First get the attachment details
            $sql = "SELECT * FROM notice_attachment WHERE id = :id";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([':id' => $attachmentId]);
            $attachment = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$attachment) {
                return false;
            }

            // Delete from database
            $sql = "DELETE FROM notice_attachment WHERE id = :id";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([':id' => $attachmentId]);

            // Delete the file
            if (file_exists($attachment['file_path'])) {
                unlink($attachment['file_path']);
            }

            return true;
        } catch (Exception $e) {
            error_log($e->getMessage());
            return false;
        }
    }

    /**
     * Validate file type
     */
    public static function isValidFileType(string $fileType): bool
    {
        return in_array(strtolower($fileType), ['pdf', 'jpg', 'jpeg', 'png', 'gif']);
    }

    /**
     * Generate unique filename
     */
    public static function generateUniqueFilename(string $originalName): string
    {
        return uniqid() . '_' . $originalName;
    }
    public function getAttachmentByNoticeId($noticeId)
    {
        try {
            $sql = "SELECT * FROM notice_attachment WHERE notice_id = :notice_id";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([':notice_id' => $noticeId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error fetching attachment: " . $e->getMessage());
            return [];
        }
    }
}
