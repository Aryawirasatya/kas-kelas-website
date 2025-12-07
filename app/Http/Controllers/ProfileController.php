<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Models\ActivityLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
        ]);
    }

    /**
     * Update the user's profile information + catat ke activity_logs.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();

        // 🔹 Snapshot sebelum (field yang mau kita log saja)
        $before = $user->only(['name', 'email']);

        // 🔹 Logic default Breeze
        $user->fill($request->validated());

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        // 🔹 Snapshot sesudah
        $after = $user->only(['name', 'email']);

        // 🔹 Simpan ke activity_logs
        try {
            ActivityLog::create([
                'class_year_id' => null,         // profil tidak terkait tahun ajaran tertentu
                'actor_id'      => $user->id,    // siapa yang melakukan perubahan
                'action'        => 'profile.update',
                'entity_type'   => 'user',
                'entity_id'     => $user->id,
                'from_json'     => $before,
                'to_json'       => $after,
            ]);
        } catch (\Throwable $e) {
            // jangan ganggu update profil kalau log gagal
        }

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * Delete the user's account + catat ke activity_logs.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        // 🔹 Snapshot sebelum hapus
        $before = $user->only(['name', 'email']);

        Auth::logout();

        // 🔹 Catat di log dulu sebelum user beneran dihapus
        try {
            ActivityLog::create([
                'class_year_id' => null,
                'actor_id'      => $user->id,
                'action'        => 'profile.deleted',
                'entity_type'   => 'user',
                'entity_id'     => $user->id,
                'from_json'     => $before,
                'to_json'       => null,
            ]);
        } catch (\Throwable $e) {
            // silent
        }

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
