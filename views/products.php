<?php
/**
 * views/products.php
 * Product Management — CRUD + Live Search + Inline Stock Update
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../controllers/AuthController.php';
require_once __DIR__ . '/../controllers/ProductController.php';
require_once __DIR__ . '/../controllers/SupplierController.php';

AuthController::requireAuth();

$isAdmin    = AuthController::isAdmin();
$action     = $_GET['action']  ?? '';
$productId  = (int)($_GET['id'] ?? 0);
$flash      = '';
$flashType  = 'success';

// ── Handle Form Submissions ───────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postAction = $_POST['action'] ?? '';

    // DELETE (AJAX — returns JSON)
    if ($postAction === 'delete') {
        header('Content-Type: application/json');
        if (!$isAdmin) {
            echo json_encode(['success' => false, 'error' => ERR_UNAUTHORIZED]);
            exit;
        }
        $id  = (int)($_POST['product_id'] ?? 0);
        $ok  = ProductController::delete($id);
        echo json_encode(['success' => $ok]);
        exit;
    }

    // CREATE
    if ($postAction === 'create' && $isAdmin) {
        try {
            $newId = ProductController::create($_POST);
            $flash = 'Product created successfully! Draft POs auto-generated for any low-stock items.';
            header('Location: /Collaborative_project/views/products.php?flash=created');
            exit;
        } catch (Exception $e) {
            $flash     = 'Failed to create product: ' . htmlspecialchars($e->getMessage());
            $flashType = 'danger';
        }
    }

    // UPDATE
    if ($postAction === 'update' && $isAdmin) {
        $pid = (int)($_POST['product_id'] ?? 0);
        try {
            ProductController::update($pid, $_POST);
            header('Location: /Collaborative_project/views/products.php?flash=updated');
            exit;
        } catch (Exception $e) {
            $flash     = 'Failed to update product: ' . htmlspecialchars($e->getMessage());
            $flashType = 'danger';
        }
    }
}

// Flash from redirect
if (isset($_GET['flash'])) {
    $msgs = ['created' => 'Product created.', 'updated' => 'Product updated.'];
    $flash = $msgs[$_GET['flash']] ?? '';
}

// ── Data for Edit Form ────────────────────────────────────────────────────────
$editProduct = null;
if ($action === 'edit' && $productId > 0) {
    $editProduct = ProductController::getById($productId);
}

$suppliers  = SupplierController::getAll();
$pageTitle  = 'Products';
$activePage = 'products';

require_once __DIR__ . '/layout/header.php';
?>

<?php if ($flash): ?>
<div class="alert alert-<?= $flashType ?> animate-fade-in" data-auto-dismiss="5000" role="alert">
    <?= $flash ?>
</div>
<?php endif; ?>

<!-- ── Page Header ──────────────────────────────────────────────────────── -->
<div class="page-header animate-fade-in">
    <div>
        <div class="page-title">Products</div>
        <div class="page-subtitle" id="productCount">Loading products…</div>
    </div>
    <?php if ($isAdmin): ?>
    <button class="btn btn-primary" id="openAddProduct" aria-controls="addProductModal">
        ➕ Add Product
    </button>
    <?php endif; ?>
</div>

<!-- ── Edit Product Form ─────────────────────────────────────────────────── -->
<?php if ($editProduct && $isAdmin): ?>
<div class="card mb-4 animate-fade-in" id="editProductCard">
    <div class="card-header">
        <span class="card-title">✏️ Edit Product — <?= htmlspecialchars($editProduct['name']) ?></span>
        <a href="/Collaborative_project/views/products.php" class="btn btn-ghost btn-sm">✕ Cancel</a>
    </div>
    <div class="card-body">
        <form method="POST" action="/Collaborative_project/views/products.php">
            <input type="hidden" name="action"     value="update">
            <input type="hidden" name="product_id" value="<?= $editProduct['id'] ?>">
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="edit_name">Product Name <span class="required">*</span></label>
                    <input type="text" id="edit_name" name="name" class="form-control"
                           value="<?= htmlspecialchars($editProduct['name']) ?>" required>
                </div>
                <div class="form-group">
                    <label class="form-label" for="edit_sku">SKU <span class="required">*</span></label>
                    <input type="text" id="edit_sku" name="sku" class="form-control"
                           value="<?= htmlspecialchars($editProduct['sku']) ?>" required>
                </div>
                <div class="form-group">
                    <label class="form-label" for="edit_supplier">Supplier <span class="required">*</span></label>
                    <select id="edit_supplier" name="supplier_id" class="form-control" required>
                        <?php foreach ($suppliers as $s): ?>
                        <option value="<?= $s['id'] ?>" <?= $s['id'] == $editProduct['supplier_id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($s['name']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label" for="edit_category">Category</label>
                    <input type="text" id="edit_category" name="category" class="form-control"
                           value="<?= htmlspecialchars($editProduct['category'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label class="form-label" for="edit_reorder">Reorder Level <span class="required">*</span></label>
                    <input type="number" id="edit_reorder" name="reorder_level" class="form-control"
                           value="<?= $editProduct['reorder_level'] ?>" min="0" required>
                </div>
                <div class="form-group">
                    <label class="form-label" for="edit_price">Unit Price ($) <span class="required">*</span></label>
                    <input type="number" id="edit_price" name="unit_price" class="form-control"
                           value="<?= $editProduct['unit_price'] ?>" min="0" step="0.01" required>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label" for="edit_desc">Description</label>
                <input type="text" id="edit_desc" name="description" class="form-control"
                       value="<?= htmlspecialchars($editProduct['description'] ?? '') ?>">
            </div>
            <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:8px;">
                <a href="/Collaborative_project/views/products.php" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">💾 Save Changes</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<!-- ── Product Table ─────────────────────────────────────────────────────── -->
<div class="card animate-fade-in">
    <div class="card-header">
        <span class="card-title">📦 Inventory</span>
        <!-- Live Search Bar -->
        <div class="search-bar-wrap">
            <span class="search-icon" aria-hidden="true">🔍</span>
            <input type="search"
                   id="productSearch"
                   class="form-control search-input"
                   placeholder="Search name, SKU, category…"
                   aria-label="Search products"
                   autocomplete="off">
            <div class="search-spinner spinner" aria-hidden="true"></div>
        </div>
    </div>
    <div class="card-body no-pad">
        <div class="table-wrap">
            <table class="data-table" aria-label="Product inventory table">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>SKU</th>
                        <th>Supplier</th>
                        <th>Stock Level</th>
                        <th>Reorder At</th>
                        <th>Unit Price</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="productTableBody"
                       data-is-admin="<?= $isAdmin ? '1' : '0' ?>">
                    <tr><td colspan="7" class="text-center" style="padding:40px;color:var(--text-muted);">
                        <div class="spinner" style="margin:0 auto;"></div>
                    </td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ── Add Product Modal (Admin Only) ────────────────────────────────────── -->
<?php if ($isAdmin): ?>
<div class="modal-overlay" id="addProductModal" style="display:none;" role="dialog" aria-modal="true" aria-label="Add new product">
    <div class="modal">
        <div class="modal-header">
            <span class="modal-title">➕ Add New Product</span>
            <button class="modal-close" id="closeAddProduct" aria-label="Close modal">✕</button>
        </div>
        <form method="POST" action="/Collaborative_project/views/products.php">
            <input type="hidden" name="action" value="create">
            <div class="modal-body">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="add_name">Name <span class="required">*</span></label>
                        <input type="text" id="add_name" name="name" class="form-control" placeholder="e.g. USB Hub 7-Port" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="add_sku">SKU <span class="required">*</span></label>
                        <input type="text" id="add_sku" name="sku" class="form-control" placeholder="e.g. SKU-NT-007" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="add_supplier">Supplier <span class="required">*</span></label>
                        <select id="add_supplier" name="supplier_id" class="form-control" required>
                            <option value="">— Select supplier —</option>
                            <?php foreach ($suppliers as $s): ?>
                            <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="add_category">Category</label>
                        <input type="text" id="add_category" name="category" class="form-control" placeholder="e.g. Electronics">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="add_stock">Initial Stock <span class="required">*</span></label>
                        <input type="number" id="add_stock" name="stock_level" class="form-control" value="0" min="0" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="add_reorder">Reorder Level <span class="required">*</span></label>
                        <input type="number" id="add_reorder" name="reorder_level" class="form-control" value="10" min="0" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="add_price">Unit Price ($) <span class="required">*</span></label>
                        <input type="number" id="add_price" name="unit_price" class="form-control" value="0.00" min="0" step="0.01" required>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label" for="add_desc">Description</label>
                    <input type="text" id="add_desc" name="description" class="form-control" placeholder="Brief product description">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" id="closeAddProduct2">Cancel</button>
                <button type="submit" class="btn btn-primary">✅ Create Product</button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const modal   = document.getElementById('addProductModal');
    const openBtn = document.getElementById('openAddProduct');
    const closeBtn1 = document.getElementById('closeAddProduct');
    const closeBtn2 = document.getElementById('closeAddProduct2');

    function openModal()  { modal.style.display = 'flex'; document.getElementById('add_name').focus(); }
    function closeModal() { modal.style.display = 'none'; }

    openBtn.addEventListener('click', openModal);
    closeBtn1.addEventListener('click', closeModal);
    closeBtn2.addEventListener('click', closeModal);
    modal.addEventListener('click', e => { if (e.target === modal) closeModal(); });
    document.addEventListener('keydown', e => { if (e.key === 'Escape') closeModal(); });

    // Open directly if ?action=new
    <?php if ($action === 'new'): ?> openModal(); <?php endif; ?>
});
</script>
<?php endif; ?>

<?php require_once __DIR__ . '/layout/footer.php'; ?>
