<div class="card shadow-sm">
  <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
    <h4 class="card-title mb-0">Categories</h4>
    <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
      + Add Category
    </button>
  </div>
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-striped table-hover align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th style="width: 80px;">ID</th>
            <th style="width: 220px;">Name</th>
            <th>Description</th>
            <th style="width: 180px;">Created At</th>
            <th class="text-center" style="width: 180px;">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!empty($categories)): ?>
            <?php foreach ($categories as $cat): ?>
              <tr>
                <td><?= htmlspecialchars($cat['id']) ?></td>
                <td class="fw-semibold"><?= htmlspecialchars($cat['name']) ?></td>
                <td>
                  <?php if (!empty($cat['description'])): ?>
                    <?= htmlspecialchars($cat['description']) ?>
                  <?php else: ?>
                    <span class="text-muted fst-italic">No description</span>
                  <?php endif; ?>
                </td>
                <td><small class="text-muted"><?= htmlspecialchars($cat['created_at'] ?? 'N/A') ?></small></td>
                <td class="text-center">
                  <div class="d-flex justify-content-center gap-2">
                    <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editCategoryModal<?= $cat['id'] ?>">
                      Edit
                    </button>
                    <a href="index.php?url=category/delete&id=<?= $cat['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure you want to delete this category?');">
                      Delete
                    </a>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr>
              <td colspan="5" class="text-center text-muted py-4">No categories found. Click "+ Add Category" to create one.</td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
  <div class="card-footer bg-white text-muted py-2">
    <small>Total Categories: <?= count($categories ?? []) ?></small>
  </div>
</div>

<!-- Modal: Add Category -->
<div class="modal fade" id="addCategoryModal" tabindex="-1" aria-labelledby="addCategoryModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form action="index.php?url=category/store" method="POST">
        <div class="modal-header">
          <h5 class="modal-title" id="addCategoryModalLabel">Add Category</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label for="addCategoryName" class="form-label">Category Name <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="addCategoryName" name="name" placeholder="e.g. Electronics" required>
          </div>
          <div class="mb-3">
            <label for="addCategoryDescription" class="form-label">Description</label>
            <textarea class="form-control" id="addCategoryDescription" name="description" rows="3" placeholder="Brief category description"></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Save Category</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Modals: Edit Category (per row) -->
<?php if (!empty($categories)): ?>
  <?php foreach ($categories as $cat): ?>
    <div class="modal fade" id="editCategoryModal<?= $cat['id'] ?>" tabindex="-1" aria-labelledby="editCategoryModalLabel<?= $cat['id'] ?>" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content">
          <form action="index.php?url=category/update&id=<?= $cat['id'] ?>" method="POST">
            <input type="hidden" name="id" value="<?= $cat['id'] ?>">
            <div class="modal-header">
              <h5 class="modal-title" id="editCategoryModalLabel<?= $cat['id'] ?>">Edit Category</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
              <div class="mb-3">
                <label for="editName<?= $cat['id'] ?>" class="form-label">Category Name <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="editName<?= $cat['id'] ?>" name="name" value="<?= htmlspecialchars($cat['name']) ?>" required>
              </div>
              <div class="mb-3">
                <label for="editDesc<?= $cat['id'] ?>" class="form-label">Description</label>
                <textarea class="form-control" id="editDesc<?= $cat['id'] ?>" name="description" rows="3"><?= htmlspecialchars($cat['description'] ?? '') ?></textarea>
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
              <button type="submit" class="btn btn-primary">Update Category</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  <?php endforeach; ?>
<?php endif; ?>
