# Data Flow Diagram — URU Smart Mobile Backend

เอกสารนี้อ้างอิงจาก routes, controllers, services และ models ที่ใช้งานจริงในโปรเจกต์

## Level 0 — Context Diagram

```mermaid
flowchart LR
    U[ผู้ใช้งาน Mobile App]
    A[ผู้ดูแลระบบ]
    SSO[URU SSO]
    AI[Google Gemini API]
    EXPO[Expo Push Service]
    SYS((URU Smart Backend))

    U -->|ข้อมูลเข้าสู่ระบบ โปรไฟล์ ผลงาน คำค้น ข้อความแชต| SYS
    SYS -->|Token โปรไฟล์ ผลงาน ผลค้นหา การแจ้งเตือน คำตอบแชต| U

    A -->|เข้าสู่ระบบ จัดการผู้ใช้และประกาศ| SYS
    SYS -->|รายชื่อผู้ใช้ รายละเอียด และผลการดำเนินการ| A

    SYS -->|Credentials หรือ SSO access token| SSO
    SSO -->|ข้อมูลยืนยันตัวตนและข้อมูลบุคลากร| SYS

    SYS -->|Prompt และประวัติสนทนา| AI
    AI -->|คำตอบจาก AI| SYS

    SYS -->|Push message และ Expo push token| EXPO
    EXPO -->|ผลการส่งและสถานะ token| SYS
    EXPO -->|Push notification| U
```

## Level 1 — System Data Flow

```mermaid
flowchart LR
    U[ผู้ใช้งาน]
    A[ผู้ดูแลระบบ]
    SSO[URU SSO]
    AI[Gemini API]
    EXPO[Expo Push API]

    P1((1.0 Authentication))
    P2((2.0 Profile & Academic Portfolio))
    P3((3.0 Search & Reference Data))
    P4((4.0 Project Management & Files))
    P5((5.0 Notification))
    P6((6.0 AI Chatbot))
    P7((7.0 Admin Management))

    D1[(D1 Users & Sanctum Tokens)]
    D2[(D2 Profiles & Academic Works)]
    D3[(D3 Reference Data)]
    D4[(D4 Proposals Reports & Files)]
    D5[(D5 Announcements Notifications & Push Tokens)]
    FS[(D6 File Storage)]

    U -->|email/password หรือ SSO token| P1
    P1 <--> |ยืนยันตัวตน/ข้อมูลบุคลากร| SSO
    P1 <--> D1
    P1 -->|Sanctum token และข้อมูลผู้ใช้| U

    U -->|อ่าน/แก้ไขโปรไฟล์และผลงาน| P2
    P2 <--> D1
    P2 <--> D2
    P2 -->|โปรไฟล์ ผลงาน หรือ PDF| U

    U -->|คำค้นและตัวกรอง| P3
    P3 --> D1
    P3 --> D2
    P3 --> D3
    P3 -->|ผลค้นหาและตัวเลือกอ้างอิง| U

    U -->|ข้อเสนอ รายงาน และไฟล์แนบ| P4
    P4 <--> D4
    P4 <--> FS
    P4 -->|ข้อมูลโครงการและไฟล์| U

    U -->|push token การตั้งค่า และคำสั่งอ่าน/ลบ| P5
    P5 <--> D5
    P5 -->|ข้อความ push| EXPO
    EXPO -->|สถานะการส่ง/token ใช้ไม่ได้| P5
    EXPO -->|แจ้งเตือนบนอุปกรณ์| U
    P5 -->|รายการแจ้งเตือนและจำนวนยังไม่อ่าน| U

    U -->|ข้อความและประวัติสนทนา| P6
    P6 <--> |prompt/คำตอบ| AI
    P6 -->|คำตอบ chatbot| U

    A -->|จัดการผู้ใช้และประกาศ| P7
    P7 <--> D1
    P7 <--> D5
    P7 -->|ผลการจัดการ| A
    P7 -->|ประกาศที่เผยแพร่| P5
```

## Level 2 — Authentication Flow

```mermaid
sequenceDiagram
    actor User as ผู้ใช้งาน
    participant API as Laravel API
    participant SSO as URU SSO
    participant DB as MySQL

    User->>API: POST /api/auth/login หรือ /auth/sso-token
    API->>SSO: ตรวจสอบ credentials/access token
    SSO-->>API: ข้อมูลบัญชีและบุคลากร
    API->>DB: สร้างหรืออัปเดต users
    API->>DB: สร้าง Sanctum personal access token
    API-->>User: token + user profile
    User->>API: Request พร้อม Bearer token
    API->>DB: ตรวจสอบ token และผู้ใช้
    API-->>User: Protected resource
```

## Level 2 — Announcement & Notification Flow

```mermaid
sequenceDiagram
    actor Admin as ผู้ดูแลระบบ
    participant API as Admin/API
    participant OBS as AnnouncementObserver
    participant DB as MySQL
    participant PUSH as Expo Push API
    actor User as ผู้ใช้งาน

    Admin->>API: สร้าง/เผยแพร่ประกาศ
    API->>DB: บันทึก announcement
    API->>OBS: created/updated event
    OBS->>DB: อ่านผู้ใช้และ notification settings
    loop ผู้ใช้แต่ละราย
        OBS->>DB: สร้าง app_notification
        OBS->>DB: อ่าน active push tokens
        OBS->>PUSH: ส่งข้อความ push
        PUSH-->>OBS: ticket/status
    end
    PUSH-->>User: Push notification
    User->>API: GET /api/notifications
    API->>DB: อ่าน notification inbox
    API-->>User: รายการแจ้งเตือน
```

## Data Store Mapping

| Data store | ข้อมูลหลัก |
|---|---|
| D1 Users & Auth | `users`, `personal_access_tokens`, ข้อมูลบัญชีและสิทธิ์ admin |
| D2 Profile & Portfolio | education, research, journal, book, patent, award, academic, training, lecturer, proceeding, HSP, work experience, board experience, interest และ expertise |
| D3 Reference | prefixes, positions, lines, departments, degrees, research/journal types และตัวเลือกค้นหา |
| D4 Project & Files | proposals, reports, entity files และความเป็นเจ้าของข้อมูล |
| D5 Notification | announcements, app notifications, scheduled notifications, push tokens และ notification settings |
| D6 File Storage | รูปโปรไฟล์และไฟล์แนบที่จัดเก็บผ่าน Laravel Storage |

## Supporting / Demo Flow

`GET /db-connections-demo` เป็น flow สำหรับสาธิตการเชื่อมต่อหลายฐานข้อมูล โดยอ่านผู้ใช้จาก MySQL หลัก และอ่าน/เขียน `external_training_courses` ที่ MySQL connection `mysql_second` ไม่ใช่ flow หลักของ Mobile API

