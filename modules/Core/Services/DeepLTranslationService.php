<?php

namespace Modules\Core\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Modules\Core\Contracts\Services\TextTranslationServiceInterface;
use Modules\Core\DTOs\Outputs\TranslatedTextOutput;
use Modules\Core\Exceptions\ExternalServiceException;

final class DeepLTranslationService implements TextTranslationServiceInterface
{
    public function translateFrenchToEnglish(string $title, ?string $slug, ?string $description): TranslatedTextOutput
    {
        $texts = [$title];
        $hasSlug = $slug !== null && $slug !== '';
        $hasDescription = $description !== null && $description !== '';

        if ($hasSlug) {
            $texts[] = str_replace('-', ' ', $slug);
        }

        if ($hasDescription) {
            $texts[] = $description;
        }

        $key = config('services.deepl.key');

        if (! is_string($key) || $key === '') {
            throw new ExternalServiceException;
        }

        try {
            $baseUrl = rtrim((string) config('services.deepl.url'), '/');
            $endpoint = str_ends_with($baseUrl, '/v2/translate') ? $baseUrl : $baseUrl.'/v2/translate';
            $response = Http::withToken($key, 'DeepL-Auth-Key')->timeout(10)->post($endpoint, [
                'text' => $texts,
                'source_lang' => 'FR',
                'target_lang' => 'EN-US',
            ]);
        } catch (ConnectionException) {
            throw new ExternalServiceException;
        }

        $translations = $response->json('translations');

        if (! $response->successful() || ! is_array($translations) || count($translations) !== count($texts)) {
            throw new ExternalServiceException;
        }

        $translated = array_map(fn (array $item): string => (string) ($item['text'] ?? ''), $translations);
        $position = 1;
        $translatedSlug = $hasSlug ? Str::slug($translated[$position++]) : null;
        $translatedDescription = $hasDescription ? $translated[$position] : null;

        return new TranslatedTextOutput($translated[0], $translatedSlug, $translatedDescription);
    }
}
