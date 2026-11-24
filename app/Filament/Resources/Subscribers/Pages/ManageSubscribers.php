<?php

declare(strict_types=1);

namespace App\Filament\Resources\Subscribers\Pages;

use App\Filament\Resources\Subscribers\SubscriberResource;
use App\Models\Subscriber;
use Filament\Actions\Action;
use Filament\Resources\Pages\ManageRecords;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ManageSubscribers extends ManageRecords
{
    protected static string $resource = SubscriberResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('exportCsv')
                ->label(__('admin.common.export_csv'))
                ->icon('heroicon-o-arrow-down-tray')
                ->action(fn (): StreamedResponse => self::csvDownload()),
        ];
    }

    /** CSV of all subscribers, e.g. for importing into a mailing tool. */
    public static function csvDownload(): StreamedResponse
    {
        return response()->streamDownload(function (): void {
            $output = fopen('php://output', 'w');
            fputcsv($output, ['email', 'locale', 'subscribed_at']);

            Subscriber::query()->orderBy('id')->lazy()->each(function (Subscriber $subscriber) use ($output): void {
                fputcsv($output, [
                    self::csvSafe($subscriber->email),
                    $subscriber->locale,
                    $subscriber->created_at?->toDateTimeString(),
                ]);
            });

            fclose($output);
        }, 'subscribers-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /** Prevent spreadsheet formula injection from user-supplied values. */
    private static function csvSafe(string $value): string
    {
        return preg_match('/^[=+\-@\t\r]/', $value) ? "'".$value : $value;
    }
}
