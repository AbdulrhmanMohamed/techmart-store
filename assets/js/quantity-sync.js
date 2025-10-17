/**
 * Global Quantity Synchronization System
 * Ensures quantity changes are synchronized across all pages
 */

class QuantitySync {
    constructor() {
        this.listeners = new Map();
        this.isUpdating = false;
        this.init();
    }

    init() {
        // Listen for storage changes (for localStorage sync)
        window.addEventListener('storage', (e) => {
            if (e.key === 'cart' || e.key === 'quantity_sync') {
                this.handleStorageChange(e);
            }
        });

        // Listen for custom quantity change events
        document.addEventListener('quantityChanged', (e) => {
            this.handleQuantityChange(e.detail);
        });

        // Listen for cart changes
        document.addEventListener('cartChanged', (e) => {
            this.handleCartChange(e.detail);
        });
    }

    /**
     * Register a listener for quantity changes
     * @param {string} productId - Product ID to listen for
     * @param {Function} callback - Callback function to execute
     */
    onQuantityChange(productId, callback) {
        if (!this.listeners.has(productId)) {
            this.listeners.set(productId, []);
        }
        this.listeners.get(productId).push(callback);
    }

    /**
     * Remove a listener
     * @param {string} productId - Product ID
     * @param {Function} callback - Callback function to remove
     */
    offQuantityChange(productId, callback) {
        if (this.listeners.has(productId)) {
            const callbacks = this.listeners.get(productId);
            const index = callbacks.indexOf(callback);
            if (index > -1) {
                callbacks.splice(index, 1);
            }
        }
    }

    /**
     * Emit a quantity change event
     * @param {string} productId - Product ID
     * @param {number} quantity - New quantity
     * @param {string} source - Source of the change (product, cart, wishlist)
     */
    emitQuantityChange(productId, quantity, source = 'unknown') {
        if (this.isUpdating) return;
        
        this.isUpdating = true;
        
        const event = new CustomEvent('quantityChanged', {
            detail: { productId, quantity, source }
        });
        
        document.dispatchEvent(event);
        
        // Update localStorage for cross-tab sync
        const syncData = {
            productId,
            quantity,
            source,
            timestamp: Date.now()
        };
        
        localStorage.setItem('quantity_sync', JSON.stringify(syncData));
        
        setTimeout(() => {
            this.isUpdating = false;
        }, 100);
    }

    /**
     * Handle quantity change events
     * @param {Object} detail - Event detail
     */
    handleQuantityChange(detail) {
        const { productId, quantity, source } = detail;
        
        // Notify all listeners for this product
        if (this.listeners.has(productId)) {
            this.listeners.get(productId).forEach(callback => {
                try {
                    callback(quantity, source);
                } catch (error) {
                    console.error('Error in quantity change callback:', error);
                }
            });
        }
    }

    /**
     * Handle storage changes
     * @param {StorageEvent} e - Storage event
     */
    handleStorageChange(e) {
        if (e.key === 'quantity_sync' && e.newValue) {
            try {
                const syncData = JSON.parse(e.newValue);
                this.handleQuantityChange(syncData);
            } catch (error) {
                console.error('Error parsing quantity sync data:', error);
            }
        }
    }

    /**
     * Handle cart changes
     * @param {Object} detail - Cart change detail
     */
    handleCartChange(detail) {
        const { productId, quantity, action } = detail;
        
        if (action === 'update' || action === 'add') {
            this.emitQuantityChange(productId, quantity, 'cart');
        } else if (action === 'remove') {
            this.emitQuantityChange(productId, 0, 'cart');
        }
    }

    /**
     * Sync quantity from cart data
     * @param {Array} cartItems - Cart items
     */
    syncFromCart(cartItems) {
        cartItems.forEach(item => {
            this.emitQuantityChange(item.id, item.quantity, 'cart');
        });
    }

    /**
     * Get current quantity for a product
     * @param {string} productId - Product ID
     * @returns {number} Current quantity
     */
    getCurrentQuantity(productId) {
        const syncData = localStorage.getItem('quantity_sync');
        if (syncData) {
            try {
                const data = JSON.parse(syncData);
                if (data.productId === productId) {
                    return data.quantity;
                }
            } catch (error) {
                console.error('Error parsing quantity sync data:', error);
            }
        }
        return 1; // Default quantity
    }
}

// Global instance
window.quantitySync = new QuantitySync();

// Helper functions for easy use
window.syncQuantity = (productId, quantity, source = 'unknown') => {
    window.quantitySync.emitQuantityChange(productId, quantity, source);
};

window.onQuantityChange = (productId, callback) => {
    window.quantitySync.onQuantityChange(productId, callback);
};

window.offQuantityChange = (productId, callback) => {
    window.quantitySync.offQuantityChange(productId, callback);
};

// Export for module systems
if (typeof module !== 'undefined' && module.exports) {
    module.exports = QuantitySync;
}



