<?php // src/includes/custom_header.php ?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>DRE Admin Portal</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/icon?family=Material+Icons+Round" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
<style>
:root{
  --c-primary:#0D47A1;--c-primary-dark:#0a3680;--c-primary-bg:#EFF3FB;
  --c-surface:#ffffff;--c-bg:#F4F6FA;--c-border:#DDE3EE;
  --c-text:#1A2236;--c-text-muted:#6B7A99;
  --c-success:#1B7F5A;--c-success-bg:#E6F4EE;
  --c-danger:#C0392B;--c-danger-bg:#FDE9E8;
  --c-warning:#D97706;--c-warning-bg:#FEF3C7;
  --c-info:#0369A1;--c-info-bg:#E0F2FE;
  --r:8px;--r-lg:12px;--sh:0 1px 4px rgba(0,0,0,.08);
  --sidebar-w:240px;
}
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
body{font-family:'Inter',sans-serif;background:var(--c-bg);color:var(--c-text);font-size:0.875rem;min-height:100vh;}
a{color:var(--c-primary);text-decoration:none;}
a:hover{text-decoration:underline;}

/* Layout */
.wrapper{display:flex;min-height:100vh;}
.main-area{flex:1;display:flex;flex-direction:column;min-width:0;}
.main-content{flex:1;padding:24px 28px;}
.page-header{margin-bottom:20px;}
.page-header h1{font-size:1.5rem;font-weight:700;color:var(--c-text);}
.breadcrumb{font-size:0.78rem;color:var(--c-text-muted);margin-top:4px;}

/* Cards */
.card{background:var(--c-surface);border-radius:var(--r-lg);box-shadow:var(--sh);margin-bottom:20px;}
.card-header{padding:16px 20px;border-bottom:1px solid var(--c-border);display:flex;align-items:center;justify-content:space-between;}
.card-header h2,.card-header h3,.card-header h5{font-size:1rem;font-weight:600;margin:0;}
.card-body{padding:20px;}

