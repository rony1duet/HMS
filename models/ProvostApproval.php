<?php
require_once 'Model.php';

class ProvostApproval extends Model
{
    private $id;
    private $slug;
    private $status;
    private $approved_by_slug;
    private $hall_id;
    private $approval_date;
    private $rejection_reason;
    private $created_at;
    private $updated_at;

    public function __construct($db)
    {
        parent::__construct($db);
    }

    /**
     * Get all available halls
     */
    public function getAvailableHalls(): array
    {
        $sql = "SELECT id, name, gender FROM halls ORDER BY name";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Create a new provost approval request
     */
    public function createApprovalRequest(string $slug): bool
    {
        $sql = "INSERT INTO provost_approvals (slug) VALUES (?)";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([$slug]);
    }

    /**
     * Update provost approval status
     */
    public function updateApprovalStatus(string $slug, string $status, ?string $approved_by_slug = null, ?string $rejection_reason = null): bool
    {
        $sql = "UPDATE provost_approvals SET status = ?, approved_by_slug = ?, approval_date = CURRENT_TIMESTAMP, rejection_reason = ? WHERE slug = ?";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([$status, $approved_by_slug, $rejection_reason, $slug]);
    }

    /**
     * Get approval status for a provost
     */
    public function getApprovalStatus(string $slug): ?array
    {
        $sql = "SELECT pa.*, u.display_name as approver_name, h.name as hall_name 
                FROM provost_approvals pa 
                LEFT JOIN users u ON pa.approved_by_slug = u.slug 
                LEFT JOIN halls h ON pa.hall_id = h.id 
                WHERE pa.slug = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$slug]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * Get all pending approval requests
     */
    public function getPendingApprovals(): array
    {
        $sql = "SELECT pa.*, u.display_name, u.email, h.name as hall_name 
                FROM provost_approvals pa 
                JOIN users u ON pa.slug = u.slug 
                LEFT JOIN halls h ON pa.hall_id = h.id 
                WHERE pa.status = 'pending'";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Check if a provost is approved
     */
    public function isProvostApproved(string $slug): bool
    {
        $sql = "SELECT status FROM provost_approvals WHERE slug = ? AND status = 'approved'";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$slug]);
        return (bool)$stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Approve a provost request
     */
    public function approveProvost(string $slug, string $approved_by_slug, int $hall_id): bool
    {
        $sql = "UPDATE provost_approvals SET status = 'approved', approved_by_slug = ?, approval_date = CURRENT_TIMESTAMP, hall_id = ? WHERE slug = ?";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([$approved_by_slug, $hall_id, $slug]);
    }

    /**
     * Reject a provost request
     */
    public function rejectProvost(string $slug, string $rejection_reason, string $rejected_by_slug): bool
    {
        return $this->updateApprovalStatus($slug, 'rejected', $rejected_by_slug, $rejection_reason);
    }

    /**
     * Delete approval request
     */
    public function deleteApprovalRequest(string $slug): bool
    {
        $sql = "DELETE FROM provost_approvals WHERE slug = ?";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([$slug]);
    }
}
