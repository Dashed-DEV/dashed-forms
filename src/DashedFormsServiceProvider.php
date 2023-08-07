<?php

namespace Dashed\DashedForms;

use Filament\PluginServiceProvider;
use Livewire\Livewire;
use Dashed\DashedForms\Filament\Pages\Settings\FormSettingsPage;
use Dashed\DashedForms\Filament\Resources\FormResource;
use Dashed\DashedForms\Livewire\Form;
use Spatie\LaravelPackageTools\Package;

class DashedFormsServiceProvider extends PluginServiceProvider
{
    public static string $name = 'dashed-forms';

    public function bootingPackage()
    {
        Livewire::component('dashed-forms.form', Form::class);
    }

    public function configurePackage(Package $package): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        cms()->builder(
            'settingPages',
            array_merge(cms()->builder('settingPages'), [
                'formNotifications' => [
                    'name' => 'Formulier instellingen',
                    'description' => 'Beheer instellingen voor de formulieren',
                    'icon' => 'bell',
                    'page' => FormSettingsPage::class,
                ],
            ])
        );

        $package
            ->name('dashed-forms')
            ->hasRoutes([
                'frontend',
            ])
            ->hasViews();

    }

    protected function getPages(): array
    {
        return array_merge(parent::getPages(), [
            FormSettingsPage::class,
        ]);
    }

    protected function getResources(): array
    {
        return array_merge(parent::getResources(), [
            FormResource::class,
        ]);
    }
}
