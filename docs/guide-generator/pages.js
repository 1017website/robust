// Katalog halaman yang di-screenshot, dikelompokkan per role.
// full: true  -> tangkap seluruh halaman (form panjang / tabel panjang)
// full: false -> tangkap satu layar penuh saja (dashboard, halaman ringkas)

const ID = {
  lead: 454,          // LD-DEMO-03 Institut Teknologi Sepuluh Nopember
  leadWon: 456,       // LD-DEMO-05 PT Cheil Jedang Indonesia
  customer: 1581,     // CUST-DEMO-02
  customerWon: 1583,  // CUST-DEMO-04
  drDone: 720,        // DR-DEMO-05 completed
  drAssigned: 717,    // DR-DEMO-02 assigned
  drCosting: 719,     // DR-DEMO-04 costing
  quoDraft: 792,      // Q-DEMO-07 draft, milik sales@robust.test
  quoSent: 788,       // Q-DEMO-03 sent_to_customer
  quoPo: 790,         // Q-DEMO-05 request_po_created
  projectRunning: 391,// PRJ-DEMO-01 ongoing
  projectFinishing: 392, // PRJ-DEMO-02 finishing
  rpoDraft: 328,      // RPO-DEMO-01 draft
  rpoSubmitted: 329,  // RPO-DEMO-02 submitted
  rpoCreated: 330,    // RPO-DEMO-03 po_created
  invoice: 190,       // INV-DEMO-01
  rpoBillable: 331,   // RPO-DEMO-04 delivery selesai, belum ditagihkan
};

const ROLES = {
  administrator: { email: 'superadmin@robust.test', label: 'Administrator' },
  sales:         { email: 'sales@robust.test',      label: 'Sales' },
  sales_spv:     { email: 'spv@robust.test',        label: 'SPV Sales' },
  drafter:       { email: 'drafter@robust.test',    label: 'Drafter' },
  production:    { email: 'production@robust.test', label: 'Produksi' },
  qc:            { email: 'qc@robust.test',         label: 'Quality Control' },
  delivery:      { email: 'delivery@robust.test',   label: 'Delivery' },
  administration:{ email: 'administration@robust.test', label: 'Administration' },
};

