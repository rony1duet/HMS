<?php
require_once '../includes/session.php';
require_once '../models/ProvostApproval.php';
require_once '../config/database.php';

Session::init();

// Check if user is logged in and has admin role
if (!Session::isLoggedIn() || !Session::hasPermission('admin')) {
    header('Location: /HMS/');
    exit();
}

$provostApproval = new ProvostApproval($conn);
$pendingApprovals = $provostApproval->getPendingApprovals();

require_once '../includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-transparent">
                    <h5 class="mb-0">Pending Provost Approvals</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($pendingApprovals)): ?>
                        <div class="text-center py-4">
                            <i class="fas fa-check-circle text-success fa-3x mb-3"></i>
                            <h5>No Pending Approvals</h5>
                            <p class="text-muted">All provost approval requests have been processed.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Request Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($pendingApprovals as $approval): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($approval['display_name']); ?></td>
                                            <td><?php echo htmlspecialchars($approval['email']); ?></td>
                                            <td><?php echo date('Y-m-d H:i', strtotime($approval['created_at'])); ?></td>
                                            <td>
                                                <button type="button"
                                                    class="btn btn-sm btn-success me-2"
                                                    onclick="showApproveModal('<?php echo $approval["slug"]; ?>')">
                                                    <i class="fas fa-check me-1"></i>Approve
                                                </button>
                                                <button type="button"
                                                    class="btn btn-sm btn-danger"
                                                    onclick="showRejectModal('<?php echo $approval["slug"]; ?>')">
                                                    <i class="fas fa-times me-1"></i>Reject
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Approve Modal -->
<div class="modal fade" id="approveModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Approve Provost</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="approveForm">
                    <input type="hidden" id="approveUserSlug" name="slug">
                    <div class="mb-3">
                        <label for="hallSelect" class="form-label">Select Hall</label>
                        <select class="form-select" id="hallSelect" name="hall_id" required>
                            <option value="">Choose a hall...</option>
                            <?php
                            $halls = $provostApproval->getAvailableHalls();
                            foreach ($halls as $hall) {
                                echo '<option value="' . $hall['id'] . '">' . htmlspecialchars($hall['name']) . '</option>';
                            }
                            ?>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success" onclick="approveProvost()">Approve</button>
            </div>
        </div>
    </div>
</div>

<!-- Reject Modal -->
<div class="modal fade" id="rejectModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Reject Provost Approval</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="rejectForm">
                    <input type="hidden" id="rejectUserSlug" name="slug">
                    <div class="mb-3">
                        <label for="rejectionReason" class="form-label">Rejection Reason</label>
                        <textarea class="form-control" id="rejectionReason" name="reason" rows="3" required></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" onclick="rejectProvost()">Reject</button>
            </div>
        </div>
    </div>
</div>

<script>
    function showApproveModal(userSlug) {
        document.getElementById('approveUserSlug').value = userSlug;
        new bootstrap.Modal(document.getElementById('approveModal')).show();
    }

    function approveProvost() {
        const form = document.getElementById('approveForm');
        if (!form.checkValidity()) {
            form.reportValidity();
            return;
        }

        const userSlug = document.getElementById('approveUserSlug').value;
        const hallId = document.getElementById('hallSelect').value;

        fetch('/HMS/actions/approve_provost.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    slug: userSlug,
                    hall_id: hallId
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert(data.message || 'Failed to approve provost');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while processing your request');
            });
    }


    function showRejectModal(userSlug) {
        document.getElementById('rejectUserSlug').value = userSlug;
        new bootstrap.Modal(document.getElementById('rejectModal')).show();
    }

    function rejectProvost() {
        const userSlug = document.getElementById('rejectUserSlug').value;
        const reason = document.getElementById('rejectionReason').value.trim();

        if (!reason) {
            alert('Please provide a rejection reason');
            return;
        }

        fetch('/HMS/actions/reject_provost.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    slug: userSlug,
                    reason: reason
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert(data.message || 'Failed to reject provost');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while processing your request');
            });
    }
</script>

<?php require_once '../includes/footer.php'; ?>