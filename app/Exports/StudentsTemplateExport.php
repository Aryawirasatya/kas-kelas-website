<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;

class StudentsTemplateExport implements FromArray
{
    public function array(): array
    {
        return [
            ['name','email','nis','nisn','gender','password','active'],

            // NISN harus 10 digit
            ['Ahmad 01','siswa01@smkn1cjr.sch.id','2025001','0098700001','L','p@ss01word',1],
            ['Nisa 02','siswa02@smkn1cjr.sch.id','2025002','0098700002','P','p@ss02word',1],
        ];
    }
}
