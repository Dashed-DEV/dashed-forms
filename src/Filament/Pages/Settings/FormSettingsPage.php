<?php

namespace Qubiqx\QcommerceForms\Filament\Pages\Settings;

use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Tabs\Tab;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use Qubiqx\QcommerceCore\Classes\Sites;
use Qubiqx\QcommerceCore\Models\Customsetting;
use Qubiqx\QcommerceCore\Models\User;

class FormSettingsPage extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-bell';
    protected static bool $shouldRegisterNavigation = false;
    protected static ?string $navigationLabel = 'Formulier instellingen';
    protected static ?string $navigationGroup = 'Overige';
    protected static ?string $title = 'Formulier instellingen';

    protected static string $view = 'qcommerce-core::settings.pages.default-settings';

    public function mount(): void
    {
        $formData = [];

        $sites = Sites::getSites();
        foreach ($sites as $site) {
            $formData["notification_form_inputs_emails_{$site['id']}"] = json_decode(Customsetting::get('notification_form_inputs_emails', $site['id'], '{}'));
            $formData["form_activecampaign_url_{$site['id']}"] = Customsetting::get('form_activecampaign_url', $site['id']);
            $formData["form_activecampaign_key_{$site['id']}"] = Customsetting::get('form_activecampaign_key', $site['id']);
        }

        $this->form->fill($formData);
    }

    protected function getFormSchema(): array
    {
        $sites = Sites::getSites();
        $tabGroups = [];

        $tabs = [];
        foreach ($sites as $site) {
            $schema = [
                Placeholder::make('label')
                    ->label("Formulier instellingen voor {$site['name']}")
                    ->content('Stel extra opties in voor de formulieren.'),
                TagsInput::make("notification_form_inputs_emails_{$site['id']}")
                    ->suggestions(User::where('role', 'admin')->pluck('email')->toArray())
                    ->label('Emails om de bevestigingsmail van een formulier aanvraag naar te sturen')
                    ->placeholder('Voer een email in')
                    ->reactive(),
                TextInput::make("form_activecampaign_url_{$site['id']}")
                    ->label('ActiveCampaign API url')
                    ->reactive(),
                TextInput::make("form_activecampaign_key_{$site['id']}")
                    ->label('ActiveCampaign API key')
                    ->reactive(),
            ];

            $tabs[] = Tab::make($site['id'])
                ->label(ucfirst($site['name']))
                ->schema($schema)
                ->columns([
                    'default' => 1,
                    'lg' => 2,
                ]);
        }
        $tabGroups[] = Tabs::make('Sites')
            ->tabs($tabs);

        return $tabGroups;
    }

    public function submit()
    {
        $sites = Sites::getSites();
        $formState = $this->form->getState();

        foreach ($sites as $site) {
            $emails = $this->form->getState()["notification_form_inputs_emails_{$site['id']}"];
            foreach ($emails as $key => $email) {
                if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    unset($emails[$key]);
                }
            }
            Customsetting::set('notification_form_inputs_emails', json_encode($emails), $site['id']);
            $formState["notification_form_inputs_emails_{$site['id']}"] = $emails;

            Customsetting::set('form_activecampaign_url', $this->form->getState()["form_activecampaign_url_{$site['id']}"], $site['id']);
            Customsetting::set('form_activecampaign_key', $this->form->getState()["form_activecampaign_key_{$site['id']}"], $site['id']);
        }

        $this->form->fill($formState);
        $this->notify('success', 'De formulier instellingen zijn opgeslagen');
    }
}
