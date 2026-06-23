<?php

namespace Tests\Unit;

use App\Models\User;
use Tests\TestCase;

class UserTest extends TestCase
{
    public function test_full_name_th_with_prefix(): void
    {
        $user = new User([
            'prefix_th'     => 'ดร.',
            'first_name_th' => 'สมชาย',
            'last_name_th'  => 'ใจดี',
        ]);

        $this->assertSame('ดร.สมชาย ใจดี', $user->full_name_th);
    }

    public function test_full_name_th_without_prefix(): void
    {
        $user = new User([
            'prefix_th'     => null,
            'first_name_th' => 'สมชาย',
            'last_name_th'  => 'ใจดี',
        ]);

        $this->assertSame('สมชาย ใจดี', $user->full_name_th);
    }

    public function test_full_name_en_with_prefix(): void
    {
        $user = new User([
            'prefix_en'    => 'Dr.',
            'first_name_en' => 'Somchai',
            'last_name_en'  => 'Jaidee',
        ]);

        $this->assertSame('Dr. Somchai Jaidee', $user->full_name_en);
    }

    public function test_full_name_en_without_prefix(): void
    {
        $user = new User([
            'prefix_en'    => null,
            'first_name_en' => 'Somchai',
            'last_name_en'  => 'Jaidee',
        ]);

        $this->assertSame('Somchai Jaidee', $user->full_name_en);
    }
}
