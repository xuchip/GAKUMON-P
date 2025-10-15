(() => {
  'use strict';
  // local (file-scoped) only, no global collision
  const isMobile = (window.__GAK_IS_MOBILE__ ?? (window.innerWidth <= 768));

// Touch-optimized post function
async function postForm(url, data) {
  const form = new FormData();
  Object.entries(data).forEach(([k,v]) => form.append(k, v));
  
  try {
    const res = await fetch(url, { method: 'POST', body: form });
    let json;
    try { json = await res.json(); } catch { json = { ok:false, error:'Bad JSON' }; }

    // Auto-refresh from DB truth after successful buy/feed
    const isMutating = typeof url === 'string' && (
      url.includes('gakumonFeed.inc.php') || url.includes('gakumonBuy.inc.php')
    );
    if (isMutating && json?.ok) {
      // Let the UI show your optimistic update first, then snap to DB truth
      setTimeout(() => { refreshStateFromServer(); }, 0);
    }

    return json;
  } catch (error) {
    console.warn('Network error:', error);
    showFeedback('Network error - please check connection', '#dc3545');
    return { ok: false, error: 'Network error' };
  }
}

// State refresh with mobile optimization
async function refreshStateFromServer() {
  try {
    const res = await fetch('include/gakumonState.inc.php', { method: 'GET' });
    const data = await res.json();
    if (!data?.ok) return;

    // sync coins
    if (typeof data.currency === 'number') {
      gameData.currency = Number(data.currency);
      if (typeof updateCurrencyDisplay === 'function') updateCurrencyDisplay();
    }

    // sync pet energy
    if (data.pet && typeof data.pet.energy === 'number') {
      gameData.pet.energy = Number(data.pet.energy);
      if (typeof updateEnergyBar === 'function') updateEnergyBar();
    }

    // sync inventory quantities / ownership
    if (Array.isArray(data.inventory)) {
      // Map by id for fast lookup
      const fresh = new Map(data.inventory.map(i => [Number(i.id), i]));

      // Update existing items, add missing ones, remove zero-qty foods
      gameData.inventory = gameData.inventory
        .map(old => {
          const newer = fresh.get(old.id);
          if (!newer) return old;
          return {
            ...old,
            owned: Number(newer.owned ?? old.owned),
            // keep equipped flag from client, but you can also take server's
          };
        });

      // Add items that exist on server but not locally (edge cases)
      data.inventory.forEach(item => {
        if (!gameData.inventory.find(i => i.id === Number(item.id))) {
          gameData.inventory.push({
            id: Number(item.id),
            name: item.name,
            type: normalizeType(item.type),
            price: Number(item.price ?? 0),
            icon: item.icon ?? '',
            accessory_image_url: item.accessory_image_url,
            energy_restore: item.energy_restore !== null && item.energy_restore !== undefined
              ? Number(item.energy_restore) : null,
            owned: Number(item.owned ?? 1),
            equipped: Boolean(item.equipped ?? false),
          });
        }
      });

      // Remove foods that the server says are 0 (in case our optimistic UI decremented)
      gameData.inventory = gameData.inventory.filter(it => !(it.type === 'food' && it.owned <= 0));

      if (typeof renderInventoryItems === 'function') renderInventoryItems();
      if (typeof renderShopItems === 'function') renderShopItems();
    }
  } catch (e) {
    console.warn('refreshStateFromServer failed', e);
  }
}

function normalizeType(t) {
  if (!t) return t;
  t = String(t).toLowerCase().trim();
  if (t === 'accessory') return 'accessories';
  if (t === 'wallpaper') return 'decorations';
  return t; // 'food', 'toys', etc.
}

// Build data from PHP
const shopItemsFromServer = Array.isArray(window.serverData?.shopItems) ? window.serverData.shopItems : [];
const petFromServer = window.serverData?.pet;
const inventoryFromServer = Array.isArray(window.serverData?.inventory) ? window.serverData.inventory : [];

const gameData = {
  currency: Number(window.serverData?.currency ?? 0),

  pet: petFromServer ? {
    name: petFromServer.name,
    type: petFromServer.type,
    level: 1, // no level in DB yet
    age: petFromServer.age,
    energy: petFromServer.energy,
    maxEnergy: petFromServer.maxEnergy,
    equipped: { accessories: [], decorations: [] }
  } : {
    name: "MOHI", type: "CHRIX", level: 1, age: 0, energy: 100, maxEnergy: 100,
    equipped: { accessories: [], decorations: [] }
  },

  inventory: inventoryFromServer.map(i => ({
    id: Number(i.id),
    name: i.name,
    type: normalizeType(i.type),
    price: Number(i.price ?? 0),
    icon: i.icon ?? '',
    accessory_image_url: i.accessory_image_url,
    energy_restore: i.energy_restore !== null && i.energy_restore !== undefined
        ? Number(i.energy_restore) : null,
    owned: Number(i.owned ?? 1),
    equipped: Boolean(i.equipped ?? false),
  })),

  // Use DB shop items (already mapped to 'food' | 'accessories' | 'decorations')
    shopItems: shopItemsFromServer.map(i => ({
    id: Number(i.id),
    name: i.name,
    type: normalizeType(i.type),
    price: Number(i.price),
    icon: i.icon,
    accessory_image_url: i.accessory_image_url,
    energy_restore: i.energy_restore !== null ? Number(i.energy_restore) : null
    }))
};

// DOM Elements
let categoryTabs, shopCategoryTabs, itemsScrollable, shopModal, openShopBtn, closeShopBtn;
let shopItemsGrid, currencyDisplay, energyFill, accessoryLayers, decorationLayers;

// Current selected categories
let currentCategory = 'food';
let currentShopCategory = 'all';

// Initialize Game when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    initializeElements();
    initGame();
    setupMobileOptimizations();
});

