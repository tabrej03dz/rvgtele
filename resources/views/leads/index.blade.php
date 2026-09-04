@extends('layouts.crm', ['title' => 'Leads'])

@section('content')

@once
<style>
    [x-cloak] { display: none !important; }

    .lead-board {
        font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        color: #172033;
    }

    .stats-grid {
        display: grid;
        grid-template-columns: repeat(8, minmax(135px, 1fr));
        gap: 10px;
    }

    .stat-card {
        min-height: 82px;
        background: #fff;
        border: 1px solid #e7eaf0;
        border-radius: 11px;
        padding: 13px;
        display: flex;
        align-items: center;
        gap: 11px;
        box-shadow: 0 2px 8px rgba(15, 23, 42, .035);
    }

    .stat-card-link {
        color: inherit;
        text-decoration: none;
        cursor: pointer;
        transition: transform .15s ease, box-shadow .15s ease, border-color .15s ease;
    }

    .stat-card-link:hover {
        transform: translateY(-2px);
        border-color: #f3c54a;
        box-shadow: 0 8px 20px rgba(15, 23, 42, .08);
    }

    .stat-card-link:focus-visible {
        outline: 3px solid rgba(245, 185, 0, .22);
        outline-offset: 2px;
    }

    .stat-card-link .stat-label,
    .stat-card-link .stat-number,
    .stat-card-link .stat-sub {
        color: inherit;
    }

    .stat-icon {
        width: 40px;
        height: 40px;
        flex: 0 0 40px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .stat-icon svg { width: 20px; height: 20px; }
    .stat-label { font-size: 11px; font-weight: 700; color: #344054; line-height: 1.15; }
    .stat-number { margin-top: 4px; font-size: 21px; line-height: 1; font-weight: 800; color: #111827; }
    .stat-sub { margin-top: 4px; font-size: 9px; color: #98a2b3; }

    .board-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(350px, 1fr));
        gap: 12px;
        align-items: start;
    }

    .call-column {
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 2px 10px rgba(15, 23, 42, .04);
    }

    .new-column { border-top: 3px solid #18a957; }
    .dialed-column { border-top: 3px solid #f28b19; }
    .connected-column { border-top: 3px solid #1769d2; }

    .column-header {
        padding: 14px 17px 12px;
        border-bottom: 1px solid #eceff3;
    }

    .column-heading {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 9px;
        font-size: 18px;
        font-weight: 800;
    }

    .column-heading svg { width: 20px; height: 20px; }
    .new-color { color: #159447; }
    .dialed-color { color: #e87508; }
    .connected-color { color: #0759b5; }

    .count-pill {
        padding: 3px 9px;
        border-radius: 12px;
        color: #fff;
        font-size: 11px;
        line-height: 1.4;
        font-weight: 800;
    }

    .new-pill { background: #35aa5e; }
    .dialed-pill { background: #f7a238; }
    .connected-pill { background: #2c7bdc; }

    .column-description {
        margin-top: 4px;
        text-align: center;
        font-size: 10px;
        color: #667085;
    }

    .filter-toggle-row {
        padding: 9px 12px;
        border-bottom: 1px solid #edf0f4;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        background: #fff;
    }

    .section-filter-btn {
        min-height: 32px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        border: 1px solid #dde2ea;
        border-radius: 7px;
        padding: 0 11px;
        background: #fff;
        color: #344054;
        font-size: 9px;
        font-weight: 800;
        cursor: pointer;
        transition: .15s;
    }

    .section-filter-btn:hover { background: #f8fafc; }
    .section-filter-btn svg { width: 13px; height: 13px; }
    .section-filter-btn.active-new { border-color: #86d7a2; background: #effbf3; color: #159447; }
    .section-filter-btn.active-dialed { border-color: #f5c27e; background: #fff8ed; color: #e87508; }
    .section-filter-btn.active-connected { border-color: #9ac4ee; background: #eff7ff; color: #0759b5; }

    .active-filter-count {
        min-width: 19px;
        height: 19px;
        padding: 0 5px;
        border-radius: 10px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: currentColor;
        font-size: 9px;
    }

    .active-filter-count span { color: #fff; }

    .column-filter {
        padding: 12px;
        border-bottom: 1px solid #edf0f4;
        background: #fbfcfe;
    }

    .filter-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 8px;
    }

    .filter-field label {
        display: block;
        margin-bottom: 4px;
        color: #667085;
        font-size: 8px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: .03em;
    }

    .filter-grid select {
        width: 100%;
        min-height: 34px;
        border: 1px solid #dfe3e8;
        border-radius: 6px;
        padding: 0 8px;
        background: #fff;
        color: #344054;
        font-size: 10px;
        outline: none;
    }

    .filter-grid select:focus {
        border-color: #f5b900;
        box-shadow: 0 0 0 2px rgba(245, 185, 0, .08);
    }

    .filter-actions {
        grid-column: 1 / -1;
        display: flex;
        align-items: center;
        justify-content: flex-end;
        gap: 7px;
        padding-top: 3px;
    }

    .apply-filter-btn {
        height: 32px;
        border: 0;
        border-radius: 6px;
        padding: 0 14px;
        color: #fff;
        font-size: 9px;
        font-weight: 800;
        cursor: pointer;
    }

    .apply-new { background: #159447; }
    .apply-dialed { background: #e87508; }
    .apply-connected { background: #0759b5; }

    .clear-filter-btn {
        height: 32px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border: 1px solid #dfe3e8;
        border-radius: 6px;
        padding: 0 12px;
        background: #fff;
        color: #667085;
        font-size: 9px;
        font-weight: 800;
    }

    .lead-list { padding: 10px; }

    .lead-card {
        position: relative;
        margin-bottom: 9px;
        border: 1px solid #e4e7ec;
        border-radius: 10px;
        padding: 12px;
        background: #fff;
        transition: .15s ease;
    }

    .lead-card:hover { transform: translateY(-1px); box-shadow: 0 6px 15px rgba(15, 23, 42, .06); }
    .new-card { border-left: 3px solid #43b96b; }
    .dialed-card { border-left: 3px solid #f4ad51; background: linear-gradient(90deg, #fffdf8 0%, #fff 16%); }
    .connected-card { border-left: 3px solid #72aee8; background: linear-gradient(90deg, #fafdff 0%, #fff 16%); }

    .lead-top { display: flex; align-items: flex-start; justify-content: space-between; gap: 10px; }
    .lead-profile { display: flex; min-width: 0; gap: 10px; }

    .lead-avatar {
        width: 37px;
        height: 37px;
        flex: 0 0 37px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        font-size: 13px;
        font-weight: 800;
    }

    .avatar-new { background: #e8f8ed; color: #16994b; }
    .avatar-dialed { background: #fff3df; color: #dc7609; }
    .avatar-connected { background: #eaf3ff; color: #125cb4; }

    .lead-name {
        display: block;
        max-width: 170px;
        overflow: hidden;
        color: #101828;
        font-size: 12px;
        font-weight: 800;
        text-decoration: none;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .lead-name:hover { color: #1769d2; }
    .lead-meta { margin-top: 4px; display: flex; align-items: center; gap: 5px; color: #475467; font-size: 9.5px; }
    .lead-meta svg { width: 11px; height: 11px; }
    .lead-right { flex: 0 0 auto; text-align: right; font-size: 9px; }
    .lead-badge { font-weight: 700; }
    .call-time { display: flex; justify-content: flex-end; align-items: center; gap: 4px; color: #475467; }
    .call-time svg { width: 11px; height: 11px; }
    .call-state { margin-top: 6px; font-weight: 700; }
    .feedback-row { margin-top: 9px; font-size: 9.5px; color: #475467; line-height: 1.45; }
    .feedback-label,
    .note-label { color: #667085; }
    .feedback-value { font-weight: 700; color: #101828; }
    .note-row { margin-top: 4px; font-size: 9.5px; color: #475467; line-height: 1.45; }
    .note-value { font-weight: 700; color: #7c3aed; }
    .followup-row { margin-top: 4px; display: flex; align-items: center; gap: 4px; color: #475467; font-size: 9px; }
    .followup-row svg { width: 11px; height: 11px; }
    .card-bottom { margin-top: 10px; display: flex; align-items: center; justify-content: space-between; gap: 8px; }

    .category-tag {
        border-radius: 5px;
        padding: 4px 9px;
        background: #f4f3ff;
        color: #6941c6;
        font-size: 9px;
        font-weight: 600;
    }

    .action-group { display: flex; align-items: center; gap: 7px; }

    .round-action {
        width: 34px;
        height: 31px;
        border: 1px solid #dfe4ea;
        border-radius: 7px;
        background: #fff;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: .15s;
    }

    .round-action svg { width: 15px; height: 15px; }
    .call-action { color: #139b4b; }
    .whatsapp-action { color: #18a957; }
    .open-action { color: #d99b00; }
    .round-action:hover { background: #f8fafc; transform: translateY(-1px); }

    .demo-sent {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 5px 8px;
        border-radius: 6px;
        background: #eaf3ff;
        color: #1262c5;
        font-size: 9px;
        font-weight: 800;
    }

    .demo-sent svg { width: 13px; height: 13px; }
    .column-footer { padding: 10px 12px; border-top: 1px solid #edf0f4; text-align: center; font-size: 10px; font-weight: 800; }
    .empty-board { padding: 35px 15px; text-align: center; color: #98a2b3; font-size: 11px; }

    .board-tip {
        margin-top: 11px;
        padding: 11px 15px;
        border: 1px solid #f4e3aa;
        border-radius: 8px;
        background: #fffaf0;
        display: flex;
        align-items: center;
        gap: 10px;
        font-size: 10px;
        color: #475467;
    }

    @media (max-width: 1350px) {
        .stats-grid { grid-template-columns: repeat(4, 1fr); }
    }

    @media (max-width: 1100px) {
        .board-grid { grid-template-columns: 1fr; }
    }

    @media (max-width: 700px) {
        .stats-grid { grid-template-columns: repeat(2, 1fr); }
    }

    /* QUICK FEEDBACK BUTTON + PREMIUM MODAL */
    .feedback-action {
        color:#7c3aed !important;
        background:#f5f3ff !important;
        border-color:#ddd6fe !important;
        padding:0 9px !important;
        width:auto !important;
    }

    .feedback-action span {
        font-size:9px;
        font-weight:800;
    }

    .quick-modal-backdrop {
        position:fixed;
        inset:0;
        z-index:100;
        display:flex;
        align-items:center;
        justify-content:center;
        padding:18px;
        background:rgba(15,23,42,.58);
        backdrop-filter:blur(3px);
        overflow-y:auto;
        overscroll-behavior:contain;
        -webkit-overflow-scrolling:touch;
    }

    .quick-modal {
        width:100%;
        max-width:800px;
        max-height:calc(100dvh - 36px);
        display:flex;
        flex-direction:column;
        overflow:hidden;
        border:1px solid #f4ddb0;
        border-radius:18px;
        background:
            linear-gradient(145deg,#fffef9 0%,#fffaf0 42%,#fff 100%);
        box-shadow:
            0 30px 90px rgba(15,23,42,.28),
            0 0 0 1px rgba(245,185,0,.05);
    }

    .quick-modal-head {
        position:relative;
        flex:0 0 auto;
        display:flex;
        align-items:flex-start;
        justify-content:space-between;
        gap:14px;
        padding:20px 24px 15px;
        border-bottom:0;
        background:
            radial-gradient(circle at 92% 10%,rgba(245,185,0,.12),transparent 34%),
            transparent;
    }

    .quick-modal-tag {
        display:inline-flex;
        align-items:center;
        gap:6px;
        min-height:28px;
        padding:0 10px;
        border-radius:8px;
        background:#fff1bf;
        color:#9a6500;
        font-size:9px;
        font-weight:900;
        text-transform:uppercase;
        letter-spacing:.03em;
    }

    .quick-modal-tag svg {
        width:13px;
        height:13px;
    }

    .quick-modal-title {
        margin-top:8px;
        color:#111827;
        font-size:22px;
        line-height:1.1;
        font-weight:900;
        letter-spacing:-.02em;
    }

    .quick-modal-meta {
        margin-top:8px;
        display:flex;
        flex-wrap:wrap;
        align-items:center;
        gap:14px;
        color:#7c6b4d;
        font-size:10px;
        font-weight:600;
    }

    .quick-modal-meta span {
        display:inline-flex;
        align-items:center;
        gap:5px;
    }

    .quick-modal-meta svg {
        width:13px;
        height:13px;
        color:#d99b00;
    }

    .quick-close {
        width:42px;
        height:42px;
        flex:0 0 42px;
        display:inline-flex;
        align-items:center;
        justify-content:center;
        border:1px solid #dedede;
        border-radius:13px;
        background:#fff;
        color:#475467;
        box-shadow:0 4px 12px rgba(15,23,42,.05);
        cursor:pointer;
    }

    .quick-close:hover {
        background:#fff8e8;
        border-color:#efc95a;
        color:#111827;
    }

    .quick-close svg {
        width:20px;
        height:20px;
    }

    .quick-modal-body {
        flex:1 1 auto;
        min-height:0;
        overflow-y:auto;
        overscroll-behavior:contain;
        -webkit-overflow-scrolling:touch;
        padding:0 24px 22px;
        background:transparent;
    }

    .quick-summary {
        display:grid;
        grid-template-columns:repeat(4,minmax(0,1fr));
        gap:10px;
        margin-bottom:14px;
    }

    .quick-summary-card {
        min-height:86px;
        display:flex;
        align-items:center;
        gap:10px;
        border:1px solid #eadfc9;
        border-radius:13px;
        background:rgba(255,255,255,.88);
        padding:12px;
        box-shadow:0 5px 16px rgba(83,63,22,.035);
    }

    .quick-summary-icon {
        width:38px;
        height:38px;
        flex:0 0 38px;
        display:flex;
        align-items:center;
        justify-content:center;
        border-radius:50%;
        background:#fff4cb;
        color:#d99b00;
    }

    .quick-summary-icon svg {
        width:17px;
        height:17px;
    }

    .quick-summary-label {
        font-size:8px;
        font-weight:900;
        color:#8a8f98;
        text-transform:uppercase;
        letter-spacing:.04em;
    }

    .quick-summary-value {
        margin-top:4px;
        font-size:11px;
        line-height:1.35;
        font-weight:900;
        color:#1f2937;
        overflow-wrap:anywhere;
    }

    .quick-top-actions {
        display:grid;
        grid-template-columns:1fr 1fr 1fr;
        gap:9px;
        margin-bottom:10px;
    }

    .quick-primary-action {
        min-height:50px;
        display:flex;
        align-items:center;
        justify-content:center;
        gap:9px;
        border:1px solid #f0c333;
        border-radius:11px;
        background:linear-gradient(180deg,#ffd22e 0%,#ffc400 100%);
        color:#171717;
        font-size:10px;
        font-weight:900;
        cursor:pointer;
        text-decoration:none;
        box-shadow:0 7px 16px rgba(255,196,0,.15);
    }

    .quick-primary-action.secondary {
        background:#fff;
        border-color:#eccd71;
        box-shadow:none;
    }

    .quick-primary-action svg {
        width:18px;
        height:18px;
    }

    .quick-tabs {
        display:grid;
        grid-template-columns:repeat(3,1fr);
        gap:9px;
        margin-bottom:12px;
    }

    .quick-tab {
        min-height:50px;
        display:inline-flex;
        align-items:center;
        justify-content:center;
        gap:8px;
        border:1px solid #e8cb78;
        border-radius:11px;
        background:#fff;
        padding:0 12px;
        color:#344054;
        font-size:10px;
        font-weight:900;
        cursor:pointer;
        text-decoration:none;
        transition:.15s ease;
    }

    .quick-tab svg {
        width:17px;
        height:17px;
    }

    .quick-tab.active {
        border-color:#f1b900;
        background:#fff8dc;
        color:#1f2937;
        box-shadow:inset 0 0 0 1px rgba(241,185,0,.12);
    }

    .quick-panel {
        border:1px solid #eadfc9;
        border-radius:13px;
        background:rgba(255,255,255,.90);
        padding:16px;
    }

    .quick-grid {
        display:grid;
        grid-template-columns:1fr 1fr;
        gap:14px;
    }

    .quick-field label {
        display:block;
        margin-bottom:6px;
        color:#475569;
        font-size:10px;
        font-weight:900;
    }

    .quick-field input,
    .quick-field select,
    .quick-field textarea {
        width:100%;
        border:1px solid #d7dde7;
        border-radius:10px;
        background:#fff;
        color:#1e293b;
        font-size:11px;
        outline:none;
    }

    .quick-field input,
    .quick-field select {
        min-height:46px;
        padding:0 12px;
    }

    .quick-field textarea {
        min-height:96px;
        padding:10px 12px;
        resize:vertical;
    }

    .quick-field input:focus,
    .quick-field select:focus,
    .quick-field textarea:focus {
        border-color:#f1b900;
        box-shadow:0 0 0 3px rgba(241,185,0,.12);
    }

    .quick-form-actions {
        position:sticky;
        bottom:-1px;
        z-index:12;
        display:flex;
        align-items:center;
        justify-content:flex-end;
        gap:9px;
        margin-top:16px;
        padding:12px 0 4px;
        background:linear-gradient(to top, #fff 72%, rgba(255,255,255,.90) 88%, rgba(255,255,255,0));
    }

    .quick-btn {
        min-height:42px;
        display:inline-flex;
        align-items:center;
        justify-content:center;
        gap:7px;
        border:1px solid #d8dee7;
        border-radius:10px;
        background:#fff;
        padding:0 15px;
        color:#475467;
        font-size:9px;
        font-weight:900;
        cursor:pointer;
        text-decoration:none;
    }

    .quick-btn-green {
        border-color:#059669;
        background:#059669;
        color:#fff;
    }

    .quick-btn-blue {
        border-color:#2563eb;
        background:#2563eb;
        color:#fff;
    }

    .quick-btn-violet {
        border-color:#f1b900;
        background:linear-gradient(180deg,#ffd22e 0%,#ffc400 100%);
        color:#171717;
    }

    .quick-btn-dark {
        border-color:#334155;
        background:#334155;
        color:#fff;
    }

    .quick-status {
        border:1px solid #f0dfac;
        border-radius:10px;
        background:#fffaf0;
        padding:12px;
    }

    @media (max-width:700px) {
        .quick-modal-backdrop {
            align-items:flex-start;
            padding:8px;
        }

        .quick-modal {
            max-height:calc(100dvh - 16px);
            margin:auto 0;
            border-radius:14px;
        }

        .quick-modal-head {
            padding-top:14px;
            padding-bottom:10px;
        }

        .quick-modal-head,
        .quick-modal-body {
            padding-left:14px;
            padding-right:14px;
        }

        .quick-modal-title {
            font-size:18px;
        }

        .quick-close {
            width:38px;
            height:38px;
            flex-basis:38px;
        }

        .quick-summary {
            grid-template-columns:repeat(2,1fr);
            gap:8px;
        }

        .quick-summary-card {
            min-height:74px;
            padding:10px;
        }

        .quick-top-actions,
        .quick-tabs,
        .quick-grid {
            grid-template-columns:1fr;
        }

        .quick-top-actions,
        .quick-tabs {
            gap:7px;
        }

        .quick-primary-action,
        .quick-tab {
            min-height:44px;
        }

        .quick-panel {
            padding:12px;
        }

        .quick-form-actions {
            margin-left:-12px;
            margin-right:-12px;
            padding:12px 12px 6px;
        }
    }

    @media (max-height:720px) {
        .quick-modal-backdrop {
            align-items:flex-start;
            padding-top:8px;
            padding-bottom:8px;
        }

        .quick-modal {
            max-height:calc(100dvh - 16px);
            margin:auto 0;
        }

        .quick-modal-head {
            padding-top:12px;
            padding-bottom:10px;
        }

        .quick-modal-body {
            padding-bottom:12px;
        }
    }


    /* =========================================================
       COMPACT QUICK ACTION MODAL
       ========================================================= */
    .quick-modal {
        max-width: 720px;
        border-radius: 14px;
    }

    .quick-modal-head {
        gap: 10px;
        padding: 12px 16px 9px;
    }

    .quick-modal-tag {
        min-height: 22px;
        padding: 0 8px;
        border-radius: 6px;
        font-size: 8px;
    }

    .quick-modal-tag svg {
        width: 11px;
        height: 11px;
    }

    .quick-modal-title {
        margin-top: 5px;
        font-size: 18px;
    }

    .quick-modal-meta {
        margin-top: 5px;
        gap: 10px;
        font-size: 9px;
    }

    .quick-modal-meta svg {
        width: 11px;
        height: 11px;
    }

    .quick-close {
        width: 34px;
        height: 34px;
        flex: 0 0 34px;
        border-radius: 9px;
    }

    .quick-close svg {
        width: 16px;
        height: 16px;
    }

    .quick-modal-body {
        padding: 0 16px 14px;
    }

    .quick-summary {
        gap: 7px;
        margin-bottom: 9px;
    }

    .quick-summary-card {
        min-height: 62px;
        gap: 8px;
        padding: 8px 9px;
        border-radius: 10px;
    }

    .quick-summary-icon {
        width: 30px;
        height: 30px;
        flex: 0 0 30px;
    }

    .quick-summary-icon svg {
        width: 14px;
        height: 14px;
    }

    .quick-summary-label {
        font-size: 7px;
    }

    .quick-summary-value {
        margin-top: 2px;
        font-size: 10px;
        line-height: 1.2;
    }

    .quick-top-actions {
        gap: 7px;
        margin-bottom: 7px;
    }

    .quick-primary-action {
        min-height: 38px;
        gap: 6px;
        border-radius: 8px;
        font-size: 9px;
    }

    .quick-primary-action svg {
        width: 14px;
        height: 14px;
    }

    .quick-tabs {
        gap: 7px;
        margin-bottom: 8px;
    }

    .quick-tab {
        min-height: 38px;
        gap: 6px;
        border-radius: 8px;
        padding: 0 9px;
        font-size: 9px;
    }

    .quick-tab svg {
        width: 14px;
        height: 14px;
    }

    .quick-panel {
        padding: 11px;
        border-radius: 10px;
    }

    .quick-grid {
        gap: 9px;
    }

    .quick-field label {
        margin-bottom: 4px;
        font-size: 9px;
    }

    .quick-field input,
    .quick-field select,
    .quick-field textarea {
        border-radius: 8px;
        font-size: 10px;
    }

    .quick-field input,
    .quick-field select {
        min-height: 36px;
        padding: 0 9px;
    }

    .quick-field textarea {
        min-height: 68px;
        padding: 8px 9px;
    }

    .quick-status {
        padding: 9px;
        border-radius: 8px;
    }

    .quick-form-actions {
        gap: 7px;
        margin-top: 9px;
        padding: 8px 0 2px;
    }

    .quick-btn {
        min-height: 34px;
        gap: 5px;
        border-radius: 8px;
        padding: 0 11px;
        font-size: 8px;
    }

    .quick-btn svg {
        width: 13px;
        height: 13px;
    }

    /* KPI filter buttons */
    button.stat-card {
        width: 100%;
        text-align: left;
    }

    .stat-card-link.active-metric {
        border-color: #f5b900;
        background: #fffbeb;
        box-shadow: 0 0 0 2px rgba(245,185,0,.11);
    }

    .metric-filter-bar {
        display:flex;
        align-items:center;
        justify-content:space-between;
        gap:10px;
        margin-top:8px;
        padding:8px 11px;
        border:1px solid #f2d675;
        border-radius:8px;
        background:#fffaf0;
        color:#475467;
        font-size:10px;
        font-weight:700;
    }

    .metric-filter-clear {
        border:0;
        background:transparent;
        color:#dc2626;
        font-size:9px;
        font-weight:900;
        cursor:pointer;
    }

    @media (max-width:700px) {
        .quick-modal-head {
            padding: 10px 11px 8px;
        }

        .quick-modal-body {
            padding-left: 11px;
            padding-right: 11px;
            padding-bottom: 10px;
        }

        .quick-summary {
            grid-template-columns: repeat(2,1fr);
            gap: 6px;
        }

        .quick-summary-card {
            min-height: 56px;
            padding: 7px;
        }

        .quick-top-actions,
        .quick-tabs {
            grid-template-columns: repeat(3,1fr);
            gap: 5px;
        }

        .quick-primary-action,
        .quick-tab {
            min-height: 36px;
            padding: 0 6px;
            font-size: 8px;
        }

        .quick-grid {
            grid-template-columns:1fr;
        }
    }

    @media (max-height:720px) {
        .quick-modal-head {
            padding-top:8px;
            padding-bottom:7px;
        }

        .quick-summary-card {
            min-height:54px;
        }

        .quick-primary-action,
        .quick-tab {
            min-height:34px;
        }
    }

</style>
@endonce



@php
    /*
    |--------------------------------------------------------------------------
    | Board Configuration
    |--------------------------------------------------------------------------
    |
    | Tino sections isi config se render hongi. Isliye filter fields tino me
    | EXACT same rahenge, sirf query parameter prefix alag hoga.
    |
    */

    $boardSections = [
        'new' => [
            'title' => 'New Call',
            'description' => 'Jin par abhi tak koi call nahi hui',
            'icon' => 'phone',
            'column_class' => 'new-column',
            'color_class' => 'new-color',
            'pill_class' => 'new-pill',
            'active_class' => 'active-new',
            'apply_class' => 'apply-new',
            'count' => $newCount,
            'leads' => $newLeads,
            'empty' => 'No new call leads found.',
            'more' => 'View More New Leads →',
            'all_loaded' => 'All New Leads Loaded',
            'show_var' => 'showNewFilters',
        ],

        'dialed' => [
            'title' => 'Dialed Call',
            'description' => 'Call lagayi hai — disposition kuch bhi ho',
            'icon' => 'phone',
            'column_class' => 'dialed-column',
            'color_class' => 'dialed-color',
            'pill_class' => 'dialed-pill',
            'active_class' => 'active-dialed',
            'apply_class' => 'apply-dialed',
            'count' => $dialedCount,
            'leads' => $dialedLeads,
            'empty' => 'No dialed leads found.',
            'more' => 'View More Dialed Leads →',
            'all_loaded' => 'All Dialed Leads Loaded',
            'show_var' => 'showDialedFilters',
        ],

        'connected' => [
            'title' => 'Connected Call',
            'description' => 'Jinse baat hui hai / feedback save hua hai',
            'icon' => 'phone-call',
            'column_class' => 'connected-column',
            'color_class' => 'connected-color',
            'pill_class' => 'connected-pill',
            'active_class' => 'active-connected',
            'apply_class' => 'apply-connected',
            'count' => $connectedCount,
            'leads' => $connectedLeads,
            'empty' => 'No connected leads found.',
            'more' => 'View More Connected Leads →',
            'all_loaded' => 'All Connected Leads Loaded',
            'show_var' => 'showConnectedFilters',
        ],
    ];

    $boardFilterFields = [
        'category',
        'source',
        'city',
        'priority',
        'assigned_to',
        'date_filter',
        'disposition_id',
        'demo_status',
        'label_id',
    ];

    $activeQuickMetric = $quickMetric ?? (string) request('quick_metric', '');

    // Demo tab fixed "Demo" disposition ko backend ke saath save karega.
    $demoDisposition = $dispositions->first(
        fn ($item) => mb_strtolower(trim((string) $item->name)) === 'demo'
    );

    // KPI click par existing search/board filters preserve rahenge,
    // pagination reset hogi aur selected quick_metric database query me jayega.
    $metricBaseQuery = request()->except([
        'quick_metric',
        'new_page',
        'dialed_page',
        'connected_page',
        'page',
    ]);

    $metricUrl = function (string $metric) use ($metricBaseQuery, $activeQuickMetric) {
        if ($activeQuickMetric === $metric) {
            return route('leads.index', $metricBaseQuery);
        }

        return route('leads.index', array_merge($metricBaseQuery, [
            'quick_metric' => $metric,
        ]));
    };

    $quickMetricLabels = [
        'calls_today' => 'Calls Today',
        'connected_today' => 'Connected Today',
        'employee_total_calls' => 'Employee Total Calls',
        'unique_connected' => 'Unique Connected',
        'follow_up' => 'Follow-up Calls',
        'demo_today' => 'Demo Today',
        'total_demo' => 'Total Demo',
    ];
@endphp

<div
    class="lead-board space-y-4"
    x-data="leadIndexBoard()"
    @keydown.escape.window="feedbackOpen = false"
>

    @if(session('success'))
        <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-xs font-bold text-emerald-700">
            {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-xs font-bold text-rose-700">
            {{ $errors->first() }}
        </div>
    @endif

    {{-- HEADER --}}
    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
        <div>
            <h1 class="text-2xl font-extrabold text-slate-900">Leads</h1>
            <p class="mt-1 text-xs text-slate-500">Manage your leads and track every interaction</p>
        </div>

        <div class="flex items-center gap-2">
            <form method="GET" action="{{ route('leads.index') }}" class="relative">
                {{-- Preserve all section filters while searching --}}
                @foreach(request()->except(['search', 'new_page', 'dialed_page', 'connected_page']) as $key => $value)
                    @if(!is_array($value) && $value !== '')
                        <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                    @endif
                @endforeach

                <i
                    data-lucide="search"
                    class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"
                ></i>

                <input
                    type="text"
                    name="search"
                    value="{{ request('search') }}"
                    placeholder="Search by Name, Number or Business..."
                    class="h-10 w-[360px] rounded-lg border border-slate-200 bg-white pl-10 pr-4 text-xs outline-none focus:border-amber-400"
                >
            </form>

            @can('leads.create')
                <a
                    href="{{ route('leads.create') }}"
                    class="inline-flex h-10 items-center gap-2 rounded-lg bg-amber-400 px-5 text-xs font-extrabold text-slate-900 shadow-sm hover:bg-amber-500"
                >
                    <i data-lucide="plus" class="h-4 w-4"></i>
                    Add Lead
                </a>
            @endcan
        </div>
    </div>

    {{-- STAT CARDS --}}
    <div class="stats-grid">
        <div class="stat-card">
            <span class="stat-icon bg-emerald-50 text-emerald-600"><i data-lucide="phone"></i></span>
            <div>
                <div class="stat-label">Total Leads</div>
                <div class="stat-number">{{ number_format($totalLeads) }}</div>
                <div class="stat-sub">In Panel</div>
            </div>
        </div>

        <a href="{{ $metricUrl('calls_today') }}"
           class="stat-card stat-card-link {{ $activeQuickMetric === 'calls_today' ? 'active-metric' : '' }}"
           title="Filter Calls Today">
            <span class="stat-icon bg-blue-50 text-blue-600"><i data-lucide="phone"></i></span>
            <div>
                <div class="stat-label">Calls Today</div>
                <div class="stat-number">{{ number_format($callsToday) }}</div>
            </div>
        </a>

        <a href="{{ $metricUrl('connected_today') }}"
           class="stat-card stat-card-link {{ $activeQuickMetric === 'connected_today' ? 'active-metric' : '' }}"
           title="Filter Connected Today">
            <span class="stat-icon bg-violet-50 text-violet-600"><i data-lucide="users"></i></span>
            <div>
                <div class="stat-label">Connected Today</div>
                <div class="stat-number">{{ number_format($connectedToday) }}</div>
            </div>
        </a>

        <a href="{{ $metricUrl('employee_total_calls') }}"
           class="stat-card stat-card-link {{ $activeQuickMetric === 'employee_total_calls' ? 'active-metric' : '' }}"
           title="Filter Employee Total Calls">
            <span class="stat-icon bg-orange-50 text-orange-600"><i data-lucide="badge-headset"></i></span>
            <div>
                <div class="stat-label">Employee Total Calls</div>
                <div class="stat-number">{{ number_format($employeeTotalCalls) }}</div>
                <div class="stat-sub">Since Joining</div>
            </div>
        </a>

        <a href="{{ $metricUrl('unique_connected') }}"
           class="stat-card stat-card-link {{ $activeQuickMetric === 'unique_connected' ? 'active-metric' : '' }}"
           title="Filter Unique Connected">
            <span class="stat-icon bg-cyan-50 text-cyan-600"><i data-lucide="users-round"></i></span>
            <div>
                <div class="stat-label">Unique Connected</div>
                <div class="stat-number">{{ number_format($uniqueConnected) }}</div>
                <div class="stat-sub">Distinct Leads</div>
            </div>
        </a>

        <a href="{{ $metricUrl('follow_up') }}"
           class="stat-card stat-card-link {{ $activeQuickMetric === 'follow_up' ? 'active-metric' : '' }}"
           title="Filter Follow-up Calls">
            <span class="stat-icon bg-rose-50 text-rose-600"><i data-lucide="refresh-cw"></i></span>
            <div>
                <div class="stat-label">Follow-up Calls</div>
                <div class="stat-number">{{ number_format($followUpCount) }}</div>
            </div>
        </a>

        <a href="{{ $metricUrl('demo_today') }}"
           class="stat-card stat-card-link {{ $activeQuickMetric === 'demo_today' ? 'active-metric' : '' }}"
           title="Filter Demo Today">
            <span class="stat-icon bg-blue-50 text-blue-600"><i data-lucide="video"></i></span>
            <div>
                <div class="stat-label">Demo Today</div>
                <div class="stat-number">{{ number_format($demoToday) }}</div>
            </div>
        </a>

        <a href="{{ $metricUrl('total_demo') }}"
           class="stat-card stat-card-link {{ $activeQuickMetric === 'total_demo' ? 'active-metric' : '' }}"
           title="Filter Total Demo">
            <span class="stat-icon bg-amber-50 text-amber-500"><i data-lucide="send"></i></span>
            <div>
                <div class="stat-label">Total Demo</div>
                <div class="stat-number">{{ number_format($totalDemo) }}</div>
                <div class="stat-sub">Till Now</div>
            </div>
        </a>
    </div>

    @if($activeQuickMetric !== '')
        <div class="metric-filter-bar">
            <span>
                Showing database filter:
                <strong>{{ $quickMetricLabels[$activeQuickMetric] ?? $activeQuickMetric }}</strong>
            </span>
            <a href="{{ route('leads.index', $metricBaseQuery) }}" class="metric-filter-clear">
                CLEAR FILTER
            </a>
        </div>
    @endif

    {{-- THREE COLUMNS --}}
    <div class="board-grid">

        @foreach($boardSections as $sectionKey => $section)
            @php
                $sectionFilterKeys = collect($boardFilterFields)
                    ->map(fn ($field) => $sectionKey . '_' . $field)
                    ->values()
                    ->all();

                $activeFilters = collect($sectionFilterKeys)
                    ->map(fn ($key) => request($key))
                    ->filter(fn ($value) => $value !== null && $value !== '')
                    ->count();

                $clearKeys = array_merge(
                    $sectionFilterKeys,
                    [$sectionKey . '_page']
                );

                $clearQuery = request()->except($clearKeys);
                $sectionLeads = $section['leads'];
                $showVar = $section['show_var'];
            @endphp

            <section id="{{ $sectionKey }}-call-section" class="call-column {{ $section['column_class'] }}" style="scroll-margin-top: 18px;">

                {{-- COLUMN HEADER --}}
                <div class="column-header">
                    <div class="column-heading {{ $section['color_class'] }}">
                        <i data-lucide="{{ $section['icon'] }}"></i>
                        <span>{{ $section['title'] }}</span>
                        <span class="count-pill {{ $section['pill_class'] }}">
                            {{ number_format($section['count']) }}
                        </span>
                    </div>

                    <div class="column-description">
                        {{ $section['description'] }}
                    </div>
                </div>

                {{-- FILTER TOGGLE --}}
                <div class="filter-toggle-row">
                    <button
                        type="button"
                        class="section-filter-btn"
                        :class="{{ $showVar }} ? '{{ $section['active_class'] }}' : ''"
                        @click="{{ $showVar }} = !{{ $showVar }}"
                    >
                        <i data-lucide="sliders-horizontal"></i>
                        FILTER

                        @if($activeFilters > 0)
                            <span class="active-filter-count">
                                <span>{{ $activeFilters }}</span>
                            </span>
                        @endif

                        <span x-text="{{ $showVar }} ? '▲' : '▼'" class="text-[8px]"></span>
                    </button>

                    @if($activeFilters > 0)
                        <a
                            href="{{ route('leads.index', $clearQuery) }}"
                            class="text-[9px] font-bold text-rose-600"
                        >
                            CLEAR FILTER
                        </a>
                    @endif
                </div>

                {{-- FILTER PANEL --}}
                <div
                    x-show="{{ $showVar }}"
                    x-cloak
                    class="column-filter"
                >
                    <form
                        method="GET"
                        action="{{ route('leads.index') }}"
                        class="filter-grid"
                    >
                        {{--
                            Preserve global search + OTHER sections filters.
                            Current section values are replaced by its own select fields below.
                        --}}
                        @foreach(request()->except($clearKeys) as $key => $value)
                            @if(!is_array($value) && $value !== '')
                                <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                            @endif
                        @endforeach

                        {{-- Category --}}
                        <div class="filter-field">
                            <label>Category</label>
                            <select name="{{ $sectionKey }}_category">
                                <option value="">All Categories</option>
                                @foreach($categories as $category)
                                    <option
                                        value="{{ $category }}"
                                        @selected(request($sectionKey . '_category') === $category)
                                    >
                                        {{ $category }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Source --}}
                        <div class="filter-field">
                            <label>Source</label>
                            <select name="{{ $sectionKey }}_source">
                                <option value="">All Sources</option>
                                @foreach($sources as $source)
                                    <option
                                        value="{{ $source->id }}"
                                        @selected((string) request($sectionKey . '_source') === (string) $source->id)
                                    >
                                        {{ $source->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        {{-- City --}}
                        <div class="filter-field">
                            <label>City</label>
                            <select name="{{ $sectionKey }}_city">
                                <option value="">All Cities</option>
                                @foreach($cities as $city)
                                    <option
                                        value="{{ $city }}"
                                        @selected(request($sectionKey . '_city') === $city)
                                    >
                                        {{ $city }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Priority --}}
                        <div class="filter-field">
                            <label>Priority</label>
                            <select name="{{ $sectionKey }}_priority">
                                <option value="">All Priority</option>
                                @foreach(['low', 'normal', 'high', 'urgent', 'hot'] as $priority)
                                    <option
                                        value="{{ $priority }}"
                                        @selected(request($sectionKey . '_priority') === $priority)
                                    >
                                        {{ ucfirst($priority) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Assigned Employee --}}
                        <div class="filter-field">
                            <label>Assigned To</label>
                            <select
                                name="{{ $sectionKey }}_assigned_to"
                                @disabled(!$canFilterByEmployee)
                            >
                                <option value="">All Employees</option>
                                @foreach($users as $user)
                                    <option
                                        value="{{ $user->id }}"
                                        @selected((string) request($sectionKey . '_assigned_to') === (string) $user->id)
                                    >
                                        {{ $user->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Date Added --}}
                        <div class="filter-field">
                            <label>Date Added</label>
                            <select name="{{ $sectionKey }}_date_filter">
                                <option value="">Any Time</option>
                                <option value="today" @selected(request($sectionKey . '_date_filter') === 'today')>Today</option>
                                <option value="yesterday" @selected(request($sectionKey . '_date_filter') === 'yesterday')>Yesterday</option>
                                <option value="week" @selected(request($sectionKey . '_date_filter') === 'week')>This Week</option>
                                <option value="month" @selected(request($sectionKey . '_date_filter') === 'month')>This Month</option>
                            </select>
                        </div>

                        @if (!($section['title'] == 'New Call'))
                            
                        {{-- Latest Call Disposition --}}
                        <div class="filter-field">
                            <label>Call Disposition</label>

                            <select name="{{ $sectionKey }}_disposition_id">
                                <option value="">
                                    All Dispositions
                                </option>

                                <option
                                    value="none"
                                    @selected(
                                        request(
                                            $sectionKey . '_disposition_id'
                                        ) === 'none'
                                    )
                                >
                                    No Disposition / No Call Yet
                                </option>

                                @foreach($dispositions as $disposition)
                                    <option
                                        value="{{ $disposition->id }}"
                                        @selected(
                                            (string) request(
                                                $sectionKey . '_disposition_id'
                                            )
                                            ===
                                            (string) $disposition->id
                                        )
                                    >
                                        {{ $disposition->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Demo Status --}}
                        <div class="filter-field">
                            <label>Demo Status</label>
                            <select name="{{ $sectionKey }}_demo_status">
                                <option value="">All Demo Status</option>
                                <option value="sent" @selected(request($sectionKey . '_demo_status') === 'sent')>Demo Sent</option>
                                <option value="not_sent" @selected(request($sectionKey . '_demo_status') === 'not_sent')>Demo Not Sent</option>
                            </select>
                        </div>

                        {{-- Label --}}
                        <div class="filter-field">
                            <label>Label</label>
                            <select name="{{ $sectionKey }}_label_id">
                                <option value="">All Labels</option>
                                @foreach($labels as $label)
                                    <option
                                        value="{{ $label->id }}"
                                        @selected((string) request($sectionKey . '_label_id') === (string) $label->id)
                                    >
                                        {{ $label->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        @endif


                        {{-- Actions --}}
                        <div class="filter-actions">
                            <a
                                href="{{ route('leads.index', $clearQuery) }}"
                                class="clear-filter-btn"
                            >
                                CLEAR
                            </a>

                            <button
                                type="submit"
                                class="apply-filter-btn {{ $section['apply_class'] }}"
                            >
                                APPLY FILTER
                            </button>
                        </div>
                    </form>
                </div>

                {{-- LEADS LIST --}}
                <div class="lead-list">
                    @forelse($sectionLeads as $lead)
                        @php
                            // Current employee ke New Call card par purane employee ki activity mat dikhana.
                            $latestCall = $sectionKey === 'new' ? null : $lead->latestCall;
                            $latestRemark = $latestCall?->remarks
                                ?? $latestCall?->remark
                                ?? $latestCall?->auto_remarks
                                ?? null;
                            $latestFeedback = $sectionKey === 'new'
                                ? null
                                : $latestRemark;

                            $latestNote = $sectionKey === 'new'
                                ? null
                                : $lead->latest_note_body;

                            $duration = $latestCall?->duration_seconds
                                ?? $latestCall?->duration
                                ?? $latestCall?->call_duration
                                ?? null;

                            $durationText = null;
                            if (is_numeric($duration)) {
                                $duration = (int) $duration;
                                $durationText = str_pad((string) intdiv($duration, 60), 2, '0', STR_PAD_LEFT)
                                    . 'm '
                                    . str_pad((string) ($duration % 60), 2, '0', STR_PAD_LEFT)
                                    . 's';
                            } elseif (is_string($duration) && trim($duration) !== '') {
                                $durationText = $duration;
                            }

                            $displayName = $lead->company_name ?: $lead->name ?: 'Unnamed Lead';
                            $initials = collect(preg_split('/\s+/', trim($lead->name ?: $lead->company_name ?: 'Lead')))
                                ->filter()->take(2)
                                ->map(fn($word) => mb_strtoupper(mb_substr($word, 0, 1)))
                                ->implode('');

                            $avatarClass = match($sectionKey) {
                                'new' => 'avatar-new',
                                'dialed' => 'avatar-dialed',
                                default => 'avatar-connected',
                            };

                            $cardClass = match($sectionKey) {
                                'new' => 'new-card',
                                'dialed' => 'dialed-card',
                                default => 'connected-card',
                            };

                            $leadUrl = route('leads.show', array_merge(
                                ['lead' => $lead->id],
                                request()->except(['new_page','dialed_page','connected_page'])
                            ));

                            $whatsappNumber = preg_replace('/\D+/', '', (string) ($lead->whatsapp_number ?: $lead->mobile));

                            $popupLead = [
                                'id' => (int) $lead->id,
                                'name' => $lead->name ?: 'No Name',
                                'business' => $lead->company_name ?: '',
                                'mobile' => $lead->mobile ?: '',
                                'whatsapp' => $whatsappNumber,
                                'city' => $lead->city ?: '',
                                'state' => $lead->state ?: '',
                                'category' => $lead->category ?: '',
                                'demoSent' => (bool) $lead->demo_send,
                                'showUrl' => $leadUrl,
                                'callStoreUrl' => route('calls.store', $lead),
                                'noteStoreUrl' => route('leads.notes', $lead),
                                'demoUpdateUrl' => route('leads.update', $lead),

                                'demoCallUrl' => route('leads.demo.store', $lead),
                            ];
                        @endphp

                        <div class="lead-card {{ $cardClass }}">
                            <div class="lead-top">
                                <div class="lead-profile">
                                    <div class="lead-avatar {{ $avatarClass }}">{{ $initials ?: 'L' }}</div>

                                    <div class="min-w-0">
                                        <a href="{{ $leadUrl }}" class="lead-name">{{ $displayName }}</a>

                                        <div class="lead-meta">
                                            <i data-lucide="phone"></i>
                                            <span>{{ $lead->mobile ?: 'No Mobile' }}</span>
                                        </div>

                                        @if($lead->city || $lead->state)
                                            <div class="lead-meta">
                                                <i data-lucide="map-pin"></i>
                                                <span>{{ collect([$lead->city,$lead->state])->filter()->implode(', ') }}</span>
                                            </div>
                                        @endif
                                    </div>
                                </div>

                                <div class="lead-right">
                                    @if($sectionKey === 'new')
                                        <div class="lead-badge text-emerald-600">New</div>
                                        <div class="call-state text-slate-500">No call yet</div>
                                    @else
                                        @if($latestCall?->created_at)
                                            <div class="call-time">
                                                <i data-lucide="clock"></i>
                                                <span>
                                                    {{ $latestCall->created_at->isToday()
                                                        ? 'Today '.$latestCall->created_at->format('h:i A')
                                                        : $latestCall->created_at->format('d M, h:i A') }}
                                                </span>
                                            </div>
                                        @endif

                                        @if($sectionKey === 'dialed')
                                            <div class="call-state text-orange-600">
                                                {{ $latestCall?->disposition?->name ?: 'Dialed' }}
                                            </div>
                                        @else
                                            <div class="call-state text-emerald-600">
                                                {{ $durationText ? '☎ '.$durationText : 'Connected' }}
                                            </div>
                                        @endif
                                    @endif
                                </div>
                            </div>

                            @if($sectionKey !== 'new')
                                <div class="feedback-row">
                                    <span class="feedback-label">Last Feedback:</span>
                                    <span class="feedback-value">{{ $latestFeedback ?: 'No feedback entered' }}</span>
                                </div>

                                <div class="note-row">
                                    <span class="note-label">Note:</span>
                                    <span class="note-value">{{ $latestNote ?: 'No note entered' }}</span>
                                </div>
                            @endif

                            @if($lead->next_follow_up_at)
                                <div class="followup-row">
                                    <i data-lucide="calendar-clock"></i>
                                    <span>
                                        Next Follow-up:
                                        <strong>{{ \Illuminate\Support\Carbon::parse($lead->next_follow_up_at)->format('d M, h:i A') }}</strong>
                                    </span>
                                </div>
                            @endif

                            @if($lead->demo_send)
                                <div class="mt-2 flex justify-end">
                                    <span class="demo-sent"><i data-lucide="video"></i> Demo Sent</span>
                                </div>
                            @endif

                            <div class="card-bottom">
                                <div>
                                    @if($lead->category)
                                        <span class="category-tag">{{ $lead->category }}</span>
                                    @endif
                                </div>

                                <div class="action-group">
                                    @if($lead->mobile)
                                        <button
                                            type="button"
                                            class="round-action call-action"
                                            title="Call from registered mobile"
                                            @click="sendCall({{ (int) $lead->id }})"
                                            :disabled="sendingCall === {{ (int) $lead->id }}"
                                        >
                                            <i data-lucide="phone"></i>
                                        </button>
                                    @endif

                                    {{-- @if($whatsappNumber)
                                        <button
                                            type="button"
                                            class="round-action whatsapp-action"
                                            title="Open WhatsApp Web"
                                            @click="openWhatsApp(@js($whatsappNumber))"
                                        >
                                            <i data-lucide="message-circle"></i>
                                        </button>
                                    @endif --}}

                                       @if($whatsappNumber)

        <button
            type="button"
            class="round-action whatsapp-action"
            title="Send WhatsApp Message"
            onclick="openWhatsappTemplateModal({{ (int) $lead->id }})"
        >
            <i data-lucide="message-circle"></i>
        </button>

    @endif

                                    <button
                                        type="button"
                                        class="round-action feedback-action"
                                        title="Feedback & Actions"
                                        @click='openFeedback(@json($popupLead))'
                                    >
                                        <i data-lucide="message-square-text"></i>
                                        <span>Feedback</span>
                                    </button>

                                    <a href="{{ $leadUrl }}" class="round-action open-action" title="Open full lead">
                                        <i data-lucide="external-link"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="empty-board">
                            {{ $section['empty'] }}
                        </div>
                    @endforelse
                </div>

                {{-- COLUMN FOOTER --}}
                <div class="column-footer {{ $section['color_class'] }}">
                    @if($sectionLeads->hasMorePages())
                        <a href="{{ $sectionLeads->nextPageUrl() }}">
                            {{ $section['more'] }}
                        </a>
                    @else
                        {{ $section['all_loaded'] }}
                    @endif
                </div>
            </section>
        @endforeach

    </div>

    <div class="board-tip">
        <i data-lucide="lightbulb" class="h-5 w-5 text-amber-500"></i>
        <div>
            <strong>Flow:</strong>
            New Call → Dialed Call → Connected Call
            <span class="ml-2">Lead ki activity aur feedback clearly track hoti rahegi.</span>
        </div>
    </div>

    {{-- QUICK FEEDBACK / ALL ACTIONS MODAL --}}
    <div
        x-show="feedbackOpen"
        x-cloak
        class="quick-modal-backdrop"
        @click.self="feedbackOpen = false"
    >
        <div class="quick-modal" role="dialog" aria-modal="true">

            <div class="quick-modal-head">

                <div class="min-w-0">

                    <div class="quick-modal-tag">
                        <i data-lucide="zap"></i>
                        Quick Lead Actions
                    </div>

                    <div
                        class="quick-modal-title truncate"
                        x-text="
                            selectedLead.business
                            ||
                            selectedLead.name
                            ||
                            'Lead'
                        "
                    ></div>

                    <div class="quick-modal-meta">

                        <span>
                            <i data-lucide="phone"></i>

                            <span
                                x-text="
                                    selectedLead.mobile
                                    ||
                                    'No mobile'
                                "
                            ></span>
                        </span>

                        <span x-show="selectedLead.city">
                            <i data-lucide="map-pin"></i>

                            <span
                                x-text="selectedLead.city"
                            ></span>
                        </span>

                        <span x-show="selectedLead.category">
                            <i data-lucide="briefcase-business"></i>

                            <span
                                x-text="selectedLead.category"
                            ></span>
                        </span>

                    </div>

                </div>


                <button
                    type="button"
                    class="quick-close"
                    @click="feedbackOpen = false"
                    aria-label="Close"
                >
                    <i data-lucide="x"></i>
                </button>

            </div>


            <div class="quick-modal-body">

                <div class="quick-summary">

                    <div class="quick-summary-card">

                        <div class="quick-summary-icon">
                            <i data-lucide="user"></i>
                        </div>

                        <div class="min-w-0">
                            <div class="quick-summary-label">
                                Lead
                            </div>

                            <div
                                class="quick-summary-value"
                                x-text="
                                    selectedLead.name
                                    ||
                                    '—'
                                "
                            ></div>
                        </div>

                    </div>


                    <div class="quick-summary-card">

                        <div class="quick-summary-icon">
                            <i data-lucide="briefcase-business"></i>
                        </div>

                        <div class="min-w-0">
                            <div class="quick-summary-label">
                                Business
                            </div>

                            <div
                                class="quick-summary-value"
                                x-text="
                                    selectedLead.business
                                    ||
                                    '—'
                                "
                            ></div>
                        </div>

                    </div>


                    <div class="quick-summary-card">

                        <div class="quick-summary-icon">
                            <i data-lucide="smartphone"></i>
                        </div>

                        <div class="min-w-0">
                            <div class="quick-summary-label">
                                Mobile
                            </div>

                            <div
                                class="quick-summary-value"
                                x-text="
                                    selectedLead.mobile
                                    ||
                                    '—'
                                "
                            ></div>
                        </div>

                    </div>


                    <div class="quick-summary-card">

                        <div class="quick-summary-icon">
                            <i data-lucide="video"></i>
                        </div>

                        <div class="min-w-0">
                            <div class="quick-summary-label">
                                Demo
                            </div>

                            <div
                                class="quick-summary-value"
                                :class="
                                    selectedLead.demoSent
                                        ? 'text-emerald-600'
                                        : 'text-slate-700'
                                "
                                x-text="
                                    selectedLead.demoSent
                                        ? 'Demo Sent'
                                        : 'Not Sent'
                                "
                            ></div>
                        </div>

                    </div>

                </div>


                <div class="quick-top-actions">

                    <button
                        type="button"
                        class="quick-primary-action"
                        @click="sendCall(selectedLead.id)"
                        :disabled="
                            !selectedLead.id
                            ||
                            sendingCall === selectedLead.id
                        "
                    >
                        <i data-lucide="phone"></i>
                        Call on Mobile
                    </button>


                    <button
                        type="button"
                        class="quick-primary-action"
                        @click="
                            openWhatsApp(
                                selectedLead.whatsapp
                            )
                        "
                        :disabled="
                            !selectedLead.whatsapp
                        "
                    >
                        <i data-lucide="message-circle"></i>
                        WhatsApp Web
                    </button>


                    <a
                        :href="
                            selectedLead.showUrl
                            ||
                            '#'
                        "
                        class="
                            quick-primary-action
                            secondary
                        "
                    >
                        <i data-lucide="external-link"></i>
                        Full Lead
                    </a>

                </div>


                <div class="quick-tabs">

                    @can('calls.create')
                        <button
                            type="button"
                            class="quick-tab"
                            :class="
                                feedbackTab === 'call'
                                    ? 'active'
                                    : ''
                            "
                            @click="
                                feedbackTab = 'call'
                            "
                        >
                            <i data-lucide="star"></i>
                            Save Feedback
                        </button>
                    @endcan


                    @can('leads.update')
                        <button
                            type="button"
                            class="quick-tab"
                            :class="
                                feedbackTab === 'demo'
                                    ? 'active'
                                    : ''
                            "
                            @click="
                                feedbackTab = 'demo'
                            "
                        >
                            <i data-lucide="video"></i>
                            Demo
                        </button>
                    @endcan


                    @can('leads.notes.create')
                        <button
                            type="button"
                            class="quick-tab"
                            :class="
                                feedbackTab === 'note'
                                    ? 'active'
                                    : ''
                            "
                            @click="
                                feedbackTab = 'note'
                            "
                        >
                            <i data-lucide="notebook-pen"></i>
                            Add Note
                        </button>
                    @endcan

                </div>


                @can('calls.create')
                        <button type="button" class="quick-tab"
                                :class="feedbackTab==='call' ? 'active' : ''"
                                @click="feedbackTab='call'">
                            <i data-lucide="phone-call"></i> Save Feedback
                        </button>
                    @endcan

                    @can('leads.update')
                        <button type="button" class="quick-tab"
                                :class="feedbackTab==='demo' ? 'active' : ''"
                                @click="feedbackTab='demo'">
                            <i data-lucide="video"></i> Demo
                        </button>
                    @endcan

                    @can('leads.notes.create')
                        <button type="button" class="quick-tab"
                                :class="feedbackTab==='note' ? 'active' : ''"
                                @click="feedbackTab='note'">
                            <i data-lucide="notebook-pen"></i> Add Note
                        </button>
                    @endcan
                </div>

                @can('calls.create')
                    <div x-show="feedbackTab==='call'" x-cloak class="quick-panel">
                        <form method="POST" :action="selectedLead.callStoreUrl">
                            @csrf

                            <div class="quick-grid">
                                <div class="quick-field">
                                    <label>Call Result <span class="text-rose-500">*</span></label>
                                    <select
                                        name="call_disposition_id"
                                        required
                                        x-model="callForm.dispositionId"
                                        @change="dispositionChanged($event)"
                                    >
                                        <option value="">Select call result</option>
                                        @foreach($dispositions as $disposition)
                                            <option
                                                value="{{ $disposition->id }}"
                                                data-requires-remarks="{{ $disposition->requires_remarks ? '1' : '0' }}"
                                                data-requires-follow-up="{{ $disposition->requires_follow_up ? '1' : '0' }}"
                                                data-auto-remarks="{{ e($disposition->auto_remarks ?? '') }}"
                                                data-next-followup="{{ $disposition->next_followup ?? '' }}"
                                            >
                                                {{ $disposition->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="quick-field">
                                    <label>Call Duration</label>
                                    <input type="number" min="0" name="duration_seconds"
                                           x-model="callForm.duration"
                                           placeholder="Seconds">
                                </div>

                                <div class="quick-field md:col-span-2" x-show="callForm.showRemarks" x-cloak>
                                    <label>
                                        Remarks
                                        <span x-show="callForm.remarksRequired" class="text-rose-500">*</span>
                                    </label>
                                    <textarea
                                        name="remarks"
                                        x-model="callForm.remarks"
                                        :required="callForm.remarksRequired"
                                        placeholder="Customer ne kya kaha..."
                                    ></textarea>
                                </div>

                                <div class="quick-field md:col-span-2" x-show="callForm.showFollowup" x-cloak>
                                    <label>
                                        Next Follow-up
                                        <span x-show="callForm.followupRequired" class="text-rose-500">*</span>
                                    </label>
                                    <input
                                        type="datetime-local"
                                        name="follow_up_at"
                                        x-model="callForm.followupAt"
                                        :required="callForm.followupRequired"
                                        min="{{ now()->addMinute()->format('Y-m-d\TH:i') }}"
                                    >
                                </div>
                            </div>

                            <div class="quick-form-actions">
                                <button type="button" class="quick-btn" @click="feedbackOpen=false">Cancel</button>
                                <button type="submit" class="quick-btn quick-btn-green">
                                    <i data-lucide="save"></i> Save Feedback
                                </button>
                            </div>
                        </form>
                    </div>
                @endcan

                @can('leads.update')
                    <div
                        x-show="feedbackTab === 'demo'"
                        x-cloak
                        class="quick-panel"
                    >
                        <form
                            method="POST"
                            :action="selectedLead.demoCallUrl"
                            @submit="prepareDemoSubmit($event)"
                            data-next-followup="{{ $demoDisposition?->next_followup ?? '' }}"
                        >
                            @csrf
                            <input type="hidden" name="demo_send_only" value="1">
                            <input type="hidden" name="demo_send" value="1">
                            @if($demoDisposition)
                                <input type="hidden" name="call_disposition_id" value="{{ $demoDisposition->id }}">
                                <input type="hidden" name="remarks" value="{{ $demoDisposition->auto_remarks ?? '' }}">
                                <input type="hidden" name="follow_up_at" value="">
                            @endif

                            <div class="quick-status">
                                <div class="text-[9px] font-bold uppercase text-slate-500">
                                    Demo Action
                                </div>

                                <div
                                    class="mt-1 text-sm font-extrabold"
                                    :class="selectedLead.demoSent
                                        ? 'text-emerald-600'
                                        : 'text-violet-700'"
                                    x-text="selectedLead.demoSent
                                        ? 'Demo Already Sent — Resend Allowed'
                                        : 'Ready To Send Demo'"
                                ></div>

                                <div class="mt-2 text-[10px] leading-5 text-slate-500">
                                    <strong>Send Demo</strong> par click karte hi
                                    lead ka <strong>Demo Sent</strong> status aur
                                    Call Log me <strong>Demo disposition</strong>
                                    dono ek hi backend transaction me save honge.
                                </div>
                            </div>

                            <div class="mt-3 rounded-lg border border-violet-100 bg-violet-50 p-3">
                                <div class="flex items-center justify-between gap-3">
                                    <div>
                                        <div class="text-[9px] font-bold uppercase text-violet-500">
                                            Call Disposition
                                        </div>
                                        <div class="mt-1 text-sm font-extrabold text-violet-800">
                                            Demo
                                        </div>
                                    </div>

                                    <div class="text-right">
                                        <div class="text-[9px] font-bold uppercase text-slate-400">
                                            Current Demo Status
                                        </div>
                                        <div
                                            class="mt-1 text-sm font-extrabold"
                                            :class="selectedLead.demoSent
                                                ? 'text-emerald-600'
                                                : 'text-slate-600'"
                                            x-text="selectedLead.demoSent
                                                ? 'Already Sent'
                                                : 'Not Sent'"
                                        ></div>
                                    </div>
                                </div>
                            </div>

                            <div class="quick-form-actions">
                                <button
                                    type="button"
                                    class="quick-btn"
                                    @click="feedbackOpen = false"
                                >
                                    Cancel
                                </button>

                                <button
                                    type="submit"
                                    class="quick-btn quick-btn-violet"
                                >
                                    <i data-lucide="video"></i>
                                    <span x-text="selectedLead.demoSent ? 'Resend Demo' : 'Send Demo'"></span>
                                </button>
                            </div>
                        </form>
                    </div>
                @endcan

                @can('leads.notes.create')
                    <div x-show="feedbackTab==='note'" x-cloak class="quick-panel">
                        <form method="POST" :action="selectedLead.noteStoreUrl">
                            @csrf

                            <div class="quick-field">
                                <label>Internal Note <span class="text-rose-500">*</span></label>
                                <textarea
                                    name="body"
                                    required
                                    x-model="noteBody"
                                    placeholder="Customer discussion, requirement ya internal note..."
                                ></textarea>
                            </div>

                            <div class="quick-form-actions">
                                <button type="button" class="quick-btn" @click="feedbackOpen=false">Cancel</button>
                                <button type="submit" class="quick-btn quick-btn-dark">
                                    <i data-lucide="plus"></i> Add Note
                                </button>
                            </div>
                        </form>
                    </div>
                @endcan
            </div>
        </div>
    </div>

</div>


@include('components.whatsapp-template-modal')

<script>
    function leadIndexBoard() {
        return {
            sendingCall: null,
            feedbackOpen: false,
            feedbackTab: 'call',
            selectedLead: {
                id:null, name:'', business:'', mobile:'', whatsapp:'',
                city:'', state:'', category:'', demoSent:false,
                showUrl:'', callStoreUrl:'', noteStoreUrl:'', demoUpdateUrl:'', demoCallUrl:''
            },
            noteBody: '',
            callForm: {
                dispositionId:'', duration:'', remarks:'', followupAt:'',
                showRemarks:false, remarksRequired:false,
                showFollowup:false, followupRequired:false
            },

            showNewFilters: {{ request()->hasAny([
                'new_category','new_source','new_city','new_priority',
                'new_assigned_to','new_date_filter','new_disposition_id',
                'new_demo_status','new_label_id'
            ]) ? 'true' : 'false' }},

            showDialedFilters: {{ request()->hasAny([
                'dialed_category','dialed_source','dialed_city','dialed_priority',
                'dialed_assigned_to','dialed_date_filter','dialed_disposition_id',
                'dialed_demo_status','dialed_label_id'
            ]) ? 'true' : 'false' }},

            showConnectedFilters: {{ request()->hasAny([
                'connected_category','connected_source','connected_city','connected_priority',
                'connected_assigned_to','connected_date_filter','connected_disposition_id',
                'connected_demo_status','connected_label_id'
            ]) ? 'true' : 'false' }},

            openFeedback(lead) {
                this.selectedLead = { ...this.selectedLead, ...lead };
                this.feedbackTab = 'call';
                this.noteBody = '';
                this.callForm = {
                    dispositionId:'', duration:'', remarks:'', followupAt:'',
                    showRemarks:false, remarksRequired:false,
                    showFollowup:false, followupRequired:false
                };
                this.feedbackOpen = true;

                this.$nextTick(() => {
                    if (window.lucide) window.lucide.createIcons();
                });
            },

            datetimeLocalAfterMinutes(minutes) {
                const value = Number(minutes);
                if (!Number.isFinite(value) || value <= 0) return '';

                const d = new Date(Date.now() + value * 60 * 1000);
                const pad = n => String(n).padStart(2,'0');

                return d.getFullYear() + '-'
                    + pad(d.getMonth()+1) + '-'
                    + pad(d.getDate()) + 'T'
                    + pad(d.getHours()) + ':'
                    + pad(d.getMinutes());
            },

            prepareDemoSubmit(event) {
                const form = event?.target;
                if (!form) return;

                const followupInput = form.querySelector('input[name="follow_up_at"]');
                if (!followupInput) return;

                const minutes = Number(form.dataset.nextFollowup || 0);

                followupInput.value = Number.isFinite(minutes) && minutes > 0
                    ? this.datetimeLocalAfterMinutes(minutes)
                    : '';
            },

            dispositionChanged(event) {
                const option = event.target.options[event.target.selectedIndex];

                if (!option || !option.value) {
                    this.callForm.remarks = '';
                    this.callForm.followupAt = '';
                    this.callForm.showRemarks = false;
                    this.callForm.remarksRequired = false;
                    this.callForm.showFollowup = false;
                    this.callForm.followupRequired = false;
                    return;
                }

                const requiresRemarks = option.dataset.requiresRemarks === '1';
                const requiresFollowUp = option.dataset.requiresFollowUp === '1';
                const autoRemarks = String(option.dataset.autoRemarks || '').trim();
                const nextMinutes = Number(option.dataset.nextFollowup || 0);
                const hasAutoFollowup = Number.isFinite(nextMinutes) && nextMinutes > 0;

                this.callForm.remarks = autoRemarks;
                this.callForm.remarksRequired = requiresRemarks;
                this.callForm.showRemarks = requiresRemarks || autoRemarks !== '';

                this.callForm.followupAt = hasAutoFollowup
                    ? this.datetimeLocalAfterMinutes(nextMinutes)
                    : '';

                this.callForm.followupRequired = requiresFollowUp || hasAutoFollowup;
                this.callForm.showFollowup = requiresFollowUp || hasAutoFollowup;
            },

            async sendCall(leadId) {
                if (!leadId || this.sendingCall) return;

                this.sendingCall = leadId;

                try {
                    const token = document.querySelector('meta[name=csrf-token]')?.content || '';

                    const response = await fetch(`/leads/${leadId}/call-on-mobile`, {
                        method:'POST',
                        headers:{
                            'X-CSRF-TOKEN':token,
                            'Accept':'application/json',
                            'Content-Type':'application/json'
                        },
                        body:JSON.stringify({})
                    });

                    let data = {};
                    try { data = await response.json(); } catch(e) {}

                    if (!response.ok || !data.status) {
                        throw new Error(data.message || 'Unable to send call to mobile.');
                    }

                    alert(data.message || 'Call sent to mobile successfully.');
                } catch(error) {
                    alert(error.message || 'Unable to send call to mobile.');
                } finally {
                    this.sendingCall = null;
                }
            },

            openWhatsApp(number) {
                const clean = String(number || '').replace(/\D/g,'');

                if (!clean) {
                    alert('WhatsApp number is missing.');
                    return;
                }

                const url = `https://web.whatsapp.com/send?phone=${encodeURIComponent(clean)}`;

                /*
                 * Named window: CRM se pehli baar WhatsApp khulne ke baad
                 * next clicks same WhatsApp Web tab/window ko reuse karenge.
                 * Login na ho to WhatsApp Web login/QR screen khud kholega.
                 */
                const w = window.open(url, 'rvg_whatsapp_web');

                if (w) {
                    w.focus();
                } else {
                    alert('Browser popup blocked hai. Popups allow karke dobara try karein.');
                }
            }
        };
    }

    document.addEventListener('DOMContentLoaded', () => {
        if (window.lucide) window.lucide.createIcons();
    });
</script>

@endsection

@section('content')

@once
<style>
    [x-cloak] { display: none !important; }

    .lead-board {
        font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        color: #172033;
    }

    .stats-grid {
        display: grid;
        grid-template-columns: repeat(8, minmax(135px, 1fr));
        gap: 10px;
    }

    .stat-card {
        min-height: 82px;
        background: #fff;
        border: 1px solid #e7eaf0;
        border-radius: 11px;
        padding: 13px;
        display: flex;
        align-items: center;
        gap: 11px;
        box-shadow: 0 2px 8px rgba(15, 23, 42, .035);
    }

    .stat-card-link {
        color: inherit;
        text-decoration: none;
        cursor: pointer;
        transition: transform .15s ease, box-shadow .15s ease, border-color .15s ease;
    }

    .stat-card-link:hover {
        transform: translateY(-2px);
        border-color: #f3c54a;
        box-shadow: 0 8px 20px rgba(15, 23, 42, .08);
    }

    .stat-card-link:focus-visible {
        outline: 3px solid rgba(245, 185, 0, .22);
        outline-offset: 2px;
    }

    .stat-card-link .stat-label,
    .stat-card-link .stat-number,
    .stat-card-link .stat-sub {
        color: inherit;
    }

    .stat-icon {
        width: 40px;
        height: 40px;
        flex: 0 0 40px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .stat-icon svg { width: 20px; height: 20px; }
    .stat-label { font-size: 11px; font-weight: 700; color: #344054; line-height: 1.15; }
    .stat-number { margin-top: 4px; font-size: 21px; line-height: 1; font-weight: 800; color: #111827; }
    .stat-sub { margin-top: 4px; font-size: 9px; color: #98a2b3; }

    .board-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(350px, 1fr));
        gap: 12px;
        align-items: start;
    }

    .call-column {
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 2px 10px rgba(15, 23, 42, .04);
    }

    .new-column { border-top: 3px solid #18a957; }
    .dialed-column { border-top: 3px solid #f28b19; }
    .connected-column { border-top: 3px solid #1769d2; }

    .column-header {
        padding: 14px 17px 12px;
        border-bottom: 1px solid #eceff3;
    }

    .column-heading {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 9px;
        font-size: 18px;
        font-weight: 800;
    }

    .column-heading svg { width: 20px; height: 20px; }
    .new-color { color: #159447; }
    .dialed-color { color: #e87508; }
    .connected-color { color: #0759b5; }

    .count-pill {
        padding: 3px 9px;
        border-radius: 12px;
        color: #fff;
        font-size: 11px;
        line-height: 1.4;
        font-weight: 800;
    }

    .new-pill { background: #35aa5e; }
    .dialed-pill { background: #f7a238; }
    .connected-pill { background: #2c7bdc; }

    .column-description {
        margin-top: 4px;
        text-align: center;
        font-size: 10px;
        color: #667085;
    }

    .filter-toggle-row {
        padding: 9px 12px;
        border-bottom: 1px solid #edf0f4;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        background: #fff;
    }

    .section-filter-btn {
        min-height: 32px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        border: 1px solid #dde2ea;
        border-radius: 7px;
        padding: 0 11px;
        background: #fff;
        color: #344054;
        font-size: 9px;
        font-weight: 800;
        cursor: pointer;
        transition: .15s;
    }

    .section-filter-btn:hover { background: #f8fafc; }
    .section-filter-btn svg { width: 13px; height: 13px; }
    .section-filter-btn.active-new { border-color: #86d7a2; background: #effbf3; color: #159447; }
    .section-filter-btn.active-dialed { border-color: #f5c27e; background: #fff8ed; color: #e87508; }
    .section-filter-btn.active-connected { border-color: #9ac4ee; background: #eff7ff; color: #0759b5; }

    .active-filter-count {
        min-width: 19px;
        height: 19px;
        padding: 0 5px;
        border-radius: 10px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: currentColor;
        font-size: 9px;
    }

    .active-filter-count span { color: #fff; }

    .column-filter {
        padding: 12px;
        border-bottom: 1px solid #edf0f4;
        background: #fbfcfe;
    }

    .filter-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 8px;
    }

    .filter-field label {
        display: block;
        margin-bottom: 4px;
        color: #667085;
        font-size: 8px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: .03em;
    }

    .filter-grid select {
        width: 100%;
        min-height: 34px;
        border: 1px solid #dfe3e8;
        border-radius: 6px;
        padding: 0 8px;
        background: #fff;
        color: #344054;
        font-size: 10px;
        outline: none;
    }

    .filter-grid select:focus {
        border-color: #f5b900;
        box-shadow: 0 0 0 2px rgba(245, 185, 0, .08);
    }

    .filter-actions {
        grid-column: 1 / -1;
        display: flex;
        align-items: center;
        justify-content: flex-end;
        gap: 7px;
        padding-top: 3px;
    }

    .apply-filter-btn {
        height: 32px;
        border: 0;
        border-radius: 6px;
        padding: 0 14px;
        color: #fff;
        font-size: 9px;
        font-weight: 800;
        cursor: pointer;
    }

    .apply-new { background: #159447; }
    .apply-dialed { background: #e87508; }
    .apply-connected { background: #0759b5; }

    .clear-filter-btn {
        height: 32px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border: 1px solid #dfe3e8;
        border-radius: 6px;
        padding: 0 12px;
        background: #fff;
        color: #667085;
        font-size: 9px;
        font-weight: 800;
    }

    .lead-list { padding: 10px; }

    .lead-card {
        position: relative;
        margin-bottom: 9px;
        border: 1px solid #e4e7ec;
        border-radius: 10px;
        padding: 12px;
        background: #fff;
        transition: .15s ease;
    }

    .lead-card:hover { transform: translateY(-1px); box-shadow: 0 6px 15px rgba(15, 23, 42, .06); }
    .new-card { border-left: 3px solid #43b96b; }
    .dialed-card { border-left: 3px solid #f4ad51; background: linear-gradient(90deg, #fffdf8 0%, #fff 16%); }
    .connected-card { border-left: 3px solid #72aee8; background: linear-gradient(90deg, #fafdff 0%, #fff 16%); }

    .lead-top { display: flex; align-items: flex-start; justify-content: space-between; gap: 10px; }
    .lead-profile { display: flex; min-width: 0; gap: 10px; }

    .lead-avatar {
        width: 37px;
        height: 37px;
        flex: 0 0 37px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        font-size: 13px;
        font-weight: 800;
    }

    .avatar-new { background: #e8f8ed; color: #16994b; }
    .avatar-dialed { background: #fff3df; color: #dc7609; }
    .avatar-connected { background: #eaf3ff; color: #125cb4; }

    .lead-name {
        display: block;
        max-width: 170px;
        overflow: hidden;
        color: #101828;
        font-size: 12px;
        font-weight: 800;
        text-decoration: none;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .lead-name:hover { color: #1769d2; }
    .lead-meta { margin-top: 4px; display: flex; align-items: center; gap: 5px; color: #475467; font-size: 9.5px; }
    .lead-meta svg { width: 11px; height: 11px; }
    .lead-right { flex: 0 0 auto; text-align: right; font-size: 9px; }
    .lead-badge { font-weight: 700; }
    .call-time { display: flex; justify-content: flex-end; align-items: center; gap: 4px; color: #475467; }
    .call-time svg { width: 11px; height: 11px; }
    .call-state { margin-top: 6px; font-weight: 700; }
    .feedback-row { margin-top: 9px; font-size: 9.5px; color: #475467; line-height: 1.45; }
    .feedback-label,
    .note-label { color: #667085; }
    .feedback-value { font-weight: 700; color: #101828; }
    .note-row { margin-top: 4px; font-size: 9.5px; color: #475467; line-height: 1.45; }
    .note-value { font-weight: 700; color: #7c3aed; }
    .followup-row { margin-top: 4px; display: flex; align-items: center; gap: 4px; color: #475467; font-size: 9px; }
    .followup-row svg { width: 11px; height: 11px; }
    .card-bottom { margin-top: 10px; display: flex; align-items: center; justify-content: space-between; gap: 8px; }

    .category-tag {
        border-radius: 5px;
        padding: 4px 9px;
        background: #f4f3ff;
        color: #6941c6;
        font-size: 9px;
        font-weight: 600;
    }

    .action-group { display: flex; align-items: center; gap: 7px; }

    .round-action {
        width: 34px;
        height: 31px;
        border: 1px solid #dfe4ea;
        border-radius: 7px;
        background: #fff;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: .15s;
    }

    .round-action svg { width: 15px; height: 15px; }
    .call-action { color: #139b4b; }
    .whatsapp-action { color: #18a957; }
    .open-action { color: #d99b00; }
    .round-action:hover { background: #f8fafc; transform: translateY(-1px); }

    .demo-sent {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 5px 8px;
        border-radius: 6px;
        background: #eaf3ff;
        color: #1262c5;
        font-size: 9px;
        font-weight: 800;
    }

    .demo-sent svg { width: 13px; height: 13px; }
    .column-footer { padding: 10px 12px; border-top: 1px solid #edf0f4; text-align: center; font-size: 10px; font-weight: 800; }
    .empty-board { padding: 35px 15px; text-align: center; color: #98a2b3; font-size: 11px; }

    .board-tip {
        margin-top: 11px;
        padding: 11px 15px;
        border: 1px solid #f4e3aa;
        border-radius: 8px;
        background: #fffaf0;
        display: flex;
        align-items: center;
        gap: 10px;
        font-size: 10px;
        color: #475467;
    }

    @media (max-width: 1350px) {
        .stats-grid { grid-template-columns: repeat(4, 1fr); }
    }

    @media (max-width: 1100px) {
        .board-grid { grid-template-columns: 1fr; }
    }

    @media (max-width: 700px) {
        .stats-grid { grid-template-columns: repeat(2, 1fr); }
    }

    /* QUICK FEEDBACK BUTTON + PREMIUM MODAL */
    .feedback-action {
        color:#7c3aed !important;
        background:#f5f3ff !important;
        border-color:#ddd6fe !important;
        padding:0 9px !important;
        width:auto !important;
    }

    .feedback-action span {
        font-size:9px;
        font-weight:800;
    }

    .quick-modal-backdrop {
        position:fixed;
        inset:0;
        z-index:100;
        display:flex;
        align-items:center;
        justify-content:center;
        padding:18px;
        background:rgba(15,23,42,.58);
        backdrop-filter:blur(3px);
        overflow-y:auto;
        overscroll-behavior:contain;
        -webkit-overflow-scrolling:touch;
    }

    .quick-modal {
        width:100%;
        max-width:800px;
        max-height:calc(100dvh - 36px);
        display:flex;
        flex-direction:column;
        overflow:hidden;
        border:1px solid #f4ddb0;
        border-radius:18px;
        background:
            linear-gradient(145deg,#fffef9 0%,#fffaf0 42%,#fff 100%);
        box-shadow:
            0 30px 90px rgba(15,23,42,.28),
            0 0 0 1px rgba(245,185,0,.05);
    }

    .quick-modal-head {
        position:relative;
        flex:0 0 auto;
        display:flex;
        align-items:flex-start;
        justify-content:space-between;
        gap:14px;
        padding:20px 24px 15px;
        border-bottom:0;
        background:
            radial-gradient(circle at 92% 10%,rgba(245,185,0,.12),transparent 34%),
            transparent;
    }

    .quick-modal-tag {
        display:inline-flex;
        align-items:center;
        gap:6px;
        min-height:28px;
        padding:0 10px;
        border-radius:8px;
        background:#fff1bf;
        color:#9a6500;
        font-size:9px;
        font-weight:900;
        text-transform:uppercase;
        letter-spacing:.03em;
    }

    .quick-modal-tag svg {
        width:13px;
        height:13px;
    }

    .quick-modal-title {
        margin-top:8px;
        color:#111827;
        font-size:22px;
        line-height:1.1;
        font-weight:900;
        letter-spacing:-.02em;
    }

    .quick-modal-meta {
        margin-top:8px;
        display:flex;
        flex-wrap:wrap;
        align-items:center;
        gap:14px;
        color:#7c6b4d;
        font-size:10px;
        font-weight:600;
    }

    .quick-modal-meta span {
        display:inline-flex;
        align-items:center;
        gap:5px;
    }

    .quick-modal-meta svg {
        width:13px;
        height:13px;
        color:#d99b00;
    }

    .quick-close {
        width:42px;
        height:42px;
        flex:0 0 42px;
        display:inline-flex;
        align-items:center;
        justify-content:center;
        border:1px solid #dedede;
        border-radius:13px;
        background:#fff;
        color:#475467;
        box-shadow:0 4px 12px rgba(15,23,42,.05);
        cursor:pointer;
    }

    .quick-close:hover {
        background:#fff8e8;
        border-color:#efc95a;
        color:#111827;
    }

    .quick-close svg {
        width:20px;
        height:20px;
    }

    .quick-modal-body {
        flex:1 1 auto;
        min-height:0;
        overflow-y:auto;
        overscroll-behavior:contain;
        -webkit-overflow-scrolling:touch;
        padding:0 24px 22px;
        background:transparent;
    }

    .quick-summary {
        display:grid;
        grid-template-columns:repeat(4,minmax(0,1fr));
        gap:10px;
        margin-bottom:14px;
    }

    .quick-summary-card {
        min-height:86px;
        display:flex;
        align-items:center;
        gap:10px;
        border:1px solid #eadfc9;
        border-radius:13px;
        background:rgba(255,255,255,.88);
        padding:12px;
        box-shadow:0 5px 16px rgba(83,63,22,.035);
    }

    .quick-summary-icon {
        width:38px;
        height:38px;
        flex:0 0 38px;
        display:flex;
        align-items:center;
        justify-content:center;
        border-radius:50%;
        background:#fff4cb;
        color:#d99b00;
    }

    .quick-summary-icon svg {
        width:17px;
        height:17px;
    }

    .quick-summary-label {
        font-size:8px;
        font-weight:900;
        color:#8a8f98;
        text-transform:uppercase;
        letter-spacing:.04em;
    }

    .quick-summary-value {
        margin-top:4px;
        font-size:11px;
        line-height:1.35;
        font-weight:900;
        color:#1f2937;
        overflow-wrap:anywhere;
    }

    .quick-top-actions {
        display:grid;
        grid-template-columns:1fr 1fr 1fr;
        gap:9px;
        margin-bottom:10px;
    }

    .quick-primary-action {
        min-height:50px;
        display:flex;
        align-items:center;
        justify-content:center;
        gap:9px;
        border:1px solid #f0c333;
        border-radius:11px;
        background:linear-gradient(180deg,#ffd22e 0%,#ffc400 100%);
        color:#171717;
        font-size:10px;
        font-weight:900;
        cursor:pointer;
        text-decoration:none;
        box-shadow:0 7px 16px rgba(255,196,0,.15);
    }

    .quick-primary-action.secondary {
        background:#fff;
        border-color:#eccd71;
        box-shadow:none;
    }

    .quick-primary-action svg {
        width:18px;
        height:18px;
    }

    .quick-tabs {
        display:grid;
        grid-template-columns:repeat(3,1fr);
        gap:9px;
        margin-bottom:12px;
    }

    .quick-tab {
        min-height:50px;
        display:inline-flex;
        align-items:center;
        justify-content:center;
        gap:8px;
        border:1px solid #e8cb78;
        border-radius:11px;
        background:#fff;
        padding:0 12px;
        color:#344054;
        font-size:10px;
        font-weight:900;
        cursor:pointer;
        text-decoration:none;
        transition:.15s ease;
    }

    .quick-tab svg {
        width:17px;
        height:17px;
    }

    .quick-tab.active {
        border-color:#f1b900;
        background:#fff8dc;
        color:#1f2937;
        box-shadow:inset 0 0 0 1px rgba(241,185,0,.12);
    }

    .quick-panel {
        border:1px solid #eadfc9;
        border-radius:13px;
        background:rgba(255,255,255,.90);
        padding:16px;
    }

    .quick-grid {
        display:grid;
        grid-template-columns:1fr 1fr;
        gap:14px;
    }

    .quick-field label {
        display:block;
        margin-bottom:6px;
        color:#475569;
        font-size:10px;
        font-weight:900;
    }

    .quick-field input,
    .quick-field select,
    .quick-field textarea {
        width:100%;
        border:1px solid #d7dde7;
        border-radius:10px;
        background:#fff;
        color:#1e293b;
        font-size:11px;
        outline:none;
    }

    .quick-field input,
    .quick-field select {
        min-height:46px;
        padding:0 12px;
    }

    .quick-field textarea {
        min-height:96px;
        padding:10px 12px;
        resize:vertical;
    }

    .quick-field input:focus,
    .quick-field select:focus,
    .quick-field textarea:focus {
        border-color:#f1b900;
        box-shadow:0 0 0 3px rgba(241,185,0,.12);
    }

    .quick-form-actions {
        position:sticky;
        bottom:-1px;
        z-index:12;
        display:flex;
        align-items:center;
        justify-content:flex-end;
        gap:9px;
        margin-top:16px;
        padding:12px 0 4px;
        background:linear-gradient(to top, #fff 72%, rgba(255,255,255,.90) 88%, rgba(255,255,255,0));
    }

    .quick-btn {
        min-height:42px;
        display:inline-flex;
        align-items:center;
        justify-content:center;
        gap:7px;
        border:1px solid #d8dee7;
        border-radius:10px;
        background:#fff;
        padding:0 15px;
        color:#475467;
        font-size:9px;
        font-weight:900;
        cursor:pointer;
        text-decoration:none;
    }

    .quick-btn-green {
        border-color:#059669;
        background:#059669;
        color:#fff;
    }

    .quick-btn-blue {
        border-color:#2563eb;
        background:#2563eb;
        color:#fff;
    }

    .quick-btn-violet {
        border-color:#f1b900;
        background:linear-gradient(180deg,#ffd22e 0%,#ffc400 100%);
        color:#171717;
    }

    .quick-btn-dark {
        border-color:#334155;
        background:#334155;
        color:#fff;
    }

    .quick-status {
        border:1px solid #f0dfac;
        border-radius:10px;
        background:#fffaf0;
        padding:12px;
    }

    @media (max-width:700px) {
        .quick-modal-backdrop {
            align-items:flex-start;
            padding:8px;
        }

        .quick-modal {
            max-height:calc(100dvh - 16px);
            margin:auto 0;
            border-radius:14px;
        }

        .quick-modal-head {
            padding-top:14px;
            padding-bottom:10px;
        }

        .quick-modal-head,
        .quick-modal-body {
            padding-left:14px;
            padding-right:14px;
        }

        .quick-modal-title {
            font-size:18px;
        }

        .quick-close {
            width:38px;
            height:38px;
            flex-basis:38px;
        }

        .quick-summary {
            grid-template-columns:repeat(2,1fr);
            gap:8px;
        }

        .quick-summary-card {
            min-height:74px;
            padding:10px;
        }

        .quick-top-actions,
        .quick-tabs,
        .quick-grid {
            grid-template-columns:1fr;
        }

        .quick-top-actions,
        .quick-tabs {
            gap:7px;
        }

        .quick-primary-action,
        .quick-tab {
            min-height:44px;
        }

        .quick-panel {
            padding:12px;
        }

        .quick-form-actions {
            margin-left:-12px;
            margin-right:-12px;
            padding:12px 12px 6px;
        }
    }

    @media (max-height:720px) {
        .quick-modal-backdrop {
            align-items:flex-start;
            padding-top:8px;
            padding-bottom:8px;
        }

        .quick-modal {
            max-height:calc(100dvh - 16px);
            margin:auto 0;
        }

        .quick-modal-head {
            padding-top:12px;
            padding-bottom:10px;
        }

        .quick-modal-body {
            padding-bottom:12px;
        }
    }


    /* =========================================================
       COMPACT QUICK ACTION MODAL
       ========================================================= */
    .quick-modal {
        max-width: 720px;
        border-radius: 14px;
    }

    .quick-modal-head {
        gap: 10px;
        padding: 12px 16px 9px;
    }

    .quick-modal-tag {
        min-height: 22px;
        padding: 0 8px;
        border-radius: 6px;
        font-size: 8px;
    }

    .quick-modal-tag svg {
        width: 11px;
        height: 11px;
    }

    .quick-modal-title {
        margin-top: 5px;
        font-size: 18px;
    }

    .quick-modal-meta {
        margin-top: 5px;
        gap: 10px;
        font-size: 9px;
    }

    .quick-modal-meta svg {
        width: 11px;
        height: 11px;
    }

    .quick-close {
        width: 34px;
        height: 34px;
        flex: 0 0 34px;
        border-radius: 9px;
    }

    .quick-close svg {
        width: 16px;
        height: 16px;
    }

    .quick-modal-body {
        padding: 0 16px 14px;
    }

    .quick-summary {
        gap: 7px;
        margin-bottom: 9px;
    }

    .quick-summary-card {
        min-height: 62px;
        gap: 8px;
        padding: 8px 9px;
        border-radius: 10px;
    }

    .quick-summary-icon {
        width: 30px;
        height: 30px;
        flex: 0 0 30px;
    }

    .quick-summary-icon svg {
        width: 14px;
        height: 14px;
    }

    .quick-summary-label {
        font-size: 7px;
    }

    .quick-summary-value {
        margin-top: 2px;
        font-size: 10px;
        line-height: 1.2;
    }

    .quick-top-actions {
        gap: 7px;
        margin-bottom: 7px;
    }

    .quick-primary-action {
        min-height: 38px;
        gap: 6px;
        border-radius: 8px;
        font-size: 9px;
    }

    .quick-primary-action svg {
        width: 14px;
        height: 14px;
    }

    .quick-tabs {
        gap: 7px;
        margin-bottom: 8px;
    }

    .quick-tab {
        min-height: 38px;
        gap: 6px;
        border-radius: 8px;
        padding: 0 9px;
        font-size: 9px;
    }

    .quick-tab svg {
        width: 14px;
        height: 14px;
    }

    .quick-panel {
        padding: 11px;
        border-radius: 10px;
    }

    .quick-grid {
        gap: 9px;
    }

    .quick-field label {
        margin-bottom: 4px;
        font-size: 9px;
    }

    .quick-field input,
    .quick-field select,
    .quick-field textarea {
        border-radius: 8px;
        font-size: 10px;
    }

    .quick-field input,
    .quick-field select {
        min-height: 36px;
        padding: 0 9px;
    }

    .quick-field textarea {
        min-height: 68px;
        padding: 8px 9px;
    }

    .quick-status {
        padding: 9px;
        border-radius: 8px;
    }

    .quick-form-actions {
        gap: 7px;
        margin-top: 9px;
        padding: 8px 0 2px;
    }

    .quick-btn {
        min-height: 34px;
        gap: 5px;
        border-radius: 8px;
        padding: 0 11px;
        font-size: 8px;
    }

    .quick-btn svg {
        width: 13px;
        height: 13px;
    }

    /* KPI filter buttons */
    button.stat-card {
        width: 100%;
        text-align: left;
    }

    .stat-card-link.active-metric {
        border-color: #f5b900;
        background: #fffbeb;
        box-shadow: 0 0 0 2px rgba(245,185,0,.11);
    }

    .metric-filter-bar {
        display:flex;
        align-items:center;
        justify-content:space-between;
        gap:10px;
        margin-top:8px;
        padding:8px 11px;
        border:1px solid #f2d675;
        border-radius:8px;
        background:#fffaf0;
        color:#475467;
        font-size:10px;
        font-weight:700;
    }

    .metric-filter-clear {
        border:0;
        background:transparent;
        color:#dc2626;
        font-size:9px;
        font-weight:900;
        cursor:pointer;
    }

    @media (max-width:700px) {
        .quick-modal-head {
            padding: 10px 11px 8px;
        }

        .quick-modal-body {
            padding-left: 11px;
            padding-right: 11px;
            padding-bottom: 10px;
        }

        .quick-summary {
            grid-template-columns: repeat(2,1fr);
            gap: 6px;
        }

        .quick-summary-card {
            min-height: 56px;
            padding: 7px;
        }

        .quick-top-actions,
        .quick-tabs {
            grid-template-columns: repeat(3,1fr);
            gap: 5px;
        }

        .quick-primary-action,
        .quick-tab {
            min-height: 36px;
            padding: 0 6px;
            font-size: 8px;
        }

        .quick-grid {
            grid-template-columns:1fr;
        }
    }

    @media (max-height:720px) {
        .quick-modal-head {
            padding-top:8px;
            padding-bottom:7px;
        }

        .quick-summary-card {
            min-height:54px;
        }

        .quick-primary-action,
        .quick-tab {
            min-height:34px;
        }
    }

</style>
@endonce



@php
    /*
    |--------------------------------------------------------------------------
    | Board Configuration
    |--------------------------------------------------------------------------
    |
    | Tino sections isi config se render hongi. Isliye filter fields tino me
    | EXACT same rahenge, sirf query parameter prefix alag hoga.
    |
    */

    $boardSections = [
        'new' => [
            'title' => 'New Call',
            'description' => 'Jin par abhi tak koi call nahi hui',
            'icon' => 'phone',
            'column_class' => 'new-column',
            'color_class' => 'new-color',
            'pill_class' => 'new-pill',
            'active_class' => 'active-new',
            'apply_class' => 'apply-new',
            'count' => $newCount,
            'leads' => $newLeads,
            'empty' => 'No new call leads found.',
            'more' => 'View More New Leads →',
            'all_loaded' => 'All New Leads Loaded',
            'show_var' => 'showNewFilters',
        ],

        'dialed' => [
            'title' => 'Dialed Call',
            'description' => 'Call lagayi hai — disposition kuch bhi ho',
            'icon' => 'phone',
            'column_class' => 'dialed-column',
            'color_class' => 'dialed-color',
            'pill_class' => 'dialed-pill',
            'active_class' => 'active-dialed',
            'apply_class' => 'apply-dialed',
            'count' => $dialedCount,
            'leads' => $dialedLeads,
            'empty' => 'No dialed leads found.',
            'more' => 'View More Dialed Leads →',
            'all_loaded' => 'All Dialed Leads Loaded',
            'show_var' => 'showDialedFilters',
        ],

        'connected' => [
            'title' => 'Connected Call',
            'description' => 'Jinse baat hui hai / feedback save hua hai',
            'icon' => 'phone-call',
            'column_class' => 'connected-column',
            'color_class' => 'connected-color',
            'pill_class' => 'connected-pill',
            'active_class' => 'active-connected',
            'apply_class' => 'apply-connected',
            'count' => $connectedCount,
            'leads' => $connectedLeads,
            'empty' => 'No connected leads found.',
            'more' => 'View More Connected Leads →',
            'all_loaded' => 'All Connected Leads Loaded',
            'show_var' => 'showConnectedFilters',
        ],
    ];

    $boardFilterFields = [
        'category',
        'source',
        'city',
        'priority',
        'assigned_to',
        'date_filter',
        'disposition_id',
        'demo_status',
        'label_id',
    ];

    $activeQuickMetric = $quickMetric ?? (string) request('quick_metric', '');

    // Demo tab fixed "Demo" disposition ko backend ke saath save karega.
    $demoDisposition = $dispositions->first(
        fn ($item) => mb_strtolower(trim((string) $item->name)) === 'demo'
    );

    // KPI click par existing search/board filters preserve rahenge,
    // pagination reset hogi aur selected quick_metric database query me jayega.
    $metricBaseQuery = request()->except([
        'quick_metric',
        'new_page',
        'dialed_page',
        'connected_page',
        'page',
    ]);

    $metricUrl = function (string $metric) use ($metricBaseQuery, $activeQuickMetric) {
        if ($activeQuickMetric === $metric) {
            return route('leads.index', $metricBaseQuery);
        }

        return route('leads.index', array_merge($metricBaseQuery, [
            'quick_metric' => $metric,
        ]));
    };

    $quickMetricLabels = [
        'calls_today' => 'Calls Today',
        'connected_today' => 'Connected Today',
        'employee_total_calls' => 'Employee Total Calls',
        'unique_connected' => 'Unique Connected',
        'follow_up' => 'Follow-up Calls',
        'demo_today' => 'Demo Today',
        'total_demo' => 'Total Demo',
    ];
@endphp

<div
    class="lead-board space-y-4"
    x-data="leadIndexBoard()"
    @keydown.escape.window="feedbackOpen = false"
>

    @if(session('success'))
        <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-xs font-bold text-emerald-700">
            {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-xs font-bold text-rose-700">
            {{ $errors->first() }}
        </div>
    @endif

    {{-- HEADER --}}
    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
        <div>
            <h1 class="text-2xl font-extrabold text-slate-900">Leads</h1>
            <p class="mt-1 text-xs text-slate-500">Manage your leads and track every interaction</p>
        </div>

        <div class="flex items-center gap-2">
            <form method="GET" action="{{ route('leads.index') }}" class="relative">
                {{-- Preserve all section filters while searching --}}
                @foreach(request()->except(['search', 'new_page', 'dialed_page', 'connected_page']) as $key => $value)
                    @if(!is_array($value) && $value !== '')
                        <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                    @endif
                @endforeach

                <i
                    data-lucide="search"
                    class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"
                ></i>

                <input
                    type="text"
                    name="search"
                    value="{{ request('search') }}"
                    placeholder="Search by Name, Number or Business..."
                    class="h-10 w-[360px] rounded-lg border border-slate-200 bg-white pl-10 pr-4 text-xs outline-none focus:border-amber-400"
                >
            </form>

            @can('leads.create')
                <a
                    href="{{ route('leads.create') }}"
                    class="inline-flex h-10 items-center gap-2 rounded-lg bg-amber-400 px-5 text-xs font-extrabold text-slate-900 shadow-sm hover:bg-amber-500"
                >
                    <i data-lucide="plus" class="h-4 w-4"></i>
                    Add Lead
                </a>
            @endcan
        </div>
    </div>

    {{-- STAT CARDS --}}
    <div class="stats-grid">
        <div class="stat-card">
            <span class="stat-icon bg-emerald-50 text-emerald-600"><i data-lucide="phone"></i></span>
            <div>
                <div class="stat-label">Total Leads</div>
                <div class="stat-number">{{ number_format($totalLeads) }}</div>
                <div class="stat-sub">In Panel</div>
            </div>
        </div>

        <a href="{{ $metricUrl('calls_today') }}"
           class="stat-card stat-card-link {{ $activeQuickMetric === 'calls_today' ? 'active-metric' : '' }}"
           title="Filter Calls Today">
            <span class="stat-icon bg-blue-50 text-blue-600"><i data-lucide="phone"></i></span>
            <div>
                <div class="stat-label">Calls Today</div>
                <div class="stat-number">{{ number_format($callsToday) }}</div>
            </div>
        </a>

        <a href="{{ $metricUrl('connected_today') }}"
           class="stat-card stat-card-link {{ $activeQuickMetric === 'connected_today' ? 'active-metric' : '' }}"
           title="Filter Connected Today">
            <span class="stat-icon bg-violet-50 text-violet-600"><i data-lucide="users"></i></span>
            <div>
                <div class="stat-label">Connected Today</div>
                <div class="stat-number">{{ number_format($connectedToday) }}</div>
            </div>
        </a>

        <a href="{{ $metricUrl('employee_total_calls') }}"
           class="stat-card stat-card-link {{ $activeQuickMetric === 'employee_total_calls' ? 'active-metric' : '' }}"
           title="Filter Employee Total Calls">
            <span class="stat-icon bg-orange-50 text-orange-600"><i data-lucide="badge-headset"></i></span>
            <div>
                <div class="stat-label">Employee Total Calls</div>
                <div class="stat-number">{{ number_format($employeeTotalCalls) }}</div>
                <div class="stat-sub">Since Joining</div>
            </div>
        </a>

        <a href="{{ $metricUrl('unique_connected') }}"
           class="stat-card stat-card-link {{ $activeQuickMetric === 'unique_connected' ? 'active-metric' : '' }}"
           title="Filter Unique Connected">
            <span class="stat-icon bg-cyan-50 text-cyan-600"><i data-lucide="users-round"></i></span>
            <div>
                <div class="stat-label">Unique Connected</div>
                <div class="stat-number">{{ number_format($uniqueConnected) }}</div>
                <div class="stat-sub">Distinct Leads</div>
            </div>
        </a>

        <a href="{{ $metricUrl('follow_up') }}"
           class="stat-card stat-card-link {{ $activeQuickMetric === 'follow_up' ? 'active-metric' : '' }}"
           title="Filter Follow-up Calls">
            <span class="stat-icon bg-rose-50 text-rose-600"><i data-lucide="refresh-cw"></i></span>
            <div>
                <div class="stat-label">Follow-up Calls</div>
                <div class="stat-number">{{ number_format($followUpCount) }}</div>
            </div>
        </a>

        <a href="{{ $metricUrl('demo_today') }}"
           class="stat-card stat-card-link {{ $activeQuickMetric === 'demo_today' ? 'active-metric' : '' }}"
           title="Filter Demo Today">
            <span class="stat-icon bg-blue-50 text-blue-600"><i data-lucide="video"></i></span>
            <div>
                <div class="stat-label">Demo Today</div>
                <div class="stat-number">{{ number_format($demoToday) }}</div>
            </div>
        </a>

        <a href="{{ $metricUrl('total_demo') }}"
           class="stat-card stat-card-link {{ $activeQuickMetric === 'total_demo' ? 'active-metric' : '' }}"
           title="Filter Total Demo">
            <span class="stat-icon bg-amber-50 text-amber-500"><i data-lucide="send"></i></span>
            <div>
                <div class="stat-label">Total Demo</div>
                <div class="stat-number">{{ number_format($totalDemo) }}</div>
                <div class="stat-sub">Till Now</div>
            </div>
        </a>
    </div>

    @if($activeQuickMetric !== '')
        <div class="metric-filter-bar">
            <span>
                Showing database filter:
                <strong>{{ $quickMetricLabels[$activeQuickMetric] ?? $activeQuickMetric }}</strong>
            </span>
            <a href="{{ route('leads.index', $metricBaseQuery) }}" class="metric-filter-clear">
                CLEAR FILTER
            </a>
        </div>
    @endif

    {{-- THREE COLUMNS --}}
    <div class="board-grid">

        @foreach($boardSections as $sectionKey => $section)
            @php
                $sectionFilterKeys = collect($boardFilterFields)
                    ->map(fn ($field) => $sectionKey . '_' . $field)
                    ->values()
                    ->all();

                $activeFilters = collect($sectionFilterKeys)
                    ->map(fn ($key) => request($key))
                    ->filter(fn ($value) => $value !== null && $value !== '')
                    ->count();

                $clearKeys = array_merge(
                    $sectionFilterKeys,
                    [$sectionKey . '_page']
                );

                $clearQuery = request()->except($clearKeys);
                $sectionLeads = $section['leads'];
                $showVar = $section['show_var'];
            @endphp

            <section id="{{ $sectionKey }}-call-section" class="call-column {{ $section['column_class'] }}" style="scroll-margin-top: 18px;">

                {{-- COLUMN HEADER --}}
                <div class="column-header">
                    <div class="column-heading {{ $section['color_class'] }}">
                        <i data-lucide="{{ $section['icon'] }}"></i>
                        <span>{{ $section['title'] }}</span>
                        <span class="count-pill {{ $section['pill_class'] }}">
                            {{ number_format($section['count']) }}
                        </span>
                    </div>

                    <div class="column-description">
                        {{ $section['description'] }}
                    </div>
                </div>

                {{-- FILTER TOGGLE --}}
                <div class="filter-toggle-row">
                    <button
                        type="button"
                        class="section-filter-btn"
                        :class="{{ $showVar }} ? '{{ $section['active_class'] }}' : ''"
                        @click="{{ $showVar }} = !{{ $showVar }}"
                    >
                        <i data-lucide="sliders-horizontal"></i>
                        FILTER

                        @if($activeFilters > 0)
                            <span class="active-filter-count">
                                <span>{{ $activeFilters }}</span>
                            </span>
                        @endif

                        <span x-text="{{ $showVar }} ? '▲' : '▼'" class="text-[8px]"></span>
                    </button>

                    @if($activeFilters > 0)
                        <a
                            href="{{ route('leads.index', $clearQuery) }}"
                            class="text-[9px] font-bold text-rose-600"
                        >
                            CLEAR FILTER
                        </a>
                    @endif
                </div>

                {{-- FILTER PANEL --}}
                <div
                    x-show="{{ $showVar }}"
                    x-cloak
                    class="column-filter"
                >
                    <form
                        method="GET"
                        action="{{ route('leads.index') }}"
                        class="filter-grid"
                    >
                        {{--
                            Preserve global search + OTHER sections filters.
                            Current section values are replaced by its own select fields below.
                        --}}
                        @foreach(request()->except($clearKeys) as $key => $value)
                            @if(!is_array($value) && $value !== '')
                                <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                            @endif
                        @endforeach

                        {{-- Category --}}
                        <div class="filter-field">
                            <label>Category</label>
                            <select name="{{ $sectionKey }}_category">
                                <option value="">All Categories</option>
                                @foreach($categories as $category)
                                    <option
                                        value="{{ $category }}"
                                        @selected(request($sectionKey . '_category') === $category)
                                    >
                                        {{ $category }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Source --}}
                        <div class="filter-field">
                            <label>Source</label>
                            <select name="{{ $sectionKey }}_source">
                                <option value="">All Sources</option>
                                @foreach($sources as $source)
                                    <option
                                        value="{{ $source->id }}"
                                        @selected((string) request($sectionKey . '_source') === (string) $source->id)
                                    >
                                        {{ $source->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        {{-- City --}}
                        <div class="filter-field">
                            <label>City</label>
                            <select name="{{ $sectionKey }}_city">
                                <option value="">All Cities</option>
                                @foreach($cities as $city)
                                    <option
                                        value="{{ $city }}"
                                        @selected(request($sectionKey . '_city') === $city)
                                    >
                                        {{ $city }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Priority --}}
                        <div class="filter-field">
                            <label>Priority</label>
                            <select name="{{ $sectionKey }}_priority">
                                <option value="">All Priority</option>
                                @foreach(['low', 'normal', 'high', 'urgent', 'hot'] as $priority)
                                    <option
                                        value="{{ $priority }}"
                                        @selected(request($sectionKey . '_priority') === $priority)
                                    >
                                        {{ ucfirst($priority) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Assigned Employee --}}
                        <div class="filter-field">
                            <label>Assigned To</label>
                            <select
                                name="{{ $sectionKey }}_assigned_to"
                                @disabled(!$canFilterByEmployee)
                            >
                                <option value="">All Employees</option>
                                @foreach($users as $user)
                                    <option
                                        value="{{ $user->id }}"
                                        @selected((string) request($sectionKey . '_assigned_to') === (string) $user->id)
                                    >
                                        {{ $user->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Date Added --}}
                        <div class="filter-field">
                            <label>Date Added</label>
                            <select name="{{ $sectionKey }}_date_filter">
                                <option value="">Any Time</option>
                                <option value="today" @selected(request($sectionKey . '_date_filter') === 'today')>Today</option>
                                <option value="yesterday" @selected(request($sectionKey . '_date_filter') === 'yesterday')>Yesterday</option>
                                <option value="week" @selected(request($sectionKey . '_date_filter') === 'week')>This Week</option>
                                <option value="month" @selected(request($sectionKey . '_date_filter') === 'month')>This Month</option>
                            </select>
                        </div>

                        @if (!($section['title'] == 'New Call'))
                            
                        {{-- Latest Call Disposition --}}
                        <div class="filter-field">
                            <label>Call Disposition</label>

                            <select name="{{ $sectionKey }}_disposition_id">
                                <option value="">
                                    All Dispositions
                                </option>

                                <option
                                    value="none"
                                    @selected(
                                        request(
                                            $sectionKey . '_disposition_id'
                                        ) === 'none'
                                    )
                                >
                                    No Disposition / No Call Yet
                                </option>

                                @foreach($dispositions as $disposition)
                                    <option
                                        value="{{ $disposition->id }}"
                                        @selected(
                                            (string) request(
                                                $sectionKey . '_disposition_id'
                                            )
                                            ===
                                            (string) $disposition->id
                                        )
                                    >
                                        {{ $disposition->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Demo Status --}}
                        <div class="filter-field">
                            <label>Demo Status</label>
                            <select name="{{ $sectionKey }}_demo_status">
                                <option value="">All Demo Status</option>
                                <option value="sent" @selected(request($sectionKey . '_demo_status') === 'sent')>Demo Sent</option>
                                <option value="not_sent" @selected(request($sectionKey . '_demo_status') === 'not_sent')>Demo Not Sent</option>
                            </select>
                        </div>

                        {{-- Label --}}
                        <div class="filter-field">
                            <label>Label</label>
                            <select name="{{ $sectionKey }}_label_id">
                                <option value="">All Labels</option>
                                @foreach($labels as $label)
                                    <option
                                        value="{{ $label->id }}"
                                        @selected((string) request($sectionKey . '_label_id') === (string) $label->id)
                                    >
                                        {{ $label->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        @endif


                        {{-- Actions --}}
                        <div class="filter-actions">
                            <a
                                href="{{ route('leads.index', $clearQuery) }}"
                                class="clear-filter-btn"
                            >
                                CLEAR
                            </a>

                            <button
                                type="submit"
                                class="apply-filter-btn {{ $section['apply_class'] }}"
                            >
                                APPLY FILTER
                            </button>
                        </div>
                    </form>
                </div>

                {{-- LEADS LIST --}}
                <div class="lead-list">
                    @forelse($sectionLeads as $lead)
                        @php
                            // Current employee ke New Call card par purane employee ki activity mat dikhana.
                            $latestCall = $sectionKey === 'new' ? null : $lead->latestCall;
                            $latestRemark = $latestCall?->remarks
                                ?? $latestCall?->remark
                                ?? $latestCall?->auto_remarks
                                ?? null;
                            $latestFeedback = $sectionKey === 'new'
                                ? null
                                : $latestRemark;

                            $latestNote = $sectionKey === 'new'
                                ? null
                                : $lead->latest_note_body;

                            /*
                             | Popup me poori lead ki latest previous activity dikhani hai.
                             | New Call card employee-wise hi rahega; popup history global latest call se aayegi.
                             */
                            $popupLatestCall = $lead->latestCall;

                            $popupLastRemark = $popupLatestCall?->remarks
                                ?? $popupLatestCall?->remark
                                ?? $popupLatestCall?->auto_remarks
                                ?? null;

                            $popupLastFeedback = $popupLastRemark ?: $lead->latest_note_body;

                            $duration = $latestCall?->duration_seconds
                                ?? $latestCall?->duration
                                ?? $latestCall?->call_duration
                                ?? null;

                            $durationText = null;
                            if (is_numeric($duration)) {
                                $duration = (int) $duration;
                                $durationText = str_pad((string) intdiv($duration, 60), 2, '0', STR_PAD_LEFT)
                                    . 'm '
                                    . str_pad((string) ($duration % 60), 2, '0', STR_PAD_LEFT)
                                    . 's';
                            } elseif (is_string($duration) && trim($duration) !== '') {
                                $durationText = $duration;
                            }

                            $displayName = $lead->company_name ?: $lead->name ?: 'Unnamed Lead';
                            $initials = collect(preg_split('/\s+/', trim($lead->name ?: $lead->company_name ?: 'Lead')))
                                ->filter()->take(2)
                                ->map(fn($word) => mb_strtoupper(mb_substr($word, 0, 1)))
                                ->implode('');

                            $avatarClass = match($sectionKey) {
                                'new' => 'avatar-new',
                                'dialed' => 'avatar-dialed',
                                default => 'avatar-connected',
                            };

                            $cardClass = match($sectionKey) {
                                'new' => 'new-card',
                                'dialed' => 'dialed-card',
                                default => 'connected-card',
                            };

                            $leadUrl = route('leads.show', array_merge(
                                ['lead' => $lead->id],
                                request()->except(['new_page','dialed_page','connected_page'])
                            ));

                            $whatsappNumber = preg_replace('/\D+/', '', (string) ($lead->whatsapp_number ?: $lead->mobile));

                            $popupLead = [
                                'id' => (int) $lead->id,
                                'name' => $lead->name ?: 'No Name',
                                'business' => $lead->company_name ?: '',
                                'mobile' => $lead->mobile ?: '',
                                'whatsapp' => $whatsappNumber,
                                'city' => $lead->city ?: '',
                                'state' => $lead->state ?: '',
                                'category' => $lead->category ?: '',
                                'demoSent' => (bool) $lead->demo_send,
                                'showUrl' => $leadUrl,
                                'callStoreUrl' => route('calls.store', $lead),
                                'noteStoreUrl' => route('leads.notes', $lead),
                                'demoUpdateUrl' => route('leads.update', $lead),

                                // Previous overall lead activity for popup.
                                'lastDisposition' => $popupLatestCall?->disposition?->name ?: '',
                                'lastRemark' => $popupLastFeedback ?: '',
                                'lastEmployee' => $popupLatestCall?->user?->name ?: '',
                                'lastCallAt' => $popupLatestCall?->created_at
                                    ? $popupLatestCall->created_at->format('d M Y, h:i A')
                                    : '',

                                'demoCallUrl' => route('leads.demo.store', $lead),
                            ];
                        @endphp

                        <div class="lead-card {{ $cardClass }}">
                            <div class="lead-top">
                                <div class="lead-profile">
                                    <div class="lead-avatar {{ $avatarClass }}">{{ $initials ?: 'L' }}</div>

                                    <div class="min-w-0">
                                        <a href="{{ $leadUrl }}" class="lead-name">{{ $displayName }}</a>

                                        <div class="lead-meta">
                                            <i data-lucide="phone"></i>
                                            <span>{{ $lead->mobile ?: 'No Mobile' }}</span>
                                        </div>

                                        @if($lead->city || $lead->state)
                                            <div class="lead-meta">
                                                <i data-lucide="map-pin"></i>
                                                <span>{{ collect([$lead->city,$lead->state])->filter()->implode(', ') }}</span>
                                            </div>
                                        @endif
                                    </div>
                                </div>

                                <div class="lead-right">
                                    @if($sectionKey === 'new')
                                        <div class="lead-badge text-emerald-600">New</div>
                                        <div class="call-state text-slate-500">No call yet</div>
                                    @else
                                        @if($latestCall?->created_at)
                                            <div class="call-time">
                                                <i data-lucide="clock"></i>
                                                <span>
                                                    {{ $latestCall->created_at->isToday()
                                                        ? 'Today '.$latestCall->created_at->format('h:i A')
                                                        : $latestCall->created_at->format('d M, h:i A') }}
                                                </span>
                                            </div>
                                        @endif

                                        @if($sectionKey === 'dialed')
                                            <div class="call-state text-orange-600">
                                                {{ $latestCall?->disposition?->name ?: 'Dialed' }}
                                            </div>
                                        @else
                                            <div class="call-state text-emerald-600">
                                                {{ $durationText ? '☎ '.$durationText : 'Connected' }}
                                            </div>
                                        @endif
                                    @endif
                                </div>
                            </div>

                            @if($sectionKey !== 'new')
                                <div class="feedback-row">
                                    <span class="feedback-label">Last Feedback:</span>
                                    <span class="feedback-value">{{ $latestFeedback ?: 'No feedback entered' }}</span>
                                </div>

                                <div class="note-row">
                                    <span class="note-label">Note:</span>
                                    <span class="note-value">{{ $latestNote ?: 'No note entered' }}</span>
                                </div>
                            @endif

                            @if($lead->next_follow_up_at)
                                <div class="followup-row">
                                    <i data-lucide="calendar-clock"></i>
                                    <span>
                                        Next Follow-up:
                                        <strong>{{ \Illuminate\Support\Carbon::parse($lead->next_follow_up_at)->format('d M, h:i A') }}</strong>
                                    </span>
                                </div>
                            @endif

                            @if($lead->demo_send)
                                <div class="mt-2 flex justify-end">
                                    <span class="demo-sent"><i data-lucide="video"></i> Demo Sent</span>
                                </div>
                            @endif

                            <div class="card-bottom">
                                <div>
                                    @if($lead->category)
                                        <span class="category-tag">{{ $lead->category }}</span>
                                    @endif
                                </div>

                                <div class="action-group">
                                    @if($lead->mobile)
                                        <button
                                            type="button"
                                            class="round-action call-action"
                                            title="Call from registered mobile"
                                            @click="sendCall({{ (int) $lead->id }})"
                                            :disabled="sendingCall === {{ (int) $lead->id }}"
                                        >
                                            <i data-lucide="phone"></i>
                                        </button>
                                    @endif

                                    {{-- @if($whatsappNumber)
                                        <button
                                            type="button"
                                            class="round-action whatsapp-action"
                                            title="Open WhatsApp Web"
                                            @click="openWhatsApp(@js($whatsappNumber))"
                                        >
                                            <i data-lucide="message-circle"></i>
                                        </button>
                                    @endif --}}

                                       @if($whatsappNumber)

        <button
            type="button"
            class="round-action whatsapp-action"
            title="Send WhatsApp Message"
            onclick="openWhatsappTemplateModal({{ (int) $lead->id }})"
        >
            <i data-lucide="message-circle"></i>
        </button>

    @endif

                                    <button
                                        type="button"
                                        class="round-action feedback-action"
                                        title="Feedback & Actions"
                                        @click='openFeedback(@json($popupLead))'
                                    >
                                        <i data-lucide="message-square-text"></i>
                                        <span>Feedback</span>
                                    </button>

                                    <a href="{{ $leadUrl }}" class="round-action open-action" title="Open full lead">
                                        <i data-lucide="external-link"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="empty-board">
                            {{ $section['empty'] }}
                        </div>
                    @endforelse
                </div>

                {{-- COLUMN FOOTER --}}
                <div class="column-footer {{ $section['color_class'] }}">
                    @if($sectionLeads->hasMorePages())
                        <a href="{{ $sectionLeads->nextPageUrl() }}">
                            {{ $section['more'] }}
                        </a>
                    @else
                        {{ $section['all_loaded'] }}
                    @endif
                </div>
            </section>
        @endforeach

    </div>

    <div class="board-tip">
        <i data-lucide="lightbulb" class="h-5 w-5 text-amber-500"></i>
        <div>
            <strong>Flow:</strong>
            New Call → Dialed Call → Connected Call
            <span class="ml-2">Lead ki activity aur feedback clearly track hoti rahegi.</span>
        </div>
    </div>

    {{-- QUICK FEEDBACK / ALL ACTIONS MODAL --}}
    <div
        x-show="feedbackOpen"
        x-cloak
        class="quick-modal-backdrop"
        @click.self="feedbackOpen = false"
    >
        <div class="quick-modal" role="dialog" aria-modal="true">

            <div class="quick-modal-head">

                <div class="min-w-0">

                    <div class="quick-modal-tag">
                        <i data-lucide="zap"></i>
                        Quick Lead Actions
                    </div>

                    <div
                        class="quick-modal-title truncate"
                        x-text="
                            selectedLead.business
                            ||
                            selectedLead.name
                            ||
                            'Lead'
                        "
                    ></div>

                    <div class="quick-modal-meta">

                        <span>
                            <i data-lucide="phone"></i>

                            <span
                                x-text="
                                    selectedLead.mobile
                                    ||
                                    'No mobile'
                                "
                            ></span>
                        </span>

                        <span x-show="selectedLead.city">
                            <i data-lucide="map-pin"></i>

                            <span
                                x-text="selectedLead.city"
                            ></span>
                        </span>

                        <span x-show="selectedLead.category">
                            <i data-lucide="briefcase-business"></i>

                            <span
                                x-text="selectedLead.category"
                            ></span>
                        </span>

                    </div>

                </div>


                <button
                    type="button"
                    class="quick-close"
                    @click="feedbackOpen = false"
                    aria-label="Close"
                >
                    <i data-lucide="x"></i>
                </button>

            </div>


            <div class="quick-modal-body">

                <div class="quick-summary">

                    <div class="quick-summary-card">

                        <div class="quick-summary-icon">
                            <i data-lucide="user"></i>
                        </div>

                        <div class="min-w-0">
                            <div class="quick-summary-label">
                                Lead
                            </div>

                            <div
                                class="quick-summary-value"
                                x-text="
                                    selectedLead.name
                                    ||
                                    '—'
                                "
                            ></div>
                        </div>

                    </div>


                    <div class="quick-summary-card">

                        <div class="quick-summary-icon">
                            <i data-lucide="briefcase-business"></i>
                        </div>

                        <div class="min-w-0">
                            <div class="quick-summary-label">
                                Business
                            </div>

                            <div
                                class="quick-summary-value"
                                x-text="
                                    selectedLead.business
                                    ||
                                    '—'
                                "
                            ></div>
                        </div>

                    </div>


                    <div class="quick-summary-card">

                        <div class="quick-summary-icon">
                            <i data-lucide="smartphone"></i>
                        </div>

                        <div class="min-w-0">
                            <div class="quick-summary-label">
                                Mobile
                            </div>

                            <div
                                class="quick-summary-value"
                                x-text="
                                    selectedLead.mobile
                                    ||
                                    '—'
                                "
                            ></div>
                        </div>

                    </div>


                    <div class="quick-summary-card">

                        <div class="quick-summary-icon">
                            <i data-lucide="video"></i>
                        </div>

                        <div class="min-w-0">
                            <div class="quick-summary-label">
                                Demo
                            </div>

                            <div
                                class="quick-summary-value"
                                :class="
                                    selectedLead.demoSent
                                        ? 'text-emerald-600'
                                        : 'text-slate-700'
                                "
                                x-text="
                                    selectedLead.demoSent
                                        ? 'Demo Sent'
                                        : 'Not Sent'
                                "
                            ></div>
                        </div>

                    </div>

                </div>

                {{-- PREVIOUS LEAD ACTIVITY: visible even when this employee sees the lead as New Call --}}
                <div
                    x-show="
                        selectedLead.lastDisposition
                        || selectedLead.lastRemark
                        || selectedLead.lastEmployee
                    "
                    x-cloak
                    class="mb-3 rounded-lg border border-amber-200 bg-amber-50 p-3"
                >
                    <div class="mb-2 flex items-center gap-2">
                        <i data-lucide="history" class="h-4 w-4 text-amber-600"></i>
                        <span class="text-[10px] font-black text-amber-700">
                            PREVIOUS ACTIVITY
                        </span>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <div class="text-[8px] font-bold text-slate-400">
                                LAST DISPOSITION
                            </div>
                            <div
                                class="mt-1 text-[10px] font-extrabold text-slate-800"
                                x-text="selectedLead.lastDisposition || '—'"
                            ></div>
                        </div>

                        <div>
                            <div class="text-[8px] font-bold text-slate-400">
                                LAST EMPLOYEE
                            </div>
                            <div
                                class="mt-1 text-[10px] font-extrabold text-slate-800"
                                x-text="selectedLead.lastEmployee || '—'"
                            ></div>
                        </div>

                        <div class="col-span-2">
                            <div class="text-[8px] font-bold text-slate-400">
                                LAST REMARK
                            </div>
                            <div
                                class="mt-1 whitespace-pre-line text-[10px] font-semibold leading-5 text-slate-700"
                                x-text="selectedLead.lastRemark || 'No previous remark'"
                            ></div>
                        </div>

                        <div class="col-span-2" x-show="selectedLead.lastCallAt">
                            <div class="text-[8px] font-bold text-slate-400">
                                LAST CALL
                            </div>
                            <div
                                class="mt-1 text-[9px] font-bold text-slate-600"
                                x-text="selectedLead.lastCallAt"
                            ></div>
                        </div>
                    </div>
                </div>


                <div class="quick-top-actions">

                    <button
                        type="button"
                        class="quick-primary-action"
                        @click="sendCall(selectedLead.id)"
                        :disabled="
                            !selectedLead.id
                            ||
                            sendingCall === selectedLead.id
                        "
                    >
                        <i data-lucide="phone"></i>
                        Call on Mobile
                    </button>


                    <button
                        type="button"
                        class="quick-primary-action"
                        @click="
                            openWhatsApp(
                                selectedLead.whatsapp
                            )
                        "
                        :disabled="
                            !selectedLead.whatsapp
                        "
                    >
                        <i data-lucide="message-circle"></i>
                        WhatsApp Web
                    </button>


                    <a
                        :href="
                            selectedLead.showUrl
                            ||
                            '#'
                        "
                        class="
                            quick-primary-action
                            secondary
                        "
                    >
                        <i data-lucide="external-link"></i>
                        Full Lead
                    </a>

                </div>


                <div class="quick-tabs">

                    @can('calls.create')
                        <button
                            type="button"
                            class="quick-tab"
                            :class="
                                feedbackTab === 'call'
                                    ? 'active'
                                    : ''
                            "
                            @click="
                                feedbackTab = 'call'
                            "
                        >
                            <i data-lucide="star"></i>
                            Save Feedback
                        </button>
                    @endcan


                    @can('leads.update')
                        <button
                            type="button"
                            class="quick-tab"
                            :class="
                                feedbackTab === 'demo'
                                    ? 'active'
                                    : ''
                            "
                            @click="
                                feedbackTab = 'demo'
                            "
                        >
                            <i data-lucide="video"></i>
                            Demo
                        </button>
                    @endcan


                    @can('leads.notes.create')
                        <button
                            type="button"
                            class="quick-tab"
                            :class="
                                feedbackTab === 'note'
                                    ? 'active'
                                    : ''
                            "
                            @click="
                                feedbackTab = 'note'
                            "
                        >
                            <i data-lucide="notebook-pen"></i>
                            Add Note
                        </button>
                    @endcan

                </div>


                @can('calls.create')
                        <button type="button" class="quick-tab"
                                :class="feedbackTab==='call' ? 'active' : ''"
                                @click="feedbackTab='call'">
                            <i data-lucide="phone-call"></i> Save Feedback
                        </button>
                    @endcan

                    @can('leads.update')
                        <button type="button" class="quick-tab"
                                :class="feedbackTab==='demo' ? 'active' : ''"
                                @click="feedbackTab='demo'">
                            <i data-lucide="video"></i> Demo
                        </button>
                    @endcan

                    @can('leads.notes.create')
                        <button type="button" class="quick-tab"
                                :class="feedbackTab==='note' ? 'active' : ''"
                                @click="feedbackTab='note'">
                            <i data-lucide="notebook-pen"></i> Add Note
                        </button>
                    @endcan
                </div>

                @can('calls.create')
                    <div x-show="feedbackTab==='call'" x-cloak class="quick-panel">
                        <form method="POST" :action="selectedLead.callStoreUrl">
                            @csrf

                            <div class="quick-grid">
                                <div class="quick-field">
                                    <label>Call Result <span class="text-rose-500">*</span></label>
                                    <select
                                        name="call_disposition_id"
                                        required
                                        x-model="callForm.dispositionId"
                                        @change="dispositionChanged($event)"
                                    >
                                        <option value="">Select call result</option>
                                        @foreach($dispositions as $disposition)
                                            <option
                                                value="{{ $disposition->id }}"
                                                data-requires-remarks="{{ $disposition->requires_remarks ? '1' : '0' }}"
                                                data-requires-follow-up="{{ $disposition->requires_follow_up ? '1' : '0' }}"
                                                data-auto-remarks="{{ e($disposition->auto_remarks ?? '') }}"
                                                data-next-followup="{{ $disposition->next_followup ?? '' }}"
                                            >
                                                {{ $disposition->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="quick-field">
                                    <label>Call Duration</label>
                                    <input type="number" min="0" name="duration_seconds"
                                           x-model="callForm.duration"
                                           placeholder="Seconds">
                                </div>

                                <div class="quick-field md:col-span-2" x-show="callForm.showRemarks" x-cloak>
                                    <label>
                                        Remarks
                                        <span x-show="callForm.remarksRequired" class="text-rose-500">*</span>
                                    </label>
                                    <textarea
                                        name="remarks"
                                        x-model="callForm.remarks"
                                        :required="callForm.remarksRequired"
                                        placeholder="Customer ne kya kaha..."
                                    ></textarea>
                                </div>

                                <div class="quick-field md:col-span-2" x-show="callForm.showFollowup" x-cloak>
                                    <label>
                                        Next Follow-up
                                        <span x-show="callForm.followupRequired" class="text-rose-500">*</span>
                                    </label>
                                    <input
                                        type="datetime-local"
                                        name="follow_up_at"
                                        x-model="callForm.followupAt"
                                        :required="callForm.followupRequired"
                                        min="{{ now()->addMinute()->format('Y-m-d\TH:i') }}"
                                    >
                                </div>
                            </div>

                            <div class="quick-form-actions">
                                <button type="button" class="quick-btn" @click="feedbackOpen=false">Cancel</button>
                                <button type="submit" class="quick-btn quick-btn-green">
                                    <i data-lucide="save"></i> Save Feedback
                                </button>
                            </div>
                        </form>
                    </div>
                @endcan

                @can('leads.update')
                    <div
                        x-show="feedbackTab === 'demo'"
                        x-cloak
                        class="quick-panel"
                    >
                        <form
                            method="POST"
                            :action="selectedLead.demoCallUrl"
                            @submit="prepareDemoSubmit($event)"
                            data-next-followup="{{ $demoDisposition?->next_followup ?? '' }}"
                        >
                            @csrf
                            <input type="hidden" name="demo_send_only" value="1">
                            <input type="hidden" name="demo_send" value="1">
                            @if($demoDisposition)
                                <input type="hidden" name="call_disposition_id" value="{{ $demoDisposition->id }}">
                                <input type="hidden" name="remarks" value="{{ $demoDisposition->auto_remarks ?? '' }}">
                                <input type="hidden" name="follow_up_at" value="">
                            @endif

                            <div class="quick-status">
                                <div class="text-[9px] font-bold uppercase text-slate-500">
                                    Demo Action
                                </div>

                                <div
                                    class="mt-1 text-sm font-extrabold"
                                    :class="selectedLead.demoSent
                                        ? 'text-emerald-600'
                                        : 'text-violet-700'"
                                    x-text="selectedLead.demoSent
                                        ? 'Demo Already Sent — Resend Allowed'
                                        : 'Ready To Send Demo'"
                                ></div>

                                <div class="mt-2 text-[10px] leading-5 text-slate-500">
                                    <strong>Send Demo</strong> par click karte hi
                                    lead ka <strong>Demo Sent</strong> status aur
                                    Call Log me <strong>Demo disposition</strong>
                                    dono ek hi backend transaction me save honge.
                                </div>
                            </div>

                            <div class="mt-3 rounded-lg border border-violet-100 bg-violet-50 p-3">
                                <div class="flex items-center justify-between gap-3">
                                    <div>
                                        <div class="text-[9px] font-bold uppercase text-violet-500">
                                            Call Disposition
                                        </div>
                                        <div class="mt-1 text-sm font-extrabold text-violet-800">
                                            Demo
                                        </div>
                                    </div>

                                    <div class="text-right">
                                        <div class="text-[9px] font-bold uppercase text-slate-400">
                                            Current Demo Status
                                        </div>
                                        <div
                                            class="mt-1 text-sm font-extrabold"
                                            :class="selectedLead.demoSent
                                                ? 'text-emerald-600'
                                                : 'text-slate-600'"
                                            x-text="selectedLead.demoSent
                                                ? 'Already Sent'
                                                : 'Not Sent'"
                                        ></div>
                                    </div>
                                </div>
                            </div>

                            <div class="quick-form-actions">
                                <button
                                    type="button"
                                    class="quick-btn"
                                    @click="feedbackOpen = false"
                                >
                                    Cancel
                                </button>

                                <button
                                    type="submit"
                                    class="quick-btn quick-btn-violet"
                                >
                                    <i data-lucide="video"></i>
                                    <span x-text="selectedLead.demoSent ? 'Resend Demo' : 'Send Demo'"></span>
                                </button>
                            </div>
                        </form>
                    </div>
                @endcan

                @can('leads.notes.create')
                    <div x-show="feedbackTab==='note'" x-cloak class="quick-panel">
                        <form method="POST" :action="selectedLead.noteStoreUrl">
                            @csrf

                            <div class="quick-field">
                                <label>Internal Note <span class="text-rose-500">*</span></label>
                                <textarea
                                    name="body"
                                    required
                                    x-model="noteBody"
                                    placeholder="Customer discussion, requirement ya internal note..."
                                ></textarea>
                            </div>

                            <div class="quick-form-actions">
                                <button type="button" class="quick-btn" @click="feedbackOpen=false">Cancel</button>
                                <button type="submit" class="quick-btn quick-btn-dark">
                                    <i data-lucide="plus"></i> Add Note
                                </button>
                            </div>
                        </form>
                    </div>
                @endcan
            </div>
        </div>
    </div>

</div>


@include('components.whatsapp-template-modal')

<script>
    function leadIndexBoard() {
        return {
            sendingCall: null,
            feedbackOpen: false,
            feedbackTab: 'call',
            selectedLead: {
                id:null, name:'', business:'', mobile:'', whatsapp:'',
                city:'', state:'', category:'', demoSent:false,
                lastDisposition:'', lastRemark:'', lastEmployee:'', lastCallAt:'',
                showUrl:'', callStoreUrl:'', noteStoreUrl:'', demoUpdateUrl:'', demoCallUrl:''
            },
            noteBody: '',
            callForm: {
                dispositionId:'', duration:'', remarks:'', followupAt:'',
                showRemarks:false, remarksRequired:false,
                showFollowup:false, followupRequired:false
            },

            showNewFilters: {{ request()->hasAny([
                'new_category','new_source','new_city','new_priority',
                'new_assigned_to','new_date_filter','new_disposition_id',
                'new_demo_status','new_label_id'
            ]) ? 'true' : 'false' }},

            showDialedFilters: {{ request()->hasAny([
                'dialed_category','dialed_source','dialed_city','dialed_priority',
                'dialed_assigned_to','dialed_date_filter','dialed_disposition_id',
                'dialed_demo_status','dialed_label_id'
            ]) ? 'true' : 'false' }},

            showConnectedFilters: {{ request()->hasAny([
                'connected_category','connected_source','connected_city','connected_priority',
                'connected_assigned_to','connected_date_filter','connected_disposition_id',
                'connected_demo_status','connected_label_id'
            ]) ? 'true' : 'false' }},

            openFeedback(lead) {
                this.selectedLead = { ...this.selectedLead, ...lead };
                this.feedbackTab = 'call';
                this.noteBody = '';
                this.callForm = {
                    dispositionId:'', duration:'', remarks:'', followupAt:'',
                    showRemarks:false, remarksRequired:false,
                    showFollowup:false, followupRequired:false
                };
                this.feedbackOpen = true;

                this.$nextTick(() => {
                    if (window.lucide) window.lucide.createIcons();
                });
            },

            datetimeLocalAfterMinutes(minutes) {
                const value = Number(minutes);
                if (!Number.isFinite(value) || value <= 0) return '';

                const d = new Date(Date.now() + value * 60 * 1000);
                const pad = n => String(n).padStart(2,'0');

                return d.getFullYear() + '-'
                    + pad(d.getMonth()+1) + '-'
                    + pad(d.getDate()) + 'T'
                    + pad(d.getHours()) + ':'
                    + pad(d.getMinutes());
            },

            prepareDemoSubmit(event) {
                const form = event?.target;
                if (!form) return;

                const followupInput = form.querySelector('input[name="follow_up_at"]');
                if (!followupInput) return;

                const minutes = Number(form.dataset.nextFollowup || 0);

                followupInput.value = Number.isFinite(minutes) && minutes > 0
                    ? this.datetimeLocalAfterMinutes(minutes)
                    : '';
            },

            dispositionChanged(event) {
                const option = event.target.options[event.target.selectedIndex];

                if (!option || !option.value) {
                    this.callForm.remarks = '';
                    this.callForm.followupAt = '';
                    this.callForm.showRemarks = false;
                    this.callForm.remarksRequired = false;
                    this.callForm.showFollowup = false;
                    this.callForm.followupRequired = false;
                    return;
                }

                const requiresRemarks = option.dataset.requiresRemarks === '1';
                const requiresFollowUp = option.dataset.requiresFollowUp === '1';
                const autoRemarks = String(option.dataset.autoRemarks || '').trim();
                const nextMinutes = Number(option.dataset.nextFollowup || 0);
                const hasAutoFollowup = Number.isFinite(nextMinutes) && nextMinutes > 0;

                this.callForm.remarks = autoRemarks;
                this.callForm.remarksRequired = requiresRemarks;
                this.callForm.showRemarks = requiresRemarks || autoRemarks !== '';

                this.callForm.followupAt = hasAutoFollowup
                    ? this.datetimeLocalAfterMinutes(nextMinutes)
                    : '';

                this.callForm.followupRequired = requiresFollowUp || hasAutoFollowup;
                this.callForm.showFollowup = requiresFollowUp || hasAutoFollowup;
            },

            async sendCall(leadId) {
                if (!leadId || this.sendingCall) return;

                this.sendingCall = leadId;

                try {
                    const token = document.querySelector('meta[name=csrf-token]')?.content || '';

                    const response = await fetch(`/leads/${leadId}/call-on-mobile`, {
                        method:'POST',
                        headers:{
                            'X-CSRF-TOKEN':token,
                            'Accept':'application/json',
                            'Content-Type':'application/json'
                        },
                        body:JSON.stringify({})
                    });

                    let data = {};
                    try { data = await response.json(); } catch(e) {}

                    if (!response.ok || !data.status) {
                        throw new Error(data.message || 'Unable to send call to mobile.');
                    }

                    alert(data.message || 'Call sent to mobile successfully.');
                } catch(error) {
                    alert(error.message || 'Unable to send call to mobile.');
                } finally {
                    this.sendingCall = null;
                }
            },

            // openWhatsApp(number) {
            //     const clean = String(number || '').replace(/\D/g,'');

            //     if (!clean) {
            //         alert('WhatsApp number is missing.');
            //         return;
            //     }

            //     const url = `https://web.whatsapp.com/send?phone=${encodeURIComponent(clean)}`;

            //     /*
            //      * Named window: CRM se pehli baar WhatsApp khulne ke baad
            //      * next clicks same WhatsApp Web tab/window ko reuse karenge.
            //      * Login na ho to WhatsApp Web login/QR screen khud kholega.
            //      */
            //     const w = window.open(url, 'rvg_whatsapp_web');

            //     if (w) {
            //         w.focus();
            //     } else {
            //         alert('Browser popup blocked hai. Popups allow karke dobara try karein.');
            //     }
            // }


            openWhatsApp(number) {
                const clean = String(number || '').replace(/\D/g, '');

                if (!clean) {
                    alert('WhatsApp number is missing.');
                    return;
                }

                const url = `https://web.whatsapp.com/send?phone=${encodeURIComponent(clean)}`;

                const whatsappTab = window.open('', 'rvg_whatsapp_web');

                if (whatsappTab) {
                    whatsappTab.location.href = url;
                    whatsappTab.focus();
                } else {
                    alert('Browser popup blocked hai. Popups allow karke dobara try karein.');
                }
            }
        };
    }

    document.addEventListener('DOMContentLoaded', () => {
        if (window.lucide) window.lucide.createIcons();
    });
</script>

@endsection