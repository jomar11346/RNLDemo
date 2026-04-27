<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function loadUsers(Request $request)
    {
        $search = $request->input('search');

        $users = User::with(['gender'])
            ->leftJoin('tbl_genders', 'tbl_users.gender_id', '=', 'tbl_genders.gender_id')
            ->where('tbl_users.is_deleted', false)
            ->orderBy('tbl_users.last_name', 'asc')
            ->orderBy('tbl_users.first_name', 'asc')
            ->orderBy('tbl_users.middle_name', 'asc')
            ->orderBy('tbl_users.suffix_name', 'asc');

        if ($search) {
            $users->where(function ($user) use ($search) {
                $user->where('tbl_users.first_name', 'like', "%{$search}%")
                    ->orWhere('tbl_users.middle_name', 'like', "%{$search}%")
                    ->orWhere('tbl_users.last_name', 'like', "%{$search}%")
                    ->orWhere('tbl_users.suffix_name', 'like', "%{$search}%")
                    ->orWhere('tbl_genders.gender', 'like', "%{$search}%");
            });
        }

        $users = $users->paginate(15);

        $users->getCollection()->transform(function ($user) {
            $user->profile_picture = $user->profile_picture ? url('storage/public/img/user/profile_picture/' . $user->profile_picture) : null;

            return $user;
        });

        return response()->json([
            'users' => $users
        ], 200);
    }

    public function storeUser(Request $request)
    {
        $hasProfilePictureColumn = Schema::hasColumn('tbl_users', 'profile_picture');

        $validated = $request->validate([
            'add_user_profile_picture' => ['nullable', 'image', 'mimes:png,jpg,jpeg'],
            'first_name' => ['required', 'max:55'],
            'middle_name' => ['nullable', 'max:55'],
            'last_name' => ['required', 'max:55'],
            'suffix_name' => ['nullable', 'max:55'],
            'gender' => ['required'],
            'birth_date' => ['required', 'date'],
            'username' => ['required', 'min:6', 'max:12', Rule::unique('tbl_users', 'username')],
            'password' => ['required', 'min:6', 'max:12', 'confirmed'],
            'password_confirmation' => ['required', 'min:6', 'max:12']
        ]);

        if ($hasProfilePictureColumn && $request->hasFile('add_user_profile_picture')) {
            $filenameWithExtension = $request->file('add_user_profile_picture');
            $filename = pathinfo($filenameWithExtension->getClientOriginalName(), PATHINFO_FILENAME);
            $extension = $filenameWithExtension->getClientOriginalExtension();
            $filenameToStore = sha1($filename . '_' . time() . '.' . $extension);
            $filenameWithExtension->storeAs('public/img/user/profile_picture', $filenameToStore);
            $validated['add_user_profile_picture'] = $filenameToStore;
        }

        $age = date_diff(date_create($validated['birth_date']), date_create('now'))->y;

        $createData = [
            'first_name' => $validated['first_name'],
            'middle_name' => $validated['middle_name'],
            'last_name' => $validated['last_name'],
            'suffix_name' => $validated['suffix_name'],
            'gender_id' => $validated['gender'],
            'birth_date' => $validated['birth_date'],
            'age' => $age,
            'username' => $validated['username'],
            'password' => $validated['password']
        ];

        if ($hasProfilePictureColumn) {
            $createData['profile_picture'] = $validated['add_user_profile_picture'] ?? null;
        }

        User::create($createData);

        return response()->json([
            'message' => 'User Successfully Saved.'
        ], 200);
    }

    public function updateUser(Request $request, User $user)
    {
        $hasProfilePictureColumn = Schema::hasColumn('tbl_users', 'profile_picture');

        $validated = $request->validate([
            'edit_user_profile_picture' => ['nullable', 'image', 'mimes:png,jpg,jpeg'],
            'first_name' => ['required', 'max:55'],
            'middle_name' => ['nullable', 'max:55'],
            'last_name' => ['required', 'max:55'],
            'suffix_name' => ['nullable', 'max:55'],
            'gender' => ['required'],
            'birth_date' => ['required', 'date'],
            'username' => ['required', 'min:6', 'max:12', Rule::unique('tbl_users', 'username')->ignore($user)]
        ]);

        if ($hasProfilePictureColumn && $request->has('remove_profile_picture') && $request->remove_profile_picture == '1') {
            if ($user->profile_picture && Storage::exists('public/img/user/profile_picture/' . $user->profile_picture)) {
                Storage::delete('public/img/user/profile_picture/' . $user->profile_picture);
                $user->profile_picture = null;
            }
        } else if ($hasProfilePictureColumn && $request->hasFile('edit_user_profile_picture')) {
            if ($user->profile_picture && Storage::exists('public/img/user/profile_picture/' . $user->profile_picture)) {
                Storage::delete('public/img/user/profile_picture/' . $user->profile_picture);
            }

            $filenameWithExtension = $request->file('edit_user_profile_picture');
            $filename = pathinfo($filenameWithExtension->getClientOriginalName(), PATHINFO_FILENAME);
            $extension = $filenameWithExtension->getClientOriginalExtension();
            $filenameToStore = sha1($filename . '_' . time() . '.' . $extension);
            $filenameWithExtension->storeAs('public/img/user/profile_picture', $filenameToStore);
            $validated['edit_user_profile_picture'] = $filenameToStore;
        }

        $age = date_diff(date_create($validated['birth_date']), date_create('now'))->y;

        $updateData = [
            'first_name' => $validated['first_name'],
            'middle_name' => $validated['middle_name'],
            'last_name' => $validated['last_name'],
            'suffix_name' => $validated['suffix_name'],
            'gender_id' => $validated['gender'],
            'birth_date' => $validated['birth_date'],
            'age' => $age,
            'username' => $validated['username']
        ];

        if ($hasProfilePictureColumn) {
            $updateData['profile_picture'] = $validated['edit_user_profile_picture'] ?? $user->profile_picture;
        }

        $user->update($updateData);

        if ($hasProfilePictureColumn) {
            $user->profile_picture = $user->profile_picture ? url('storage/public/img/user/profile_picture/' . $user->profile_picture) : null;
        } else {
            $user->profile_picture = null;
        }

        return response()->json([
            'message' => 'User Successfully Updated.',
            'user' => $user
        ], 200);
    }

    public function destroyUser(User $user)
    {
        $user->update([
            'is_deleted' => true
        ]);

        return response()->json([
            'message' => 'User Successfully Deleted.'
        ], 200);
    }
}
