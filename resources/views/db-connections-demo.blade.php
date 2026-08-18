<!doctype html>
<html lang="th">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Laravel ใช้ 2 ฐานข้อมูล</title>
    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f5f7fb;
            color: #1f2937;
        }

        main {
            max-width: 1120px;
            margin: 32px auto;
            padding: 0 20px 40px;
        }

        .hero {
            background: #ffffff;
            border: 1px solid #dbe3ef;
            border-radius: 8px;
            padding: 24px;
            margin-bottom: 18px;
        }

        h1 {
            margin: 0 0 10px;
            font-size: 30px;
        }

        .lead {
            margin: 0;
            font-size: 18px;
            line-height: 1.6;
            color: #475569;
        }

        .flow {
            display: grid;
            grid-template-columns: 1fr auto 1fr;
            gap: 14px;
            align-items: stretch;
            margin: 18px 0;
        }

        .panel {
            background: #ffffff;
            border: 2px solid #dbe3ef;
            border-radius: 8px;
            overflow: hidden;
        }

        .panel.primary {
            border-color: #2563eb;
        }

        .panel.second {
            border-color: #16a34a;
        }

        .panel-head {
            padding: 16px 18px;
            color: #ffffff;
        }

        .primary .panel-head {
            background: #2563eb;
        }

        .second .panel-head {
            background: #16a34a;
        }

        .panel-title {
            margin: 0 0 6px;
            font-size: 22px;
        }

        .panel-meta {
            margin: 0;
            font-size: 14px;
            opacity: .95;
        }

        .connector {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0 8px;
            color: #334155;
            font-weight: 700;
            text-align: center;
        }

        .table-wrap {
            padding: 16px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 11px 10px;
            border-bottom: 1px solid #e5e7eb;
            text-align: left;
            vertical-align: top;
        }

        th {
            color: #475569;
            font-size: 14px;
            background: #f8fafc;
        }

        tr:last-child td {
            border-bottom: 0;
        }

        .explain {
            background: #fff7ed;
            border: 1px solid #fed7aa;
            border-radius: 8px;
            padding: 18px;
            line-height: 1.7;
        }

        code {
            background: #eef2ff;
            border-radius: 4px;
            padding: 2px 6px;
            color: #3730a3;
        }

        @media (max-width: 760px) {
            .flow {
                grid-template-columns: 1fr;
            }

            .connector {
                padding: 8px 0;
            }
        }
    </style>
</head>
<body>
    <main>
        <section class="hero">
            <h1>ตัวอย่างหน้าเว็บที่ดึงข้อมูลจาก 2 ฐานข้อมูล</h1>
            <p class="lead">
                หน้าเดียวกันนี้ดึง “ผู้ใช้” จากฐานหลัก และดึง “คอร์สตัวอย่าง” จากฐานที่สอง
                เพื่อให้เห็นชัดว่าข้อมูลมาจากคนละ database จริง
            </p>
        </section>

        <section class="flow">
            <div class="panel primary">
                <div class="panel-head">
                    <h2 class="panel-title">ฐานข้อมูลหลักของระบบ</h2>
                    <p class="panel-meta">Connection: <code>mysql</code> | Database: <code>{{ $primaryDatabase }}</code> | Table: <code>users</code></p>
                </div>

                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Username</th>
                                <th>Email</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($primaryUsers as $user)
                                <tr>
                                    <td>{{ $user->id }}</td>
                                    <td>{{ $user->username }}</td>
                                    <td>{{ $user->email }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3">ยังไม่มีข้อมูลผู้ใช้ในฐานหลัก</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="connector">
                Laravel<br>รวมข้อมูลมาแสดง<br>ในหน้าเดียว
            </div>

            <div class="panel second">
                <div class="panel-head">
                    <h2 class="panel-title">ฐานข้อมูลที่สอง</h2>
                    <p class="panel-meta">Connection: <code>mysql_second</code> | Database: <code>{{ $secondDatabase }}</code> | Table: <code>external_training_courses</code></p>
                </div>

                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Code</th>
                                <th>Course Name</th>
                                <th>Owner</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($secondCourses as $course)
                                <tr>
                                    <td>{{ $course->course_code }}</td>
                                    <td>{{ $course->course_name }}</td>
                                    <td>{{ $course->owner_system }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

        <section class="explain">
            <strong>สรุป:</strong>
            ข้อมูลฝั่งซ้ายมาจากฐาน <code>{{ $primaryDatabase }}</code>
            แต่ข้อมูลฝั่งขวามาจากฐาน <code>{{ $secondDatabase }}</code>
            โดย Laravel ใช้ <code>DB::connection()</code> เลือกว่าจะอ่านจากฐานไหน แล้วส่งข้อมูลทั้งสองชุดมาแสดงใน Blade view เดียวกัน
        </section>
    </main>
</body>
</html>
