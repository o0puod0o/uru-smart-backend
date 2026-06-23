<?php

namespace Database\Seeders;

use App\Models\Announcement;
use Illuminate\Database\Seeder;

class AnnouncementSeeder extends Seeder
{
    public function run(): void
    {
        Announcement::truncate();

        Announcement::insert([
            [
                'title'        => 'เปิดรับสมัครโครงการวิจัยใหม่',
                'message'      => 'ขอเชิญผู้สนใจสมัครเข้าร่วมโครงการวิจัยด้านเทคโนโลยีการศึกษา ภายในวันที่ 30 มิถุนายน 2569',
                'tag'          => 'ข่าวสาร',
                'icon'         => 'megaphone-outline',
                'is_published' => true,
                'published_at' => now()->subDays(1),
            ],
            [
                'title'        => 'ปรับปรุงระบบฐานข้อมูลนักวิจัย',
                'message'      => 'ระบบฐานข้อมูลจะทำการอัพเดตในวันศุกร์นี้ เวลา 22:00 น. โปรดเตรียมข้อมูลให้พร้อม',
                'tag'          => 'แจ้งเตือน',
                'icon'         => 'server-outline',
                'is_published' => true,
                'published_at' => now()->subDays(2),
            ],
            [
                'title'        => 'กิจกรรมสัมมนา URUSmart',
                'message'      => 'เข้าร่วมสัมมนาออนไลน์ฟรี หัวข้อ “นวัตกรรมงานวิจัยในยุคดิจิทัล” วันที่ 5 กรกฎาคม 2569',
                'tag'          => 'กิจกรรม',
                'icon'         => 'calendar-outline',
                'is_published' => true,
                'published_at' => now()->subDays(3),
            ],
            [
                'title'        => 'ประกาศผลการคัดเลือกผู้เชี่ยวชาญ',
                'message'      => 'ประกาศรายชื่อผู้ได้รับการคัดเลือกเป็นผู้เชี่ยวชาญประจำปี 2569 โปรดตรวจสอบในระบบ',
                'tag'          => 'ประกาศ',
                'icon'         => 'ribbon-outline',
                'is_published' => true,
                'published_at' => now()->subDays(4),
            ],
            [
                'title'        => 'บริการช่วยเหลือระบบ',
                'message'      => 'หากพบปัญหาในการใช้งาน สามารถติดต่อทีมซัพพอร์ตผ่านแชทหรืออีเมล support@urusmart.example',
                'tag'          => 'บริการ',
                'icon'         => 'help-circle-outline',
                'is_published' => true,
                'published_at' => now()->subDays(5),
            ],
        ]);
    }
}
