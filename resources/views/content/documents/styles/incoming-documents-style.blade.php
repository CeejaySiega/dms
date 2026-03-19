<style>
/* ── Column headers ── */
.col-header {
    font-size: 0.65rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.1rem;
    color: #6c757d;
}
@media (max-width: 768px) {
    .col-header { font-size: 0.6rem; }
}

/* ── Mail rows ── */
.mail-header { background: #f8f9fc; }
.mail-item { transition: background .15s; }
.mail-item:hover { background: #f5f6ff; }
.mail-unread { background: rgba(105, 108, 255, 0.04); }

/* ── Scrollable list ── */
.mail-list {
    flex-grow: 1;
    overflow-y: auto;
    min-height: 0;
}

/* ── Search input in toolbar ── */
.mail-search-wrap {
    display: flex;
    align-items: center;
    gap: 8px;
    background: #f0f2f7;
    border: 1px solid #e2e5f0;
    border-radius: 7px;
    padding: 6px 12px;
    max-width: 280px;
    flex: 1;
}
.mail-search-wrap input {
    border: none;
    background: transparent;
    font-size: 0.8125rem;
    color: #1a1d3a;
    outline: none;
    width: 100%;
}
.mail-search-wrap input::placeholder { color: #8b90b8; }

/* ── Hint bar ── */
.hint-bar {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 8px 14px;
    border-radius: 8px;
    font-size: 0.78rem;
    font-weight: 600;
    background: #f0fdf4;
    border: 1px solid #bbf7d0;
    color: #166534;
    margin-bottom: 16px;
}

/* ── DataTables-style pagination ── */
.dt-pagination {
    display: flex;
    align-items: center;
    gap: 3px;
    list-style: none;
    margin: 0;
    padding: 0;
    flex-wrap: wrap;
    justify-content: flex-end;
}
@media (max-width: 576px) {
    .dt-pagination { justify-content: center; width: 100%; }
}
.dt-pagination .page-item .page-link {
    border: 1px solid transparent;
    border-radius: 0.375rem !important;
    padding: 0.3rem 0.65rem;
    font-size: 0.8rem;
    color: #6c757d;
    background: transparent;
    min-width: 32px;
    text-align: center;
    line-height: 1.5;
    transition: background 0.15s, color 0.15s;
}
@media (max-width: 576px) {
    .dt-pagination .page-item .page-link { padding: 0.25rem 0.5rem; font-size: 0.7rem; min-width: 26px; }
}
.dt-pagination .page-item .page-link:hover { background: #f0f1ff; color: #696cff; }
.dt-pagination .page-item.active .page-link { background: #696cff; color: #fff; border-color: #696cff; }
.dt-pagination .page-item.disabled .page-link { color: #c4c6d0; pointer-events: none; }

/* SweetAlert2 always above Bootstrap modal */
.swal2-container { z-index: 99999 !important; }

/* ── Mail card ── */
.mail-card {
    min-height: clamp(600px, 75vh, 900px);
    display: flex;
    flex-direction: column;
    overflow: hidden;
    border-radius: 12px;
    box-shadow: 0 1px 3px rgba(26,29,58,0.07), 0 4px 16px rgba(26,29,58,0.05);
    border: 1px solid #e2e5f0;
}

/* ── Page title styling ── */
.page-title-icon {
    width: 38px;
    height: 38px;
    border-radius: 10px;
    background: rgba(105, 108, 255, 0.12);
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}
</style>
