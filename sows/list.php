<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/header.php';

$result = $conn->query("
   SELECT * FROM sows WHERE status != 'Culled' ORDER BY created_at DESC
");
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h2>🐷 Sows</h2>
    <a href="add.php" class="btn btn-success btn-sm">+ Add Sow</a>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>Tag No</th>
                    <th>Breed</th>
                    <th>Status</th>
                    <th>Date of Birth</th>
                    <th>Added</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result->num_rows > 0): ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['tag_no']) ?></td>
                            <td><?= htmlspecialchars($row['breed']) ?></td>
                            <td>
                                <span class="badge bg-<?= 
                                    $row['status'] === 'Pregnant' ? 'warning' :
                                    ($row['status'] === 'Active' ? 'success' : 'secondary')
                                ?>">
                                    <?= $row['status'] ?>
                                </span>
                            </td>
                            <td><?= $row['date_of_birth'] ?? '-' ?></td>
                            <td><?= date('M d, Y', strtotime($row['created_at'])) ?></td>
                            <td class="text-center">
                                <a href="edit.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-outline-primary">Edit</a>
                                <a href="cull.php?id=<?= $row['id'] ?>"class="btn btn-sm btn-outline-danger"onclick="return confirm('Mark this sow as culled?');">Cull</a>
                                <a href="profile.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-outline-secondary">Profile</a>
                        
                                </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" class="text-center text-muted">
                            No sows added yet
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
