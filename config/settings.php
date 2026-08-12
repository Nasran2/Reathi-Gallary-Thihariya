<?php

return [
    /*
    |--------------------------------------------------------------------------
    | General Settings
    |--------------------------------------------------------------------------
    */
    'general' => [
        'title' => 'General Settings',
        'description' => 'Control localization, currency, display preferences, and system-wide notification behavior.',
        'sections' => [
            [
                'title' => 'Localization',
                'description' => 'Regional formats used across invoices, POS, reports, and exports.',
                'fields' => [
                    ['name' => 'business_name', 'label' => 'System Name', 'type' => 'text', 'default' => 'Phone Shop POS', 'required' => true],
                    ['name' => 'default_language', 'label' => 'Default Language', 'type' => 'select', 'options' => ['en' => 'English', 'si' => 'Sinhala', 'ta' => 'Tamil'], 'default' => 'en'],
                    ['name' => 'timezone', 'label' => 'Timezone', 'type' => 'select', 'options' => ['Asia/Colombo' => 'Asia/Colombo'], 'default' => 'Asia/Colombo'],
                    ['name' => 'date_format', 'label' => 'Date Format', 'type' => 'select', 'options' => ['Y-m-d' => '2026-07-10', 'd/m/Y' => '10/07/2026'], 'default' => 'Y-m-d'],
                    ['name' => 'time_format', 'label' => 'Time Format', 'type' => 'select', 'options' => ['h:i A' => '12 hour', 'H:i' => '24 hour'], 'default' => 'h:i A'],
                ],
            ],
            [
                'title' => 'Currency & Numbers',
                'description' => 'Money and numeric precision defaults.',
                'fields' => [
                    ['name' => 'currency_symbol', 'label' => 'Default Currency', 'type' => 'text', 'default' => 'Rs.', 'required' => true],
                    ['name' => 'number_format', 'label' => 'Number Format', 'type' => 'select', 'options' => [',' => '1,234.56', '.' => '1.234,56'], 'default' => ','],
                    ['name' => 'money_decimals', 'label' => 'Decimal Places', 'type' => 'number', 'min' => 0, 'max' => 4, 'default' => 2],
                    ['name' => 'quantity_decimals', 'label' => 'Quantity display decimals', 'type' => 'number', 'min' => 0, 'max' => 6, 'default' => 3],
                ],
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Business Profile
    |--------------------------------------------------------------------------
    */
    'business-profile' => [
        'title' => 'Business Profile',
        'description' => 'Maintain legal business details shown on invoices, reports, receipts, and customer documents.',
        'sections' => [
            [
                'title' => 'Brand Identity',
                'description' => 'Primary business information used throughout the POS.',
                'fields' => [
                    ['name' => 'business_logo', 'label' => 'Business Logo', 'type' => 'file'],
                    ['name' => 'legal_name', 'label' => 'Business Name', 'type' => 'text'],
                    ['name' => 'business_registration', 'label' => 'Business Registration', 'type' => 'text'],
                    ['name' => 'tax_registration', 'label' => 'Tax Registration', 'type' => 'text'],
                ],
            ],
            [
                'title' => 'Contact Details',
                'description' => 'Customer-facing contact channels.',
                'fields' => [
                    ['name' => 'address', 'label' => 'Address', 'type' => 'textarea', 'full' => true],
                    ['name' => 'phone', 'label' => 'Phone', 'type' => 'text'],
                    ['name' => 'email', 'label' => 'Email', 'type' => 'email'],
                ],
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Branch / Store Settings
    |--------------------------------------------------------------------------
    */
    'branches' => [
        'title' => 'Branch / Store Settings',
        'description' => 'Configure multiple stores and branch locations.',
        'sections' => [
            [
                'title' => 'Branch Configuration',
                'fields' => [
                    ['name' => 'enable_multi_branch', 'label' => 'Enable Multi-Branch Support', 'type' => 'checkbox', 'default' => false],
                    ['name' => 'default_branch_id', 'label' => 'Default Branch ID', 'type' => 'text', 'default' => '1'],
                ],
            ]
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Invoice Settings
    |--------------------------------------------------------------------------
    */
    'invoice' => [
        'title' => 'Invoice Settings',
        'description' => 'Customize invoice templates, terms, and numbering.',
        'sections' => [
            [
                'title' => 'Invoice Links',
                'fields' => [
                    ['name' => 'invoice_link_expiry', 'label' => 'Invoice link expiry', 'type' => 'select', 'options' => ['never' => 'Never expires', '30_days' => '30 days', '90_days' => '90 days', '1_year' => '1 year'], 'default' => 'never'],
                ]
            ],
            [
                'title' => 'Invoice Template',
                'fields' => [
                    ['name' => 'invoice_prefix', 'label' => 'Invoice Prefix', 'type' => 'text', 'default' => 'INV-'],
                    ['name' => 'invoice_terms', 'label' => 'Terms & Conditions', 'type' => 'textarea', 'full' => true, 'default' => 'Goods once sold cannot be returned.'],
                ]
            ]
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | POS Settings
    |--------------------------------------------------------------------------
    */
    'pos' => [
        'title' => 'POS Settings',
        'description' => 'Configure the Point of Sale behavior and rules.',
        'sections' => [
            [
                'title' => 'POS Rules',
                'fields' => [
                    ['name' => 'primary_colour', 'label' => 'Primary UI Colour', 'type' => 'text', 'default' => '#177d75'],
                    ['name' => 'block_main_below_cost', 'label' => 'Block main sales below cost', 'type' => 'checkbox', 'default' => false, 'help' => 'Remnant sales remain allowed'],
                    ['name' => 'remnant_partial_sale', 'label' => 'Allow partial remnant measurement', 'type' => 'checkbox', 'default' => true, 'help' => 'Off means whole-piece selling only'],
                ],
            ]
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Product Settings (Custom View mapped in controller)
    |--------------------------------------------------------------------------
    */
    'products' => 'custom',

    /*
    |--------------------------------------------------------------------------
    | Stock Settings
    |--------------------------------------------------------------------------
    */
    'stock' => [
        'title' => 'Stock Settings',
        'description' => 'Configure inventory management and stock alerts.',
        'sections' => [
            [
                'title' => 'Inventory Rules',
                'fields' => [
                    ['name' => 'allow_negative_stock', 'label' => 'Allow negative stock', 'type' => 'checkbox', 'default' => false, 'help' => 'Recommended: off'],
                    ['name' => 'low_stock_threshold', 'label' => 'Global Low Stock Alert Threshold', 'type' => 'number', 'default' => 5],
                ],
            ]
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Barcode Settings
    |--------------------------------------------------------------------------
    */
    'barcode' => [
        'title' => 'Barcode Settings',
        'description' => 'Configure barcode generation and printing layout.',
        'sections' => [
            [
                'title' => 'Barcode Printing',
                'fields' => [
                    ['name' => 'barcode_type', 'label' => 'Barcode Encoding', 'type' => 'select', 'options' => ['C128' => 'Code 128', 'C39' => 'Code 39', 'EAN13' => 'EAN-13'], 'default' => 'C128'],
                    ['name' => 'barcode_label_width', 'label' => 'Label Width (mm)', 'type' => 'number', 'default' => 38],
                    ['name' => 'barcode_label_height', 'label' => 'Label Height (mm)', 'type' => 'number', 'default' => 25],
                ]
            ]
        ]
    ],

    /*
    |--------------------------------------------------------------------------
    | Purchase Settings
    |--------------------------------------------------------------------------
    */
    'purchase' => [
        'title' => 'Purchase Settings',
        'description' => 'Configure purchase orders and receiving rules.',
        'sections' => [
            [
                'title' => 'Purchasing',
                'fields' => [
                    ['name' => 'purchase_prefix', 'label' => 'Purchase Prefix', 'type' => 'text', 'default' => 'PO-'],
                    ['name' => 'update_cost_on_purchase', 'label' => 'Auto-update average cost on purchase', 'type' => 'checkbox', 'default' => true],
                ]
            ]
        ]
    ],

    /*
    |--------------------------------------------------------------------------
    | Sales Settings
    |--------------------------------------------------------------------------
    */
    'sales' => [
        'title' => 'Sales Settings',
        'description' => 'General sales rules not specific to POS.',
        'sections' => [
            [
                'title' => 'Sales Configuration',
                'fields' => [
                    ['name' => 'allow_credit_sales', 'label' => 'Allow Credit / Due Sales', 'type' => 'checkbox', 'default' => true],
                    ['name' => 'credit_limit_default', 'label' => 'Default Customer Credit Limit', 'type' => 'number', 'default' => 0],
                ]
            ]
        ]
    ],

    /*
    |--------------------------------------------------------------------------
    | Customer Settings
    |--------------------------------------------------------------------------
    */
    'customers' => [
        'title' => 'Customer Settings',
        'description' => 'Manage customer groups and preferences.',
        'sections' => [
            [
                'title' => 'Customer Defaults',
                'fields' => [
                    ['name' => 'default_customer_group', 'label' => 'Default Group', 'type' => 'text', 'default' => 'General'],
                    ['name' => 'require_customer_phone', 'label' => 'Require Phone Number', 'type' => 'checkbox', 'default' => false],
                ]
            ]
        ]
    ],

    /*
    |--------------------------------------------------------------------------
    | Supplier Settings
    |--------------------------------------------------------------------------
    */
    'suppliers' => [
        'title' => 'Supplier Settings',
        'description' => 'Manage supplier configurations.',
        'sections' => [
            [
                'title' => 'Supplier Defaults',
                'fields' => [
                    ['name' => 'require_supplier_tax_id', 'label' => 'Require Tax ID', 'type' => 'checkbox', 'default' => false],
                ]
            ]
        ]
    ],

    /*
    |--------------------------------------------------------------------------
    | Account Settings
    |--------------------------------------------------------------------------
    */
    'accounts' => [
        'title' => 'Account Settings',
        'description' => 'Configure accounting and ledger preferences.',
        'sections' => [
            [
                'title' => 'Accounting',
                'fields' => [
                    ['name' => 'financial_year_start', 'label' => 'Financial Year Start Month', 'type' => 'select', 'options' => ['1' => 'January', '4' => 'April', '7' => 'July'], 'default' => '1'],
                ]
            ]
        ]
    ],

    /*
    |--------------------------------------------------------------------------
    | Expense Settings
    |--------------------------------------------------------------------------
    */
    'expenses' => [
        'title' => 'Expense Settings',
        'description' => 'Manage expense approvals and limits.',
        'sections' => [
            [
                'title' => 'Expense Rules',
                'fields' => [
                    ['name' => 'require_expense_receipt', 'label' => 'Require Receipt Upload', 'type' => 'checkbox', 'default' => false],
                ]
            ]
        ]
    ],

    /*
    |--------------------------------------------------------------------------
    | Tax & Discount Settings
    |--------------------------------------------------------------------------
    */
    'taxes' => [
        'title' => 'Tax & Discount Settings',
        'description' => 'Configure tax rates and default discounts.',
        'sections' => [
            [
                'title' => 'Tax Rules',
                'fields' => [
                    ['name' => 'enable_tax', 'label' => 'Enable Tax Module', 'type' => 'checkbox', 'default' => false],
                    ['name' => 'default_tax_rate', 'label' => 'Default Tax Rate (%)', 'type' => 'number', 'step' => '0.01', 'default' => 0],
                ]
            ],
            [
                'title' => 'Discount Rules',
                'fields' => [
                    ['name' => 'max_discount_percentage', 'label' => 'Max Discount Allowed (%)', 'type' => 'number', 'default' => 100],
                ]
            ]
        ]
    ],

    /*
    |--------------------------------------------------------------------------
    | Payment Method Settings (Custom View)
    |--------------------------------------------------------------------------
    */
    'payments' => 'custom',

    /*
    |--------------------------------------------------------------------------
    | SMS & WhatsApp Settings
    |--------------------------------------------------------------------------
    */
    'sms' => [
        'title' => 'SMS & WhatsApp Settings',
        'description' => 'Configure TextIt.biz SMS gateway and notification templates.',
        'sections' => [
            [
                'title' => 'TextIt.biz SMS Gateway',
                'description' => 'Credentials are encrypted in the database. SMS defaults to off at checkout.',
                'fields' => [
                    ['name' => 'sms_enabled', 'label' => 'Enable SMS gateway', 'type' => 'checkbox', 'default' => false],
                    ['name' => 'sms_gateway_url', 'label' => 'Gateway URL', 'type' => 'text', 'default' => 'https://www.textit.biz/sendmsg'],
                    ['name' => 'sms_textit_id', 'label' => 'TextIt ID', 'type' => 'text'],
                    ['name' => 'sms_password', 'label' => 'Password / API credential', 'type' => 'password', 'placeholder' => 'Enter to set or replace'],
                    ['name' => 'sms_timeout', 'label' => 'Timeout seconds', 'type' => 'number', 'default' => 10],
                    ['name' => 'sms_template', 'label' => 'SMS template', 'type' => 'textarea', 'full' => true, 'default' => 'Thank you for shopping at {business_name}. Invoice: {invoice_no} Total: {total} Paid: {paid} Due: {due} View Bill: {invoice_url}', 'help' => 'Placeholders: {customer_name}, {business_name}, {invoice_no}, {invoice_date}, {total}, {paid}, {due}, {invoice_url}'],
                ]
            ]
        ]
    ],

    /*
    |--------------------------------------------------------------------------
    | Report Settings
    |--------------------------------------------------------------------------
    */
    'reports' => [
        'title' => 'Report Settings',
        'description' => 'Configure reporting preferences and auto-exports.',
        'sections' => [
            [
                'title' => 'Reporting',
                'fields' => [
                    ['name' => 'report_page_size', 'label' => 'Default Page Size', 'type' => 'select', 'options' => ['A4' => 'A4', 'Letter' => 'Letter'], 'default' => 'A4'],
                ]
            ]
        ]
    ],

    /*
    |--------------------------------------------------------------------------
    | Backup Settings
    |--------------------------------------------------------------------------
    */
    'backups' => [
        'title' => 'Backup Settings',
        'description' => 'Manage automated database and file backups.',
        'sections' => [
            [
                'title' => 'Automated Backups',
                'fields' => [
                    ['name' => 'enable_auto_backup', 'label' => 'Enable Daily Backups', 'type' => 'checkbox', 'default' => true],
                    ['name' => 'backup_retention_days', 'label' => 'Keep Backups For (Days)', 'type' => 'number', 'default' => 30],
                ]
            ]
        ]
    ],

    /*
    |--------------------------------------------------------------------------
    | System Security
    |--------------------------------------------------------------------------
    */
    'security' => [
        'title' => 'System Security',
        'description' => 'Configure login rules, passwords, and sessions.',
        'sections' => [
            [
                'title' => 'Security Rules',
                'fields' => [
                    ['name' => 'session_timeout', 'label' => 'Session Timeout (Minutes)', 'type' => 'number', 'default' => 120],
                    ['name' => 'require_strong_passwords', 'label' => 'Require Strong Passwords', 'type' => 'checkbox', 'default' => false],
                ]
            ]
        ]
    ],

    /*
    |--------------------------------------------------------------------------
    | Audit Log Settings
    |--------------------------------------------------------------------------
    */
    'audit' => [
        'title' => 'Audit Log Settings',
        'description' => 'Manage system tracking and activity logs.',
        'sections' => [
            [
                'title' => 'Audit Configuration',
                'fields' => [
                    ['name' => 'enable_audit_log', 'label' => 'Enable Action Logging', 'type' => 'checkbox', 'default' => true],
                    ['name' => 'audit_retention_days', 'label' => 'Keep Logs For (Days)', 'type' => 'number', 'default' => 90],
                ]
            ]
        ]
    ],
];
