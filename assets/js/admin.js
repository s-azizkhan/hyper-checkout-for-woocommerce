document.addEventListener("DOMContentLoaded", function () {
    document.getElementById("add-product").addEventListener("click", function () {
        let container = document.getElementById("products-container");
        let entry = document.createElement("div");
        entry.classList.add("product-entry", "d-flex", "align-items-center", "gap-2", "mb-2");
        entry.innerHTML = `
            <select class="form-select" name="products[][id]">
                ${document.querySelector(".product-entry select").innerHTML}
            </select>
            <input class="form-control" type="number" name="products[][quantity]" value="1" min="1" placeholder="Quantity">
            <input class="form-control" type="number" name="products[][discount]" value="0" min="0" max="100" placeholder="Discount (%)">
            <button type="button" class="btn btn-danger btn-sm remove-product">-</button>
        `;
        container.appendChild(entry);
    });

    document.addEventListener("click", function (e) {
        if (e.target.classList.contains("remove-product")) {
            e.target.closest(".product-entry").remove();
        }
    });
});
