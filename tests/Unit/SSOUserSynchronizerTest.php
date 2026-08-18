<?php

namespace Tests\Unit;

use App\Models\User;
use App\Services\SSOUserSynchronizer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SSOUserSynchronizerTest extends TestCase
{
    use RefreshDatabase;

    public function test_sync_accepts_real_expert_callback_payload(): void
    {
        $payload = [
            'data' => [
                'profile' => [
                    'users_id' => 304,
                    'id_card' => '3530900177802',
                    'expert_id' => 6,
                    'prefix' => 'นาย',
                    'th_firstname' => 'ภานุวัฒน์',
                    'th_lastname' => 'ขันจา',
                    'en_firstname' => 'Phanuwat',
                    'en_lastname' => 'Khanja',
                    'email' => 'mr.phanuwat@gmail.com',
                    'tel' => '-',
                    'mobile' => '0862004911',
                    'status_type' => 'T',
                    'picture' => '1772680602.jpg',
                    'picture_url' => 'https://expert.uru.ac.th/storage/user/pictures/1772680602.jpg',
                    'date_update' => '2026-06-22 09:28:36',
                    'sdname' => 'วิศวกรรมคอมพิวเตอร์',
                    'dname' => 'คณะเทคโนโลยีอุตสาหกรรม',
                    'unit_name' => 'คณะเทคโนโลยีอุตสาหกรรม',
                ],
                'expert' => [
                    'expert_id' => 6,
                    'name' => 'กลุ่มวิศวกรรมศาสตร์',
                ],
                'education' => [],
                'interests' => [],
                'research' => [],
            ],
        ];

        $user = app(SSOUserSynchronizer::class)->sync($payload);

        $this->assertInstanceOf(User::class, $user);
        $this->assertSame(304, $user->sso_id);
        $this->assertSame('304', $user->code);
        $this->assertSame('3530900177802', $user->username);
        $this->assertSame('3530900177802', $user->citizen_id);
        $this->assertSame('นาย', $user->prefix_th);
        $this->assertSame('ภานุวัฒน์', $user->first_name_th);
        $this->assertSame('ขันจา', $user->last_name_th);
        $this->assertSame('Phanuwat', $user->first_name_en);
        $this->assertSame('Khanja', $user->last_name_en);
        $this->assertSame('mr.phanuwat@gmail.com', $user->email);
        $this->assertSame('https://expert.uru.ac.th/storage/user/pictures/1772680602.jpg', $user->sso_picture);
        $this->assertSame('คณะเทคโนโลยีอุตสาหกรรม', $user->faculty_name_th);
        $this->assertSame('วิศวกรรมคอมพิวเตอร์', $user->department_name_th);
    }
}
