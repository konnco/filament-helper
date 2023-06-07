<?php

namespace Konnco\FilamentHelper\Tables\Actions\Action;

use Closure;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\Action;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;
use Konnco\FilamentHelper\Exports\Export;

class ExportAction extends Action
{
    /**
     * Should applying filter from Filament Table
     */
    protected bool $shouldApplyTableFilter = true;

    /**
     * Modify current query
     */
    protected ?Closure $modifyQueryUsing = null;

    /**
     * Minimum threshold the exporter should work on queue or not
     */
    protected int $shouldQueueWhen = 500;

    /**
     * Basic query builder
     */
    protected ?Builder $query = null;

    /**
     * Headings
     */
    protected array $headings = [];

    /**
     * Rows Builder
     */
    protected ?Closure $rows;

    /**
     * Should on what queue we should put this
     */
    protected ?string $onQueue;

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('Export to Excel');
        $this->icon('heroicon-o-download');

        $this->form([
            TextInput::make('name')
                ->label('Filename')
                ->placeholder('Ex: Success Transaction'),
        ]);

        $this->action(function (array $data) {
            $filename = str($data['name'])->slug().'-'.now()->timestamp.'.xlsx';

            $this->prepareQuery();
            $exporter = new Export(
                query: $this->query,
                headings: $this->headings,
                map: $this->rows
            );

            $user = auth()->user();

            if ($this->shouldNotQueue()) {
                return $exporter->download($filename);
            }

            /**
             * we queue the exporter
             */
            $this->notifyUserExportHasQueued($filename);
            $exporter->queue($filename, 'excel')
                ->onQueue($this->getOnQueueName())
                ->chain([
                    function () use ($filename, $user) {
                        Notification::make()
                            ->icon('heroicon-o-collection')
                            ->title("$filename is Ready!")
                            ->body("Your file (**$filename**) ready to download. The file are available to download only for 24 hours since it's ready to download.")
                            ->success()
                            ->actions([
                                \Filament\Notifications\Actions\Action::make('Download')
                                    ->label('Download')
                                    ->url(Storage::disk('excel')->url($filename))
                                    ->openUrlInNewTab(),
                            ])
                            ->sendToDatabase($user)
                            ->broadcast($user);
                    },
                ]);
        });

        $this->requiresConfirmation();
    }

    /**
     * Returning static
     */
    public function headings($headings = []): static
    {
        $this->headings = $headings;

        return $this;
    }

    /**
     * Modify query
     */
    public function modifyQueryUsing(Closure $query): static
    {
        $this->modifyQueryUsing = $query;

        return $this;
    }

    /**
     * @return mixed
     */
    protected function getModelPrimaryKeyName()
    {
        return (new (invade($this->getLivewire())->getModel()))->getKeyName();
    }

    public function getModelLatestId(): int|string
    {
        return $this->getModel()::latest($this->getModelPrimaryKeyName())
            ?->first()
            ?->{$this->getModelPrimaryKeyName()} ?? 0;
    }

    public function prepareQuery(): void
    {
        $this->query = $this->getModel()::query();
        $this->query->where($this->getModelPrimaryKeyName(), '<=', $this->getModelLatestId());

        /**
         * Applying custom filters from filament tables
         */
        if ($this->shouldApplyTableFilter) {
            $table = invade($this->getLivewire());
            $table->applyFiltersToTableQuery($this->query);
            $table->applySearchToTableQuery($this->query);
        }

        $closure = $this->modifyQueryUsing;
        if ($closure) {
            tap($this->query, fn () => $closure($this->query));
        }
    }

    /**
     * @return $this
     */
    public function shouldQueueWhen($recordCount): static
    {
        $this->shouldQueueWhen = $recordCount;

        return $this;
    }

    private function shouldQueue(): bool
    {
        return $this->query->count() > $this->shouldQueueWhen;
    }

    private function shouldNotQueue(): bool
    {
        return ! $this->shouldQueue();
    }

    public function onQueue($onQueue): static
    {
        $this->onQueue = $onQueue;

        return $this;
    }

    public function getOnQueueName(): ?string
    {
        return $this->onQueue ?: null;
    }

    /**
     * @return $this
     */
    public function rows(Closure $rows): static
    {
        $this->rows = $rows;

        return $this;
    }

    public function notifyUserExportHasQueued(string $filename): void
    {
        Notification::make()
            ->icon('heroicon-o-collection')
            ->title("Queued : $filename")
            ->body("Your file (**$filename**) is currently in the creation **queue**, please wait. This may take a while depending on the size of your request. <br/><br/> we will notify you when it's ready. Cheers!")
            ->success()
            ->sendToDatabase(auth()->user())
            ->broadcast(auth()->user());
    }
}
