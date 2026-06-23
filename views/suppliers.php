<?php
/**
 * views/suppliers.php
 * Supplier Management — CRUD (Admin Only for write operations)
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../controllers/AuthController.php';
require_once __DIR__ . '/../controllers/SupplierController.php';

AuthController::requireAuth();

$isAdmin   = AuthController::isAdmin();
$action    = $_GET['action']  ?? '';
$supplierId = (int)($_GET['id'] ?? 0);
$flash     = '';
$flashType = 'success';

// ── Handle POST ───────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $isAdmin) {
    $postAction = $_POST['action'] ?? '';

    if ($postAction === 'create') {
        SupplierController::create($_POST);
        header('Location: /Collaborative_project/views/suppliers.php?flash=created');
        exit;
    }

    if ($postAction === 'update') {
        SupplierController::update((int)$_POST['supplier_id'], $_POST);
        header('Location: /Collaborative_project/views/suppliers.php?flash=updated');
        exit;
    }

    if ($postAction === 'delete') {
        header('Content-Type: application/json');
        SupplierController::delete((int)$_POST['supplier_id']);
        echo json_encode(['success' => true]);
        exit;
    }
}

if (isset($_GET['flash'])) {
    $msgs  = ['created' => 'Supplier added successfully.', 'updated' => 'Supplier updated.'];
    $flash = $msgs[$_GET['flash']] ?? '';
}

$editSupplier = null;
if ($action === 'edit' && $supplierId > 0 && $isAdmin) {
    $editSupplier = SupplierController::getById($supplierId);
}

$suppliers  = SupplierController::getAll();
$pageTitle  = 'Suppliers';
$activePage = 'suppliers';

require_once __DIR__ . '/layout/header.php';
?>

<?php if ($flash): ?>
<div class="alert alert-<?= $flashType ?> animate-fade-in" data-auto-dismiss="5000" role="alert"><?= $flash ?></div>
<?php endif; ?>

<div class="page-header animate-fade-in">
    <div>
        <div class="page-title">Suppliers</div>
        <div class="page-subtitle"><?= count($suppliers) ?> active vendor<?= count($suppliers) !== 1 ? 's' : '' ?></div>
    </div>
    <?php if ($isAdmin): ?>
    <button class="btn btn-primary" id="openAddSupplier">➕ Add Supplier</button>
    <?php endif; ?>
</div>

<!-- Edit Form -->
<?php if ($editSupplier && $isAdmin): ?>
<div class="card mb-4 animate-fade-in">
    <div class="card-header">
        <span class="card-title">✏️ Edit — <?= htmlspecialchars($editSupplier['name']) ?></span>
        <a href="/Collaborative_project/views/suppliers.php" class="btn btn-ghost btn-sm">✕ Cancel</a>
    </div>
    <div class="card-body">
        <form method="POST">
            <input type="hidden" name="action"      value="update">
            <input type="hidden" name="supplier_id" value="<?= $editSupplier['id'] ?>">
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Name <span class="required">*</span></label>
                    <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($editSupplier['name']) ?>" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Contact Email</label>
                    <input type="email" name="contact_email" class="form-control" value="<?= htmlspecialchars($editSupplier['contact_email'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Phone</label>
                    <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($editSupplier['phone'] ?? '') ?>">
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Address</label>
                <input type="text" name="address" class="form-control" value="<?= htmlspecialchars($editSupplier['address'] ?? '') ?>">
            </div>
            <div style="display:flex;gap:10px;justify-content:flex-end;">
                <a href="/Collaborative_project/views/suppliers.php" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">💾 Save</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<!-- Supplier Cards Grid -->
<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:var(--space-5);" class="animate-fade-in">
<?php foreach ($suppliers as $s): ?>
<div class="card">
    <div style="padding:var(--space-5) var(--space-5) var(--space-3);">
        <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:12px;">
            <div style="width:44px;height:44px;border-radius:var(--radius-md);background:linear-gradient(135deg,var(--brand-primary),var(--brand-secondary));display:flex;align-items:center;justify-content:center;font-size:22px;flex-shrink:0;">🏭</div>
            <div style="flex:1;min-width:0;">
                <div style="font-weight:700;font-size:15px;color:var(--text-primary);line-height:1.3;"><?= htmlspecialchars($s['name']) ?></div>
                <div style="font-size:12px;color:var(--text-muted);margin-top:2px;">
                    <span class="badge badge-primary"><?= $s['product_count'] ?> product<?= $s['product_count'] != 1 ? 's' : '' ?></span>
                </div>
            </div>
        </div>
        <div style="margin-top:var(--space-4);display:flex;flex-direction:column;gap:6px;">
            <?php if (!empty($s['contact_email'])): ?>
            <div style="font-size:12px;color:var(--text-secondary);display:flex;align-items:center;gap:6px;">
                <span>📧</span>
                <a href="mailto:<?= htmlspecialchars($s['contact_email']) ?>"
                   style="color:var(--brand-primary);text-decoration:none;">
                    <?= htmlspecialchars($s['contact_email']) ?>
                </a>
            </div>
            <?php endif; ?>
            <?php if (!empty($s['phone'])): ?>
            <div style="font-size:12px;color:var(--text-secondary);display:flex;align-items:center;gap:6px;">
                <span>📞</span><?= htmlspecialchars($s['phone']) ?>
            </div>
            <?php endif; ?>
            <?php if (!empty($s['address'])): ?>
            <div style="font-size:12px;color:var(--text-secondary);display:flex;align-items:flex-start;gap:6px;">
                <span>📍</span><?= htmlspecialchars($s['address']) ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php if ($isAdmin): ?>
    <div style="padding:var(--space-3) var(--space-5);border-top:1px solid var(--border-color);display:flex;gap:8px;">
        <a href="/Collaborative_project/views/suppliers.php?action=edit&id=<?= $s['id'] ?>"
           class="btn btn-secondary btn-sm" style="flex:1;justify-content:center;">✏️ Edit</a>
        <button class="btn btn-danger btn-sm delete-supplier-btn"
                data-id="<?= $s['id'] ?>"
                data-name="<?= htmlspecialchars($s['name']) ?>"
                data-products="<?= $s['product_count'] ?>"
                style="flex:1;justify-content:center;">🗑️ Delete</button>
    </div>
    <?php endif; ?>
</div>
<?php endforeach; ?>
</div>

<!-- Add Supplier Modal -->
<?php if ($isAdmin): ?>
<div class="modal-overlay" id="addSupplierModal" style="display:none;" role="dialog" aria-modal="true">
    <div class="modal">
        <div class="modal-header">
            <span class="modal-title">➕ Add New Supplier</span>
            <button class="modal-close" id="closeAddSupplier">✕</button>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="create">
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">Company Name <span class="required">*</span></label>
                    <input type="text" name="name" class="form-control" placeholder="e.g. GlobalParts Inc." required>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Contact Email</label>
                        <input type="email" name="contact_email" class="form-control" placeholder="orders@example.com">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Phone</label>
                        <input type="text" name="phone" class="form-control" placeholder="+1-800-555-0100">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Address</label>
                    <input type="text" name="address" class="form-control" placeholder="Street, City, State">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" id="closeAddSupplier2">Cancel</button>
                <button type="submit" class="btn btn-primary">✅ Add Supplier</button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const modal    = document.getElementById('addSupplierModal');
    const openBtn  = document.getElementById('openAddSupplier');
    const close1   = document.getElementById('closeAddSupplier');
    const close2   = document.getElementById('closeAddSupplier2');

    const open  = () => { modal.style.display = 'flex'; }
    const close = () => { modal.style.display = 'none'; }

    openBtn.addEventListener('click', open);
    close1.addEventListener('click', close);
    close2.addEventListener('click', close);
    modal.addEventListener('click', e => { if (e.target === modal) close(); });
    <?php if ($action === 'new'): ?> open(); <?php endif; ?>

    // Delete supplier
    document.querySelectorAll('.delete-supplier-btn').forEach(btn => {
        btn.addEventListener('click', async () => {
            const id       = btn.dataset.id;
            const name     = btn.dataset.name;
            const products = parseInt(btn.dataset.products, 10);

            const msg = products > 0
                ? `Delete <strong>${name}</strong>? This supplier has ${products} linked product(s). Products will need reassignment.`
                : `Delete <strong>${name}</strong>? This cannot be undone.`;

            const ok = await confirmDialog(msg, 'Delete Supplier');
            if (!ok) return;

            const res  = await fetch('/Collaborative_project/views/suppliers.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `action=delete&supplier_id=${id}`,
            });
            const json = await res.json();
            if (json.success) {
                Toast.show(`Supplier "${name}" deleted.`, 'success');
                btn.closest('.card').style.transition = 'opacity 0.3s';
                btn.closest('.card').style.opacity = '0';
                setTimeout(() => btn.closest('.card').remove(), 300);
            }
        });
    });
});
</script>
<?php endif; ?>

<?php require_once __DIR__ . '/layout/footer.php'; ?>