function initializeElements() {
    categoryTabs = document.querySelectorAll('.category-tab');
    shopCategoryTabs = document.querySelectorAll('.shop-category-tab');
    itemsScrollable = document.getElementById('itemsScrollable');
    shopModal = document.getElementById('shopModal');
    openShopBtn = document.getElementById('openShop');
    closeShopBtn = document.getElementById('closeShop');
    shopItemsGrid = document.getElementById('shopItemsGrid');
    currencyDisplay = document.getElementById('currencyAmount');
    energyFill = document.getElementById('energyFill');
    accessoryLayers = document.getElementById('accessoryLayers');
    decorationLayers = document.getElementById('decorationLayers');
}

// Mobile-specific optimizations
function setupMobileOptimizations() {
    if (!isMobile) return;
    
    // Prevent zoom on input focus
    document.addEventListener('touchstart', function() {}, {passive: true});
    
    // Better touch handling for all interactive elements
    const interactiveElements = document.querySelectorAll('button, .item-card, .category-tab, .shop-category-tab');
    interactiveElements.forEach(element => {
        element.addEventListener('touchstart', function() {
            this.style.transform = 'scale(0.95)';
            this.style.transition = 'transform 0.1s ease';
        });
        
        element.addEventListener('touchend', function() {
            this.style.transform = 'scale(1)';
            this.style.transition = 'transform 0.2s ease';
        });
        
        element.addEventListener('touchcancel', function() {
            this.style.transform = 'scale(1)';
            this.style.transition = 'transform 0.2s ease';
        });
    });
    
    // Handle orientation changes
    window.addEventListener('orientationchange', function() {
        setTimeout(() => {
            window.scrollTo(0, 0);
            // Re-render to ensure proper layout
            renderInventoryItems();
            renderShopItems();
        }, 100);
    });
}

// Initialize Game
function applyPetImage() {
  const img = document.querySelector('#petImage img');
  if (img && window.serverData?.pet?.imageUrl) {
    img.src = window.serverData.pet.imageUrl;
    img.alt = gameData.pet.name;
  }
}

function initGame() {
    updateCurrencyDisplay();
    updateEnergyBar();
    renderInventoryItems();
    renderShopItems();
    applyPetImage();
    updateFloatingPetInfo();
    updateAccessoryDisplay(); // Render equipped accessories on pet
    updateDecorationDisplay(); // Render equipped decorations in room
    setupEventListeners();
}

// For Pet Data Display
function updateFloatingPetInfo() {
  const box = document.querySelector('.floating-pet-info');
  if (!box || !window.serverData?.pet) return;

  const pet = window.serverData.pet;
  const nameEl = box.querySelector('.pet-name');
  const typeEl = box.querySelector('.pet-type');
  const ageEl = box.querySelector('.pet-age');
  const energyEl = box.querySelector('.pet-energy');
  const imgEl = box.querySelector('.pet-image');

  if (nameEl) nameEl.textContent = pet.name;
  if (ageEl) ageEl.textContent = `${pet.age} days old`;
  if (energyEl) energyEl.textContent = `${pet.energy}/${pet.maxEnergy}`;
  if (imgEl) imgEl.src = pet.imageUrl;
}

