@props(['type', 'title', 'count'])

<section class="info-form-card info-admin-panel info-lookup-panel" aria-labelledby="reference-{{ $type }}">
    <div class="info-admin-panel-head">
        <h2 id="reference-{{ $type }}">{{ $title }}</h2>
        <small class="info-lookup-count">{{ $count }} سجل</small>
    </div>

    <form class="info-lookup-form" method="post" action="{{ route('information.admin.lookups.references.store', $type) }}">
        @csrf
        {{ $form }}

        <button class="info-button info-button-primary" type="submit">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 5v14M5 12h14"></path></svg>
            <span>إضافة</span>
        </button>
    </form>

    <div class="info-admin-table-scroll">
        <table class="info-admin-table info-lookup-table">
            {{ $table }}
        </table>
    </div>
</section>
