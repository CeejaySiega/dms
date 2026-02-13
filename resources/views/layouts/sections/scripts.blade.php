<!-- BEGIN: Vendor JS-->

@vite(['resources/assets/vendor/libs/jquery/jquery.js', 'resources/assets/vendor/libs/popper/popper.js', 'resources/assets/vendor/js/bootstrap.js'])

@vite(['resources/assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.js', 'resources/assets/vendor/js/menu.js'])

@yield('vendor-script')
<!-- END: Page Vendor JS-->
<!-- BEGIN: Theme JS-->
@vite(['resources/assets/js/main.js'])

<!-- END: Theme JS-->
<!-- Pricing Modal JS-->
@stack('pricing-script')
<!-- END: Pricing Modal JS-->
<!-- BEGIN: Page JS-->
@yield('page-script')
<!-- END: Page JS-->

<!-- app JS -->
@vite(['resources/js/app.js'])
<!-- END: app JS-->

<!-- Global Notifications -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
@if (session('success'))
<script>
	Swal.fire({
		icon: 'success',
		title: 'Success',
		text: @json(session('success')),
		timer: 2000,
		showConfirmButton: false
	});
</script>
@endif
@if (session('error'))
<script>
	Swal.fire({
		icon: 'error',
		title: 'Error',
		text: @json(session('error')),
		confirmButtonColor: '#d33'
	});
</script>
@endif

@php
	$routeName = \Illuminate\Support\Facades\Route::currentRouteName();
@endphp
@if (($inboxCount ?? 0) > 0 && !in_array($routeName, ['documents.incoming', 'documents.received']))
<script>
	Swal.fire({
		icon: 'info',
		title: 'New Inbox Documents',
		text: 'You have {{ $inboxCount }} pending document(s) in your inbox.',
		confirmButtonText: 'View Inbox',
		showCancelButton: true,
		cancelButtonText: 'Later'
	}).then((result) => {
		if (result.isConfirmed) {
			window.location.href = '{{ route('documents.incoming') }}';
		}
	});
</script>
@endif
