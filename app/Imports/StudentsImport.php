<?php

namespace App\Imports;

use App\Models\User;
use App\Models\StudentEnrollment;
use App\Models\ClassYear;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Concerns\OnEachRow;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\SkipsErrors;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Row;

class StudentsImport implements OnEachRow, WithHeadingRow, WithValidation, SkipsOnFailure, WithChunkReading
{
    use SkipsErrors;

    protected ClassYear $year;
    protected array $failures = [];

    public function __construct(ClassYear $year)
    {
        $this->year = $year;
    }

    // ⬇️ GANTI NAMA METHOD: onEachRow ➜ onRow
    public function onRow(Row $row): void
    {
        $r = $row->toArray();

        $name     = trim((string)($r['name']   ?? $r['nama']   ?? ''));
        $email    = trim((string)($r['email']  ?? ''));
        $nis      = trim((string)($r['nis']    ?? ''));
        $nisn     = trim((string)($r['nisn']   ?? ''));
        $gender   = strtoupper(trim((string)($r['gender'] ?? '')));
        $password = (string)($r['password'] ?? '');
        $active   = trim((string)($r['active'] ?? ''));
        $active   = $active === '' ? '1' : $active;

        if (!in_array($gender, ['L','P'], true)) {
            $g = strtolower($gender);
            if (in_array($g, ['l','male','laki','laki-laki','pria','cowok'], true)) $gender = 'L';
            elseif (in_array($g, ['p','female','perempuan','wanita','cewek'], true)) $gender = 'P';
            else $gender = null;
        }

        DB::transaction(function () use ($name,$email,$nis,$nisn,$gender,$password,$active) {
            $user = User::firstOrCreate(
                ['email' => $email],
                [
                    'name'     => $name,
                    'password' => Hash::make($password !== '' ? $password : 'password123'),
                    'gender'   => $gender,
                    'nisn'     => $nisn !== '' ? $nisn : null,
                    'active'   => (bool) ((int) $active),
                ]
            );

            if (!$user->wasRecentlyCreated) {
                $updates = [
                    'name'   => $name ?: $user->name,
                    'gender' => $gender,
                    'nisn'   => $nisn !== '' ? $nisn : $user->nisn,
                ];
                if ($password !== '') {
                    $updates['password'] = Hash::make($password);
                }
                $user->update($updates);
            }

            if (!$user->hasRole('siswa')) {
                $user->assignRole('siswa');
            }

            StudentEnrollment::updateOrCreate(
                [
                    'class_year_id'   => $this->year->id,
                    'student_user_id' => $user->id,
                ],
                [
                    'nis'       => $nis !== '' ? $nis : null,
                    'is_active' => (bool) ((int) $active),
                ]
            );
        });
    }

    public function rules(): array
    {
        return [
            '*.name'     => ['required','string','max:100'],
            '*.email'    => ['required','email','max:150'],
            '*.nis'      => ['nullable','string','max:30'],
            '*.nisn'     => ['nullable','string','max:30'],
            '*.gender'   => ['nullable','in:L,P,l,p,male,female,laki,laki-laki,perempuan,wanita,pria,cowok,cewek'],
            '*.password' => ['nullable','string','min:6'],
            '*.active'   => ['nullable', Rule::in(['0','1','',0,1])],
        ];
    }

    public function onFailure(...$failures): void
    {
        foreach ($failures as $f) {
            $this->failures[] = $f;
        }
    }

    public function getFailures()
    {
        return $this->failures;
    }

    public function chunkSize(): int
    {
        return 500;
    }
}
