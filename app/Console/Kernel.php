protected function schedule($schedule)
{
    $schedule->command('companies:check-expiry')->daily();
}