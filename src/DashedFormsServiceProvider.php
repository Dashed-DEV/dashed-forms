<?php

namespace Dashed\DashedForms;

use Livewire\Livewire;
use Dashed\DashedForms\Livewire\Form;
use Spatie\LaravelPackageTools\Package;
use Illuminate\Console\Scheduling\Schedule;
use Dashed\DashedForms\Commands\SendApisForFormInputs;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Dashed\DashedForms\Commands\SendWebhooksForFormInputs;
use Dashed\DashedForms\Filament\Pages\Settings\FormSettingsPage;

class DashedFormsServiceProvider extends PackageServiceProvider
{
    public static string $name = 'dashed-forms';

    public function bootingPackage()
    {
        Livewire::component('dashed-forms.form', Form::class);

        $this->app->booted(function () {
            $schedule = app(Schedule::class);
            $schedule->command(SendWebhooksForFormInputs::class)->everyMinute();
            $schedule->command(SendApisForFormInputs::class)->everyMinute();
        });
    }

    public function configurePackage(Package $package): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        $this->publishes([
            __DIR__ . '/../resources/templates' => resource_path('views/' . env('SITE_THEME', 'dashed')),
            __DIR__ . '/../resources/component-templates' => resource_path('views/components'),
        ], 'dashed-templates');

        cms()->registerSettingsPage(FormSettingsPage::class, 'Formulier instellingen', 'bell', 'Beheer instellingen voor de formulieren');

        $package
            ->name('dashed-forms')
            ->hasRoutes([
                'frontend',
            ])
            ->hasCommands([
                SendWebhooksForFormInputs::class,
                SendApisForFormInputs::class,
            ])
            ->hasViews();

        cms()->builder('plugins', [
            new DashedFormsPlugin(),
        ]);
    }
}
