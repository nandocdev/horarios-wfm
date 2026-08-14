<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\File;

it('cada selector data-tour de definitions.js existe en alguna vista de modulo', function () {
    $definitions = File::get(base_path('resources/js/tours/definitions.js'));

    preg_match_all('/\[data-tour="([^"]+)"\]/', $definitions, $matches);

    $selectors = array_values(array_unique($matches[1]));

    expect($selectors)->not->toBeEmpty();

    $bladeContents = collect(File::allFiles(base_path('app/Modules')))
        ->filter(fn (SplFileInfo $file) => str_ends_with($file->getFilename(), '.blade.php'))
        ->map(fn (SplFileInfo $file) => $file->getContents())
        ->implode("\n");

    foreach ($selectors as $selector) {
        expect(
            $bladeContents,
            "Selector data-tour=\"{$selector}\" definido en definitions.js pero inexistente en las vistas de app/Modules"
        )->toContain(sprintf('data-tour="%s"', $selector));
    }
});

it('todo tour referenciado desde una vista esta definido en definitions.js', function () {
    $definitions = File::get(base_path('resources/js/tours/definitions.js'));

    preg_match_all("/'([a-z0-9.\-]+)':\s*\{/", $definitions, $matches);

    $definedKeys = array_values(array_unique($matches[1]));

    expect($definedKeys)->not->toBeEmpty();

    $bladeContents = collect(File::allFiles(base_path('app/Modules')))
        ->filter(fn (SplFileInfo $file) => str_ends_with($file->getFilename(), '.blade.php'))
        ->map(fn (SplFileInfo $file) => $file->getContents())
        ->implode("\n");

    preg_match_all('/[:\s]tour="\'?([a-z0-9.\-]+)\'?"/', $bladeContents, $refs);

    $referencedKeys = array_values(array_unique($refs[1]));

    foreach ($referencedKeys as $key) {
        expect($definedKeys, "Tour \"{$key}\" referenciado en una vista pero sin definicion en definitions.js")
            ->toContain($key);
    }
});

it('todo tour en definitions.js declara su version numerica', function () {
    $definitions = File::get(base_path('resources/js/tours/definitions.js'));

    preg_match_all("/\n    '([a-z0-9.\-]+)': \{(.*?)\n    \},/s", $definitions, $matches);

    $tours = array_combine($matches[1], $matches[2]);

    expect($tours)->not->toBeEmpty();

    foreach ($tours as $key => $block) {
        preg_match('/version:\s*(\d+)/', $block, $version);

        expect(
            $version[1] ?? null,
            "Tour \"{$key}\" sin campo version numerico en definitions.js"
        )->not->toBeNull();

        expect((int) $version[1])->toBeGreaterThanOrEqual(1);
    }
});

it('el page-header propaga data-tour y renderiza el boton de guia', function () {
    $html = Blade::render(
        '<x-wfm.page-header title="Prueba" tour="my-schedule" data-tour="my-schedule-header" />'
    );

    expect($html)
        ->toContain('data-tour="my-schedule-header"')
        ->toContain("window.startWfmTour('my-schedule')");
});
