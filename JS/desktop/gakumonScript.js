// Prevent double-execution if the script is included twice
if (window.__GAKUMON_SCRIPT_LOADED__) {
  console.debug('gakumonScript already loaded — skipping.');
} else {
  window.__GAKUMON_SCRIPT_LOADED__ = true;

  (function () {
    'use strict';

    // Use one source of truth for server data
    const DATA = window.__GAKUMON_DATA__ || window.serverData || {};

    // Build data from PHP (local/file-scoped — not globals)
    const shopItemsFromServer = Array.isArray(DATA.shopItems) ? DATA.shopItems : [];
    const petFromServer       = DATA.pet;
    const inventoryFromServer = Array.isArray(DATA.inventory) ? DATA.inventory : [];

    async function postForm(url, data) {
      const form = new FormData();
      Object.entries(data).forEach(([k,v]) => form.append(k, v));
      const res = await fetch(url, { method: 'POST', body: form });
      let json;
      try { json = await res.json(); } catch { json = { ok:false, error:'Bad JSON' }; }

      // Auto-refresh from DB truth after successful buy/feed — centralized, no handler edits needed
      const isMutating = typeof url === 'string' && (
        url.includes('gakumonFeed.inc.php') || url.includes('gakumonBuy.inc.php')
      );
      if (isMutating && json?.ok) {
        // Let the UI show your optimistic update first, then snap to DB truth
        setTimeout(() => { refreshStateFromServer(); }, 0);
      }

      return json;
    }

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

    // Dynamic accessory system that uses database info
    function getAccessoryImagePath(accessoryName) {
      // Simple mapping - you can expand this based on your actual accessory names
      const name = accessoryName.toLowerCase();
      
      if (name.includes('hat')) return 'IMG/Accessories/hat.png';
      if (name.includes('glass')) return 'IMG/Accessories/glasses.png';
      if (name.includes('collar')) return 'IMG/Accessories/collar.png';
      if (name.includes('wing')) return 'IMG/Accessories/wings.png';
      
      // Default fallback
      return 'IMG/Accessories/default.png';
    }

    function getAccessoryLayer(accessoryName) {
      const name = accessoryName.toLowerCase();
      
      if (name.includes('collar')) return 1;
      if (name.includes('glass')) return 2;
      if (name.includes('hat')) return 3;
      if (name.includes('wing')) return 4;
      
      return 0;
    }

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
            accessory_image_url: i.accessory_image_url, // NEW: Use accessory image or fallback
            energy_restore: i.energy_restore !== null && i.energy_restore !== undefined
                ? Number(i.energy_restore) : null,
            owned: Number(i.owned ?? 1),
            equipped: Boolean(i.equipped ?? false),
        })),

        shopItems: shopItemsFromServer.map(i => ({
            id: Number(i.id),
            name: i.name,
            type: normalizeType(i.type),
            price: Number(i.price),
            icon: i.icon,
            accessory_image_url: i.accessory_image_url, // Fallback to regular icon if no accessory image
            energy_restore: i.energy_restore !== null ? Number(i.energy_restore) : null
        }))
    };

    // === Persist equipped items across reloads (localStorage) ===
    function getEquipStorageKey() {
    const uid = (window.serverData && window.serverData.userId) || 'anon';
    const petType = (window.serverData && window.serverData.pet && window.serverData.pet.type) || 'pet';
    return `gaku_equipped_${uid}_${petType}`;
    }

    function saveEquipped() {
    try {
        const equippedIds = gameData.inventory
        .filter(i => (i.type === 'accessories' || i.type === 'decorations') && i.equipped)
        .map(i => i.id);
        localStorage.setItem(getEquipStorageKey(), JSON.stringify(equippedIds));
    } catch (e) {
        console.warn('saveEquipped failed', e);
    }
    }

    function loadEquipped() {
    try {
        const raw = localStorage.getItem(getEquipStorageKey());
        if (!raw) return;
        const ids = JSON.parse(raw);
        if (!Array.isArray(ids)) return;

        // Apply equipped flags to inventory
        gameData.inventory.forEach(i => {
        if (i.type === 'accessories' || i.type === 'decorations') {
            i.equipped = ids.includes(i.id);
        }
        });

        // Rebuild pet.equipped arrays
        gameData.pet.equipped.accessories = gameData.inventory
        .filter(i => i.type === 'accessories' && i.equipped)
        .map(i => i.id);

        gameData.pet.equipped.decorations = gameData.inventory
        .filter(i => i.type === 'decorations' && i.equipped)
        .map(i => i.id);
    } catch (e) {
        console.warn('loadEquipped failed', e);
    }
    }


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
        loadEquipped(); // Load equipped items from localStorage
        renderInventoryItems();
        renderShopItems();
        applyPetImage();
        updateFloatingPetInfo();
        updateAccessoryDisplay(); // Render equipped accessories on pet
        updateDecorationDisplay(); // Render equipped decorations in room
        setupEventListeners();
        
        // Debug: Log all accessories
        const allAccessories = gameData.inventory.filter(item => item.type === 'accessories');
        console.log('All accessories in inventory:', allAccessories);
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

