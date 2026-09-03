<?php

namespace App\Models;

use Laravel\Sanctum\HasApiTokens;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * User Model
 * Fields from SSO (read-only): id, code, username, citizen_id, passport_id, full_name, email, picture, etc.
 * Editable fields: prefix_th, birth_date, phone_work, phone_mobile, line_id, facebook, website, bio, profile_picture, address, position, branch, etc.
 * Key relationships: interests, researches, journals, books, patents, awards, lecturers, trainings, experts (with many-to-many pivot)
 */
class User extends Authenticatable
{
    use HasApiTokens, HasFactory;

    protected $fillable = [
        'sso_id', 'code', 'username', 'citizen_id', 'passport_id',
        'type', 'degree', 'status',
        'prefix_th', 'first_name_th', 'last_name_th',
        'prefix_en', 'first_name_en', 'last_name_en', 'nickname',
        'gender', 'birth_date', 'nationality', 'email', 'picture',
        'faculty_id', 'faculty_name_th', 'faculty_name_en',
        'department_id', 'department_name_th', 'department_name_en', 'sub_dep_id',
        'campus_id', 'curriculum_id', 'study_year',
        'custom1', 'custom2', 'custom3',
        'sso_last_updated_at',
        'phone_work', 'phone_mobile', 'line_id',
        'facebook', 'website', 'bio', 'profile_picture', 'sso_picture',
        'address', 'moo', 'road', 'tambon',
        'amphoe', 'province', 'zipcode',
        'position', 'branch', 'push_token', 'role', 'lrd_researcher_id',
    ];

    protected $casts = [
        'birth_date'          => 'date',
        'sso_last_updated_at' => 'datetime',
    ];

    public function getFullNameThAttribute(): string
    {
        return trim("{$this->prefix_th}{$this->first_name_th} {$this->last_name_th}");
    }

    public function getFullNameEnAttribute(): string
    {
        return trim("{$this->prefix_en} {$this->first_name_en} {$this->last_name_en}");
    }

    // ===== Relationships =====
    // Used in: GET /api/profile/{id} endpoint (public profile)

    public function educations()
    {
        return $this->hasMany(Education::class, 'id_card', 'citizen_id');
    }

    public function interests()
    {
        return $this->hasMany(HasInterest::class, 'id_card', 'citizen_id');
    }

    public function researches()
    {
        return $this->hasMany(HasResearch::class, 'id_card', 'citizen_id');
    }

    public function journals()
    {
        return $this->hasMany(HasJournal::class, 'id_card', 'citizen_id');
    }

    public function books()
    {
        return $this->hasMany(HasBook::class, 'user_id', 'id');
    }

    public function patents()
    {
        return $this->hasMany(HasPatent::class, 'user_id', 'id');
    }

    public function awards()
    {
        return $this->hasMany(HasAward::class, 'user_id', 'id');
    }

    public function academics()
    {
        return $this->hasMany(HasAcademic::class, 'user_id', 'id');
    }

    public function trainings()
    {
        return $this->hasMany(HasTraining::class, 'user_id', 'id');
    }

    public function lecturers()
    {
        return $this->hasMany(HasLecturer::class, 'user_id', 'id');
    }

    public function proceedings()
    {
        return $this->hasMany(HasProceeding::class, 'user_id', 'id');
    }

    public function hsps()
    {
        return $this->hasMany(HasHsp::class, 'user_id', 'id');
    }

    public function workexes()
    {
        return $this->hasMany(Workex::class, 'id_card', 'citizen_id');
    }

    public function boardexes()
    {
        return $this->hasMany(Boardex::class, 'user_id', 'id');
    }

    // Many-to-Many: user มี multiple expertise groups (via pivot table users_expert)
    // ใช้ส่วน profileOptions dropdown ของ frontend
    public function experts()
    {
        return $this->belongsToMany(
        HasExpert::class, 'users_expert',
        'user_id', 'expert_id'
        );
    }

    // Belongs To: reference to departments table
    public function department()
    {
        return $this->belongsTo(Department::class, 'department_id', 'dep_id');
    }

    public function subDepartment()
    {
        return $this->belongsTo(SubDepartment::class, 'sub_dep_id', 'sub_dep_id');
    }

    public function pushTokens()
    {
        return $this->hasMany(PushToken::class);
    }

    public function notificationSetting()
    {
        return $this->hasOne(NotificationSetting::class);
    }

    public function notifications()
    {
        return $this->hasMany(AppNotification::class);
    }

    public function chatbotHistories()
    {
        return $this->hasMany(ChatbotHistory::class);
    }

    public function chatConversations()
    {
        return $this->hasMany(ChatConversation::class);
    }

    public function getDisplayPictureAttribute()
    {
        return $this->picture ?: $this->sso_picture;
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }
}
