<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateTranslationsRequest;
use App\Services\AppSettingsService;
use App\Support\LocaleMessageFile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Inertia\Inertia;
use Inertia\Response;

class TranslationController extends Controller
{
    public function __construct(private readonly AppSettingsService $settings) {}

    public function index(Request $request): Response
    {
        $supported = $this->supportedLocales();
        $locale = $request->query('locale', $supported[0] ?? 'en');
        if (! in_array($locale, $supported, true)) {
            $locale = $supported[0] ?? 'en';
        }

        $messages = $this->readLocaleFile($locale);
        $flat = LocaleMessageFile::flatten($messages);

        return Inertia::render('admin/Translations', [
            'locales' => $supported,
            'activeLocale' => $locale,
            'flat' => $flat,
        ]);
    }

    public function update(UpdateTranslationsRequest $request, string $locale): RedirectResponse
    {
        $supported = $this->supportedLocales();
        abort_unless(in_array($locale, $supported, true), 404);

        /** @var array<string, string|null> $flatInput */
        $flatInput = $request->validated()['flat'] ?? [];
        $flat = [];
        foreach ($flatInput as $key => $value) {
            $flat[$key] = $value ?? '';
        }

        $nested = LocaleMessageFile::unflatten($flat);
        LocaleMessageFile::ksortRecursive($nested);

        $path = lang_path("{$locale}.json");
        $json = json_encode($nested, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        File::put($path, $json."\n");

        return redirect()
            ->route('admin.translations.index', ['locale' => $locale])
            ->with('success', __('admin.translations.saved'));
    }

    /**
     * @return list<string>
     */
    private function supportedLocales(): array
    {
        $raw = $this->settings->get('localization.supported_locales', ['en']);
        if (is_string($raw)) {
            $raw = json_decode($raw, true) ?? ['en'];
        }
        if (! is_array($raw)) {
            $raw = ['en'];
        }
        $locales = array_values(array_unique(array_filter(array_map('strval', $raw))));
        if ($locales === []) {
            $locales = ['en'];
        }

        return $locales;
    }

    /**
     * @return array<string, mixed>
     */
    private function readLocaleFile(string $locale): array
    {
        $path = lang_path("{$locale}.json");
        if (! File::exists($path)) {
            return [];
        }

        $decoded = json_decode(File::get($path), true, 512, JSON_THROW_ON_ERROR);
        if (! is_array($decoded)) {
            return [];
        }

        return $decoded;
    }
}