function updateAccessoryDisplay() {
    console.log('=== updateAccessoryDisplay START ===');
    
    if (!accessoryLayers) {
        console.error('❌ accessoryLayers element not found!');
        return;
    }
    
    // Clear existing accessory layers
    accessoryLayers.innerHTML = '';
    
    // Get all equipped accessories
    const equippedAccessories = gameData.inventory.filter(item => 
        item.type === 'accessories' && item.equipped
    );
    
    console.log('🔍 Equipped accessories found:', equippedAccessories);
    
    if (equippedAccessories.length === 0) {
        console.log('ℹ️ No accessories equipped');
        return;
    }
    
    // Get the user's ACTUAL current pet name from server data
    const currentPetName = window.serverData.pet.type; // This gets the actual equipped pet
    console.log('🐾 User current pet:', currentPetName);
    
    // Create and append accessory layers
    equippedAccessories.forEach(accessory => {
        console.log('🎯 Processing accessory:', accessory);
        
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
        accessoryLayer.style.zIndex = 10 + equippedAccessories.indexOf(accessory);
        
        // Add error handling for image loading
        accessoryLayer.onerror = function() {
            console.error('❌ Failed to load pet-specific accessory:', this.src);
            // Fallback to generic accessory if pet-specific doesn't exist
            const fallbackPath = `IMG/Accessories/${accessory.accessory_image_url}`;
            console.log('🔄 Trying fallback path:', fallbackPath);
            this.src = fallbackPath;
        };
        
        accessoryLayer.onload = function() {
            console.log('✅ Successfully loaded pet-specific accessory:', this.src);
        };
        
        accessoryLayers.appendChild(accessoryLayer);
        console.log('✅ Added accessory layer for', currentPetName, 'with path:', dynamicPath);
    });
    
    console.log('=== updateAccessoryDisplay END ===');
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
        if (item.type === 'accessories') updateAccessoryDisplay();
        else if (item.type === 'decorations') updateDecorationDisplay();
    
        saveEquipped(); 
        showEquipFeedback(item.name, item.equipped);
    }

// ALSO update renderInventoryItems to re-setup clicks after rendering
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
        
        let actionText = 'Click to use';
        let ownedText = `${item.owned}`;
        
        if (item.type === 'accessories' || item.type === 'decorations') {
            actionText = item.equipped ? 'EQUIPPED - Click to remove' : 'Click to equip';
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

        itemsScrollable.appendChild(itemCard);
    });

    // RE-SETUP CLICKS AFTER RENDERING
    setTimeout(() => {
        setupInventoryItemClicks();
    }, 100);
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
                <button class="buy-button" onclick="buyItem(${item.id})" 
                        ${gameData.currency < item.price ? 'disabled' : ''}>
                    Buy
                </button>
            `;
            shopItemsGrid.appendChild(shopItem);
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

    // Generic Feedback Function
    function showFeedback(message, color) {
        const feedback = document.createElement('div');
        feedback.className = 'feedback-message';
        feedback.style.background = color;
        feedback.textContent = message;
        document.body.appendChild(feedback);

        setTimeout(() => {
            if (document.body.contains(feedback)) {
                document.body.removeChild(feedback);
            }
        }, 2000);
    }

// Setup Event Listeners
function setupEventListeners() {
    console.log('🔧 Setting up event listeners...');
    
    // Inventory category tabs
    categoryTabs.forEach(tab => {
        tab.addEventListener('click', () => {
            console.log('📁 Category tab clicked:', tab.dataset.category);
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
    }

    // MANUALLY SET UP INVENTORY ITEM CLICKS SINCE THEY'RE DYNAMIC
    setTimeout(() => {
        setupInventoryItemClicks();
    }, 1000);
}

// NEW FUNCTION: Set up click handlers for inventory items
function setupInventoryItemClicks() {
    console.log('🖱️ Setting up inventory item clicks...');
    
    const itemCards = document.querySelectorAll('.item-card');
    console.log('📦 Found item cards:', itemCards.length);
    
    itemCards.forEach(card => {
        card.addEventListener('click', function() {
            console.log('🎯 Item card clicked!');
            
            // Find which item was clicked by its name
            const itemNameElement = this.querySelector('.item-name');
            if (itemNameElement) {
                const itemName = itemNameElement.textContent;
                console.log('📝 Clicked item name:', itemName);
                
                // Find the item in gameData
                const item = gameData.inventory.find(i => i.name === itemName);
                if (item) {
                    console.log('🔍 Found item in inventory:', item);
                    
                    if (item.type === 'accessories' || item.type === 'decorations') {
                        console.log('👗 Calling toggleEquipItem for:', item.id);
                        toggleEquipItem(item.id);
                    } else if (item.type === 'food') {
                        console.log('🍎 Calling useFoodItem for:', item.id);
                        useFoodItem(item.id);
                    }
                } else {
                    console.error('❌ Item not found in gameData inventory');
                }
            }
        });
    });
}


    // If you call functions via onclick in HTML, expose just those:
    window.buyItem = buyItem;
    window.useFoodItem = useFoodItem;
    // Add others only if referenced from HTML attributes:
    // window.toggleEquipItem = toggleEquipItem;

  })(); // end IIFE
} // end one-time guard