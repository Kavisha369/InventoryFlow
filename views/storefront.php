<?php
/**
 * views/storefront.php
 * Upgraded Public Storefront Showcase View
 * ─────────────────────────────────────────────────────────────────────────────
 * Standalone customer-facing stock catalog showcase with premium UI/UX.
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/constants.php';
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Public Stock Catalog Showcase — Real-time inventory tracking and availability check.">
    <title>Public Stock Catalog — InvenTrack Pro</title>
    
    <!-- Premium Google Fonts and Storefront Stylesheet -->
    <link rel="stylesheet" href="/Collaborative_project/assets/css/storefront.css">
    
    <!-- Favicon -->
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🛍️</text></svg>">
</head>
<body class="storefront-body">

    <!-- Decorative Animated Background Blobs -->
    <div class="storefront-blur-1"></div>
    <div class="storefront-blur-2"></div>

    <!-- ── Header Area ──────────────────────────────────────────────────────── -->
    <header class="sf-header" role="banner">
        <div class="sf-header-container">
            <a href="/Collaborative_project/storefront.php" class="sf-logo" aria-label="InvenTrack Storefront Home">
                <div class="sf-logo-icon" aria-hidden="true">🛍️</div>
                <div class="sf-logo-text">Inven<span>Track</span> Catalog</div>
            </a>
            
            <div class="sf-header-actions">
                <!-- Real-time synchronization widget -->
                <div class="sf-sync-status" aria-live="polite">
                    <span class="sf-sync-dot" aria-hidden="true"></span>
                    <span id="syncText">Syncing…</span>
                    <button class="sf-refresh-btn" id="refreshBtn" title="Synchronize now" aria-label="Sync catalog data">
                        🔄
                    </button>
                </div>
                
                <!-- Dark Mode Toggle Button -->
                <button class="sf-theme-btn" id="sfThemeToggle" title="Toggle dark mode" aria-label="Toggle dark mode">
                    🌙
                </button>
                
                <a href="/Collaborative_project/login.php" class="sf-admin-link">
                    Console Portal ➔
                </a>
            </div>
        </div>
    </header>

    <!-- ── Hero Section ─────────────────────────────────────────────────────── -->
    <section class="sf-hero" aria-labelledby="hero-title">
        <div class="sf-hero-card">
            <div>
                <h1 class="sf-hero-title" id="hero-title">Real-Time Product Showcase</h1>
                <p class="sf-hero-desc">
                    Browse our live inventory catalog below. Stock metrics are synchronized directly with our primary distribution center. Search, filter by category, and check availability instantly.
                </p>
            </div>
            
            <!-- Dynamic Controls (Search, Sort & Category Filtering) -->
            <div class="sf-controls">
                <div class="sf-inputs-row">
                    <!-- Search Input -->
                    <div class="sf-search-wrap">
                        <span class="sf-search-icon" aria-hidden="true">🔍</span>
                        <input type="search" 
                               id="sfSearchInput" 
                               class="sf-search-input" 
                               placeholder="Search catalog by name, SKU or category..."
                               aria-label="Search catalog"
                               autocomplete="off">
                        <div class="sf-search-spinner spinner" id="sfSearchSpinner" aria-hidden="true"></div>
                    </div>
                    
                    <!-- Sorting Dropdown -->
                    <div class="sf-sort-wrap">
                        <select id="sfSortSelect" class="sf-sort-select" aria-label="Sort products">
                            <option value="name_asc">Sort by: Name (A-Z)</option>
                            <option value="name_desc">Sort by: Name (Z-A)</option>
                            <option value="price_asc">Sort by: Price (Low to High)</option>
                            <option value="price_desc">Sort by: Price (High to Low)</option>
                            <option value="stock_desc">Sort by: Stock level (High to Low)</option>
                        </select>
                    </div>
                </div>
                
                <!-- Category Tabs -->
                <div class="sf-categories" id="sfCategoryContainer" role="tablist" aria-label="Filter products by category">
                    <button class="sf-category-btn active" role="tab" aria-selected="true" data-category="all">
                        All Products
                    </button>
                </div>
            </div>
        </div>
    </section>

    <!-- ── Dynamic Stats Ribbon Widget ───────────────────────────────────────── -->
    <section class="sf-stats-ribbon" aria-label="Storefront Catalog Summary">
        <div class="sf-stat-card">
            <div>
                <p class="sf-stat-title">Catalog Showcase</p>
                <p class="sf-stat-val" id="statTotalItems">0</p>
            </div>
            <span class="sf-stat-icon" aria-hidden="true">📦</span>
        </div>
        <div class="sf-stat-card">
            <div>
                <p class="sf-stat-title">Low Stock Alerts</p>
                <p class="sf-stat-val" id="statLowStock">0</p>
            </div>
            <span class="sf-stat-icon" id="statLowStockIcon" aria-hidden="true">⚠️</span>
        </div>
        <div class="sf-stat-card">
            <div>
                <p class="sf-stat-title">Average Price</p>
                <p class="sf-stat-val" id="statAvgPrice">$0.00</p>
            </div>
            <span class="sf-stat-icon" aria-hidden="true">🏷️</span>
        </div>
    </section>

    <!-- ── Catalog Grid Section ─────────────────────────────────────────────── -->
    <main class="sf-catalog-section">
        <div class="sf-grid" id="sfCatalogGrid" aria-label="Product Showcase Catalog">
            <!-- Loading state indicator -->
            <div style="grid-column: 1 / -1; text-align: center; padding: 100px 0;">
                <div class="spinner" style="width: 40px; height: 40px; border-width: 3px;"></div>
                <p style="margin-top: 16px; color: var(--sf-text-sub); font-weight: 600;">Retrieving live stock showcase…</p>
            </div>
        </div>
    </main>

    <!-- ── Glassmorphic Detail Modal Overlay ────────────────────────────────── -->
    <div class="sf-modal-overlay" id="sfDetailModal" role="dialog" aria-modal="true" aria-labelledby="modalTitle">
        <div class="sf-modal-card">
            <div class="sf-modal-visual" id="modalVisual">
                <button class="sf-modal-close" id="modalCloseBtn" aria-label="Close detail modal">✕</button>
                <span class="sf-card-category" id="modalCategory">Category</span>
                <span class="sf-card-sku" id="modalSku">SKU</span>
                <span class="sf-card-icon" id="modalIcon" style="font-size: 80px; z-index: 1;">📦</span>
            </div>
            <div class="sf-modal-body">
                <div>
                    <h2 class="sf-card-title" id="modalTitle" style="font-size: 24px;">Product Title</h2>
                </div>
                
                <p class="sf-card-desc" id="modalDesc" style="height: auto; display: block; -webkit-line-clamp: initial;">
                    Product detailed description goes here.
                </p>
                
                <div id="modalStockBadgeContainer" style="margin-bottom: 8px;"></div>
                
                <div class="sf-modal-grid">
                    <div>
                        <div class="sf-modal-label">Showcase Price</div>
                        <div class="sf-modal-value price" id="modalPrice">$0.00</div>
                    </div>
                    <div>
                        <div class="sf-modal-label">Supplier Partner</div>
                        <div class="sf-modal-value" id="modalSupplier">Supplier Name</div>
                    </div>
                    <div>
                        <div class="sf-modal-label">Safety Stock Trigger</div>
                        <div class="sf-modal-value" id="modalReorder">Reorder level: 0 units</div>
                    </div>
                    <div>
                        <div class="sf-modal-label">Sync Status</div>
                        <div class="sf-modal-value" style="color: #10b981;">● Online</div>
                    </div>
                </div>

                <div style="border-top: 1px solid var(--sf-border); padding-top: 18px; margin-top: 4px;">
                    <div class="sf-modal-label">Supplier Partner Contact</div>
                    <p style="font-size: 13px; color: var(--sf-text-sub); margin: 6px 0 0 0; line-height: 1.4;" id="modalSupplierContact">
                        Email: supplier@inventrack.io<br>
                        Phone: +1 (800) 555-1234
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- ── JavaScript Interaction Engine ────────────────────────────────────── -->
    <script>
    document.addEventListener('DOMContentLoaded', () => {
        // Elements
        const catalogGrid      = document.getElementById('sfCatalogGrid');
        const searchInput      = document.getElementById('sfSearchInput');
        const categoryContainer = document.getElementById('sfCategoryContainer');
        const refreshBtn       = document.getElementById('refreshBtn');
        const syncText         = document.getElementById('syncText');
        const searchSpinner    = document.getElementById('sfSearchSpinner');
        const sortSelect       = document.getElementById('sfSortSelect');
        const themeToggle      = document.getElementById('sfThemeToggle');
        
        // Stats Elements
        const statTotalItems   = document.getElementById('statTotalItems');
        const statLowStock     = document.getElementById('statLowStock');
        const statLowStockIcon = document.getElementById('statLowStockIcon');
        const statAvgPrice     = document.getElementById('statAvgPrice');
        
        // Modal Elements
        const detailModal      = document.getElementById('sfDetailModal');
        const modalCloseBtn    = document.getElementById('modalCloseBtn');
        const modalVisual      = document.getElementById('modalVisual');
        const modalCategory    = document.getElementById('modalCategory');
        const modalSku         = document.getElementById('modalSku');
        const modalIcon        = document.getElementById('modalIcon');
        const modalTitle       = document.getElementById('modalTitle');
        const modalDesc        = document.getElementById('modalDesc');
        const modalPrice       = document.getElementById('modalPrice');
        const modalSupplier    = document.getElementById('modalSupplier');
        const modalReorder     = document.getElementById('modalReorder');
        const modalSupplierContact = document.getElementById('modalSupplierContact');
        const modalStockBadgeContainer = document.getElementById('modalStockBadgeContainer');
        
        // State variables
        let allProducts    = [];
        let activeCategory = 'all';
        let searchQuery    = '';
        let sortBy         = 'name_asc';
        let lastSyncTime   = null;
        let searchDebounceTimeout = null;
        
        // Extract tenant ID from query parameter, defaulting to 1 (Enterprise)
        const urlParams = new URLSearchParams(window.location.search);
        const tenantId  = urlParams.get('tenant_id') || '1';

        // Initialize Theme Mode
        const savedTheme = localStorage.getItem('theme') || 'light';
        document.documentElement.setAttribute('data-theme', savedTheme);
        updateThemeToggleUI(savedTheme);

        themeToggle.addEventListener('click', () => {
            const currentTheme = document.documentElement.getAttribute('data-theme') || 'light';
            const newTheme = currentTheme === 'light' ? 'dark' : 'light';
            document.documentElement.setAttribute('data-theme', newTheme);
            localStorage.setItem('theme', newTheme);
            updateThemeToggleUI(newTheme);
        });

        function updateThemeToggleUI(theme) {
            themeToggle.textContent = theme === 'light' ? '🌙' : '☀️';
            themeToggle.setAttribute('title', theme === 'light' ? 'Toggle dark mode' : 'Toggle light mode');
        }

        // Emoji list based on category or product name characteristics
        function getProductEmoji(category, name) {
            const cat = (category || '').toLowerCase();
            const n = (name || '').toLowerCase();
            
            if (cat.includes('electr') || cat.includes('tech') || n.includes('usb') || n.includes('charger') || n.includes('cable')) {
                return '💻';
            }
            if (cat.includes('office') || cat.includes('station') || n.includes('pen') || n.includes('notebook') || n.includes('paper')) {
                return '✏️';
            }
            if (cat.includes('furnit') || n.includes('chair') || n.includes('desk') || n.includes('table')) {
                return '🪑';
            }
            if (cat.includes('tool') || cat.includes('hardw') || n.includes('screw') || n.includes('hammer') || n.includes('drill')) {
                return '🛠️';
            }
            if (cat.includes('cloth') || cat.includes('wear') || n.includes('shirt') || n.includes('jacket') || n.includes('pants')) {
                return '👕';
            }
            if (cat.includes('food') || cat.includes('drink') || n.includes('coffee') || n.includes('tea') || n.includes('snack')) {
                return '☕';
            }
            return '📦';
        }

        // Fun dynamic card gradients for visual variety
        function getCardGradientClass(index) {
            const gradients = [
                'linear-gradient(135deg, #e0e7ff 0%, #c7d2fe 100%)', // Indigo/Blue
                'linear-gradient(135deg, #e0f2fe 0%, #bae6fd 100%)', // Light blue/Cyan
                'linear-gradient(135deg, #ecfdf5 0%, #d1fae5 100%)', // Emerald/Mint
                'linear-gradient(135deg, #fef3c7 0%, #fde68a 100%)', // Amber/Yellow
                'linear-gradient(135deg, #faf5ff 0%, #f3e8ff 100%)', // Purple
                'linear-gradient(135deg, #fff1f2 0%, #ffe4e6 100%)'  // Rose
            ];
            
            // For dark mode, tone down brightness using overlays or different colors in CSS,
            // but we can pass same index
            return gradients[index % gradients.length];
        }

        // Fetch products list from API
        async function fetchCatalogData(isManual = false) {
            if (isManual) {
                refreshBtn.classList.add('spinning');
            }
            
            try {
                const response = await fetch(`/Collaborative_project/api/products.php?public=1&tenant_id=${tenantId}`);
                if (!response.ok) {
                    throw new Error(`Server returned HTTP status ${response.status}`);
                }
                const result = await response.json();
                
                if (result.success) {
                    allProducts = result.data || [];
                    lastSyncTime = new Date();
                    updateSyncWidget();
                    rebuildCategoryFilters();
                    filterAndRender();
                } else {
                    renderErrorState(result.error || 'Server rejected catalog query.');
                }
            } catch (err) {
                console.error('[Storefront Catalog Fetch Error]', err);
                renderErrorState('Network error occurred. Make sure your local XAMPP Apache is running.');
            } finally {
                if (isManual) {
                    setTimeout(() => refreshBtn.classList.remove('spinning'), 500);
                }
            }
        }

        // Rebuild the category tab filters based on currently fetched products
        function rebuildCategoryFilters() {
            const categories = new Set();
            allProducts.forEach(p => {
                const cat = (p.category || '').trim();
                if (cat) {
                    categories.add(cat);
                }
            });

            const activeBtn = categoryContainer.querySelector('.sf-category-btn.active');
            const prevActiveCategory = activeBtn ? activeBtn.getAttribute('data-category') : 'all';

            categoryContainer.innerHTML = `
                <button class="sf-category-btn ${prevActiveCategory === 'all' ? 'active' : ''}" 
                        role="tab" 
                        aria-selected="${prevActiveCategory === 'all'}" 
                        data-category="all">
                    All Products
                </button>
            `;

            Array.from(categories).sort().forEach(cat => {
                const isThisActive = prevActiveCategory === cat;
                const button = document.createElement('button');
                button.className = `sf-category-btn ${isThisActive ? 'active' : ''}`;
                button.setAttribute('role', 'tab');
                button.setAttribute('aria-selected', isThisActive ? 'true' : 'false');
                button.setAttribute('data-category', cat);
                button.textContent = cat;
                
                categoryContainer.appendChild(button);
            });

            categoryContainer.querySelectorAll('.sf-category-btn').forEach(btn => {
                btn.addEventListener('click', (e) => {
                    categoryContainer.querySelectorAll('.sf-category-btn').forEach(b => {
                        b.classList.remove('active');
                        b.setAttribute('aria-selected', 'false');
                    });
                    
                    e.currentTarget.classList.add('active');
                    e.currentTarget.setAttribute('aria-selected', 'true');
                    
                    activeCategory = e.currentTarget.getAttribute('data-category');
                    filterAndRender();
                });
            });
        }

        // Client-side local filtering, sorting, stats calculation, and rendering
        function filterAndRender() {
            let filtered = [...allProducts];
            
            // 1. Filter by category
            if (activeCategory !== 'all') {
                filtered = filtered.filter(p => p.category === activeCategory);
            }
            
            // 2. Filter by search query (Name or SKU)
            if (searchQuery) {
                const q = searchQuery.toLowerCase();
                filtered = filtered.filter(p => 
                    (p.name || '').toLowerCase().includes(q) || 
                    (p.sku || '').toLowerCase().includes(q) ||
                    (p.category || '').toLowerCase().includes(q)
                );
            }
            
            // 3. Compute dynamic stats on filtered set
            calculateStats(filtered);

            // 4. Sort filtered items
            sortItems(filtered);

            // 5. Render Grid
            renderGrid(filtered);
        }

        // Calculate stats for the stats ribbon widget
        function calculateStats(products) {
            statTotalItems.textContent = products.length;
            
            // Low stock alerts count (stock < reorder level)
            const lowStockCount = products.filter(p => parseInt(p.stock_level) < parseInt(p.reorder_level)).length;
            statLowStock.textContent = lowStockCount;
            
            if (lowStockCount > 0) {
                statLowStock.classList.add('pulse-stat');
                statLowStockIcon.textContent = '⚠';
                statLowStockIcon.style.animation = 'sf-pulse-orange 1.5s ease-in-out infinite';
            } else {
                statLowStock.classList.remove('pulse-stat');
                statLowStockIcon.textContent = '✅';
                statLowStockIcon.style.animation = 'none';
            }
            
            // Average price computation
            const avgPrice = products.length > 0
                ? products.reduce((sum, p) => sum + parseFloat(p.unit_price), 0) / products.length
                : 0.00;
            statAvgPrice.textContent = `$${avgPrice.toFixed(2)}`;
        }

        // Sort items in place
        function sortItems(products) {
            if (sortBy === 'name_asc') {
                products.sort((a, b) => (a.name || '').localeCompare(b.name || ''));
            } else if (sortBy === 'name_desc') {
                products.sort((a, b) => (b.name || '').localeCompare(a.name || ''));
            } else if (sortBy === 'price_asc') {
                products.sort((a, b) => parseFloat(a.unit_price) - parseFloat(b.unit_price));
            } else if (sortBy === 'price_desc') {
                products.sort((a, b) => parseFloat(b.unit_price) - parseFloat(a.unit_price));
            } else if (sortBy === 'stock_desc') {
                products.sort((a, b) => parseInt(b.stock_level) - parseInt(a.stock_level));
            }
        }

        // Highlight matching text helper
        function highlightText(text, query) {
            if (!text) return '';
            if (!query) return htmlEscape(text);
            
            const escapedQuery = query.replace(/[-\/\\^$*+?.()|[\]{}]/g, '\\$&');
            const regex = new RegExp(`(${escapedQuery})`, 'gi');
            return htmlEscape(text).replace(regex, '<span class="sf-highlight">$1</span>');
        }

        // Render the filtered product catalog grid
        function renderGrid(products) {
            catalogGrid.innerHTML = '';
            
            if (products.length === 0) {
                catalogGrid.innerHTML = `
                    <div class="sf-empty">
                        <div class="sf-empty-icon">🔍</div>
                        <h3 class="sf-empty-title">No products match your criteria</h3>
                        <p class="sf-empty-desc">Try clearing your search query or choosing another category filter.</p>
                    </div>
                `;
                return;
            }
            
            products.forEach((p, index) => {
                const card = document.createElement('div');
                card.className = 'sf-card animate-in';
                card.style.animationDelay = `${index * 0.03}s`;
                
                const stock = parseInt(p.stock_level);
                const reorder = parseInt(p.reorder_level);
                
                // 1. Formulate Stock availability badge details
                let badgeHTML = '';
                let meterClass = 'healthy';
                let meterPct = 100;
                
                if (stock === 0) {
                    badgeHTML = `<span class="sf-badge sf-badge-out" role="status">✕ Out of Stock</span>`;
                    meterClass = 'out';
                    meterPct = 0;
                } else if (stock < reorder) {
                    badgeHTML = `<span class="sf-badge sf-badge-lowstock" role="status">⚠ Running Low: ${stock} left</span>`;
                    meterClass = 'warning';
                    meterPct = Math.max(10, Math.round((stock / reorder) * 100)); // cap min at 10% for visibility
                } else {
                    badgeHTML = `<span class="sf-badge sf-badge-instock" role="status">● In Stock: ${stock} units</span>`;
                    meterClass = 'healthy';
                    meterPct = 100;
                }

                const price = parseFloat(p.unit_price).toFixed(2);
                const categoryLabel = p.category ? htmlEscape(p.category) : 'General';
                const emoji = getProductEmoji(p.category, p.name);
                const cardGradient = getCardGradientClass(p.id);

                // Highlighted text if search query exists
                const highlightedName = highlightText(p.name, searchQuery);
                const highlightedSku  = highlightText(p.sku, searchQuery);

                card.innerHTML = `
                    <div class="sf-card-visual" style="background: ${cardGradient}">
                        <span class="sf-card-category">${categoryLabel}</span>
                        <span class="sf-card-sku">${highlightedSku}</span>
                        <span class="sf-card-icon" role="img" aria-label="${categoryLabel}">${emoji}</span>
                    </div>
                    <div class="sf-card-body">
                        <h3 class="sf-card-title">${highlightedName}</h3>
                        <p class="sf-card-desc">${htmlEscape(p.description || 'No description provided for this product showcase.')}</p>
                        
                        <!-- Premium visual stock progress bar -->
                        <div class="sf-stock-meter-wrap" aria-hidden="true">
                            <div class="sf-stock-meter-label">
                                <span>Availability Gauge</span>
                                <span>${stock === 0 ? 'Empty' : (stock < reorder ? 'Low Level' : 'Healthy')}</span>
                            </div>
                            <div class="sf-stock-meter-bar">
                                <div class="sf-stock-meter-fill ${meterClass}" style="width: ${meterPct}%"></div>
                            </div>
                        </div>
                        
                        <div style="margin-top: 4px;">
                            ${badgeHTML}
                        </div>
                        
                        <div class="sf-card-footer">
                            <div class="sf-card-price-wrap">
                                <span class="sf-card-price-label">Showcase Price</span>
                                <span class="sf-card-price">$${price}</span>
                            </div>
                            <div class="sf-supplier" title="Supplier: ${htmlEscape(p.supplier_name)}">
                                🏭 ${htmlEscape(p.supplier_name)}
                            </div>
                        </div>
                    </div>
                `;
                
                // Add Quick View Click Handler
                card.addEventListener('click', () => {
                    openQuickViewModal(p);
                });
                
                catalogGrid.appendChild(card);
            });
        }

        // Open detailed view modal
        function openQuickViewModal(product) {
            const stock = parseInt(product.stock_level);
            const reorder = parseInt(product.reorder_level);
            
            let badgeHTML = '';
            if (stock === 0) {
                badgeHTML = `<span class="sf-badge sf-badge-out" role="status">✕ Out of Stock</span>`;
            } else if (stock < reorder) {
                badgeHTML = `<span class="sf-badge sf-badge-lowstock" role="status">⚠ Running Low: Only ${stock} units left</span>`;
            } else {
                badgeHTML = `<span class="sf-badge sf-badge-instock" role="status">● Available: ${stock} units in stock</span>`;
            }
            
            const cardGradient = getCardGradientClass(product.id);
            modalVisual.style.background = cardGradient;
            modalCategory.textContent = product.category ? product.category : 'General';
            modalSku.textContent = product.sku;
            modalIcon.textContent = getProductEmoji(product.category, product.name);
            modalTitle.textContent = product.name;
            modalDesc.textContent = product.description || 'No detailed product description available at this moment.';
            modalPrice.textContent = `$${parseFloat(product.unit_price).toFixed(2)}`;
            modalSupplier.textContent = product.supplier_name;
            modalReorder.textContent = `Trigger point: ${reorder} units`;
            modalStockBadgeContainer.innerHTML = badgeHTML;
            
            // Mock dynamic phone/email contact for visual completion
            const cleanName = product.supplier_name.replace(/[^a-zA-Z]/g, '').toLowerCase();
            modalSupplierContact.innerHTML = `
                <strong>Email Partner:</strong> orders@${cleanName || 'supplier'}.com<br>
                <strong>Procurement Hotline:</strong> +1 (800) 555-` + (1000 + parseInt(product.id) * 37);
            
            detailModal.classList.add('open');
            document.body.style.overflow = 'hidden'; // Lock body scroll
        }

        // Close detailed view modal
        function closeQuickViewModal() {
            detailModal.classList.remove('open');
            document.body.style.overflow = ''; // Restore scroll
        }

        modalCloseBtn.addEventListener('click', closeQuickViewModal);
        detailModal.addEventListener('click', (e) => {
            if (e.target === detailModal) closeQuickViewModal();
        });
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && detailModal.classList.contains('open')) {
                closeQuickViewModal();
            }
        });

        // Simple helper to avoid HTML injection
        function htmlEscape(str) {
            if (!str) return '';
            return str
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        // Render error state on service failure
        function renderErrorState(message) {
            catalogGrid.innerHTML = `
                <div class="sf-empty" style="border-color: rgba(239, 68, 68, 0.3);">
                    <div class="sf-empty-icon" style="color: var(--sf-out-of-stock-text);">⚠️</div>
                    <h3 class="sf-empty-title" style="color: var(--sf-out-of-stock-text);">Catalog Sync Failed</h3>
                    <p class="sf-empty-desc">${htmlEscape(message)}</p>
                    <button class="sf-category-btn active" id="retryFetchBtn" style="margin-top: 16px; background: var(--sf-primary); color: white; border: none;">
                        🔄 Retry Connection
                    </button>
                </div>
            `;
            
            const retryBtn = document.getElementById('retryFetchBtn');
            if (retryBtn) {
                retryBtn.addEventListener('click', () => {
                    catalogGrid.innerHTML = `
                        <div style="grid-column: 1 / -1; text-align: center; padding: 100px 0;">
                            <div class="spinner" style="width: 40px; height: 40px; border-width: 3px;"></div>
                            <p style="margin-top: 16px; color: var(--sf-text-sub); font-weight: 600;">Reconnecting showcase…</p>
                        </div>
                    `;
                    fetchCatalogData(true);
                });
            }
        }

        // Update real-time sync text details in the header widget
        function updateSyncWidget() {
            if (!lastSyncTime) {
                syncText.textContent = 'Not synced';
                return;
            }
            
            const elapsedSeconds = Math.round((new Date() - lastSyncTime) / 1000);
            
            if (elapsedSeconds < 5) {
                syncText.textContent = 'Sync Active: Just now';
            } else if (elapsedSeconds < 60) {
                syncText.textContent = `Sync Active: ${elapsedSeconds}s ago`;
            } else {
                const mins = Math.floor(elapsedSeconds / 60);
                syncText.textContent = `Sync Active: ${mins}m ago`;
            }
        }

        // Set interval to update the last sync time text representation every second
        setInterval(updateSyncWidget, 1000);

        // Bind instant filtering input with debounce
        searchInput.addEventListener('input', (e) => {
            searchSpinner.style.display = 'block';
            
            clearTimeout(searchDebounceTimeout);
            searchDebounceTimeout = setTimeout(() => {
                searchQuery = e.target.value.trim();
                filterAndRender();
                searchSpinner.style.display = 'none';
            }, 200);
        });

        // Sort selector change
        sortSelect.addEventListener('change', (e) => {
            sortBy = e.target.value;
            filterAndRender();
        });

        // Sync button click
        refreshBtn.addEventListener('click', () => {
            fetchCatalogData(true);
        });

        // Set auto-refresh polling every 30 seconds for live stock numbers
        setInterval(() => {
            fetchCatalogData(false);
        }, 30000);

        // Initial Data Retrieve
        fetchCatalogData(false);
    });
    </script>
</body>
</html>