// Update Currency Display
function updateCurrencyDisplay() {
    if (currencyDisplay) {
        currencyDisplay.textContent = gameData.currency.toLocaleString();
    }
}

// Update Energy Bar
function updateEnergyBar() {
    if (energyFill) {
        const energyPercentage = (gameData.pet.energy / gameData.pet.maxEnergy) * 100;
        energyFill.style.width = `${energyPercentage}%`;
    }
}

// ===== ACCESSORY LAYERING SYSTEM =====

// Update accessory display on pet
function updateAccessoryDisplay() {
    if (!accessoryLayers) return;
    
    // Clear existing accessory layers
    accessoryLayers.innerHTML = '';
    
    // Get all equipped accessories
    const equippedAccessories = gameData.inventory.filter(item => 
        item.type === 'accessories' && item.equipped
    );
    
    if (equippedAccessories.length === 0) {
        return;
    }
    
    // Get the user's ACTUAL current pet name from server data
    const currentPetName = window.serverData.pet.type; // This gets the actual equipped pet
    
    // Create and append accessory layers
    equippedAccessories.forEach((accessory, index) => {
        const accessoryLayer = document.createElement('img');
        accessoryLayer.className = `accessory-layer`;
        
        // BUILD DYNAMIC PATH: IMG/Accessories/(UserPetName)/(accessory_file)
        const dynamicPath = `IMG/Accessories/${currentPetName}/${accessory.accessory_image_url}`;
        accessoryLayer.src = dynamicPath;
        accessoryLayer.alt = accessory.name;
        accessoryLayer.title = accessory.name;
        
        // Style to cover entire pet area
        accessoryLayer.style.width = '100%';
        accessoryLayer.style.height = '100%';
        accessoryLayer.style.objectFit = 'contain';
        accessoryLayer.style.position = 'absolute';
        accessoryLayer.style.top = '0';
        accessoryLayer.style.left = '0';
        accessoryLayer.style.pointerEvents = 'none';
        
        // Simple z-index stacking
        accessoryLayer.style.zIndex = 10 + index;
        
        // Add error handling for image loading
        accessoryLayer.onerror = function() {
            console.error('❌ Failed to load pet-specific accessory:', this.src);
            // Fallback to generic accessory if pet-specific doesn't exist
            const fallbackPath = `IMG/Accessories/${accessory.accessory_image_url}`;
            this.src = fallbackPath;
        };
        
        accessoryLayers.appendChild(accessoryLayer);
    });
}

// ===== DECORATION POSITIONING SYSTEM =====

// Update decoration display in room
function updateDecorationDisplay() {
    if (!decorationLayers) return;
    
    // Clear existing decoration layers
    decorationLayers.innerHTML = '';
    
    // Get all equipped decorations
    const equippedDecorations = gameData.inventory.filter(item => 
        item.type === 'decorations' && item.equipped
    );
    
    // Create and append decoration layers
    equippedDecorations.forEach(decoration => {
        const decorationLayer = document.createElement('img');
        decorationLayer.className = `decoration-layer decoration-${decoration.id}`;
        decorationLayer.src = decoration.image;
        decorationLayer.alt = decoration.name;
        decorationLayer.title = decoration.name;
        
        // Set custom position and size for each decoration
        if (decoration.position) {
            decorationLayer.style.position = 'absolute';
            decorationLayer.style.left = `${decoration.position.x}px`;
            decorationLayer.style.top = `${decoration.position.y}px`;
        }
        
        if (decoration.size) {
            decorationLayer.style.width = `${decoration.size.width}px`;
            decorationLayer.style.height = `${decoration.size.height}px`;
        }
        
        // Decorations should be above room background but below pet
        decorationLayer.style.zIndex = '8';
        decorationLayer.style.pointerEvents = 'none';
        decorationLayer.style.objectFit = 'contain';
        
        decorationLayers.appendChild(decorationLayer);
    });
}

