<?php

namespace Dashed\DashedForms\Filament\Resources;

use Dashed\DashedForms\Classes\Forms;
use Dashed\DashedForms\Enums\MailingProviders;
use Dashed\DashedForms\Filament\Resources\FormResource\Pages\CreateForm;
use Dashed\DashedForms\Filament\Resources\FormResource\Pages\EditForm;
use Dashed\DashedForms\Filament\Resources\FormResource\Pages\ListForm;
use Dashed\DashedForms\Filament\Resources\FormResource\Pages\ViewForm;
use Dashed\DashedForms\Filament\Resources\FormResource\Pages\ViewFormInput;
use Dashed\DashedForms\Models\FormInput;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Concerns\Translatable;
use Filament\Resources\Form;
use Filament\Resources\Resource;
use Filament\Resources\Table;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Model;

class FormResource extends Resource
{
    use Translatable;

    protected static ?string $model = \Dashed\DashedForms\Models\Form::class;
    protected static ?string $recordTitleAttribute = 'name';

    protected static ?string $navigationIcon = 'heroicon-o-archive';
    protected static ?string $navigationGroup = 'Formulieren';
    protected static ?string $label = 'Formulier';
    protected static ?string $pluralLabel = 'Formulieren';
    protected static bool $isGloballySearchable = false;

    protected static function getNavigationLabel(): string
    {
        return 'Formulieren (' . FormInput::unviewed()->count() . ')';
    }

