// Advanced Search Component
class AdvancedSearch {
    constructor() {
        this.filters = {
            q: '',
            categories: [],
            min_price: '',
            max_price: '',
            date_from: '',
            date_to: '',
            unit_type: '',
            sort_by: 'kode_item',
            sort_order: 'asc'
        };
        this.results = [];
        this.loading = false;
    }

    async search() {
        this.loading = true;
        
        try {
            const params = new URLSearchParams();
            
            // Add filters to params
            Object.entries(this.filters).forEach(([key, value]) => {
                if (value) {
                    if (Array.isArray(value)) {
                        value.forEach(v => params.append(`${key}[]`, v));
                    } else {
                        params.append(key, value);
                    }
                }
            });

            const response = await fetch(`/api/data/advanced/search?${params}`);
            const result = await response.json();

            if (result.success) {
                this.results = result.data;
                this.updateResultsDisplay();
            } else {
                console.error('Search failed:', result.message);
            }
        } catch (error) {
            console.error('Search error:', error);
        } finally {
            this.loading = false;
        }
    }

    updateResultsDisplay() {
        // Update UI dengan hasil pencarian
        const container = document.getElementById('search-results');
        if (container) {
            container.innerHTML = this.results.map(item => `
                <div class="search-result-item">
                    <h4>${item.kode_item} - ${item.uraian_item}</h4>
                    <p>Kategori: ${item.kategori} | Harga: ${this.formatCurrency(item.harga_satuan_1)}</p>
                </div>
            `).join('');
        }
    }

    formatCurrency(amount) {
        return new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            minimumFractionDigits: 0
        }).format(amount);
    }
}

// Price Analysis Dashboard
class PriceAnalysis {
    constructor() {
        this.charts = {};
    }

    async loadAnalysis(timeRange = '30d', kategori = '') {
        try {
            const params = new URLSearchParams({ time_range: timeRange, kategori });
            const response = await fetch(`/api/data/advanced/price-analysis?${params}`);
            const result = await response.json();

            if (result.success) {
                this.renderCharts(result.data);
            }
        } catch (error) {
            console.error('Price analysis error:', error);
        }
    }

    renderCharts(data) {
        // Render category distribution chart
        this.renderCategoryChart(data.category_stats);
        
        // Render price trends chart
        this.renderPriceTrendsChart(data.price_changes);
        
        // Update summary cards
        this.updateSummaryCards(data.summary);
    }

    renderCategoryChart(categoryStats) {
        const ctx = document.getElementById('category-chart');
        if (!ctx) return;

        const labels = Object.keys(categoryStats);
        const data = labels.map(label => categoryStats[label].count);

        if (this.charts.category) {
            this.charts.category.destroy();
        }

        this.charts.category = new Chart(ctx, {
            type: 'pie',
            data: {
                labels: labels,
                datasets: [{
                    data: data,
                    backgroundColor: [
                        '#3B82F6', '#10B981', '#F59E0B', '#EF4444', '#8B5CF6', '#06B6D4'
                    ]
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    }

    updateSummaryCards(summary) {
        const elements = {
            'avg-price': summary.avg_price,
            'max-price': summary.max_price,
            'min-price': summary.min_price
        };

        Object.entries(elements).forEach(([id, value]) => {
            const element = document.getElementById(id);
            if (element) {
                element.textContent = this.formatCurrency(value);
            }
        });
    }
}

// Real-time Data Sync
class RealTimeSync {
    constructor() {
        this.connection = null;
        this.isConnected = false;
    }

    connect() {
        // WebSocket connection untuk real-time updates
        if (typeof Echo !== 'undefined') {
            this.connection = Echo.channel('master-data')
                .listen('MasterDataUpdated', (e) => {
                    this.handleUpdate(e);
                })
                .listen('MasterDataCreated', (e) => {
                    this.handleCreate(e);
                })
                .listen('MasterDataDeleted', (e) => {
                    this.handleDelete(e);
                });
            
            this.isConnected = true;
        }
    }

    handleUpdate(event) {
        // Update UI ketika data berubah
        this.showNotification('Data diperbarui', `${event.item.kode_item} telah diperbarui`, 'info');
        
        // Refresh data jika needed
        if (window.masterDataApp) {
            window.masterDataApp.fetchData();
        }
    }

    handleCreate(event) {
        this.showNotification('Data ditambahkan', `Item baru: ${event.item.kode_item}`, 'success');
        
        if (window.masterDataApp) {
            window.masterDataApp.fetchData();
            window.masterDataApp.loadStatistics();
        }
    }

    handleDelete(event) {
        this.showNotification('Data dihapus', `${event.item.kode_item} telah dihapus`, 'warning');
        
        if (window.masterDataApp) {
            window.masterDataApp.fetchData();
            window.masterDataApp.loadStatistics();
        }
    }

    showNotification(title, message, type) {
        if (window.masterDataApp && window.masterDataApp.showToast) {
            window.masterDataApp.showToast(type, title, message);
        }
    }
}

// Keyboard Shortcuts
class KeyboardShortcuts {
    constructor() {
        this.shortcuts = new Map();
        this.setupShortcuts();
    }

    setupShortcuts() {
        // Add new item
        this.addShortcut('ctrl+n', () => {
            if (window.masterDataApp) {
                window.masterDataApp.showCreateModal = true;
            }
        });

        // Search focus
        this.addShortcut('ctrl+k', (e) => {
            e.preventDefault();
            const searchInput = document.querySelector('input[type="search"]');
            if (searchInput) searchInput.focus();
        });

        // Refresh data
        this.addShortcut('ctrl+r', (e) => {
            e.preventDefault();
            if (window.masterDataApp) {
                window.masterDataApp.fetchData();
            }
        });

        // Export data
        this.addShortcut('ctrl+e', (e) => {
            e.preventDefault();
            if (window.masterDataApp) {
                window.masterDataApp.exportData();
            }
        });
    }

    addShortcut(keys, callback) {
        this.shortcuts.set(keys, callback);
    }

    handleKeydown(event) {
        const key = this.getKeyString(event);
        
        for (const [shortcut, callback] of this.shortcuts) {
            if (this.matchesShortcut(key, shortcut)) {
                event.preventDefault();
                callback(event);
                break;
            }
        }
    }

    getKeyString(event) {
        const parts = [];
        if (event.ctrlKey) parts.push('ctrl');
        if (event.altKey) parts.push('alt');
        if (event.shiftKey) parts.push('shift');
        parts.push(event.key.toLowerCase());
        
        return parts.join('+');
    }

    matchesShortcut(key, shortcut) {
        return key === shortcut;
    }
}

// Initialize semua advanced features
document.addEventListener('DOMContentLoaded', function() {
    // Initialize advanced search
    window.advancedSearch = new AdvancedSearch();
    
    // Initialize price analysis
    window.priceAnalysis = new PriceAnalysis();
    
    // Initialize real-time sync
    window.realTimeSync = new RealTimeSync();
    window.realTimeSync.connect();
    
    // Initialize keyboard shortcuts
    window.keyboardShortcuts = new KeyboardShortcuts();
    document.addEventListener('keydown', (e) => window.keyboardShortcuts.handleKeydown(e));
});
