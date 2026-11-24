<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Filament\Support\Translatable;
use Filament\Actions\Action;
use Filament\Forms\Components\Field;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Base for admin pages that edit a single-row model (site settings, homepage).
 */
abstract class SingletonSettingsPage extends Page
{
    /** @var array<string, mixed>|null */
    public ?array $data = [];

    abstract protected function getRecord(): Model;

    /**
     * @return array<Component|Field>
     */
    abstract protected function getFormComponents(): array;

    public function mount(): void
    {
        $record = $this->getRecord();

        $this->form->fill(Translatable::fill($record->attributesToArray(), $record));
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components($this->getFormComponents())
            ->model($this->getRecord())
            ->operation('edit')
            ->statePath('data');
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Form::make([EmbeddedSchema::make('form')])
                ->id('form')
                ->livewireSubmitHandler('save')
                ->footer([
                    Actions::make([
                        Action::make('save')
                            ->label(__('admin.save'))
                            ->submit('save')
                            ->keyBindings(['mod+s']),
                    ])->key('form-actions'),
                ]),
        ]);
    }

    public function save(): void
    {
        $record = $this->getRecord();

        DB::transaction(function () use ($record): void {
            $data = $this->form->getState();
            $record->fill($data)->save();
        });

        $record->refresh();
        $this->form->fill(Translatable::fill($record->attributesToArray(), $record));

        Notification::make()->success()->title(__('admin.saved'))->send();
    }
}
