<?php

namespace App\Services\Dalal;

use App\Models\AuditLog;
use App\Models\DalalInvoiceReview;
use App\Models\User;
use App\Services\Notifications\Notifier;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * مراجعة فواتير الدلال بين الطرفين: المالك يقبل سطور مصيده في الفاتورة أو
 * يرفضها بسبب، والدلال يردّ على المرفوضة فتعود إلى مراجعة المالك.
 *
 * القبول نهائي (لا يُرفض بعده)؛ المرفوضة تُقبل بعد التسوية أو بعد ردّ الدلال.
 * المراجعة لا تمسّ الأرقام: صافي المالك يبقى مستحقًا عليه حتى يُصحَّح.
 */
class InvoiceReviewService
{
    public function __construct(private readonly Notifier $notifier) {}

    public function accept(User $owner, DalalInvoiceReview $review): DalalInvoiceReview
    {
        if ($review->isAccepted()) {
            throw ValidationException::withMessages(['review' => 'الفاتورة مقبولة بالفعل.']);
        }

        $review->update(['status' => DalalInvoiceReview::ACCEPTED, 'reviewed_by' => $owner->id, 'reviewed_at' => now()]);
        $this->notifier->invoiceReviewed($review->load('sale', 'owner', 'dalal'));
        $this->log('قبول فاتورة دلال', $owner, $review, "قبول الفاتورة {$review->sale->invoice_number} من {$review->dalal?->name}");

        return $review;
    }

    public function reject(User $owner, DalalInvoiceReview $review, string $reason): DalalInvoiceReview
    {
        if (! $review->isPending()) {
            throw ValidationException::withMessages(['reason' => $review->isAccepted()
                ? 'لا تُرفض فاتورة قبلتها.'
                : 'الفاتورة مرفوضة بالفعل — بانتظار ردّ الدلال.']);
        }

        $review->update(['status' => DalalInvoiceReview::REJECTED, 'reason' => $reason, 'reviewed_by' => $owner->id, 'reviewed_at' => now()]);
        $this->notifier->invoiceReviewed($review->load('sale', 'owner', 'dalal'));
        $this->log('رفض فاتورة دلال', $owner, $review, "رفض الفاتورة {$review->sale->invoice_number} من {$review->dalal?->name}: {$reason}");

        return $review;
    }

    /**
     * يقبل كل فواتير المالك قيد المراجعة (من دلال واحد إن حُدِّد). المرفوضة
     * لا تدخل — كلٌّ منها تُقبل وحدها بعد النظر في سببها.
     */
    public function acceptPending(User $owner, ?User $dalal = null): int
    {
        $reviews = DalalInvoiceReview::forOwner($owner)->pending()
            ->when($dalal, fn ($q) => $q->where('dalal_id', $dalal->id))
            ->with('sale', 'owner', 'dalal')
            ->get();

        DB::transaction(function () use ($owner, $reviews) {
            foreach ($reviews as $review) {
                $review->update(['status' => DalalInvoiceReview::ACCEPTED, 'reviewed_by' => $owner->id, 'reviewed_at' => now()]);
                $this->notifier->invoiceReviewed($review);
            }
        });

        if ($reviews->isNotEmpty()) {
            AuditLog::create([
                'user_email' => $owner->email ?? $owner->phone,
                'role' => $owner->app_role_key ?? 'owner',
                'action' => 'قبول فواتير دلال',
                'entity' => 'DalalInvoiceReview',
                'record_label' => (string) $reviews->count(),
                'details' => 'قبول '.$reviews->count().' فاتورة: '.$reviews->map(fn ($r) => $r->sale->invoice_number)->implode('، '),
                'ip' => request()?->ip(),
            ]);
        }

        return $reviews->count();
    }

    /**
     * الدلال يردّ على رفض المالك (صحّح بالتواصل، أو يوضّح السعر) فتعود
     * الفاتورة قيد المراجعة ويُبلَّغ المالك.
     */
    public function reply(User $dalal, DalalInvoiceReview $review, string $reply): DalalInvoiceReview
    {
        if (! $review->isRejected()) {
            throw ValidationException::withMessages(['reply' => 'الردّ على فاتورة رفضها المالك فقط.']);
        }

        $review->update(['status' => DalalInvoiceReview::PENDING, 'dalal_reply' => $reply, 'replied_at' => now()]);
        $this->notifier->invoiceReplied($review->load('sale', 'owner', 'dalal'));
        $this->log('رد على رفض فاتورة', $dalal, $review, "رد على رفض {$review->owner?->name} للفاتورة {$review->sale->invoice_number}: {$reply}");

        return $review;
    }

    private function log(string $action, User $by, DalalInvoiceReview $review, string $details): void
    {
        AuditLog::create([
            'user_email' => $by->email ?? $by->phone,
            'role' => $by->app_role_key,
            'action' => $action,
            'entity' => 'DalalInvoiceReview',
            'record_label' => (string) $review->sale?->invoice_number,
            'details' => $details,
            'ip' => request()?->ip(),
        ]);
    }
}
