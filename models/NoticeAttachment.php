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
     * Create multiple attachments for a notice
     */
    public function createAttachments(int $noticeId, array $attachments): bool
    {
        try {
            $sql = "INSERT INTO notice_attachments (notice_id, file_name, file_path, file_type, file_size) 
                    VALUES (:notice_id, :file_name, :file_path, :file_type, :file_size)";
            
            $stmt = $this->conn->prepare($sql);
            
            foreach ($attachments as $attachment) {
                $stmt->execute([
                    ':notice_id' => $noticeId,
                    ':file_name' => $attachment['file_name'],
                    ':file_path' => $attachment['file_path'],
                    ':file_type' => $attachment['file_type'],
                    ':file_size' => $attachment['file_size']
                ]);
            }

            return true;
        } catch (Exception $e) {
            error_log($e->getMessage());
            return false;
        }
    }

    /**
     * Get attachments for a notice
     */
    public function getAttachments(int $noticeId): array
    {
        $sql = "SELECT * FROM notice_attachments WHERE notice_id = :notice_id";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':notice_id' => $noticeId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Delete attachments for a notice
     */
    public function deleteAttachments(int $noticeId): bool
    {
        try {
            // Get file paths before deletion
            $attachments = $this->getAttachments($noticeId);
            
            // Delete from database
            $sql = "DELETE FROM notice_attachments WHERE notice_id = :notice_id";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([':notice_id' => $noticeId]);

            // Delete physical files
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
}