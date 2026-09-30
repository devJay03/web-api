<div class="row justify-content-center">
  <div class="col-md-8 col-lg-6">
    <div class="card shadow-sm">
      <div class="card-header bg-white py-3">
        <h4 class="card-title mb-0">Edit Product</h4>
      </div>
      <div class="card-body p-4">
        <form action="?url=product/update&id=<?= htmlspecialchars($product['id']) ?>" method="POST">
          <input type="hidden" name="id" value="<?= htmlspecialchars($product['id']) ?>">

          <div class="mb-3">
            <label for="name" class="form-label">Product Name <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="name" name="name"
              value="<?= htmlspecialchars($_SESSION['old']['name'] ?? $product['name']) ?>" required>
          </div>

          <div class="mb-3">
            <label for="category_id" class="form-label">Category <span class="text-danger">*</span></label>
            <select name="category_id" id="category_id" class="form-select" required>
              <option value="">-- Select Category --</option>
              <?php
                $currentCategoryId = $_SESSION['old']['category_id'] ?? $product['category_id'] ?? '';
              ?>
              <?php foreach ($categories as $cat): ?>
                <?php
                  $selected = ($currentCategoryId == $cat['id']) ? 'selected' : '';
                ?>
                <option value="<?= $cat['id'] ?>" <?= $selected ?>>
                  <?= htmlspecialchars($cat['name']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="mb-3">
            <label for="quantity" class="form-label">Quantity <span class="text-danger">*</span></label>
            <input type="number" class="form-control" id="quantity" name="quantity" min="1"
              value="<?= htmlspecialchars($_SESSION['old']['quantity'] ?? $product['quantity']) ?>" required>
          </div>

          <div class="mb-3">
            <label for="price" class="form-label">Price <span class="text-danger">*</span></label>
            <div class="input-group">
              <span class="input-group-text">$</span>
              <input type="number" step="0.01" min="0.01" class="form-control" id="price" name="price"
                value="<?= htmlspecialchars($_SESSION['old']['price'] ?? $product['price']) ?>" required>
            </div>
          </div>

          <div class="d-flex justify-content-between pt-2">
            <a href="?url=product" class="btn btn-secondary">Cancel</a>
            <button type="submit" class="btn btn-primary">Update Product</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<?php unset($_SESSION['old']); ?>