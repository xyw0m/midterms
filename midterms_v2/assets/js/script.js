/**
 * Core script for the Point of Sale (POS) functionality.
 */

let cart = {}; // Stores items in the cart: {productId: {name, price, qty, image}}
let productsData = []; // To store the initial product list from PHP

// --- State Management (Using localStorage for persistence) ---

function saveCartToLocalStorage() {
    localStorage.setItem('posCart', JSON.stringify(cart));
}

/**
 * Loads the cart from local storage on initialization.
 */
function loadCartFromLocalStorage() {
    const storedCart = localStorage.getItem('posCart');
    if (storedCart) {
        cart = JSON.parse(storedCart);
    }
}

/**
 * Calculates the total cost of all items in the cart.
 * @returns {number} The total amount.
 */
function calculateTotal() {
    return Object.values(cart).reduce((sum, item) => sum + (item.price * item.qty), 0);
}

// --- Event Listeners and Initialization ---

document.addEventListener('DOMContentLoaded', () => {
    console.log("POS Script Loaded. Initializing...");
    
    // 1. Load data and setup
    loadCartFromLocalStorage();
    
    // 2. Initial UI render
    renderCart();

    // 3. Setup event listeners for the order panel
    document.getElementById('ordered-items-list').addEventListener('change', handleQuantityChange);
    document.getElementById('ordered-items-list').addEventListener('click', handleRemoveItem);
    document.getElementById('clear-order-button').addEventListener('click', clearOrder);
    document.getElementById('pay-button').addEventListener('click', processPayment);
    document.getElementById('amount-paid').addEventListener('input', updateChangeDisplay);
    
    // 4. Attach event listeners to all 'Add to order' buttons (assuming they exist after PHP renders)
    setupProductButtons(); 
});


/**
 * Sets up click listeners for all "Add to order" buttons rendered by PHP.
 * IMPORTANT: This function relies on the buttons having the class .add-to-order-btn
 * AND the data-id attribute for the product ID.
 * The quantity input must also have the ID: qty-[productId]
 */
function setupProductButtons() {
    try {
        // Selector remains '.add-to-order-btn' - ENSURE your PHP adds this class to the button.
        const buttons = document.querySelectorAll('.add-to-order-btn'); 
        
        if (buttons.length === 0) {
            // Log a strong warning if no buttons are found, indicating an HTML mismatch.
            console.error("CRITICAL: No product buttons found! Ensure PHP renders them with class '.add-to-order-btn' and the script is loaded AFTER the products.");
        }

        buttons.forEach(button => {
            // Check to prevent double-binding
            if (button.dataset.listenerAttached) return; 
            button.dataset.listenerAttached = true; 

            button.addEventListener('click', (event) => {
                const buttonElement = event.target;
                
                // CRITICAL DATA POINTS from HTML
                
                // 1. Find the parent product card, which holds the data-id in the PHP structure
                const productCard = buttonElement.closest('.product-card'); 

                if (!productCard) {
                    console.error("Add to Cart failed: Button not wrapped in required '.product-card'.", { target: buttonElement });
                    Swal.fire('Error', 'Product data missing (No card wrapper found).', 'error');
                    return;
                }
                
                // 2. Get the product ID from the card
                const productId = productCard.dataset.id;
                
                if (!productId) {
                    console.error("Add to Cart failed: Missing required HTML attribute data-id on product card.", { productCard });
                    Swal.fire('Error', 'Product data missing. Please check the console.', 'error');
                    return;
                }

                // 3. Get quantity input using the class provided by PHP (.product-qty) relative to the card
                const quantityInput = productCard.querySelector('.product-qty');
                const quantity = quantityInput ? parseInt(quantityInput.value, 10) : 1; 

                // 4. Get Name using the element provided by PHP (h3)
                const productNameElement = productCard.querySelector('h3');
                const productName = productNameElement ? productNameElement.textContent.trim() : `Product ${productId}`;
                
                // 5. Get Price using the class provided by PHP (.price)
                const productPriceElement = productCard.querySelector('.price');
                const productPriceText = productPriceElement ? productPriceElement.textContent.trim() : '0.00';
                
                // Extract number from price text (handles "25.00 PHP" or "PHP 25.00")
                const priceMatch = productPriceText.match(/(\d+\.?\d*)/);
                const productPrice = priceMatch ? parseFloat(priceMatch[1]) : 0;

                if (quantity > 0 && productPrice > 0) {
                    addToCart(productId, productName, productPrice, quantity);
                    if (quantityInput) quantityInput.value = 1; // Reset quantity input after adding
                } else {
                    Swal.fire('Invalid Order', 'Quantity or Price data is invalid.', 'warning');
                    console.error(`Invalid Add Attempt: ID=${productId}, Qty=${quantity}, Price=${productPrice}`);
                }
            });
        });
    } catch (e) {
        console.error("Error setting up product buttons:", e);
    }
}

