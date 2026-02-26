<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;

class RowsImport implements ToCollection
{
    public Collection $rows;

    public function collection(Collection $collection): void
    {
        $this->rows = $collection;
    }
}