/* Stat cards */
.stat-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:16px;margin-bottom:24px;}
.stat-card{background:var(--c-surface);border-radius:var(--r-lg);box-shadow:var(--sh);padding:20px;display:flex;align-items:center;gap:14px;}
.stat-card .stat-icon{width:48px;height:48px;border-radius:var(--r);display:flex;align-items:center;justify-content:center;}
.stat-card .stat-icon .material-icons-round{font-size:22px;color:#fff;}
.stat-card .stat-label{font-size:0.78rem;color:var(--c-text-muted);}
.stat-card .stat-value{font-size:1.6rem;font-weight:700;color:var(--c-text);line-height:1;}

/* Tables */
table.dt-table{width:100%;border-collapse:collapse;}
.data-table{width:100%;border-collapse:collapse;}
.data-table th{font-size:0.75rem;font-weight:600;color:var(--c-text-muted);text-transform:uppercase;letter-spacing:.04em;padding:10px 12px;background:var(--c-bg);border-bottom:2px solid var(--c-border);text-align:left;}
.data-table td{padding:11px 12px;border-bottom:1px solid var(--c-border);vertical-align:middle;font-size:0.875rem;}
.data-table tr:last-child td{border-bottom:none;}
.data-table tr:hover td{background:var(--c-primary-bg);}

/* Forms */
.form-group{margin-bottom:14px;}
.field-label{display:block;font-size:0.78rem;font-weight:600;color:var(--c-text-muted);margin-bottom:5px;text-transform:uppercase;letter-spacing:.04em;}
input[type=text],input[type=email],input[type=number],input[type=date],input[type=password],textarea,select{
  width:100%;padding:9px 12px;border:1.5px solid var(--c-border);border-radius:var(--r);
  font-family:'Inter',sans-serif;font-size:0.875rem;color:var(--c-text);
  background:var(--c-surface);transition:border .15s;
}
input:focus,textarea:focus,select:focus{outline:none;border-color:var(--c-primary);box-shadow:0 0 0 3px rgba(13,71,161,.1);}
textarea{resize:vertical;}
.form-row{display:flex;gap:14px;flex-wrap:wrap;}
.form-row .form-group{flex:1;min-width:180px;}

/* Buttons */
.btn{display:inline-flex;align-items:center;gap:6px;padding:9px 18px;border-radius:var(--r);font-size:0.875rem;font-weight:500;cursor:pointer;border:1.5px solid transparent;transition:background .15s,border-color .15s,color .15s;white-space:nowrap;text-decoration:none;}
.btn .material-icons-round{font-size:16px;}
.btn-primary{background:var(--c-primary);color:#fff;border-color:var(--c-primary);}
.btn-primary:hover{background:var(--c-primary-dark);border-color:var(--c-primary-dark);text-decoration:none;color:#fff;}
.btn-secondary{background:var(--c-bg);color:var(--c-text);border-color:var(--c-border);}
.btn-secondary:hover{background:var(--c-border);text-decoration:none;color:var(--c-text);}
.btn-outline{background:transparent;color:var(--c-primary);border-color:var(--c-primary);}
.btn-outline:hover{background:var(--c-primary-bg);text-decoration:none;color:var(--c-primary);}
.btn-danger{background:var(--c-danger);color:#fff;border-color:var(--c-danger);}
.btn-danger:hover{background:#a93226;border-color:#a93226;text-decoration:none;color:#fff;}
.btn-success{background:var(--c-success);color:#fff;border-color:var(--c-success);}
.btn-success:hover{background:#166447;border-color:#166447;text-decoration:none;color:#fff;}
.btn-sm{padding:5px 12px;font-size:0.8rem;}
.btn-sm .material-icons-round{font-size:14px;}
.btn-lg{padding:11px 22px;font-size:0.95rem;}

/* Badges */
.badge{display:inline-flex;align-items:center;padding:3px 9px;border-radius:20px;font-size:0.72rem;font-weight:600;}
.badge-primary{background:var(--c-primary-bg);color:var(--c-primary);}
.badge-success{background:var(--c-success-bg);color:var(--c-success);}
.badge-danger{background:var(--c-danger-bg);color:var(--c-danger);}
.badge-warning{background:var(--c-warning-bg);color:var(--c-warning);}
.badge-info{background:var(--c-info-bg);color:var(--c-info);}
.badge-secondary{background:var(--c-border);color:var(--c-text-muted);}

/* Alerts */
.alert{padding:12px 16px;border-radius:var(--r);margin-bottom:16px;font-size:0.875rem;display:flex;align-items:center;gap:10px;}
.alert-success{background:var(--c-success-bg);color:var(--c-success);}
.alert-info{background:var(--c-info-bg);color:var(--c-info);}
.alert-danger{background:var(--c-danger-bg);color:var(--c-danger);}

/* Modal */
.modal-backdrop{position:fixed;inset:0;background:rgba(0,0,0,.4);z-index:1000;display:none;align-items:center;justify-content:center;}
.modal-backdrop.show{display:flex;}
.modal-box{background:var(--c-surface);border-radius:var(--r-lg);box-shadow:0 8px 40px rgba(0,0,0,.18);width:100%;max-width:600px;max-height:90vh;overflow-y:auto;margin:16px;}
.modal-header{padding:18px 20px;border-bottom:1px solid var(--c-border);display:flex;align-items:center;justify-content:space-between;}
.modal-header h3{font-size:1rem;font-weight:600;margin:0;}
.modal-close{background:none;border:none;cursor:pointer;color:var(--c-text-muted);font-size:20px;display:flex;padding:4px;}
.modal-close:hover{color:var(--c-text);}
.modal-body{padding:20px;}
.modal-footer{padding:14px 20px;border-top:1px solid var(--c-border);display:flex;justify-content:flex-end;gap:10px;}

/* Detail table (view submission) */
.detail-table{width:100%;border-collapse:collapse;}
.detail-table th{width:30%;font-size:0.8rem;font-weight:600;color:var(--c-text-muted);padding:10px 12px;background:var(--c-bg);border-bottom:1px solid var(--c-border);text-align:left;vertical-align:top;}
.detail-table td{padding:10px 12px;border-bottom:1px solid var(--c-border);font-size:0.875rem;}
.detail-table tr:last-child th,.detail-table tr:last-child td{border-bottom:none;}

/* File download link */
.file-download-link{display:inline-flex;align-items:center;gap:6px;padding:6px 12px;border:1.5px solid var(--c-border);border-radius:var(--r);font-size:0.8rem;font-weight:500;color:var(--c-primary);background:var(--c-surface);transition:background .15s;}
.file-download-link:hover{background:var(--c-primary-bg);border-color:var(--c-primary);text-decoration:none;}
.file-download-link .material-icons-round{font-size:15px;}

/* Text utils */
.text-muted{color:var(--c-text-muted);}
.mt-1{margin-top:6px;}.mt-2{margin-top:12px;}.mt-3{margin-top:18px;}
.mb-0{margin-bottom:0;}.mb-1{margin-bottom:6px;}.mb-2{margin-bottom:12px;}
.fs-sm{font-size:0.78rem;}
.d-flex{display:flex;}.align-center{align-items:center;}.gap-2{gap:8px;}.gap-3{gap:12px;}
.justify-between{justify-content:space-between;}

/* Select2 overrides */
.select2-container--default .select2-selection--single{
  border:1.5px solid var(--c-border);border-radius:var(--r);height:38px;
  padding:4px 10px;font-family:'Inter',sans-serif;font-size:0.875rem;
  background:var(--c-surface);
}
.select2-container--default .select2-selection--single .select2-selection__rendered{
  color:var(--c-text);line-height:28px;padding:0;
}
.select2-container--default .select2-selection--single .select2-selection__arrow{height:36px;right:8px;}
.select2-container--default.select2-container--focus .select2-selection--single,
.select2-container--default.select2-container--open .select2-selection--single{
  border-color:var(--c-primary);box-shadow:0 0 0 3px rgba(13,71,161,.1);
}
.select2-dropdown{border:1.5px solid var(--c-primary);border-radius:var(--r);box-shadow:0 4px 12px rgba(0,0,0,.1);}
.select2-search--dropdown .select2-search__field{border:1.5px solid var(--c-border);border-radius:var(--r);padding:6px 10px;font-family:'Inter',sans-serif;}
.select2-results__option--highlighted[aria-selected]{background:var(--c-primary);}
</style>

<!-- jQuery (before everything so page scripts work) -->
<script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
<!-- Select2 -->
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
</head>
<body>
<div class="wrapper">
