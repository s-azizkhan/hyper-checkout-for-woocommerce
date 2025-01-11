<?php if (!defined('ABSPATH')) exit; ?>

<div class="container mt-4">
    <h2 class="mb-4">Hyper Checkout Links</h2>

    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success">Hyper Checkout Link created successfully!</div>
    <?php endif; ?>
    
    <?php if (isset($_GET['deleted'])): ?>
        <div class="alert alert-danger">Checkout Link deleted successfully!</div>
    <?php endif; ?>

    <table class="table table-bordered">
        <thead class="table-dark">
            <tr>
                <th>ID</th>
                <th>Link Hash</th>
                <th>Products</th>
                <th>Free Shipping</th>
                <th>Logged In Only</th>
                <th>Use Once</th>
                <th>Total Used</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($links as $link) : ?>
                <tr>
                    <td><?= esc_html($link->id); ?></td>
                    <td>
                        <a href="<?= esc_url(site_url('?hyper_checkout=' . $link->link_hash)); ?>" target="_blank">
                            <?= esc_html($link->link_hash); ?>
                        </a>
                    </td>
                    <td><?= esc_html($link->products); ?></td>
                    <td><?= $link->free_shipping ? 'Yes' : 'No'; ?></td>
                    <td><?= $link->logged_in_only ? 'Yes' : 'No'; ?></td>
                    <td><?= $link->use_once ? 'Yes' : 'No'; ?></td>
                    <td><?= $link->used ?></td>
                    <td>
                        <form method="POST" style="display:inline;">
                            <?php wp_nonce_field('hyper_checkout_nonce'); ?>
                            <input type="hidden" name="delete_link" value="<?= esc_attr($link->id); ?>">
                            <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createLinkModal">Create New Link</button>
</div>

<!-- Bootstrap Modal for Creating New Link -->
<div class="modal fade" id="createLinkModal" tabindex="-1" aria-labelledby="createLinkModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="createLinkModalLabel">Create Hyper Checkout Link</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form method="POST">
                    <?php wp_nonce_field('hyper_checkout_nonce'); ?>
                    <div id="products-container">
                        <label>Select Products:</label><br>
                        <div class="product-entry d-flex align-items-center gap-2 mb-2">
                            <select class="form-select" name="products[][id]">
                                    <?php foreach ($products as $product) : ?>
                                    <option value="<?= esc_attr($product->ID); ?>"><?= esc_html(get_the_title($product->ID)); ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <input class="form-control" type="number" name="products[][quantity]" value="1" min="1" placeholder="Quantity">
                                <input class="form-control" type="number" name="products[][discount]" value="0" min="0" max="100" placeholder="Discount (%)">
                                <button type="button" class="btn btn-danger btn-sm remove-product">-</button>
                            </div>
                    </div>
                    <button type="button" class="btn btn-success" id="add-product">+</button><br>
                    <div class="mt-3">
                        <label><input type="checkbox" name="free_shipping"> Enable Free Shipping</label><br>
                        <label><input type="checkbox" name="logged_in_only"> Only for Logged-in Users</label><br>
                        <label><input type="checkbox" name="use_once"> Use Only Once</label><br>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" name="create_hyper_link" class="btn btn-primary">Create Link</button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