/**
 * Adds or updates an item in the cart.
 * @param {string} productId - ID of the product.
 * @param {string} name - Name of the product.
 * @param {number} price - Price of the product.
 * @param {number} qty - Quantity to add.
 */
function addToCart(productId, name, price, qty) {
    if (cart[productId]) {
        // Item exists, increase quantity
        cart[productId].qty += qty;
    } else {
        // Item is new, add to cart
        cart[productId] = {
            name: name,
            price: price,
            qty: qty
            // image: item.image // Add image path if available
        };
    }
    saveCartToLocalStorage();
    renderCart();
}

/**
 * Handles quantity changes in the ordered items list.
 * @param {Event} event - The change event from the input field.
 */
function handleQuantityChange(event) {
    if (event.target.classList.contains('ordered-item-qty')) {
        const productId = event.target.dataset.id;
        const newQty = parseInt(event.target.value, 10);

        if (newQty > 0) {
            cart[productId].qty = newQty;
        } else {
            // Remove item if quantity is set to 0 or less
            delete cart[productId];
        }

        saveCartToLocalStorage();
        renderCart();
    }
}

/**
 * Handles the removal of an item from the cart.
 * @param {Event} event - The click event from the remove button.
 */
function handleRemoveItem(event) {
    if (event.target.classList.contains('remove-item-btn')) {
        const productId = event.target.dataset.id;
        delete cart[productId];
        saveCartToLocalStorage();
        renderCart();
    }
}


// --- Display / UI Updates ---

/**
 * Updates the display of ordered items and the total.
 * Also manages the Pay and Clear button enabled/disabled state.
 */
function renderCart() {
    const orderedItemsList = document.getElementById('ordered-items-list');
    const orderTotalSpan = document.getElementById('order-total');
    const total = calculateTotal();
    orderedItemsList.innerHTML = ''; // Clear previous items

    orderTotalSpan.textContent = total.toFixed(2);

    const payButton = document.getElementById('pay-button');
    const clearButton = document.getElementById('clear-order-button');
    const amountPaidInput = document.getElementById('amount-paid');
    
    const cartIsEmpty = Object.keys(cart).length === 0;

    // Control main button states
    payButton.disabled = cartIsEmpty;
    clearButton.disabled = cartIsEmpty;
    amountPaidInput.disabled = cartIsEmpty;

    if (cartIsEmpty) {
        orderedItemsList.innerHTML = '<p class="empty-order-msg text-center text-gray-500 py-4">No items in the order.</p>';
        amountPaidInput.value = '';
    } else {
        for (const productId in cart) {
            const item = cart[productId];
            const itemSubtotal = item.price * item.qty;

            const itemElement = document.createElement('div');
            itemElement.classList.add('ordered-item', 'flex', 'justify-between', 'items-center', 'border-b', 'py-2');
            itemElement.innerHTML = `
                <div class="flex-grow">
                    <span class="item-name font-medium text-gray-800 block">${item.name}</span>
                    <span class="item-price text-sm text-gray-500">@ PHP ${item.price.toFixed(2)}</span>
                </div>
                <div class="flex items-center space-x-2">
                    <input type="number" min="1" value="${item.qty}" class="ordered-item-qty w-16 border rounded text-center p-1" data-id="${productId}">
                    <span class="item-subtotal font-semibold w-20 text-right">PHP ${itemSubtotal.toFixed(2)}</span>
                    <button class="remove-item-btn text-red-500 hover:text-red-700 font-bold p-1 leading-none transition-colors duration-150" data-id="${productId}" title="Remove Item">
                        &times;
                    </button>
                </div>
            `;
            orderedItemsList.appendChild(itemElement);
        }
    }
    
    // Crucial: Call change display function to update visibility and state
    updateChangeDisplay(); 
}

/**
 * Calculates and displays the change due based on the amount paid.
 * Also manages the Pay button disabled state based on total vs paid amount.
 */
