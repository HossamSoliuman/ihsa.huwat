{{--
    إعداد Chart.js الموحّد: يُحمَّل مرّة في أعلى كل صفحة ذات رسوم، فتقرأ رسوم
    اللوحة كلّها الخطّ والألوان والفواصل نفسها بدل أن تكرّرها صفحةً صفحة.

    الألوان تُقرأ من متغيّرات CSS نفسها التي تلوّن البطاقات، ولوحة الفئات
    مُتحقَّق منها في الوضعين (فصل عمى الألوان وتباين السطح).
--}}
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
(function () {
    const dark = document.documentElement.classList.contains('dark');
    const css = getComputedStyle(document.documentElement);
    const token = (name, alpha) => 'hsl(' + css.getPropertyValue(name).trim() + ' / ' + alpha + ')';

    /*
     * لوحة الفئات: ثمانية ألوان بترتيب ثابت لا يُدوَّر. الترتيب هو ما يضمن
     * انفصال كل لونين متجاورين لقارئ عمى الألوان — فلا يُعاد ترتيبها.
     */
    const categorical = dark
        ? ['#3f96e0', '#d95926', '#199e70', '#c98500', '#d55181', '#008300', '#9085e9', '#e66767']
        : ['#1d6fb8', '#eb6834', '#1baf7a', '#eda100', '#e87ba4', '#008300', '#4a3aa7', '#e34948'];

    /* تدرّج المقدار: لونٌ واحد من الفاتح إلى الغامق — لا قوس قزح. */
    const sequential = dark
        ? ['#0e3d68', '#155690', '#1d6fb8', '#3f96e0', '#7bb0e3', '#a6cbee']
        : ['#0e3d68', '#155690', '#1d6fb8', '#3f96e0', '#7bb0e3', '#a6cbee'];

    /*
     * ألوان الحالة محجوزة: جيّد / تنبيه / حرج / محايد. لا تُستعمل لتمييز سلسلة
     * عن أخرى، ولكلّ وضعٍ درجاته حتى يبقى التباين على سطحه فوق 3:1.
     */
    const status = dark
        ? { good: '#3cb98a', warn: '#e0a53c', critical: '#ef5f7a', neutral: '#3f96e0', none: '#64748b' }
        : { good: '#0f7a5a', warn: '#b45309', critical: '#d61f47', neutral: '#1d6fb8', none: '#94a3b8' };

    window.hawatChart = {
        status,
        /* يحوّل قائمة مفاتيح حالة إلى ألوانها في الوضع الحالي. */
        statusColors: (keys) => keys.map((k) => status[k] ?? status.none),
        categorical,
        sequential,
        accent: categorical[0],
        /* لون التعبئة تحت الخطّ — الشفافية تُبقي خطوط الشبكة مقروءة تحته. */
        accentFill: dark ? 'rgba(63,150,224,.16)' : 'rgba(29,111,184,.12)',
        /* يعيد n لونًا من لوحة الفئات دون تدوير: ما زاد عن الثمانية يُطوى. */
        colors: (n) => categorical.slice(0, Math.max(0, n)),
        /* تدرّج بـ n خطوة من التدرّج الأحادي، موزّعة على مداه. */
        ramp: (n) => Array.from({ length: n }, (_, i) =>
            sequential[Math.round(i * (sequential.length - 1) / Math.max(1, n - 1))]),
    };

    Chart.defaults.font.family = "'Chakra Petch', 'Tajawal', sans-serif";
    Chart.defaults.font.size = 11;
    Chart.defaults.color = token('--muted-foreground', 1);
    Chart.defaults.borderColor = token('--border', .55);
    Chart.defaults.maintainAspectRatio = false;

    /* علامات رفيعة وشبكة متراجعة: البيانات هي ما يُرى، لا إطارها. */
    Chart.defaults.elements.bar.borderRadius = 0;
    /* سقف لسماكة العمود: لوحة بثلاث فئات لا تصير ثلاثة ألواح عريضة. */
    Chart.defaults.datasets.bar.maxBarThickness = 26;
    Chart.defaults.elements.line.borderWidth = 2;
    Chart.defaults.elements.line.tension = .3;
    Chart.defaults.elements.point.radius = 2.5;
    Chart.defaults.elements.point.hoverRadius = 5;
    Chart.defaults.elements.arc.borderWidth = 2;
    /* الفاصل بين قطاعي الحلقة بلون الصفحة: البطاقة شفّافة فلا سطح لها يُقتبس. */
    Chart.defaults.elements.arc.borderColor = token('--background', 1);

    Chart.defaults.scale.grid.color = token('--border', .45);
    Chart.defaults.scale.grid.drawTicks = false;
    Chart.defaults.scale.border.display = false;
    Chart.defaults.scale.ticks.padding = 6;
    /* التسميات أفقية دائمًا؛ ما لا يتّسع يُتخطّى بدل أن يُمال. */
    Chart.defaults.scale.ticks.maxRotation = 0;
    Chart.defaults.scale.ticks.autoSkipPadding = 10;

    Chart.defaults.plugins.legend.position = 'bottom';
    Chart.defaults.plugins.legend.labels.boxWidth = 9;
    Chart.defaults.plugins.legend.labels.boxHeight = 9;
    Chart.defaults.plugins.legend.labels.padding = 14;
    Chart.defaults.plugins.legend.labels.usePointStyle = false;

    /* التلميح بزوايا قائمة كبقية اللوحة. */
    Chart.defaults.plugins.tooltip.backgroundColor = token('--foreground', .93);
    Chart.defaults.plugins.tooltip.titleColor = token('--background', 1);
    Chart.defaults.plugins.tooltip.bodyColor = token('--background', 1);
    Chart.defaults.plugins.tooltip.borderWidth = 0;
    Chart.defaults.plugins.tooltip.cornerRadius = 0;
    Chart.defaults.plugins.tooltip.padding = 9;
    Chart.defaults.plugins.tooltip.displayColors = false;
    Chart.defaults.plugins.tooltip.titleFont = { weight: '700' };
})();
</script>
