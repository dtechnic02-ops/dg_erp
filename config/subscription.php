<?php



return [

    'register_trial_days' => 7,

    'register_trial_staff_limit' => 5,

    'free_trial_days' => 30,

    'free_trial_staff_limit' => 100,

    'expiring_soon_days' => 14,



    'hidden_module_codes' => [

        'crm',

        'loan',

        'hr',

        'delivery',

    ],



    'route_module_map' => [

        'company.crm.' => 'crm',

        'company.crm-leads.' => 'crm',

        'company.crm-contacts.' => 'crm',

        'company.crm-opportunities.' => 'crm',

        'company.crm-follow-ups.' => 'crm',

        'company.crm-meetings.' => 'crm',

        'company.crm-tasks.' => 'crm',

        'company.crm-notes.' => 'crm',

        'company.crm-attachments.' => 'crm',

        'company.party-account.' => 'loan',

        'company.loan-account.' => 'loan',

        'company.loan-payment.' => 'loan',

        'company.loan-saving-ledger.' => 'loan',

        'company.loan-saving-withdraw.' => 'loan',

        'company.employee-account.' => 'hr',

        'company.salary-sheets.' => 'hr',

        'company.employee-payment.' => 'hr',

        'company.employee-ledger.' => 'hr',

        'company.payroll-register.' => 'hr',

        'company.delivery-notes.' => 'delivery',

    ],

];

