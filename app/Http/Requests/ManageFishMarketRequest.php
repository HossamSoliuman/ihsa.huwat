<?php

namespace App\Http\Requests;

/**
 * Shared base for every fish market screen: the markets, their shops and stalls, their
 * workers and their brokers all answer to the one gate. Subclasses that carry no payload —
 * the delete actions — need nothing beyond the authorisation check.
 */
class ManageFishMarketRequest extends AccessInformationDeskRequest {}
