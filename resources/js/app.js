import Alpine from '@alpinejs/csp';
import collapse from '@alpinejs/collapse';
import TomSelect from 'tom-select/dist/esm/tom-select.complete.js';
import 'tom-select/dist/css/tom-select.css';

window.Alpine = Alpine;
window.TomSelect = TomSelect;
Alpine.plugin(collapse);

const readJson = (id, fallback = {}) => {
    const element = document.getElementById(id);

    if (!element) return fallback;

    try {
        return JSON.parse(element.textContent);
    } catch (error) {
        console.error(`Unable to parse Alpine data from #${id}.`, error);
        return fallback;
    }
};

Alpine.data('appLayout', () => ({
    menu: false,
    collapsed: localStorage.getItem('sidebarCollapsed') === 'true',
    hoverExpand: false,
    mobile: window.matchMedia('(max-width: 1023px)').matches,

    get isCollapsed() {
        return !this.mobile && this.collapsed && !this.hoverExpand;
    },

    init() {
        const mobileQuery = window.matchMedia('(max-width: 1023px)');
        const syncViewport = event => {
            this.mobile = event.matches;
            if (!event.matches) this.menu = false;
        };

        mobileQuery.addEventListener('change', syncViewport);
        this.$watch('collapsed', value => {
            localStorage.setItem('sidebarCollapsed', value);
        });
    },

    closeMenu() {
        this.menu = false;
    },

    openMenu() {
        this.menu = true;
    },

    startSidebarHover() {
        if (this.collapsed) this.hoverExpand = true;
    },

    endSidebarHover() {
        this.hoverExpand = false;
    },

    toggleSidebar() {
        if (this.mobile) return;
        this.collapsed = !this.collapsed;
        this.hoverExpand = false;
    },

    toggleNavGroup() {
        this.open = !this.open;

        if (this.collapsed) {
            this.collapsed = false;
            this.open = true;
        }
    },

    initTomSelect(element) {
        if (!element.tomselect) {
            new window.TomSelect(element, {
                maxOptions: 50,
                dropdownParent: 'body',
            });
        }
    },
}));

Alpine.data('productForm', () => {
    const data = readJson('product-form-data', {
        rows: [],
        baseUnitId: '',
        presets: [],
        mainPrice: '0',
        remnantPrice: '0',
        categoryStoreUrl: '',
    });

    return {
        rows: data.rows,
        baseUnitId: data.baseUnitId,
        selectedPreset: '',
        presets: data.presets,
        mainPrice: data.mainPrice,
        remnantPrice: data.remnantPrice,
        categoryStoreUrl: data.categoryStoreUrl,
        showAddCategory: false,
        newCatName: '',
        newCatParent: '',

        get filteredPresets() {
            return this.presets.filter(preset => String(preset.base_unit_id) === String(this.baseUnitId));
        },

        get baseName() {
            const element = document.getElementById('base_unit_id');
            return element && element.value ? element.options[element.selectedIndex].text : '';
        },

        baseChanged() {
            this.rows = [];
            this.selectedPreset = '';
        },

        addConversion() {
            this.rows.push({
                unit_id: '',
                base_quantity: 1,
                unit_quantity: 1,
                can_purchase: true,
                can_sell: true,
                group: false,
            });
        },

        applyPreset() {
            const preset = this.filteredPresets.find(item => String(item.id) === String(this.selectedPreset));
            if (!preset) return;

            this.rows = preset.conversions.map(conversion => ({
                unit_id: String(conversion.unit_id),
                base_quantity: Number(conversion.base_quantity),
                unit_quantity: Number(conversion.unit_quantity),
                can_purchase: true,
                can_sell: true,
                group: Number(conversion.base_quantity) !== 1,
            }));
        },

        convertedPrice(row, price) {
            const quantity = Number(row.unit_quantity);
            return quantity > 0
                ? Number(price || 0) * Number(row.base_quantity || 0) / quantity
                : 0;
        },

        fieldName(index, field) {
            return `units[${index}][${field}]`;
        },

        isBaseUnit(unitId) {
            return String(this.baseUnitId) === String(unitId);
        },

        normalizeGroup(row) {
            if (!row.group) row.base_quantity = 1;
        },

        removeConversion(index) {
            this.rows.splice(index, 1);
        },

        async addCategory() {
            if (!this.newCatName || !this.categoryStoreUrl) return;

            const response = await fetch(this.categoryStoreUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                },
                body: JSON.stringify({
                    name: this.newCatName,
                    parent_id: this.newCatParent,
                }),
            });
            const category = await response.json();

            if (!category.id) return;

            const option = new Option(category.name, category.id, false, true);
            document.getElementById('category_select').add(option);
            this.showAddCategory = false;
            this.newCatName = '';
            this.newCatParent = '';
        },
    };
});

Alpine.data('unitPresetForm', () => {
    const data = readJson('unit-preset-form-data', {
        rows: [],
        baseUnitId: '',
    });

    return {
        rows: data.rows,
        baseUnitId: data.baseUnitId,
        baseName: '',

        init() {
            this.$nextTick(() => this.updateBaseName(this.$refs.baseUnit));
        },

        updateBaseName(element) {
            this.baseName = element.value ? element.options[element.selectedIndex].text : '';
        },

        baseChanged(event) {
            this.rows = [];
            this.updateBaseName(event.target);
        },

        addConversion() {
            this.rows.push({
                unit_id: '',
                base_quantity: 1,
                unit_quantity: 1,
                group: false,
            });
        },

        fieldName(index, field) {
            return `conversions[${index}][${field}]`;
        },

        isBaseUnit(unitId) {
            return String(this.baseUnitId) === String(unitId);
        },

        normalizeGroup(row) {
            if (!row.group) row.base_quantity = 1;
        },

        removeConversion(index) {
            this.rows.splice(index, 1);
        },
    };
});

Alpine.data('customerManager', () => ({
    add: false,
    editOpen: false,
    edit: {},

    openEdit(element) {
        this.edit = JSON.parse(element.dataset.customer);
        this.editOpen = true;
    },
}));

Alpine.data('flash', () => ({
    show: true,
    init() {
        setTimeout(() => this.show = false, 7000);
    }
}));

document.addEventListener('submit', event => {
    const form = event.target.closest('form[data-confirm]');

    if (form && !window.confirm(form.dataset.confirm)) {
        event.preventDefault();
    }
});

// These components still live beside their Blade templates. Registering them
// with Alpine keeps them compatible with the CSP-safe expression evaluator.
[
    'adjust',
    'brandManager',
    'pos',
    'purchaseForm',
    'saleShow',
    'saleSuccessModal',
    'transfer',
].forEach(name => {
    if (typeof window[name] === 'function') {
        Alpine.data(name, (...args) => window[name](...args));
    }
});

Alpine.start();
