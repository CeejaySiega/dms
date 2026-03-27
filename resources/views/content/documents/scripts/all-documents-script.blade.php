<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
$(document).ready(function () {

    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json'
        }
    });

    // Live search to backend filter (debounced)
    let searchDebounce = null;
    $('#liveSearchInput').on('input', function() {
        const value = $(this).val();
        $('#searchQuery').val(value);

        if (searchDebounce) {
            clearTimeout(searchDebounce);
        }

        searchDebounce = setTimeout(function () {
            $('#filterForm').submit();
        }, 400);
    });

    $('#liveSearchInput').on('keydown', function (e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            if (searchDebounce) {
                clearTimeout(searchDebounce);
            }
            $('#searchQuery').val($(this).val());
            $('#filterForm').submit();
        }
    });

    // Archive confirmation
    $('.archive-form').on('submit', function (e) {
        e.preventDefault();
        const form = this;
        const code = $(this).closest('tr').find('.badge.bg-label-primary').text().trim();

        Swal.fire({
            title: 'Archive Document?',
            text: `Move "${code}" to archive?`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#696cff',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, archive it',
            cancelButtonText: 'Cancel'
        }).then(result => {
            if (result.isConfirmed) form.submit();
        });
    });
});
</script>
