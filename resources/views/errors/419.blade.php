@include('errors.layout', ['code' => '419', 'title' => 'Sesi berakhir', 'message' => $exception->getMessage() ?: 'Sesi Anda telah kedaluwarsa. Muat ulang halaman lalu coba lagi.'])
