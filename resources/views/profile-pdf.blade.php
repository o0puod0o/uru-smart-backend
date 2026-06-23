<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>{{ $user['full_name_th'] ?? '' }} - ข้อมูลผู้บริหาร</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 16px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 15px; table-layout: auto; word-break: break-all; }
        th, td {
            border: 1px solid #333;
            padding: 6px 8px;
            word-break: break-word;
            overflow-wrap: break-word;
            white-space: normal;
        }
        th { background: #eee; font-weight: bold; }
        .panel-heading { font-weight: bold; margin-top: 15px; margin-bottom: 8px; font-size: 14px; color: #333; }
        .service-tag { display: inline-block; background: #e0e0e0; padding: 3px 8px; border-radius: 4px; margin: 2px 2px 2px 0; font-size: 13px; }
        .no-border-table, .no-border-table td, .no-border-table tr {
            border: none !important;
        }
        .no-border-table td {
            padding: 4px 8px;
        }
        h2 { text-align: center; margin-bottom: 20px; font-size: 18px; }
        .profile-info { margin-bottom: 20px; }
        img { max-width: 100%; height: auto; }
    </style>
</head>
<body>
    <h2>ระบบฐานข้อมูลผู้บริหาร มหาวิทยาลัยราชภัฏอุตรดิตถ์</h2>

    <!-- Profile Section -->
    <table class="no-border-table">
        <tr>
            <td rowspan="5" style="width: 100px; text-align: center;">
                @if($user['picture'] ?? false)
                    <img src="{{ $user['picture'] }}" width="100" style="border: 1px solid #ccc; padding: 4px;">
                @else
                    <div style="width: 100px; height: 130px; background: #f0f0f0; display: flex; align-items: center; justify-content: center; color: #999; border: 1px solid #ccc;">ไม่มีรูป</div>
                @endif
            </td>
            <td colspan="2" style="font-weight: bold; font-size: 16px;">{{ $user['full_name_th'] ?? '' }}</td>
        </tr>
        <tr>
            <td style="width: 100px;">ตำแหน่ง:</td>
            <td>{{ $user['position'] ?? '-' }}</td>
        </tr>
        <tr>
            <td>คณะ:</td>
            <td>{{ $user['faculty_name_th'] ?? '-' }}</td>
        </tr>
        <tr>
            <td>สาขา:</td>
            <td>{{ $user['department_name_th'] ?? '-' }}</td>
        </tr>
        <tr>
            <td>อีเมล:</td>
            <td>{{ $user['email'] ?? '-' }}</td>
        </tr>
        <tr>
            <td>โทรศัพท์:</td>
            <td>{{ $user['phone_work'] ?? '-' }}</td>
        </tr>
    </table>

    <!-- ความเชี่ยวชาญ -->
    @if(!empty($user['expertises']))
    <div class="panel-heading">ความเชี่ยวชาญ</div>
    @forelse($user['expertises'] as $expertise)
        <span class="service-tag">{{ $expertise['name'] ?? '' }}</span>
    @empty
        <span>-</span>
    @endforelse
    @endif

    <!-- ความสนใจ -->
    @if(!empty($user['interests']))
    <div class="panel-heading">ความสนใจ</div>
    @forelse($user['interests'] as $interest)
        <span class="service-tag">{{ $interest['name'] ?? '' }}</span>
    @empty
        <span>-</span>
    @endforelse
    @endif

    <!-- ประวัติการศึกษา -->
    @if(!empty($user['educations']))
    <div class="panel-heading">ประวัติการศึกษา</div>
    <table>
        <tr>
            <th style="width: 30px;">ที่</th>
            <th style="width: 100px;">ระดับการศึกษา</th>
            <th style="width: 200px;">สาขาวิชา</th>
            <th style="width: 150px;">สถาบัน</th>
            <th style="width: 50px;">ปีจบ</th>
        </tr>
        @foreach($user['educations'] as $idx=>$edu)
        <tr>
            <td>{{ $idx+1 }}</td>
            <td>{{ $edu['degree_name'] ?? '-' }}</td>
            <td>{{ $edu['field_of_study'] ?? '-' }}</td>
            <td>{{ $edu['institution'] ?? '-' }}</td>
            <td>{{ $edu['year'] ?? '-' }}</td>
        </tr>
        @endforeach
    </table>
    @endif

    <!-- ผลงานวิจัย -->
    @if(!empty($user['researches']))
    <div class="panel-heading">ผลงานวิจัย</div>
    <table>
        <tr>
            <th style="width: 30px;">ที่</th>
            <th style="width: 50px;">ปี</th>
            <th style="width: 300px;">ชื่องานวิจัย</th>
            <th style="width: 100px;">ประเภท</th>
            <th style="width: 80px;">ระดับ</th>
        </tr>
        @foreach($user['researches'] as $idx=>$research)
        <tr>
            <td>{{ $idx+1 }}</td>
            <td>{{ $research['year'] ?? '-' }}</td>
            <td>{{ $research['name'] ?? '-' }}</td>
            <td>{{ $research['research_type'] ?? '-' }}</td>
            <td>{{ $research['research_level'] ?? '-' }}</td>
        </tr>
        @endforeach
    </table>
    @endif

    <!-- บทความวิจัยในวารสาร -->
    @if(!empty($user['journals']))
    <div class="panel-heading">บทความวิจัย/วิชาการที่ตีพิมพ์ในวารสาร (Journal)</div>
    <table>
        <tr>
            <th style="width: 30px;">ที่</th>
            <th style="width: 50px;">ปี</th>
            <th style="width: 400px;">ชื่อบทความ</th>
        </tr>
        @foreach($user['journals'] as $idx=>$journal)
        <tr>
            <td>{{ $idx+1 }}</td>
            <td>{{ $journal['year'] ?? '-' }}</td>
            <td>{{ $journal['name'] ?? '-' }}</td>
        </tr>
        @endforeach
    </table>
    @endif

    <!-- บทความ Proceeding -->
    @if(!empty($user['proceedings']))
    <div class="panel-heading">บทความวิจัย/วิชาการนำเสนอในงานประชุมวิชาการ (Proceeding)</div>
    <table>
        <tr>
            <th style="width: 30px;">ที่</th>
            <th style="width: 50px;">ปี</th>
            <th style="width: 400px;">ชื่อบทความ</th>
        </tr>
        @foreach($user['proceedings'] as $idx=>$proc)
        <tr>
            <td>{{ $idx+1 }}</td>
            <td>{{ $proc['year'] ?? '-' }}</td>
            <td>{{ $proc['name'] ?? '-' }}</td>
        </tr>
        @endforeach
    </table>
    @endif

    <!-- หนังสือ -->
    @if(!empty($user['books']))
    <div class="panel-heading">หนังสือ</div>
    <table>
        <tr>
            <th style="width: 30px;">ที่</th>
            <th style="width: 50px;">ปี</th>
            <th style="width: 400px;">ชื่อหนังสือ</th>
        </tr>
        @foreach($user['books'] as $idx=>$book)
        <tr>
            <td>{{ $idx+1 }}</td>
            <td>{{ $book['year'] ?? '-' }}</td>
            <td>{{ $book['name'] ?? '-' }}</td>
        </tr>
        @endforeach
    </table>
    @endif

    <!-- สิทธิบัตร -->
    @if(!empty($user['patents']))
    <div class="panel-heading">สิทธิบัตร</div>
    <table>
        <tr>
            <th style="width: 30px;">ที่</th>
            <th style="width: 50px;">ปี</th>
            <th style="width: 380px;">ชื่อสิทธิบัตร</th>
            <th style="width: 80px;">เลขที่</th>
        </tr>
        @foreach($user['patents'] as $idx=>$patent)
        <tr>
            <td>{{ $idx+1 }}</td>
            <td>{{ $patent['year'] ?? '-' }}</td>
            <td>{{ $patent['name'] ?? '-' }}</td>
            <td>{{ $patent['link'] ?? '-' }}</td>
        </tr>
        @endforeach
    </table>
    @endif

    <!-- รางวัล -->
    @if(!empty($user['awards']))
    <div class="panel-heading">รางวัล</div>
    <table>
        <tr>
            <th style="width: 30px;">ที่</th>
            <th style="width: 50px;">ปี</th>
            <th style="width: 400px;">ชื่อรางวัล</th>
        </tr>
        @foreach($user['awards'] as $idx=>$award)
        <tr>
            <td>{{ $idx+1 }}</td>
            <td>{{ $award['year'] ?? '-' }}</td>
            <td>{{ $award['name'] ?? '-' }}</td>
        </tr>
        @endforeach
    </table>
    @endif

</body>
</html>
