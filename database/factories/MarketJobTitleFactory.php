<?php

namespace Database\Factories;

use App\Models\MarketJobTitle;

/** @extends LookupListFactory<MarketJobTitle> */
class MarketJobTitleFactory extends LookupListFactory
{
    protected function namePrefix(): string
    {
        return 'وظيفة سوق';
    }
}
