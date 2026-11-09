<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use Tests\TestCase;

class StructureTest extends TestCase
{
    public function test_application_route_handlers_exist_and_are_public(): void
    {
        foreach (Route::getRoutes() as $route) {
            $action = $route->getActionName();
            if (! str_starts_with($action, 'App\\Http\\Controllers\\')) {
                continue;
            }

            [$controller, $method] = explode('@', $action);
            $this->assertTrue(class_exists($controller), $action);
            $this->assertTrue(method_exists($controller, $method), $action);
            $this->assertTrue((new \ReflectionMethod($controller, $method))->isPublic(), $action);
        }
    }

    public function test_controller_and_blade_view_references_resolve(): void
    {
        $files = array_merge(
            File::allFiles(app_path('Http/Controllers')),
            File::allFiles(resource_path('views')),
        );

        foreach ($files as $file) {
            preg_match_all('/(?:\bview|@extends|@include|->links)\(\s*[\'"]([^\'"]+)[\'"]/', $file->getContents(), $matches);
            foreach ($matches[1] as $view) {
                $this->assertTrue(View::exists($view), $file->getFilename().': '.$view);
            }
        }
    }

    public function test_vite_entrypoints_and_built_assets_exist(): void
    {
        $manifestPath = public_path('build/manifest.json');
        $this->assertFileExists($manifestPath);
        $manifest = json_decode(File::get($manifestPath), true, 512, JSON_THROW_ON_ERROR);

        foreach (['resources/css/app.css', 'resources/js/app.js'] as $entry) {
            $this->assertFileExists(base_path($entry));
            $this->assertArrayHasKey($entry, $manifest);
            $this->assertTrue($manifest[$entry]['isEntry']);
        }

        foreach ($manifest as $asset) {
            $this->assertFileExists(public_path('build/'.$asset['file']));
            foreach (array_merge($asset['css'] ?? [], $asset['assets'] ?? []) as $file) {
                $this->assertFileExists(public_path('build/'.$file));
            }
        }
    }

    public function test_login_and_registration_views_render(): void
    {
        $this->get(route('login'))->assertOk()->assertViewIs('login');
        $this->get(route('register'))->assertOk()->assertViewIs('login');
    }
}
