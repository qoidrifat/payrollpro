<?php

namespace App\Actions\Employee;

use App\Imports\EmployeeImport;
use App\Models\Employee;
use App\Repositories\EmployeeRepositoryInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;
use Maatwebsite\Excel\Facades\Excel;

class ImportEmployees
{
    /**
     * Import employees from an Excel/CSV file.
     *
     * @return array{imported_count: int, failures: \Illuminate\Support\Collection}
     */
    public function execute(UploadedFile $file): array
    {
        Gate::authorize('create', Employee::class);

        $import = new EmployeeImport();
        Excel::import($import, $file);

        return [
            'imported_count' => $import->getImportedCount(),
            'failures'       => $import->failures(),
        ];
    }
}