    public static function form(Form $form): Form
    {
        $schema = [
            TextInput::make('name')
                ->label('Naam')
                ->maxLength(255)
                ->required()
                ->rules([
                    'required',
                    'max:255',
                ]),
            Select::make('email_confirmation_form_field_id')
                ->label('Email bevestiging veld')
                ->options(fn ($record) => $record ? $record->fields()->where('type', 'input')->where('input_type', 'email')->pluck('name', 'id') : []),
        ];

        foreach (MailingProviders::cases() as $provider) {
            $provider = $provider->getClass();
            if ($provider->connected) {
                $schema[] = Toggle::make("external_options.send_to_$provider->slug")
                    ->label('Verstuur naar ' . $provider->name)
                    ->reactive();
                $schema = array_merge($schema, $provider->getFormSchema());
            }
        }

        $repeaterSchema = [
            TextInput::make('name')
                ->label('Naam')
                ->maxLength(255)
                ->required()
                ->rules([
                    'required',
                    'max:255',
                ]),
            Select::make('type')
                ->label('Type veld')
                ->options(Forms::availableInputTypes())
                ->required()
                ->reactive()
                ->rules([
                    'required',
                ]),
            Select::make('input_type')
                ->label('Input type veld')
                ->options(Forms::availableInputTypesForInput())
                ->required(fn ($get) => in_array($get('type'), ['input']))
                ->reactive()
                ->when(fn ($get) => in_array($get('type'), ['input'])),
            TextInput::make('placeholder')
                ->label('Placeholder')
                ->maxLength(255)
                ->when(fn ($get) => in_array($get('type'), ['input', 'textarea']))
                ->rules([
                    'max:255',
                ]),
            TextInput::make('helper_text')
                ->label('Helper tekst')
                ->helperText('Zet hier eventueel uitleg neer over dit veld')
                ->maxLength(255)
                ->rules([
                    'max:255',
                ]),
            Toggle::make('required')
                ->label('Verplicht in te vullen')
                ->when(fn ($get) => ! in_array($get('type'), ['info', 'image'])),
            Toggle::make('stack_start')
                ->label('Start van de stack'),
            Toggle::make('stack_end')
                ->label('Einde van de stack'),
            Textarea::make('description')
                ->label('Descriptie')
                ->maxLength(500)
                ->required(fn ($get) => in_array($get('type'), ['info']))
                ->when(fn ($get) => in_array($get('type'), ['info', 'select-image']))
                ->rules([
                    'max:500',
                ]),
            Repeater::make('options')
                ->label('Opties')
                ->required(fn ($get) => in_array($get('type'), ['checkbox', 'radio', 'select']))
                ->when(fn ($get) => in_array($get('type'), ['checkbox', 'radio', 'select']))
                ->orderable()
                ->schema([
                    TextInput::make('name')
                        ->label('Naam')
                        ->maxLength(255)
                        ->required()
                        ->rules([
                            'required',
                            'max:255',
                        ]),
                ])
                ->rules([
                    'required',
                ]),
            Repeater::make('images')
                ->label('Afbeeldingen')
                ->required(fn ($get) => in_array($get('type'), ['select-image']))
                ->when(fn ($get) => in_array($get('type'), ['select-image']))
                ->orderable()
                ->schema([
                    TextInput::make('name')
                        ->label('Naam')
                        ->maxLength(255)
                        ->rules([
                            'max:255',
                        ]),
                    FileUpload::make('image')
                        ->label('Afbeelding')
                        ->required()
                        ->image()
                        ->directory('dashed/quotations')
                        ->rules([
                            'required',
                            'image',
                        ]),
                ])
                ->rules([
                    'required',
                ]),
            FileUpload::make('image')
                ->label('Afbeelding')
                ->required(fn ($get) => in_array($get('type'), ['image']))
                ->when(fn ($get) => in_array($get('type'), ['image']))
                ->image()
                ->directory('dashed/quotations')
                ->rules([
                    'required',
                    'image',
                ]),
        ];

        foreach (MailingProviders::cases() as $provider) {
            $provider = $provider->getClass();
            if ($provider->connected) {
                $repeaterSchema = array_merge($repeaterSchema, $provider->getFormFieldSchema());
            }
        }

        $schema[] = Repeater::make('fields')
            ->relationship('fields')
            ->label('Velden')
            ->orderable()
            ->cloneable()
            ->schema($repeaterSchema)
            ->mutateRelationshipDataBeforeSaveUsing(function (array $data, Model $record, $livewire) {
                if ($data['options'] ?? false) {
                    $content = $data['options'];
                    $data['options'] = null;
                    $data['options'][$livewire->activeFormLocale] = $content;
                }
                if ($data['images'] ?? false) {
                    $content = $data['images'];
                    $data['images'] = null;
                    $data['images'][$livewire->activeFormLocale] = $content;
                }

                return $data;
            })
            ->mutateRelationshipDataBeforeCreateUsing(function (array $data, Model $record, $livewire) {
                if ($data['options'] ?? false) {
                    $content = $data['options'];
                    $data['options'] = null;
                    $data['options'][$livewire->activeFormLocale] = $content;
                }
                if ($data['images'] ?? false) {
                    $content = $data['images'];
                    $data['images'] = null;
                    $data['images'][$livewire->activeFormLocale] = $content;
                }

                return $data;
            })
            ->columns([
                'default' => 1,
                'lg' => 2,
            ])
            ->columnSpan(2);

        return $form
            ->schema($schema);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Naam')
                    ->formatStateUsing(fn ($state) => ucfirst($state))
                    ->sortable()
                    ->searchable(),
                TextColumn::make('amount_of_requests')
                    ->label('Aantal aanvragen')
                    ->getStateUsing(fn ($record) => $record->inputs->count()),
                TextColumn::make('amount_of_unviewed_requests')
                    ->label('Aantal openstaande aanvragen')
                    ->getStateUsing(fn ($record) => $record->inputs()->unviewed()->count()),
            ])
            ->actions([
                EditAction::make(),
                Action::make('viewInputs')
                    ->label('Bekijk aanvragen')
                    ->icon('heroicon-s-eye')
                    ->url(fn ($record) => route('filament.resources.forms.viewInputs', [$record])),
            ])
            ->filters([
                //
            ]);
    }

    public static function getRelations(): array
    {
        return [
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListForm::route('/'),
            'create' => CreateForm::route('/create'),
            'edit' => EditForm::route('/{record}/edit'),
            'viewInputs' => ViewForm::route('/{record}/inputs'),
            'viewInput' => ViewFormInput::route('/{record}/inputs/{formInput}'),
        ];
    }
}