const PAGES = {
  administrator: [
    ['dashboard',            'Dashboard Administrator',        '/dashboard', false],
    ['pipeline',             'Monitoring Pipeline',            '/pipeline-monitoring', true],
    ['pra-leads',            'Pra Leads',                      '/admin/pra-leads', true],
    ['assignment',           'Assignment',                     '/admin/assignment', true],
    ['request-masuk',        'Request Masuk',                  '/sales/request-masuk', true],
    ['leads',                'Daftar Leads',                   '/sales/leads', true],
    ['activities',           'Activities',                     '/activities', true],
    ['design-requests',      'Daftar Design Request',          '/sales/design-requests', true],
    ['quotations',           'Daftar Penawaran',               '/sales/quotations', true],
    ['quotation-approvals',  'Monitoring Penawaran',           '/spv/quotation-approvals', true],
    ['request-po',           'Daftar Request PO',              '/admin/request-po', true],
    ['request-po-show',      'Detail Request PO',              `/admin/request-po/${ID.rpoCreated}`, true],
    ['invoices',             'Daftar Invoice',                 '/admin/invoices', true],
    ['invoice-create',       'Terbitkan Invoice',              `/admin/invoices/create?request_po=${ID.rpoBillable}`, true],
    ['invoice-show',         'Detail Invoice',                 `/admin/invoices/${ID.invoice}`, true],
    ['item-masters',         'Master Item',                    '/admin/item-masters', true],
    ['customers',            'Daftar Customer',                '/sales/customers', true],
    ['projects',             'Daftar Project',                 '/sales/projects', true],
    ['project-monitoring',   'Project Monitoring',             '/administration/project-monitoring', true],
    ['workspace',            'Workspace Project - Informasi Project', `/project-workspace/${ID.projectFinishing}`, true],
    ['workspace-ops',        'Workspace - Production, QC & Delivery', `/project-workspace/${ID.projectFinishing}`, true, '[data-bs-target="#operations"]'],
    ['calendar',             'Calendar',                       '/calendar', true],
    ['documents',            'Documents',                      '/documents', true],
    ['reports',              'Reports',                        '/reports', true],
    ['users',                'Manage User',                    '/admin/users', true],
    ['system-settings',      'System Settings',                '/admin/system-settings', true],
    ['search',               'Pencarian Global',               '/search?q=Cheil', true],
    ['profile',              'Profil & Ganti Password',        '/profile', true],
  ],

  sales: [
    ['dashboard',            'Dashboard Sales',                '/dashboard', false],
    ['request-masuk',        'Request Masuk',                  '/sales/request-masuk', true],
    ['leads',                'Daftar Leads',                   '/sales/leads', true],
    ['lead-create',          'Form Tambah Lead',               '/sales/leads/create', true],
    ['lead-show',            'Detail Lead',                    `/sales/leads/${ID.lead}`, true],
    ['lead-edit',            'Form Edit Lead',                 `/sales/leads/${ID.lead}/edit`, true],
    ['activities',           'Activities',                     '/activities', true],
    ['activity-create',      'Form Tambah Activity',           '/activities/create', true],
    ['design-requests',      'Daftar Design Request',          '/sales/design-requests', true],
    ['dr-create',            'Form Design Request Baru',       '/sales/design-requests/create', true],
    ['dr-show',              'Detail Design Request',          `/sales/design-requests/${ID.drDone}`, true],
    ['quotations',           'Daftar Penawaran',               '/sales/quotations', true],
    ['quo-create',           'Form Penawaran Baru',            '/sales/quotations/create', true],
    ['quo-edit',             'Form Edit Penawaran',            `/sales/quotations/${ID.quoDraft}/edit`, true],
    ['quo-show',             'Detail Penawaran',               `/sales/quotations/${ID.quoSent}`, true],
    ['request-po',           'Daftar Request PO',              '/admin/request-po', true],
    ['rpo-create',           'Form Request PO Baru',           '/admin/request-po/create', true],
    ['rpo-draft-edit',       'Melanjutkan Draf Request PO',    `/admin/request-po/${ID.rpoDraft}/edit`, true],
    ['rpo-show',             'Detail Request PO',              `/admin/request-po/${ID.rpoSubmitted}`, true],
    ['customers',            'Daftar Customer',                '/sales/customers', true],
    ['customer-create',      'Form Tambah Customer',           '/sales/customers/create', true],
    ['customer-show',        'Detail Customer',                `/sales/customers/${ID.customer}`, true],
    ['projects',             'Daftar Project',                 '/sales/projects', true],
    ['project-create',       'Form Project Baru',              '/sales/projects/create', true],
    ['project-show',         'Detail Project',                 `/sales/projects/${ID.projectRunning}`, true],
    ['pra-leads',            'Pra Leads',                      '/admin/pra-leads', true],
    ['invoices',             'Daftar Invoice',                 '/admin/invoices', true],
    ['project-monitoring',   'Project Monitoring',             '/administration/project-monitoring', true],
    ['users',                'Manage User',                    '/admin/users', true],
    ['calendar',             'Calendar',                       '/calendar', true],
    ['documents',            'Documents',                      '/documents', true],
    ['reports',              'Reports',                        '/reports', true],
  ],

  sales_spv: [
    ['dashboard',            'Dashboard SPV Sales',            '/dashboard', false],
    ['assignment',           'Assignment',                     '/admin/assignment', true],
    ['quotation-approvals',  'Monitoring Penawaran',           '/spv/quotation-approvals', true],
    ['quotation-approval-show', 'Detail Penawaran (SPV)',      `/spv/quotation-approvals/${ID.quoSent}`, true],
    ['request-masuk',        'Request Masuk',                  '/sales/request-masuk', true],
    ['leads',                'Daftar Leads',                   '/sales/leads', true],
    ['design-requests',      'Daftar Design Request',          '/sales/design-requests', true],
    ['customers',            'Daftar Customer',                '/sales/customers', true],
    ['projects',             'Daftar Project',                 '/sales/projects', true],
    ['activities',           'Activities',                     '/activities', true],
    ['reports',              'Reports',                        '/reports', true],
  ],

  drafter: [
    ['dashboard',            'Dashboard Drafter',              '/dashboard', false],
    ['design-requests',      'Daftar Design Request',          '/drafter/design-requests', true],
    ['dr-show',              'Mengerjakan Design Request',     `/drafter/design-requests/${ID.drAssigned}`, true],
    ['dr-costing',           'Design Request Tahap Costing',   `/drafter/design-requests/${ID.drCosting}`, true],
    ['projects',             'Daftar Project',                 '/drafter/projects', true],
    ['workspace',            'Workspace Project',              `/project-workspace/${ID.projectRunning}`, true, '[data-bs-target="#operations"]'],
    ['tasks',                'Tasks',                          '/drafter/tasks', true],
    ['documents',            'Documents',                      '/documents', true],
    ['calendar',             'Calendar',                       '/drafter/calendar', true],
    ['reports',              'Reports',                        '/drafter/reports', true],
  ],

  production: [
    ['dashboard',            'Halaman Awal Produksi',          '/drafter/projects', true],
    ['design-requests',      'Daftar Design Request',          '/drafter/design-requests', true],
    ['dr-show',              'Mengisi Spesifikasi & HPP',      `/drafter/design-requests/${ID.drCosting}`, true],
    ['item-masters',         'Master Item',                    '/admin/item-masters', true],
    ['workspace',            'Update Progres Produksi',        `/project-workspace/${ID.projectRunning}`, true, '[data-bs-target="#operations"]'],
    ['documents',            'Dokumen Project',                '/documents', true],
    ['calendar',             'Calendar',                       '/drafter/calendar', true],
    ['reports',              'Reports',                        '/drafter/reports', true],
  ],

  qc: [
    ['dashboard',            'Halaman Awal QC',                '/drafter/projects', true],
    ['workspace-spec',       'Spesifikasi Penawaran sebagai Acuan QC', `/project-workspace/${ID.projectFinishing}`, true, '[data-bs-target="#design-request"]'],
    ['workspace',            'Checklist QC pada Workspace',    `/project-workspace/${ID.projectFinishing}`, true, '[data-bs-target="#operations"]'],
    ['calendar',             'Calendar',                       '/drafter/calendar', true],
    ['profile',              'Profil & Ganti Password',        '/profile', true],
  ],

  delivery: [
    ['dashboard',            'Halaman Awal Delivery',          '/drafter/projects', true],
    ['workspace',            'Pengiriman pada Workspace',      `/project-workspace/${ID.projectFinishing}`, true, '[data-bs-target="#operations"]'],
    ['calendar',             'Calendar',                       '/drafter/calendar', true],
    ['profile',              'Profil & Ganti Password',        '/profile', true],
  ],

  administration: [
    ['dashboard',            'Halaman Awal Administration',    '/administration/project-monitoring', true],
    ['projects',             'Daftar Project',                 '/drafter/projects', true],
    ['workspace',            'Workspace Project',              `/project-workspace/${ID.projectFinishing}`, true, '[data-bs-target="#operations"]'],
    ['calendar',             'Calendar',                       '/calendar', true],
    ['reports',              'Reports',                        '/reports', true],
  ],
};

module.exports = { ID, ROLES, PAGES };
