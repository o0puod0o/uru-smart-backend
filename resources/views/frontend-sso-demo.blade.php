<!doctype html>
<html lang="th">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Frontend SSO + Multiple Database Demo</title>
    <style>
        :root {
            --bg: #f4f7fb;
            --card: #ffffff;
            --ink: #172033;
            --muted: #667085;
            --primary: #2563eb;
            --expert: #7c3aed;
            --lrd: #059669;
            --danger: #dc2626;
            --line: #e5e7eb;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            background: linear-gradient(135deg, #eef4ff 0%, #f8fafc 42%, #ecfdf5 100%);
            color: var(--ink);
        }

        .page {
            max-width: 1180px;
            margin: 0 auto;
            padding: 32px 18px 48px;
        }

        .hero {
            display: grid;
            grid-template-columns: minmax(0, 1.4fr) minmax(320px, .8fr);
            gap: 20px;
            align-items: stretch;
        }

        .panel {
            background: rgba(255, 255, 255, .88);
            border: 1px solid rgba(229, 231, 235, .9);
            border-radius: 22px;
            box-shadow: 0 18px 50px rgba(15, 23, 42, .08);
            padding: 24px;
        }

        h1 {
            margin: 0 0 10px;
            font-size: clamp(28px, 4vw, 46px);
            line-height: 1.08;
        }

        h2 {
            margin: 0 0 14px;
            font-size: 20px;
        }

        h3 {
            margin: 0 0 10px;
            font-size: 16px;
        }

        p {
            color: var(--muted);
            line-height: 1.7;
        }

        .flow {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 12px;
            margin-top: 24px;
        }

        .step {
            border: 1px solid var(--line);
            border-radius: 16px;
            padding: 14px;
            background: #fff;
        }

        .step b {
            display: inline-flex;
            width: 28px;
            height: 28px;
            border-radius: 999px;
            align-items: center;
            justify-content: center;
            background: #dbeafe;
            color: #1d4ed8;
            margin-right: 8px;
        }

        label {
            display: block;
            margin: 12px 0 6px;
            font-weight: 700;
            font-size: 14px;
        }

        input {
            width: 100%;
            border: 1px solid #cbd5e1;
            border-radius: 12px;
            padding: 12px 14px;
            font-size: 15px;
            outline: none;
        }

        input:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(37, 99, 235, .12);
        }

        button {
            width: 100%;
            margin-top: 16px;
            border: 0;
            border-radius: 14px;
            padding: 13px 16px;
            color: #fff;
            background: var(--primary);
            font-weight: 800;
            cursor: pointer;
            font-size: 15px;
        }

        button:disabled {
            opacity: .55;
            cursor: wait;
        }

        .status {
            margin-top: 14px;
            padding: 12px 14px;
            border-radius: 14px;
            background: #eff6ff;
            color: #1e40af;
            font-size: 14px;
            word-break: break-word;
        }

        .status.error {
            background: #fef2f2;
            color: var(--danger);
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 18px;
            margin-top: 20px;
        }

        .source-card {
            background: var(--card);
            border: 1px solid var(--line);
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 14px 35px rgba(15, 23, 42, .06);
        }

        .source-head {
            padding: 16px 18px;
            color: #fff;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 10px;
        }

        .source-head.expert {
            background: linear-gradient(135deg, var(--expert), #a855f7);
        }

        .source-head.lrd {
            background: linear-gradient(135deg, var(--lrd), #10b981);
        }

        .badge {
            border: 1px solid rgba(255,255,255,.5);
            border-radius: 999px;
            padding: 4px 10px;
            font-size: 12px;
            white-space: nowrap;
        }

        .source-body {
            padding: 18px;
        }

        .kv {
            display: grid;
            grid-template-columns: 150px minmax(0, 1fr);
            gap: 8px 12px;
            margin-bottom: 16px;
            font-size: 14px;
        }

        .kv span:nth-child(odd) {
            color: var(--muted);
        }

        .list {
            display: grid;
            gap: 8px;
        }

        .row {
            border: 1px solid var(--line);
            border-radius: 12px;
            padding: 10px 12px;
            background: #fafafa;
            font-size: 14px;
        }

        .muted {
            color: var(--muted);
        }

        pre {
            max-height: 340px;
            overflow: auto;
            background: #0f172a;
            color: #dbeafe;
            border-radius: 16px;
            padding: 16px;
            font-size: 12px;
            line-height: 1.6;
        }

        .hidden {
            display: none;
        }

        @media (max-width: 840px) {
            .hero,
            .grid,
            .flow {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
<main class="page">
    <section class="hero">
        <div class="panel">
            <h1>จำลอง Frontend หลัง Login SSO</h1>
            <p>
                หน้านี้เป็นตัวอย่าง frontend จริงใน browser: login ผ่าน <b>/api/auth/login</b>,
                เก็บ token แล้วใช้ token เรียก <b>/api/me/external-profile</b>
                เพื่อแสดงข้อมูลที่มาจากคนละ database ให้เห็นชัด ๆ
            </p>

            <div class="flow">
                <div class="step"><b>1</b> Login จาก expert2.users</div>
                <div class="step"><b>2</b> Backend ออก Bearer token</div>
                <div class="step"><b>3</b> Token ดึง expert2 + lrdsystem2</div>
            </div>
        </div>

        <form class="panel" id="loginForm">
            <h2>Frontend Login Form</h2>
            <label for="email">Username / Email / ID Card</label>
            <input id="email" name="email" value="3530900177802" autocomplete="username">

            <label for="password">Password</label>
            <input id="password" name="password" value="password" type="password" autocomplete="current-password">

            <button id="loginButton" type="submit">Login แล้วโหลดข้อมูล 2 Database</button>
            <div id="status" class="status">พร้อมทดสอบ frontend flow</div>
        </form>
    </section>

    <section id="result" class="hidden">
        <div class="panel" style="margin-top: 20px;">
            <h2>Token ที่ frontend ได้หลัง login</h2>
            <p class="muted">frontend จะเก็บ token นี้ไว้ แล้วส่งใน header <b>Authorization: Bearer &lt;token&gt;</b></p>
            <pre id="tokenBox"></pre>
        </div>

        <div class="grid">
            <article class="source-card">
                <div class="source-head expert">
                    <strong>Database: expert2</strong>
                    <span class="badge">connection: expert</span>
                </div>
                <div class="source-body">
                    <h3>Profile / Portfolio</h3>
                    <div class="kv" id="expertProfile"></div>

                    <h3>Education</h3>
                    <div class="list" id="educationList"></div>

                    <h3 style="margin-top: 18px;">Research จาก expert2</h3>
                    <div class="list" id="expertResearchList"></div>
                </div>
            </article>

            <article class="source-card">
                <div class="source-head lrd">
                    <strong>Database: lrdsystem2</strong>
                    <span class="badge">connection: lrd</span>
                </div>
                <div class="source-body">
                    <h3>Researcher Mapping</h3>
                    <div class="kv" id="lrdResearcher"></div>

                    <h3>Projects</h3>
                    <div class="list" id="projectList"></div>

                    <h3 style="margin-top: 18px;">Researchs จาก lrdsystem2</h3>
                    <div class="list" id="lrdResearchList"></div>
                </div>
            </article>
        </div>

        <div class="panel" style="margin-top: 20px;">
            <h2>Raw API Response</h2>
            <pre id="rawJson"></pre>
        </div>
    </section>
</main>

<script>
    const form = document.getElementById('loginForm');
    const button = document.getElementById('loginButton');
    const statusBox = document.getElementById('status');
    const result = document.getElementById('result');

    function setStatus(message, isError = false) {
        statusBox.textContent = message;
        statusBox.classList.toggle('error', isError);
    }

    function text(value) {
        return value === null || value === undefined || value === '' ? '-' : String(value);
    }

    function kv(targetId, pairs) {
        const target = document.getElementById(targetId);
        target.innerHTML = pairs.map(([label, value]) => `
            <span>${label}</span>
            <strong>${text(value)}</strong>
        `).join('');
    }

    function rows(targetId, rows, titleKeys) {
        const target = document.getElementById(targetId);

        if (!rows || rows.length === 0) {
            target.innerHTML = '<div class="row muted">ไม่มีข้อมูลในชุดนี้</div>';
            return;
        }

        target.innerHTML = rows.slice(0, 5).map((row) => {
            const title = titleKeys.map((key) => row[key]).find(Boolean) || `#${row.id ?? '-'}`;
            const sub = row.year || row.date || row.date_update || row.created_at || '';

            return `
                <div class="row">
                    <strong>${text(title)}</strong>
                    <div class="muted">${text(sub)}</div>
                </div>
            `;
        }).join('');
    }

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        button.disabled = true;
        result.classList.add('hidden');
        setStatus('กำลัง login ผ่าน /api/auth/login ...');

        try {
            const loginResponse = await fetch('/api/auth/login', {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    email: document.getElementById('email').value,
                    password: document.getElementById('password').value,
                }),
            });

            const loginJson = await loginResponse.json();

            if (!loginResponse.ok) {
                throw new Error(loginJson.message || 'Login failed');
            }

            setStatus('login สำเร็จ ได้ token แล้ว กำลังเรียก /api/me/external-profile ...');

            const profileResponse = await fetch('/api/me/external-profile', {
                headers: {
                    'Accept': 'application/json',
                    'Authorization': `Bearer ${loginJson.token}`,
                },
            });

            const profileJson = await profileResponse.json();

            if (!profileResponse.ok) {
                throw new Error(profileJson.message || 'Load external profile failed');
            }

            const data = profileJson.data;
            const expert = data.expert2;
            const lrd = data.lrdsystem2;

            document.getElementById('tokenBox').textContent = JSON.stringify({
                token_sample: `${loginJson.token.slice(0, 22)}...`,
                user: loginJson.user,
            }, null, 2);

            kv('expertProfile', [
                ['users_id', expert.profile?.users_id],
                ['username', expert.profile?.username],
                ['id_card', expert.profile?.id_card],
                ['ชื่อ', `${text(expert.profile?.prefix)} ${text(expert.profile?.th_firstname)} ${text(expert.profile?.th_lastname)}`],
                ['email', expert.profile?.email],
                ['department', expert.profile?.department_name],
                ['sub_department', expert.profile?.sub_department_name],
            ]);

            kv('lrdResearcher', [
                ['researcher_id', lrd.researcher?.id],
                ['codeuser', lrd.researcher?.codeuser],
                ['username', lrd.researcher?.username],
                ['idcard', lrd.researcher?.idcard],
                ['ชื่อ', `${text(lrd.researcher?.firstname)} ${text(lrd.researcher?.lastname)}`],
                ['key ที่ match', `${text(expert.profile?.id_card)} → ${text(lrd.researcher?.codeuser || lrd.researcher?.idcard || lrd.researcher?.username)}`],
            ]);

            rows('educationList', expert.education, ['degree_name', 'degree', 'level', 'educate']);
            rows('expertResearchList', expert.research, ['research_name', 'name', 'title', 'topic']);
            rows('projectList', lrd.projects, ['name', 'project_name', 'title']);
            rows('lrdResearchList', lrd.researchs, ['name', 'research_name', 'title']);

            document.getElementById('rawJson').textContent = JSON.stringify(profileJson, null, 2);
            result.classList.remove('hidden');
            setStatus('สำเร็จ: frontend ใช้ token ดึงข้อมูลจาก expert2 และ lrdsystem2 ได้แล้ว');
        } catch (error) {
            setStatus(error.message, true);
        } finally {
            button.disabled = false;
        }
    });
</script>
</body>
</html>
