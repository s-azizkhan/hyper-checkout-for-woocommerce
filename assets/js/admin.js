// File location: assets/js/admin.js
document.addEventListener('DOMContentLoaded', function () {
    // get the hcfwObj from localized data
    const hcfwObj = window.hcfwObj || {};
    document.querySelectorAll('.copy-btn').forEach(button => {
        button.addEventListener('click', function () {
            let hash = this.getAttribute('data-link');
            navigator.clipboard.writeText(hash).then(() => {
                this.innerHTML = '<i class="fas fa-check text-success"></i>';
                setTimeout(() => this.innerHTML = '<i class="fas fa-copy"></i>', 2000);
            });
        });
    });

    let hcfwSelectProductElement = `<div class="product-entry d-flex align-items-center gap-2 mb-2">
                                <select class="form-select" name="products[][id]">
                                    ${hcfwObj.product_options}
                                </select>
                                <input class="form-control" type="number" name="products[][quantity]" required value="" min="1" placeholder="Qty">
                                <input class="form-control" type="number" name="products[][discount]" required value="" min="0" max="100" placeholder="Discount (%)">
                                <button type="button" class="btn btn-danger btn-sm remove-product" title="Remove Product">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            </div>`;
    addNewProductEntry();


    // Add more product fields dynamically
    document.getElementById('add-product').addEventListener('click', function () {
        addNewProductEntry();
        // let container = document.getElementById('products-container');
        // let clone = container.querySelector('.product-entry').cloneNode(true);
        // container.appendChild();
        // add hcfwSelectProductElement to element
        // container.insertAdjacentHTML('beforeend', hcfwSelectProductElement);
    });

    function addNewProductEntry() {
        let container = document.getElementById('products-container');
        container.insertAdjacentHTML('beforeend', hcfwSelectProductElement);
    }

    // Remove product entry
    document.querySelector('#products-container').addEventListener('click', function (event) {
        if (event.target.closest('.remove-product')) {
            event.target.closest('.product-entry').remove();
        }
    });

    const createLinkForm = document.getElementById('create-link-form');

    createLinkForm.addEventListener('submit', async function (e) {
        e.preventDefault(); // Prevent page reload
        
        //  get the data from form
        let formData = new FormData(createLinkForm);
        formData.append('action', 'hcfw_create_link');
        formData.append('security', hcfwObj.nonce);
        
        let currentStateHtml = e.target.innerHTML;
        // change the button to loading state
        e.target.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Creating...';

        fetch(hcfwObj.ajaxurl, {
            method: 'POST',
            body: formData
        })
            .then(response => response.json())
            .then(data => {
                let alertBox = document.createElement('div');
                alertBox.className = 'alert ' + (data.success ? 'alert-success' : 'alert-danger');
                alertBox.innerHTML = data.data.message;

                document.querySelector('.modal-body').prepend(alertBox);

                if (data.success) {
                    e.target.innerHTML = '';
                    setTimeout(() => {
                        window.location.reload(); // Reload to update table
                    }, 1500);
                    return
                } else {
                    e.target.innerHTML = currentStateHtml;
                    return;
                }
            })
            .catch(error => console.error('Error:', error));
    });

    // Delete link via AJAX
    document.querySelectorAll('.delete-link').forEach(button => {
        button.addEventListener('click', function () {
            let linkHash = this.getAttribute('data-hash');

            // disable the button click
            jQuery(this).prop('disabled', true);

            // change the button to loading state
            this.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Deleting...';
            let formData = new FormData();
            formData.append('action', 'hcfw_delete_link');
            formData.append('delete_link', linkHash);
            formData.append('security', hcfwObj.nonce);

            fetch(hcfwObj.ajaxurl, {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        this.closest('tr').remove(); // Remove row from table
                    }
                })
                .catch(error => console.error('Error:', error));
        });
    });
});
