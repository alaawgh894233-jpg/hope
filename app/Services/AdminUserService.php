<?php

namespace App\Services;

use App\Models\User;

class AdminUserService
{
    // 📌 قائمة المستخدمين مع فلترة
    public function list(array $filters)
    {
        $query = User::query();

        if (!empty($filters['role'])) {
            $query->where('role', $filters['role']);
        }

        if (!empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('name', 'like', "%{$filters['search']}%")
                    ->orWhere('email', 'like', "%{$filters['search']}%");
            });
        }

        if (!empty($filters['status'])) {
            $filters['status'] === 'banned'
                ? $query->whereNotNull('banned_at')
                : $query->whereNull('banned_at');
        }

        $perPage = min((int)($filters['per_page'] ?? 15), 50);

        return $query->latest()->paginate($perPage);
    }

    // 📌 عرض مستخدم
    public function show(int $id): User
    {
        return User::with('company')->findOrFail($id);
    }

    // 📌 حظر مستخدم
    public function ban(int $id, ?string $reason = null): array
    {
        $user = User::findOrFail($id);

        if ($user->role === 'admin') {
            return ['status' => 403, 'message' => 'Cannot ban an admin'];
        }

        if ($user->banned_at) {
            return ['status' => 409, 'message' => 'User already banned'];
        }

        $user->update([
            'banned_at'     => now(),
            'ban_reason'    => $reason,
        ]);

        // ✅ نحذف كل توكناته فوراً
        $user->tokens()->delete();

        return ['status' => 200, 'message' => 'User banned', 'data' => $user];
    }

    // 📌 تفعيل مستخدم محظور
    public function unban(int $id): array
    {
        $user = User::findOrFail($id);

        if (!$user->banned_at) {
            return ['status' => 409, 'message' => 'User is not banned'];
        }

        $user->update([
            'banned_at'  => null,
            'ban_reason' => null,
        ]);

        return ['status' => 200, 'message' => 'User unbanned', 'data' => $user];
    }

    // 📌 حذف مستخدم
    public function delete(int $id): array
    {
        $user = User::findOrFail($id);

        if ($user->role === 'admin') {
            return ['status' => 403, 'message' => 'Cannot delete an admin'];
        }

        $user->tokens()->delete();
        $user->delete();

        return ['status' => 200, 'message' => 'User deleted'];
    }
}
