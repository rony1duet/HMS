<?php
require_once 'Model.php';
require_once 'NoticeAttachment.php';

class Notice extends Model
{
    private $attachmentModel;

    public function __construct($db)
    {
        parent::__construct($db);
        $this->attachmentModel = new NoticeAttachment($db);
    }

    /**
     * Create a new notice with attachment
     */
    public function createNotice(array $data, array $attachment = []): int
    {
        try {
            $this->conn->beginTransaction();

            if (!$this->validateNoticeData($data)) {
                throw new Exception('Invalid notice data');
            }

            $sql = "INSERT INTO notices (title, content, posted_by_slug, hall_id, importance, start_date, end_date) 
                    VALUES (:title, :content, :posted_by_slug, :hall_id, :importance, :start_date, :end_date)";


            $stmt = $this->conn->prepare($sql);
            $stmt->execute([
                ':title' => $data['title'],
                ':content' => $data['content'],
                ':posted_by_slug' => $data['posted_by_slug'],
                ':hall_id' => $data['hall_id'],
                ':importance' => $data['importance'],
                ':start_date' => $data['start_date'],
                ':end_date' => $data['end_date'] ?? null
            ]);

            $noticeId = $this->conn->lastInsertId();

            if (!empty($attachment)) {
                if (!$this->attachmentModel->createAttachment($noticeId, $attachment)) {
                    throw new Exception('Failed to save attachment');
                }
            }

            $this->conn->commit();
            return $noticeId;
        } catch (Exception $e) {
            $this->conn->rollBack();
            error_log('Error in createNotice: ' . $e->getMessage() . "\nTrace: " . $e->getTraceAsString());
            throw $e;
        }
    }

    /**
     * Get notices for a specific hall with pagination
     */
    public function getNoticesByHall(?string $hallName, int $page, int $perPage, string $filter = 'all', string $orderBy = 'created_at DESC'): array
    {
        if ($hallName === null) {
            return [];
        }

        try {
            $offset = ($page - 1) * $perPage;
            $currentDate = date('Y-m-d');
            $twentyFourHoursAgo = date('Y-m-d H:i:s', strtotime('-24 hours'));

            $sql = "SELECT n.*,
                       GROUP_CONCAT(
                           CONCAT(na.id, ':', na.file_name, ':', na.file_path, ':', na.file_type)
                       ) as attachment,
                       u.display_name as posted_by_name
                FROM notices n
                LEFT JOIN notice_attachment na ON n.id = na.notice_id
                LEFT JOIN halls h ON n.hall_id = h.id
                LEFT JOIN users u ON n.posted_by_slug = u.slug
                WHERE h.name = :hall_name
                AND (n.end_date IS NULL OR n.end_date >= :current_date)";

            // Handle the 'new' filter differently
            if ($filter === 'new') {
                $sql .= " AND n.created_at >= :twenty_four_hours_ago";
            } elseif ($filter !== 'all') {
                $sql .= " AND n.importance = :importance";
            }

            $sql .= " GROUP BY n.id
                ORDER BY $orderBy
                LIMIT :offset, :per_page";

            $stmt = $this->conn->prepare($sql);
            $stmt->bindValue(':hall_name', $hallName, PDO::PARAM_STR);
            $stmt->bindValue(':current_date', $currentDate, PDO::PARAM_STR);

            if ($filter === 'new') {
                $stmt->bindValue(':twenty_four_hours_ago', $twentyFourHoursAgo, PDO::PARAM_STR);
            } elseif ($filter !== 'all') {
                $stmt->bindValue(':importance', $filter, PDO::PARAM_STR);
            }

            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->bindValue(':per_page', $perPage, PDO::PARAM_INT);
            $stmt->execute();

            $notices = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return array_map([$this, 'formatNotice'], $notices);
        } catch (Exception $e) {
            error_log($e->getMessage());
            return [];
        }
    }

