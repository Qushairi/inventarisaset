@php
    $flashMessages = collect([
        'success' => session('success'),
        'error' => session('error'),
        'warning' => session('warning'),
        'info' => session('info') ?? session('status'),
    ])->filter(fn ($message) => filled($message));
@endphp

<script>
    document.addEventListener('DOMContentLoaded', () => {
        if (typeof Swal === 'undefined') {
            return;
        }

        const flashMessages = @json($flashMessages);
        const iconTitles = {
            success: 'Berhasil',
            error: 'Gagal',
            warning: 'Perhatian',
            info: 'Informasi',
        };

        Object.values(flashMessages).forEach((message) => {
            document.querySelectorAll('.alert').forEach((alert) => {
                if (alert.textContent.trim().includes(message)) {
                    alert.remove();
                }
            });
        });

        Object.entries(flashMessages).forEach(([icon, message]) => {
            Swal.fire({
                icon,
                title: iconTitles[icon] ?? 'Informasi',
                text: message,
                confirmButtonText: 'OK',
                confirmButtonColor: '#435ebe',
            });
        });

        document.querySelectorAll('form[data-swal-confirm]').forEach((form) => {
            form.addEventListener('submit', (event) => {
                if (form.dataset.swalConfirmed === 'true') {
                    return;
                }

                event.preventDefault();

                const submitter = event.submitter;
                const options = submitter?.dataset.swalConfirm !== undefined
                    ? submitter.dataset
                    : form.dataset;

                Swal.fire({
                    icon: options.swalIcon || 'warning',
                    title: options.swalTitle || 'Konfirmasi aksi',
                    text: options.swalText || 'Apakah Anda yakin ingin melanjutkan?',
                    showCancelButton: true,
                    confirmButtonText: options.swalConfirmText || 'Ya, lanjutkan',
                    cancelButtonText: options.swalCancelText || 'Batal',
                    confirmButtonColor: options.swalConfirmColor || '#435ebe',
                    cancelButtonColor: '#6c757d',
                    reverseButtons: true,
                }).then((result) => {
                    if (!result.isConfirmed) {
                        return;
                    }

                    form.dataset.swalConfirmed = 'true';

                    if (submitter && typeof form.requestSubmit === 'function') {
                        form.requestSubmit(submitter);
                        return;
                    }

                    form.submit();
                });
            });
        });
    });
</script>
