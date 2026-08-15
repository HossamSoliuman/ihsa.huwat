<style>
    :root {
        --background: 210 40% 98%;
        --foreground: 222 32% 14%;
        --card: 0 0% 100%;
        --primary: 199 89% 28%;
        --primary-foreground: 0 0% 100%;
        --muted: 210 30% 95%;
        --muted-foreground: 215 16% 47%;
        --accent: 199 85% 90%;
        --accent-foreground: 199 89% 22%;
        --destructive: 0 72% 51%;
        --border: 214 25% 88%;
        --amber-bg: 48 96% 96%;
        --amber-border: 45 93% 82%;
        --amber-fg: 26 90% 30%;
        --ok-bg: 152 76% 94%;
        --ok-fg: 152 69% 28%;
        --warn-bg: 45 96% 92%;
        --warn-fg: 32 81% 32%;
        --danger-bg: 0 86% 96%;
        --danger-fg: 0 72% 42%;
        --radius: 0.5rem;
        --font-body: 'Tajawal', ui-sans-serif, system-ui, sans-serif;

        /* الحاوية الوسطى — هامش يمين ويسار متساويان على كل الشاشات.
           على شاشة 1920px يصبح الهامش 180px على كل جانب، وينكمش تدريجيًا في الشاشات الأصغر. */
        --page-max: 1560px;
        --page-pad: clamp(16px, 2.5vw, 28px);
        --sidebar-width: 280px;
        --sidebar-gap: 32px;
    }

    * { box-sizing: border-box; }
    html, body { margin: 0; padding: 0; }

    body {
        font-family: var(--font-body);
        background: hsl(var(--background));
        color: hsl(var(--foreground));
        font-size: 14px;
        -webkit-font-smoothing: antialiased;
    }

    a { color: inherit; text-decoration: none; }
    ul { margin: 0; padding-inline-start: 18px; }

    /* الشريط العلوي يمتد بعرض الشاشة، ومحتواه محاذٍ لنفس حاوية الصفحة. */
    .topbar { background: hsl(var(--card)); border-bottom: 1px solid hsl(var(--border)); }
    .topbar-inner {
        width: min(100% - 2 * var(--page-pad), var(--page-max));
        margin-inline: auto;
        padding-block: 14px;
        display: flex; align-items: center; justify-content: space-between; gap: 16px;
    }
    .topbar-brand { display: inline-flex; align-items: center; gap: 12px; }
    .topbar-brand .brand-mark { width: 44px; height: 44px; border-radius: 14px; background: hsl(var(--accent)); color: hsl(var(--accent-foreground)); display: grid; place-items: center; flex-shrink: 0; }
    .topbar-brand .brand-mark .icon { width: 22px; height: 22px; }
    .topbar-brand .brand-text { display: flex; flex-direction: column; align-items: flex-end; line-height: 1.35; }
    .topbar-brand .brand-title { font-weight: 800; font-size: 16px; }
    .topbar-brand .brand-sub { font-size: 11.5px; color: hsl(var(--muted-foreground)); }
    .topbar-ministry { display: inline-flex; align-items: center; gap: 8px; border: 1px solid hsl(var(--border)); border-radius: var(--radius); padding: 9px 14px; font-size: 12.5px; font-weight: 600; }
    .topbar-ministry:hover { background: hsl(var(--muted)); }

    .shell {
        width: min(100% - 2 * var(--page-pad), var(--page-max));
        margin-inline: auto;
        display: flex; align-items: flex-start; gap: var(--sidebar-gap);
        min-height: calc(100vh - 73px);
    }

    .sidebar {
        width: var(--sidebar-width);
        flex-shrink: 0;
        background: transparent;
        border-inline-end: 1px solid hsl(var(--border));
        padding-block: 22px 40px;
        padding-inline: 4px var(--sidebar-gap);
        position: sticky;
        top: 0;
        align-self: stretch;
        max-height: 100vh;
        overflow-y: auto;
        overscroll-behavior: contain;
        /* شريط تمرير رفيع وهادئ — يظهر بوضوح عند المرور على القائمة فقط. */
        scrollbar-width: thin;
        scrollbar-color: hsl(var(--foreground) / .14) transparent;
        scrollbar-gutter: stable;
    }
    .sidebar:hover { scrollbar-color: hsl(var(--foreground) / .28) transparent; }
    .sidebar::-webkit-scrollbar { width: 6px; }
    .sidebar::-webkit-scrollbar-track { background: transparent; }
    .sidebar::-webkit-scrollbar-thumb { background: hsl(var(--foreground) / .14); border-radius: 999px; }
    .sidebar:hover::-webkit-scrollbar-thumb { background: hsl(var(--foreground) / .28); }
    .sidebar::-webkit-scrollbar-thumb:hover { background: hsl(var(--foreground) / .42); }

    .sidebar-nav { display: flex; flex-direction: column; }
    .sidebar-group { padding: 20px 8px 8px; font-size: 12px; font-weight: 700; color: hsl(var(--muted-foreground)); }
    .sidebar-group:first-child { padding-top: 4px; }
    .sidebar-link { display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 11px 8px; border-radius: 10px; font-size: 14px; line-height: 1.5; color: hsl(var(--foreground) / .85); }
    .sidebar-link .icon { width: 16px; height: 16px; opacity: .4; }
    .sidebar-link:hover { background: hsl(var(--muted)); }
    .sidebar-link.is-active { background: hsl(var(--accent)); color: hsl(var(--accent-foreground)); font-weight: 700; }
    .sidebar-link.is-active .icon { opacity: 1; }

    .shell-content { flex: 1; min-width: 0; padding-block: 28px 40px; display: flex; flex-direction: column; gap: 24px; }

    .flash { border: 1px solid hsl(var(--ok-bg)); background: hsl(var(--ok-bg)); color: hsl(var(--ok-fg)); padding: 11px 16px; border-radius: 12px; font-size: 13px; font-weight: 600; }
    .flash-error { background: hsl(var(--danger-bg)); color: hsl(var(--danger-fg)); border-color: hsl(var(--danger-bg)); font-weight: 500; }

    .page-header { display: flex; align-items: center; gap: 12px; border-bottom: 1px solid hsl(var(--border)); padding-bottom: 16px; }
    .page-header .icon { width: 44px; height: 44px; border-radius: 12px; background: hsl(var(--accent)); color: hsl(var(--accent-foreground)); display: grid; place-items: center; flex-shrink: 0; }
    .page-header .icon .icon { width: 22px; height: 22px; background: none; border-radius: 0; }
    .page-header h1 { margin: 0; font-size: 22px; font-weight: 800; letter-spacing: -.01em; }
    .page-header p { margin: 3px 0 0; font-size: 12.5px; color: hsl(var(--muted-foreground)); }

    .notice { display: flex; gap: 10px; align-items: flex-start; border: 1px solid hsl(var(--amber-border)); background: hsl(var(--amber-bg)); color: hsl(var(--amber-fg)); border-radius: 12px; padding: 14px 16px; font-size: 13px; line-height: 1.8; }
    .notice strong { font-weight: 800; }

    .tabbar { display: flex; flex-wrap: wrap; gap: 8px; border: 1px solid hsl(var(--border)); background: hsl(var(--card)); border-radius: 12px; padding: 8px; }
    .tabbar-item { display: inline-flex; align-items: center; gap: 8px; border-radius: var(--radius); padding: 8px 14px; font-size: 13px; font-weight: 500; color: hsl(var(--foreground) / .8); }
    .tabbar-item:hover { background: hsl(var(--muted)); }
    .tabbar-item.is-active { background: hsl(var(--primary)); color: hsl(var(--primary-foreground)); font-weight: 700; box-shadow: 0 1px 3px hsl(var(--foreground) / .12); }

    .panel { border: 1px solid hsl(var(--border)); background: hsl(var(--card)); border-radius: 16px; padding: 20px; box-shadow: 0 1px 2px hsl(var(--foreground) / .04), 0 4px 12px -2px hsl(var(--foreground) / .06); display: flex; flex-direction: column; gap: 20px; }

    .subtabbar { display: flex; flex-wrap: wrap; gap: 8px; border: 1px solid hsl(var(--border)); border-radius: 12px; padding: 8px; }
    .subtab { border-radius: var(--radius); padding: 6px 16px; font-size: 13px; font-weight: 500; color: hsl(var(--foreground) / .8); }
    .subtab:hover { background: hsl(var(--muted)); }
    .subtab.is-active { background: hsl(var(--primary)); color: hsl(var(--primary-foreground)); font-weight: 700; }

    .section-head { display: flex; align-items: flex-end; justify-content: space-between; gap: 16px; flex-wrap: wrap; }
    .section-head h2 { margin: 0; font-size: 16px; font-weight: 800; }
    .section-head p { margin: 3px 0 0; font-size: 12.5px; color: hsl(var(--muted-foreground)); }

    .btn { display: inline-flex; align-items: center; gap: 7px; border: none; cursor: pointer; font-family: inherit; font-size: 13px; font-weight: 600; border-radius: var(--radius); padding: 9px 16px; transition: .15s; }
    .btn-primary { background: hsl(var(--primary)); color: hsl(var(--primary-foreground)); }
    .btn-primary:hover { background: hsl(var(--primary) / .9); }
    .btn-outline { background: transparent; border: 1px solid hsl(var(--border)); color: hsl(var(--foreground)); }
    .btn-outline:hover { background: hsl(var(--muted)); }
    .btn-icon { width: 32px; height: 32px; padding: 0; justify-content: center; border-radius: var(--radius); border: 1px solid hsl(var(--border)); background: transparent; cursor: pointer; }
    .btn-icon:hover { background: hsl(var(--muted)); }
    .btn-icon.is-danger { color: hsl(var(--destructive)); border-color: hsl(var(--destructive) / .3); }
    .btn-icon.is-danger:hover { background: hsl(var(--danger-bg)); }

    .table-wrap { border: 1px solid hsl(var(--border)); border-radius: 12px; overflow: auto; }
    table { width: 100%; border-collapse: collapse; }
    thead th { background: hsl(var(--muted) / .7); color: hsl(var(--muted-foreground)); font-size: 12px; font-weight: 700; text-align: right; padding: 11px 16px; border-bottom: 1px solid hsl(var(--border)); white-space: nowrap; }
    tbody td { padding: 12px 16px; font-size: 13px; border-bottom: 1px solid hsl(var(--border) / .7); }
    tbody tr:last-child td { border-bottom: none; }
    tbody tr:nth-child(even) { background: hsl(var(--muted) / .35); }
    tbody tr:hover { background: hsl(var(--accent) / .35); }
    td.cell-actions { text-align: center; white-space: nowrap; }
    .inline-form { display: inline; }
    .cell-actions .btn-icon { display: inline-grid; place-items: center; vertical-align: middle; margin-inline-start: 4px; }
    .empty-state { padding: 44px 20px; text-align: center; color: hsl(var(--muted-foreground)); font-size: 13px; }

    .badge { display: inline-flex; align-items: center; gap: 6px; padding: 3px 11px; border-radius: 999px; font-size: 11.5px; font-weight: 700; }
    .badge::before { content: ''; width: 6px; height: 6px; border-radius: 999px; background: currentColor; }
    .badge-ok { background: hsl(var(--ok-bg)); color: hsl(var(--ok-fg)); }
    .badge-warn { background: hsl(var(--warn-bg)); color: hsl(var(--warn-fg)); }
    .badge-danger { background: hsl(var(--danger-bg)); color: hsl(var(--danger-fg)); }
    .badge-muted { background: hsl(var(--muted)); color: hsl(var(--muted-foreground)); }

    .pager { display: flex; align-items: center; justify-content: space-between; gap: 12px; font-size: 12.5px; color: hsl(var(--muted-foreground)); }
    .pager nav { display: flex; gap: 6px; }
    .pager a, .pager span { border: 1px solid hsl(var(--border)); border-radius: var(--radius); padding: 5px 11px; }

    .form-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(240px, 1fr)); gap: 14px; }
    .field { display: flex; flex-direction: column; gap: 6px; }
    .field label { font-size: 12px; font-weight: 700; color: hsl(var(--foreground) / .85); }
    .field .req { color: hsl(var(--destructive)); }
    .field input[type=text], .field input[type=number], .field input[type=date], .field select, .field textarea {
        font-family: inherit; font-size: 13px; width: 100%; padding: 9px 11px;
        border: 1px solid hsl(var(--border)); border-radius: var(--radius);
        background: hsl(var(--card)); color: hsl(var(--foreground)); outline: none;
    }
    .field input:focus, .field select:focus, .field textarea:focus { border-color: hsl(var(--primary)); box-shadow: 0 0 0 3px hsl(var(--primary) / .18); }
    .field textarea { min-height: 84px; resize: vertical; }
    .field-check { flex-direction: row; align-items: center; gap: 9px; }
    .field-check input { width: 17px; height: 17px; accent-color: hsl(var(--primary)); }
    .field-wide { grid-column: 1 / -1; }

    dialog.modal { border: none; padding: 0; border-radius: 16px; width: min(1120px, 96vw); max-width: 96vw; max-height: 94vh; background: hsl(var(--card)); color: hsl(var(--foreground)); box-shadow: 0 24px 60px -12px hsl(var(--foreground) / .3); }
    dialog.modal::backdrop { background: hsl(var(--foreground) / .45); }
    .modal-head { display: flex; align-items: center; justify-content: space-between; padding: 16px 20px; border-bottom: 1px solid hsl(var(--border)); }
    .modal-head h3 { margin: 0; font-size: 15px; font-weight: 800; }
    .modal-body { padding: 20px; }
    /* بدون تمرير داخلي — النموذج يتمدد ليتسع لكل الحقول دفعة واحدة. */
    .modal .form-grid { grid-template-columns: repeat(auto-fill, minmax(190px, 1fr)); gap: 12px 14px; }
    .modal .field textarea { min-height: 64px; }
    .modal-foot { display: flex; justify-content: flex-start; gap: 10px; padding: 14px 20px; border-top: 1px solid hsl(var(--border)); background: hsl(var(--muted) / .4); border-radius: 0 0 16px 16px; }

    .stat-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 12px; }
    .stat-card { border: 1px solid hsl(var(--border)); border-radius: 14px; padding: 14px 16px; background: hsl(var(--card)); }
    .stat-card .label { font-size: 11.5px; color: hsl(var(--muted-foreground)); font-weight: 600; }
    .stat-card .value { font-size: 22px; font-weight: 800; margin-top: 6px; color: hsl(var(--primary)); }
    .stat-card .unit { font-size: 11px; color: hsl(var(--muted-foreground)); font-weight: 500; }

    .callout { display: flex; gap: 12px; align-items: flex-start; border: 1px solid hsl(var(--ok-bg)); background: hsl(var(--ok-bg) / .6); color: hsl(var(--ok-fg)); border-radius: 12px; padding: 14px 16px; }
    .callout .callout-title { font-weight: 800; font-size: 13.5px; }
    .callout p { margin: 4px 0 0; font-size: 12.5px; line-height: 1.8; }

    .info-list { display: grid; gap: 10px; }
    .info-row { display: flex; justify-content: space-between; gap: 12px; border: 1px solid hsl(var(--border)); border-radius: 10px; padding: 10px 14px; font-size: 13px; }
    .info-row span:last-child { color: hsl(var(--muted-foreground)); }

    .icon { width: 17px; height: 17px; stroke: currentColor; fill: none; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; flex-shrink: 0; }

    @media (max-width: 900px) {
        .sidebar { display: none; }
        .shell-content { padding-block: 20px 32px; }
        .topbar-brand .brand-mark { width: 38px; height: 38px; border-radius: 12px; }
        .topbar-brand .brand-title { font-size: 14px; }
    }
</style>