// Toggle equip/unequip for accessories and decorations
function toggleEquipItem(itemId) {
    const item = gameData.inventory.find(i => i.id === itemId);
    if (!item || item.owned === 0) return;

    // For accessories, allow multiple to be equipped at once!
    if (item.type === 'accessories') {
        // Just toggle this specific accessory
        item.equipped = !item.equipped;
    } 
    // For decorations, also allow multiple to be equipped
    else if (item.type === 'decorations') {
        item.equipped = !item.equipped;
    }

    // Update equipped items in pet data
    if (item.equipped) {
        if (!gameData.pet.equipped[item.type].includes(itemId)) {
            gameData.pet.equipped[item.type].push(itemId);
        }
    } else {
        gameData.pet.equipped[item.type] = gameData.pet.equipped[item.type].filter(id => id !== itemId);
    }

    renderInventoryItems();
    
    // Update the appropriate display
    if (item.type === 'accessories') {
        updateAccessoryDisplay();
    } else if (item.type === 'decorations') {
        updateDecorationDisplay();
    }
    
    showEquipFeedback(item.name, item.equipped);
}

// Render Inventory Items based on selected category
function renderInventoryItems() {
    if (!itemsScrollable) return;
    
    itemsScrollable.innerHTML = '';
    
    const filteredItems = gameData.inventory.filter(item => {
        if (currentCategory === 'all') return true;
        return item.type === currentCategory;
    });

    if (filteredItems.length === 0) {
        const emptyMessage = document.createElement('div');
        emptyMessage.className = 'empty-category';
        if (currentCategory === 'food') {
            emptyMessage.textContent = 'No food items in your inventory. Visit the store to buy some!';
        } else {
            emptyMessage.textContent = `No ${currentCategory} items in your inventory. Visit the store to buy some!`;
        }
        itemsScrollable.appendChild(emptyMessage);
        return;
    }

    filteredItems.forEach(item => {
        const itemCard = document.createElement('div');
        itemCard.className = `item-card ${item.equipped ? 'equipped' : ''}`;
        
        let actionText = 'Tap to use';
        let ownedText = `${item.owned}`;
        
        if (item.type === 'accessories' || item.type === 'decorations') {
            actionText = item.equipped ? 'EQUIPPED - Tap to remove' : 'Tap to equip';
            // For accessories/decorations, we only own 1, so no count needed
            ownedText = 'Owned';
        }
        
        itemCard.innerHTML = `
            <div class="item-owned">${ownedText}</div>
            <div class="item-icon">${item.icon}</div>
            <div class="item-name">${item.name}</div>
            <div class="item-price">${item.price} Gakucoins</div>
            <div class="item-owned-use">${actionText}</div>
        `;

        // Mobile: Add touch feedback
        if (isMobile) {
            itemCard.addEventListener('touchstart', function() {
                this.style.transform = 'scale(0.95)';
            });
            itemCard.addEventListener('touchend', function() {
                this.style.transform = 'scale(1)';
            });
        }

        // Add click handlers based on item type
        if (item.type === 'accessories' || item.type === 'decorations') {
            itemCard.addEventListener('click', () => toggleEquipItem(item.id));
        } else if (item.type === 'food') {
            itemCard.addEventListener('click', () => useFoodItem(item.id));
        }

        itemsScrollable.appendChild(itemCard);
    });
}

// Render Shop Items based on selected category - UPDATED: HIDE OWNED ACCESSORIES/DECORATIONS
function renderShopItems() {
    if (!shopItemsGrid) return;
    
    shopItemsGrid.innerHTML = '';
    
    const filteredItems = gameData.shopItems.filter(item => {
        // First filter by category
        if (currentShopCategory !== 'all' && item.type !== currentShopCategory) {
            return false;
        }
        
        // For accessories and decorations: hide if already owned
        if (item.type === 'accessories' || item.type === 'decorations') {
            const ownedItem = gameData.inventory.find(invItem => invItem.id === item.id);
            return !ownedItem || ownedItem.owned === 0;
        }
        
        // For food and toys: always show (can buy multiple)
        return true;
    });

    if (filteredItems.length === 0) {
        const emptyMessage = document.createElement('div');
        emptyMessage.className = 'empty-category';
        emptyMessage.textContent = `No ${currentShopCategory} items available to purchase.`;
        shopItemsGrid.appendChild(emptyMessage);
        return;
    }

    filteredItems.forEach(item => {
        const shopItem = document.createElement('div');
        shopItem.className = 'shop-item';
        shopItem.innerHTML = `
            <div class="shop-item-icon">${item.icon}</div>
            <div class="shop-item-name">${item.name}</div>
            <div class="shop-item-price">${item.price} Gakucoins</div>
            <button class="buy-button" data-item-id="${item.id}" 
                    ${gameData.currency < item.price ? 'disabled' : ''}>
                Buy
            </button>
        `;

        // Mobile: Add touch feedback to shop items
        if (isMobile) {
            shopItem.addEventListener('touchstart', function() {
                this.style.transform = 'scale(0.98)';
            });
            shopItem.addEventListener('touchend', function() {
                this.style.transform = 'scale(1)';
            });
        }

        shopItemsGrid.appendChild(shopItem);
    });

    // Re-attach buy button event listeners
    attachBuyButtonListeners();
}

