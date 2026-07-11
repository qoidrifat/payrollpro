<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tables = ['users', 'employees', 'payrolls', 'attendances'];

        foreach ($tables as $table) {
            if (! Schema::hasColumn($table, 'company_id')) {
                Schema::table($table, function (Blueprint $table) {
                    // restrictOnDelete: menghapus perusahaan yang masih memiliki
                    // data (user/karyawan/penggajian/absensi) ditolak, sehingga
                    // baris tidak pernah menjadi orphan (company_id = NULL) dan
                    // lepas dari isolasi tenant. nullable() tetap dipertahankan
                    // karena kolom ditambahkan ke tabel yang sudah terisi
                    // (NOT NULL akan gagal sebelum backfill) dan user global
                    // boleh tidak terikat perusahaan.
                    $table->foreignId('company_id')->nullable()->constrained()->restrictOnDelete();
                });
            }
        }
    }

    public function down(): void
    {
        foreach (['users', 'employees', 'payrolls', 'attendances'] as $table) {
            if (Schema::hasColumn($table, 'company_id')) {
                Schema::table($table, function (Blueprint $table) {
                    $table->dropForeign(['company_id']);
                    $table->dropColumn('company_id');
                });
            }
        }
    }
};
