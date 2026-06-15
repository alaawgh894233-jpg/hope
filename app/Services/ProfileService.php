<?php

namespace App\Services;

use App\Models\Profile;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

class ProfileService
{
    public function create(User $user, array $data)
    {
        if (isset($data['profile_image'])) {
            $data['profile_image'] = $data['profile_image']
                ->store('profiles', 'public');
        }

        if (isset($data['cv_file'])) {
            $data['cv_file'] = $data['cv_file']
                ->store('cvs', 'public');
        }

        $profile = Profile::create(array_merge(
            $data,
            ['user_id' => $user->id]
        ));

        return $this->formatProfile($profile);
    }

    public function update(User $user, array $data)
    {
        $profile = $user->profile;

        if (!$profile) {
            return null;
        }

        if (isset($data['profile_image'])) {

            if ($profile->profile_image) {
                Storage::disk('public')
                    ->delete($profile->profile_image);
            }

            $data['profile_image'] = $data['profile_image']
                ->store('profiles', 'public');
        }

        if (isset($data['cv_file'])) {

            if ($profile->cv_file) {
                Storage::disk('public')
                    ->delete($profile->cv_file);
            }

            $data['cv_file'] = $data['cv_file']
                ->store('cvs', 'public');
        }

        $profile->update($data);

        return $this->formatProfile($profile);
    }

    public function get(User $user)
    {
        return $this->formatProfile($user->profile);
    }

    public function delete(User $user)
    {
        $profile = $user->profile;

        if (!$profile) {
            return false;
        }

        if ($profile->profile_image) {
            Storage::disk('public')
                ->delete($profile->profile_image);
        }

        if ($profile->cv_file) {
            Storage::disk('public')
                ->delete($profile->cv_file);
        }

        return $profile->delete();
    }

    private function formatProfile($profile)
    {
        if (!$profile) {
            return null;
        }

        $profile->profile_image = $profile->profile_image
            ? asset('storage/' . $profile->profile_image)
            : null;

        $profile->cv_file = $profile->cv_file
            ? asset('storage/' . $profile->cv_file)
            : null;

        return $profile;
    }
}
