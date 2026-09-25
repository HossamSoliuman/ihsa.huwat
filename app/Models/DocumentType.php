<?php

namespace App\Models;

/**
 * قائمة مرجعية — جدول document_types (استمارة القارب، إقامة، جواز سفر…).
 */
class DocumentType extends LookupModel
{
    protected $table = 'document_types';
}