function updateChangeDisplay() {
    const total = calculateTotal();
    const paidInput = document.getElementById('amount-paid');
    const payButton = document.getElementById('pay-button');
    // Use || 0 to default to zero if the input is empty or invalid
    const amountPaid = parseFloat(paidInput.value) || 0; 
    const change = amountPaid - total;
    
    const cartIsEmpty = Object.keys(cart).length === 0;

    // Remove old change display
    let changeDisplay = document.getElementById('change-due-display');
    if (changeDisplay) changeDisplay.remove();

    // Create new change display if there are items
    if (!cartIsEmpty) {
        changeDisplay = document.createElement('p');
        changeDisplay.id = 'change-due-display';
        // Apply class based on positive/negative change and set text
        const isSufficient = change >= 0;
        changeDisplay.classList.add('change-display', 'text-sm', 'font-medium', isSufficient ? 'text-green-600' : 'text-red-600');
        changeDisplay.innerHTML = `Change Due: <strong>${change.toFixed(2)} PHP</strong>`;
        
        // Insert it right after the total
        const summaryTotalBox = document.querySelector('.order-summary > .text-4xl'); // Assuming total is in a large text element
        
        // A safer way to find the total container is to look for its ID
        const totalContainer = document.getElementById('order-total').closest('div'); 
        if (totalContainer) {
             // Find the parent of the total display to insert the change display below it
             totalContainer.parentNode.insertBefore(changeDisplay, totalContainer.nextSibling);
        }
    }

    // Disable Pay button if cart is empty OR if paid amount is insufficient
    payButton.disabled = cartIsEmpty || (change < 0);
}


function clearOrder() {
    Swal.fire({
        title: 'Clear Order?',
        text: "This will remove all items from the current order.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, clear it!'
    }).then((result) => {
        if (result.isConfirmed) {
            cart = {};
            saveCartToLocalStorage();
            document.getElementById('amount-paid').value = ''; // Clear amount paid
            renderCart(); // Re-render to clear list and update total/buttons
            Swal.fire(
                'Cleared!',
                'The order has been cleared.',
                'success'
            );
        }
    });
}

/**
 * Validates payment and sends the transaction to the server to be saved.
 */
async function processPayment() {
    if (Object.keys(cart).length === 0) {
        Swal.fire('Empty Order', 'Please add items to the order first.', 'warning');
        return;
    }

    const totalAmount = calculateTotal();
    const amountPaidInput = document.getElementById('amount-paid');
    const amountPaid = parseFloat(amountPaidInput.value);

    // Re-validate funds before processing
    if (isNaN(amountPaid) || amountPaid < totalAmount) {
        Swal.fire({
            icon: 'error',
            title: 'Insufficient Funds',
            html: `Amount paid must be at least <strong>${totalAmount.toFixed(2)} PHP</strong> to cover the order total.`,
        });
        return; // Exit here if funds are insufficient, preventing the server call
    }

    const changeDue = amountPaid - totalAmount;
    
    // Disable pay button to prevent double-click while processing
    const payButton = document.getElementById('pay-button');
    payButton.disabled = true;

    // --- 1. Prepare Data for Server ---
    const transactionData = {
        cart: cart, // Send the full cart details
        total_amount: totalAmount,
        amount_paid: amountPaid,
        change_due: changeDue
    };

    // --- MOCK SERVER RESPONSE (Temporary Bypass) ---
    // Since the actual server file '../api/save_order.php' is unavailable,
    // we mock a successful response to allow testing of the client-side flow.
    try {
        console.log("MOCKING SERVER RESPONSE: Simulating a successful transaction save...");
        
        // Simulate network delay
        await new Promise(resolve => setTimeout(resolve, 500)); 

        const result = { success: true, message: "Mock transaction saved." };
        // End of Mock

        if (result.success) {
            // --- 3. Success: Display Confirmation and Clear POS ---
            Swal.fire({
                icon: 'success',
                title: 'Payment Successful!',
                html: `
                    Total: ${totalAmount.toFixed(2)} PHP<br>
                    Paid: ${amountPaid.toFixed(2)} PHP<br>
                    Change: <strong>${changeDue.toFixed(2)} PHP</strong>
                `,
                confirmButtonText: 'New Order'
            }).then(() => {
                cart = {}; // Clear cart directly
                saveCartToLocalStorage();
                amountPaidInput.value = ''; // Clear amount paid field
                renderCart(); // Re-render to clear list and update total/buttons
            });
        } else {
            // Mock server-side save failed 
            Swal.fire({
                icon: 'error',
                title: 'Transaction Error',
                text: result.message || 'Failed to save transaction history. Check server logs.'
            });
        }

    } catch (e) {
        // This catch block will only execute if the local code inside try fails now.
        Swal.fire({
            icon: 'error',
            title: 'Local Error',
            text: 'An unexpected client-side error occurred during payment processing.'
        });
        console.error('Local error during payment process:', e); 
    } finally {
        // Ensure the cart state correctly determines the pay button state after the transaction attempt
        renderCart(); 
    }
    // --- END MOCK SERVER RESPONSE ---
}
