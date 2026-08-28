@once
<style>

    .software-ui {
        font-family:
            Inter,
            ui-sans-serif,
            system-ui,
            sans-serif;
        color: #18213a;
        font-size: 12px;
    }

    .software-panel,
    .software-toolbar {
        border: 1px solid #e5e9f2;
        background: #fff;
        border-radius: 12px;
        box-shadow:
            0 4px 14px
            rgba(31, 42, 80, .055);
    }

    .software-panel-title {
        min-height: 46px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        padding: 11px 16px;
        border-bottom: 1px solid #edf0f5;
        color: #17203a;
        font-size: 13px;
        font-weight: 800;
    }

    .panel-heading-label {
        display: inline-flex;
        align-items: center;
        gap: 9px;
    }

    .panel-heading-icon {
        display: inline-flex;
        width: 27px;
        height: 27px;
        align-items: center;
        justify-content: center;
        border-radius: 8px;
        background: #eef2ff;
        color: #4f46e5;
    }

    .panel-heading-icon svg {
        width: 15px;
        height: 15px;
    }

    .data-toolbar-icon {
        display: inline-flex;
        width: 42px;
        height: 42px;
        align-items: center;
        justify-content: center;
        border-radius: 10px;
        background:
            linear-gradient(
                145deg,
                #2864fa,
                #842de8
            );
        color: #fff;
    }

    .software-btn {
        display: inline-flex;
        min-height: 36px;
        align-items: center;
        justify-content: center;
        gap: 6px;
        border: 1px solid #e1e5ed;
        border-radius: 8px;
        background: #fff;
        padding: 0 14px;
        color: #33405b;
        font-size: 10px;
        font-weight: 800;
        transition: .15s;
    }

    .software-btn-primary {
        border-color: transparent;
        background:
            linear-gradient(
                100deg,
                #2563eb 0%,
                #6338ef 58%,
                #8b2de9 100%
            );
        color: #fff;
    }

    .software-btn-primary:hover {
        color: #fff;
    }

    .software-btn-success {
        border-color: #a7f3d0;
        background: #ecfdf5;
        color: #047857;
    }

    .software-btn-danger {
        border-color: #fecaca;
        background: #fff1f2;
        color: #be123c;
    }

    .software-label {
        margin-bottom: 6px;
        display: block;
        color: #475569;
        font-size: 9px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: .055em;
    }

    .software-ui input,
    .software-ui select,
    .software-ui textarea {
        border:
            1px solid
            #cbd5e1 !important;
        border-radius:
            8px !important;
        background:
            #fff;
        font-size:
            11px !important;
        color:
            #0f172a;
    }

    .software-ui input,
    .software-ui select {
        min-height: 40px;
        padding-left: 11px;
        padding-right: 11px;
    }

    .software-ui textarea {
        padding: 10px 11px;
    }

    .software-ui input:focus,
    .software-ui select:focus,
    .software-ui textarea:focus {
        border-color:
            #3b82f6 !important;
        outline:
            none;
        box-shadow:
            0 0 0 3px
            rgba(59,130,246,.10);
    }

    .software-error {
        margin-top: 4px;
        color: #dc2626;
        font-size: 10px;
        font-weight: 600;
    }

</style>
@endonce