// Attach event listeners to buy buttons (separate function for re-rendering)
function attachBuyButtonListeners() {
    const buyButtons = document.querySelectorAll('.buy-button');
    buyButtons.forEach(button => {
        const itemId = button.getAttribute('data-item-id');
        if (itemId) {
            // Remove existing listeners and add new one
            button.replaceWith(button.cloneNode(true));
            const newButton = document.querySelector(`.buy-button[data-item-id="${itemId}"]`);
            newButton.addEventListener('click', () => buyItem(parseInt(itemId)));
            
            // Mobile touch feedback
            if (isMobile) {
                newButton.addEventListener('touchstart', function() {
                    this.style.transform = 'scale(0.9)';
                });
                newButton.addEventListener('touchend', function() {
                    this.style.transform = 'scale(1)';
                });
            }
        }
    });
}

// Use food item - UPDATED TO REMOVE FOOD WHEN COUNT REACHES 0
function useFoodItem(itemId) {
  const item = gameData.inventory.find(i => i.id === itemId);
  if (!item || item.owned === 0) return;

  // Decrement local inventory first for snappy UI
  item.owned--;

  // Use energy_restore from shop definition (falls back to 10)
  const shopDef = gameData.shopItems.find(s => s.id === itemId);
  const energyGain = Number(shopDef?.energy_restore ?? 10);

  gameData.pet.energy = Math.min(gameData.pet.energy + energyGain, gameData.pet.maxEnergy);
  updateEnergyBar();
  renderInventoryItems();
  showFoodFeedback(item.name);

  // Persist to DB (URL per step A)
  postForm('include/gakumonFeed.inc.php', { item_id: itemId })
    .then(resp => {
      if (resp?.ok) {
        // trust server truth
        gameData.pet.energy = Number(resp.energy);
        updateEnergyBar();

        // sync local inventory with server remaining count
        const inv = gameData.inventory.find(i => i.id === itemId);
        if (inv && resp.remaining <= 0) {
          gameData.inventory = gameData.inventory.filter(i => !(i.id === itemId && i.type === 'food'));
          renderInventoryItems();
        }
        showFeedback(`Fed +${resp.energy_gain} energy`, '#ffc107');
      } else {
        console.warn(resp?.error || 'Feed failed');
        // (optional) revert local change if strict consistency is needed
      }
    })
    .catch(() => console.warn('Network error (feed)'));

  // Local clean-up when hits zero
  if (item.owned === 0) {
    gameData.inventory = gameData.inventory.filter(i => !(i.id === itemId && i.type === 'food'));
    renderInventoryItems();
  }
}

// Buy Item Function - UPDATED: ACCESSORIES/DECORATIONS CAN ONLY BE OWNED ONCE
function buyItem(itemId) {
    const item = gameData.shopItems.find(i => i.id === itemId);
    if (!item) return;

    if (gameData.currency >= item.price) {
        gameData.currency -= item.price;
        
        // Check if item already exists in inventory
        const existingItem = gameData.inventory.find(i => i.id === itemId);
        
        if (existingItem) {
            // For food: increase count
            if (item.type === 'food') {
                existingItem.owned++;
            }
            // For accessories/decorations: can only own one, so do nothing
        } else {
            // Create new inventory item
            const newItem = {
                ...item,
                owned: 1,
                equipped: false
            };
            gameData.inventory.push(newItem);
        }

        updateCurrencyDisplay();

        postForm('include/gakumonBuy.inc.php', { item_id: itemId })
        .then(resp => {
            if (resp?.ok) {
            gameData.currency = Number(resp.currency);
            updateCurrencyDisplay();
            // If accessory/wallpaper was already owned on server, you might want to re-hide from shop:
            renderShopItems();
            } else {
            console.warn(resp?.error || 'Purchase failed');
            // Optional: revert local change if you want strict consistency
            }
        })
        .catch(() => console.warn('Network error (buy)'));

        renderInventoryItems();
        renderShopItems(); // Re-render shop to hide purchased accessories/decorations
        
        showPurchaseFeedback(item.name);
    } else {
        showFeedback('Not enough Gakucoins!', '#dc3545');
    }
}

