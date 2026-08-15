<div class="section-head">
    <div>
        <h2>بيانات Power BI</h2>
        <p>الجداول المتاحة لتغذية Power BI مع أعداد السجلات ودورية التحديث.</p>
    </div>
</div>

<div class="table-wrap">
    <table>
        <thead>
            <tr>
                <th>الجدول</th>
                <th>عدد السجلات</th>
                <th>دورية التحديث</th>
                <th>الحالة</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($data['tables'] as $table)
                <tr>
                    <td style="direction:ltr;text-align:left;">{{ $table['name'] }}</td>
                    <td>{{ number_format($table['rows']) }}</td>
                    <td>{{ $table['refresh'] }}</td>
                    <td>
                        <span class="badge {{ $table['rows'] > 0 ? 'badge-ok' : 'badge-warn' }}">{{ $table['rows'] > 0 ? 'جاهز' : 'فارغ' }}</span>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>