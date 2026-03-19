<style>
/* ── Column headers ── */
.col-header {
    font-size: 0.65rem; font-weight: 700; text-transform: uppercase;
    letter-spacing: 0.1rem; color: #6c757d;
}
@media (max-width: 768px) { .col-header { font-size: 0.6rem; } }

/* ── Mail layout ── */
.mail-header { background: #f8f9fc; }
.mail-item { transition: background .15s; }
.mail-item:hover { background: #f5f6ff; }
.sent-document-row { cursor: pointer; }
.mail-list { flex-grow: 1; overflow-y: auto; min-height: 0; }

/* ── Search ── */
.mail-search-wrap {
    display: flex; align-items: center; gap: 8px;
    background: #f0f2f7; border: 1px solid #e2e5f0;
    border-radius: 7px; padding: 6px 12px;
    max-width: 280px; flex: 1;
}
.mail-search-wrap input {
    border: none; background: transparent;
    font-size: 0.8125rem; color: #1a1d3a; outline: none; width: 100%;
}
.mail-search-wrap input::placeholder { color: #8b90b8; }

/* ── Hint bar ── */
.hint-bar {
    display: flex; align-items: center; gap: 6px; padding: 8px 14px;
    border-radius: 8px; font-size: 0.78rem; font-weight: 600;
    background: #f0fdf4; border: 1px solid #bbf7d0;
    color: #166534; margin-bottom: 16px;
}

/* ── Pagination ── */
.dt-pagination {
    display: flex; align-items: center; gap: 3px; list-style: none;
    margin: 0; padding: 0; flex-wrap: wrap; justify-content: flex-end;
}
@media (max-width: 576px) { .dt-pagination { justify-content: center; width: 100%; } }
.dt-pagination .page-item .page-link {
    border: 1px solid transparent; border-radius: 0.375rem !important;
    padding: 0.3rem 0.65rem; font-size: 0.8rem; color: #6c757d;
    background: transparent; min-width: 32px; text-align: center;
    line-height: 1.5; transition: background 0.15s, color 0.15s;
}
@media (max-width: 576px) { .dt-pagination .page-item .page-link { padding: 0.25rem 0.5rem; font-size: 0.7rem; min-width: 26px; } }
.dt-pagination .page-item .page-link:hover  { background: #f0f1ff; color: #696cff; }
.dt-pagination .page-item.active .page-link  { background: #696cff; color: #fff; border-color: #696cff; }
.dt-pagination .page-item.disabled .page-link { color: #c4c6d0; pointer-events: none; }

/* ── SweetAlert above modal ── */
.swal2-container { z-index: 99999 !important; }

/* ── Mail card ── */
.mail-card {
    min-height: clamp(600px, 75vh, 900px);
    width: 100%;
    max-width: 1320px;
    margin-left: auto;
    margin-right: auto;
    display: flex; flex-direction: column; overflow: hidden;
    border-radius: 12px;
    box-shadow: 0 1px 3px rgba(26,29,58,0.07), 0 4px 16px rgba(26,29,58,0.05);
    border: 1px solid #e2e5f0;
}

/* ── Page title icon ── */
.page-title-icon {
    width: 38px; height: 38px; border-radius: 10px;
    background: rgba(105, 108, 255, 0.12);
    display: flex; align-items: center; justify-content: center; flex-shrink: 0;
}

/* ══════════════════════════════════════
   Trail log — matches screenshot exactly
══════════════════════════════════════ */

/* Outer wrapper */
.trail-log-wrap {
    border: 1px solid #e9ecef;
    border-radius: 10px;
    overflow: hidden;
}

/* "DETAILED TRAIL LOG" header */
.trail-log-title {
    font-size: .68rem;
    font-weight: 700;
    letter-spacing: .09em;
    text-transform: uppercase;
    color: #8b90b8;
    padding: 10px 16px 9px;
    background: #fff;
    border-bottom: 1px solid #f0f2f7;
}

/* Each row */
.tl-row {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    padding: 11px 16px 11px 14px;
    border-bottom: 1px solid #f0f2f7;
    position: relative;
}
.tl-row:last-child { border-bottom: none; }

/* Dot + vertical line column */
.tl-dot-col {
    display: flex;
    flex-direction: column;
    align-items: center;
    flex-shrink: 0;
    width: 12px;
    padding-top: 5px;
    align-self: stretch;
}
.tl-dot {
    width: 10px;
    height: 10px;
    border-radius: 50%;
    flex-shrink: 0;
}
.tl-vline {
    width: 1.5px;
    flex: 1;
    background: #e9ecef;
    margin-top: 5px;
}

/* Content area */
.tl-content { flex: 1; min-width: 0; }

/* Top row: name + action left, badge + date right */
.tl-main {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 10px;
    flex-wrap: wrap;
}
.tl-left  { flex: 1; min-width: 0; }
.tl-right { display: flex; align-items: center; gap: 10px; flex-shrink: 0; }

/* Name */
.tl-name {
    font-size: .875rem;
    font-weight: 600;
    color: #1a1d3a;
}

/* Action text beside name */
.tl-action {
    font-size: .875rem;
    font-weight: 400;
    color: #4b5563;
    margin-left: 3px;
}

/* Department line */
.tl-dept {
    font-size: .78rem;
    color: #8b90b8;
    margin-top: 1px;
}

/* Badge pill — matches screenshot style */
.tl-badge {
    font-size: .72rem;
    padding: 2px 10px;
    border-radius: 999px;
    font-weight: 500;
    white-space: nowrap;
    border: 1px solid transparent;
}

/* Date */
.tl-date {
    font-size: .78rem;
    color: #8b90b8;
    white-space: nowrap;
    min-width: 110px;
    text-align: right;
}

/* Remark box — italic quoted, left amber border */
.tl-remark {
    margin-top: 8px;
    padding: 6px 12px;
    background: #fafafa;
    border: 1px solid #e9ecef;
    border-radius: 6px;
    font-size: .8rem;
    color: #6c757d;
    font-style: italic;
}

/* Legend row */
.trail-legend {
    display: flex;
    gap: 14px;
    flex-wrap: wrap;
    padding: 10px 16px;
    border-top: 1px solid #f0f2f7;
    background: #fff;
}
.tleg     { display: flex; align-items: center; gap: 5px; font-size: .75rem; color: #6c757d; }
.tleg-dot { width: 9px; height: 9px; border-radius: 50%; }

/* Animations */
@keyframes pulseBlue {
    0%,100% { box-shadow: 0 0 0 3px rgba(59,130,246,.2); }
    50%     { box-shadow: 0 0 0 6px rgba(59,130,246,.05); }
}
</style>