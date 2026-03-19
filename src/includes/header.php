<?php // src/includes/header.php ?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>DRE Portal</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap">
<link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons+Round">
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.dataTables.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">

<!-- jQuery (loaded here so page scripts can use it) -->
<script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
<!-- Select2 -->
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<style>
:root {
  --c-primary:#1565C0; --c-primary-dark:#0D47A1; --c-primary-light:#1976D2;
  --c-primary-bg:#E3F2FD; --c-accent:#0288D1;
  --c-success:#2E7D32; --c-success-bg:#E8F5E9;
  --c-warning:#E65100; --c-warning-bg:#FFF3E0;
  --c-danger:#C62828;  --c-danger-bg:#FFEBEE;
  --c-surface:#FFFFFF; --c-bg:#F1F5F9;
  --c-border:#E2E8F0; --c-text:#1E293B; --c-text-muted:#64748B;
  --r:8px; --r-lg:12px;
  --sh:0 1px 3px rgba(0,0,0,0.08),0 1px 2px rgba(0,0,0,0.05);
  --sh-md:0 4px 12px rgba(0,0,0,0.08),0 2px 4px rgba(0,0,0,0.05);
  --sh-lg:0 10px 25px rgba(0,0,0,0.1),0 4px 8px rgba(0,0,0,0.06);
}
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
html{font-size:15px;}
body{font-family:'Inter',sans-serif;background:var(--c-bg);color:var(--c-text);min-height:100vh;}
a{color:inherit;text-decoration:none;}
.wrapper{display:flex;min-height:100vh;}

/* ── Layout ── */
.main-content{flex:1;min-width:0;overflow-x:auto;display:flex;flex-direction:column;}
.page-header{padding:22px 28px 0;}
.page-header h1{font-size:1.45rem;font-weight:600;}
.page-header .breadcrumb{font-size:0.8rem;color:var(--c-text-muted);margin-top:3px;}
.page-body{padding:18px 28px 40px;}

/* ── Card ── */
.card{background:var(--c-surface);border-radius:var(--r-lg);box-shadow:var(--sh-md);margin-bottom:18px;overflow:hidden;}
.card-header{padding:14px 20px;border-bottom:1px solid var(--c-border);display:flex;align-items:center;justify-content:space-between;}
.card-header h2,.card-header h3,.card-header h4{font-size:0.95rem;font-weight:600;}
.card-body{padding:20px;}

/* ── Forms ── */
.form-group{margin-bottom:14px;}
.form-row{display:flex;gap:14px;flex-wrap:wrap;}
.form-row>.form-group{flex:1;min-width:120px;}
.field-label{font-size:0.875rem;font-weight:500;color:var(--c-text);margin-bottom:5px;display:block;}
.field-hint{font-size:0.78rem;color:var(--c-text-muted);margin-top:3px;}
.section-label{font-size:0.75rem;font-weight:700;letter-spacing:.07em;text-transform:uppercase;color:var(--c-primary);border-left:3px solid var(--c-primary);padding-left:10px;margin:22px 0 10px;}

input[type=text],input[type=number],input[type=date],input[type=email],
select,textarea{
  display:block;width:100%;padding:9px 12px;
  border:1px solid var(--c-border);border-radius:var(--r);
  font-family:inherit;font-size:0.88rem;color:var(--c-text);
  background:var(--c-surface);transition:border-color .15s,box-shadow .15s;
}
input[type=text]:focus,input[type=number]:focus,input[type=date]:focus,
input[type=email]:focus,select:focus,textarea:focus{
  outline:none;border-color:var(--c-primary);
  box-shadow:0 0 0 3px rgba(21,101,192,.15);
}
input[type=file]{width:100%;padding:8px 0;font-size:0.85rem;color:var(--c-text-muted);}
textarea{resize:vertical;min-height:88px;}
/* ── Select2 overrides ── */
.select2-container{width:100%!important;}
.select2-container--default .select2-selection--single{
  height:38px;border:1px solid var(--c-border);border-radius:var(--r);
  transition:border-color .15s,box-shadow .15s;
}
.select2-container--default.select2-container--focus .select2-selection--single,
.select2-container--default.select2-container--open .select2-selection--single{
  border-color:var(--c-primary);box-shadow:0 0 0 3px rgba(21,101,192,.15);outline:none;
}
.select2-container--default .select2-selection--single .select2-selection__rendered{
  line-height:38px;color:var(--c-text);padding-left:12px;font-size:0.88rem;font-family:'Inter',sans-serif;
}
.select2-container--default .select2-selection--single .select2-selection__arrow{height:36px;}
.select2-dropdown{border:1px solid var(--c-border);border-radius:var(--r);box-shadow:var(--sh-md);}
.select2-search--dropdown .select2-search__field{
  border:1px solid var(--c-border);border-radius:var(--r);
  padding:6px 10px;font-family:inherit;font-size:0.85rem;
}
.select2-container--default .select2-results__option{font-size:0.875rem;padding:8px 12px;}
.select2-container--default .select2-results__option--highlighted[aria-selected]{background:var(--c-primary);}
.select2-container--default .select2-results__option[aria-selected=true]{background:var(--c-primary-bg);color:var(--c-primary-dark);}

