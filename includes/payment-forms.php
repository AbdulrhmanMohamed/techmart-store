<?php
/**
 * Payment Forms Component
 * Professional payment forms for Stripe, PayPal, and Bank Transfer
 */
?>

<!-- Stripe Payment Form -->
<div id="stripe-payment-form" class="payment-form hidden">
    <div class="space-y-6">
        <div class="flex items-center space-x-3 mb-4">
            <img src="assets/images/stripe-logo.svg" alt="Stripe" class="w-8 h-8">
            <span class="text-lg font-semibold text-primary">Pay with Stripe</span>
        </div>
        
        <!-- Card Information -->
        <div class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-primary mb-2">Card Number</label>
                <div class="relative">
                    <input type="text" id="card-number" placeholder="1234 5678 9012 3456" 
                           class="w-full px-4 py-3 border rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent transition-colors duration-300" 
                           style="background-color: var(--bg-primary); border-color: var(--border-light); color: var(--text-primary);"
                           maxlength="19">
                    <div class="absolute right-3 top-3">
                        <svg class="w-6 h-6 text-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
                        </svg>
                    </div>
                </div>
            </div>
            
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-primary mb-2">Expiry Date</label>
                    <input type="text" id="card-expiry" placeholder="MM/YY" 
                           class="w-full px-4 py-3 border rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent transition-colors duration-300" 
                           style="background-color: var(--bg-primary); border-color: var(--border-light); color: var(--text-primary);"
                           maxlength="5">
                </div>
                <div>
                    <label class="block text-sm font-medium text-primary mb-2">CVC</label>
                    <input type="text" id="card-cvc" placeholder="123" 
                           class="w-full px-4 py-3 border rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent transition-colors duration-300" 
                           style="background-color: var(--bg-primary); border-color: var(--border-light); color: var(--text-primary);"
                           maxlength="4">
                </div>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-primary mb-2">Cardholder Name</label>
                <input type="text" id="cardholder-name" placeholder="John Doe" 
                       class="w-full px-4 py-3 border rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent transition-colors duration-300" 
                       style="background-color: var(--bg-primary); border-color: var(--border-light); color: var(--text-primary);">
            </div>
        </div>
        
        <!-- Test Card Information -->
        <div class="bg-info/10 border border-info/20 rounded-lg p-4">
            <h4 class="font-medium text-info mb-2">Test Card Information</h4>
            <div class="text-sm text-info space-y-1">
                <p><strong>Visa:</strong> 4242 4242 4242 4242</p>
                <p><strong>Mastercard:</strong> 5555 5555 5555 4444</p>
                <p><strong>American Express:</strong> 3782 822463 10005</p>
                <p><strong>Any future date:</strong> 12/25</p>
                <p><strong>Any 3 digits:</strong> 123</p>
            </div>
        </div>
        
        <!-- Security Notice -->
        <div class="flex items-center space-x-2 text-sm text-muted">
            <svg class="w-4 h-4 text-success" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
            </svg>
            <span>Your payment information is secure and encrypted</span>
        </div>
    </div>
</div>

<!-- PayPal Payment Form -->
<div id="paypal-payment-form" class="payment-form hidden">
    <div class="space-y-6">
        <div class="flex items-center space-x-3 mb-4">
            <img src="https://www.paypalobjects.com/webstatic/mktg/logo/pp_cc_mark_37x23.jpg" alt="PayPal" class="w-8 h-8">
            <span class="text-lg font-semibold text-primary">Pay with PayPal</span>
        </div>
        
        <!-- PayPal Button -->
        <div id="paypal-button-container" class="w-full">
            <button id="paypal-button" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-4 px-6 rounded-lg transition-colors duration-300 flex items-center justify-center space-x-3">
                <svg class="w-6 h-6" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M7.076 21.337H2.47a.641.641 0 0 1-.633-.74L4.944.901C5.026.382 5.474 0 5.998 0h7.46c2.57 0 4.578.543 5.69 1.81 1.01 1.15 1.304 2.42 1.012 4.287-.023.143-.047.288-.077.437-.983 5.05-4.349 6.797-8.647 6.797h-2.19c-.524 0-.968.382-1.05.9l-1.12 7.106zm14.146-14.42a3.35 3.35 0 0 0-.105-.633c-.077-.344-.18-.684-.308-1.02-.715-1.805-1.304-2.677-2.126-3.129-.673-.302-1.548-.448-2.655-.448H8.89c-.524 0-.968.382-1.05.9L5.163 20.597h4.61l1.12-7.106c.082-.518.526-.9 1.05-.9h2.19c4.298 0 7.664-1.747 8.647-6.797.03-.149.054-.294.077-.437.292-1.867-.002-3.137-1.012-4.287z"/>
                </svg>
                <span>Pay with PayPal</span>
            </button>
        </div>
        
        <!-- PayPal Test Information -->
        <div class="bg-info/10 border border-info/20 rounded-lg p-4">
            <h4 class="font-medium text-info mb-2">PayPal Test Mode</h4>
            <div class="text-sm text-info space-y-1">
                <p><strong>Test Account:</strong> sb-test@paypal.com</p>
                <p><strong>Password:</strong> test123456</p>
                <p><strong>Note:</strong> This is a sandbox environment for testing</p>
            </div>
        </div>
        
        <!-- Security Notice -->
        <div class="flex items-center space-x-2 text-sm text-muted">
            <svg class="w-4 h-4 text-success" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
            </svg>
            <span>PayPal protects your financial information</span>
        </div>
    </div>
</div>

