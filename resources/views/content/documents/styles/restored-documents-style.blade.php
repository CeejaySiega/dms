<style>
    /* ── Top controls ── */
    .dt-controls {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1rem;
        flex-wrap: wrap;
        gap: 0.75rem;
    }
    @media (max-width: 768px) {
        .dt-controls {
            flex-direction: column;
            align-items: stretch;
        }
    }
    .dt-length-label {
        font-size: 0.875rem;
        color: #6c757d;
        display: flex;
        align-items: center;
        gap: 0.4rem;
    }
    @media (max-width: 576px) {
        .dt-length-label {
            font-size: 0.75rem;
        }
    }
    .dt-length-select {
        display: inline-block;
        padding: 0.25rem 1.75rem 0.25rem 0.6rem;
        font-size: 0.875rem;
        border: 1px solid #d9dee3;
        border-radius: 0.375rem;
        background-color: #fff;
        appearance: auto;
        cursor: pointer;
        color: #4a5568;
    }
    @media (max-width: 576px) {
        .dt-length-select {
            width: 100%;
            font-size: 0.75rem;
        }
    }
    .dt-length-select:focus {
        outline: none;
        border-color: #696cff;
        box-shadow: 0 0 0 0.2rem rgba(105, 108, 255, 0.15);
    }
    .dt-search-label {
        font-size: 0.875rem;
        color: #6c757d;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    @media (max-width: 768px) {
        .dt-search-label {
            flex-direction: column;
            align-items: flex-start;
        }
    }
    .dt-search-input {
        border: 1px solid #d9dee3;
        border-radius: 0.375rem;
        padding: 0.375rem 0.75rem;
        font-size: 0.875rem;
        min-width: 400px;
        transition: border-color 0.15s, box-shadow 0.15s;
    }
    @media (max-width: 768px) {
        .dt-search-input {
            min-width: 100%;
            width: 100%;
        }
    }
    .dt-search-input:focus {
        outline: none;
        border-color: #696cff;
        box-shadow: 0 0 0 0.2rem rgba(105, 108, 255, 0.15);
    }

    /* ── Table ── */
    .dt-table thead th {
        font-size: 0.72rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: #6c757d;
        border-top: none;
        border-bottom: 1px solid #e9ecef !important;
        padding: 0.85rem 1rem;
        white-space: nowrap;
        background: #fff;
    }
    @media (max-width: 576px) {
        .dt-table thead th {
            font-size: 0.65rem;
            padding: 0.5rem 0.5rem;
            white-space: normal;
        }
    }
    .dt-table tbody td {
        padding: 0.8rem 1rem;
        font-size: 0.875rem;
        vertical-align: middle;
        border-bottom: 1px solid #f0f1f3;
        color: #4a5568;
    }
    @media (max-width: 576px) {
        .dt-table tbody td {
            padding: 0.5rem 0.5rem;
            font-size: 0.75rem;
        }
    }
    .dt-table tbody tr:last-child td { border-bottom: none; }
    .dt-table tbody tr:hover { background-color: #f8f8ff; }

    .dt-action-btn {
        width: 34px;
        height: 34px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 0;
    }

    .dt-action-btn i {
        font-size: 1rem;
    }

    @media (max-width: 576px) {
        .dt-action-btn {
            width: 30px;
            height: 30px;
        }

        .dt-action-btn i {
            font-size: 0.9rem;
        }
    }

    /* ── Bottom controls ── */
    .dt-bottom {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-top: 1.25rem;
        flex-wrap: wrap;
        gap: 0.75rem;
    }
    @media (max-width: 576px) {
        .dt-bottom {
            flex-direction: column;
            align-items: stretch;
        }
    }
    .dt-info {
        font-size: 0.8125rem;
        color: #6c757d;
    }
    @media (max-width: 576px) {
        .dt-info {
            font-size: 0.75rem;
            text-align: center;
        }
    }

    /* ── Pagination ── */
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
        .dt-pagination {
            justify-content: center;
            width: 100%;
        }
    }
    .dt-pagination .page-item .page-link {
        border: 1px solid transparent;
        border-radius: 0.375rem !important;
        padding: 0.3rem 0.65rem;
        font-size: 0.875rem;
        color: #6c757d;
        background: transparent;
        min-width: 34px;
        text-align: center;
        line-height: 1.5;
        transition: background 0.15s, color 0.15s;
    }
    @media (max-width: 576px) {
        .dt-pagination .page-item .page-link {
            padding: 0.25rem 0.5rem;
            font-size: 0.75rem;
            min-width: 28px;
        }
    }
    .dt-pagination .page-item .page-link:hover {
        background: #f0f1ff;
        color: #696cff;
    }
    .dt-pagination .page-item.active .page-link {
        background: #696cff;
        color: #fff;
        border-color: #696cff;
    }
    .dt-pagination .page-item.disabled .page-link {
        color: #c4c6d0;
        pointer-events: none;
    }

    /* ── Empty state ── */
    .dt-empty {
        padding: 3rem 1rem;
        text-align: center;
    }

    .dt-empty-search {
        padding: 2.5rem 1rem;
        text-align: center;
    }

    .dt-empty-search .empty-icon {
        font-size: 2.5rem;
        color: #b8bfcc;
        margin-bottom: 0.5rem;
    }

    .dt-empty-search .empty-title {
        margin: 0;
        font-size: 1rem;
        font-weight: 600;
        color: #5f6673;
    }

    .dt-empty-search .empty-subtitle {
        margin: 0.35rem 0 0;
        font-size: 0.8125rem;
        color: #8a92a3;
    }

    @media (max-width: 576px) {
        .dt-empty-search {
            padding: 2rem 0.75rem;
        }

        .dt-empty-search .empty-icon {
            font-size: 2rem;
        }

        .dt-empty-search .empty-title {
            font-size: 0.92rem;
        }

        .dt-empty-search .empty-subtitle {
            font-size: 0.75rem;
        }
    }
</style>
