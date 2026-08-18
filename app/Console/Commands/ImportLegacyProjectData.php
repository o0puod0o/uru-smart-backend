<?php

namespace App\Console\Commands;

use App\Models\{HasJournal,Proposal,Report,User};
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportLegacyProjectData extends Command
{
    protected $signature = 'project:import {file : JSON export file} {--user= : Owner user ID}';
    protected $description = 'Import journals, proposals and reports from legacy/LocalStorage JSON';

    public function handle(): int
    {
        $path = $this->argument('file');
        $owner = User::find($this->option('user'));
        if (! is_file($path) || ! $owner) {
            $this->error('Valid JSON file and --user are required.');
            return self::FAILURE;
        }

        $payload = json_decode(file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);
        DB::transaction(function () use ($payload, $owner): void {
            foreach ($payload['journals'] ?? [] as $row) {
                HasJournal::create([
                    'user_id'=>$owner->id, 'name'=>$row['name'] ?? $row['title'],
                    'year'=>(string)($row['year'] ?? date('Y')), 'journal_type_id'=>$row['journal_type_id'] ?? 1,
                    'url'=>$row['url'] ?? null, 'status'=>$row['status'] ?? 'draft',
                    'budget'=>$row['budget'] ?? null, 'dateAdd'=>now(),
                ]);
            }
            foreach ($payload['proposals'] ?? [] as $row) {
                Proposal::create(array_merge(collect($row)->only(['title','summary','year','status','budget'])->all(), ['owner_user_id'=>$owner->id]));
            }
            foreach ($payload['reports'] ?? [] as $row) {
                Report::create(array_merge(collect($row)->only(['proposal_id','title','content','status'])->all(), ['owner_user_id'=>$owner->id]));
            }
        });
        $this->info('Import completed.');
        return self::SUCCESS;
    }
}