// Show Purchase Feedback
function showPurchaseFeedback(itemName) {
    showFeedback(`Purchased ${itemName}!`, '#28a745');
}

// Show Equip Feedback
function showEquipFeedback(itemName, equipped) {
    const action = equipped ? 'equipped' : 'unequipped';
    showFeedback(`${itemName} ${action}!`, '#007bff');
}

// Show Food Feedback
function showFoodFeedback(itemName) {
    showFeedback(`Fed ${itemName}`, '#ffc107');
}

// Generic Feedback Function - Mobile optimized
function showFeedback(message, color) {
    const feedback = document.createElement('div');
    feedback.className = 'feedback-message';
    feedback.style.background = color;
    feedback.style.fontSize = isMobile ? '16px' : '18px';
    feedback.style.padding = isMobile ? '15px 30px' : '20px 40px';
    feedback.textContent = message;
    document.body.appendChild(feedback);

    setTimeout(() => {
        if (document.body.contains(feedback)) {
            document.body.removeChild(feedback);
        }
    }, 2000);
}

// Setup Event Listeners - Mobile optimized
function setupEventListeners() {
    // Inventory category tabs
    categoryTabs.forEach(tab => {
        // Mobile: Add touch feedback
        if (isMobile) {
            tab.addEventListener('touchstart', function() {
                this.style.transform = 'scale(0.95)';
            });
            tab.addEventListener('touchend', function() {
                this.style.transform = 'scale(1)';
            });
        }

        tab.addEventListener('click', () => {
            // Remove active class from all tabs
            categoryTabs.forEach(t => t.classList.remove('active'));
            // Add active class to clicked tab
            tab.classList.add('active');
            // Update current category and re-render
            currentCategory = tab.dataset.category;
            renderInventoryItems();
        });
    });

    // Shop category tabs
    shopCategoryTabs.forEach(tab => {
        // Mobile: Add touch feedback
        if (isMobile) {
            tab.addEventListener('touchstart', function() {
                this.style.transform = 'scale(0.95)';
            });
            tab.addEventListener('touchend', function() {
                this.style.transform = 'scale(1)';
            });
        }

        tab.addEventListener('click', () => {
            // Remove active class from all tabs
            shopCategoryTabs.forEach(t => t.classList.remove('active'));
            // Add active class to clicked tab
            tab.classList.add('active');
            // Update current shop category and re-render
            currentShopCategory = tab.dataset.shopCategory;
            renderShopItems();
        });
    });

    // Shop modal functionality
    if (openShopBtn) {
        openShopBtn.addEventListener('click', () => {
            shopModal.style.display = 'flex';
            renderShopItems(); // Refresh shop items
        });
    }

    if (closeShopBtn) {
        closeShopBtn.addEventListener('click', () => {
            shopModal.style.display = 'none';
        });
    }

    // Close modal when clicking outside
    if (shopModal) {
        shopModal.addEventListener('click', (e) => {
            if (e.target === shopModal) {
                shopModal.style.display = 'none';
            }
        });
        
        // Mobile: Also close on swipe down
        if (isMobile) {
            let startY;
            shopModal.addEventListener('touchstart', (e) => {
                startY = e.touches[0].clientY;
            });
            
            shopModal.addEventListener('touchmove', (e) => {
                if (!startY) return;
                const currentY = e.touches[0].clientY;
                const diff = currentY - startY;
                
                // If swiping down significantly, close modal
                if (diff > 100) {
                    shopModal.style.display = 'none';
                    startY = null;
                }
            });
        }
    }

    // Prevent accidental refresh/swipe
    window.addEventListener('beforeunload', function(e) {
        if (shopModal.style.display === 'flex') {
            e.preventDefault();
            e.returnValue = 'Are you sure you want to leave? Your shop progress will be lost.';
        }
    });
}

// Handle window resize for mobile
window.addEventListener('resize', function() {
    if (isMobile && window.innerWidth <= 768) {
        // Re-render on mobile resize to ensure proper layout
        setTimeout(() => {
            renderInventoryItems();
            renderShopItems();
        }, 100);
    }
});

})();