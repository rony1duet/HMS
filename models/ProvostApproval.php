<?php
require_once 'Model.php';

class ProvostApproval extends Model
{
    private $id;
    private $user_slug;
    private $status;
    private $approved_by_slug;
    private $approval_date;
    private $rejection_reason;
    private $created_at;
    private $updated_at;

    public function __construct($db)
    {
        parent::__construct($db);
    }

    /**
     * Create a new provost approval request
     */
    public function createApprovalRequest(string $user_slug): bool
    {
        $sql = "INSERT INTO provost_approvals (user_slug) VALUES (?)";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([$user_slug]);
    }

    /**
     * Update provost approval status
     */
    public function updateApprovalStatus(string $user_slug, string $status, ?string $approved_by_slug = null, ?string $rejection_reason = null): bool
    {
        $sql = "UPDATE provost_approvals SET status = ?, approved_by_slug = ?, approval_date = CURRENT_TIMESTAMP, rejection_reason = ? WHERE user_slug = ?";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([$status, $approved_by_slug, $rejection_reason, $user_slug]);
    }

    /**
     * Get approval status for a provost
     */
    public function getApprovalStatus(string $user_slug): ?array
    {
        $sql = "SELECT pa.*, u.display_name as approver_name 
                FROM provost_approvals pa 
                LEFT JOIN users u ON pa.approved_by_slug = u.slug 
                WHERE pa.user_slug = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$user_slug]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * Get all pending approval requests
     */
    public function getPendingApprovals(): array
    {
        $sql = "SELECT pa.*, u.display_name, u.email 
                FROM provost_approvals pa 
                JOIN users u ON pa.user_slug = u.slug 
                WHERE pa.status = 'pending'";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Check if a provost is approved
     */
    public function isProvostApproved(string $user_slug): bool
    {
        $sql = "SELECT status FROM provost_approvals WHERE user_slug = ? AND status = 'approved'";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$user_slug]);
        return (bool)$stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Approve a provost request
     */
    public function approveProvost(string $user_slug, string $approved_by_slug): bool
    {
        return $this->updateApprovalStatus($user_slug, 'approved', $approved_by_slug);
    }

    /**
     * Reject a provost request
     */
    public function rejectProvost(string $user_slug, string $rejection_reason, string $rejected_by_slug): bool
    {
        return $this->updateApprovalStatus($user_slug, 'rejected', $rejected_by_slug, $rejection_reason);
    }

    /**
     * Delete approval request
     */
    public function deleteApprovalRequest(string $user_slug): bool
    {
        $sql = "DELETE FROM provost_approvals WHERE user_slug = ?";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([$user_slug]);
    }
}
