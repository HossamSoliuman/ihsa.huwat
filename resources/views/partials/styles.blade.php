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
    --border: 214 25% 88%;
    --ring: 199 89% 28%;
    --radius: 0.5rem;
}
html.dark {
    --background: 222 24% 7%;
    --foreground: 210 25% 96%;
    --card: 222 22% 10%;
    --primary: 199 85% 58%;
    --primary-foreground: 222 24% 7%;
    --muted: 222 18% 14%;
    --muted-foreground: 215 18% 62%;
    --accent: 222 20% 16%;
    --border: 220 16% 18%;
    --ring: 199 85% 58%;
}
* { box-sizing: border-box; margin: 0; padding: 0; border-color: hsl(var(--border)); }
body {
    font-family: 'Tajawal', ui-sans-serif, system-ui, sans-serif;
    background: hsl(var(--background));
    color: hsl(var(--foreground));
    -webkit-font-smoothing: antialiased;
}
a { color: inherit; text-decoration: none; }
::-webkit-scrollbar { width: 6px; height: 6px; }
::-webkit-scrollbar-thumb { background: hsl(var(--muted-foreground) / .22); border-radius: 9999px; }

.shell { min-height: 100vh; }
.sidebar {
    position: fixed; inset-block: 0; right: 0; z-index: 40; width: 18rem;
    background: hsl(var(--card)); border-left: 1px solid hsl(var(--border));
    display: flex; flex-direction: column; transform: translateX(100%);
    transition: transform .3s ease;
}
.sidebar.is-open { transform: translateX(0); }
.backdrop { position: fixed; inset: 0; z-index: 30; background: rgba(0,0,0,.3); display: none; }
.backdrop.is-visible { display: block; }
.main { min-height: 100vh; }
@media (min-width: 1024px) {
    .sidebar { transform: translateX(0); }
    .main { margin-right: 18rem; }
    .backdrop { display: none !important; }
    .menu-btn, .sidebar-close { display: none !important; }
}

.sidebar-head { display: flex; align-items: center; gap: .75rem; border-bottom: 1px solid hsl(var(--border)); padding: 1rem 1.25rem; }
.sidebar-head img { height: 44px; width: 44px; border-radius: .75rem; object-fit: cover; }
.sidebar-head .app-name { font-size: 1rem; font-weight: 700; line-height: 1.2; }
.sidebar-close { margin-right: auto; border: 0; background: none; color: hsl(var(--muted-foreground)); border-radius: .5rem; padding: .35rem; cursor: pointer; }

