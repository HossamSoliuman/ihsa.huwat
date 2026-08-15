<div class="section-head">
    <div>
        <h2>الاستيراد الجماعي</h2>
        <p>استيراد البيانات الأساسية من ملفات CSV أو Excel مع التحقق قبل الإدخال.</p>
    </div>
</div>

<div class="notice">
    @include('admin.partials.icon', ['name' => 'upload'])
    <p>يُقبل الاستيراد للبيانات الأساسية فقط. الصف الأول يجب أن يحتوي أسماء الأعمدة كما هي موضحة في الجدول أدناه.</p>
</div>

<div class="table-wrap">
    <table>
        <thead>
            <tr>
                <th>الجدول</th>
                <th>السجلات الحالية</th>
                <th>الأعمدة المطلوبة</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($data['targets'] as $target)
                <tr>
                    <td>{{ $target['label'] }}</td>
                    <td>{{ number_format($target['count']) }}</td>
                    <td style="direction:ltr;text-align:left;font-size:12px;">{{ $target['columns'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>