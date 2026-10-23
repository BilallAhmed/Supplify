<?php

namespace App\Services\Assistant;

/** Conservative intent detection; never determines product suitability. */
class AssistantMedicalGuard
{
    public function classify(string $message): ?string
    {
        $text = mb_strtolower(strip_tags($message));
        if (preg_match('/\b(overdos(?:e|ed|ing)|swallowed\s+\d+\s+(?:pills|tablets|capsules)|chest pain|difficulty breathing|cannot breathe|can.t breathe|trouble breathing|suicid\w*)\b|سانس.*(?:تکلیف|مشکل)|زیادہ گولیاں/u', $text)) {
            return 'urgent';
        }
        // Only ignore this explicit introductory negation for a purely factual request.
        if (preg_match('/^i am not pregnant[.!]\s*(?:what is (?:the )?.*price|how much .*cost)\??$/u', $text)) {
            $text = preg_replace('/^i am not pregnant[.!]\s*/u', '', $text);
        }
        if (preg_match('/\b(pregnan\w*|breastfeed\w*|nursing|toddler|infant|baby|babies|child|children|kid|kids|\d+\s*(?:year|month)[ -]old|warfarin|metformin|insulin|blood thinners?|medications?|prescri\w*|allerg\w*|diabet\w*|kidney disease|liver disease|chemotherapy)\b|حاملہ|حمل|بچوں|بچے|شوگر|ذیابیطس|دوائی|دوا کے ساتھ|گردے/u', $text)) {
            return 'medical';
        }
        if (preg_match('/\b(cure|treat|diagnos\w*|safe for|suitable for|can i take|should i take|how many.*(?:take|give)|how much.*(?:take|give)|what dose|dosage for|combine.*(?:with|and)|interact\w*)\b|kitni.*(?:leni|loon|lun|do)|kitna.*(?:loon|lun|lena)|لے سکتا|لینی چاہیے|کتنی.*(?:لوں|لینی)/u', $text)) {
            return 'medical';
        }

        return null;
    }
}
