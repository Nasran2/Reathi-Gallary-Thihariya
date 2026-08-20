<?php

return [
    'groups' => [
        'Dashboard' => [
            'dashboard.view' => 'View dashboard',
            'dashboard.view_profit' => 'View profit figures',
            'dashboard.view_stock_value' => 'View stock value',
            'dashboard.view_customer_due' => 'View customer dues',
            'dashboard.view_supplier_due' => 'View supplier dues',
        ],
        'Users' => [
            'users.view' => 'View users', 'users.create' => 'Create users', 'users.edit' => 'Edit users',
            'users.deactivate' => 'Activate or deactivate users', 'users.delete' => 'Delete users',
        ],
        'Roles' => [
            'roles.view' => 'View roles', 'roles.create' => 'Create roles', 'roles.edit' => 'Edit roles',
            'roles.delete' => 'Delete roles', 'roles.assign_permissions' => 'Assign role permissions',
        ],
        'Products' => [
            'products.view' => 'View products', 'products.create' => 'Create products', 'products.edit' => 'Edit products',
            'products.delete' => 'Delete products', 'products.view_cost' => 'View product costs',
            'products.change_cost' => 'Change product costs', 'products.change_selling_price' => 'Change selling prices',
        ],
        'Product setup' => [
            'categories.manage' => 'Manage categories', 'brands.manage' => 'Manage brands',
            'units.manage' => 'Manage units', 'unit_presets.manage' => 'Manage multiple-unit presets',
        ],
        'Purchases' => [
            'purchases.view' => 'View purchases', 'purchases.create' => 'Create purchases', 'purchases.edit' => 'Edit purchases',
            'purchases.cancel' => 'Cancel purchases', 'purchases.return' => 'Return purchases',
            'purchases.view_cost' => 'View purchase costs', 'purchases.make_payment' => 'Make supplier payments',
        ],
        'Sales' => [
            'sales.view' => 'View sales', 'sales.create' => 'Create sales', 'sales.cancel' => 'Cancel sales',
            'sales.return' => 'Return sales', 'sales.change_price' => 'Change sale price', 'sales.discount' => 'Apply sale discounts',
        ],
        'Main POS' => [
            'pos.main.access' => 'Access main POS', 'pos.main.sell' => 'Complete main POS sales',
            'pos.main.hold' => 'Hold main POS sales', 'pos.main.quotation' => 'Create quotations',
            'pos.main.discount' => 'Apply main POS discounts', 'pos.main.change_price' => 'Change main POS prices',
        ],
        'Remnant POS' => [
            'pos.remnant.access' => 'Access remnant POS', 'pos.remnant.sell' => 'Complete remnant sales',
            'pos.remnant.discount' => 'Apply remnant discounts', 'pos.remnant.sell_below_cost' => 'Sell remnants below cost',
        ],
        'Inventory' => [
            'inventory.view' => 'View inventory', 'inventory.view_cost' => 'View inventory costs',
            'inventory.adjust' => 'Adjust stock', 'inventory.transfer' => 'Transfer stock',
            'inventory.remnant_transfer' => 'Transfer stock to remnants', 'inventory.write_off' => 'Write off stock',
        ],
        'Customers' => [
            'customers.view' => 'View customers', 'customers.create' => 'Create customers', 'customers.edit' => 'Edit customers',
            'customers.deactivate' => 'Activate or deactivate customers', 'customers.pay_due' => 'Receive customer due payments',
            'customers.view_ledger' => 'View customer ledger',
        ],
        'Suppliers' => [
            'suppliers.view' => 'View suppliers', 'suppliers.create' => 'Create suppliers', 'suppliers.edit' => 'Edit suppliers',
            'suppliers.deactivate' => 'Activate or deactivate suppliers', 'suppliers.pay' => 'Pay suppliers',
            'suppliers.view_ledger' => 'View supplier ledger',
        ],
        'Expenses' => [
            'expenses.view' => 'View expenses', 'expenses.create' => 'Create expenses', 'expenses.edit' => 'Edit expenses',
            'expenses.delete' => 'Delete expenses', 'expense_categories.manage' => 'Manage expense categories',
        ],
        'Cheques' => [
            'cheques.view' => 'View cheques', 'cheques.receive' => 'Receive cheques', 'cheques.issue' => 'Issue cheques',
            'cheques.endorse' => 'Endorse cheques', 'cheques.pass' => 'Pass or clear cheques',
            'cheques.return' => 'Return cheques', 'cheques.cancel' => 'Cancel cheques',
        ],
        'Reports' => [
            'reports.sales' => 'View sales report', 'reports.profit_loss' => 'View profit and loss report',
            'reports.stock' => 'View stock report', 'reports.stock_value' => 'View stock valuation',
            'reports.low_stock' => 'View low stock report', 'reports.dead_stock' => 'View dead stock report',
            'reports.purchase' => 'View purchase report', 'reports.expenses' => 'View expense report',
            'reports.customer_due' => 'View customer due report', 'reports.supplier_due' => 'View supplier due report',
            'reports.cheques' => 'View cheque report', 'reports.daily_closing' => 'View daily closing report',
        ],
        'Settings' => [
            'settings.general' => 'Manage general settings', 'settings.business' => 'Manage business settings',
            'settings.invoice' => 'Manage invoice settings', 'settings.pos' => 'Manage POS settings',
            'settings.product' => 'Manage product settings', 'settings.stock' => 'Manage stock settings',
            'settings.purchase' => 'Manage purchase settings', 'settings.sales' => 'Manage sales settings',
            'settings.customer' => 'Manage customer settings', 'settings.supplier' => 'Manage supplier settings',
            'settings.account' => 'Manage account settings', 'settings.payment_methods' => 'Manage payment methods',
            'settings.sms' => 'Manage SMS settings', 'settings.report' => 'Manage report settings',
            'settings.backup' => 'Manage backups', 'settings.security' => 'Manage security settings',
            'settings.audit' => 'Manage audit settings',
        ],
        'Audit' => ['audit.view' => 'View audit log'],
    ],

    'roles' => [
        'super_admin' => ['*'],
        'admin' => ['*', '!settings.security', '!settings.backup'],
        'manager' => [
            'dashboard.*', 'products.*', 'categories.manage', 'brands.manage', 'units.manage', 'unit_presets.manage',
            'purchases.*', 'sales.*', 'pos.*', 'inventory.*', 'customers.*', 'suppliers.*', 'expenses.*',
            'cheques.*', 'reports.*', 'settings.general', 'settings.invoice', 'settings.pos', 'settings.product',
            'settings.stock', 'settings.purchase', 'settings.sales', 'settings.customer', 'settings.supplier',
            'settings.payment_methods', 'settings.sms', 'settings.report',
        ],
        'cashier' => [
            'dashboard.view', 'products.view', 'sales.view', 'sales.create', 'sales.return', 'sales.discount',
            'pos.main.*', 'pos.remnant.access', 'pos.remnant.sell', 'pos.remnant.discount',
            'customers.view', 'customers.create', 'customers.edit', 'customers.pay_due', 'customers.view_ledger',
            'cheques.view', 'cheques.receive',
        ],
        'storekeeper' => [
            'dashboard.view', 'products.view', 'products.create', 'products.edit', 'products.view_cost',
            'products.change_cost', 'products.change_selling_price', 'categories.manage', 'brands.manage',
            'units.manage', 'unit_presets.manage', 'purchases.view', 'purchases.create', 'purchases.edit',
            'purchases.return', 'purchases.view_cost', 'inventory.*', 'suppliers.view', 'suppliers.create',
            'suppliers.edit', 'suppliers.view_ledger', 'reports.stock', 'reports.stock_value',
            'reports.low_stock', 'reports.dead_stock', 'reports.purchase',
        ],
    ],
];