<!-- Bank Transfer Payment Form -->
<div id="bank-transfer-payment-form" class="payment-form hidden">
    <div class="space-y-6">
        <div class="flex items-center space-x-3 mb-4">
            <svg class="w-8 h-8 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
            </svg>
            <span class="text-lg font-semibold text-primary">Bank Transfer</span>
        </div>
        
        <!-- Bank Transfer Information -->
        <div class="bg-tertiary rounded-lg p-6">
            <h4 class="font-medium text-primary mb-4">Transfer Instructions</h4>
            <div class="space-y-3 text-sm text-muted">
                <div class="flex justify-between">
                    <span>Bank Name:</span>
                    <span class="font-medium text-primary">TechMart Bank</span>
                </div>
                <div class="flex justify-between">
                    <span>Account Number:</span>
                    <span class="font-medium text-primary">1234567890</span>
                </div>
                <div class="flex justify-between">
                    <span>Routing Number:</span>
                    <span class="font-medium text-primary">021000021</span>
                </div>
                <div class="flex justify-between">
                    <span>SWIFT Code:</span>
                    <span class="font-medium text-primary">TMBKUS33</span>
                </div>
                <div class="flex justify-between">
                    <span>Amount:</span>
                    <span class="font-medium text-primary" id="bank-transfer-amount">$0.00</span>
                </div>
                <div class="flex justify-between">
                    <span>Reference:</span>
                    <span class="font-medium text-primary" id="bank-transfer-reference">ORD-00000000-0000</span>
                </div>
            </div>
        </div>
        
        <!-- Transfer Confirmation -->
        <div class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-primary mb-2">Transfer Reference Number</label>
                <input type="text" id="transfer-reference" placeholder="Enter your bank transfer reference number" 
                       class="w-full px-4 py-3 border rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent transition-colors duration-300" 
                       style="background-color: var(--bg-primary); border-color: var(--border-light); color: var(--text-primary);">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-primary mb-2">Transfer Date</label>
                <input type="date" id="transfer-date" 
                       class="w-full px-4 py-3 border rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent transition-colors duration-300" 
                       style="background-color: var(--bg-primary); border-color: var(--border-light); color: var(--text-primary);">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-primary mb-2">Additional Notes (Optional)</label>
                <textarea id="transfer-notes" rows="3" placeholder="Any additional information about your transfer..." 
                          class="w-full px-4 py-3 border rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent transition-colors duration-300" 
                          style="background-color: var(--bg-primary); border-color: var(--border-light); color: var(--text-primary);"></textarea>
            </div>
        </div>
        
        <!-- Processing Time Notice -->
        <div class="bg-warning/10 border border-warning/20 rounded-lg p-4">
            <h4 class="font-medium text-warning mb-2">Processing Time</h4>
            <div class="text-sm text-warning">
                <p>Bank transfers typically take 1-3 business days to process. Your order will be confirmed once the payment is received.</p>
            </div>
        </div>
    </div>
</div>

<script>
// Payment form handling
document.addEventListener('DOMContentLoaded', function() {
    // Card number formatting
    const cardNumberInput = document.getElementById('card-number');
    if (cardNumberInput) {
        cardNumberInput.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\s/g, '').replace(/[^0-9]/gi, '');
            let formattedValue = value.match(/.{1,4}/g)?.join(' ') || value;
            e.target.value = formattedValue;
        });
    }
    
    // Expiry date formatting
    const cardExpiryInput = document.getElementById('card-expiry');
    if (cardExpiryInput) {
        cardExpiryInput.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length >= 2) {
                value = value.substring(0, 2) + '/' + value.substring(2, 4);
            }
            e.target.value = value;
        });
    }
    
    // CVC formatting
    const cardCvcInput = document.getElementById('card-cvc');
    if (cardCvcInput) {
        cardCvcInput.addEventListener('input', function(e) {
            e.target.value = e.target.value.replace(/[^0-9]/g, '');
        });
    }
    
    // Set today's date as default for transfer date
    const transferDateInput = document.getElementById('transfer-date');
    if (transferDateInput) {
        transferDateInput.value = new Date().toISOString().split('T')[0];
    }
});

// Show/hide payment forms
function showPaymentForm(paymentMethod) {
    // Hide all payment forms
    document.querySelectorAll('.payment-form').forEach(form => {
        form.classList.add('hidden');
    });
    
    // Show selected payment form
    const formId = paymentMethod + '-payment-form';
    const form = document.getElementById(formId);
    if (form) {
        form.classList.remove('hidden');
    }
    
    // Update bank transfer information
    if (paymentMethod === 'bank_transfer') {
        updateBankTransferInfo();
    }
}

// Update bank transfer information
function updateBankTransferInfo() {
    const amountElement = document.getElementById('bank-transfer-amount');
    const referenceElement = document.getElementById('bank-transfer-reference');
    
    if (amountElement && referenceElement) {
        // Get total from order summary
        const totalElement = document.querySelector('.order-total');
        if (totalElement) {
            amountElement.textContent = totalElement.textContent;
        }
        
        // Generate reference number
        const reference = 'ORD-' + Date.now().toString().slice(-8) + '-' + Math.floor(Math.random() * 10000).toString().padStart(4, '0');
        referenceElement.textContent = reference;
    }
}

// PayPal button handler
function initializePayPal() {
    // Simulate PayPal SDK initialization
    const paypalButton = document.getElementById('paypal-button');
    if (paypalButton) {
        paypalButton.addEventListener('click', function() {
            // Simulate PayPal payment
            const paypalOrderId = 'PAY-' + Date.now().toString();
            
            // Store PayPal order ID for processing
            window.paypalOrderId = paypalOrderId;
            
            // Show success message
            showNotification('PayPal payment initiated successfully!', 'success');
        });
    }
}

// Initialize PayPal when page loads
document.addEventListener('DOMContentLoaded', function() {
    initializePayPal();
});
</script>
