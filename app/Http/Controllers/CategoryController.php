<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\ClassYear;
use App\Models\ActivityLog;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    /**
     * Tampilkan daftar kategori (global, tidak per-tahun).
     */
    public function index(Request $request)
    {
        $user       = $request->user();
        $activeYear = ClassYear::active()->latest('id')->first(); // cuma buat konteks, boleh juga dihapus

        // KATEGORI GLOBAL → TIDAK PAKAI class_year_id, tidak pakai is_active
        $categories = Category::orderBy('type')
            ->orderBy('name')
            ->get();

        // Activity log (opsional)
        try {
            ActivityLog::record(
                'category.index.view',
                null,
                $activeYear?->id,
                null,
                [
                    'total'       => $categories->count(),
                    'has_year'    => (bool) $activeYear,
                    'actor_id'    => $user?->id,
                    'actor_roles' => $user?->getRoleNames()->all(),
                ]
            );
        } catch (\Throwable $e) {
            // jangan sampai log bikin error
        }

        return view('categories.index', compact('categories', 'activeYear'));
    }

    /**
     * Form tambah kategori baru.
     */
    public function create(Request $request)
    {
        $activeYear = ClassYear::active()->latest('id')->first();

        return view('categories.create', [
            'activeYear' => $activeYear,
        ]);
    }

    /**
     * Simpan kategori baru (GLOBAL, type = 'expense' fix).
     */
    public function store(Request $request)
    {
        $user       = $request->user();
        $activeYear = ClassYear::active()->latest('id')->first();

        $data = $request->validate([
            'name'        => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:255'],
            // tidak ada 'type' & 'is_active' dari form
        ]);

        $category = Category::create([
            'name'        => $data['name'],
            'description' => $data['description'] ?? null,
            'type'        => 'expense', // fix, karena kategori dipakai untuk pengeluaran
        ]);

        // LOG: kategori dibuat
        try {
            ActivityLog::record(
                'category.created',
                $category,
                $activeYear?->id,
                null,
                $category->toArray()
            );
        } catch (\Throwable $e) {}

        return redirect()
            ->route('categories.index')
            ->with('success', 'Kategori berhasil dibuat.');
    }

    /**
     * Form edit kategori.
     */
    public function edit(Category $category)
    {
        $activeYear = ClassYear::active()->latest('id')->first();

        return view('categories.edit', [
            'category'   => $category,
            'activeYear' => $activeYear,
        ]);
    }

    /**
     * Update kategori global (hanya name & description).
     */
    public function update(Request $request, Category $category)
    {
        $user       = $request->user();
        $activeYear = ClassYear::active()->latest('id')->first();

        $data = $request->validate([
            'name'        => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:255'],
            // tidak ada 'type', tidak ada 'is_active'
        ]);

        $before = $category->toArray();

        $category->update([
            'name'        => $data['name'],
            'description' => $data['description'] ?? null,
            // 'type' dibiarkan apa adanya (biasanya 'expense')
        ]);

        // LOG: kategori di-update
        try {
            ActivityLog::record(
                'category.updated',
                $category,
                $activeYear?->id,
                $before,
                $category->toArray()
            );
        } catch (\Throwable $e) {}

        return redirect()
            ->route('categories.index')
            ->with('success', 'Kategori berhasil diperbarui.');
    }

    /**
     * Hapus kategori.
     */
    public function destroy(Request $request, Category $category)
{
    $activeYear = ClassYear::active()->latest('id')->first();
    $before     = $category->toArray();

    $category->delete();

    try {
        ActivityLog::record(
            'category.deleted',
            null,
            $activeYear?->id,
            $before,
            [
                'message' => 'Kategori pengeluaran dihapus',
            ]
        );
    } catch (\Throwable $e) {}

    return redirect()
        ->route('categories.index')
        ->with('success', 'Kategori berhasil dihapus.');
}
}