    /**
     * Get a single notice with its attachment
     */
    public function getNotice(int $noticeId): ?array
    {
        try {
            $sql = "SELECT n.*, 
                           GROUP_CONCAT(
                               CONCAT(na.id, ':', na.file_name, ':', na.file_path, ':', na.file_type)
                           ) as attachment,
                           u.display_name as posted_by_name
                    FROM notices n 
                    LEFT JOIN notice_attachment na ON n.id = na.notice_id 
                    LEFT JOIN users u ON n.posted_by_slug = u.slug
                    WHERE n.id = :notice_id
                    GROUP BY n.id";

            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':notice_id', $noticeId, PDO::PARAM_INT);
            $stmt->execute();

            $notice = $stmt->fetch(PDO::FETCH_ASSOC);
            return $notice ? $this->formatNotice($notice) : null;
        } catch (Exception $e) {
            error_log($e->getMessage());
            return null;
        }
    }

    /**
     * Delete a notice and its attachment
     */
    public function deleteNotice(int $noticeId): bool
    {
        try {
            $this->conn->beginTransaction();

            if (!$this->attachmentModel->deleteAttachment($noticeId)) {
                throw new Exception('Failed to delete attachment');
            }

            $sql = "DELETE FROM notices WHERE id = :notice_id";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([':notice_id' => $noticeId]);

            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            $this->conn->rollBack();
            error_log($e->getMessage());
            return false;
        }
    }

    /**
     * Count total notices for a specific hall with filtering
     */
    public function countNoticesByHall(?string $hallName, string $filter = 'all'): int
    {
        if ($hallName === null) {
            return 0;
        }

        try {
            $currentDate = date('Y-m-d');
            $twentyFourHoursAgo = date('Y-m-d H:i:s', strtotime('-24 hours'));

            $sql = "SELECT COUNT(DISTINCT n.id) as total
                FROM notices n
                LEFT JOIN halls h ON n.hall_id = h.id
                WHERE h.name = :hall_name
                AND (n.end_date IS NULL OR n.end_date >= :current_date)";

            if ($filter === 'new') {
                $sql .= " AND n.created_at >= :twenty_four_hours_ago";
            } elseif ($filter !== 'all') {
                $sql .= " AND n.importance = :importance";
            }

            $stmt = $this->conn->prepare($sql);
            $stmt->bindValue(':hall_name', $hallName, PDO::PARAM_STR);
            $stmt->bindValue(':current_date', $currentDate, PDO::PARAM_STR);

            if ($filter === 'new') {
                $stmt->bindValue(':twenty_four_hours_ago', $twentyFourHoursAgo, PDO::PARAM_STR);
            } elseif ($filter !== 'all') {
                $stmt->bindValue(':importance', $filter, PDO::PARAM_STR);
            }

            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int)($result['total'] ?? 0);
        } catch (Exception $e) {
            error_log($e->getMessage());
            return 0;
        }
    }
    public function getNoticeById(int $noticeId): ?array
    {
        try {
            $sql = "SELECT n.*,
                       GROUP_CONCAT(
                           CONCAT(na.id, ':', na.file_name, ':', na.file_path, ':', na.file_type)
                       ) as attachment,
                       u.display_name as posted_by_name
                FROM notices n
                LEFT JOIN notice_attachment na ON n.id = na.notice_id
                LEFT JOIN users u ON n.posted_by_slug = u.slug
                WHERE n.id = :notice_id
                GROUP BY n.id";

            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':notice_id', $noticeId, PDO::PARAM_INT);
            $stmt->execute();

            $notice = $stmt->fetch(PDO::FETCH_ASSOC);
            return $notice ? $this->formatNotice($notice) : null;
        } catch (Exception $e) {
            error_log($e->getMessage());
            return null;
        }
    }
    public function updateNotice(int $noticeId, array $data, array $attachment = []): bool
    {
        try {
            $this->conn->beginTransaction();

            if (!$this->validateNoticeData($data)) {
                throw new Exception('Invalid notice data');
            }

            $sql = "UPDATE notices
                SET title = :title, 
                    content = :content, 
                    importance = :importance, 
                    start_date = :start_date, 
                    end_date = :end_date,
                    updated_at = NOW()
                WHERE id = :notice_id";

            $stmt = $this->conn->prepare($sql);
            $stmt->execute([
                ':title' => $data['title'],
                ':content' => $data['content'],
                ':importance' => $data['importance'],
                ':start_date' => $data['start_date'],
                ':end_date' => $data['end_date'] ?? null,
                ':notice_id' => $noticeId
            ]);

            if (!empty($attachment)) {
                // Delete existing attachments first
                $this->attachmentModel->deleteAttachment($noticeId);

                // Add new attachment
                if (!$this->attachmentModel->createAttachment($noticeId, $attachment)) {
                    throw new Exception('Failed to save attachment');
                }
            }

            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            $this->conn->rollBack();
            error_log('Error in updateNotice: ' . $e->getMessage());
            return false;
        }
    }

    public function getNoticesByHallId(int $hallId, int $page, int $perPage): array
    {
        try {
            $offset = ($page - 1) * $perPage;
            $currentDate = date('Y-m-d');

            $sql = "SELECT n.*,
                       GROUP_CONCAT(
                           CONCAT(na.id, ':', na.file_name, ':', na.file_path, ':', na.file_type)
                       ) as attachment,
                       u.display_name as posted_by_name
                FROM notices n
                LEFT JOIN notice_attachment na ON n.id = na.notice_id
                LEFT JOIN users u ON n.posted_by_slug = u.slug
                WHERE n.hall_id = :hall_id
                AND (n.end_date IS NULL OR n.end_date >= :current_date)
                GROUP BY n.id
                ORDER BY n.created_at DESC
                LIMIT :offset, :per_page";

            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':hall_id', $hallId, PDO::PARAM_INT);
            $stmt->bindParam(':current_date', $currentDate, PDO::PARAM_STR);
            $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
            $stmt->bindParam(':per_page', $perPage, PDO::PARAM_INT);
            $stmt->execute();

            $notices = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return array_map([$this, 'formatNotice'], $notices);
        } catch (Exception $e) {
            error_log($e->getMessage());
            return [];
        }
    }

    /**
     * Format a notice record with its attachment
     */
    private function formatNotice(array $notice): array
    {
        if (!empty($notice['attachment'])) {
            $attachmentParts = explode(',', $notice['attachment']);
            $notice['attachment'] = array_map(function ($part) {
                $parts = explode(':', $part);
                return [
                    'id' => $parts[0],
                    'file_name' => $parts[1],
                    'file_path' => $parts[2],
                    'file_type' => $parts[3]
                ];
            }, $attachmentParts);
        } else {
            $notice['attachment'] = [];
        }
        return $notice;
    }

    /**
     * Validate notice data
     */
    private function validateNoticeData(array $data): bool
    {
        // Check required fields for all operations
        if (empty($data['title']) || empty($data['content']) || empty($data['importance']) || empty($data['start_date'])) {
            return false;
        }

        if (!in_array($data['importance'], ['normal', 'important', 'urgent'])) {
            return false;
        }

        $startDate = strtotime($data['start_date']);
        if ($startDate === false) {
            return false;
        }

        if (!empty($data['end_date'])) {
            $endDate = strtotime($data['end_date']);
            if ($endDate === false || $endDate <= $startDate) {
                return false;
            }
        }

        // Only check hall_id if it's provided (needed for create but not for update)
        if (!empty($data['hall_id'])) {
            try {
                $stmt = $this->conn->prepare("SELECT id FROM halls WHERE id = :hall_id");
                $stmt->execute([':hall_id' => $data['hall_id']]);
                if (!$stmt->fetch()) {
                    return false;
                }
            } catch (Exception $e) {
                error_log($e->getMessage());
                return false;
            }
        }

        return true;
    }
}
