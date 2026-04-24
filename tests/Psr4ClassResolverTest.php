<?php

use Shelfwood\N8n\Support\Psr4ClassResolver;

function makeFakeBase(array $psr4, array $psr4Dev = []): string
{
    $dir = sys_get_temp_dir().'/laravel-n8n-psr4-'.uniqid();
    mkdir($dir, 0777, true);
    $composer = [
        'autoload' => ['psr-4' => $psr4],
        'autoload-dev' => ['psr-4' => $psr4Dev],
    ];
    file_put_contents($dir.'/composer.json', json_encode($composer));

    return $dir;
}

beforeEach(function () {
    Psr4ClassResolver::flush();
});

it('resolves classes under App\\ → app/ (regression)', function () {
    $base = makeFakeBase(['App\\' => 'app/']);

    expect(Psr4ClassResolver::resolve($base.'/app/Events/Foo.php', $base))
        ->toBe('App\\Events\\Foo');
});

it('resolves classes under Modules\\ → modules/ (regression)', function () {
    $base = makeFakeBase(['Modules\\' => 'modules/']);

    expect(Psr4ClassResolver::resolve($base.'/modules/Catalog/Events/Bar.php', $base))
        ->toBe('Modules\\Catalog\\Events\\Bar');
});

it('resolves classes under Domain\\ → src/Domain/', function () {
    $base = makeFakeBase(['Domain\\' => 'src/Domain/']);

    expect(Psr4ClassResolver::resolve($base.'/src/Domain/Redesign/Events/Baz.php', $base))
        ->toBe('Domain\\Redesign\\Events\\Baz');
});

it('resolves classes under Infra\\ → src/Infra/ (no trailing slash in json)', function () {
    $base = makeFakeBase(['Infra\\' => 'src/Infra']);

    expect(Psr4ClassResolver::resolve($base.'/src/Infra/Ai/Telemetry/Quux.php', $base))
        ->toBe('Infra\\Ai\\Telemetry\\Quux');
});

it('returns null for unknown paths', function () {
    $base = makeFakeBase(['App\\' => 'app/']);

    expect(Psr4ClassResolver::resolve($base.'/random/unknown/File.php', $base))
        ->toBeNull();
});

it('chooses longest prefix when multiple namespaces match', function () {
    $base = makeFakeBase([
        'App\\' => 'src/',
        'App\\Domain\\' => 'src/Domain/',
    ]);

    expect(Psr4ClassResolver::resolve($base.'/src/Domain/Redesign/Events/Baz.php', $base))
        ->toBe('App\\Domain\\Redesign\\Events\\Baz');
});
