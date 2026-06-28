<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class PublicProfile extends Model
{
    protected $fillable = [
        'user_id',
        'slug',
        'is_public',
        'visible_sections',
        'total_views',
        'last_viewed_at',
        'meta_title',
        'meta_description',
        'theme_color',
    ];

    protected $casts = [
        'is_public'        => 'boolean',
        'visible_sections' => 'array',
        'last_viewed_at'   => 'datetime',
    ];

    // ==================
    // Relations
    // ==================

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function views()
    {
        return $this->hasMany(ProfileView::class);
    }

    // ==================
    // Static
    // ==================

    public static function generateSlug(string $name, int $userId): string
    {
        $base = Str::slug($name);
        $suffix = Str::random(6);
        $slug = "{$base}-{$suffix}";

        // التأكد من عدم التكرار
        while (static::where('slug', $slug)->exists()) {
            $slug = "{$base}-" . Str::random(6);
        }

        return $slug;
    }

    // ==================
    // Helpers
    // ==================

    public function getPublicUrl(): string
    {
        return url("/profile/{$this->slug}");
    }

    public function isSectionVisible(string $section): bool
    {
        $sections = $this->visible_sections ?? [];
        return $sections[$section] ?? true; // افتراضياً كل الأقسام مرئية
    }

    public function recordView(?int $viewerId = null, ?string $ip = null): void
    {
        ProfileView::create([
            'public_profile_id' => $this->id,
            'viewer_id'         => $viewerId,
            'viewer_ip'         => $ip,
            'viewed_at'         => now(),
        ]);

        $this->increment('total_views');
        $this->update(['last_viewed_at' => now()]);
    }

    public function getDefaultVisibleSections(): array
    {
        return [
            'contact_info'   => false, // مخفي للخصوصية
            'experience'     => true,
            'education'      => true,
            'skills'         => true,
            'projects'       => true,
            'certifications' => true,
            'reviews'        => false,
        ];
    }
}