.sidebar-nav { flex: 1; overflow-y: auto; padding: 1rem .75rem; }
.nav-section { margin-bottom: 1.25rem; }
.nav-section-title { padding: 0 .75rem; margin-bottom: .375rem; font-size: 11px; font-weight: 600; letter-spacing: .05em; text-transform: uppercase; color: hsl(var(--muted-foreground) / .7); }
.nav-link {
    position: relative; display: flex; align-items: center; gap: .75rem;
    border-radius: .5rem; padding: .5rem .75rem; margin-bottom: 2px;
    font-size: .875rem; font-weight: 500; color: hsl(var(--muted-foreground));
    transition: all .2s;
}
.nav-link:hover { background: hsl(var(--muted) / .6); color: hsl(var(--foreground)); }
.nav-link.is-active { background: hsl(var(--primary) / .1); color: hsl(var(--primary)); }
.nav-link.is-active::before {
    content: ''; position: absolute; right: 0; top: 50%; transform: translateY(-50%);
    height: 1.25rem; width: 3px; border-radius: 9999px; background: hsl(var(--primary));
}
.nav-link svg { width: 18px; height: 18px; flex-shrink: 0; }
.nav-link span { white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

.topbar {
    position: sticky; top: 0; z-index: 20; display: flex; align-items: center; gap: .75rem;
    height: 4rem; border-bottom: 1px solid hsl(var(--border));
    background: hsl(var(--card) / .85); backdrop-filter: saturate(180%) blur(14px);
    padding: 0 1.5rem;
}
.topbar h2 { font-size: .95rem; font-weight: 600; }
.topbar-actions { display: flex; align-items: center; gap: .5rem; margin-right: auto; }
.icon-btn { position: relative; border: 0; background: none; color: hsl(var(--muted-foreground)); border-radius: .5rem; padding: .5rem; cursor: pointer; }
.icon-btn:hover { background: hsl(var(--muted)); }
.icon-btn svg { width: 20px; height: 20px; display: block; }
.icon-btn .dot { position: absolute; top: 6px; right: 6px; height: 8px; width: 8px; border-radius: 9999px; background: #f43f5e; box-shadow: 0 0 0 2px hsl(var(--card)); }
.menu-btn { border: 0; background: none; color: hsl(var(--muted-foreground)); border-radius: .5rem; padding: .5rem; cursor: pointer; }
.search-box { position: relative; display: none; }
@media (min-width: 640px) { .search-box { display: block; } }
.search-box svg { position: absolute; top: 50%; right: .75rem; transform: translateY(-50%); width: 16px; height: 16px; color: hsl(var(--muted-foreground)); pointer-events: none; }
.search-box input {
    width: 11rem; border-radius: .5rem; border: 1px solid hsl(var(--border));
    background: hsl(var(--background)); padding: .5rem 2.25rem .5rem .75rem;
    font-size: .875rem; font-family: inherit; color: inherit; outline: none; transition: all .2s;
}
.search-box input:focus { width: 16rem; border-color: hsl(var(--primary)); box-shadow: 0 0 0 2px hsl(var(--primary) / .2); }
.user-chip { display: flex; align-items: center; gap: .5rem; border: 1px solid hsl(var(--border)); background: hsl(var(--muted) / .4); border-radius: .5rem; padding: .375rem .625rem; }
.user-chip .avatar { display: flex; align-items: center; justify-content: center; height: 1.75rem; width: 1.75rem; border-radius: 9999px; background: hsl(var(--primary)); color: hsl(var(--primary-foreground)); font-size: .72rem; font-weight: 700; }
.user-chip .role { font-size: .72rem; font-weight: 600; line-height: 1.2; }
.user-chip .sub { font-size: 10px; color: hsl(var(--muted-foreground)); }
.user-chip .meta { display: none; }
@media (min-width: 640px) { .user-chip .meta { display: block; } }

.content { padding: 1.5rem 2rem; }
@media (max-width: 640px) { .content { padding: 1.25rem 1rem; } }

.page-header { display: flex; flex-wrap: wrap; align-items: flex-start; justify-content: space-between; gap: 1rem; border-bottom: 1px solid hsl(var(--border)); padding-bottom: 1.25rem; margin-bottom: 1.5rem; }
.page-header .lead { display: flex; align-items: flex-start; gap: .75rem; }
.page-header .icon-wrap { display: flex; align-items: center; justify-content: center; height: 2.75rem; width: 2.75rem; border-radius: .75rem; background: hsl(var(--primary) / .1); color: hsl(var(--primary)); }
.page-header .icon-wrap svg { width: 22px; height: 22px; }
.page-header h1 { font-size: 1.35rem; font-weight: 800; letter-spacing: -.01em; }
.page-header p { margin-top: .25rem; font-size: .8rem; color: hsl(var(--muted-foreground)); }
.page-header .actions { display: flex; flex-wrap: wrap; align-items: center; gap: .5rem; }

.card { border-radius: 1rem; border: 1px solid hsl(var(--border)); background: hsl(var(--card)); padding: 1rem; box-shadow: 0 1px 2px hsl(var(--foreground) / .04), 0 4px 12px -2px hsl(var(--foreground) / .06); }
.card-title { font-size: .875rem; font-weight: 700; }
.card-sub { font-size: .72rem; color: hsl(var(--muted-foreground)); margin-top: .125rem; }

.kpi-grid { display: grid; gap: .75rem; grid-template-columns: repeat(1, 1fr); }
@media (min-width: 640px) { .kpi-grid { grid-template-columns: repeat(2, 1fr); } }
@media (min-width: 1024px) { .kpi-grid { grid-template-columns: repeat(3, 1fr); } }
@media (min-width: 1280px) { .kpi-grid { grid-template-columns: repeat(5, 1fr); } }
.kpi-card { border-radius: 1rem; border: 1px solid hsl(var(--border)); background: hsl(var(--card)); padding: 1rem; box-shadow: 0 1px 2px hsl(var(--foreground) / .04); }
.kpi-card .top { display: flex; align-items: center; justify-content: space-between; margin-bottom: .5rem; }
.kpi-card .label { font-size: .72rem; font-weight: 600; color: hsl(var(--muted-foreground)); }
.kpi-card .value { font-size: 1.3rem; font-weight: 800; line-height: 1.2; }
.kpi-card .unit { font-size: .7rem; font-weight: 500; color: hsl(var(--muted-foreground)); margin-right: .25rem; }
.kpi-icon { display: flex; align-items: center; justify-content: center; height: 2.25rem; width: 2.25rem; border-radius: .625rem; }
.kpi-icon svg { width: 18px; height: 18px; }
.kpi-icon.primary { background: hsl(var(--primary) / .1); color: hsl(var(--primary)); }
.kpi-icon.info { background: hsl(199 85% 90% / .6); color: #0369a1; }
.kpi-icon.success { background: #d1fae5; color: #047857; }
.kpi-icon.warning { background: #fef3c7; color: #b45309; }
.kpi-icon.danger { background: #ffe4e6; color: #e11d48; }
html.dark .kpi-icon.info { background: hsl(199 55% 30% / .26); color: hsl(199 80% 78%); }
html.dark .kpi-icon.success { background: hsl(152 50% 30% / .26); color: hsl(152 60% 75%); }
html.dark .kpi-icon.warning { background: hsl(38 65% 28% / .28); color: hsl(38 85% 78%); }
html.dark .kpi-icon.danger { background: hsl(0 55% 30% / .26); color: hsl(0 75% 72%); }

.grid-3 { display: grid; gap: 1rem; grid-template-columns: 1fr; }
@media (min-width: 1024px) { .grid-3 { grid-template-columns: repeat(3, 1fr); } .span-2 { grid-column: span 2; } }

.alert-item { display: flex; align-items: flex-start; gap: .75rem; border-radius: .5rem; border: 1px solid hsl(var(--border)); background: hsl(var(--muted) / .3); padding: .75rem; margin-bottom: .5rem; }
.alert-item .sev-dot { margin-top: .3rem; height: 8px; width: 8px; flex-shrink: 0; border-radius: 9999px; }
.alert-item .a-title { font-size: .875rem; font-weight: 500; }
.alert-item .a-desc { font-size: .72rem; color: hsl(var(--muted-foreground)); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 200px; }

.badge { display: inline-flex; align-items: center; gap: .3rem; border-radius: 9999px; border: 1px solid; padding: .125rem .55rem; font-size: .7rem; font-weight: 600; white-space: nowrap; }
.badge::before { content: ''; height: 6px; width: 6px; border-radius: 9999px; background: currentColor; }
.badge-ok { color: #047857; border-color: #a7f3d0; background: #ecfdf5; }
.badge-warn { color: #b45309; border-color: #fde68a; background: #fffbeb; }
.badge-danger { color: #e11d48; border-color: #fecdd3; background: #fff1f2; }
.badge-info { color: #0369a1; border-color: #bae6fd; background: #f0f9ff; }
html.dark .badge-ok { background: hsl(152 50% 30% / .26); border-color: hsl(152 50% 40% / .5); color: hsl(152 60% 75%); }
html.dark .badge-warn { background: hsl(38 65% 28% / .28); border-color: hsl(38 65% 40% / .5); color: hsl(38 85% 78%); }
html.dark .badge-danger { background: hsl(0 55% 30% / .26); border-color: hsl(0 55% 42% / .5); color: hsl(0 75% 72%); }
html.dark .badge-info { background: hsl(199 55% 30% / .26); border-color: hsl(199 55% 42% / .5); color: hsl(199 80% 78%); }

.note-box { display: flex; align-items: flex-start; gap: .75rem; border-radius: .75rem; border: 1px solid #a7f3d0; background: #ecfdf5; padding: 1rem; margin-top: 1.5rem; }
html.dark .note-box { border-color: hsl(152 50% 40% / .4); background: hsl(152 50% 30% / .18); }
.note-box svg { width: 20px; height: 20px; flex-shrink: 0; margin-top: 2px; color: #047857; }
html.dark .note-box svg { color: hsl(152 60% 75%); }
.note-box .n-title { font-size: .875rem; font-weight: 700; color: #064e3b; }
.note-box .n-body { margin-top: .25rem; font-size: .72rem; line-height: 1.8; color: #065f46; }
html.dark .note-box .n-title { color: hsl(152 60% 82%); }
html.dark .note-box .n-body { color: hsl(152 45% 70%); }

.chart-wrap { position: relative; height: 280px; }
.link-more { display: inline-flex; align-items: center; gap: .25rem; font-size: .72rem; font-weight: 500; color: hsl(var(--primary)); }
.link-more:hover { text-decoration: underline; }
.section-head { display: flex; align-items: center; justify-content: space-between; margin-bottom: 1rem; }
.section-head .with-icon { display: flex; align-items: center; gap: .5rem; }
.section-head .with-icon svg { width: 16px; height: 16px; color: #b45309; }

.pending-card { display: flex; flex-direction: column; align-items: center; justify-content: center; gap: .75rem; border-radius: 1rem; border: 1px dashed hsl(var(--border)); background: hsl(var(--card)); padding: 4rem 2rem; text-align: center; }
.pending-card svg { width: 40px; height: 40px; color: hsl(var(--muted-foreground)); }
.pending-card h3 { font-size: 1.1rem; font-weight: 700; }
.pending-card p { font-size: .8rem; color: hsl(var(--muted-foreground)); max-width: 28rem; line-height: 1.8; }

.stat-grid { display: grid; gap: .75rem; grid-template-columns: repeat(2, 1fr); }
@media (min-width: 640px) { .stat-grid { grid-template-columns: repeat(3, 1fr); } }
@media (min-width: 1024px) { .stat-grid.cols-4 { grid-template-columns: repeat(4, 1fr); } .stat-grid.cols-5 { grid-template-columns: repeat(5, 1fr); } .stat-grid.cols-6 { grid-template-columns: repeat(6, 1fr); } }
.stat-card { display: flex; align-items: flex-start; justify-content: space-between; gap: .5rem; border-radius: .75rem; border: 1px solid hsl(var(--border)); background: hsl(var(--card)); padding: 1rem; }
.stat-card .label { font-size: .72rem; font-weight: 600; color: hsl(var(--muted-foreground)); }
.stat-card .value { margin-top: .25rem; font-size: 1.25rem; font-weight: 800; }
.stat-card .unit { font-size: .7rem; font-weight: 500; color: hsl(var(--muted-foreground)); margin-right: .25rem; }
.filter-bar { display: flex; flex-wrap: wrap; align-items: flex-end; gap: .75rem; border-radius: .75rem; border: 1px solid hsl(var(--border)); background: hsl(var(--card)); padding: 1rem; }
.field { display: flex; flex-direction: column; gap: .35rem; }
.field > span { font-size: .72rem; font-weight: 500; color: hsl(var(--muted-foreground)); }
.input, .select { border-radius: .5rem; border: 1px solid hsl(var(--border)); background: hsl(var(--background)); padding: .55rem .75rem; font-size: .875rem; font-family: inherit; color: inherit; outline: none; width: 100%; }
.input:focus, .select:focus { border-color: hsl(var(--primary)); box-shadow: 0 0 0 2px hsl(var(--primary) / .15); }
.btn { display: inline-flex; align-items: center; gap: .4rem; border-radius: .5rem; padding: .55rem 1rem; font-size: .875rem; font-weight: 600; cursor: pointer; border: 0; font-family: inherit; }
.btn svg { width: 16px; height: 16px; }
.btn-primary { background: hsl(var(--primary)); color: hsl(var(--primary-foreground)); }
.btn-primary:hover { opacity: .92; }
.btn-outline { background: hsl(var(--card)); color: hsl(var(--foreground)); border: 1px solid hsl(var(--border)); }
.btn-outline:hover { background: hsl(var(--muted)); }
.icon-action { border: 0; background: none; cursor: pointer; border-radius: .4rem; padding: .35rem; color: hsl(var(--muted-foreground)); }
.icon-action svg { width: 16px; height: 16px; display: block; }
.icon-action:hover { background: hsl(var(--muted)); color: hsl(var(--primary)); }
.icon-action.danger:hover { background: #fff1f2; color: #e11d48; }
.table-card { overflow-x: auto; border-radius: .75rem; border: 1px solid hsl(var(--border)); background: hsl(var(--card)); }
.data-table { width: 100%; border-collapse: collapse; font-size: .82rem; }
.data-table th { background: hsl(var(--muted) / .6); padding: .6rem .75rem; text-align: right; font-size: .72rem; font-weight: 700; color: hsl(var(--muted-foreground)); white-space: nowrap; }
.data-table td { padding: .6rem .75rem; border-top: 1px solid hsl(var(--border)); vertical-align: middle; }
.data-table tbody tr:nth-child(even) { background: hsl(var(--muted) / .25); }
.cards-grid { display: grid; gap: 1rem; grid-template-columns: 1fr; }
@media (min-width: 640px) { .cards-grid { grid-template-columns: repeat(2, 1fr); } }
@media (min-width: 1024px) { .cards-grid.cols-3 { grid-template-columns: repeat(3, 1fr); } .cards-grid.cols-4 { grid-template-columns: repeat(4, 1fr); } }
.entity-card { display: flex; flex-direction: column; border-radius: .75rem; border: 1px solid hsl(var(--border)); background: hsl(var(--card)); padding: 1.25rem; transition: all .2s; color: inherit; text-align: right; }
.entity-card:hover { border-color: hsl(var(--primary) / .4); box-shadow: 0 8px 24px -6px hsl(var(--foreground) / .1); transform: translateY(-2px); }
.mini { display: flex; align-items: center; gap: .5rem; border-radius: .5rem; background: hsl(var(--muted) / .5); padding: .5rem .625rem; min-width: 0; }
.mini svg { width: 16px; height: 16px; flex-shrink: 0; color: hsl(var(--primary)); }
.mini .m-label { font-size: 11px; color: hsl(var(--muted-foreground)); }
.mini .m-value { font-size: .85rem; font-weight: 700; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.mini-grid { display: grid; gap: .625rem; grid-template-columns: repeat(2, 1fr); margin-top: 1rem; }
.progress { height: .5rem; width: 100%; overflow: hidden; border-radius: 9999px; background: hsl(var(--muted)); }
.progress > div { height: 100%; border-radius: 9999px; transition: width .3s; }
.drawer-overlay { position: fixed; inset: 0; z-index: 50; background: rgba(0, 0, 0, .4); display: none; }
.drawer-overlay.is-open { display: block; }
.drawer { position: fixed; inset-block: 0; left: 0; z-index: 51; width: 100%; max-width: 28rem; overflow-y: auto; background: hsl(var(--card)); box-shadow: 0 25px 50px -12px rgba(0, 0, 0, .25); transform: translateX(-110%); transition: transform .3s; }
.drawer.is-open { transform: translateX(0); }
.drawer.wide { max-width: 48rem; }
.drawer-head { position: sticky; top: 0; z-index: 1; display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid hsl(var(--border)); background: hsl(var(--card)); padding: 1rem 1.25rem; }
.drawer-head h3 { font-size: 1.05rem; font-weight: 700; }
.drawer-body { padding: 1.25rem; display: flex; flex-direction: column; gap: 1rem; }
.form-grid { display: grid; gap: .75rem; grid-template-columns: 1fr; }
@media (min-width: 640px) { .form-grid { grid-template-columns: repeat(2, 1fr); } .form-grid .wide { grid-column: span 2; } }
.tag { display: inline-flex; align-items: center; gap: .25rem; border-radius: 9999px; padding: .1rem .5rem; font-size: 11px; font-weight: 500; }
.tag-gulf { background: #f0f9ff; color: #0369a1; box-shadow: inset 0 0 0 1px #bae6fd; }
.tag-red { background: #fff1f2; color: #be123c; box-shadow: inset 0 0 0 1px #fecdd3; }
.group-head { display: flex; align-items: center; gap: .5rem; border-bottom: 2px solid hsl(var(--border)); padding-bottom: .5rem; margin-bottom: .75rem; }
.group-head.gulf { border-color: #7dd3fc; }
.group-head.red { border-color: #fda4af; }
.group-head h2 { font-size: 1rem; font-weight: 700; }
.count-pill { border-radius: 9999px; background: hsl(var(--muted)); padding: .1rem .5rem; font-size: .72rem; font-weight: 500; color: hsl(var(--muted-foreground)); }
.hier-chip { border-radius: .5rem; background: hsl(var(--primary) / .1); padding: .5rem 1rem; font-size: .875rem; font-weight: 500; color: hsl(var(--primary)); }
.legend-row { display: flex; align-items: center; justify-content: space-between; font-size: .75rem; margin-bottom: .5rem; }
.legend-row .l-icon { display: inline-flex; height: 1.25rem; width: 1.25rem; align-items: center; justify-content: center; border-radius: .25rem; color: #fff; margin-left: .5rem; }
.legend-row .l-icon svg { width: 12px; height: 12px; }
.grid-2 { display: grid; gap: 1rem; grid-template-columns: 1fr; }
@media (min-width: 1024px) { .grid-2 { grid-template-columns: repeat(2, 1fr); } }
.workflow-grid { display: grid; gap: .75rem; grid-template-columns: 1fr; }
@media (min-width: 768px) { .workflow-grid { grid-template-columns: repeat(4, 1fr); } }
.pill { border-radius: 9999px; padding: .25rem .625rem; font-size: .72rem; font-weight: 500; white-space: nowrap; }
.pill-emerald { background: #d1fae5; color: #047857; }
.pill-sky { background: #e0f2fe; color: #0369a1; }
.pill-amber { background: #fef3c7; color: #b45309; }
.pill-rose { background: #ffe4e6; color: #be123c; }
.pill-slate { background: hsl(var(--muted)); color: hsl(var(--muted-foreground)); }
.gov-box { border-radius: .5rem; background: hsl(var(--muted) / .5); padding: .5rem .75rem; }
.gov-box .g-label { font-size: .72rem; color: hsl(var(--muted-foreground)); }
.gov-box .g-value { margin-top: .25rem; font-size: 1.5rem; font-weight: 700; }
.gov-grid { display: grid; gap: .75rem; grid-template-columns: repeat(2, 1fr); }
@media (min-width: 640px) { .gov-grid { grid-template-columns: repeat(4, 1fr); } }
.flash { border-radius: .5rem; border: 1px solid #a7f3d0; background: #ecfdf5; color: #047857; padding: .75rem 1rem; font-size: .82rem; margin-bottom: 1rem; }
html.dark .flash { background: hsl(152 50% 30% / .2); border-color: hsl(152 50% 40% / .4); color: hsl(152 60% 75%); }

/* قسم الإحصاء — بوابته الموحّدة ولوحاته */
.portal-hero { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 1rem; border-radius: 1rem; background: linear-gradient(270deg, #0369a1, #06b6d4); color: #fff; padding: 1.5rem; margin-bottom: 1.25rem; }
.portal-hero h2 { font-size: 1.25rem; font-weight: 800; }
.portal-hero p { margin-top: .25rem; font-size: .82rem; color: #e0f2fe; }
.portal-hero .tiles { display: flex; flex-wrap: wrap; gap: .625rem; }
.portal-hero .tile { border-radius: .75rem; background: rgba(255,255,255,.12); box-shadow: inset 0 0 0 1px rgba(255,255,255,.16); padding: .5rem 1rem; text-align: center; }
.portal-hero .tile b { display: block; font-size: 1.4rem; font-weight: 800; line-height: 1.2; }
.portal-hero .tile span { font-size: 10px; color: #e0f2fe; }

.portal-group { margin-bottom: 1.25rem; }
.portal-group .head { display: flex; align-items: center; gap: .75rem; margin-bottom: .75rem; }
.portal-group .head .badge-icon { display: flex; align-items: center; justify-content: center; height: 2.5rem; width: 2.5rem; flex-shrink: 0; border-radius: .75rem; border: 1px solid; }
.portal-group .head h3 { font-size: 1rem; font-weight: 700; }
.portal-group .head p { font-size: .72rem; color: hsl(var(--muted-foreground)); }
.tone-sky { border-color: #bae6fd; background: #f0f9ff; color: #0369a1; }
.tone-amber { border-color: #fde68a; background: #fffbeb; color: #b45309; }
.tone-violet { border-color: #ddd6fe; background: #f5f3ff; color: #6d28d9; }
.tone-emerald { border-color: #a7f3d0; background: #ecfdf5; color: #047857; }
.tone-cyan { border-color: #a5f3fc; background: #ecfeff; color: #0e7490; }
.tone-rose { border-color: #fecdd3; background: #fff1f2; color: #be123c; }
html.dark .tone-sky, html.dark .tone-amber, html.dark .tone-violet,
html.dark .tone-emerald, html.dark .tone-cyan, html.dark .tone-rose { background: hsl(var(--muted) / .5); border-color: hsl(var(--border)); }

.portal-grid { display: grid; gap: .75rem; grid-template-columns: 1fr; }
@media (min-width: 640px) { .portal-grid { grid-template-columns: repeat(2, 1fr); } }
@media (min-width: 1024px) { .portal-grid { grid-template-columns: repeat(3, 1fr); } }
@media (min-width: 1440px) { .portal-grid { grid-template-columns: repeat(4, 1fr); } }
.portal-card { display: flex; flex-direction: column; gap: .5rem; border-radius: .75rem; border: 1px solid hsl(var(--border)); background: hsl(var(--muted) / .25); padding: 1rem; transition: all .2s; }
.portal-card:hover { transform: translateY(-2px); border-color: hsl(var(--primary) / .4); background: hsl(var(--card)); box-shadow: 0 8px 22px -6px hsl(var(--foreground) / .12); }
.portal-card .top { display: flex; align-items: center; justify-content: space-between; }
.portal-card .p-icon { display: flex; align-items: center; justify-content: center; height: 2.25rem; width: 2.25rem; border-radius: .625rem; background: hsl(var(--primary) / .1); color: hsl(var(--primary)); box-shadow: inset 0 0 0 1px hsl(var(--primary) / .15); }
.portal-card .go { color: hsl(var(--muted-foreground)); opacity: 0; transition: opacity .2s; }
.portal-card:hover .go { opacity: 1; }
.portal-card .p-title { font-size: .875rem; font-weight: 700; }
.portal-card .p-desc { margin-top: .125rem; font-size: 11px; line-height: 1.7; color: hsl(var(--muted-foreground)); }

.gap-card { border-radius: .75rem; border: 1px solid; padding: .875rem; }
.gap-card .g-label { font-size: .72rem; font-weight: 500; opacity: .85; }
.gap-card .g-value { margin-top: .25rem; font-size: 1.5rem; font-weight: 700; }
.gap-card .g-hint { margin-top: .125rem; font-size: 11px; opacity: .7; }
.gap-card.primary { border-color: #bae6fd; background: #f0f9ff; color: #0369a1; }
.gap-card.success { border-color: #a7f3d0; background: #ecfdf5; color: #047857; }
.gap-card.warning { border-color: #fde68a; background: #fffbeb; color: #b45309; }
.gap-card.danger { border-color: #fecdd3; background: #fff1f2; color: #be123c; }
html.dark .gap-card { background: hsl(var(--muted) / .5); border-color: hsl(var(--border)); color: hsl(var(--foreground)); }

.delta-pill { display: inline-flex; align-items: center; gap: .25rem; border-radius: 9999px; padding: .1rem .5rem; font-size: .72rem; font-weight: 700; }
.delta-pill svg { width: 13px; height: 13px; }
.delta-pill.up { background: #d1fae5; color: #047857; }
.delta-pill.flat { background: hsl(var(--muted)); color: hsl(var(--muted-foreground)); }
.delta-pill.down { background: #ffe4e6; color: #be123c; }
.delta-pill.extreme { box-shadow: 0 0 0 2px #fcd34d; }
html.dark .delta-pill.up { background: hsl(152 50% 30% / .3); color: hsl(152 60% 75%); }
html.dark .delta-pill.down { background: hsl(0 55% 30% / .3); color: hsl(0 75% 72%); }
.score-chip { display: inline-block; border-radius: 9999px; padding: .1rem .55rem; font-size: .72rem; font-weight: 700; color: #fff; }

.seg { display: inline-flex; gap: .25rem; border-radius: .625rem; border: 1px solid hsl(var(--border)); background: hsl(var(--card)); padding: .25rem; }
.seg a { display: inline-flex; align-items: center; gap: .375rem; border-radius: .5rem; padding: .375rem .75rem; font-size: .78rem; font-weight: 600; color: hsl(var(--muted-foreground)); }
.seg a svg { width: 14px; height: 14px; }
.seg a.is-active { background: hsl(var(--primary)); color: hsl(var(--primary-foreground)); }
.seg a:not(.is-active):hover { background: hsl(var(--muted)); color: hsl(var(--foreground)); }

.report-card { display: flex; flex-direction: column; overflow: hidden; border-radius: .75rem; border: 1px solid hsl(var(--border)); background: hsl(var(--card)); transition: all .2s; }
.report-card:hover { transform: translateY(-2px); box-shadow: 0 8px 22px -6px hsl(var(--foreground) / .12); }
.report-card .accent { height: 3px; width: 100%; background: hsl(var(--primary)); }
.report-card .accent.info { background: #0ea5e9; }
.report-card .accent.success { background: #059669; }
.report-card .accent.warning { background: #d97706; }
.report-card .accent.danger { background: #e11d48; }
.report-card .body { display: flex; flex: 1; flex-direction: column; padding: 1rem; }
.report-card .lead { display: flex; align-items: flex-start; gap: .75rem; }
.report-card h3 { font-size: .875rem; font-weight: 700; line-height: 1.4; }
.report-card .desc { margin-top: .125rem; font-size: 11px; line-height: 1.7; color: hsl(var(--muted-foreground)); }
.report-card .actions { display: flex; gap: .375rem; margin-top: auto; padding-top: .75rem; }
.report-card .actions > * { flex: 1; justify-content: center; padding: .45rem .5rem; font-size: 11px; }

.chat { display: flex; flex-direction: column; gap: 1.25rem; border-radius: 1rem; border: 1px solid hsl(var(--border)); background: hsl(var(--card)); padding: 1.25rem; }
.chat-row { display: flex; gap: .75rem; }
.chat-row.me { flex-direction: row-reverse; }
.chat-avatar { display: flex; height: 2.25rem; width: 2.25rem; flex-shrink: 0; align-items: center; justify-content: center; border-radius: .75rem; background: linear-gradient(225deg, #0ea5e9, #0891b2); color: #fff; }
.chat-avatar.me { background: hsl(var(--primary)); color: hsl(var(--primary-foreground)); }
.chat-bubble { max-width: 82%; border-radius: 1rem; background: hsl(var(--primary)); color: hsl(var(--primary-foreground)); padding: .625rem 1rem; font-size: .875rem; line-height: 1.8; }
.chat-bubble.bot { max-width: none; flex: 1; background: hsl(var(--muted)); color: hsl(var(--foreground)); }
.insight { flex: 1; min-width: 0; display: flex; flex-direction: column; gap: .75rem; }
.insight-head { border-radius: 1rem; border: 1px solid #bae6fd; background: linear-gradient(160deg, #f0f9ff, #ecfeff); padding: 1.25rem; }
html.dark .insight-head { border-color: hsl(var(--border)); background: hsl(var(--muted) / .5); }
.insight-head .kicker { font-size: 11px; font-weight: 700; letter-spacing: .1em; color: #0369a1; }
html.dark .insight-head .kicker { color: hsl(199 80% 78%); }
.insight-head h3 { margin-top: .25rem; font-size: 1.05rem; font-weight: 700; }
.insight-head .answer { margin-top: .75rem; font-size: .875rem; font-weight: 500; line-height: 1.9; }
.insight-meta { display: flex; flex-wrap: wrap; gap: .5rem; margin-top: .75rem; }
.insight-meta span { display: inline-flex; align-items: center; gap: .25rem; border-radius: 9999px; background: hsl(var(--card)); box-shadow: inset 0 0 0 1px hsl(var(--border)); padding: .25rem .625rem; font-size: 11px; color: hsl(var(--muted-foreground)); }
.insight-meta svg { width: 13px; height: 13px; }
.note-list { border-radius: .75rem; border: 1px solid; padding: 1rem; }
.note-list h4 { font-size: .875rem; font-weight: 700; }
.note-list li { margin-top: .5rem; font-size: .82rem; line-height: 1.8; }
.note-list.drivers { border-color: #fde68a; background: #fffbeb; color: #78350f; }
.note-list.actions { border-color: #a7f3d0; background: #ecfdf5; color: #064e3b; }
html.dark .note-list.drivers, html.dark .note-list.actions { background: hsl(var(--muted) / .5); border-color: hsl(var(--border)); color: hsl(var(--foreground)); }
.suggestions { display: flex; flex-wrap: wrap; gap: .5rem; }
.suggestions a { border-radius: 9999px; border: 1px solid hsl(var(--border)); background: hsl(var(--card)); padding: .375rem .75rem; font-size: .78rem; font-weight: 500; }
.suggestions a:hover { border-color: hsl(var(--primary) / .4); background: hsl(var(--primary) / .06); }
.ask-bar { display: flex; gap: .5rem; }
.ask-bar .input { flex: 1; }

/* قسم الإدارة الفرعية — الهيكل والتقويم والإنذارات والتنبيهات والإعدادات */
.level-amber { background: #fffbeb; color: #b45309; box-shadow: inset 0 0 0 1px #fde68a; }
.level-emerald { background: #ecfdf5; color: #047857; box-shadow: inset 0 0 0 1px #a7f3d0; }
.level-sky { background: #f0f9ff; color: #0369a1; box-shadow: inset 0 0 0 1px #bae6fd; }
.level-violet { background: #f5f3ff; color: #6d28d9; box-shadow: inset 0 0 0 1px #ddd6fe; }
.level-cyan { background: #ecfeff; color: #0e7490; box-shadow: inset 0 0 0 1px #a5f3fc; }
.level-slate { background: hsl(var(--muted)); color: hsl(var(--muted-foreground)); box-shadow: inset 0 0 0 1px hsl(var(--border)); }
html.dark .level-amber, html.dark .level-emerald, html.dark .level-sky,
html.dark .level-violet, html.dark .level-cyan { background: hsl(var(--muted) / .6); color: hsl(var(--foreground)); box-shadow: inset 0 0 0 1px hsl(var(--border)); }

.org-tree { display: flex; flex-direction: column; gap: .625rem; }
.org-node { border-radius: .75rem; border: 1px solid hsl(var(--border)); background: hsl(var(--card)); border-right-width: 4px; }
.org-node.level-amber { border-right-color: #fbbf24; }
.org-node.level-emerald { border-right-color: #34d399; }
.org-node.level-sky { border-right-color: #38bdf8; }
.org-node.level-violet { border-right-color: #a78bfa; }
.org-node.level-cyan { border-right-color: #22d3ee; }
.org-node.level-slate { border-right-color: hsl(var(--border)); }
.org-node-head { display: flex; align-items: flex-start; justify-content: space-between; gap: .75rem; padding: 1rem; }
.org-staff { border-top: 1px dashed hsl(var(--border)); padding: .5rem 1rem .75rem; display: flex; flex-direction: column; gap: .375rem; }
.org-staff-row { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: .5rem; border-radius: .5rem; background: hsl(var(--muted) / .4); padding: .5rem .75rem; }

.cal-nav { display: flex; align-items: center; justify-content: space-between; border-radius: .75rem; border: 1px solid hsl(var(--border)); background: hsl(var(--card)); padding: .5rem 1rem; margin-bottom: 1rem; }
.cal-nav h2 { font-size: 1rem; font-weight: 700; }
.cal { overflow: hidden; border-radius: .75rem; border: 1px solid hsl(var(--border)); background: hsl(var(--card)); }
.cal-head { display: grid; grid-template-columns: repeat(7, 1fr); background: hsl(var(--muted) / .5); border-bottom: 1px solid hsl(var(--border)); }
.cal-head span { padding: .5rem .25rem; text-align: center; font-size: .72rem; font-weight: 700; color: hsl(var(--muted-foreground)); }
.cal-grid { display: grid; grid-template-columns: repeat(7, 1fr); }
.cal-cell { min-height: 6rem; border-bottom: 1px solid hsl(var(--border)); border-left: 1px solid hsl(var(--border)); padding: .375rem; color: inherit; display: block; }
.cal-cell.is-blank { background: hsl(var(--muted) / .25); }
.cal-cell:not(.is-blank):hover { background: hsl(var(--accent) / .35); }
.cal-cell.is-today { background: hsl(var(--primary) / .06); }
.cal-cell.is-selected { box-shadow: inset 0 0 0 2px hsl(var(--primary)); }
.cal-day { display: flex; align-items: center; justify-content: space-between; margin-bottom: .25rem; }
.cal-day b { display: inline-flex; height: 1.35rem; min-width: 1.35rem; align-items: center; justify-content: center; border-radius: 9999px; font-size: .72rem; font-weight: 700; color: hsl(var(--muted-foreground)); }
.cal-cell.is-today .cal-day b { background: hsl(var(--primary)); color: hsl(var(--primary-foreground)); }
.cal-day span { font-size: 10px; color: hsl(var(--muted-foreground)); }
.cal-task { display: flex; align-items: center; gap: .25rem; border-radius: .3rem; padding: .1rem .35rem; margin-bottom: 2px; font-size: 11px; font-weight: 600; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.cal-task i { height: 6px; width: 6px; flex-shrink: 0; border-radius: 9999px; }
.dot-عادية { background: #38bdf8; }
.dot-مهمة { background: #fbbf24; }
.dot-عاجلة { background: #f43f5e; }
.task-مجدولة { background: #f0f9ff; color: #0369a1; box-shadow: inset 0 0 0 1px #bae6fd; }
.task-قيد { background: #fffbeb; color: #b45309; box-shadow: inset 0 0 0 1px #fde68a; }
.task-مكتملة { background: #ecfdf5; color: #047857; box-shadow: inset 0 0 0 1px #a7f3d0; }
.task-متأخرة { background: #fff1f2; color: #be123c; box-shadow: inset 0 0 0 1px #fecdd3; }
.task-ملغاة { background: hsl(var(--muted)); color: hsl(var(--muted-foreground)); box-shadow: inset 0 0 0 1px hsl(var(--border)); }
html.dark .task-مجدولة, html.dark .task-قيد, html.dark .task-مكتملة,
html.dark .task-متأخرة { background: hsl(var(--muted) / .6); color: hsl(var(--foreground)); box-shadow: inset 0 0 0 1px hsl(var(--border)); }

.alert-group { overflow: hidden; border-radius: .75rem; border: 1px solid hsl(var(--border)); background: hsl(var(--card)); margin-bottom: 1rem; }
.alert-group > summary { display: flex; align-items: center; justify-content: space-between; gap: .75rem; padding: .75rem 1rem; cursor: pointer; border-right: 4px solid; list-style: none; }
.alert-group > summary::-webkit-details-marker { display: none; }
.alert-group .g-title { font-size: .875rem; font-weight: 700; }
.alert-group .g-meta { font-size: .72rem; color: hsl(var(--muted-foreground)); }
.alert-group .body { background: hsl(var(--muted) / .3); padding: .75rem; display: flex; flex-direction: column; gap: .625rem; }
.alert-row { display: flex; flex-wrap: wrap; align-items: flex-start; gap: .75rem; border-radius: .5rem; border: 1px solid hsl(var(--border)); border-right-width: 4px; background: hsl(var(--card)); padding: .875rem; }
.alert-row .a-icon { display: flex; height: 2.25rem; width: 2.25rem; flex-shrink: 0; align-items: center; justify-content: center; border-radius: .5rem; }
.sev-حرج { border-right-color: #f43f5e; }
.sev-مرتفع { border-right-color: #fb923c; }
.sev-متوسط { border-right-color: #fbbf24; }
.sev-منخفض { border-right-color: #38bdf8; }
.sev-icon-حرج { background: #ffe4e6; color: #e11d48; }
.sev-icon-مرتفع { background: #ffedd5; color: #c2410c; }
.sev-icon-متوسط { background: #fef3c7; color: #b45309; }
.sev-icon-منخفض { background: #e0f2fe; color: #0369a1; }
.alert-meta { display: flex; flex-wrap: wrap; gap: .25rem 1rem; margin-top: .5rem; font-size: .72rem; color: hsl(var(--muted-foreground)); }

.notif-card { display: flex; align-items: flex-start; gap: .75rem; border-radius: .75rem; border: 1px solid hsl(var(--border)); background: hsl(var(--card)); padding: 1rem; margin-bottom: .625rem; }
.notif-card.is-unread { border-color: hsl(var(--primary) / .35); box-shadow: 0 0 0 1px hsl(var(--primary) / .12); }
.notif-card .n-icon { display: flex; height: 2.25rem; width: 2.25rem; flex-shrink: 0; align-items: center; justify-content: center; border-radius: .5rem; }
.notif-card .n-body { margin-top: .375rem; font-size: .78rem; line-height: 1.9; color: hsl(var(--muted-foreground)); white-space: pre-line; }
.type-طلب { background: #f0f9ff; color: #0369a1; }
.type-اعتماد { background: #f5f3ff; color: #6d28d9; }
.type-تذكير { background: #fffbeb; color: #b45309; }
.type-أخرى { background: hsl(var(--muted)); color: hsl(var(--muted-foreground)); }

.settings-grid { display: grid; gap: 1rem; grid-template-columns: 1fr; }
@media (min-width: 900px) { .settings-grid { grid-template-columns: repeat(2, 1fr); } }
.settings-panel { border-radius: .75rem; border: 1px solid hsl(var(--border)); background: hsl(var(--card)); padding: 1.25rem; }
.settings-panel .p-head { display: flex; align-items: center; gap: .5rem; margin-bottom: 1rem; }
.settings-panel .p-head svg { width: 18px; height: 18px; color: hsl(var(--primary)); }
.settings-panel .p-head h3 { font-size: .875rem; font-weight: 700; }
.set-row { display: flex; align-items: center; justify-content: space-between; gap: 1rem; border-bottom: 1px solid hsl(var(--border)); padding-bottom: .5rem; margin-bottom: .5rem; font-size: .82rem; }
.set-row:last-child { border-bottom: 0; padding-bottom: 0; margin-bottom: 0; }
.set-row .s-label { color: hsl(var(--muted-foreground)); }
.set-row .s-value { font-weight: 600; text-align: left; }
.set-row .s-value.ok { color: #047857; }
html.dark .set-row .s-value.ok { color: hsl(152 60% 72%); }
.switch { position: relative; display: inline-block; height: 1.5rem; width: 2.75rem; flex-shrink: 0; }
.switch input { position: absolute; opacity: 0; height: 100%; width: 100%; margin: 0; cursor: pointer; }
.switch span { position: absolute; inset: 0; border-radius: 9999px; background: hsl(var(--muted)); box-shadow: inset 0 0 0 1px hsl(var(--border)); transition: background .2s; pointer-events: none; }
.switch span::after { content: ''; position: absolute; top: .175rem; right: .175rem; height: 1.15rem; width: 1.15rem; border-radius: 9999px; background: #fff; box-shadow: 0 1px 3px rgba(0,0,0,.2); transition: transform .2s; }
.switch input:checked + span { background: hsl(var(--primary)); }
.switch input:checked + span::after { transform: translateX(-1.25rem); }

/* لوحة الحكومة — شاشة الاختيار بمربّعاتها، ووضع العرض على شاشة القاعة */
.screen-launcher { display: flex; flex-direction: column; gap: clamp(1rem, 2vh, 2rem); }
.screen-launcher-head { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 1rem; }
.screen-launcher-head .kicker { font-size: .72rem; font-weight: 700; letter-spacing: .08em; color: hsl(var(--muted-foreground)); }
.screen-launcher-head h1 { margin-top: .25rem; font-size: clamp(1.35rem, 2.2vw, 2.25rem); font-weight: 800; letter-spacing: -.01em; }

.screen-grid { display: grid; gap: clamp(.75rem, 1.2vw, 1.5rem); grid-template-columns: 1fr; }
@media (min-width: 720px) { .screen-grid { grid-template-columns: repeat(2, 1fr); } }
@media (min-width: 1280px) {
    .screen-grid { grid-template-columns: repeat(3, 1fr); }
    /* عدد المربّعات لا يقسم على ثلاثة: يتمدّد الأول عمودين فلا يبقى صفٌّ ناقصًا. */
    .screen-grid > :first-child:nth-last-child(3n + 2) { grid-column: span 2; }
}
.screen-tile {
    display: flex; flex-direction: column; gap: .75rem;
    min-height: clamp(9rem, 21vh, 16rem);
    border-radius: 1.25rem; border: 1px solid hsl(var(--border));
    background: hsl(var(--card)); padding: clamp(1rem, 1.6vw, 1.75rem); color: inherit;
    box-shadow: 0 1px 2px hsl(var(--foreground) / .04), 0 10px 28px -16px hsl(var(--foreground) / .5);
    transition: transform .18s ease, border-color .18s ease, box-shadow .18s ease;
}
.screen-tile:hover, .screen-tile:focus-visible {
    outline: none; transform: translateY(-3px);
    border-color: hsl(var(--primary) / .55);
    box-shadow: 0 18px 40px -20px hsl(var(--primary) / .6);
}
.screen-tile .t-top { display: flex; align-items: center; justify-content: space-between; gap: .5rem; }
.screen-tile .t-icon {
    display: flex; align-items: center; justify-content: center;
    height: clamp(3rem, 3.6vw, 4.5rem); width: clamp(3rem, 3.6vw, 4.5rem);
    border-radius: 1rem; background: hsl(var(--primary) / .1); color: hsl(var(--primary));
    box-shadow: inset 0 0 0 1px hsl(var(--primary) / .16);
}
.screen-tile .t-icon svg { width: 52%; height: 52%; }
.screen-tile .t-group { font-size: .7rem; font-weight: 700; letter-spacing: .06em; color: hsl(var(--muted-foreground) / .9); }
.screen-tile h2 { font-size: clamp(1.05rem, 1.4vw, 1.6rem); font-weight: 800; }
.screen-tile p { margin-top: .35rem; font-size: clamp(.75rem, .8vw, 1rem); line-height: 1.8; color: hsl(var(--muted-foreground)); }
.screen-tile .t-go { display: flex; align-items: center; gap: .35rem; margin-top: auto; font-size: .8rem; font-weight: 700; color: hsl(var(--primary)); }
.screen-tile .t-go svg { width: 16px; height: 16px; }

/* قياس الجذر يكبر مع الشاشة، وبقية اللوحة مبنية على rem فتكبر معه. */
html.screen-mode { font-size: clamp(16px, 1vw, 22px); }
html.screen-mode .main { margin-right: 0; }
html.screen-mode .content { padding: clamp(1rem, 2vw, 2.5rem); }
html.screen-mode .chart-wrap { height: clamp(260px, 32vh, 520px); }
html.screen-mode .screen-launcher { min-height: calc(100dvh - 2 * clamp(1rem, 2vw, 2.5rem)); justify-content: center; }
{{-- على شاشة القاعة تملأ المربّعات الارتفاع بدل أن تتكوّم في أعلاها. --}}
html.screen-mode .screen-grid { flex: 1; grid-auto-rows: 1fr; }

.screen-bar {
    position: fixed; top: 1rem; left: 1rem; z-index: 60;
    display: flex; align-items: center; gap: .25rem;
    border-radius: 9999px; border: 1px solid hsl(var(--border));
    background: hsl(var(--card) / .9); backdrop-filter: saturate(180%) blur(12px);
    padding: .25rem; box-shadow: 0 10px 26px -14px hsl(var(--foreground) / .6);
    opacity: .4; transition: opacity .2s;
}
.screen-bar:hover, .screen-bar:focus-within { opacity: 1; }
.screen-bar a, .screen-bar button {
    display: inline-flex; align-items: center; gap: .35rem;
    border: 0; background: none; cursor: pointer; color: hsl(var(--foreground));
    border-radius: 9999px; padding: .45rem .7rem;
    font-family: inherit; font-size: .8rem; font-weight: 600;
}
.screen-bar a:hover, .screen-bar button:hover { background: hsl(var(--muted)); }
.screen-bar svg { width: 18px; height: 18px; }
.fs-btn .fs-off, .fs-btn.is-full .fs-on { display: none; }
.fs-btn.is-full .fs-off { display: inline-flex; }
</style>