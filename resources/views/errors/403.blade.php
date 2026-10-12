@include('errors.layout', ['code' => '403', 'title' => 'Akses ditolak', 'message' => $exception->getMessage() ?: 'Anda tidak memiliki izin untuk membuka halaman ini.'])
