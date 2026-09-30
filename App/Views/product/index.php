<div class="card shadow-sm">
  <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
    <h4 class="card-title mb-0">Products</h4>
    <a href="?url=product/add" class="btn btn-primary btn-sm">
      + Add Product
    </a>
  </div>
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-striped table-hover align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th style="width: 80px;">ID</th>
            <th>Name</th>
            <th>Category</th>
            <th>Price</th>
            <th>Quantity</th>
            <th class="text-center" style="width: 180px;">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!empty($products)): ?>
            <?php foreach ($products as $product): ?>
              <tr>
                <td><?= htmlspecialchars($product['id']) ?></td>
                <td class="fw-semibold"><?= htmlspecialchars($product['name']) ?></td>
                <td>
                  <?php if (!empty($product['category_name'])): ?>
                    <span class="badge bg-secondary"><?= htmlspecialchars($product['category_name']) ?></span>
                  <?php else: ?>
                    <span class="badge bg-light text-dark border">Uncategorized</span>
                  <?php endif; ?>
                </td>
                <td>$<?= htmlspecialchars(number_format((float)$product['price'], 2)) ?></td>
                <td><?= htmlspecialchars($product['quantity']) ?></td>
                <td class="text-center">
                  <div class="d-flex justify-content-center gap-2">
                    <a href="?url=product/edit&id=<?= $product['id'] ?>" class="btn btn-sm btn-outline-primary">Edit</a>
                    <a href="?url=product/delete&id=<?= $product['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure you want to delete this product?');">Delete</a>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr>
              <td colspan="6" class="text-center text-muted py-4">No products found. Click "+ Add Product" to create one.</td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
  <div class="card-footer bg-white text-muted py-2">
    <small>Total Products: <?= count($products ?? []) ?></small>
  </div>
</div>