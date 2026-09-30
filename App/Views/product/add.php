<div class="row justify-content-center">
  <div class="col-md-8 col-lg-6">
    <div class="card shadow-sm">
      <div class="card-header bg-white py-3">
        <h4 class="card-title mb-0">Add Product</h4>
      </div>
      <div class="card-body p-4">
        <form action="?url=product/store" method="POST">
          <div class="mb-3">
            <label for="name" class="form-label">Product Name <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="name" name="name" placeholder="Enter product name"
              value="<?php echo isset($_SESSION['old']['name']) ? htmlspecialchars($_SESSION['old']['name']) : ''; ?>" required>
          </div>

          <div class="mb-3">
            <label for="category_id" class="form-label">Category <span class="text-danger">*</span></label>
            <select name="category_id" id="category_id" class="form-select" required>
              <option value="">-- Select Category --</option>
              <?php foreach ($categories as $cat): ?>
                <?php
                  $selected = (isset($_SESSION['old']['category_id']) && $_SESSION['old']['category_id'] == $cat['id']) ? 'selected' : '';
                ?>
                <option value="<?= $cat['id'] ?>" <?= $selected ?>>
                  <?= htmlspecialchars($cat['name']) ?>
                </option>
              <?php endforeach; ?>
            </select>
            <?php if (empty($categories)): ?>
              <div class="form-text text-danger">
                No categories available. Please <a href="?url=category">create a category</a> first.
              </div>
            <?php endif; ?>
          </div>

          <div class="mb-3">
            <label for="quantity" class="form-label">Quantity <span class="text-danger">*</span></label>
            <input type="number" class="form-control" id="quantity" name="quantity" min="1" placeholder="Enter quantity"
              value="<?php echo isset($_SESSION['old']['quantity']) ? htmlspecialchars($_SESSION['old']['quantity']) : ''; ?>" required>
          </div>

          <div class="mb-3">
            <label for="price" class="form-label">Price <span class="text-danger">*</span></label>
            <div class="input-group">
              <span class="input-group-text">$</span>
              <input type="number" step="0.01" min="0.01" class="form-control" id="price" name="price" placeholder="0.00"
                value="<?php echo isset($_SESSION['old']['price']) ? htmlspecialchars($_SESSION['old']['price']) : ''; ?>" required>
            </div>
          </div>

          <div class="d-flex justify-content-between pt-2">
            <a href="?url=product" class="btn btn-secondary">Cancel</a>
            <button type="submit" class="btn btn-primary" <?= empty($categories) ? 'disabled' : '' ?>>Save Product</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<?php unset($_SESSION['old']); ?>