/* ── Buttons ── */
.btn{display:inline-flex;align-items:center;gap:6px;padding:8px 16px;border:none;border-radius:var(--r);font-family:inherit;font-size:0.875rem;font-weight:500;cursor:pointer;transition:background .15s,opacity .15s;white-space:nowrap;text-decoration:none;}
.btn .material-icons-round{font-size:16px;}
.btn-primary{background:var(--c-primary);color:#fff;} .btn-primary:hover{background:var(--c-primary-dark);}
.btn-success{background:var(--c-success);color:#fff;} .btn-success:hover{background:#1B5E20;}
.btn-danger{background:var(--c-danger);color:#fff;} .btn-danger:hover{background:#B71C1C;}
.btn-warning{background:var(--c-warning);color:#fff;} .btn-warning:hover{background:#BF360C;}
.btn-secondary{background:#546E7A;color:#fff;} .btn-secondary:hover{background:#37474F;}
.btn-outline{background:transparent;color:var(--c-primary);border:1px solid var(--c-primary);} .btn-outline:hover{background:var(--c-primary-bg);}
.btn-sm{padding:5px 10px;font-size:0.8rem;} .btn-lg{padding:11px 22px;font-size:1rem;}
.btn-group{display:flex;gap:8px;flex-wrap:wrap;}

/* ── Alerts ── */
.alert{padding:12px 16px;border-radius:var(--r);font-size:0.88rem;margin-bottom:14px;display:flex;align-items:flex-start;gap:10px;}
.alert .material-icons-round{font-size:20px;flex-shrink:0;margin-top:1px;}
.alert-info{background:var(--c-primary-bg);color:var(--c-primary-dark);border-left:4px solid var(--c-primary);}
.alert-success{background:var(--c-success-bg);color:var(--c-success);border-left:4px solid var(--c-success);}
.alert-warning{background:var(--c-warning-bg);color:var(--c-warning);border-left:4px solid var(--c-warning);}
.alert-danger{background:var(--c-danger-bg);color:var(--c-danger);border-left:4px solid var(--c-danger);}

/* ── Badge ── */
.badge{display:inline-block;padding:3px 8px;border-radius:20px;font-size:0.72rem;font-weight:600;}
.badge-primary{background:var(--c-primary-bg);color:var(--c-primary);}
.badge-success{background:var(--c-success-bg);color:var(--c-success);}
.badge-warning{background:var(--c-warning-bg);color:var(--c-warning);}
.badge-danger{background:var(--c-danger-bg);color:var(--c-danger);}
.badge-secondary{background:#ECEFF1;color:#546E7A;}

/* ── Tables ── */
.data-table{width:100%;border-collapse:collapse;}
.data-table thead th{background:var(--c-primary);color:#fff;padding:10px 14px;text-align:left;font-size:0.78rem;font-weight:600;letter-spacing:.05em;text-transform:uppercase;white-space:nowrap;}
.data-table tbody td{padding:10px 14px;border-bottom:1px solid var(--c-border);font-size:0.875rem;vertical-align:middle;}
.data-table tbody tr:last-child td{border-bottom:none;}
.data-table tbody tr:hover td{background:var(--c-primary-bg);}
.data-table tfoot td,.data-table tfoot th{padding:10px 14px;background:var(--c-bg);font-weight:600;font-size:0.875rem;border-top:2px solid var(--c-border);}

.info-table{width:100%;border-collapse:collapse;}
.info-table th{width:30%;padding:10px 14px;font-weight:600;font-size:0.875rem;background:var(--c-bg);border-bottom:1px solid var(--c-border);vertical-align:top;}
.info-table td{padding:10px 14px;font-size:0.875rem;border-bottom:1px solid var(--c-border);vertical-align:top;}
.info-table tr:last-child th,.info-table tr:last-child td{border-bottom:none;}

/* ── File zones ── */
.file-zone{border:2px dashed var(--c-border);border-radius:var(--r);padding:14px;background:var(--c-bg);text-align:center;}
.file-zone:hover{border-color:var(--c-primary);background:var(--c-primary-bg);}
.file-zone input[type=file]{display:inline-block;width:auto;}
.file-download-link{display:inline-flex;align-items:center;gap:6px;font-size:0.82rem;color:var(--c-primary);font-weight:500;margin-bottom:8px;}

/* ── Cost rows ── */
.cost-entry{background:var(--c-bg);border-radius:var(--r);padding:10px 12px;margin-bottom:8px;}
.cost-entry-grid{display:grid;align-items:end;gap:8px;}
.staff-grid{grid-template-columns:2fr 1fr 1fr 1fr 32px;}
.expense-grid{grid-template-columns:2fr 1fr 1fr 1fr 32px;}
.yr-label{font-size:0.7rem;font-weight:600;color:var(--c-text-muted);text-align:center;margin-bottom:2px;}
.rm-btn{background:none;border:none;color:var(--c-danger);cursor:pointer;padding:4px;border-radius:4px;display:flex;align-items:center;justify-content:center;align-self:flex-end;height:36px;width:32px;}
.rm-btn:hover{background:var(--c-danger-bg);}
.rm-btn .material-icons-round{font-size:18px;}
.subtotal-bar{background:var(--c-primary-bg);border-radius:var(--r);padding:10px 14px;display:flex;justify-content:space-between;align-items:center;font-weight:600;color:var(--c-primary-dark);font-size:0.9rem;}

/* ── Team table ── */
.team-table{width:100%;border-collapse:collapse;margin-top:10px;}
.team-table th{background:var(--c-primary);color:#fff;padding:8px 12px;font-size:0.78rem;font-weight:600;text-align:left;}
.team-table td{padding:8px 12px;border-bottom:1px solid var(--c-border);font-size:0.875rem;}
.team-table tfoot td{font-weight:600;background:var(--c-primary-bg);color:var(--c-primary-dark);}

/* ── Total box ── */
.total-box{background:linear-gradient(135deg,#0D47A1,#1565C0);color:#fff;border-radius:var(--r-lg);padding:20px 24px;}

/* ── Stat grid ── */
.stat-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:16px;margin-bottom:20px;}
.stat-card{background:var(--c-surface);border-radius:var(--r-lg);padding:20px;box-shadow:var(--sh-md);display:flex;align-items:center;gap:14px;}
.stat-card .icon{width:48px;height:48px;border-radius:var(--r);display:flex;align-items:center;justify-content:center;}
.stat-card .icon .material-icons-round{font-size:24px;color:#fff;}
.stat-card .value{font-size:1.6rem;font-weight:700;line-height:1;}
.stat-card .label{font-size:0.78rem;color:var(--c-text-muted);margin-top:3px;}

/* ── Appendix block ── */
.appendix-block{background:var(--c-primary-bg);border:1px dashed var(--c-primary);border-radius:var(--r-lg);padding:14px 18px;margin-bottom:16px;}
.appendix-block .app-title{font-weight:600;font-size:0.875rem;color:var(--c-primary-dark);margin-bottom:8px;display:flex;align-items:center;gap:8px;}
.appendix-block .app-title .material-icons-round{font-size:18px;}

/* ── Numbered sections ── */
.numbered-section{background:var(--c-surface);border-radius:var(--r-lg);box-shadow:var(--sh);padding:20px 22px;margin-bottom:14px;border-left:4px solid var(--c-primary);}
.sec-head{display:flex;align-items:baseline;gap:10px;margin-bottom:12px;}
.sec-num{background:var(--c-primary);color:#fff;min-width:28px;height:28px;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;font-size:0.78rem;font-weight:700;flex-shrink:0;}
.sec-title{font-size:0.95rem;font-weight:600;color:var(--c-text);}
.sec-sub{font-size:0.8rem;color:var(--c-text-muted);margin-top:2px;}

/* ── Grand total ── */
.grand-total-card{background:linear-gradient(135deg,#0D47A1,#1565C0);color:#fff;border-radius:var(--r-lg);padding:20px 22px;margin-bottom:14px;}
.grand-total-card .gt-row{display:flex;justify-content:space-between;padding:5px 0;font-size:0.875rem;opacity:.9;}
.grand-total-card .gt-total{display:flex;justify-content:space-between;padding:12px 0 0;margin-top:8px;border-top:1px solid rgba(255,255,255,.3);font-size:1.15rem;font-weight:700;}

/* ── Confirm block ── */
.confirm-block{background:var(--c-primary-bg);border-radius:var(--r-lg);padding:18px 22px;margin-bottom:14px;border:1px solid var(--c-primary);}
.action-bar{background:var(--c-surface);border-top:1px solid var(--c-border);padding:14px 28px;display:flex;align-items:center;gap:12px;position:sticky;bottom:0;z-index:10;box-shadow:0 -2px 8px rgba(0,0,0,0.06);}

/* ── Utilities ── */
.mt-0{margin-top:0!important;} .mt-1{margin-top:4px!important;} .mt-2{margin-top:8px!important;}
.mt-3{margin-top:16px!important;} .mt-4{margin-top:24px!important;}
.mb-0{margin-bottom:0!important;} .mb-1{margin-bottom:4px!important;} .mb-2{margin-bottom:8px!important;}
.mb-3{margin-bottom:16px!important;} .mb-4{margin-bottom:24px!important;}
.d-flex{display:flex;} .align-center{align-items:center;} .justify-between{justify-content:space-between;}
.gap-2{gap:8px;} .gap-3{gap:16px;}
.text-muted{color:var(--c-text-muted);} .text-primary{color:var(--c-primary);}
.text-success{color:var(--c-success);} .text-danger{color:var(--c-danger);}
.fw-600{font-weight:600;} .fs-sm{font-size:0.8rem;} .hidden{display:none!important;} .w-100{width:100%!important;}

@media(max-width:640px){
  .page-body{padding:14px 12px 32px;}
  .form-row{flex-direction:column;}
  .staff-grid,.expense-grid{grid-template-columns:1fr;}
}
@media print{aside,.btn,.alert,.action-bar{display:none!important;} .card{box-shadow:none;border:1px solid #ccc;}}
</style>
</head>
<body>
<div class="wrapper">
