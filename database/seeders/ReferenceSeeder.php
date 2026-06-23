<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ReferenceSeeder extends Seeder
{
    public function run(): void
    {
        // Prefixes
        DB::table('prefixes')->insertOrIgnore([
            ['code' => 'นาย', 'name' => 'นาย'],
            ['code' => 'นาง', 'name' => 'นาง'],
            ['code' => 'นางสาว', 'name' => 'นางสาว'],
            ['code' => 'ดร.', 'name' => 'ดร.'],
            ['code' => 'ผศ.', 'name' => 'ผศ.'],
            ['code' => 'รศ.', 'name' => 'รศ.'],
            ['code' => 'ศ.', 'name' => 'ศ.'],
        ]);

        // Lines
        DB::table('lines')->insertOrIgnore([
            ['code' => 'สายวิชาการ', 'name' => 'สายวิชาการ'],
            ['code' => 'สายบริหาร', 'name' => 'สายบริหาร'],
            ['code' => 'สายสนับสนุน', 'name' => 'สายสนับสนุน'],
        ]);

        // Degrees
        DB::table('degrees')->insertOrIgnore([
            ['id' => 1, 'name' => 'ต่ำกว่าปริญญาตรี', 'date_add' => '2020-01-23'],
            ['id' => 2, 'name' => 'ปริญญาตรี',        'date_add' => '2020-01-23'],
            ['id' => 3, 'name' => 'ปริญญาโท',         'date_add' => '2020-01-23'],
            ['id' => 4, 'name' => 'ปริญญาเอก',        'date_add' => '2020-01-23'],
        ]);

        // Journal Types
        DB::table('journal_types')->insertOrIgnore([
            ['journal_type_id' => 1, 'name' => 'SCOPUS', 'orders' => 3, 'date_add' => now()],
            ['journal_type_id' => 2, 'name' => 'ISI',    'orders' => 2, 'date_add' => now()],
            ['journal_type_id' => 3, 'name' => '-',      'orders' => 8, 'date_add' => now()],
            ['journal_type_id' => 4, 'name' => 'etc.',   'orders' => 9, 'date_add' => now()],
            ['journal_type_id' => 5, 'name' => 'TCI1',   'orders' => 6, 'date_add' => now()],
            ['journal_type_id' => 6, 'name' => 'TCI2',   'orders' => 7, 'date_add' => now()],
            ['journal_type_id' => 7, 'name' => 'ISI-Q1', 'orders' => 1, 'date_add' => now()],
            ['journal_type_id' => 8, 'name' => 'ISI-Q2', 'orders' => 4, 'date_add' => now()],
            ['journal_type_id' => 9, 'name' => 'ISI-Q3', 'orders' => 5, 'date_add' => now()],
        ]);

        // Research Types
        DB::table('research_types')->insertOrIgnore([
            ['research_type_id' => 1, 'name' => 'งานวิจัย',                    'orders' => 1, 'date_add' => now()],
            ['research_type_id' => 2, 'name' => 'งานสร้างสรรค์',               'orders' => 2, 'date_add' => now()],
            ['research_type_id' => 3, 'name' => 'บทความวิจัย',                 'orders' => 3, 'date_add' => now()],
            ['research_type_id' => 4, 'name' => 'บทความวิชาการ',               'orders' => 4, 'date_add' => now()],
        ]);

        // Research Levels
        DB::table('research_levels')->insertOrIgnore([
            ['research_level_id' => 1, 'name' => 'ระดับชาติ',       'date_add' => now()],
            ['research_level_id' => 2, 'name' => 'ระดับนานาชาติ',   'date_add' => now()],
            ['research_level_id' => 3, 'name' => 'ระดับท้องถิ่น',   'date_add' => now()],
        ]);

        // Research PMU Types
        DB::table('research_pmu_types')->insertOrIgnore([
            ['research_PMU_type_id' => 1, 'name' => 'ไม่ระบุ',  'date_add' => now()],
            ['research_PMU_type_id' => 2, 'name' => 'PMU-A',    'date_add' => now()],
            ['research_PMU_type_id' => 3, 'name' => 'PMU-B',    'date_add' => now()],
            ['research_PMU_type_id' => 4, 'name' => 'PMU-C',    'date_add' => now()],
        ]);

        // Position Types (เหลือแค่ 2 ตัว)
        DB::table('position_types')->insertOrIgnore([
            ['id' => 1, 'name' => 'ตำแหน่งวิชาการ',     'date_add' => now()],
            ['id' => 2, 'name' => 'ตำแหน่งสนับสนุน',    'date_add' => now()],
        ]);

        // Positions - seeded by PositionsSeeder
        $this->call(PositionsSeeder::class);

        // Expertise groups
        DB::table('has_experts')->insertOrIgnore([
            ['name' => 'กลุ่มครุศาสตร์ ศึกษาศาสตร์พลศึกษา และพลศึกษา', 'date_add' => now()],
            ['name' => 'กลุ่มบริหาร พาณิชย์ศาสตร์ การบัญชี การท่องเที่ยวและโรงแรม เศรษฐศาสตร์', 'date_add' => now()],
            ['name' => 'กลุ่มมนุษยศาสตร์และสังคมศาสตร์', 'date_add' => now()],
            ['name' => 'กลุ่มวิชาวิทยาศาสตร์กายภาพและชีวภาพ', 'date_add' => now()],
            ['name' => 'กลุ่มวิทยาศาสตร์สุขภาพ', 'date_add' => now()],
            ['name' => 'กลุ่มวิศวกรรมศาสตร์', 'date_add' => now()],
            ['name' => 'กลุ่มศิลปกรรมศาสตร์', 'date_add' => now()],
            ['name' => 'กลุ่มสถาปัตยกรรมศาสตร์', 'date_add' => now()],
            ['name' => 'กลุ่มเกษตรศาสตร์', 'date_add' => now()],
        ]);

        // Departments / หน่วยงานหลัก
        DB::table('departments')->insertOrIgnore([
            ['dep_id' => 1,  'name' => 'คณะครุศาสตร์',                           'sort' => null, 'name_en' => null],
            ['dep_id' => 2,  'name' => 'คณะวิทยาศาสตร์และเทคโนโลยี',            'sort' => null, 'name_en' => null],
            ['dep_id' => 3,  'name' => 'คณะวิทยาการจัดการ',                      'sort' => null, 'name_en' => null],
            ['dep_id' => 4,  'name' => 'คณะมนุษยศาสตร์และสังคมศาสตร์',          'sort' => null, 'name_en' => null],
            ['dep_id' => 5,  'name' => 'คณะพยาบาลศาสตร์',                        'sort' => null, 'name_en' => null],
            ['dep_id' => 6,  'name' => 'วิทยาลัยนานาชาติ',                       'sort' => null, 'name_en' => null],
            ['dep_id' => 7,  'name' => 'วิทยาลัยน่าน',                           'sort' => null, 'name_en' => null],
            ['dep_id' => 8,  'name' => 'สำนักวิทยบริการและเทคโนโลยีสารสนเทศ',  'sort' => null, 'name_en' => null],
            ['dep_id' => 9,  'name' => 'สำนักงานอธิการบดี',                      'sort' => null, 'name_en' => null],
            ['dep_id' => 10, 'name' => 'สำนักงานเลขานุการ',                      'sort' => null, 'name_en' => null],
            ['dep_id' => 11, 'name' => 'สำนักงานตรวจสอบภายใน',                  'sort' => null, 'name_en' => null],
            ['dep_id' => 12, 'name' => 'ศูนย์วิทยบริการจังหวัดแพร่',            'sort' => null, 'name_en' => null],
            ['dep_id' => 13, 'name' => 'สถาบันวิจัยและพัฒนา',                   'sort' => null, 'name_en' => null],
            ['dep_id' => 14, 'name' => 'โรงเรียนสาธิตมหาวิทยาลัยราชภัฎ',       'sort' => null, 'name_en' => null],
            ['dep_id' => 15, 'name' => 'โรงพิมพ์มหาวิทยาลัย',                   'sort' => null, 'name_en' => null],
        ]);

        // Sub departments / สังกัดย่อย
        if (DB::table('sub_departments')->count() === 0) {
            DB::table('sub_departments')->insert([
                ['dep_id' => 1,  'name' => 'ภาควิชาพัฒนาชุมชน',                        'name_en' => null],
                ['dep_id' => 1,  'name' => 'ภาควิชาวิทยาศาสตร์ทั่วไป',                'name_en' => null],
                ['dep_id' => 1,  'name' => 'ภาควิชาการศึกษาปฐมวัย',                    'name_en' => null],
                ['dep_id' => 2,  'name' => 'ภาควิชาคณิตศาสตร์',                        'name_en' => null],
                ['dep_id' => 2,  'name' => 'ภาควิชาเคมี',                               'name_en' => null],
                ['dep_id' => 2,  'name' => 'ภาควิชาวิทยาการคอมพิวเตอร์',               'name_en' => null],
                ['dep_id' => 3,  'name' => 'ภาควิชาการบัญชี',                           'name_en' => null],
                ['dep_id' => 3,  'name' => 'ภาควิชาการจัดการ',                         'name_en' => null],
                ['dep_id' => 3,  'name' => 'ภาควิชาการตลาด',                            'name_en' => null],
                ['dep_id' => 4,  'name' => 'ภาควิชาภาษาไทย',                           'name_en' => null],
                ['dep_id' => 4,  'name' => 'ภาควิชาภาษาอังกฤษ',                        'name_en' => null],
                ['dep_id' => 4,  'name' => 'ภาควิชารัฐศาสตร์และนิติศาสตร์',            'name_en' => null],
                ['dep_id' => 5,  'name' => 'ภาควิชาพยาบาลศาสตร์ทั่วไป',                 'name_en' => null],
                ['dep_id' => 5,  'name' => 'ภาควิชาการพยาบาลชุมชน',                    'name_en' => null],
                ['dep_id' => 6,  'name' => 'โปรแกรมนานาชาติ',                          'name_en' => null],
                ['dep_id' => 6,  'name' => 'โปรแกรมภาษาอังกฤษ',                        'name_en' => null],
                ['dep_id' => 7,  'name' => 'สาขาวิชาการจัดการทรัพยากรธรรมชาติ',      'name_en' => null],
                ['dep_id' => 7,  'name' => 'สาขาวิชาการจัดการการท่องเที่ยว',         'name_en' => null],
                ['dep_id' => 8,  'name' => 'ฝ่ายบริการสารสนเทศ',                        'name_en' => null],
                ['dep_id' => 8,  'name' => 'ฝ่ายเทคโนโลยีสารสนเทศ',                    'name_en' => null],
                ['dep_id' => 9,  'name' => 'ฝ่ายนโยบายและแผน',                        'name_en' => null],
                ['dep_id' => 9,  'name' => 'ฝ่ายประสานงานวิชาการ',                    'name_en' => null],
                ['dep_id' => 10, 'name' => 'ฝ่ายธุรการ',                                'name_en' => null],
                ['dep_id' => 10, 'name' => 'ฝ่ายบุคลากร',                              'name_en' => null],
                ['dep_id' => 11, 'name' => 'ฝ่ายตรวจสอบภายใน',                        'name_en' => null],
                ['dep_id' => 12, 'name' => 'ฝ่ายบริการห้องสมุด',                      'name_en' => null],
                ['dep_id' => 12, 'name' => 'ฝ่ายเครือข่ายและระบบสารสนเทศ',           'name_en' => null],
                ['dep_id' => 13, 'name' => 'ฝ่ายส่งเสริมการวิจัย',                    'name_en' => null],
                ['dep_id' => 13, 'name' => 'ฝ่ายนวัตกรรมและถ่ายทอดเทคโนโลยี',      'name_en' => null],
                ['dep_id' => 14, 'name' => 'ฝ่ายบริหารโรงเรียนสาธิต',                'name_en' => null],
                ['dep_id' => 14, 'name' => 'ฝ่ายวิชาการโรงเรียนสาธิต',                'name_en' => null],
                ['dep_id' => 15, 'name' => 'ฝ่ายผลิตสื่อและสิ่งพิมพ์',                'name_en' => null],
            ]);
        }
    }
}
