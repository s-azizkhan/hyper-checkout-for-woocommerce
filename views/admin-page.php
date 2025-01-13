<?php
if (!defined('ABSPATH')) exit;

// Fetch existing checkout links
$links = hcfw_get_links();

?>

<div class="container mt-2 rounded">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="text-black"><i class="fas fa-link"></i> Hyper Checkout Links</h3>
        <button class="btn btn-primary bg-black" data-bs-toggle="modal" data-bs-target="#createLinkModal">
            <i class="fas fa-plus"></i> Create New Link
        </button>
    </div>


    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success position-fixed top-0 end-0 m-3 fade show" role="alert">
            <strong>Success!</strong> Hyper Checkout Link created successfully.
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['deleted'])): ?>
        <div class="alert alert-danger position-fixed top-0 end-0 m-3 fade show" role="alert">
            <strong>Deleted!</strong> Checkout Link removed successfully.
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="table-responsive rounded shadow-sm bg-white p-1">
        <table class="table table-hover table-bordered rounded">
            <thead class="table-dark rounded">
                <tr>
                    <th class="small">Name</th>
                    <th class="small">Link</th>
                    <!-- <th><i class="fas fa-shipping-fast"></i> Free Shipping</th> -->
                    <!-- <th><i class="fas fa-user-lock"></i> Logged In Only</th> -->
                    <th class="small"><i class="fas fa-redo"></i> Use Once</th>
                    <th class="small"><i class="fas fa-chart-line"></i> Used</th>
                    <th class="small">Created At</th>
                    <th class="small">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($links as $link): ?>
                    <tr>
                        <td><?= esc_html($link->link_name); ?></td>
                        <td>
                            <div class="d-flex align-items-center">
                                <span class="hash-text" data-hash="<?= esc_attr($link->link_hash); ?>">
                                    <?= esc_html($link->link_hash); ?>
                                </span>
                                <button class="btn btn-sm btn-light ms-2 copy-btn" data-link="<?= esc_url(site_url('?' . HCFW_LINK_ID . '=' . $link->link_hash));?>" title="Copy Hash">
                                    <i class="fas fa-copy"></i>
                                </button>
                            </div>
                        </td>
                        <!-- TODO: implement those are hidden -->
                        <td><span class="badge <?= $link->config['use_once'] ? 'bg-danger' : 'bg-secondary'; ?>"><?= $link->config['use_once'] ? 'Yes' : 'No'; ?></span></td>
                        <td><span class="badge bg-dark"><?= esc_html($link->usage_count); ?></span></td>
                        <td><?= esc_html($link->created_at); ?></td>
                        <td>
                            <button type="submit" class="btn btn-sm btn-danger delete-link" data-hash="<?= esc_attr($link->link_hash); ?>" title="Delete"><i class="fas fa-trash"></i></button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal for Creating New Link -->
<div class="modal fade" id="createLinkModal" tabindex="-1" aria-labelledby="createLinkModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            
            <!-- Modal Header -->
            <div class="modal-header bg-primary bg-black text-white">
                <h5 class="modal-title"><i class="fas fa-link"></i> Create Hyper Checkout Link</h5>
                <button type="button" class="btn-close text-white bg-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <!-- Modal Body -->
            <div class="modal-body">
                <form method="POST" id="create-link-form">
                    <?php wp_nonce_field('hcfw_nonce'); ?>

                    <!-- Link Name Input -->
                    <div class="mb-3">
                        <label class="fw-bold"><i class="fas fa-edit"></i> Link Name</label>
                        <input type="text" name="link_name" required class="form-control" placeholder="Enter link name">
                    </div>

                    <!-- Products Selection -->
                    <div class="mb-3">
                        <label class="fw-bold"><i class="fas fa-box"></i> Select Products</label>
                        <div id="products-container">
                            
                        </div>
                        <button type="button" class="btn btn-outline-success btn-sm mt-2" id="add-product">
                            <i class="fas fa-plus-circle"></i> Add Another Product
                        </button>
                    </div>

                    <!-- Checkout Options -->
                    <div class="bg-light p-3 rounded checkout-option">
                        <label class="fw-bold mb-3"><i class="fas fa-cogs"></i> Checkout Options</label>
                        <div class="row">
                            <div class="col-md-4"><input type="checkbox" name="free_shipping"> Free Shipping</div>
                            <div class="col-md-4"><input type="checkbox" name="logged_in_only"> Only for Logged-in Users</div>
                            <div class="col-md-4"><input type="checkbox" name="use_once"> Use Once</div>
                        </div>
                    </div>

                    <div class="modal-footer bg-light d-flex justify-content-between">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times"></i> Cancel
                        </button>
                        <button type="submit" class="btn btn-primary bg-black">
                            <i class="fas fa-save"></i> Create Link
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>


<script>
// document.addEventListener('DOMContentLoaded', function () {
//     document.getElementById('add-product').addEventListener('click', function () {
//         let container = document.getElementById('products-container');
//         let clone = container.querySelector('.product-entry').cloneNode(true);
//         container.appendChild(clone);
//     });

//     document.querySelector('#products-container').addEventListener('click', function (event) {
//         if (event.target.closest('.remove-product')) {
//             event.target.closest('.product-entry').remove();
//         }
//     });
// });
</script>
